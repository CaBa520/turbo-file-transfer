<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PHP Upload Settings</h2>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";

echo "<h2>Upload Directory Check</h2>";
$uploadDir = __DIR__ . '/public/uploads/';
echo "Upload directory path: " . $uploadDir . "<br>";
echo "Directory exists: " . (file_exists($uploadDir) ? 'Yes' : 'No') . "<br>";
if (file_exists($uploadDir)) {
    echo "Directory permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "<br>";
    echo "Directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "<br>";
    echo "Directory owner: " . getmyuid() . "<br>";
    echo "PHP process owner: " . get_current_user() . "<br>";
}

echo "<h2>Database Connection</h2>";
try {
    $db = require_once __DIR__ . '/config/database.php';
    echo "Database connection: Success<br>";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

phpinfo(); 