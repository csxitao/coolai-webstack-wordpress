<?php
/**
 * Plugin Name: CoolAI Integration
 * Description: CoolAI 导航系统的WordPress集成插件 - Must-Use Plugin版本 (v2.4.5 安全加固版)
 * Version: 2.4.5
 * Author: CoolAI Team
 * Author URI: https://coolai.top
 * 
 * ========================================
 * 核心特性
 * ========================================
 * 
 * ✅ Must-Use Plugin（自动加载，不可停用）
 * ✅ REST API端点（现代化数据同步）
 * ✅ Favicon永久存储（媒体库管理，永不过期）
 * ✅ 完整的安全机制（Nonce、频率限制、数据验证）
 * ✅ 智能冲突解决（时间戳比较 + 数组合并）
 * ✅ 用户数据隔离（每个用户独立存储）
 * ✅ 详细的调试日志
 * 
 * ========================================
 * API端点
 * ========================================
 * 
 * POST /wp-json/coolai/v1/sync            - 保存数据
 * GET  /wp-json/coolai/v1/sync?key=xxx    - 获取数据
 * GET  /wp-json/coolai/v1/sync/status     - 同步状态
 * GET  /wp-json/coolai/v1/sync/all        - 所有数据
 * GET  /wp-json/coolai/v1/favicon?domain= - Favicon代理（永久存储）
 * 
 * ========================================
 * 安装方法
 * ========================================
 * 
 * 1. 将此文件放置到 /wp-content/mu-plugins/ 目录
 * 2. 无需激活，WordPress自动加载
 * 3. 访问 /wp-json/coolai/v1/sync/status 测试
 * 
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CoolAI WordPress 集成类
 */
class CoolAI_WordPress_Integration {
    
    /**
     * 单例实例
     */
    private static $instance = null;
    
    /**
     * API命名空间
     */
    const API_NAMESPACE = 'coolai/v1';
    
    /**
     * 速率限制配置
     */
    const RATE_LIMIT_REQUESTS = 100;  // 每分钟最大请求数
    const RATE_LIMIT_WINDOW = 60;     // 时间窗口（秒）
    
    /**
     * 数据大小限制（1MB）
     */
    const MAX_DATA_SIZE = 1048576;
    
    /**
     * 日志级别（ERROR | WARNING | INFO | DEBUG）
     */
    const LOG_LEVEL = 'INFO';
    
    /**
     * 获取单例实例
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 构造函数
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 注册REST API端点
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // 在后台显示调试信息（仅管理员可见）
        add_action('admin_notices', array($this, 'show_admin_notices'));
        
        // 添加调试日志
        $this->log('CoolAI Integration 已加载 v2.4.5 (安全加固版)', 'INFO');
    }
    
    /**
     * 注册REST API路由
     */
    public function register_rest_routes() {
        // 保存数据
        register_rest_route(self::API_NAMESPACE, '/sync', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_data'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'key' => array(
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => array($this, 'validate_key'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'data' => array(
                    'required' => true,
                    'type' => 'object'
                ),
                'timestamp' => array(
                    'required' => false,
                    'type' => 'integer'
                )
            )
        ));
        
