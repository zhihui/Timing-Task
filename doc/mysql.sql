--
-- 表的结构 `timing_log`
--

CREATE TABLE `timing_log` (
  `id` int(11) NOT NULL COMMENT '日志ID',
  `task_id` int(11) NOT NULL COMMENT '定时程序APP ID',
  `log_time` int(11) NOT NULL COMMENT '日志时间',
  `log_content` text NOT NULL COMMENT '日志内容'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='定时程序运行日志';

-- --------------------------------------------------------

--
-- 表的结构 `timing_task`
--

CREATE TABLE `timing_task` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `url` varchar(255) NOT NULL COMMENT 'URL',
  `interval_time` int(11) NOT NULL COMMENT '运行时间间隔',
  `timeout` int(11) NOT NULL COMMENT '运行超时时间',
  `start_time` int(11) NOT NULL COMMENT '程序开始时间',
  `last_run_time` int(11) NOT NULL COMMENT '最后运行时间',
  `last_stop_time` int(11) NOT NULL COMMENT '最后运行结束时间',
  `run_status` tinyint(4) NOT NULL,
  `memo` text NOT NULL COMMENT '说明',
  `status` tinyint(4) NOT NULL COMMENT '状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `timing_log`
--
ALTER TABLE `timing_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `app_id` (`task_id`);

--
-- Indexes for table `timing_task`
--
ALTER TABLE `timing_task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `timing_log`
--
ALTER TABLE `timing_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID', AUTO_INCREMENT=1;
--
-- 使用表AUTO_INCREMENT `timing_task`
--
ALTER TABLE `timing_task`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=1;