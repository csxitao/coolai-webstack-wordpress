<?php
/**
 * Favicon 管理视图（增强版）
 */
if (!defined('ABSPATH')) exit;
?>

<div class="wrap coolai-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-images-alt2" style="color: #667eea;"></span>
        Favicon 管理
    </h1>
    <button class="page-title-action" onclick="showUploadModal()">
        <span class="dashicons dashicons-upload"></span> 手动上传图标
    </button>
    <button class="page-title-action" onclick="coolaiClearAllCache()">
        <span class="dashicons dashicons-trash"></span> 清空全部缓存
    </button>
    <hr class="wp-header-end">
    
    <!-- 统计信息 -->
    <div class="coolai-favicon-stats">
        <div class="coolai-stat-item">
            <strong><?php echo number_format($favicon_stats['total']); ?></strong>
            <span>总图标数</span>
        </div>
        <div class="coolai-stat-item">
            <strong><?php echo esc_html($favicon_stats['size']); ?></strong>
            <span>占用空间</span>
        </div>
        <div class="coolai-stat-item">
            <strong><?php echo esc_html($favicon_stats['newest']); ?></strong>
            <span>最新更新</span>
        </div>
        <div class="coolai-stat-item">
            <strong><?php echo esc_html($favicon_stats['oldest']); ?></strong>
            <span>最旧缓存</span>
        </div>
    </div>
    
    <!-- 搜索和筛选 -->
    <div class="coolai-panel" style="margin-top: 20px;">
        <div class="coolai-panel-body" style="padding: 15px;">
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" id="faviconSearch" placeholder="搜索域名..." 
                       style="flex: 1; padding: 8px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                <select id="statusFilter" style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">所有状态</option>
                    <option value="cached">已缓存</option>
                    <option value="not_cached">未缓存</option>
                </select>
                <button class="button" onclick="sortFavicons('domain')">
                    <span class="dashicons dashicons-sort"></span> 按域名
                </button>
                <button class="button" onclick="sortFavicons('size')">
                    <span class="dashicons dashicons-sort"></span> 按大小
                </button>
            </div>
        </div>
    </div>
    
    <!-- Favicon 列表 -->
    <div class="coolai-panel" style="margin-top: 20px;">
        <div class="coolai-panel-header">
            <h2>
                <span class="dashicons dashicons-images-alt2"></span> 
                图标列表
                <span id="visibleCount" style="font-size: 14px; font-weight: normal; opacity: 0.8;"></span>
            </h2>
        </div>
        <div class="coolai-panel-body">
            <?php if (empty($favicon_list)): ?>
                <p class="coolai-empty-state">
                    <span class="dashicons dashicons-info" style="font-size: 48px; opacity: 0.3;"></span>
                    <br>暂无图标数据
                    <br>
                    <small>添加书签后，图标会自动缓存到这里</small>
                </p>
            <?php else: ?>
                <div class="coolai-favicon-grid">
                    <?php foreach ($favicon_list as $favicon): ?>
                        <div class="coolai-favicon-item" 
                             data-domain="<?php echo esc_attr($favicon['domain']); ?>"
                             data-status="<?php echo esc_attr($favicon['status']); ?>"
                             data-size="<?php echo esc_attr($favicon['size_bytes']); ?>">
                            <div class="coolai-favicon-preview">
                                <img src="<?php echo esc_url($favicon['url']); ?>" 
                                     alt="<?php echo esc_attr($favicon['domain']); ?>"
                                     loading="lazy"
                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\'%3E%3Crect width=\'64\' height=\'64\' fill=\'%23ddd\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-size=\'20\'%3E?\%3C/text%3E%3C/svg%3E'">
                            </div>
                            <div class="coolai-favicon-info">
                                <strong title="<?php echo esc_attr($favicon['domain']); ?>">
                                    <?php echo esc_html($favicon['domain']); ?>
                                </strong>
                                <div class="coolai-favicon-meta">
                                    <?php if ($favicon['has_file']): ?>
                                        <span class="coolai-status-badge coolai-status-cached" title="已缓存">
                                            <span class="dashicons dashicons-yes-alt"></span> 已缓存
                                        </span>
                                        <span title="文件大小"><?php echo esc_html($favicon['size']); ?></span>
                                        <span title="缓存时间"><?php echo esc_html($favicon['age']); ?> 前</span>
                                    <?php else: ?>
                                        <span class="coolai-status-badge coolai-status-not-cached" title="未缓存">
                                            <span class="dashicons dashicons-warning"></span> 未缓存
                                        </span>
                                        <span>点击"刷新"生成缓存</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="coolai-favicon-actions">
                                <button class="button button-small" 
                                        onclick="coolaiUploadForDomain('<?php echo esc_js($favicon['domain']); ?>')"
                                        title="上传图标">
                                    <span class="dashicons dashicons-upload"></span>
                                </button>
                                <button class="button button-small" 
                                        onclick="coolaiRefreshFavicon('<?php echo esc_js($favicon['domain']); ?>')"
                                        title="刷新图标">
                                    <span class="dashicons dashicons-update"></span>
                                </button>
                                <?php if ($favicon['has_file']): ?>
                                <button class="button button-small button-link-delete" 
                                        onclick="coolaiDeleteFavicon('<?php echo esc_js($favicon['domain']); ?>')"
                                        title="删除">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 20px; text-align: center; color: #666;">
                    共 <span id="totalCount"><?php echo count($favicon_list); ?></span> 个图标
                    （<span id="cachedCount"></span> 已缓存，<span id="notCachedCount"></span> 未缓存）
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 上传模态框 -->
<div id="uploadModal" class="coolai-modal" style="display: none;">
    <div class="coolai-modal-content">
        <div class="coolai-modal-header">
            <h2><span class="dashicons dashicons-upload"></span> 上传 Favicon</h2>
            <button class="coolai-modal-close" onclick="closeUploadModal()">&times;</button>
        </div>
        <div class="coolai-modal-body">
            <form id="faviconUploadForm" enctype="multipart/form-data">
                <table class="form-table">
                    <tr>
                        <th>域名</th>
                        <td>
                            <input type="text" id="uploadDomain" name="favicon_domain" 
                                   placeholder="example.com" required
                                   style="width: 100%;">
                            <p class="description">输入完整域名，例如：github.com</p>
                        </td>
                    </tr>
                    <tr>
                        <th>图标文件</th>
                        <td>
                            <input type="file" id="uploadFile" name="favicon_file" 
                                   accept="image/*" required>
                            <p class="description">
                                支持格式：PNG, JPG, GIF, SVG, ICO, WebP<br>
                                最大大小：512KB
                            </p>
                            <div id="imagePreview" style="margin-top: 10px; display: none;">
                                <img id="previewImg" style="max-width: 100px; max-height: 100px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="coolai-modal-footer">
            <button class="button button-large" onclick="closeUploadModal()">取消</button>
            <button class="button button-primary button-large" onclick="submitUpload()">
                <span class="dashicons dashicons-upload"></span> 上传
            </button>
        </div>
    </div>
