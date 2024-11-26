<?php

try {
    $db = new PDO(
        // file_transfer 数据库名
        'mysql:host=localhost;dbname=file_transfer;charset=utf8mb4',
        'root',     // 用户名
        '123123', // 密码
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    return $db;
} catch (PDOException $e) {
    error_log('数据库连接错误: ' . $e->getMessage());
    die('数据库连接失败: ' . $e->getMessage());
} 