        // 获取单个数据
        register_rest_route(self::API_NAMESPACE, '/sync', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_data'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'key' => array(
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => array($this, 'validate_key')
                )
            )
        ));
        
        // 获取同步状态
        register_rest_route(self::API_NAMESPACE, '/sync/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_sync_status'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        // 获取所有数据
        register_rest_route(self::API_NAMESPACE, '/sync/all', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_all_data'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        // Favicon代理端点（无需登录）
        register_rest_route(self::API_NAMESPACE, '/favicon', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_favicon_proxy'),
            'permission_callback' => '__return_true',
            'args' => array(
                'domain' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'refresh' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false
                )
            )
        ));
        
        // 获取网页元数据（标题、描述）- 用于添加网站时自动获取信息
        register_rest_route(self::API_NAMESPACE, '/fetch-meta', array(
            'methods' => 'GET',
            'callback' => array($this, 'fetch_page_meta'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'url' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw'
                )
            )
        ));
        
        $this->log('REST API路由已注册', 'DEBUG');
    }
    
    /**
     * 速率限制检查
     */
    private function check_rate_limit($user_id) {
        $key = 'coolai_rate_limit_' . $user_id;
        $count = get_transient($key) ?: 0;
        
        if ($count >= self::RATE_LIMIT_REQUESTS) {
            $this->log("速率限制触发：用户={$user_id}, 请求数={$count}", 'WARNING');
            return new WP_Error(
                'coolai_rate_limit',
                '请求过于频繁，请稍后再试',
                array('status' => 429, 'retry_after' => self::RATE_LIMIT_WINDOW)
            );
        }
        
        set_transient($key, $count + 1, self::RATE_LIMIT_WINDOW);
        return true;
    }
    
    /**
     * 权限检查（已修复：强制登录验证）
     */
    public function check_permission($request) {
        // REST API请求中的用户验证
        $user_id = get_current_user_id();
        
        // 安全加固：未登录用户必须先登录才能访问数据API
        if ($user_id === 0) {
            $this->log('权限检查失败：用户未登录', 'WARNING');
            return new WP_Error(
                'coolai_not_logged_in',
                '请先登录后再访问此功能',
                array('status' => 401)
            );
        }
        
        // 频率限制检查
        $rate_check = $this->check_rate_limit($user_id);
        if (is_wp_error($rate_check)) {
            $this->log("频率限制：用户 {$user_id} 请求过于频繁", 'WARNING');
            return $rate_check;
        }
        
        return true;
    }
    
    /**
     * 验证数据键名
     */
    public function validate_key($key, $request, $param) {
        // 允许的键名格式：字母、数字、下划线、连字符
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
            return new WP_Error(
                'coolai_invalid_key',
                '无效的数据键名格式',
                array('status' => 400)
            );
        }
        
        // 键名长度限制
        if (strlen($key) > 50) {
            return new WP_Error(
                'coolai_key_too_long',
                '数据键名过长（最多50字符）',
                array('status' => 400)
            );
        }
        
        return true;
    }
    
    /**
     * 保存数据
     */
    public function save_data($request) {
        $user_id = get_current_user_id();
        
        // 速率限制检查
        $rate_check = $this->check_rate_limit($user_id);
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }
        
        // 详细的调试日志
        $this->log("=== 保存数据请求开始 ===", 'DEBUG');
        $this->log("用户ID: " . $user_id, 'DEBUG');
        $this->log("请求方法: " . $request->get_method(), 'DEBUG');
        $this->log("Content-Type: " . $request->get_header('content_type'), 'DEBUG');
        
        // 获取所有可能的参数来源
        $url_params = $request->get_url_params();
        $query_params = $request->get_query_params();
        $body_params = $request->get_body_params();
        $json_params = $request->get_json_params();
        
        $this->log("URL参数: " . json_encode($url_params), 'DEBUG');
        $this->log("Query参数: " . json_encode($query_params), 'DEBUG');
        $this->log("Body参数: " . json_encode($body_params), 'DEBUG');
        $this->log("JSON参数: " . json_encode($json_params), 'DEBUG');
        
        // 尝试从meta多个来源获取key
        $key = $request->get_param('key');
        if (empty($key) && !empty($json_params)) {
            $key = isset($json_params['key']) ? $json_params['key'] : null;
        }
        if (empty($key) && !empty($body_params)) {
            $key = isset($body_params['key']) ? $body_params['key'] : null;
        }
        
        // 尝试从多个来源获取data
        $data = $request->get_param('data');
        if (empty($data) && !empty($json_params)) {
            $data = isset($json_params['data']) ? $json_params['data'] : null;
        }
        if (empty($data) && !empty($body_params)) {
            $data = isset($body_params['data']) ? $body_params['data'] : null;
        }
        
        $this->log("解析后的key: " . ($key ?? 'null'), 'DEBUG');
        $this->log("解析后的data: " . (is_null($data) ? 'null' : 'exists'), 'DEBUG');
        
        // 验证key
        if (empty($key)) {
            $this->log("错误：key为空", 'ERROR');
            return new WP_Error(
                'coolai_missing_key',
                '缺少参数：key',
                array('status' => 400)
            );
        }
        
        // 验证数据
        if (empty($data)) {
            return new WP_Error(
                'coolai_empty_data',
                '数据不能为空',
                array('status' => 400)
            );
        }
        
        // 数据大小限制
        $json_data = json_encode($data);
        if (strlen($json_data) > self::MAX_DATA_SIZE) {
            return new WP_Error(
                'coolai_data_too_large',
                '数据过大（最大1MB）',
                array('status' => 413)
            );
        }
        
        // 验证客户端时间戳（防止时间穿越攻击）
        $client_timestamp = isset($data['timestamp']) ? intval($data['timestamp']) : 0;
        $current_time = time();
        $time_diff = abs($current_time - $client_timestamp);
        
        // 如果时间差超过24小时，使用服务器时间
        if ($time_diff > 86400) {
            $this->log("警告：客户端时间戳异常 (差异: {$time_diff}秒)，使用服务器时间", 'WARNING');
            $client_timestamp = $current_time;
        }
        
        // 构造存储数据
        $save_data = array(
            'data' => $data,
            'timestamp' => $current_time,
            'client_timestamp' => $client_timestamp,
            'version' => '2.4.5',
            'user_id' => $user_id
        );
        
        // 获取旧数据用于冲突检测
        $meta_key = 'coolai_' . $key;
        $old_data = get_user_meta($user_id, $meta_key, true);
        
        // 冲突检测
        $server_timestamp = isset($old_data['timestamp']) ? intval($old_data['timestamp']) : 0;
        
        if ($old_data && $server_timestamp > $client_timestamp) {
            // 服务器数据更新，尝试智能合并
            $this->log("检测到冲突：服务器时间戳={$server_timestamp}, 客户端时间戳={$client_timestamp}", 'WARNING');
            
            if ($key === 'bookmarks' || $key === 'folders') {
                // 书签和文件夹数据使用数组合并
                $merged_data = $this->merge_array_data($old_data['data'], $data);
                $save_data['data'] = $merged_data;
                $save_data['merged'] = true;
                $this->log("数据已智能合并", 'INFO');
            }
        }
        
        // 保存到WordPress用户元数据
        $result = update_user_meta($user_id, $meta_key, $save_data);
        
        if ($result === false) {
            global $wpdb;
            $db_error = $wpdb->last_error;
            $this->log("保存失败：用户={$user_id}, 键={$key}, 错误={$db_error}", 'ERROR');
            
            // 生产环境隐藏详细错误，开发环境显示
            $error_message = '数据保存失败，请稍后重试';
            if (defined('WP_DEBUG') && WP_DEBUG && $db_error) {
                $error_message .= '：' . $db_error;
            }
            
            return new WP_Error(
                'coolai_save_failed',
                $error_message,
                array('status' => 500, 'user_id' => $user_id, 'key' => $key)
            );
        }
        
        $this->log("保存成功：用户={$user_id}, 键={$key}, 大小=" . strlen($json_data), 'INFO');
        
        // 记录同步统计
        $this->record_sync_stat($user_id, $key);
        
        // 返回成功响应
        return array(
            'success' => true,
            'key' => $key,
            'timestamp' => $save_data['timestamp'],
            'merged' => isset($save_data['merged']) ? true : false,
            'size' => strlen($json_data)
        );
    }
    
    /**
     * 记录同步统计
     */
    private function record_sync_stat($user_id, $key) {
        $today = date('Y-m-d');
        $week = date('Y') . '-W' . date('W');
        
        // 今日统计
        $today_key = 'coolai_sync_today_' . $today;
        $today_count = get_transient($today_key) ?: 0;
        set_transient($today_key, $today_count + 1, DAY_IN_SECONDS);
        
        // 本周统计
        $week_key = 'coolai_sync_week_' . $week;
        $week_count = get_transient($week_key) ?: 0;
        set_transient($week_key, $week_count + 1, WEEK_IN_SECONDS);
        
        // 用户维度统计（可选）
        $user_today_key = 'coolai_sync_user_' . $user_id . '_' . $today;
        $user_today_count = get_transient($user_today_key) ?: 0;
        set_transient($user_today_key, $user_today_count + 1, DAY_IN_SECONDS);
        
        $this->log("同步统计已记录：用户={$user_id}, 键={$key}, 今日={$today_count}, 本周={$week_count}");
    }
    
    /**
     * 获取数据
     */
    public function get_data($request) {
        $user_id = get_current_user_id();
        $key = $request->get_param('key');
        
        $this->log("获取数据请求：用户={$user_id}, 键={$key}");
        
        // 从WordPress用户元数据获取
        $meta_key = 'coolai_' . $key;
        $data = get_user_meta($user_id, $meta_key, true);
        
        if (empty($data)) {
            $this->log("数据不存在：用户={$user_id}, 键={$key}");
            return array(
                'success' => true,
                'key' => $key,
                'data' => null,
                'timestamp' => 0
            );
        }
        
        $this->log("获取成功：用户={$user_id}, 键={$key}");
        
        return array(
            'success' => true,
            'key' => $key,
            'data' => $data['data'],
            'timestamp' => $data['timestamp'],
            'version' => $data['version']
        );
    }
    
    /**
     * 获取同步状态
     */
    public function get_sync_status($request) {
        $user_id = get_current_user_id();
        
        $this->log("获取状态请求：用户={$user_id}");
        
        // 检查缓存
        $cache_key = 'coolai_status_' . $user_id;
        $cached = wp_cache_get($cache_key, 'coolai_status');
        if ($cached !== false) {
            return $cached;
        }
        
        // 使用更安全的LIKE查询（转义特殊字符）
        global $wpdb;
        $like_pattern = $wpdb->esc_like('coolai_') . '%';
        $meta_keys = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_key FROM {$wpdb->usermeta} 
            WHERE user_id = %d AND meta_key LIKE %s",
            $user_id,
            $like_pattern
        ));
        
        $status = array();
        foreach ($meta_keys as $meta_key) {
            // 再次验证键名格式（双重防护）
            if (!preg_match('/^coolai_[a-zA-Z0-9_-]+$/', $meta_key)) {
                continue;
            }
            
            $key = str_replace('coolai_', '', $meta_key);
            $data = get_user_meta($user_id, $meta_key, true);
            $status[$key] = array(
                'timestamp' => isset($data['timestamp']) ? intval($data['timestamp']) : 0,
                'version' => isset($data['version']) ? sanitize_text_field($data['version']) : 'unknown',
                'size' => strlen(json_encode($data))
            );
        }
        
        $result = array(
            'success' => true,
            'user_id' => $user_id,
            'status' => $status,
            'server_time' => current_time('mysql')
        );
        
        // 缓存5分钟
        wp_cache_set($cache_key, $result, 'coolai_status', 5 * MINUTE_IN_SECONDS);
        
        return $result;
    }
    
    /**
     * 获取所有数据
     */
    public function get_all_data($request) {
        $user_id = get_current_user_id();
        
        $this->log("获取所有数据请求：用户={$user_id}");
        
        // 使用单次查询获取所有数据（避免N+1问题）
        global $wpdb;
        $like_pattern = $wpdb->esc_like('coolai_') . '%';
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$wpdb->usermeta} 
            WHERE user_id = %d AND meta_key LIKE %s",
            $user_id,
            $like_pattern
        ));
        
        $all_data = array();
        foreach ($results as $row) {
            // 验证键名格式
            if (!preg_match('/^coolai_[a-zA-Z0-9_-]+$/', $row->meta_key)) {
                continue;
            }
            
            $key = str_replace('coolai_', '', $row->meta_key);
            $data = maybe_unserialize($row->meta_value);
            
            if (is_array($data) && isset($data['data'])) {
                $all_data[$key] = array(
                    'data' => $data['data'],
                    'timestamp' => isset($data['timestamp']) ? intval($data['timestamp']) : 0,
                    'version' => isset($data['version']) ? sanitize_text_field($data['version']) : 'unknown'
                );
            }
        }
        
        return array(
            'success' => true,
            'user_id' => $user_id,
            'data' => $all_data,
            'count' => count($all_data)
        );
    }
    
    /**
     * 智能合并数组数据（书签、文件夹）
     */
    private function merge_array_data($server_data, $client_data) {
        // 如果不是数组，直接返回客户端数据
        if (!is_array($server_data) || !is_array($client_data)) {
            return $client_data;
        }
        
        // 合并数组，保留客户端修改
        $merged = $server_data;
        
        foreach ($client_data as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                // 递归合并
                $merged[$key] = $this->merge_array_data($merged[$key], $value);
            } else {
                // 直接覆盖
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }
    
    /**
     * 增强型 Favicon 代理：本地化存储版
     * 逻辑：检查本地 -> (无) -> 抓取并保存 -> 返回本地 URL
     */
    public function get_favicon_proxy($request) {
        $domain = $request->get_param('domain');
        $refresh = $request->get_param('refresh');
        
        // 清理域名
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/\/.*$/', '', $domain);
        $domain = strtolower(trim($domain));
        
        // 严格验证域名格式（防止目录遍历和SQL注入）
        if (empty($domain) || !preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)*$/i', $domain)) {
            return $this->get_default_favicon_svg(substr($domain, 0, 1));
        }
        
        // 域名长度限制（防止内存溢出）
        if (strlen($domain) > 253) {
            return $this->get_default_favicon_svg(substr($domain, 0, 1));
        }
        
        // 检查缓存（内存缓存优先）
        $cache_key = 'coolai_favicon_' . md5($domain);
        if (!$refresh) {
            $cached_url = wp_cache_get($cache_key, 'coolai_favicons');
            if ($cached_url !== false) {
                wp_redirect($cached_url);
                exit;
            }
        }
        
        // 1. 优先检查媒体库中手动上传的favicon（最高优先级）
        if (!$refresh) {
            $saved_favicon = $this->get_saved_favicon($domain);
            if ($saved_favicon && !empty($saved_favicon['url'])) {
                // 设置内存缓存
                wp_cache_set($cache_key, $saved_favicon['url'], 'coolai_favicons', 12 * HOUR_IN_SECONDS);
                wp_redirect($saved_favicon['url']);
                exit;
            }
        }
        
        // 2. 定义本地存储路径（使用安全的文件名）
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/coolai_favicons';
        $base_url = $upload_dir['baseurl'] . '/coolai_favicons';
        $safe_filename = sanitize_file_name($domain) . '.png';
        $file_path = $base_dir . '/' . $safe_filename;
        $file_url = $base_url . '/' . $safe_filename;

        // 确保目录存在（增加安全检查）
        if (!file_exists($base_dir)) {
            if (!wp_mkdir_p($base_dir)) {
                $this->log('创建favicon目录失败');
                return $this->get_default_favicon_svg(substr($domain, 0, 1));
            }
            // 创建 .htaccess 保护文件
            $htaccess = $base_dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "<FilesMatch \"\\.(png|jpg|ico|svg|gif|webp)$\">
    Allow from all
</FilesMatch>
Deny from all");
            }
        }

        // 3. 如果文件存在且不是强制刷新，直接重定向到本地文件 (极速!)
        // 注意：这里只检查自动缓存的文件，手动上传的已在步骤1处理
        if (file_exists($file_path) && !$refresh) {
            // 设置内存缓存（12小时）
            wp_cache_set($cache_key, $file_url, 'coolai_favicons', 12 * HOUR_IN_SECONDS);
            wp_redirect($file_url);
            exit;
        }

        // 4. 强制刷新时：先清除媒体库中的旧favicon，再重新抓取
        if ($refresh) {
            // 删除媒体库中的手动上传版本
            $this->delete_saved_favicon($domain);
            // 删除文件系统缓存
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
            // 清除内存缓存
            wp_cache_delete($cache_key, 'coolai_favicons');
        }

        // 5. 文件不存在或强制刷新：开始抓取
        // 优先使用 Google S2 (高清)，备用 DuckDuckGo
        $sources = [
            'https://www.google.com/s2/favicons?domain=' . urlencode($domain) . '&sz=128',
            'https://icons.duckduckgo.com/ip3/' . urlencode($domain) . '.ico',
            'https://' . urlencode($domain) . '/favicon.ico'
        ];

        $image_data = false;
        $max_size = 512 * 1024; // 最大512KB

        foreach ($sources as $source) {
            $response = wp_remote_get($source, array(
                'timeout' => 8,
                'sslverify' => true, // 启用SSL验证提高安全性
                'redirection' => 3,
                'headers' => array(
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                )
            ));

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $content_type = wp_remote_retrieve_header($response, 'content-type');
                $body = wp_remote_retrieve_body($response);
                
                // 严格验证：检查大小、类型和内容
                if (!empty($body) && 
                    strlen($body) > 100 && 
                    strlen($body) < $max_size &&
                    (strpos($content_type, 'image') !== false || strpos($content_type, 'application/octet-stream') !== false)) {
                    
                    // 验证是否真的是图片文件（防止XSS）
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $detected_type = finfo_buffer($finfo, $body);
                    finfo_close($finfo);
                    
                    if (strpos($detected_type, 'image/') === 0) {
                        $image_data = $body;
                        $this->log("Favicon下载成功: {$domain} ({$detected_type}, " . strlen($body) . " bytes)");
                        break;
                    }
                }
            }
        }

        // 4. 保存并服务
        if ($image_data) {
            // 安全保存到本地
            $bytes_written = @file_put_contents($file_path, $image_data, LOCK_EX);
            if ($bytes_written !== false) {
                // 设置文件权限（只读）
                @chmod($file_path, 0644);
                // 设置内存缓存
                wp_cache_set($cache_key, $file_url, 'coolai_favicons', 12 * HOUR_IN_SECONDS);
                // 重定向到新保存的本地文件
                wp_redirect($file_url);
                exit;
            } else {
                $this->log("Favicon保存失败: {$domain}");
            }
        }
        
        // 5. 抓取彻底失败：返回默认图标
        // 如果本地有旧文件（即使刷新失败），还是返回旧文件
        if (file_exists($file_path)) {
            wp_redirect($file_url);
            exit;
        }
        
        // 实在没有，返回默认图标
        $letter = strtoupper(substr($domain, 0, 1));
        return $this->get_default_favicon_svg($letter);
    }
    
    /**
     * 删除已存储的 favicon（用于强制刷新）
     */
    private function delete_stored_favicon($domain) {
        global $wpdb;
        
        // 查找对应的附件ID
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = 'coolai_favicon_domain' 
            AND meta_value = %s 
            LIMIT 1",
            $domain
        ));
        
        if ($attachment_id) {
            // 物理删除附件（包括文件）
            $result = wp_delete_attachment($attachment_id, true);
            if ($result) {
                $this->log("已删除旧Favicon附件: {$domain} (ID: {$attachment_id})");
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 从数据库检查是否已保存favicon
     */
    private function get_saved_favicon($domain) {
        global $wpdb;
        
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = 'coolai_favicon_domain' 
            AND meta_value = %s 
            LIMIT 1",
            $domain
        ));
        
        if ($attachment_id) {
            $url = wp_get_attachment_url($attachment_id);
            if ($url) {
                $this->log("Favicon从媒体库加载: {$domain}");
                return array(
                    'id' => $attachment_id,
                    'url' => $url
                );
            }
        }
        
        return false;
    }
    
    /**
     * 从多个服务获取favicon（针对日本服务器优化）
     */
    private function fetch_favicon_from_services($domain) {
        // 优先级排序：日本服务器访问国际服务快
        $services = array(
            "https://www.google.com/s2/favicons?domain={$domain}&sz=128",
            "https://{$domain}/favicon.ico",
            "https://icons.duckduckgo.com/ip3/{$domain}.ico",
            "https://api.iowen.cn/favicon/{$domain}.png",
            "https://favicon.cccyun.cc/{$domain}"
        );
        
        foreach ($services as $url) {
            $response = wp_remote_get($url, array(
                'timeout' => 8,
                'redirection' => 3,
                'sslverify' => false,
                'headers' => array(
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                )
            ));
            
            if (!is_wp_error($response)) {
                $code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                
                // 检查是否为有效图片（大于100字节）
                if ($code === 200 && strlen($body) > 100) {
                    // 检测图片类型
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_buffer($finfo, $body);
                    finfo_close($finfo);
                    
                    if (strpos($mime, 'image/') === 0) {
                        $this->log("Favicon获取成功: {$domain} from {$url}");
                        return array(
                            'data' => $body,
                            'mime' => $mime
                        );
                    }
                }
            }
        }
        
        $this->log("Favicon获取失败: {$domain}");
        return false;
    }
    
    /**
     * 保存favicon到WordPress媒体库（永久存储）
     */
    private function save_favicon_to_media($domain, $image_data) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // 确定文件扩展名
        $extension = 'png';
        if (strpos($image_data['mime'], 'jpeg') !== false || strpos($image_data['mime'], 'jpg') !== false) {
            $extension = 'jpg';
        } elseif (strpos($image_data['mime'], 'gif') !== false) {
            $extension = 'gif';
        } elseif (strpos($image_data['mime'], 'svg') !== false) {
            $extension = 'svg';
        } elseif (strpos($image_data['mime'], 'x-icon') !== false || strpos($image_data['mime'], 'vnd.microsoft.icon') !== false) {
            $extension = 'ico';
        } elseif (strpos($image_data['mime'], 'webp') !== false) {
            $extension = 'webp';
        }
        
        // 生成唯一文件名
        $filename = 'favicon-' . sanitize_file_name($domain) . '.' . $extension;
        
        // 创建临时文件
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['path'] . '/' . $filename;
        
        if (file_put_contents($temp_file, $image_data['data']) === false) {
            $this->log("Favicon保存失败: 无法写入临时文件");
            return false;
        }
        
        // 准备附件数据
        $attachment = array(
            'post_mime_type' => $image_data['mime'],
            'post_title' => 'Favicon: ' . $domain,
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        // 插入到媒体库
        $attachment_id = wp_insert_attachment($attachment, $temp_file);
        
        if (is_wp_error($attachment_id)) {
            @unlink($temp_file);
            $this->log("Favicon保存失败: wp_insert_attachment错误");
            return false;
        }
        
        // 生成缩略图元数据
        $attach_data = wp_generate_attachment_metadata($attachment_id, $temp_file);
        wp_update_attachment_metadata($attachment_id, $attach_data);
        
        // 保存域名关联（用于查询）
        update_post_meta($attachment_id, 'coolai_favicon_domain', $domain);
        update_post_meta($attachment_id, 'coolai_favicon_saved_time', time());
        
        // 获取URL
        $url = wp_get_attachment_url($attachment_id);
        
        $this->log("Favicon已保存到媒体库: {$domain} -> {$url}");
        
        return $url;
    }
    
    /**
     * 获取网页元数据（标题、描述）
     * 用于添加网站时自动获取信息
     */
    public function fetch_page_meta($request) {
        $url = $request->get_param('url');
        
        if (empty($url)) {
            return new WP_Error('missing_url', '缺少URL参数', array('status' => 400));
        }
        
        // 规范化 URL
        if (!preg_match('/^https?:\\/\\//i', $url)) {
            $url = 'https://' . $url;
        }
        
        // 验证URL格式
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', '无效的 URL', array('status' => 400));
        }
        
        $domain = parse_url($url, PHP_URL_HOST);
        if (!$domain) {
            return new WP_Error('invalid_domain', '无法解析域名', array('status' => 400));
        }
        
        // 抓取网页
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8'
            )
        ));
        
        if (is_wp_error($response)) {
            // 抓取失败时返回基于域名的默认值
            return rest_ensure_response(array(
                'success' => true,
                'data' => array(
                    'title' => $this->domain_to_title($domain),
                    'description' => '',
                    'icon_url' => '',
                    'fallback' => true
                )
            ));
        }
        
        $html = wp_remote_retrieve_body($response);
        
        // 检测编码并转换
        $encoding = mb_detect_encoding($html, array('UTF-8', 'GBK', 'GB2312', 'BIG5', 'ISO-8859-1'), true);
        if ($encoding && $encoding !== 'UTF-8') {
            $html = mb_convert_encoding($html, 'UTF-8', $encoding);
        }
        
        // 提取标题
        $title = '';
        if (preg_match('/<title[^>]*>([^<]+)<\\/title>/i', $html, $matches)) {
            $title = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }
        
        // 提取 og:title 作为备选
        if (empty($title) && preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $title = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }
        
        // 如果还是没有标题，使用域名
        if (empty($title)) {
            $title = $this->domain_to_title($domain);
        }
        
        // 清理标题（移除常见后缀）
        $title = $this->clean_page_title($title);
        
        // 提取描述
        $description = '';
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $description = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }
        if (empty($description) && preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $description = trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }
        
        // 限制描述长度
        if (mb_strlen($description) > 200) {
            $description = mb_substr($description, 0, 200) . '...';
        }
        
        // 获取 favicon URL
        $icon_url = '';
        $upload_dir = wp_upload_dir();
        $favicon_dir = $upload_dir['basedir'] . '/coolai_favicons';
        $favicon_url_base = $upload_dir['baseurl'] . '/coolai_favicons';
        $domain_lower = strtolower($domain);
        
        // 检查是否已有缓存
        foreach (array('png', 'ico', 'jpg', 'svg') as $ext) {
            if (file_exists($favicon_dir . '/' . $domain_lower . '.' . $ext)) {
                $icon_url = $favicon_url_base . '/' . $domain_lower . '.' . $ext;
                break;
            }
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'title' => $title,
                'description' => $description,
                'icon_url' => $icon_url,
                'domain' => $domain
            )
        ));
    }
    
    /**
     * 域名转标题（美化显示）
     */
    private function domain_to_title($domain) {
        // 移除 www. 前缀
        $domain = preg_replace('/^www\\./', '', $domain);
        // 取第一个点之前的部分
        $parts = explode('.', $domain);
        $name = $parts[0];
        // 首字母大写
        return ucfirst($name);
    }
    
    /**
     * 清理页面标题（移除常见后缀）
     */
    private function clean_page_title($title) {
        // 移除常见后缀分隔符及其后内容
        $separators = array(' - ', ' | ', ' – ', ' — ', ' :: ', ' · ', ' > ');
        foreach ($separators as $sep) {
            if (strpos($title, $sep) !== false) {
                $parts = explode($sep, $title);
                // 如果分隔后第一部分太短，保留原标题
                if (mb_strlen($parts[0]) >= 3) {
                    $title = trim($parts[0]);
                    break;
                }
            }
        }
        return $title;
    }
    
    /**
     * 输出图片
     */
    private function output_image($mime_type, $base64_data) {
        header('Content-Type: ' . $mime_type);
        header('Cache-Control: public, max-age=2592000'); // 30天
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
        echo base64_decode($base64_data);
    }
    
    /**
     * 生成默认SVG图标
     */
    private function get_default_favicon_svg($letter) {
        $letter = strtoupper(substr($letter, 0, 1));
        if (!preg_match('/[A-Z0-9]/', $letter)) {
            $letter = '?';
        }
        
        // 根据字母生成颜色
        $colors = array(
            '#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b',
            '#fa709a', '#fee140', '#30cfd0', '#a8edea', '#fed6e3'
        );
        $color_index = ord($letter) % count($colors);
        $color = $colors[$color_index];
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="64" height="64" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="grad' . $color_index . '" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:' . $color . ';stop-opacity:1" />
            <stop offset="100%" style="stop-color:' . $this->adjust_color($color, -20) . ';stop-opacity:1" />
        </linearGradient>
    </defs>
    <rect width="64" height="64" rx="12" fill="url(#grad' . $color_index . ')"/>
    <text x="32" y="44" font-family="Arial, sans-serif" font-size="32" font-weight="bold" fill="white" text-anchor="middle">' . htmlspecialchars($letter) . '</text>
</svg>';
        
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=86400'); // 1天
        echo $svg;
        exit;
    }
    
    /**
     * 调整颜色亮度
     */
    private function adjust_color($hex, $steps) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
                  . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
                  . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
    
    /**
     * 日志记录（支持日志级别过滤）
     */
    private function log($message, $level = 'INFO') {
        // 日志级别优先级：ERROR > WARNING > INFO > DEBUG
        $levels = array('ERROR' => 4, 'WARNING' => 3, 'INFO' => 2, 'DEBUG' => 1);
        $current_level = $levels[self::LOG_LEVEL] ?? 2;
        $message_level = $levels[$level] ?? 2;
        
        // 如果消息级别低于配置级别，不记录
        if ($message_level < $current_level) {
            return;
        }
        
        // 生产环境只记录警告和错误
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            if ($level !== 'ERROR' && $level !== 'WARNING') {
                return;
            }
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        error_log("[CoolAI][{$level}][{$timestamp}] {$message}");
    }
    
    /**
     * 后台通知（仅管理员）
     */
    public function show_admin_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // 检查API端点是否可访问
        $rest_url = rest_url(self::API_NAMESPACE . '/sync/status');
        
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>CoolAI Integration v2.4.2</strong> 已激活 (性能优化版)</p>';
        echo '<p>✅ 对象缓存 | ✅ 速率限制 | ✅ 日志分级 | ✅ 同步统计</p>';
        echo '<p>REST API端点: <code>' . esc_html($rest_url) . '</code></p>';
        echo '<p><a href="' . esc_url($rest_url) . '" target="_blank">测试API</a></p>';
        echo '</div>';
    }
}

// 初始化插件
CoolAI_WordPress_Integration::get_instance();

// 调试信息
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[CoolAI] Must-Use Plugin loaded successfully v2.4.2 (Performance Optimized)');
}
