1. # 文件传输柜

   一个简单、安全、高效的临时文件传输系统。

   ## 目录结构

   ```
   file_transfer/
   ├── App/ # 应用程序核心代码
   │ ├── Controllers/ # 控制器
   │ │ ├── FileController.php
   │ │ └── AdminController.php
   │ └── Utils/ # 工具类
   │ └── Captcha.php
   ├── config/ # 配置文件
   │ └── database.php
   ├── public/ # 公共访问目录
   │ ├── assets/ # 静态资源
   │ │ ├── css/
   │ │ │ └── style.css
   │ │ └── js/
   │ │ └── main.js
   │ ├── uploads/ # 文件上传目录
   │ ├── .htaccess
   │ └── index.php
   ├── resources/ # 视图文件
   │ └── views/
   │ ├── 404.php
   │ ├── 500.php
   │ └── home.php
   ├── scripts/ # 脚本文件
   │ ├── auto_cleanup.php
   │ └── cleanup.php
   ├── .gitignore
   ├── create_uploads.php # 创建上传目录并设置777权限
   ├── run_cleanup.php # 定时清理数据库以及上传目录中过期/下载次数为0的文件数据
   ├── index.php # 跳转至/public/目录
   ├── test_web_phpDatas.php # 调试文件
   ├── .htaccess
   └── README.md
   ```

   

   ## 功能特点

   - 文件上传和下载
   - 自动生成提取码
   - 验证码安全验证
   - 文件有效期控制
   - 下载次数限制
   - 自动清理过期文件
   - 支持文件备注信息
   - 拖拽上传支持

   ## 技术栈

   - PHP 7.4+
   - MySQL 5.7+
   - HTML5
   - CSS3
   - JavaScript (原生)

   ## 使用限制

   - 单文件最大：100MB
   - 支持格式：jpg、png、pdf、zip、rar、txt
   - 有效期选项：5分钟、30分钟、1小时、1天、3天
   - 下载限制：1次、3次、7次、无限制

   ## 安装部署

   1. git clone https://github.com/CaBa052/file-transfer.git
   
      ```无需改/public目录,但是PHP需要安装fileinfo扩展```

   2. 创建数据库
   
      ```sql
      CREATE DATABASE file_transfer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
      
      USE file_transfer;
      
      CREATE TABLE files (
      
      id INT AUTO_INCREMENT PRIMARY KEY,
      
      code VARCHAR(10) NOT NULL UNIQUE,
      
      filename VARCHAR(255) NOT NULL,
      
      message TEXT,
      
      downloads INT DEFAULT 0,
      
      is_deleted TINYINT(1) DEFAULT 0,
      
      created_at DATETIME NOT NULL,
      
      expires_at DATETIME,
      
      file_size BIGINT NOT NULL DEFAULT 0,
      
      mime_type VARCHAR(100) NOT NULL,
      
      upload_ip VARCHAR(45) NOT NULL,
      
      download_limit INT DEFAULT 0,
      
      downloads_remaining INT DEFAULT 0,
      
      INDEX idx_code (code),
      
      INDEX idx_created_at (created_at),
      
      INDEX idx_expires_at (expires_at)
      
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
      ```

   3. 配置数据库连接

      编辑 `config/database.php` 文件：
   
      ```php
      <?php
      return new PDO(
      'mysql:host=localhost;dbname=file_transfer;charset=utf8mb4',
      'your_username',
      'your_password',
      [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
      ]
      );
      ```

   4. 创建上传目录

      bash `php create_uploads.php` 

      ​	

   5. 配置自动清理任务
   
      ```shell
      Linux系统 (crontab)
      0 /12 /usr/bin/php /path/to/your/project/scripts/auto_cleanup.php
      
      Windows系统 (计划任务)
      schtasks /create /sc HOURLY /mo 12 /tn "FileTransferCleanup" /tr "php D:\path\to\your\project\scripts\auto_cleanup.php"
      ```

   ## 安全特性
   
   - 文件名加密存储
   - 验证码防护
   - 防止目录遍历
   - 文件类型限制
   - IP上传频率限制
   - 自动清理机制
   - 下载次数限制
   - 文件有效期控制

   ## 作者

   [CaBa052](https://icaba.top)

   ## 许可证

   MIT License

   ## 免责声明

   本服务仅供文件临时中转使用，不对用户上传的文件内容负责。请勿上传违法或侵权内容。

   ## 更新日志
   
   ### v1.0.0 (2024-11-27)
   - 初始版本发布
   - 基本的文件上传下载功能
   - 验证码安全验证
   - 文件有效期控制
   - 下载次数限制
   - 自动清理机制

   ## 联系方式
   
   - 网站：[https://icaba.top](https://icaba.top)
   - 问题反馈：请通过 GitHub Issues 提交

   ## 鸣谢
   
   感谢所有为本项目提供帮助和建议的朋友们。