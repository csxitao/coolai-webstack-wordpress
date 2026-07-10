<?php
/**
 * 仪表盘视图
 */
if (!defined('ABSPATH')) exit;
?>

<div class="wrap coolai-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-star-filled" style="color: #667eea;"></span>
        CoolAI 导航管理 - 仪表盘
    </h1>
    
    <hr class="wp-header-end">
    
    <!-- 统计卡片 -->
    <div class="coolai-stats-grid">
        <div class="coolai-stat-card">
            <div class="coolai-stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="coolai-stat-content">
                <h3><?php echo number_format($stats['total_users']); ?></h3>
                <p>活跃用户</p>
            </div>
        </div>
        
        <div class="coolai-stat-card">
            <div class="coolai-stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <span class="dashicons dashicons-book-alt"></span>
            </div>
            <div class="coolai-stat-content">
                <h3><?php echo number_format($stats['total_bookmarks']); ?></h3>
                <p>书签总数</p>
            </div>
        </div>
        
        <div class="coolai-stat-card">
            <div class="coolai-stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <span class="dashicons dashicons-category"></span>
            </div>
            <div class="coolai-stat-content">
                <h3><?php echo number_format($stats['total_folders']); ?></h3>
                <p>文件夹数</p>
            </div>
        </div>
        
        <div class="coolai-stat-card">
            <div class="coolai-stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <span class="dashicons dashicons-images-alt2"></span>
            </div>
            <div class="coolai-stat-content">
                <h3><?php echo number_format($stats['favicon_count']); ?></h3>
                <p>Favicon 缓存</p>
            </div>
        </div>
        
        <div class="coolai-stat-card">
            <div class="coolai-stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <span class="dashicons dashicons-update"></span>
            </div>
            <div class="coolai-stat-content">
                <h3><?php echo number_format($stats['sync_today']); ?></h3>
                <p>今日同步</p>
            </div>
        </div>
        
        <div class="coolai-stat-card">
            <div class="coolai-stat-icon" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="coolai-stat-content">
                <h3><?php echo number_format($stats['sync_week']); ?></h3>
                <p>本周同步</p>
            </div>
        </div>
        
        <div class="coolai-stat-card">
            <div class="coolai-stat-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                <span class="dashicons dashicons-database"></span>
            </div>
            <div class="coolai-stat-content">
                <h3><?php echo esc_html($stats['storage_used']); ?></h3>
                <p>数据库存储</p>
            </div>
        </div>
        
        <div class="coolai-stat-card">
            <div class="coolai-stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="coolai-stat-content">
                <h3><?php echo esc_html($stats['cache_size']); ?></h3>
                <p>缓存大小</p>
            </div>
        </div>
    </div>
    
    <!-- 两栏布局 -->
    <div class="coolai-dashboard-grid">
        <!-- 左侧：最近同步 -->
        <div class="coolai-panel">
            <div class="coolai-panel-header">
                <h2><span class="dashicons dashicons-update"></span> 最近同步记录</h2>
            </div>
            <div class="coolai-panel-body">
                <?php if (empty($recent_syncs)): ?>
                    <p class="coolai-empty-state">暂无同步记录</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>用户</th>
                                <th>类型</th>
                                <th>时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_syncs as $sync): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($sync['username']); ?></strong>
                                        <br>
                                        <small style="color: #999;">ID: <?php echo esc_html($sync['user_id']); ?></small>
                                    </td>
                                    <td>
                                        <span class="coolai-badge coolai-badge-<?php echo esc_attr($sync['type']); ?>">
                                            <?php echo esc_html(ucfirst($sync['type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo esc_html($sync['time_ago']); ?> 前
                                        <br>
                                        <small style="color: #999;">
                                            <?php echo date('Y-m-d H:i:s', $sync['timestamp']); ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 右侧：活跃用户 -->
        <div class="coolai-panel">
            <div class="coolai-panel-header">
                <h2><span class="dashicons dashicons-awards"></span> 活跃用户排行</h2>
            </div>
            <div class="coolai-panel-body">
                <?php if (empty($active_users)): ?>
                    <p class="coolai-empty-state">暂无活跃用户</p>
                <?php else: ?>
                    <ul class="coolai-user-list">
                        <?php 
                        $rank = 1;
                        foreach ($active_users as $user): 
                        ?>
                            <li class="coolai-user-item">
                                <div class="coolai-user-rank">
                                    <span class="coolai-rank-badge coolai-rank-<?php echo $rank; ?>">
                                        <?php echo $rank; ?>
                                    </span>
                                </div>
                                <div class="coolai-user-avatar">
                                    <?php echo get_avatar($user['id'], 40); ?>
                                </div>
                                <div class="coolai-user-info">
                                    <strong><?php echo esc_html($user['name']); ?></strong>
                                    <small><?php echo esc_html($user['email']); ?></small>
                                </div>
                                <div class="coolai-user-stats">
                                    <span class="coolai-badge coolai-badge-primary">
                                        <?php echo number_format($user['count']); ?> 条数据
                                    </span>
                                </div>
                            </li>
                        <?php 
                        $rank++;
                        endforeach; 
                        ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 快捷操作 -->
    <div class="coolai-panel">
        <div class="coolai-panel-header">
            <h2><span class="dashicons dashicons-admin-tools"></span> 快捷操作</h2>
        </div>
        <div class="coolai-panel-body">
            <div class="coolai-actions-grid">
                <a href="<?php echo admin_url('admin.php?page=coolai-bookmarks'); ?>" class="coolai-action-btn">
                    <span class="dashicons dashicons-book-alt"></span>
                    <span>管理书签</span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=coolai-favicons'); ?>" class="coolai-action-btn">
                    <span class="dashicons dashicons-images-alt2"></span>
                    <span>清理缓存</span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=coolai-settings'); ?>" class="coolai-action-btn">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span>系统设置</span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=coolai-logs'); ?>" class="coolai-action-btn">
                    <span class="dashicons dashicons-text-page"></span>
                    <span>查看日志</span>
                </a>
                <button class="coolai-action-btn" onclick="coolaiExportData()">
                    <span class="dashicons dashicons-download"></span>
                    <span>导出数据</span>
                </button>
                <a href="<?php echo rest_url('coolai/v1/sync/status'); ?>" target="_blank" class="coolai-action-btn">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <span>测试 API</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- 系统信息 -->
    <div class="coolai-panel">
        <div class="coolai-panel-header">
            <h2><span class="dashicons dashicons-info"></span> 系统信息</h2>
        </div>
        <div class="coolai-panel-body">
            <table class="form-table">
                <tr>
                    <th>插件版本</th>
                    <td><code>v2.4.1 (Admin Panel v1.0.0)</code></td>
                </tr>
                <tr>
                    <th>WordPress 版本</th>
                    <td><code><?php echo get_bloginfo('version'); ?></code></td>
                </tr>
                <tr>
                    <th>PHP 版本</th>
                    <td><code><?php echo PHP_VERSION; ?></code></td>
                </tr>
                <tr>
                    <th>REST API 地址</th>
                    <td>
                        <code><?php echo rest_url('coolai/v1/'); ?></code>
                        <a href="<?php echo rest_url('coolai/v1/sync/status'); ?>" target="_blank" class="button button-small">测试</a>
                    </td>
                </tr>
                <tr>
                    <th>调试模式</th>
                    <td>
                        <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                            <span class="coolai-badge coolai-badge-warning">已启用</span>
                        <?php else: ?>
                            <span class="coolai-badge coolai-badge-success">已关闭</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<style>
.coolai-admin-wrap {
    background: #f0f0f1;
    margin: -20px -20px 0 -22px;
    padding: 20px;
}

.coolai-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.coolai-stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}

.coolai-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.coolai-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.coolai-stat-content h3 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: #1e1e1e;
}

