function showDownloadConfirm(fileInfo) {
    const oldModal = document.querySelector('.download-confirm-modal');
    if (oldModal) {
        oldModal.remove();
    }

    const modal = document.createElement('div');
    modal.className = 'download-confirm-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-title">确认下载</div>
            <div class="modal-body">
                <div class="file-info">
                    <div class="file-info-item">
                        <span class="label">文件名：</span>
                        <span class="value">${fileInfo.filename}</span>
                    </div>
                    <div class="file-info-item">
                        <span class="label">文件大小：</span>
                        <span class="value">${formatFileSize(fileInfo.size)}</span>
                    </div>
                    <div class="file-info-item">
                        <span class="label">上传时间：</span>
                        <span class="value">${new Date(fileInfo.created_at).toLocaleString()}</span>
                    </div>
                    <div class="file-info-item">
                        <span class="label">过期时间：</span>
                        <span class="value">${new Date(fileInfo.expires_at).toLocaleString()}</span>
                    </div>
                    <div class="file-info-item">
                        <span class="label">下载限制：</span>
                        <span class="value ${fileInfo.download_limit > 0 ? 'downloads-remaining' : 'downloads-unlimited'}">
                            ${fileInfo.download_limit > 0 ? `剩余 ${fileInfo.downloads_remaining} 次下载机会` : '无限制'}
                        </span>
                    </div>
                    ${fileInfo.message ? `
                    <div class="file-info-item message-item">
                        <span class="label">备注信息：</span>
                        <span class="value">${fileInfo.message}</span>
                    </div>
                    ` : ''}
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel-btn" onclick="closeDownloadConfirm(this)">取消</button>
                <button class="modal-btn confirm-btn" onclick="confirmDownload('${fileInfo.code}', this)">确认下载</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    requestAnimationFrame(() => modal.classList.add('show'));
}

function closeDownloadConfirm(btn) {
    const modal = btn.closest('.download-confirm-modal');
    modal.classList.remove('show');
    setTimeout(() => modal.remove(), 300);
}

