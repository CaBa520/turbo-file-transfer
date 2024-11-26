<?php
namespace App\Utils;

class Captcha {
    private $width = 120;
    private $height = 40;
    private $codeLength = 4;
    private $sessionKey = 'captcha_code';
    private $expireTime = 300; // 5分钟过期

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function generate() {
        $image = imagecreatetruecolor($this->width, $this->height);
        $bgcolor = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgcolor);

        // 生成随机码
        $code = '';
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        for ($i = 0; $i < $this->codeLength; $i++) {
            $code .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        // 保存到session
        $_SESSION[$this->sessionKey] = [
            'code' => $code,
            'expire' => time() + $this->expireTime
        ];

        // 添加干扰线
        for ($i = 0; $i < 6; $i++) {
            $color = imagecolorallocate($image, mt_rand(0, 150), mt_rand(0, 150), mt_rand(0, 150));
            imageline($image, mt_rand(0, $this->width), mt_rand(0, $this->height), 
                     mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }

        // 添加干扰点
        for ($i = 0; $i < 50; $i++) {
            $color = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($image, mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }

        // 修改文字位置，让文字偏上
        for ($i = 0; $i < $this->codeLength; $i++) {
            $color = imagecolorallocate($image, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));
            $fontSize = mt_rand(14, 20);
            $x = ($i * $this->width / $this->codeLength) + mt_rand(5, 10);
            $y = mt_rand($this->height / 4, $this->height / 2);
            imagechar($image, $fontSize, $x, $y, $code[$i], $color);
        }

        // 输出图像
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return [
            'image' => base64_encode($imageData),
            'expire' => $this->expireTime
        ];
    }

    public function verify($code) {
        if (!isset($_SESSION[$this->sessionKey])) {
            return false;
        }

        $captchaData = $_SESSION[$this->sessionKey];
        unset($_SESSION[$this->sessionKey]); // 验证后立即删除

        if (time() > $captchaData['expire']) {
            return false;
        }

        return strtoupper($code) === $captchaData['code'];
    }
} 