.coolai-stat-content p {
    margin: 5px 0 0;
    color: #666;
    font-size: 14px;
}

.coolai-dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.coolai-panel {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    overflow: hidden;
}

.coolai-panel-header {
    padding: 15px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.coolai-panel-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.coolai-panel-body {
    padding: 20px;
}

.coolai-empty-state {
    text-align: center;
    padding: 40px;
    color: #999;
}

.coolai-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.coolai-badge-bookmarks { background: #e3f2fd; color: #1976d2; }
.coolai-badge-folders { background: #fff3e0; color: #f57c00; }
.coolai-badge-settings { background: #f3e5f5; color: #7b1fa2; }
.coolai-badge-primary { background: #667eea; color: white; }
.coolai-badge-success { background: #43e97b; color: white; }
.coolai-badge-warning { background: #fa709a; color: white; }

.coolai-user-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.coolai-user-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.coolai-user-item:last-child {
    border-bottom: none;
}

.coolai-rank-badge {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: white;
}

.coolai-rank-1 { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); }
.coolai-rank-2 { background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%); }
.coolai-rank-3 { background: linear-gradient(135deg, #cd7f32 0%, #dda560 100%); }
.coolai-rank-4, .coolai-rank-5 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

.coolai-user-info {
    flex: 1;
}

.coolai-user-info strong {
    display: block;
    font-size: 14px;
}

.coolai-user-info small {
    color: #999;
    font-size: 12px;
}

.coolai-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.coolai-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px;
    background: #f8f9fa;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.2s;
    cursor: pointer;
    font-size: 14px;
}

.coolai-action-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
    transform: translateY(-2px);
}

.coolai-action-btn .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

@media (max-width: 768px) {
    .coolai-dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .coolai-stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<script>
function coolaiExportData() {
    if (!confirm('确定要导出所有数据吗？')) return;
    
    jQuery.ajax({
        url: coolaiAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'coolai_admin_action',
            action_type: 'export_data',
            nonce: coolaiAdmin.nonce
        },
        success: function(response) {
            if (response.success) {
                const dataStr = JSON.stringify(response.data, null, 2);
                const blob = new Blob([dataStr], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'coolai-export-' + new Date().toISOString().split('T')[0] + '.json';
                a.click();
                alert('导出成功！');
            }
        }
    });
}
</script>
