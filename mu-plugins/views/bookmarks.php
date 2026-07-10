<?php
/**
 * 所有书签视图 - 按用户分组显示
 */
if (!defined('ABSPATH')) exit;

// 按用户分组
$bookmarks_by_user = array();
foreach ($bookmarks_data as $bookmark) {
    $user_id = $bookmark['user_id'];
    if (!isset($bookmarks_by_user[$user_id])) {
        $bookmarks_by_user[$user_id] = array(
            'username' => $bookmark['username'],
            'bookmarks' => array()
        );
    }
    $bookmarks_by_user[$user_id]['bookmarks'][] = $bookmark;
}

// 统计信息
$total_users = count($bookmarks_by_user);
$total_bookmarks = count($bookmarks_data);
$categories = array_unique(array_column($bookmarks_data, 'category'));
?>

<!-- 加载遮罩层 -->
<div id="coolaiLoadingOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.95); z-index: 9999; display: flex; align-items: center; justify-content: center; flex-direction: column;">
    <div style="text-align: center;">
        <div class="spinner" style="width: 60px; height: 60px; border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
        <p style="color: #667eea; font-size: 16px; font-weight: 500; margin: 0;">正在加载用户书签数据...</p>
        <p style="color: #999; font-size: 13px; margin: 10px 0 0;">共 <?php echo $total_users; ?> 个用户 • <?php echo $total_bookmarks; ?> 个书签</p>
    </div>
</div>

