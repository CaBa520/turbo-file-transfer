<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件传输柜</title>
    <link rel="stylesheet" href="/public/assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="transfer-box">
            <h1>文件传输柜</h1>
            
            <div class="tabs">
                <button class="tab-btn active" data-tab="send">发送</button>
                <button class="tab-btn" data-tab="receive">接收</button>
            </div>

            <div class="tab-content active" id="send">
                <form action="/public/index.php/file/upload" method="post" enctype="multipart/form-data" id="uploadForm">
                    <div class="upload-area" id="uploadArea">
                        <input type="file" id="file" name="file" accept="image/*,.pdf,.zip,.rar,.txt">
                        <div class="upload-content">
                            <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <p class="upload-text">拖放文件到这里或点击选择</p>
                            <p class="selected-file"></p>
                        </div>
                    </div>
                    <textarea name="message" placeholder="添加备注信息（可选）"></textarea>
                    
                    <div class="form-group">
                        <label>文件有效期：</label>
                        <select name="expire_minutes" class="expire-select">
                            <option value="5" selected>5分钟</option>
                            <option value="30">30分钟</option>
                            <option value="60">1小时</option>
                            <option value="1440">1天</option>
                            <option value="4320">3天</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>下载次数限制：</label>
                        <select name="download_limit" class="expire-select">
                            <option value="1" selected>1次</option>
                            <option value="3">3次</option>
                            <option value="7">7次</option>
                            <option value="0">无限制</option>
                        </select>
                    </div>

                    <div class="captcha-group">
                        <div class="captcha-wrapper">
                            <input type="text" name="captcha" placeholder="请输入验证码" maxlength="4" required>
                            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" 
                                 alt="验证码" class="captcha-image" title="点击刷新验证码">
                            <button type="button" class="refresh-btn" onclick="refreshCaptcha(this.previousElementSibling)" title="刷新验证码">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 2v6h-6"></path>
                                    <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                                    <path d="M3 22v-6h6"></path>
                                    <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">生成提取码</button>
                </form>
            </div>

            <div class="tab-content" id="receive">
                <form id="receiveForm" method="post">
                    <input type="text" name="code" placeholder="请输入提取码">
                    <div class="captcha-group">
                        <div class="captcha-wrapper">
                            <input type="text" name="captcha" placeholder="请输入验证码" maxlength="4" required>
                            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" 
                                 alt="验证码" class="captcha-image" title="点击刷新验证码">
                            <button type="button" class="refresh-btn" onclick="refreshCaptcha(this.previousElementSibling)" title="刷新验证码">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 2v6h-6"></path>
                                    <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                                    <path d="M3 22v-6h6"></path>
                                    <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn">获取文件</button>
                </form>
            </div>
        </div>
        
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-info">
                    <p class="copyright">© 2024 文件传输柜. 保留所有权利.</p>
                </div>
                <div class="usage-limits">
                    <p>使用限制：单文件最大100MB | 有效期最长3天 | 最多下载7次</p>
                </div>
                <div class="author-info">
                    <p>Designed & Developed by <a href="https://icaba.top" target="_blank">CaBa</a></p>
                </div>
            </div>
        </footer>
    </div>

    <div class="modal" id="resultModal">
        <div class="modal-content">
            <div class="modal-title">文件上传成功</div>
            <div class="modal-body">
                <p>您的文件已成功上传，请保存以下提取码：</p>
                <div class="code-container">
                    <div class="modal-code" id="codeDisplay"></div>
                    <button class="copy-btn" onclick="copyCode()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-expires" id="expiresDisplay"></div>
            </div>
            <button class="modal-btn" onclick="closeModal()">确定</button>
        </div>
    </div>

    <div class="modal terms-modal" id="termsModal">
        <div class="modal-content">
            <div class="modal-title">使用条款</div>
            <div class="modal-body terms-body">
                <h3>1. 服务说明</h3>
                <p>文件传输柜提供临时文件存储和分享服务，仅供用户进行合法文件的临时传输使用。</p>
                
                <h3>2. 使用限制</h3>
                <ul>
                    <li>单个文件大小不超过100MB</li>
                    <li>文件保存时间最长3天</li>
                    <li>单个文件最多下载7次</li>
                    <li>每小时最多上传10个文件</li>
                </ul>
                
                <h3>3. 禁止内容</h3>
                <p>严禁上传、传输下列类型的文件：</p>
                <ul>
                    <li>违反法律法规的内容</li>
                    <li>侵犯他人知识产权的内容</li>
                    <li>含有病毒或恶意代码的文件</li>
                    <li>其他可能造成危害的内容</li>
                </ul>
                
                <h3>4. 免责声明</h3>
                <p>本服务不对用户上传的文件内容负责，不保证服务的连续性和可用性，不对因使用本服务造成的任何损失承担责任。</p>
            </div>
            <button class="modal-btn" onclick="closeTermsModal()">我知道了</button>
        </div>
    </div>

    <script src="/public/assets/js/main.js"></script>
</body>
</html> 