</div>

<style>
.coolai-favicon-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.coolai-stat-item {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    text-align: center;
}

.coolai-stat-item strong {
    display: block;
    font-size: 32px;
    color: #667eea;
    margin-bottom: 5px;
}

.coolai-stat-item span {
    color: #666;
    font-size: 14px;
}

.coolai-favicon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.coolai-favicon-item {
    background: #f8f9fa;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.2s;
}

.coolai-favicon-item:hover {
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.coolai-favicon-item.hidden {
    display: none;
}

.coolai-favicon-preview {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    overflow: hidden;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid #ddd;
}

.coolai-favicon-preview img {
    width: 36px;
    height: 36px;
    object-fit: contain;
}

.coolai-favicon-info {
    flex: 1;
    min-width: 0;
}

.coolai-favicon-info strong {
    display: block;
    font-size: 14px;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.coolai-favicon-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 12px;
    color: #999;
    align-items: center;
}

.coolai-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.coolai-status-cached {
    background: #d4edda;
    color: #155724;
}

.coolai-status-not-cached {
    background: #fff3cd;
    color: #856404;
}

.coolai-favicon-actions {
    display: flex;
    gap: 5px;
}

.coolai-favicon-actions .button {
    padding: 4px 8px;
    min-width: 32px;
}

/* 模态框样式 */
.coolai-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100000;
}

.coolai-modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.coolai-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.coolai-modal-header h2 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.coolai-modal-close {
    background: none;
    border: none;
    font-size: 32px;
    line-height: 1;
    cursor: pointer;
    color: #999;
    padding: 0;
    width: 32px;
    height: 32px;
}

.coolai-modal-close:hover {
    color: #333;
}

.coolai-modal-body {
    padding: 20px;
}

.coolai-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    updateCounts();
    
    // 搜索和筛选功能
    $('#faviconSearch, #statusFilter').on('input change', function() {
        filterFavicons();
    });
    
    // 文件预览
    $('#uploadFile').on('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImg').attr('src', e.target.result);
                $('#imagePreview').show();
            };
            reader.readAsDataURL(file);
        } else {
            $('#imagePreview').hide();
        }
    });
});

