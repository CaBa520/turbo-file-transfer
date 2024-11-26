<?php
// 设置无限执行时间
set_time_limit(0);
ini_set('memory_limit', '256M');

echo "开始执行清理任务...\n";

// 执行清理脚本
require_once __DIR__ . '/scripts/auto_cleanup.php';

echo "清理任务执行完成！\n";
echo "请查看 scripts/cleanup.log 文件获取详细日志。\n"; 