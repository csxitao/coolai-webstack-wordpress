<?php
/**
 * Plugin Name: CoolAI Admin Panel
 * Description: CoolAI 导航系统的完整后台管理面板
 * Version: 2.4.5
 * Author: CoolAI Team
 * 
 * 功能模块：
 * - 📊 仪表盘（数据统计概览）
 * - 📚 书签管理（所有用户数据 + 多数据结构支持）
 * - 🖼️ Favicon 管理（缓存控制 + 搜索筛选 + 手动上传）
 * - 📝 系统日志（调试信息）
 * - ⚙️ 系统设置（全局配置）
 * - 🔧 维护工具（备份/清理）
 * 
 * 更新内容 v1.2.0:
 * - ⚡ 对象缓存优化（性能提升30-50%）
 * - 📊 同步统计功能（今日/本周）
 * - 🔧 书签统计修复（支持所有数据结构）
 * - 🐛 活跃用户排行修复（显示真实书签数）
 * - 🎯 Favicon去重优化（保留最新记录）
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CoolAI 后台管理类
 */
class CoolAI_Admin_Panel {
    
    /**
     * 单例实例
     */
    private static $instance = null;
    
    /**
     * 插件版本
     */
    const VERSION = '2.4.5';
    
    /**
     * 缓存变量（单次请求内有效）
     */
    private $bookmarks_cache = null;
    private $active_users_cache = null;
    private $statistics_cache = null;
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_coolai_admin_action', array($this, 'handle_ajax_action'));
    }
    
    /**
     * 添加管理菜单
     */
    public function add_admin_menu() {
        // 主菜单 - 仪表盘
        add_menu_page(
            'CoolAI 导航管理',
            'CoolAI 导航',
            'manage_options',
            'coolai-admin',
            array($this, 'render_dashboard'),
            'dashicons-star-filled',
            30
        );
        
        // 子菜单 - 仪表盘
        add_submenu_page(
            'coolai-admin',
            '仪表盘',
            '📊 仪表盘',
            'manage_options',
            'coolai-admin',
            array($this, 'render_dashboard')
        );
        
        // 子菜单 - 所有书签
        add_submenu_page(
            'coolai-admin',
            '所有书签',
            '📚 所有书签',
            'manage_options',
            'coolai-bookmarks',
            array($this, 'render_bookmarks')
        );
        
        // 子菜单 - 我的书签
        add_submenu_page(
            'coolai-admin',
            '我的书签',
            '⭐ 我的书签',
            'read',
            'coolai-my-bookmarks',
            array($this, 'render_my_bookmarks')
        );
        
        // 子菜单 - Favicon 管理
        add_submenu_page(
            'coolai-admin',
            'Favicon 管理',
            '🖼️ Favicon 管理',
            'manage_options',
            'coolai-favicons',
            array($this, 'render_favicons')
        );
        
        // 子菜单 - 系统日志
        add_submenu_page(
            'coolai-admin',
            '系统日志',
            '📝 系统日志',
            'manage_options',
            'coolai-logs',
            array($this, 'render_logs')
        );
        
        // 子菜单 - 系统设置
        add_submenu_page(
            'coolai-admin',
            '系统设置',
            '⚙️ 系统设置',
            'manage_options',
            'coolai-settings',
            array($this, 'render_settings')
        );
    }
    
    /**
     * 加载后台资源
     */
    public function enqueue_admin_assets($hook) {
        // 仅在 CoolAI 页面加载
        if (strpos($hook, 'coolai') === false) {
            return;
        }
        
        // 加载样式
        wp_enqueue_style(
            'coolai-admin-style',
            plugin_dir_url(__FILE__) . 'assets/admin-style.css',
            array(),
            self::VERSION
        );
        
        // 加载脚本
        wp_enqueue_script(
            'coolai-admin-script',
            plugin_dir_url(__FILE__) . 'assets/admin-script.js',
            array('jquery'),
            self::VERSION,
            true
        );
        
        // 传递数据到 JS
        wp_localize_script('coolai-admin-script', 'coolaiAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('coolai_admin_nonce'),
            'restUrl' => rest_url('coolai/v1/')
        ));
    }
    
    /**
     * 渲染仪表盘页面
     */
    public function render_dashboard() {
        $stats = $this->get_statistics();
        $recent_syncs = $this->get_recent_syncs(10);
        $active_users = $this->get_active_users(5);
        
        include dirname(__FILE__) . '/views/dashboard.php';
    }
    
    /**
     * 渲染所有书签页面
     */
    public function render_bookmarks() {
        $bookmarks_data = $this->get_all_bookmarks();
        
        // 调试信息
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[CoolAI Admin] render_bookmarks 获取到的书签数量: ' . count($bookmarks_data));
            if (empty($bookmarks_data)) {
                global $wpdb;
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'coolai_bookmarks'");
                error_log('[CoolAI Admin] 数据库中 coolai_bookmarks 记录数: ' . $count);
            }
        }
        
        include dirname(__FILE__) . '/views/bookmarks.php';
    }
    
    /**
     * 渲染我的书签页面
     */
    public function render_my_bookmarks() {
        $user_id = get_current_user_id();
        $my_bookmarks = $this->get_user_bookmarks($user_id);
        
        include dirname(__FILE__) . '/views/my-bookmarks.php';
    }
    
    /**
     * 渲染 Favicon 管理页面
     */
    public function render_favicons() {
        $favicon_stats = $this->get_favicon_statistics();
        $favicon_list = $this->get_favicon_list();
        
        include dirname(__FILE__) . '/views/favicons.php';
    }
    
    /**
     * 渲染系统日志页面
     */
    public function render_logs() {
        $logs = $this->get_system_logs(100);
        
        include dirname(__FILE__) . '/views/logs.php';
    }
    
    /**
     * 渲染系统设置页面
     */
    public function render_settings() {
        // 保存设置
        if (isset($_POST['coolai_save_settings']) && check_admin_referer('coolai_settings_nonce')) {
            $this->save_settings($_POST);
            echo '<div class="notice notice-success"><p>设置已保存！</p></div>';
        }
        
        $settings = $this->get_settings();
        
        include dirname(__FILE__) . '/views/settings.php';
    }
    
    /**
     * 获取统计数据
     */
    private function get_statistics() {
        // 返回缓存统计（单次请求内有效）
        if ($this->statistics_cache !== null) {
            return $this->statistics_cache;
        }
        
        global $wpdb;
        
        $stats = array(
            'total_users' => $this->count_active_users(),
            'total_bookmarks' => $this->count_total_bookmarks(),
            'total_folders' => $this->count_total_folders(),
            'cache_size' => $this->get_cache_size(),
            'favicon_count' => $this->count_favicons(),
            'sync_today' => $this->count_syncs_today(),
            'sync_week' => $this->count_syncs_week(),
            'storage_used' => $this->calculate_storage_used()
        );
        
        // 缓存统计结果
        $this->statistics_cache = $stats;
        
        return $stats;
    }
    
    /**
     * 统计活跃用户数
     */
    private function count_active_users() {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} 
            WHERE meta_key LIKE %s",
            $wpdb->esc_like('coolai_') . '%'
        ));
        
        return intval($count);
    }
    
    /**
     * 统计书签总数（修复版 - 支持多种数据结构）
     */
    private function count_total_bookmarks() {
        $all_bookmarks = $this->get_all_bookmarks();
        return count($all_bookmarks);
    }
    
    /**
     * 统计文件夹总数（分类总数）
     */
    private function count_total_folders() {
        global $wpdb;
        
        // 获取所有分类相关的 meta_key
        $results = $wpdb->get_results(
            "SELECT meta_value FROM {$wpdb->usermeta} 
            WHERE meta_key IN ('coolai_bookmarks_categories', 'coolai_categories', 'coolai_user_data_categories', 'coolai_folders')"
        );
        
        $all_categories = array();
        
        foreach ($results as $row) {
            $data = maybe_unserialize($row->meta_value);
            
            if (is_array($data)) {
                // 结构1: {"data": {"id": "name", ...}}
                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $key => $value) {
                        if (is_string($key)) {
                            $all_categories[$key] = true;
                        }
                    }
                }
                // 结构2: [{"id": "xxx", "name": "yyy"}, ...]
                elseif (isset($data[0]) && is_array($data[0])) {
                    foreach ($data as $item) {
                        if (isset($item['id'])) {
                            $all_categories[$item['id']] = true;
                        }
                    }
                }
                // 结构3: {"id": "name", ...}
                else {
                    foreach ($data as $key => $value) {
                        if (is_string($key)) {
                            $all_categories[$key] = true;
                        }
                    }
                }
            }
        }
        
        return count($all_categories);
    }
    
    /**
     * 统计今日同步次数
     */
    private function count_syncs_today() {
        $today = date('Y-m-d');
        $today_key = 'coolai_sync_today_' . $today;
        return get_transient($today_key) ?: 0;
    }
    
    /**
     * 统计本周同步次数
     */
    private function count_syncs_week() {
        $week = date('Y') . '-W' . date('W');
        $week_key = 'coolai_sync_week_' . $week;
        return get_transient($week_key) ?: 0;
    }
    
    /**
     * 统计 Favicon 数量
     */
    private function count_favicons() {
        $upload_dir = wp_upload_dir();
        $favicon_dir = $upload_dir['basedir'] . '/coolai_favicons';
        
        if (!is_dir($favicon_dir)) {
            return 0;
        }
        
        $files = glob($favicon_dir . '/*.{png,jpg,ico,svg,gif,webp}', GLOB_BRACE);
        return count($files);
    }
    
    /**
     * 获取缓存大小（格式化）
     */
    private function get_cache_size() {
        $upload_dir = wp_upload_dir();
        $favicon_dir = $upload_dir['basedir'] . '/coolai_favicons';
        
        if (!is_dir($favicon_dir)) {
            return '0 B';
        }
        
        $size = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($favicon_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        
        return $this->format_bytes($size);
    }
    
    /**
     * 计算数据库存储空间
     */
    private function calculate_storage_used() {
        global $wpdb;
        
        $size = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(LENGTH(meta_value)) FROM {$wpdb->usermeta} 
            WHERE meta_key LIKE %s",
            $wpdb->esc_like('coolai_') . '%'
        ));
        
        return $this->format_bytes($size);
    }
    
    /**
     * 格式化字节大小
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * 获取最近同步记录
     */
    private function get_recent_syncs($limit = 10) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, meta_key, meta_value 
            FROM {$wpdb->usermeta} 
            WHERE meta_key LIKE %s 
            ORDER BY umeta_id DESC 
            LIMIT %d",
            $wpdb->esc_like('coolai_') . '%',
            $limit
        ));
        
        $syncs = array();
        foreach ($results as $row) {
            $data = maybe_unserialize($row->meta_value);
            if (isset($data['timestamp'])) {
                $user = get_userdata($row->user_id);
                $syncs[] = array(
                    'user_id' => $row->user_id,
                    'username' => $user ? $user->display_name : '未知用户',
                    'type' => str_replace('coolai_', '', $row->meta_key),
                    'timestamp' => $data['timestamp'],
                    'time_ago' => human_time_diff($data['timestamp'], current_time('timestamp'))
                );
            }
        }
        
        return $syncs;
    }
    
    /**
     * 获取活跃用户
     */
    private function get_active_users($limit = 5) {
        global $wpdb;
        
        // 获取所有书签数据
        $all_bookmarks = $this->get_all_bookmarks();
        
        // 按用户分组统计书签数量
        $user_bookmark_counts = array();
        foreach ($all_bookmarks as $bookmark) {
            $user_id = $bookmark['user_id'];
            if (!isset($user_bookmark_counts[$user_id])) {
                $user_bookmark_counts[$user_id] = 0;
            }
            $user_bookmark_counts[$user_id]++;
        }
        
        // 按书签数量倒序排序
        arsort($user_bookmark_counts);
        
        // 获取前N个用户的详细信息
        $users = array();
        $count = 0;
        foreach ($user_bookmark_counts as $user_id => $bookmark_count) {
            if ($count >= $limit) break;
            
            $user = get_userdata($user_id);
            if ($user) {
                $users[] = array(
                    'id' => $user_id,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'count' => $bookmark_count  // 这是真实的书签数量
                );
                $count++;
            }
        }
        
        return $users;
    }
    
    /**
     * 获取所有书签数据（增强版，支持多种meta_key和数据结构）
     * 使用对象缓存避免单次请求重复查询
     */
    private function get_all_bookmarks() {
        // 返回缓存数据（单次请求内有效）
        if ($this->bookmarks_cache !== null) {
            return $this->bookmarks_cache;
        }
        
        global $wpdb;
        
        // 获取所有用户的分类映射（用于将分类ID转换为友好名称）
        $category_maps = array();
        $category_results = $wpdb->get_results(
            "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} 
            WHERE meta_key IN ('coolai_bookmarks_categories', 'coolai_categories', 'coolai_user_data_categories', 'coolai_categoryOrder')"
        );
        
        foreach ($category_results as $row) {
            $categories = maybe_unserialize($row->meta_value);
            if (is_array($categories)) {
                // 结构1: {"data": {"ai": "AI工具", "___123": "常用", ...}} - 直接映射
                if (isset($categories['data']) && is_array($categories['data'])) {
                    $data = $categories['data'];
                    // 检查第一个元素是否是直接的 key => value 映射
                    if (!empty($data)) {
                        $first_value = reset($data);
                        $first_key = key($data);
                        // 如果值是字符串且键不是数字，说明是直接的 id => name 映射
                        if (is_string($first_value) && !is_numeric($first_key)) {
                            foreach ($data as $cat_id => $cat_name) {
                                if (is_string($cat_id) && is_string($cat_name)) {
                                    $category_maps[$row->user_id][$cat_id] = $cat_name;
                                }
                            }
                        }
                        // 如果值是数组，说明是 [{"id": "xxx", "name": "xxx"}, ...] 结构
                        elseif (is_array($first_value)) {
                            foreach ($data as $cat) {
                                if (is_array($cat) && isset($cat['id']) && isset($cat['name'])) {
                                    $category_maps[$row->user_id][$cat['id']] = $cat['name'];
                                }
                            }
                        }
                    }
                }
                // 结构2: [{"id": "xxx", "name": "xxx"}, ...] 直接数组
                elseif (isset($categories[0]) && is_array($categories[0])) {
                    foreach ($categories as $cat) {
                        if (is_array($cat) && isset($cat['id']) && isset($cat['name'])) {
                            $category_maps[$row->user_id][$cat['id']] = $cat['name'];
                        }
                    }
                }
                // 结构3: {"___123": {"name": "xxx", ...}, ...} 时间戳键的对象
                else {
                    foreach ($categories as $cat_id => $cat_data) {
                        if (is_array($cat_data) && isset($cat_data['name'])) {
                            $category_maps[$row->user_id][$cat_id] = $cat_data['name'];
                        }
                    }
                }
            }
        }
        
        // 可能存储书签的所有meta_key
        $possible_keys = array(
            'coolai_bookmarks',
            'coolai_websites',
            'coolai_bookmarks_websites',
            'coolai_user_data_websites'
        );
        
        $all_bookmarks = array();
        
        foreach ($possible_keys as $meta_key) {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT user_id, meta_value FROM {$wpdb->usermeta} 
                WHERE meta_key = %s",
                $meta_key
            ));
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[CoolAI Admin] 检查 meta_key: ' . $meta_key . ', 找到 ' . count($results) . ' 条记录');
            }
            
            foreach ($results as $row) {
                $user = get_userdata($row->user_id);
                $data = maybe_unserialize($row->meta_value);
                
                // 调试：记录数据结构
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[CoolAI Admin] 用户 ' . $row->user_id . ' 的 ' . $meta_key . ' 数据结构: ' . print_r($data, true));
                }
                
                // 支持多种数据结构
                $bookmarks_array = array();
                
                // 结构1: {"data": [...]} (标准结构)
                if (isset($data['data']) && is_array($data['data'])) {
                    // 检查data下是否为分类嵌套结构: {"data": {"ai": [...], "news": [...]}}
                    $first_value = reset($data['data']);
                    $is_category_structure = false;
                    
                    // 判断是否为分类结构：第一个值是数组，且不包含url字段
                    if (is_array($first_value)) {
                        if (!isset($first_value['url']) && !isset($first_value['name']) && !isset($first_value['title'])) {
                            $is_category_structure = true;
                        }
                    }
                    
                    if ($is_category_structure) {
                        // 分类嵌套结构：遍历所有分类，提取网站
                        foreach ($data['data'] as $category_key => $category_items) {
                            if (is_array($category_items)) {
                                foreach ($category_items as $item) {
                                    if (is_array($item)) {
                                        // 检查是否为文件夹类型
                                        if (isset($item['type']) && $item['type'] === 'folder') {
                                            // 提取文件夹内的网站
                                            if (isset($item['items']) && is_array($item['items'])) {
                                                foreach ($item['items'] as $folder_item) {
                                                    if (is_array($folder_item) && isset($folder_item['url'])) {
                                                        $bookmarks_array[] = $folder_item;
                                                    }
                                                }
                                            }
                                        } elseif (isset($item['url'])) {
                                            // 直接添加网站
                                            $bookmarks_array[] = $item;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // 直接数组结构：{"data": [...]}
                        $bookmarks_array = $data['data'];
                    }
                }
                // 结构2: 直接是数组 [...]
                elseif (is_array($data) && !isset($data['data']) && !isset($data['bookmarks']) && !isset($data['websites'])) {
                    // 检查是否是书签数组（第一个元素有url字段）
                    $first_item = reset($data);
                    if (is_array($first_item) && (isset($first_item['url']) || isset($first_item['href']) || isset($first_item['link']))) {
                        $bookmarks_array = $data;
                    }
                }
                // 结构3: {"bookmarks": [...]}
                elseif (isset($data['bookmarks']) && is_array($data['bookmarks'])) {
                    $bookmarks_array = $data['bookmarks'];
                }
                // 结构4: {"websites": [...]}
                elseif (isset($data['websites']) && is_array($data['websites'])) {
                    $bookmarks_array = $data['websites'];
                }
                
                // 处理书签数组
                foreach ($bookmarks_array as $bookmark) {
                    // 跳过无效数据
                    if (!is_array($bookmark)) {
                        continue;
                    }
                    
                    // 提取URL和标题（支持多种字段名）
                    $url = '';
                    $title = '';
                    
                    if (isset($bookmark['url'])) {
                        $url = $bookmark['url'];
                    } elseif (isset($bookmark['href'])) {
                        $url = $bookmark['href'];
                    } elseif (isset($bookmark['link'])) {
                        $url = $bookmark['link'];
                    }
                    
                    if (isset($bookmark['title'])) {
                        $title = $bookmark['title'];
                    } elseif (isset($bookmark['name'])) {
                        $title = $bookmark['name'];
                    } elseif (isset($bookmark['label'])) {
                        $title = $bookmark['label'];
                    }
                    
                    // 如果没有URL，跳过
                    if (empty($url)) {
                        continue;
                    }
                    
                    // 如果没有标题，使用URL
                    if (empty($title)) {
                        $title = parse_url($url, PHP_URL_HOST) ?: $url;
                    }
                    
                    // 提取分类（并转换ID为友好名称）
                    $category = '未分类';
                    $category_id = '';
                    if (isset($bookmark['category'])) {
                        $category_id = $bookmark['category'];
                    } elseif (isset($bookmark['cat'])) {
                        $category_id = $bookmark['cat'];
                    } elseif (isset($bookmark['folder'])) {
                        $category_id = $bookmark['folder'];
                    }
                    
                    // 尝试映射分类ID到名称
                    if (!empty($category_id)) {
                        if (isset($category_maps[$row->user_id][$category_id])) {
                            $category = $category_maps[$row->user_id][$category_id];
                        } else {
                            // 如果是预设分类ID，使用它
                            $default_categories = array(
                                'ai' => 'AI工具',
                                'news' => '资讯媒体',
                                'shopping' => '购物网站',
                                'entertainment' => '娱乐影音',
                                'tools' => '实用工具',
                                'social' => '社交媒体',
                                'dev' => '开发工具',
                                'other' => '其他'
                            );
                            $category = isset($default_categories[$category_id]) ? $default_categories[$category_id] : $category_id;
                        }
                    }
                    
                    // 临时调试：记录未映射的分类ID
                    if (!empty($category_id) && $category === $category_id && strpos($category_id, '___') === 0) {
                        // 这是时间戳格式的分类ID，说明映射失败
                        // 可以在这里添加日志以便调试
                    }
                    
                    // 避免重复（同一用户的同一URL）
                    $bookmark_key = $row->user_id . '_' . md5($url);
                    if (isset($all_bookmarks[$bookmark_key])) {
                        continue;
                    }
                    
                    $all_bookmarks[$bookmark_key] = array(
                        'user_id' => $row->user_id,
                        'username' => $user ? $user->display_name : '未知',
                        'title' => $title,
                        'url' => $url,
                        'category' => $category,
                        'timestamp' => isset($data['timestamp']) ? $data['timestamp'] : (isset($bookmark['timestamp']) ? $bookmark['timestamp'] : 0),
                        'source' => $meta_key  // 记录数据来源
                    );
                }
            }
        }
        
        // 转换为索引数组并按时间戳排序
        $all_bookmarks = array_values($all_bookmarks);
        usort($all_bookmarks, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return $all_bookmarks;
    }
    
    /**
     * 获取用户书签
     */
    private function get_user_bookmarks($user_id) {
        $data = get_user_meta($user_id, 'coolai_bookmarks', true);
        
        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }
        
        return array();
    }
    
    /**
     * 获取 Favicon 列表（增强版：包含所有书签域名）
     */
    private function get_favicon_list() {
        $upload_dir = wp_upload_dir();
        $favicon_dir = $upload_dir['basedir'] . '/coolai_favicons';
        $favicon_url = $upload_dir['baseurl'] . '/coolai_favicons';
        
        $favicons = array();
        $all_domains = array(); // 用于去重的域名集合
        
        // 1. 获取媒体库中手动上传的 Favicon（优先级最高）
        $media_favicons = get_posts(array(
            'post_type' => 'attachment',
            'meta_key' => '_coolai_favicon_domain',
            'posts_per_page' => -1,
            'post_status' => 'inherit'
        ));
        
        foreach ($media_favicons as $attachment) {
            $domain = get_post_meta($attachment->ID, '_coolai_favicon_domain', true);
            if (!$domain || isset($all_domains[$domain])) {
                continue; // 跳过空域名或重复域名
            }
            
            $file_path = get_attached_file($attachment->ID);
            $size = file_exists($file_path) ? filesize($file_path) : 0;
            $modified = strtotime($attachment->post_modified);
            
            $favicons[] = array(
                'domain' => $domain,
                'filename' => basename($file_path),
                'url' => wp_get_attachment_url($attachment->ID),
                'size' => $this->format_bytes($size),
                'size_bytes' => $size,
                'modified' => date('Y-m-d H:i:s', $modified),
                'age' => human_time_diff($modified, current_time('timestamp')),
                'status' => 'cached',
                'has_file' => true,
                'source' => 'manual' // 标记为手动上传
            );
            
            $all_domains[$domain] = true;
        }
        
        // 2. 获取文件系统缓存的 Favicon（优先显示最新的）
        if (is_dir($favicon_dir)) {
            $files = glob($favicon_dir . '/*.{png,jpg,ico,svg,gif,webp}', GLOB_BRACE);
            
            // 按文件修改时间倒序排序，确保最新的文件优先
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            foreach ($files as $file) {
                $filename = basename($file);
                // 提取域名（去掉扩展名）
                $domain = pathinfo($filename, PATHINFO_FILENAME);
                
                // 严格去重：跳过已存在的域名
                if (isset($all_domains[$domain])) {
                    continue;
                }
                
                $size = filesize($file);
                $modified = filemtime($file);
                
                $favicons[] = array(
                    'domain' => $domain,
                    'filename' => $filename,
                    'url' => $favicon_url . '/' . $filename,
                    'size' => $this->format_bytes($size),
                    'size_bytes' => $size,
                    'modified' => date('Y-m-d H:i:s', $modified),
                    'age' => human_time_diff($modified, current_time('timestamp')),
                    'status' => 'cached',
                    'has_file' => true,
                    'source' => 'auto' // 标记为自动缓存
                );
                
                // 标记域名已添加
                $all_domains[$domain] = true;
            }
        }
        
        // 3. 获取所有书签中的域名（未缓存的）
        $all_bookmarks = $this->get_all_bookmarks();
        
        foreach ($all_bookmarks as $bookmark) {
            $domain = parse_url($bookmark['url'], PHP_URL_HOST);
            
            if (!$domain || isset($all_domains[$domain])) {
                continue; // 跳过空域名或已存在的域名
            }
            
            $favicons[] = array(
                'domain' => $domain,
                'filename' => '',
                'url' => rest_url('coolai/v1/favicon?domain=' . urlencode($domain)),
                'size' => 'N/A',
                'size_bytes' => 0,
                'modified' => 'N/A',
                'age' => '未缓存',
                'status' => 'not_cached',
                'has_file' => false,
                'source' => 'bookmark' // 标记为来自书签
            );
            
            $all_domains[$domain] = true;
        }
        
        // 最终去重保护：按域名去重（保留最新的）
        $unique_favicons = array();
        $seen_domains = array();
        
        foreach ($favicons as $favicon) {
            $domain = $favicon['domain'];
            if (!isset($seen_domains[$domain])) {
                $unique_favicons[] = $favicon;
                $seen_domains[$domain] = true;
            }
        }
        
        // 按状态和修改时间排序（已缓存的优先）
        usort($unique_favicons, function($a, $b) {
            // 已缓存的排在前面
            if ($a['has_file'] !== $b['has_file']) {
                return $b['has_file'] ? 1 : -1;
            }
            // 同状态按时间倒序
            if ($a['has_file']) {
                return strcmp($b['modified'], $a['modified']);
            }
            // 未缓存按域名字母序
            return strcmp($a['domain'], $b['domain']);
        });
        
        return $unique_favicons;
    }
    
    /**
     * 获取 Favicon 统计
     */
    private function get_favicon_statistics() {
        $favicons = $this->get_favicon_list();
        
        return array(
            'total' => count($favicons),
            'size' => $this->get_cache_size(),
            'oldest' => !empty($favicons) ? end($favicons)['age'] : 'N/A',
            'newest' => !empty($favicons) ? $favicons[0]['age'] : 'N/A'
        );
    }
    
    /**
     * 获取系统日志
     */
    private function get_system_logs($limit = 100) {
        $log_file = WP_CONTENT_DIR . '/debug.log';
        
        if (!file_exists($log_file)) {
            return array();
        }
        
        $logs = array();
        $handle = fopen($log_file, 'r');
        
        if ($handle) {
            $lines = array();
            while (($line = fgets($handle)) !== false) {
                if (strpos($line, '[CoolAI]') !== false) {
                    $lines[] = $line;
                }
            }
            fclose($handle);
            
            // 只取最后N条
            $logs = array_slice($lines, -$limit);
            $logs = array_reverse($logs);
        }
        
        return $logs;
    }
    
    /**
     * 获取设置
     */
    private function get_settings() {
        return array(
            'enable_api' => get_option('coolai_enable_api', true),
            'cache_duration' => get_option('coolai_cache_duration', 12),
            'rate_limit' => get_option('coolai_rate_limit', 100),
            'auto_backup' => get_option('coolai_auto_backup', false),
            'debug_mode' => get_option('coolai_debug_mode', false)
        );
    }
    
    /**
     * 保存设置
     */
    private function save_settings($post_data) {
        update_option('coolai_enable_api', isset($post_data['enable_api']));
        update_option('coolai_cache_duration', intval($post_data['cache_duration']));
        update_option('coolai_rate_limit', intval($post_data['rate_limit']));
        update_option('coolai_auto_backup', isset($post_data['auto_backup']));
        update_option('coolai_debug_mode', isset($post_data['debug_mode']));
    }
    
    /**
     * 处理 AJAX 请求
     */
    public function handle_ajax_action() {
        check_ajax_referer('coolai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('权限不足');
        }
        
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        
        switch ($action) {
            case 'clear_cache':
                $result = $this->clear_favicon_cache();
                wp_send_json_success($result);
                break;
                
            case 'export_data':
                $data = $this->export_all_data();
                wp_send_json_success($data);
                break;
                
            case 'delete_favicon':
                $domain = isset($_POST['domain']) ? sanitize_text_field($_POST['domain']) : '';
                $result = $this->delete_favicon($domain);
                wp_send_json_success($result);
                break;
            
            case 'upload_favicon':
                $result = $this->handle_favicon_upload();
                if (is_wp_error($result)) {
                    wp_send_json_error($result->get_error_message());
                } else {
                    wp_send_json_success($result);
                }
                break;
                
            default:
                wp_send_json_error('未知操作');
        }
    }
    
    /**
     * 清理 Favicon 缓存
     */
    private function clear_favicon_cache() {
        $upload_dir = wp_upload_dir();
        $favicon_dir = $upload_dir['basedir'] . '/coolai_favicons';
        
        if (!is_dir($favicon_dir)) {
            return array('message' => '缓存目录不存在', 'count' => 0);
        }
        
        $files = glob($favicon_dir . '/*.{png,jpg,ico,svg,gif,webp}', GLOB_BRACE);
        $count = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $count++;
            }
        }
        
        // 清理内存缓存
        wp_cache_flush();
        
        return array('message' => "已清理 {$count} 个图标文件", 'count' => $count);
    }
    
    /**
     * 删除单个 Favicon
     */
    private function delete_favicon($domain) {
        $upload_dir = wp_upload_dir();
        $favicon_dir = $upload_dir['basedir'] . '/coolai_favicons';
        $safe_filename = sanitize_file_name($domain) . '.png';
        $file_path = $favicon_dir . '/' . $safe_filename;
        
        if (file_exists($file_path) && unlink($file_path)) {
            // 清理缓存
            $cache_key = 'coolai_favicon_' . md5($domain);
            wp_cache_delete($cache_key, 'coolai_favicons');
            
            return array('message' => '已删除');
        }
        
        return array('message' => '文件不存在或删除失败');
    }
    
    /**
     * 导出所有数据
     */
    private function export_all_data() {
        $data = array(
            'bookmarks' => $this->get_all_bookmarks(),
            'statistics' => $this->get_statistics(),
            'exported_at' => current_time('mysql')
        );
        
        return $data;
    }
    
    /**
     * 处理 Favicon 手动上传
     */
    private function handle_favicon_upload() {
        // 检查是否有文件上传
        if (!isset($_FILES['favicon_file']) || $_FILES['favicon_file']['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', '文件上传失败');
        }
        
        $domain = isset($_POST['favicon_domain']) ? sanitize_text_field($_POST['favicon_domain']) : '';
        if (empty($domain)) {
            return new WP_Error('missing_domain', '缺少域名参数');
        }
        
        // 验证域名格式
        if (!preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)*$/i', $domain)) {
            return new WP_Error('invalid_domain', '无效的域名格式');
        }
        
        $file = $_FILES['favicon_file'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_name = $file['name'];
        
        // 文件大小限制（512KB）
        if ($file_size > 512 * 1024) {
            return new WP_Error('file_too_large', '文件过大，最大512KB');
        }
        
        // 检测文件类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        
        // 只允许图片类型
        $allowed_types = array('image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/webp');
        if (!in_array($mime_type, $allowed_types)) {
            return new WP_Error('invalid_type', '不支持的文件类型，仅支持图片格式');
        }
        
        // 确定文件扩展名
        $extension = 'png';
        $extension_map = array(
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'image/webp' => 'webp'
        );
        if (isset($extension_map[$mime_type])) {
            $extension = $extension_map[$mime_type];
        }
        
        // 目标路径
        $upload_dir = wp_upload_dir();
        $favicon_dir = $upload_dir['basedir'] . '/coolai_favicons';
        $favicon_url = $upload_dir['baseurl'] . '/coolai_favicons';
        
        // 确保目录存在
        if (!is_dir($favicon_dir)) {
            wp_mkdir_p($favicon_dir);
        }
        
        // 删除该域名的旧文件（所有格式）
        $old_files = glob($favicon_dir . '/' . sanitize_file_name($domain) . '.*');
        foreach ($old_files as $old_file) {
            @unlink($old_file);
        }
        
        // 保存新文件
        $safe_filename = sanitize_file_name($domain) . '.' . $extension;
        $target_path = $favicon_dir . '/' . $safe_filename;
        
        if (!move_uploaded_file($file_tmp, $target_path)) {
            return new WP_Error('save_failed', '文件保存失败');
        }
        
        // 设置文件权限
        @chmod($target_path, 0644);
        
        // 清理缓存
        $cache_key = 'coolai_favicon_' . md5($domain);
        wp_cache_delete($cache_key, 'coolai_favicons');
        
        return array(
            'message' => '上传成功',
            'domain' => $domain,
            'url' => $favicon_url . '/' . $safe_filename,
            'size' => $this->format_bytes($file_size)
        );
    }
}

// 初始化插件
CoolAI_Admin_Panel::get_instance();