<div class="wrap coolai-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-book-alt" style="color: #667eea;"></span>
        所有用户书签
    </h1>
    <button class="page-title-action" onclick="coolaiExportBookmarks()">
        <span class="dashicons dashicons-download"></span> 导出为 CSV
    </button>
    <hr class="wp-header-end">
    
    <!-- 统计信息 -->
    <div class="notice notice-info" style="margin-top: 20px; display: flex; align-items: center;">
        <span class="dashicons dashicons-info" style="color: #2271b1; font-size: 20px; margin-right: 10px;"></span>
        <div>
            <p style="margin: 0.5em 0;">
                <strong>数据统计：</strong>共 <?php echo $total_users; ?> 个用户，<?php echo $total_bookmarks; ?> 个书签，<?php echo count($categories); ?> 个分类
            </p>
            <p style="margin: 0.5em 0; font-size: 13px; color: #646970;">
                • 支持按用户折叠/展开 • 搜索标题/URL • 按分类筛选 • 导出CSV
            </p>
        </div>
    </div>
    
    <!-- 搜索和筛选 -->
    <div class="coolai-panel" style="margin-top: 20px;">
        <div class="coolai-panel-body">
            <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                <input type="text" id="bookmarkSearch" placeholder="搜索书签标题或URL..." 
                       style="width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <select id="categoryFilter" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">所有分类</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="userFilter" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">所有用户</option>
                    <?php foreach ($bookmarks_by_user as $uid => $udata): ?>
                        <option value="<?php echo esc_attr($uid); ?>"><?php echo esc_html($udata['username']); ?> (<?php echo count($udata['bookmarks']); ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button class="button" onclick="coolaiToggleAll(true)" style="margin-left: auto;">展开全部</button>
                <button class="button" onclick="coolaiToggleAll(false)">折叠全部</button>
            </div>
            
            <?php if (empty($bookmarks_data)): ?>
                <p class="coolai-empty-state">
                    <span class="dashicons dashicons-book-alt" style="font-size: 48px; opacity: 0.3;"></span>
                    <br>暂无书签数据
                </p>
            <?php else: ?>
                <!-- 按用户分组显示 -->
                <div id="userBookmarksContainer">
                    <?php foreach ($bookmarks_by_user as $user_id => $user_data): ?>
                        <div class="user-bookmarks-card" data-user-id="<?php echo esc_attr($user_id); ?>" 
                             style="margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <!-- 用户头部（可点击折叠） -->
                            <div class="user-card-header" onclick="coolaiToggleUser(<?php echo $user_id; ?>)" 
                                 style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: all 0.3s;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <span class="dashicons dashicons-admin-users" style="font-size: 24px;"></span>
                                    <div>
                                        <h3 style="margin: 0; color: white; font-size: 16px; font-weight: 600;">
                                            <?php echo esc_html($user_data['username']); ?>
                                        </h3>
                                        <small style="opacity: 0.85; font-size: 12px;">
                                            ID: <?php echo $user_id; ?> • 
                                            <span class="user-bookmark-count-<?php echo $user_id; ?>">
                                                <?php echo count($user_data['bookmarks']); ?>
                                            </span> 个书签
                                        </small>
                                    </div>
                                </div>
                                <span class="dashicons dashicons-arrow-down-alt2 toggle-icon" 
                                      id="toggle-icon-<?php echo $user_id; ?>" 
                                      style="font-size: 20px; transition: transform 0.3s;"></span>
                            </div>
                            
                            <!-- 书签列表（可折叠） -->
                            <div class="user-bookmarks-list" id="user-bookmarks-<?php echo $user_id; ?>" 
                                 style="display: block; transition: all 0.3s;">
                                <table class="wp-list-table widefat striped" style="margin: 0; border: none;">
                                    <thead>
                                        <tr style="background-color: #f6f7f7;">
                                            <th style="width: 50px; text-align: center;">图标</th>
                                            <th style="width: 25%;">标题</th>
                                            <th style="width: 40%;">URL</th>
                                            <th style="width: 15%;">分类</th>
                                            <th style="width: 15%;">更新时间</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_data['bookmarks'] as $bookmark): ?>
                                            <tr class="bookmark-row" 
                                                data-user-id="<?php echo esc_attr($user_id); ?>"
                                                data-category="<?php echo esc_attr($bookmark['category']); ?>" 
                                                data-search="<?php echo esc_attr(strtolower($bookmark['title'] . ' ' . $bookmark['url'])); ?>">
                                                <td style="text-align: center; padding: 8px;">
                                                    <?php 
                                                    $domain = parse_url($bookmark['url'], PHP_URL_HOST);
                                                    // ⚡ 优化：使用静态路径直接加载（和前端一致，超快）
                                                    $upload_dir = wp_upload_dir();
                                                    $static_favicon_url = $upload_dir['baseurl'] . '/coolai_favicons/' . $domain . '.png';
                                                    // REST API 作为备用（如果静态文件不存在时使用）
                                                    $api_favicon_url = rest_url('coolai/v1/favicon?domain=' . $domain);
                                                    // 使用首字母作为初始占位符
                                                    $first_char = mb_substr($bookmark['title'], 0, 1, 'UTF-8');
                                                    ?>
                                                    <img class="lazy-favicon" 
                                                         data-src="<?php echo esc_url($static_favicon_url); ?>" 
                                                         data-fallback="<?php echo esc_url($api_favicon_url); ?>"
                                                         data-domain="<?php echo esc_attr($domain); ?>"
                                                         alt="<?php echo esc_attr($bookmark['title']); ?>"
                                                         style="width: 32px; height: 32px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;"
                                                         title="<?php echo esc_attr($domain); ?>">
                                                </td>
                                                <td>
                                                    <strong style="color: #2c3e50;"><?php echo esc_html($bookmark['title']); ?></strong>
                                                </td>
                                                <td>
                                                    <a href="<?php echo esc_url($bookmark['url']); ?>" target="_blank" 
                                                       style="color: #667eea; text-decoration: none; word-break: break-all;">
                                                        <?php echo esc_html(substr($bookmark['url'], 0, 70)) . (strlen($bookmark['url']) > 70 ? '...' : ''); ?>
                                                        <span class="dashicons dashicons-external" style="font-size: 12px; vertical-align: middle; margin-left: 3px;"></span>
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="coolai-badge coolai-badge-primary" 
                                                          style="display: inline-block; padding: 3px 10px; border-radius: 12px; background: #e3f2fd; color: #1976d2; font-size: 12px; font-weight: 500;">
                                                        <?php echo esc_html($bookmark['category']); ?>
                                                    </span>
                                                </td>
                                                <td style="color: #666; font-size: 13px;">
                                                    <?php 
                                                    if ($bookmark['timestamp']) {
                                                        echo human_time_diff($bookmark['timestamp'], current_time('timestamp')) . ' 前';
                                                    } else {
                                                        echo '<span style="color: #999;">未知</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px; text-align: center; color: #666;">
                    显示 <strong><span id="visibleCount"><?php echo $total_bookmarks; ?></span></strong> / <?php echo $total_bookmarks; ?> 条书签
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* 加载动画 */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.user-card-header:hover {
    opacity: 0.95;
    transform: translateY(-1px);
}
.user-bookmarks-card {
    transition: all 0.3s;
}
.user-bookmarks-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}
.bookmark-row {
    transition: background-color 0.2s;
}
.bookmark-row:hover {
    background-color: #f0f6ff !important;
}
/* Favicon 懒加载样式 */
.lazy-favicon {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    color: white;
    text-transform: uppercase;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    transition: all 0.3s ease;
}
.lazy-favicon[src] {
    background: transparent !important;
    font-size: 0;
}
/* 加载动画 */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.lazy-favicon[src] {
    animation: fadeIn 0.3s ease-in;
}
</style>

