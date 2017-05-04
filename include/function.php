<?php
$tasks = array();
function set_tasks($tasks){
    $GLOBALS["tasks"] = $tasks;
}

function get_tasks(){
    return $GLOBALS["tasks"];
}

// ------------------------------------

$taskTime = array();
function set_task_time($taskId, $time){
    $GLOBALS["taskTime"][$taskId] = $time;
}

function get_task_time($taskId){
    if(isset($GLOBALS["taskTime"][$taskId])){
        return $GLOBALS["taskTime"][$taskId];
    }
}

// ------------------------------------

$running = array();
function task_is_running($taskId){
    return isset($GLOBALS["running"][$taskId]);
}

function task_start_running($taskId){
    $GLOBALS["running"][$taskId] = 1;
}

function task_stop_running($taskId){
    unset($GLOBALS["running"][$taskId]);
}

// ------------------------------------

$workers = array();

function worker_set($pid, $worker){
    $GLOBALS["workers"][$pid] = $worker;
}

function worker_get($pid){
    if(isset($GLOBALS["workers"][$pid])){
        return $GLOBALS["workers"][$pid];
    }
    
    return NULL;
}

function worker_unset($pid){
    unset($GLOBALS["workers"][$pid]);
}

// ------------------------------------

function is_running_time($startTime, $intervalTime){
    $time = time() - $startTime;
    return $time % $intervalTime == 0;
}

function push_to_process($varname, $data){
    $GLOBALS[$varname]->push(encode_json($data));
}


function encode_json($data){
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

function decode_json($json){
    return json_decode($json, 1);
}

function exec_task(swoole_process $worker){
    $config = json_decode($worker->read(), 1);
    $response = request_url($config["url"], $config["timeout"]);
    $response["task_id"] = $config["id"];
    $worker->write(json_encode($response, JSON_UNESCAPED_UNICODE));
    pcntl_signal_dispatch();
}

/**
 * 添加运行日志
 *
 * @param integer $taskId task ID
 * @param string  $log 日志内容
 * @return boolean
 */
function add_log($taskId, $log){
    $db = getdb();
    $data = array();
    $data["task_id"] = $taskId;
    $data["log_time"] = time();
    $data["log_content"] = $log;
    return $db->insert("timing_log", $data);
}

/**
 * 获取需要运行的task
 *
 * @return array
 */
function fetch_tasks(){
    $db = getdb();
    return $db->query("SELECT * FROM timing_task")->fetchAll();
}


/**
 * 获取DB连接
 *
 * @return Database
 */
function getdb($newLink = FALSE){
    static $db = NULL;
    if($newLink || $db == NULL){
        $db = new Database(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DBNAME, MYSQL_CHARSET);
    }
    
    return $db;
}

/**
 * 更新task的信息
 *
 * @param integer $taskId task ID
 * @param array $data 要更新的数据
 */
function update_task($taskId, array $data){
    $db = getdb();
    return $db->update("timing_task", $data, "id=?", array($taskId));
}


/**
 * GET请求task URL
 *
 * @param string $url URL
 * @param integer $timeout 超时秒数
 * @return array
 */
function request_url($url, $timeout){
    $data = array();
    $data["error"]["code"] = 0;
    $data["error"]["message"] = "";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    $errno = curl_errno($ch);
    if($errno){
        $data["error"]["code"] = $errno;
        $data["error"]["message"] = curl_error($ch);
    }
    
    $data["data"] = $result;
    return $data;
}