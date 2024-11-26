<?php

require_once __DIR__ . '/../config/database.php';

// 清理过期文件
$stmt = $db->query("
    SELECT code, mime_type 
    FROM files 
    WHERE expires_at < NOW() 
    AND is_deleted = 0
");

while ($file = $stmt->fetch()) {
    $extension = $allowedTypes[$file['mime_type']] ?? '';
    $filePath = __DIR__ . '/../public/uploads/' . $file['code'] . '.' . $extension;
    
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    $db->prepare("UPDATE files SET is_deleted = 1 WHERE code = ?")->execute([$file['code']]);
}

// 清理30天前的已删除记录
$db->exec("DELETE FROM files WHERE is_deleted = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"); 