function filterFavicons() {
    const searchTerm = jQuery('#faviconSearch').val().toLowerCase();
    const statusFilter = jQuery('#statusFilter').val();
    let visibleCount = 0;
    
    jQuery('.coolai-favicon-item').each(function() {
        const $item = jQuery(this);
        const domain = $item.attr('data-domain').toLowerCase();
        const status = $item.attr('data-status');
        
        const matchSearch = !searchTerm || domain.indexOf(searchTerm) !== -1;
        const matchStatus = !statusFilter || status === statusFilter;
        
        if (matchSearch && matchStatus) {
            $item.removeClass('hidden');
            visibleCount++;
        } else {
            $item.addClass('hidden');
        }
    });
    
    jQuery('#visibleCount').text('(' + visibleCount + ' 个显示)');
}

function sortFavicons(by) {
    const $grid = jQuery('.coolai-favicon-grid');
    const $items = $grid.children('.coolai-favicon-item').get();
    
    $items.sort(function(a, b) {
        if (by === 'domain') {
            return jQuery(a).attr('data-domain').localeCompare(jQuery(b).attr('data-domain'));
        } else if (by === 'size') {
            return parseInt(jQuery(b).attr('data-size')) - parseInt(jQuery(a).attr('data-size'));
        }
    });
    
    jQuery.each($items, function(idx, item) {
        $grid.append(item);
    });
}

function updateCounts() {
    const total = jQuery('.coolai-favicon-item').length;
    const cached = jQuery('.coolai-favicon-item[data-status="cached"]').length;
    const notCached = jQuery('.coolai-favicon-item[data-status="not_cached"]').length;
    
    jQuery('#cachedCount').text(cached);
    jQuery('#notCachedCount').text(notCached);
}

function showUploadModal() {
    jQuery('#uploadModal').fadeIn(200);
    jQuery('#uploadDomain').val('').focus();
    jQuery('#uploadFile').val('');
    jQuery('#imagePreview').hide();
}

function closeUploadModal() {
    jQuery('#uploadModal').fadeOut(200);
}

function coolaiUploadForDomain(domain) {
    showUploadModal();
    jQuery('#uploadDomain').val(domain);
}

function submitUpload() {
    const domain = jQuery('#uploadDomain').val().trim();
    const file = jQuery('#uploadFile')[0].files[0];
    
    if (!domain) {
        alert('请输入域名');
        return;
    }
    
    if (!file) {
        alert('请选择图标文件');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'coolai_admin_action');
    formData.append('action_type', 'upload_favicon');
    formData.append('nonce', coolaiAdmin.nonce);
    formData.append('favicon_domain', domain);
    formData.append('favicon_file', file);
    
    jQuery.ajax({
        url: coolaiAdmin.ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            jQuery('.coolai-modal-footer button').prop('disabled', true);
        },
        success: function(response) {
            if (response.success) {
                alert('上传成功！页面将刷新。');
                location.reload();
            } else {
                alert('上传失败：' + response.data);
            }
        },
        error: function() {
            alert('上传失败，请重试');
        },
        complete: function() {
            jQuery('.coolai-modal-footer button').prop('disabled', false);
        }
    });
}

function coolaiClearAllCache() {
    if (!confirm('确定要清空所有 Favicon 缓存吗？此操作不可恢复！')) {
        return;
    }
    
    jQuery.ajax({
        url: coolaiAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'coolai_admin_action',
            action_type: 'clear_cache',
            nonce: coolaiAdmin.nonce
        },
        beforeSend: function() {
            jQuery('.page-title-action').prop('disabled', true);
        },
        success: function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert('清理失败：' + response.data);
            }
        },
        complete: function() {
            jQuery('.page-title-action').prop('disabled', false);
        }
    });
}

function coolaiDeleteFavicon(domain) {
    if (!confirm('确定要删除 ' + domain + ' 的图标缓存吗？')) {
        return;
    }
    
    jQuery.ajax({
        url: coolaiAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'coolai_admin_action',
            action_type: 'delete_favicon',
            domain: domain,
            nonce: coolaiAdmin.nonce
        },
        success: function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            }
        }
    });
}

function coolaiRefreshFavicon(domain) {
    if (!confirm('确定要重新获取 ' + domain + ' 的图标吗？\n\n这将删除手动上传的图标和缓存，重新从网络获取。')) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span>';
    button.disabled = true;
    
    const url = coolaiAdmin.restUrl + 'favicon?domain=' + encodeURIComponent(domain) + '&refresh=1';
    
    // 使用fetch加载图标
    fetch(url)
        .then(response => {
            if (response.ok) {
                // 成功后等待1秒再刷新页面，让新图标有时间加载
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                alert('刷新失败，请重试');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('刷新失败: ' + error.message);
            button.innerHTML = originalText;
            button.disabled = false;
        });
}

// 添加旋转动画
const style = document.createElement('style');
style.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
document.head.appendChild(style);

// ESC 键关闭模态框
jQuery(document).keyup(function(e) {
    if (e.key === "Escape") {
        closeUploadModal();
    }
});
</script>
