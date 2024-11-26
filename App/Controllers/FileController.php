<?php

namespace App\Controllers;

class FileController
{
    private $uploadDir;
    private $db;
    private $maxFileSize = 104857600; // 100MB
    private $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
        'application/zip' => 'zip',
        'application/x-rar-compressed' => 'rar',
        'text/plain' => 'txt'
    ];
    private $maxUploadsPerHour = 10;

    public function __construct()
    {
        $this->uploadDir = str_replace('/', DIRECTORY_SEPARATOR, dirname(dirname(__DIR__)) . '/public/uploads/');

        if (!file_exists($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0777, true)) {
                error_log("Failed to create upload directory: " . $this->uploadDir);
                throw new \Exception('无法创建上传目录');
            }
        }

        chmod($this->uploadDir, 0777);

        try {
            $this->db = require_once dirname(dirname(__DIR__)) . '/config/database.php';
        } catch (\Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new \Exception('数据库连接失败');
        }

        error_log("Upload directory: " . $this->uploadDir);
        error_log("Directory exists: " . (file_exists($this->uploadDir) ? 'yes' : 'no'));
        error_log("Directory writable: " . (is_writable($this->uploadDir) ? 'yes' : 'no'));
        error_log("Directory permissions: " . substr(sprintf('%o', fileperms($this->uploadDir)), -4));
    }

    public function upload()
    {
        try {
            error_log("=== Upload Start ===");
            error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
            error_log("Files: " . print_r($_FILES, true));
            error_log("Upload Directory: " . $this->uploadDir);

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Invalid request method');
            }

            if (empty($_FILES['file'])) {
                throw new \Exception('No file uploaded');
            }

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception($this->getUploadErrorMessage($file['error']));
            }

            if ($file['size'] > $this->maxFileSize) {
                throw new \Exception('File is too large');
            }

            // 使用新的 MIME 类型检测方法
            $mimeType = $this->getMimeType($file['tmp_name']);
            error_log("Detected MIME type: " . $mimeType);

            if (!isset($this->allowedTypes[$mimeType])) {
                throw new \Exception('Unsupported file type: ' . $mimeType);
            }

            $code = $this->generateUniqueCode();
            $extension = $this->allowedTypes[$mimeType];
            $newFilename = $code . '.' . $extension;
            $targetPath = $this->uploadDir . $newFilename;

            error_log("Target path: " . $targetPath);

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                error_log("Failed to move file: " . error_get_last()['message']);
                throw new \Exception('Failed to save file');
            }

            chmod($targetPath, 0644);

            // 验证验证码
            $captchaCode = $_POST['captcha'] ?? '';
            if (!$this->validateCaptcha($captchaCode)) {
                throw new \Exception('验证码错误或已过期');
            }

            // 获取过期时间（分钟）
            $expireMinutes = min(4320, max(5, intval($_POST['expire_minutes'] ?? 5)));
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expireMinutes} minutes"));

            // 获取下载次数限制
            $downloadLimit = intval($_POST['download_limit'] ?? 0);
            $downloadsRemaining = $downloadLimit > 0 ? $downloadLimit : 0;

            $stmt = $this->db->prepare("
                INSERT INTO files (
                    code, filename, message, created_at, expires_at, 
                    file_size, mime_type, upload_ip, download_limit, downloads_remaining
                ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)
            ");

            if (
                !$stmt->execute([
                    $code,
                    $file['name'],
                    $_POST['message'] ?? '',
                    $expiresAt,
                    $file['size'],
                    $mimeType,
                    $_SERVER['REMOTE_ADDR'],
                    $downloadLimit,
                    $downloadsRemaining
                ])
            ) {
                throw new \Exception('Failed to save to database');
            }

            error_log("File uploaded successfully: " . $code);
            return json_encode([
                'success' => true,
                'code' => $code,
                'expires_at' => $expiresAt
            ]);

        } catch (\Exception $e) {
            error_log("Upload error: " . $e->getMessage());
            return json_encode(['error' => $e->getMessage()]);
        }
    }
    private function getMimeType($filePath)
    {
        // 尝试使用 fileinfo
        if (\function_exists('finfo_open')) {
            try {
                $finfo = \finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mimeType = \finfo_file($finfo, $filePath);
                    \finfo_close($finfo);
                    if ($mimeType) {
                        return $mimeType;
                    }
                }
            } catch (\Exception $e) {
                error_log("Fileinfo error: " . $e->getMessage());
            }
        }

        // 尝试使用 mime_content_type
        if (\function_exists('mime_content_type')) {
            try {
                $mimeType = \mime_content_type($filePath);
                if ($mimeType) {
                    return $mimeType;
                }
            } catch (\Exception $e) {
                error_log("mime_content_type error: " . $e->getMessage());
            }
        }

        // 基于文件扩展名的后备方案
        $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $extensionMimeTypes = [
            'txt' => 'text/plain',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];

        if (isset($extensionMimeTypes[$extension])) {
            error_log("Using extension-based MIME type detection: " . $extension);
            return $extensionMimeTypes[$extension];
        }

        throw new \Exception('Could not determine file type');
    }

    public function check()
    {
        try {
            error_log("\n=== Check Start ===");
            $data = json_decode(file_get_contents('php://input'), true);
            error_log("Request data: " . print_r($data, true));

            $code = $data['code'] ?? '';

            if (empty($code)) {
                throw new \Exception('请输入提取码');
            }

            // 验证验证码
            $captchaCode = $data['captcha'] ?? '';
            error_log("Captcha code: " . $captchaCode);

            if (!$this->validateCaptcha($captchaCode)) {
                throw new \Exception('验证码错误或已过期');
            }

            // 从数据库获取文件信息
            $stmt = $this->db->prepare("
                SELECT filename, file_size, message, created_at, expires_at, is_deleted, code, mime_type, download_limit, downloads_remaining 
                FROM files 
                WHERE code = ?
            ");
            $stmt->execute([$code]);
            $file = $stmt->fetch();

            error_log("Database result: " . print_r($file, true));

            if (!$file) {
                throw new \Exception('文件不存在');
            }

            if ($file['is_deleted']) {
                throw new \Exception('文件已被删除');
            }

            // 检查文件是否存在
            $filePath = $this->uploadDir . $code . '.' . $this->allowedTypes[$file['mime_type']];
            if (!file_exists($filePath)) {
                error_log("Physical file not found: " . $filePath);
                throw new \Exception('文件已被删除');
            }

            // 检查是否过期
            if (strtotime($file['expires_at']) < time()) {
                // 标记文件为已删除
                $stmt = $this->db->prepare("UPDATE files SET is_deleted = 1 WHERE code = ?");
                $stmt->execute([$code]);

                // 删除实际文件
                $this->deletePhysicalFile($code);

                throw new \Exception('文件已过期');
            }

            // 检查下载次数限制
            if ($file['download_limit'] > 0 && $file['downloads_remaining'] <= 0) {
                throw new \Exception('文件下载次数已用完');
            }

            $result = [
                'success' => true,
                'filename' => $file['filename'],
                'size' => $file['file_size'],
                'message' => $file['message'],
                'created_at' => $file['created_at'],
                'expires_at' => $file['expires_at'],
                'code' => $file['code'],
                'download_limit' => $file['download_limit'],
                'downloads_remaining' => $file['downloads_remaining']
            ];

            error_log("Sending response: " . json_encode($result));
            return json_encode($result);

        } catch (\Exception $e) {
            error_log("Check error: " . $e->getMessage());
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    public function download($code)
    {
        try {
            if (empty($code)) {
                throw new \Exception('请输入提取码');
            }

            // 从数据库获取文件信息
            $stmt = $this->db->prepare("
                SELECT * FROM files 
                WHERE code = ? 
                AND is_deleted = 0 
                AND expires_at > NOW()
            ");
            $stmt->execute([$code]);
            $file = $stmt->fetch();

            if (!$file) {
                throw new \Exception('文件不存在或已过期');
            }

            $filePath = $this->uploadDir . $code . '.' . $this->allowedTypes[$file['mime_type']];

            if (!file_exists($filePath)) {
                // 如果文件不存在，标记为已删除
                $stmt = $this->db->prepare("UPDATE files SET is_deleted = 1 WHERE code = ?");
                $stmt->execute([$code]);
                throw new \Exception('文件已被删除');
            }

            // 检查下载次数限制
            if ($file['download_limit'] > 0 && $file['downloads_remaining'] <= 0) {
                throw new \Exception('文件下载次数已用完');
            }

            // 更新下载次数和剩余次数
            $stmt = $this->db->prepare("
                UPDATE files 
                SET downloads = downloads + 1,
                    downloads_remaining = CASE 
                        WHEN download_limit > 0 THEN downloads_remaining - 1 
                        ELSE downloads_remaining 
                    END
                WHERE code = ?
            ");
            $stmt->execute([$code]);

            // 设置下载头
            header('Content-Type: ' . $file['mime_type']);
            header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
            header('Content-Length: ' . filesize($filePath));
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');

            readfile($filePath);
            exit;

        } catch (\Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    private function generateUniqueCode($length = 6)
    {
        do {
            $code = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM files WHERE code = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetchColumn() > 0);

        return $code;
    }

    private function isUploadLimitExceeded()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM files 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND upload_ip = ?
        ");
        $stmt->execute([$ip]);
        return $stmt->fetchColumn() >= $this->maxUploadsPerHour;
    }

    private function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return '文件超过了php.ini中upload_max_filesize的限制';
            case UPLOAD_ERR_FORM_SIZE:
                return '文件超过了表单中MAX_FILE_SIZE的限制';
            case UPLOAD_ERR_PARTIAL:
                return '文件只有部分被上传';
            case UPLOAD_ERR_NO_FILE:
                return '没有文件被上传';
            case UPLOAD_ERR_NO_TMP_DIR:
                return '找不到临时文件夹';
            case UPLOAD_ERR_CANT_WRITE:
                return '文件写入失败';
            case UPLOAD_ERR_EXTENSION:
                return '文件上传被PHP扩展停止';
            default:
                return '未知上传错误';
        }
    }

    private function deletePhysicalFile($code)
    {
        try {
            $stmt = $this->db->prepare("SELECT mime_type FROM files WHERE code = ?");
            $stmt->execute([$code]);
            $file = $stmt->fetch();

            if ($file) {
                $filePath = $this->uploadDir . $code . '.' . $this->allowedTypes[$file['mime_type']];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        } catch (\Exception $e) {
            error_log("Error deleting physical file: " . $e->getMessage());
        }
    }

    private function validateCaptcha($captchaCode)
    {
        if (empty($captchaCode)) {
            error_log("Empty captcha code");
            return false;
        }

        $captcha = new \App\Utils\Captcha();
        $result = $captcha->verify($captchaCode);
        error_log("Captcha validation result: " . ($result ? 'true' : 'false'));
        return $result;
    }

    public function getCaptcha()
    {
        try {
            $captcha = new \App\Utils\Captcha();
            $result = $captcha->generate();
            return json_encode([
                'success' => true,
                'image' => 'data:image/png;base64,' . $result['image'],
                'expire' => $result['expire']
            ]);
        } catch (\Exception $e) {
            error_log("Captcha generation error: " . $e->getMessage());
            return json_encode(['error' => '生成验证码失败']);
        }
    }
}