function confirmDownload(code, btn) {
    btn.disabled = true;
    btn.textContent = '下载中...';
    
    if (isMobile()) {
        // 移动端直接开始下载
        showDownloadStatus('downloading');
        window.location.href = `/public/index.php/file/download/${code}`;
        
        // 1.5秒后显示下载成功并关闭对话框
        setTimeout(() => {
            btn.disabled = false;
            btn.textContent = '确认下载';
            closeDownloadConfirm(btn);
            showDownloadStatus('success');
        }, 1500);
        
    } else {
        // 桌面端下载逻辑保持不变
        const downloadLink = document.createElement('a');
        downloadLink.style.display = 'none';
        downloadLink.href = `/public/index.php/file/download/${code}`;
        document.body.appendChild(downloadLink);
        
        showDownloadStatus('downloading');
        downloadLink.click();
        
        setTimeout(() => {
            document.body.removeChild(downloadLink);
        }, 1000);
        
        setTimeout(() => {
            btn.disabled = false;
            btn.textContent = '确认下载';
            closeDownloadConfirm(btn);
            showDownloadStatus('success');
        }, 1000);
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('file');
    const selectedFileText = document.querySelector('.selected-file');
    const uploadForm = document.getElementById('uploadForm');
    
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', () => {
            const tabs = button.closest('.tabs');
            const tabId = button.dataset.tab;
            
            // 更新活动状态
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            button.classList.add('active');
            document.getElementById(tabId).classList.add('active');
            
            // 更新滑块位置
            tabs.dataset.active = tabId;
        });
    });

    if (uploadArea && fileInput) {
        document.addEventListener('dragover', e => {
            e.preventDefault();
            e.stopPropagation();
        });

        document.addEventListener('drop', e => {
            e.preventDefault();
            e.stopPropagation();
        });

        uploadArea.addEventListener('click', e => {
            if (e.target !== fileInput) {
                fileInput.click();
            }
        });

        fileInput.addEventListener('change', async () => {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const allowedTypes = [
                    'image/jpeg', 'image/png', 'application/pdf',
                    'application/zip', 'application/x-rar-compressed', 'text/plain'
                ];
                
                if (!allowedTypes.includes(file.type)) {
                    alert('不支持的文件类型');
                    fileInput.value = '';
                    selectedFileText.textContent = '';
                    return;
                }
                
                const maxSize = 100 * 1024 * 1024; // 100MB
                if (file.size > maxSize) {
                    alert('文件大小不能超过100MB');
                    fileInput.value = '';
                    selectedFileText.textContent = '';
                    return;
                }
                
                selectedFileText.textContent = `已选择: ${file.name} (${formatFileSize(file.size)})`;

                // 如果是图片文件，显示预览
                if (file.type.startsWith('image/')) {
                    const preview = document.createElement('div');
                    preview.className = 'image-preview';
                    
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    preview.appendChild(img);

                    // 在移动端添加保存到相册按钮
                    if (isMobile()) {
                        const saveBtn = document.createElement('button');
                        saveBtn.className = 'save-photo-btn';
                        saveBtn.innerHTML = `
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            保存到相册
                        `;
                        saveBtn.onclick = () => saveImageToGallery(file);
                        preview.appendChild(saveBtn);
                    }

                    const existingPreview = document.querySelector('.image-preview');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    selectedFileText.after(preview);
                }
            }
        });

        uploadArea.addEventListener('dragenter', e => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.add('highlight');
        });

        uploadArea.addEventListener('dragover', e => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.add('highlight');
        });

        uploadArea.addEventListener('dragleave', e => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove('highlight');
        });

        uploadArea.addEventListener('drop', e => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove('highlight');
            
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length) {
                fileInput.files = files;
                if (files[0]) {
                    selectedFileText.textContent = `已选择: ${files[0].name} (${formatFileSize(files[0].size)})`;
                }
            }
        });

        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!fileInput.files.length) {
                alert('请选择要上传的文件');
                return;
            }

            const submitBtn = this.querySelector('.submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = '上传中...';
            
            showUploadStatus('preparing');

            try {
                const formData = new FormData(this);
                const xhr = new XMLHttpRequest();
                
                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        showUploadStatus('uploading', percent);
                    }
                };
                
                xhr.onload = async function() {
                    try {
                        const result = JSON.parse(xhr.responseText);
                        if (result.success) {
                            showUploadStatus('success');
                            showModal(result.code, result.expires_at);
                            uploadForm.reset();
                            selectedFileText.textContent = '';
                            refreshCaptcha(uploadForm.querySelector('.captcha-image'));
                        } else {
                            showUploadStatus('error');
                            alert('上传失败: ' + (result.error || '未知错误'));
                            if (result.error.includes('验证码')) {
                                refreshCaptcha(uploadForm.querySelector('.captcha-image'));
                            }
                        }
                    } catch (error) {
                        showUploadStatus('error');
                        console.error('解析响应失败:', error);
                        alert('服务器响应格式错误');
                    }
                    submitBtn.disabled = false;
                    submitBtn.textContent = '生成提取码';
                };
                
                xhr.onerror = function() {
                    showUploadStatus('error');
                    console.error('上传错误');
                    alert('上传失败: 网络错误');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '生成提取码';
                };
                
                xhr.open('POST', this.action, true);
                xhr.send(formData);
                
            } catch (error) {
                showUploadStatus('error');
                console.error('上传错误:', error);
                alert('上传失败: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.textContent = '生成提取码';
            }
        });
    }

    const downloadForm = document.getElementById('receiveForm');
    if (downloadForm) {
        downloadForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const code = this.querySelector('input[name="code"]').value.trim();
            const captcha = this.querySelector('input[name="captcha"]').value.trim();
            
            if (!code) {
                alert('请输入提取码');
                return;
            }

            if (!captcha) {
                alert('请输入验证码');
                return;
            }

            const submitBtn = this.querySelector('.submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = '检查中...';

            try {
                console.log('Checking file:', code);
                console.log('Captcha:', captcha);
                
                const response = await fetch('/public/index.php/file/check', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        code: code,
                        captcha: captcha
                    })
                });

                console.log('Response status:', response.status);
                const contentType = response.headers.get('content-type');
                console.log('Response type:', contentType);

                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('服���器响应格式错误');
                }

                const result = await response.json();
                console.log('Check result:', result);
                
                if (result.success) {
                    showDownloadConfirm(result);
                    this.querySelector('input[name="captcha"]').value = '';
                    refreshCaptcha(this.querySelector('.captcha-image'));
                } else {
                    alert(result.error || '文件不存在或已过期');
                    if (result.error.includes('验证码')) {
                        refreshCaptcha(this.querySelector('.captcha-image'));
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('获取文件信息失败: ' + error.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '获取文件';
            }
        });
    }

    document.querySelectorAll('.captcha-image').forEach(img => {
        img.title = '点击刷新验证码';
        refreshCaptcha(img);
    });

    // 优化下拉框交互
    document.querySelectorAll('.expire-select').forEach(select => {
        // 添加焦点效果
        select.addEventListener('focus', () => {
            select.parentElement.classList.add('focused');
        });
        
        select.addEventListener('blur', () => {
            select.parentElement.classList.remove('focused');
        });
        
        // 添加选择动画
        select.addEventListener('change', function() {
            this.classList.add('changed');
            setTimeout(() => this.classList.remove('changed'), 300);
        });
    });

    // 更新表单布局
    const expireGroup = document.querySelector('.form-group[data-type="expire"]');
    const limitGroup = document.querySelector('.form-group[data-type="limit"]');
    
    if (expireGroup && limitGroup) {
        const formRow = document.createElement('div');
        formRow.className = 'form-row';
        
        const col1 = document.createElement('div');
        const col2 = document.createElement('div');
        col1.className = col2.className = 'form-col';
        
        expireGroup.parentNode.insertBefore(formRow, expireGroup);
        col1.appendChild(expireGroup);
        col2.appendChild(limitGroup);
        formRow.appendChild(col1);
        formRow.appendChild(col2);
    }

    // 检查URL中是否包含分享参数
    const hash = window.location.hash;
    if (hash.startsWith('#receive=')) {
        const code = hash.replace('#receive=', '');
        // 切换到接收标签
        document.querySelector('[data-tab="receive"]').click();
        // 填入提取码
        document.querySelector('input[name="code"]').value = code;
        // 清除URL中的hash
        history.replaceState(null, '', window.location.pathname);
        // 聚焦到验证码输入框
        document.querySelector('input[name="captcha"]').focus();
    }
});

