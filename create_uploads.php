<?php
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';

if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0777, true)) {
        echo "上传目录创建成功: " . $uploadDir;
    } else {
        echo "上传目录创建失败，错误信息：" . error_get_last()['message'];
    }
} else {
    echo "上传目录已存在: " . $uploadDir;
}

// 确保目录权限正确
if (chmod($uploadDir, 0777)) {
    echo "\n目录权限已设置为 777";
} else {
    echo "\n设置目录权限失败，错误信息：" . error_get_last()['message'];
}

// 创建 .gitkeep 文件
$gitkeepFile = $uploadDir . DIRECTORY_SEPARATOR . '.gitkeep';
if (file_put_contents($gitkeepFile, '') !== false) {
    echo "\n.gitkeep 文件创建成功";
} else {
    echo "\n.gitkeep 文件创建失败";
}
?> 