<script>
jQuery(document).ready(function($) {
    // ========================================
    // 1. 恢复上次的筛选条件（localStorage）
    // ========================================
    const savedSearch = localStorage.getItem('coolai_bookmark_search');
    const savedCategory = localStorage.getItem('coolai_bookmark_category');
    const savedUser = localStorage.getItem('coolai_bookmark_user');
    
    if (savedSearch) {
        $('#bookmarkSearch').val(savedSearch);
    }
    if (savedCategory) {
        $('#categoryFilter').val(savedCategory).trigger('change');
    }
    if (savedUser) {
        $('#userFilter').val(savedUser).trigger('change');
    }
    
    // 如果有保存的筛选条件，立即应用
    if (savedSearch || savedCategory || savedUser) {
        setTimeout(function() {
            filterBookmarks();
        }, 200);
    }
    
    // ========================================
    // 2. 隐藏加载遮罩层
    // ========================================
    setTimeout(function() {
        $('#coolaiLoadingOverlay').fadeOut(300, function() {
            $(this).remove();
        });
    }, 500); // 延迟500ms，让用户看到加载效果
    
    // 折叠/展开用户书签
    window.coolaiToggleUser = function(userId) {
        const $list = $('#user-bookmarks-' + userId);
        const $icon = $('#toggle-icon-' + userId);
        
        $list.slideToggle(300);
        $icon.toggleClass('rotated');
        
        // 旋转图标
        if ($icon.hasClass('rotated')) {
            $icon.css('transform', 'rotate(180deg)');
        } else {
            $icon.css('transform', 'rotate(0deg)');
        }
    };
    
    // 展开/折叠全部
    window.coolaiToggleAll = function(expand) {
        $('.user-bookmarks-list').each(function() {
            const $list = $(this);
            const userId = $list.attr('id').replace('user-bookmarks-', '');
            const $icon = $('#toggle-icon-' + userId);
            
            if (expand) {
                $list.slideDown(300);
                $icon.removeClass('rotated').css('transform', 'rotate(0deg)');
            } else {
                $list.slideUp(300);
                $icon.addClass('rotated').css('transform', 'rotate(180deg)');
            }
        });
    };
    
    // 搜索和筛选功能
    function filterBookmarks() {
        const searchTerm = $('#bookmarkSearch').val().toLowerCase();
        const category = $('#categoryFilter').val();
        const userId = $('#userFilter').val();
        
        // 保存筛选条件到 localStorage（刷新后记住）
        localStorage.setItem('coolai_bookmark_search', searchTerm);
        localStorage.setItem('coolai_bookmark_category', category);
        localStorage.setItem('coolai_bookmark_user', userId);
        
        let totalVisible = 0;
        
        // 遍历每个用户卡片
        $('.user-bookmarks-card').each(function() {
            const $card = $(this);
            const cardUserId = $card.attr('data-user-id');
            let visibleInCard = 0;
            
            // 用户筛选
            if (userId && cardUserId !== userId) {
                $card.hide();
                return;
            }
            
            // 遍历该用户的书签
            $card.find('.bookmark-row').each(function() {
                const $row = $(this);
                const searchContent = $row.attr('data-search');
                const rowCategory = $row.attr('data-category');
                
                const matchSearch = !searchTerm || searchContent.indexOf(searchTerm) !== -1;
                const matchCategory = !category || rowCategory === category;
                
                if (matchSearch && matchCategory) {
                    $row.show();
                    visibleInCard++;
                    totalVisible++;
                } else {
                    $row.hide();
                }
            });
            
            // 更新用户卡片的书签计数
            $('.user-bookmark-count-' + cardUserId).text(visibleInCard);
            
            // 如果该用户没有匹配的书签，隐藏整个卡片
            if (visibleInCard > 0) {
                $card.show();
            } else {
                $card.hide();
            }
        });
        
        // 更新总计数
        $('#visibleCount').text(totalVisible);
    }
    
    // 绑定搜索事件
    $('#bookmarkSearch, #categoryFilter, #userFilter').on('input change', filterBookmarks);
    
    // Favicon 懒加载优化 - 使用 Intersection Observer（静态路径 + API 降级）
    const faviconObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                const staticSrc = img.getAttribute('data-src'); // 静态路径（优先）
                const fallbackSrc = img.getAttribute('data-fallback'); // API路径（降级）
                const domain = img.getAttribute('data-domain');
                
                if (staticSrc && !img.src) {
                    // ⚡ 优先尝试静态路径（超快，直接读取文件）
                    const tempImg = new Image();
                    tempImg.onload = function() {
                        img.src = staticSrc;
                        img.style.background = 'none';
                    };
                    tempImg.onerror = function() {
                        // 静态文件不存在，降级到 REST API
                        if (fallbackSrc) {
                            const apiImg = new Image();
                            apiImg.onload = function() {
                                img.src = fallbackSrc;
                                img.style.background = 'none';
                            };
                            apiImg.onerror = function() {
                                // API 也失败，显示域名首字母
                                showFallbackChar(img, domain);
                            };
                            apiImg.src = fallbackSrc;
                        } else {
                            showFallbackChar(img, domain);
                        }
                    };
                    tempImg.src = staticSrc;
                    
                    // 停止观察已加载的图片
                    observer.unobserve(img);
                }
            }
        });
    }, {
        root: null,
        rootMargin: '50px', // 提前50px开始加载
        threshold: 0.01
    });
    
    // 降级显示首字母的函数
    function showFallbackChar(img, domain) {
        const firstChar = domain ? domain.charAt(0).toUpperCase() : '?';
        img.setAttribute('alt', firstChar);
        img.style.display = 'inline-flex';
        img.style.alignItems = 'center';
        img.style.justifyContent = 'center';
        img.textContent = firstChar;
    }
    // 观察所有懒加载图标
    document.querySelectorAll('.lazy-favicon').forEach(img => {
        faviconObserver.observe(img);
    });
    
    // 用户折叠/展开时重新检查可见性
    const originalToggleUser = window.coolaiToggleUser;
    window.coolaiToggleUser = function(userId) {
        originalToggleUser(userId);
        // 延迟检查新可见的图标
        setTimeout(() => {
            document.querySelectorAll('.lazy-favicon').forEach(img => {
                if (!img.src && img.getBoundingClientRect().top < window.innerHeight) {
                    faviconObserver.observe(img);
                }
            });
        }, 350);
    };
});

// 导出CSV功能
function coolaiExportBookmarks() {
    let csv = 'Username,User ID,Title,URL,Category,Timestamp\n';
    
    jQuery('.bookmark-row:visible').each(function() {
        const $row = jQuery(this);
        const userId = $row.attr('data-user-id');
        const username = $row.closest('.user-bookmarks-card').find('h3').text().trim();
        const title = $row.find('td:nth-child(2)').text().trim().replace(/"/g, '""');
        const url = $row.find('td:nth-child(3) a').attr('href');
        const category = $row.find('td:nth-child(4)').text().trim().replace(/"/g, '""');
        const timestamp = $row.find('td:nth-child(5)').text().trim();
        
        csv += `"${username}",${userId},"${title}","${url}","${category}","${timestamp}"\n`;
    });
    
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'coolai-bookmarks-' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
}
</script>
