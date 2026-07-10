<?php
/**
 * 我的书签视图
 */
if (!defined('ABSPATH')) exit;
$user = wp_get_current_user();
?>

<div class="wrap coolai-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-star-filled" style="color: #667eea;"></span>
        我的书签
    </h1>
    <button class="page-title-action" onclick="coolaiExportMyBookmarks()">
        <span class="dashicons dashicons-download"></span> 导出我的数据
    </button>
    <hr class="wp-header-end">
    
    <div class="coolai-user-info-card">
        <div class="coolai-avatar">
            <?php echo get_avatar($user->ID, 80); ?>
        </div>
        <div class="coolai-user-details">
            <h2><?php echo esc_html($user->display_name); ?></h2>
            <p><?php echo esc_html($user->user_email); ?></p>
            <div class="coolai-user-stats-inline">
                <span><strong><?php echo count($my_bookmarks); ?></strong> 个书签</span>
            </div>
        </div>
    </div>
    
    <div class="coolai-panel" style="margin-top: 20px;">
        <div class="coolai-panel-header">
            <h2><span class="dashicons dashicons-book-alt"></span> 我的书签列表</h2>
        </div>
        <div class="coolai-panel-body">
            <?php if (empty($my_bookmarks)): ?>
                <div class="coolai-empty-state">
                    <span class="dashicons dashicons-book-alt" style="font-size: 64px; opacity: 0.2;"></span>
                    <h3>还没有书签</h3>
                    <p>前往 <a href="<?php echo home_url('/coolai'); ?>">CoolAI 导航页面</a> 开始添加书签吧！</p>
                </div>
            <?php else: ?>
                <div class="coolai-bookmarks-grid">
                    <?php foreach ($my_bookmarks as $bookmark): ?>
                        <div class="coolai-bookmark-card">
                            <div class="coolai-bookmark-icon">
                                <?php 
                                $domain = parse_url($bookmark['url'], PHP_URL_HOST);
                                $favicon_url = rest_url('coolai/v1/favicon?domain=' . $domain);
                                ?>
                                <img src="<?php echo esc_url($favicon_url); ?>" alt="">
                            </div>
                            <div class="coolai-bookmark-content">
                                <h4><?php echo esc_html($bookmark['title']); ?></h4>
                                <a href="<?php echo esc_url($bookmark['url']); ?>" target="_blank">
                                    <?php echo esc_html(parse_url($bookmark['url'], PHP_URL_HOST)); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                                <?php if (isset($bookmark['category'])): ?>
                                    <span class="coolai-bookmark-category">
                                        <?php echo esc_html($bookmark['category']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 20px; text-align: center; color: #666;">
                    共 <?php echo count($my_bookmarks); ?> 个书签
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.coolai-user-info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 25px;
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
    margin: 20px 0;
}

.coolai-avatar img {
    border-radius: 50%;
    border: 4px solid rgba(255, 255, 255, 0.3);
}

.coolai-user-details h2 {
    margin: 0 0 5px 0;
    color: white;
    font-size: 24px;
}

.coolai-user-details p {
    margin: 0 0 15px 0;
    opacity: 0.9;
}

.coolai-user-stats-inline {
    display: flex;
    gap: 20px;
}

.coolai-user-stats-inline span {
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
}

.coolai-bookmarks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.coolai-bookmark-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    gap: 15px;
    transition: all 0.2s;
    cursor: pointer;
}

.coolai-bookmark-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    transform: translateY(-2px);
}

.coolai-bookmark-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.coolai-bookmark-icon img {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

.coolai-bookmark-content {
    flex: 1;
    min-width: 0;
}

.coolai-bookmark-content h4 {
    margin: 0 0 8px 0;
    font-size: 15px;
    font-weight: 600;
    color: #1e1e1e;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.coolai-bookmark-content a {
    color: #667eea;
    text-decoration: none;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.coolai-bookmark-content a:hover {
    text-decoration: underline;
}

.coolai-bookmark-category {
    display: inline-block;
    margin-top: 8px;
    padding: 4px 10px;
    background: #f0f0f1;
    border-radius: 10px;
    font-size: 11px;
    color: #666;
}

.coolai-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.coolai-empty-state h3 {
    color: #666;
    font-size: 20px;
    margin: 15px 0 10px;
}

.coolai-empty-state p {
    color: #999;
}

.coolai-empty-state a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.coolai-empty-state a:hover {
    text-decoration: underline;
}
</style>

<script>
function coolaiExportMyBookmarks() {
    const data = <?php echo json_encode($my_bookmarks, JSON_UNESCAPED_UNICODE); ?>;
    const dataStr = JSON.stringify(data, null, 2);
    const blob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'my-bookmarks-' + new Date().toISOString().split('T')[0] + '.json';
    a.click();
}
</script>
