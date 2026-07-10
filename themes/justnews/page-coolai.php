<?php
/**
 * Template Name: CoolAI 导航 v2.4.5
 * Description: CoolAI智能导航系统的WordPress页面模板（安全加固版）
 * Version: 2.4.5
 * 
 * 此模板用于加载CoolAI导航页面，并注入WordPress配置
 * 注意：需要配合 mu-plugins/coolai-integration.php 使用
 * 
 * 安全改进：
 * - 添加 nonce 验证
 * - 输出转义增强
 * - CSP 内容安全策略
 * - 错误处理优化
 */

// 安全检查：防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 获取当前用户信息
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();

// 生成安全的 nonce（用于 AJAX 请求验证）
$nonce = wp_create_nonce('wp_rest');

// 构造WordPress配置对象（所有值都经过安全处理）
$wp_config = array(
    'isLoggedIn' => $is_logged_in,
    'userId' => $is_logged_in ? intval($current_user->ID) : 0,
    'userDisplayName' => $is_logged_in ? sanitize_text_field($current_user->display_name) : '游客',
    'userEmail' => $is_logged_in ? sanitize_email($current_user->user_email) : '',
    'userAvatar' => $is_logged_in ? esc_url(get_avatar_url($current_user->ID, array('size' => 96))) : '',
    'restUrl' => esc_url_raw(rest_url()),
    'nonce' => $nonce,
    'loginUrl' => esc_url(wp_login_url(get_permalink())),
    'logoutUrl' => esc_url(wp_logout_url(get_permalink())),
    'adminUrl' => $is_logged_in ? esc_url(admin_url()) : '',
    'profileUrl' => $is_logged_in ? esc_url(admin_url('profile.php')) : esc_url(wp_login_url()),
    'accountUrl' => $is_logged_in ? esc_url(admin_url('profile.php')) : esc_url(wp_login_url()),
    'siteUrl' => esc_url(get_site_url()),
    'siteName' => sanitize_text_field(get_bloginfo('name')),
    'version' => '2.4.5',
    'apiNamespace' => 'coolai/v1'
);

// 直接输出完整的CoolAI HTML文件（不使用WordPress模板结构）
$coolai_file = locate_template('coolai-nav.html');

if ($coolai_file && file_exists($coolai_file)) {
    // 读取完整HTML内容
    $content = file_get_contents($coolai_file);
    
    if ($content === false) {
        wp_die(
            __('无法读取导航文件', 'coolai'),
            __('文件读取错误', 'coolai'),
            array('response' => 500)
        );
    }
    
    // 注入WordPress配置到HTML中（在</head>之前插入）
    $wp_config_json = wp_json_encode($wp_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    
    $wp_config_script = sprintf(
        '<script id="coolai-wp-config">window.wpConfig = %s;</script>',
        $wp_config_json
    );
    
    // 添加 CSP (Content Security Policy) 头部 - v2.4.5 已启用
    header("Content-Security-Policy: default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https: fonts.googleapis.com; img-src 'self' data: https: blob:; font-src 'self' https: data: fonts.gstatic.com; connect-src 'self' https:;");
    
    // 添加其他安全头部
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    
    $content = str_replace('</head>', $wp_config_script . '</head>', $content);
    
    // 输出完整HTML并终止WordPress模板系统
    echo $content;
    exit; // 重要：阻止WordPress继续输出其他内容
} else {
    // 错误提示（使用 wp_die 更安全）
    wp_die(
        sprintf(
            '<div style="display:flex;align-items:center;justify-content:center;height:100vh;font-family:Arial,sans-serif;">
                <div style="text-align:center;">
                    <h1 style="color:#e74c3c;font-size:48px;margin-bottom:20px;">❌</h1>
                    <h2 style="color:#333;margin-bottom:10px;">CoolAI导航文件未找到</h2>
                    <p style="color:#666;margin-bottom:20px;">请确保 <code>coolai-nav.html</code> 文件在主题目录中</p>
                    <p style="color:#999;font-size:14px;">主题目录: %s</p>
                    <p style="color:#999;font-size:12px;margin-top:20px;">如需帮助，请联系管理员</p>
                </div>
            </div>',
            esc_html(get_template_directory())
        ),
        __('CoolAI导航 - 文件未找到', 'coolai'),
        array('response' => 404)
    );
}
