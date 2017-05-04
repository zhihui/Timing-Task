<?php
require "./include/common.php";

if (substr(php_sapi_name(), 0, 3) !== 'cli') {
    die("This Programe can only be run in CLI mode");
}

echo "Timing Task Start\n";

// 注册子进程终止信号
pcntl_signal(SIGCHLD, function() {
    //必须为false，非阻塞模式
    while($ret = swoole_process::wait(false)) {
        $pid = $ret["pid"];
        if($worker = worker_get($pid)){
            $json = $worker->read();
            $data = decode_json($json);
            
            task_stop_running($data["task_id"]);
            worker_unset($pid);
            
            $runStatus = TASK_RUN_STATUS_SUCCESS; // 成功
            
            $log = "";
            if($data["error"]["code"]){
                $runStatus= TASK_RUN_STATUS_FAILED; // 失败
                $log .= "CURL ERROR: [{$data["error"]["code"]}] {$data["error"]["message"]}\n";
            }
            
            if(!$data["data"] || substr($data["data"], 0, 20) != "__CRON_RUN_SUCCESS__"){
                $runStatus= TASK_RUN_STATUS_FAILED; // 失败
            }
            
            $log .= $data["data"];
            // 发送数据到负责更新数据的子进程
            push_to_process("updateProcess", array("type" => "stop", "data" => array("task_id" => $data["task_id"], "run_status" => $runStatus)));
            push_to_process("updateProcess", array("type" => "log", "data" => array("task_id" => $data["task_id"], "log" => $log)));
        }
        break;
    }
});
    
    
// 常驻子进程，用来获取最新数据的子进程
$fetchProcess = new swoole_process("interval_fetch_data", FALSE, FALSE);
$fetchProcess->useQueue(0,swoole_process::IPC_NOWAIT);
$fetchProcess->start();

// 从获取数据子进程获取最新的数据，写入变量
$fetchTiming = swoole_timer_tick(1000 , function($fetchTiming) {
    $json = $GLOBALS["fetchProcess"]->pop(10000000000);
    if($json){
        $tasks = decode_json($json);
        set_tasks($tasks);
    }
});
        
// 常驻子进程，用来接收要更新的数据，并更新到数据库
$updateProcess = new swoole_process("interval_update_data", FALSE, FALSE);
$updateProcess->useQueue(0,swoole_process::IPC_NOWAIT);
$updateProcess->start();

// 循环运行tasks
$runningTiming = swoole_timer_tick(50 , function($runningTiming) {
    $tasks = get_tasks();
    foreach ($tasks as $task){
        if(task_is_running($task["id"])){
            continue;
        }
        
        $now = time();
        $lastRunningTime = get_task_time($task["id"]); // 获取任务最后运行的时间
        if($task["start_time"] >= $now || ($lastRunningTime && $lastRunningTime == $now)){
            // 还没到开始时间或者已经运行过了，跳过
            continue;
        }
        
        // 判断是否可以运行了
        if(!is_running_time($task["start_time"], $task["interval_time"])){
            continue;
        }
        
        set_task_time($task["id"], $now); // 缓存任务最后运行的时间
        task_start_running($task["id"]);
        // 发送数据到负责更新数据的子进程
        push_to_process("updateProcess", array("type" => "run", "data" => array("task_id" => $task["id"])));
        
        $process = new swoole_process("exec_task");
        $process->write(encode_json($task));
        $pid = $process->start();
        
        worker_set($pid, $process);
        pcntl_signal_dispatch();
    }
    
    pcntl_signal_dispatch();
});

/**
 * 更新数据的子进程回调函数，接收消息后更新到数据库
 * 
 * @param swoole_process $worker 子进程
 */
function interval_update_data(swoole_process $worker){
    while (1){
        usleep(500);
        // 获取从父进程写入的数据，更新到数据库
        $json = $worker->pop(10000000000);
        if($json){
            $data = decode_json($json);
            switch ($data["type"]){
                case "run":
                    update_task($data["data"]["task_id"], array("last_run_time" => time(), "run_status" => TASK_RUN_STATUS_RUNNING));
                    break;
                case "stop":
                    update_task($data["data"]["task_id"], array("last_stop_time" => time(), "run_status" => $data["data"]["run_status"]));
                    break;
                case "log":
                    add_log($data["data"]["task_id"], $data["data"]["log"]);
                    break;
            }
        }
    }
}

/**
 * 获取数据的子进程回调，每隔一段时间从服务器获取最新的task数据
 * 
 * @param swoole_process $worker
 */
function interval_fetch_data(swoole_process $worker){
    while (1){
        $tasks = fetch_tasks();
        // 写入数据给父进程获取
        $worker->push(encode_json($tasks));
        sleep(30);
    }
}
            