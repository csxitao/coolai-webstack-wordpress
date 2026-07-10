<?php
/**
 * 系统设置视图
 */
if (!defined('ABSPATH')) exit;
?>

<div class="wrap coolai-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings" style="color: #667eea;"></span>
        系统设置
    </h1>
    <hr class="wp-header-end">
    
    <form method="post" action="">
        <?php wp_nonce_field('coolai_settings_nonce'); ?>
        
        <div class="coolai-settings-grid">
            <!-- API 设置 -->
            <div class="coolai-panel">
                <div class="coolai-panel-header">
                    <h2><span class="dashicons dashicons-admin-plugins"></span> API 设置</h2>
                </div>
                <div class="coolai-panel-body">
                    <table class="form-table">
                        <tr>
                            <th scope="row">启用 REST API</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_api" value="1" 
                                           <?php checked($settings['enable_api'], true); ?>>
                                    允许通过 REST API 进行数据同步
                                </label>
                                <p class="description">
                                    API 地址: <code><?php echo rest_url('coolai/v1/'); ?></code>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">速率限制</th>
                            <td>
                                <input type="number" name="rate_limit" 
                                       value="<?php echo esc_attr($settings['rate_limit']); ?>"
                                       min="10" max="1000" step="10"
                                       style="width: 100px;">
                                <span>请求/分钟</span>
                                <p class="description">单个用户每分钟最多请求次数（默认: 100）</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- 缓存设置 -->
            <div class="coolai-panel">
                <div class="coolai-panel-header">
                    <h2><span class="dashicons dashicons-performance"></span> 缓存设置</h2>
                </div>
                <div class="coolai-panel-body">
                    <table class="form-table">
                        <tr>
                            <th scope="row">缓存有效期</th>
                            <td>
                                <input type="number" name="cache_duration" 
                                       value="<?php echo esc_attr($settings['cache_duration']); ?>"
                                       min="1" max="168" step="1"
                                       style="width: 100px;">
                                <span>小时</span>
                                <p class="description">Favicon 缓存的有效期（默认: 12小时）</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">当前缓存状态</th>
                            <td>
                                <?php
                                $upload_dir = wp_upload_dir();
                                $favicon_dir = $upload_dir['basedir'] . '/coolai_favicons';
                                $cache_writable = is_writable($favicon_dir);
                                ?>
                                <?php if ($cache_writable): ?>
                                    <span class="coolai-badge coolai-badge-success">✓ 可写入</span>
                                <?php else: ?>
                                    <span class="coolai-badge coolai-badge-warning">✗ 不可写</span>
                                <?php endif; ?>
                                <p class="description">缓存目录: <code><?php echo esc_html($favicon_dir); ?></code></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- 备份设置 -->
            <div class="coolai-panel">
                <div class="coolai-panel-header">
                    <h2><span class="dashicons dashicons-backup"></span> 备份设置</h2>
                </div>
                <div class="coolai-panel-body">
                    <table class="form-table">
                        <tr>
                            <th scope="row">自动备份</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_backup" value="1" 
                                           <?php checked($settings['auto_backup'], true); ?>>
                                    每天自动备份用户数据
                                </label>
                                <p class="description">备份文件将保存在 <code>/wp-content/coolai-backups/</code></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">手动备份</th>
                            <td>
                                <button type="button" class="button" onclick="coolaiManualBackup()">
                                    <span class="dashicons dashicons-download"></span> 立即备份
                                </button>
                                <p class="description">导出所有用户的书签数据为 JSON 文件</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- 调试设置 -->
            <div class="coolai-panel">
                <div class="coolai-panel-header">
                    <h2><span class="dashicons dashicons-bug"></span> 调试设置</h2>
                </div>
                <div class="coolai-panel-body">
                    <table class="form-table">
                        <tr>
                            <th scope="row">调试模式</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="debug_mode" value="1" 
                                           <?php checked($settings['debug_mode'], true); ?>>
                                    启用详细日志记录
                                </label>
                                <p class="description">
                                    日志文件: <code><?php echo WP_CONTENT_DIR . '/debug.log'; ?></code>
                                    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                                        <br><span class="coolai-badge coolai-badge-warning">WordPress 调试模式已启用</span>
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">清理日志</th>
                            <td>
                                <button type="button" class="button" onclick="coolaiClearLogs()">
                                    <span class="dashicons dashicons-trash"></span> 清空日志文件
                                </button>
                                <p class="description">删除所有 CoolAI 相关的日志记录</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- 维护工具 -->
            <div class="coolai-panel">
                <div class="coolai-panel-header">
                    <h2><span class="dashicons dashicons-admin-tools"></span> 维护工具</h2>
                </div>
                <div class="coolai-panel-body">
                    <table class="form-table">
                        <tr>
                            <th scope="row">清理孤立数据</th>
                            <td>
                                <button type="button" class="button" onclick="coolaiCleanOrphanData()">
                                    <span class="dashicons dashicons-trash"></span> 清理
                                </button>
                                <p class="description">删除已删除用户的遗留数据</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">重建缓存</th>
                            <td>
                                <button type="button" class="button" onclick="coolaiRebuildCache()">
                                    <span class="dashicons dashicons-update"></span> 重建
                                </button>
                                <p class="description">清空并重新生成所有缓存</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">数据库优化</th>
                            <td>
                                <button type="button" class="button" onclick="coolaiOptimizeDB()">
                                    <span class="dashicons dashicons-database"></span> 优化
                                </button>
                                <p class="description">优化 usermeta 表，提升查询性能</p>
                            </td>
                        </tr>
                    </table>
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
                            <td><code>v2.4.1</code></td>
                        </tr>
                        <tr>
                            <th>PHP 版本</th>
                            <td>
                                <code><?php echo PHP_VERSION; ?></code>
                                <?php if (version_compare(PHP_VERSION, '7.4.0', '<')): ?>
                                    <span class="coolai-badge coolai-badge-warning">建议升级到 PHP 7.4+</span>
                                <?php else: ?>
                                    <span class="coolai-badge coolai-badge-success">✓ 版本合适</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>MySQL 版本</th>
                            <td><code><?php echo $GLOBALS['wpdb']->db_version(); ?></code></td>
                        </tr>
                        <tr>
                            <th>WordPress 版本</th>
                            <td><code><?php echo get_bloginfo('version'); ?></code></td>
                        </tr>
                        <tr>
                            <th>服务器</th>
                            <td><code><?php echo $_SERVER['SERVER_SOFTWARE']; ?></code></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 保存按钮 -->
        <p class="submit">
            <input type="submit" name="coolai_save_settings" class="button button-primary button-hero" 
                   value="保存所有设置">
        </p>
    </form>
