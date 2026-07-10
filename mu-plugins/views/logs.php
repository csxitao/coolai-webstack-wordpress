<?php
/**
 * 系统日志视图
 */
if (!defined('ABSPATH')) exit;
?>

<div class="wrap coolai-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-text-page" style="color: #667eea;"></span>
        系统日志
    </h1>
    <button class="page-title-action" onclick="location.reload()">
        <span class="dashicons dashicons-update"></span> 刷新
    </button>
    <hr class="wp-header-end">
    
    <div class="coolai-panel" style="margin-top: 20px;">
        <div class="coolai-panel-body">
            <?php if (empty($logs)): ?>
                <div class="coolai-empty-state">
                    <span class="dashicons dashicons-text-page" style="font-size: 64px; opacity: 0.2;"></span>
                    <h3>暂无日志记录</h3>
                    <p>CoolAI 相关的日志会显示在这里</p>
                    <p style="margin-top: 15px;">
                        <small>日志文件: <code><?php echo WP_CONTENT_DIR . '/debug.log'; ?></code></small>
                    </p>
                </div>
            <?php else: ?>
                <div class="coolai-log-filters">
                    <input type="text" id="logSearch" placeholder="搜索日志内容..." 
                           style="width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <select id="logLevelFilter" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-left: 10px;">
                        <option value="">所有级别</option>
                        <option value="INFO">INFO</option>
                        <option value="WARNING">WARNING</option>
                        <option value="ERROR">ERROR</option>
                    </select>
                </div>
                
                <div class="coolai-log-container">
                    <?php foreach ($logs as $log): ?>
                        <?php
                        // 解析日志级别
                        $level = 'INFO';
                        if (strpos($log, '[ERROR]') !== false) {
                            $level = 'ERROR';
                        } elseif (strpos($log, '[WARNING]') !== false) {
                            $level = 'WARNING';
                        }
                        ?>
                        <div class="coolai-log-entry coolai-log-<?php echo strtolower($level); ?>" 
                             data-level="<?php echo $level; ?>"
                             data-search="<?php echo esc_attr(strtolower($log)); ?>">
                            <div class="coolai-log-badge coolai-log-badge-<?php echo strtolower($level); ?>">
                                <?php echo $level; ?>
                            </div>
                            <div class="coolai-log-content">
                                <code><?php echo esc_html($log); ?></code>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 15px; text-align: center; color: #666;">
                    显示最近 <?php echo count($logs); ?> 条日志
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($logs)): ?>
    <div class="coolai-panel" style="margin-top: 20px;">
        <div class="coolai-panel-header">
            <h2><span class="dashicons dashicons-admin-tools"></span> 日志管理</h2>
        </div>
        <div class="coolai-panel-body">
            <p>日志文件位置: <code><?php echo WP_CONTENT_DIR . '/debug.log'; ?></code></p>
            <p>
                <button class="button" onclick="coolaiDownloadLogs()">
                    <span class="dashicons dashicons-download"></span> 下载完整日志
                </button>
                <button class="button button-link-delete" onclick="coolaiClearAllLogs()">
                    <span class="dashicons dashicons-trash"></span> 清空日志文件
                </button>
            </p>
            <p class="description">
                💡 提示：日志文件会随着时间增长，建议定期清理。启用调试模式会生成更多日志。
            </p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.coolai-log-filters {
    margin-bottom: 20px;
}

.coolai-log-container {
    background: #1e1e1e;
    border-radius: 8px;
    padding: 15px;
    max-height: 600px;
    overflow-y: auto;
}

.coolai-log-entry {
    display: flex;
    gap: 10px;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 8px;
    background: rgba(255, 255, 255, 0.05);
    transition: all 0.2s;
}

.coolai-log-entry:hover {
    background: rgba(255, 255, 255, 0.1);
}

.coolai-log-entry.hidden {
    display: none;
}

.coolai-log-info {
    border-left: 3px solid #4facfe;
}

.coolai-log-warning {
    border-left: 3px solid #fa709a;
}

.coolai-log-error {
    border-left: 3px solid #f5576c;
    background: rgba(245, 87, 108, 0.1);
}

.coolai-log-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    color: white;
    align-self: flex-start;
    min-width: 60px;
    text-align: center;
}

.coolai-log-badge-info {
    background: #4facfe;
}

.coolai-log-badge-warning {
    background: #fa709a;
}

.coolai-log-badge-error {
    background: #f5576c;
}

.coolai-log-content {
    flex: 1;
    overflow-x: auto;
}

.coolai-log-content code {
    color: #e0e0e0;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    white-space: pre-wrap;
    word-break: break-all;
}

.coolai-log-container::-webkit-scrollbar {
    width: 8px;
}

.coolai-log-container::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
}

.coolai-log-container::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}

.coolai-log-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>

<script>
jQuery(document).ready(function($) {
    // 搜索和筛选功能
    $('#logSearch, #logLevelFilter').on('input change', function() {
        const searchTerm = $('#logSearch').val().toLowerCase();
        const level = $('#logLevelFilter').val();
        
        $('.coolai-log-entry').each(function() {
            const $entry = $(this);
            const searchContent = $entry.attr('data-search');
            const entryLevel = $entry.attr('data-level');
            
            const matchSearch = !searchTerm || searchContent.indexOf(searchTerm) !== -1;
            const matchLevel = !level || entryLevel === level;
            
            if (matchSearch && matchLevel) {
                $entry.removeClass('hidden');
            } else {
                $entry.addClass('hidden');
            }
        });
    });
    
    // 自动滚动到底部
    const container = document.querySelector('.coolai-log-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
});

function coolaiDownloadLogs() {
    const logs = [];
    document.querySelectorAll('.coolai-log-content code').forEach(el => {
        logs.push(el.textContent);
    });
    
    const blob = new Blob([logs.join('\n')], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'coolai-logs-' + new Date().toISOString().split('T')[0] + '.log';
    a.click();
}

function coolaiClearAllLogs() {
    if (!confirm('确定要清空所有日志吗？此操作不可恢复！\n\n注意：这会清空整个 debug.log 文件，不仅仅是 CoolAI 的日志。')) {
        return;
    }
    
    alert('请通过FTP或SSH手动删除日志文件：\n<?php echo WP_CONTENT_DIR . "/debug.log"; ?>');
}
</script>
