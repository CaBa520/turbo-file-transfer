<?php
// 错误处理配置
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

// 定义项目根目录
define('ROOT_PATH', dirname(__DIR__));

// 设置上传限制
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('max_execution_time', 300);

// 启动会话
session_start();

// 自动加载类
spl_autoload_register(function ($class) {
    $file = ROOT_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 设置默认时区
date_default_timezone_set('Asia/Shanghai');

// 记录请求信息
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
if (!empty($_FILES)) {
    error_log("Files: " . print_r($_FILES, true));
}

// 获取请求路径
$requestUri = $_SERVER['REQUEST_URI'];
$publicPos = strpos($requestUri, '/public/index.php');
if ($publicPos !== false) {
    $requestPath = substr($requestUri, $publicPos + strlen('/public/index.php'));
} else {
    $requestPath = substr($requestUri, strpos($requestUri, '/public/') + strlen('/public/'));
}
$requestPath = trim($requestPath, '/');

error_log("Original URI: " . $requestUri);
error_log("Processed Path: " . $requestPath);

// 路由处理
try {
    if (empty($requestPath) || $requestPath === 'index.php') {
        require_once ROOT_PATH . '/resources/views/home.php';
    } else {
        switch ($requestPath) {
            case 'file/upload':
                header('Content-Type: application/json');
                try {
                    $controller = new App\Controllers\FileController();
                    echo $controller->upload();
                } catch (Exception $e) {
                    error_log('Upload error: ' . $e->getMessage());
                    echo json_encode(['error' => '文件上传失败：' . $e->getMessage()]);
                }
                break;
                
            case 'file/check':
                header('Content-Type: application/json');
                try {
                    $controller = new App\Controllers\FileController();
                    echo $controller->check();
                } catch (Exception $e) {
                    error_log('Check error: ' . $e->getMessage());
                    echo json_encode(['error' => '检查文件失败：' . $e->getMessage()]);
                }
                break;
                
            case (preg_match('/^file\/download\/([A-Z0-9]+)$/', $requestPath, $matches) ? true : false):
                $controller = new App\Controllers\FileController();
                echo $controller->download($matches[1]);
                break;
                
            case 'captcha/generate':
            case 'file/captcha/generate':
                header('Content-Type: application/json');
                try {
                    $controller = new App\Controllers\FileController();
                    echo $controller->getCaptcha();
                } catch (Exception $e) {
                    error_log('Captcha error: ' . $e->getMessage());
                    echo json_encode(['error' => '生成验证码失败']);
                }
                break;
                
            default:
                error_log('404 Not Found: ' . $requestPath);
                http_response_code(404);
                require_once ROOT_PATH . '/resources/views/404.php';
        }
    }
} catch (Exception $e) {
    error_log('Server Error: ' . $e->getMessage());
    if (strpos($requestPath, 'file/upload') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => '服务器错误：' . $e->getMessage()]);
    } else {
        http_response_code(500);
        require_once ROOT_PATH . '/resources/views/500.php';
    }
} 