</div>

<style>
.coolai-settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.coolai-panel .form-table th {
    width: 200px;
    padding: 15px 10px 15px 0;
}

.coolai-panel .form-table td {
    padding: 15px 10px;
}

.button-hero {
    font-size: 16px !important;
    padding: 10px 30px !important;
    height: auto !important;
}

@media (max-width: 768px) {
    .coolai-settings-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function coolaiManualBackup() {
    if (!confirm('确定要立即备份所有数据吗？')) return;
    
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
                a.download = 'coolai-backup-' + new Date().toISOString().replace(/:/g, '-') + '.json';
                a.click();
                alert('备份完成！');
            }
        }
    });
}

function coolaiClearLogs() {
    if (!confirm('确定要清空所有日志吗？此操作不可恢复！')) return;
    alert('此功能需要手动删除 wp-content/debug.log 文件');
}

function coolaiCleanOrphanData() {
    if (!confirm('确定要清理孤立数据吗？')) return;
    alert('清理功能开发中...');
}

function coolaiRebuildCache() {
    if (!confirm('确定要重建所有缓存吗？')) return;
    
    jQuery.ajax({
        url: coolaiAdmin.ajaxurl,
        type: 'POST',
        data: {
            action: 'coolai_admin_action',
            action_type: 'clear_cache',
            nonce: coolaiAdmin.nonce
        },
        success: function(response) {
            if (response.success) {
                alert('缓存已清空！刷新页面后将自动重建。');
                location.reload();
            }
        }
    });
}

function coolaiOptimizeDB() {
    if (!confirm('确定要优化数据库吗？这可能需要几分钟时间。')) return;
    alert('数据库优化功能开发中...');
}
</script>
