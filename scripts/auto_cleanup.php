<?php
// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 记录日志函数
function writeLog($message) {
    $logFile = __DIR__ . '/cleanup.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    writeLog("=== 开始清理任务 ===");
    
    // 连接数据库
    $db = require_once __DIR__ . '/../config/database.php';
    
    // 1. 直接删除过期的记录（而不是标记为已删除）
    $stmt = $db->prepare("
        DELETE FROM files 
        WHERE expires_at < NOW() 
        OR (download_limit > 0 AND downloads_remaining <= 0)
    ");
    $result = $stmt->execute();
    $deletedRecords = $stmt->rowCount();
    writeLog("直接删除过期记录: {$deletedRecords} 条");
    
    // 2. 清理物理文件
    $uploadDir = __DIR__ . '/../public/uploads/';
    $physicalFiles = array_diff(scandir($uploadDir), ['.', '..', '.gitkeep']);
    $deletedFiles = 0;
    $errorFiles = 0;
    
    foreach ($physicalFiles as $filename) {
        $filePath = $uploadDir . $filename;
        $fileCode = pathinfo($filename, PATHINFO_FILENAME);
        
        // 检查文件是否在数据库中存在且未过期
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM files 
            WHERE code = ? 
            AND expires_at > NOW() 
            AND (download_limit = 0 OR downloads_remaining > 0)
        ");
        $stmt->execute([$fileCode]);
        $exists = $stmt->fetchColumn() > 0;
        
        if (!$exists) {
            try {
                if (unlink($filePath)) {
                    $deletedFiles++;
                    writeLog("删除文件: {$filename}");
                } else {
                    throw new Exception("删除失败");
                }
            } catch (Exception $e) {
                $errorFiles++;
                writeLog("删除文件失败 {$filename}: " . $e->getMessage());
            }
        }
    }
    
    // 3. 清理数据库中不存在对应物理文件的记录
    $stmt = $db->prepare("
        SELECT code, filename, mime_type 
        FROM files
    ");
    $stmt->execute();
    $dbFiles = $stmt->fetchAll();
    $deletedOrphanRecords = 0;
    
    foreach ($dbFiles as $file) {
        $extension = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'application/x-rar-compressed' => 'rar',
            'text/plain' => 'txt'
        ][$file['mime_type']] ?? '';
        
        $filePath = $uploadDir . $file['code'] . '.' . $extension;
        
        if (!file_exists($filePath)) {
            $stmt = $db->prepare("DELETE FROM files WHERE code = ?");
            $stmt->execute([$file['code']]);
            $deletedOrphanRecords++;
            writeLog("删除孤立数据库记录: {$file['filename']} (Code: {$file['code']})");
        }
    }
    
    writeLog("\n清理任务完成:");
    writeLog("- 删除过期记录: {$deletedRecords} 条");
    writeLog("- 删除物理文件: 成功 {$deletedFiles}, 失败 {$errorFiles}");
    writeLog("- 删除孤立记录: {$deletedOrphanRecords} 条");
    
} catch (Exception $e) {
    writeLog("清理任务失败: " . $e->getMessage());
}

writeLog("=== 清理任务结束 ===\n"); 