function showModal(code, expiresAt) {
    const modal = document.getElementById('resultModal');
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-title">文件上传成功</div>
            <div class="modal-body">
                <p>您的文件已成功上传，请保存以下信息：</p>
                <div class="code-container">
                    <div class="modal-code" id="codeDisplay">${code}</div>
                    <button class="copy-btn" onclick="copyCode()" title="复制提取码">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-expires" id="expiresDisplay">文件将在 ${expiresAt} 过期</div>
                
                <div class="share-options">
                    <button class="share-btn" onclick="shareFile('${code}')">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="18" cy="5" r="3"></circle>
                            <circle cx="6" cy="12" r="3"></circle>
                            <circle cx="18" cy="19" r="3"></circle>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                        </svg>
                        分享文件
                    </button>
                </div>
                
                <div class="share-tips">
                    <p>分享提示：</p>
                    <ul>
                        <li>点击"分享文件"生成分享链接</li>
                        <li>接收方打开链接后将自动填入提取码</li>
                        <li>接收方仍需输入验证码以确保安全</li>
                        <li>请在文件过期前完成分享</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn" onclick="closeModal()">确定</button>
            </div>
        </div>
    `;
    
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('show'), 10);
}

function closeModal() {
    const modal = document.getElementById('resultModal');
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
}

async function copyCode() {
    const code = document.getElementById('codeDisplay').textContent;
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(code);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = code;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
            } catch (error) {
                console.error('复制失败:', error);
            }
            document.body.removeChild(textArea);
        }

        const copyBtn = document.querySelector('.copy-btn');
        copyBtn.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2">
                <path d="M20 6L9 17l-5-5"></path>
            </svg>
        `;
        copyBtn.style.color = '#4CAF50';
        
        setTimeout(() => {
            copyBtn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
            `;
            copyBtn.style.color = '';
        }, 2000);
    } catch (err) {
        console.error('复制失败:', err);
    }
}

async function refreshCaptcha(imgElement) {
    try {
        imgElement.classList.add('loading');
        imgElement.style.opacity = '0.5';
        
        const response = await fetch('/public/index.php/file/captcha/generate');
        const result = await response.json();
        
        if (result.success) {
            const newImage = new Image();
            newImage.onload = function() {
                imgElement.src = result.image;
                imgElement.classList.remove('loading');
                imgElement.style.opacity = '1';
            };
            newImage.src = result.image;
        } else {
            console.error('Failed to generate captcha:', result.error);
            imgElement.classList.remove('loading');
            imgElement.style.opacity = '1';
        }
    } catch (error) {
        console.error('刷新验证码失败:', error);
        imgElement.classList.remove('loading');
        imgElement.style.opacity = '1';
    }
}

// 修改作者信息显示函数
function showAuthor() {
    window.open('https://icaba.top', '_blank');
}

// 添加下载成功提示函数
function showDownloadSuccess() {
    const successToast = document.createElement('div');
    successToast.className = 'download-success-toast';
    successToast.innerHTML = `
        <div class="toast-content">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2">
                <path d="M20 6L9 17l-5-5"></path>
            </svg>
            <span>文件下载完成</span>
        </div>
    `;
    
    document.body.appendChild(successToast);
    
    // 添加显示动画类
    setTimeout(() => successToast.classList.add('show'), 10);
    
    // 3秒后移除提示
    setTimeout(() => {
        successToast.classList.remove('show');
        setTimeout(() => document.body.removeChild(successToast), 300);
    }, 3000);
}

// 修改下载状态提示函数
function showDownloadStatus(status) {
    let statusToast = document.querySelector('.download-status-toast');
    if (!statusToast) {
        statusToast = document.createElement('div');
        statusToast.className = 'download-status-toast';
        document.body.appendChild(statusToast);
    }

    let icon, text, color;
    switch (status) {
        case 'downloading':
            icon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>`;
            text = isMobile() ? '正在下载...' : '开始下载...';
            color = '#4CAF50';
            break;
        case 'success':
            icon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2">
                <path d="M20 6L9 17l-5-5"></path>
            </svg>`;
            text = '下载成功';
            color = '#4CAF50';
            break;
    }

    statusToast.innerHTML = `
        <div class="status-content" style="color: ${color}">
            ${icon}
            <span>${text}</span>
        </div>
    `;

    if (!statusToast.classList.contains('show')) {
        statusToast.classList.add('show');
    }

    if (status === 'success') {
        setTimeout(() => {
            statusToast.classList.remove('show');
            setTimeout(() => statusToast.remove(), 300);
        }, 3000);
    }
}

// 添加上传状态提示函数
function showUploadStatus(status, progress = '') {
    let statusToast = document.querySelector('.upload-status-toast');
    if (!statusToast) {
        statusToast = document.createElement('div');
        statusToast.className = 'upload-status-toast';
        document.body.appendChild(statusToast);
    }

    let icon, text, color;
    switch (status) {
        case 'preparing':
            icon = `<svg class="rotating" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4a90e2" stroke-width="2">
                <path d="M21 12a9 9 0 11-9-9c2.52 0 4.83.91 6.62 2.43"></path>
            </svg>`;
            text = '准备上传...';
            color = '#4a90e2';
            break;
        case 'uploading':
            icon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>`;
            text = '正在上传' + (progress ? ` (${progress}%)` : '...');
            color = '#4CAF50';
            break;
        case 'success':
            icon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2">
                <path d="M20 6L9 17l-5-5"></path>
            </svg>`;
            text = '上传成功';
            color = '#4CAF50';
            break;
        case 'error':
            icon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff4444" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>`;
            text = '上传失败';
            color = '#ff4444';
            break;
    }

    statusToast.innerHTML = `
        <div class="status-content" style="color: ${color}">
            ${icon}
            <span>${text}</span>
        </div>
    `;

    if (!statusToast.classList.contains('show')) {
        statusToast.classList.add('show');
    }

    if (status === 'success' || status === 'error') {
        setTimeout(() => {
            statusToast.classList.remove('show');
            setTimeout(() => statusToast.remove(), 300);
        }, 3000);
    }
}

// 修改分享功能
async function shareFile(code) {
    // 生成分享链接
    const shareUrl = `${window.location.origin}${window.location.pathname}#receive=${code}`;
    
    // 先尝试复制到剪贴板
    try {
        await navigator.clipboard.writeText(shareUrl);
        showShareToast('分享链接已复制到剪贴板');
    } catch (err) {
        console.error('复制到剪贴板失败:', err);
    }
    
    // 然后尝试使用系统分享
    if (navigator.share) {
        try {
            await navigator.share({
                title: '文件传输柜 - 文件分享',
                text: '我通过文件传输柜分享了一个文件给您，请点击链接接收：',
                url: shareUrl
            });
        } catch (err) {
            // 用户取消分享不需要显示错误
            if (err.name !== 'AbortError') {
                console.error('系统分享失败:', err);
                // 如果之前没有成功复制到剪贴板，则显示分享对话框
                if (!navigator.clipboard) {
                    showShareDialog(shareUrl);
                }
            }
        }
    } else {
        // 如果不支持系统分享且之前没有成功复制到剪贴板
        if (!navigator.clipboard) {
            showShareDialog(shareUrl);
        }
    }
}

// 修改分享提示样式，使其更醒目
function showShareToast(message) {
    const toast = document.createElement('div');
    toast.className = 'share-toast';
    toast.innerHTML = `
        <div class="toast-content">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2">
                <path d="M20 6L9 17l-5-5"></path>
            </svg>
            <div class="toast-message">
                <span class="toast-title">分享成功</span>
                <span class="toast-desc">${message}</span>
            </div>
        </div>
    `;
    
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// 显示分享对话框
function showShareDialog(shareUrl) {
    const dialog = document.createElement('div');
    dialog.className = 'share-dialog modal';
    dialog.innerHTML = `
        <div class="modal-content">
            <div class="modal-title">分享链接</div>
            <div class="modal-body">
                <p>复制以下链接分享给他人：</p>
                <div class="share-link-container">
                    <input type="text" value="${shareUrl}" readonly class="share-link-input">
                    <button class="copy-btn" onclick="copyShareLinkFromDialog(this)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <button class="modal-btn" onclick="this.closest('.share-dialog').remove()">关闭</button>
        </div>
    `;
    
    document.body.appendChild(dialog);
    setTimeout(() => dialog.classList.add('show'), 10);
}

// 从对话框中复制分享链接
function copyShareLinkFromDialog(btn) {
    const input = btn.parentElement.querySelector('.share-link-input');
    input.select();
    document.execCommand('copy');
    showShareToast('分享链接已复制到剪贴板');
}

// 添加移动端检测函数
function isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// 添加保存图片到相册功能
async function saveImageToGallery(file) {
    try {
        // 检查是否支持文件系统访问 API
        if ('showSaveFilePicker' in window) {
            const handle = await window.showSaveFilePicker({
                suggestedName: file.name,
                types: [{
                    description: 'Images',
                    accept: {
                        'image/*': ['.jpg', '.jpeg', '.png']
                    }
                }]
            });
            const writable = await handle.createWritable();
            await writable.write(file);
            await writable.close();
            showToast('图片已保存到相册');
        } else if (navigator.share) {
            // 如果不支持文件系统API，尝试使用分享API
            await navigator.share({
                files: [file],
                title: '保存图片',
                text: '请选择保存到相册'
            });
        } else {
            // 降级方案：创建下载链接
            const a = document.createElement('a');
            a.href = URL.createObjectURL(file);
            a.download = file.name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(a.href);
            showToast('请在下载完成后手动保存到相册');
        }
    } catch (error) {
        console.error('保存图片失败:', error);
        showToast('保存图片失败，请重试');
    }
}

// 添加提示消息
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'mobile-toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }, 10);
}