<?php
/**
 * auth_guard.php — 安全鉴权守卫（兼容 PHP 5.3+）
 * 用法：在受保护文件的 <?php 之后加一行：
 *   require_once __DIR__.'/auth_guard.php';
 * 不改变任何现有业务逻辑。
 */

// 防止重复加载
if (defined('AUTH_GUARD_LOADED')) return;
define('AUTH_GUARD_LOADED', true);

// ========== 1. 阻止空 UA 和常见爬虫/脚本 UA ==========
$__ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';
if ($__ua === '') {
    if (function_exists('http_response_code')) {
        http_response_code(403);
    } else {
        header('HTTP/1.1 403 Forbidden');
    }
    exit('Forbidden');
}
$__ual = strtolower($__ua);
$__badUAs = array(
    // 命令行 / 脚本库
    'curl/', 'wget', 'python-requests', 'python-urllib', 'python/',
    'scrapy', 'java/', 'okhttp', 'go-http-client', 'libwww-perl',
    'httpclient', 'php/', 'axios/', 'node-fetch', 'postmanruntime',
    'insomnia', 'httpie', 'apachebench', 'guzzlehttp', 'httpx/',
    'aiohttp', 'requests/', 'urllib', 'mechanize', 'htmlunit',
    // 无头浏览器
    'headlesschrome', 'phantomjs', 'puppeteer', 'playwright',
    // 常见爬虫
    'semrush', 'ahrefsbot', 'mj12bot', 'dotbot', 'petalbot',
    'bytespider', 'zoominfobot', 'yandexbot', 'baiduspider',
    'sogou', '360spider', 'bingbot', 'googlebot', 'slurp',
    'spider', 'crawler', 'bot/', 'bot ', '_bot', '-bot',
);
foreach ($__badUAs as $__bad) {
    if (strpos($__ual, $__bad) !== false) {
        if (function_exists('http_response_code')) {
            http_response_code(403);
        } else {
            header('HTTP/1.1 403 Forbidden');
        }
        exit('Forbidden');
    }
}

// ========== 2. 对 POST 请求校验来源（防 CSRF）==========
$__method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
if ($__method === 'POST') {
    $__host       = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $__hostNoPort = preg_replace('/:\d+$/', '', $__host);
    $__ref        = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $__origin     = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

    $__refOk = false;
    if ($__ref !== '') {
        $__refHost = parse_url($__ref, PHP_URL_HOST);
        if ($__refHost && ($__refHost === $__host || $__refHost === $__hostNoPort)) {
            $__refOk = true;
        }
    }
    $__oriOk = false;
    if ($__origin !== '') {
        $__oriHost = parse_url($__origin, PHP_URL_HOST);
        if ($__oriHost && ($__oriHost === $__host || $__oriHost === $__hostNoPort)) {
            $__oriOk = true;
        }
    }

    // 只要提供了 referer 或 origin，就必须匹配；两者都为空则放行
    $__provided = ($__ref !== '' || $__origin !== '');
    $__passed   = ($__refOk || $__oriOk);
    if ($__provided && !$__passed) {
        if (function_exists('http_response_code')) {
            http_response_code(403);
        } else {
            header('HTTP/1.1 403 Forbidden');
        }
        exit('Forbidden');
    }
}

// ========== 3. 速率限制（防高频爬取）==========
$__rateDir = rtrim(sys_get_temp_dir(), '/\\') . '/__rl_auth';
if (!is_dir($__rateDir)) {
    @mkdir($__rateDir, 0700, true);
}
$__remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
$__scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$__rateKey    = md5($__remoteAddr . '|' . $__scriptName);
$__rateFile   = $__rateDir . '/' . $__rateKey . '.json';
$__now        = time();
$__window     = 60;     // 60 秒窗口
$__maxReq     = 600;    // 每 60 秒每 IP 每脚本最多 600 次

$__data = null;
if (is_readable($__rateFile)) {
    $__raw = @file_get_contents($__rateFile);
    if ($__raw !== false) $__data = @json_decode($__raw, true);
}
if (!is_array($__data) || !isset($__data['t']) || !isset($__data['c'])) {
    $__data = array('t' => $__now, 'c' => 0);
}
if ($__now - (int)$__data['t'] > $__window) {
    $__data = array('t' => $__now, 'c' => 1);
} else {
    $__data['c'] = (int)$__data['c'] + 1;
    if ($__data['c'] > $__maxReq) {
        if (function_exists('http_response_code')) {
            http_response_code(429);
        } else {
            header('HTTP/1.1 429 Too Many Requests');
        }
        header('Retry-After: ' . $__window);
        exit('Too Many Requests');
    }
}
@file_put_contents($__rateFile, json_encode($__data), LOCK_EX);

// ========== 4. 安全响应头 ==========
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('X-XSS-Protection: 1; mode=block');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// ========== 5. 辅助函数 ==========
// 判断是否管理员已登录（需调用方已 session_start()）
function auth_is_admin() {
    return isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === 1;
}
// 强制要求管理员登录，否则跳回 admin.php
function auth_require_admin() {
    if (!auth_is_admin()) {
        header('Location: admin.php');
        exit;
    }
}
// CSRF Token（备用）
function auth_csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) return '';
    if (empty($_SESSION['__csrf'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['__csrf'] = bin2hex(random_bytes(16));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $_SESSION['__csrf'] = bin2hex(openssl_random_pseudo_bytes(16));
        } else {
            $_SESSION['__csrf'] = md5(uniqid(mt_rand(), true));
        }
    }
    return $_SESSION['__csrf'];
}
function auth_csrf_check($token) {
    if (session_status() !== PHP_SESSION_ACTIVE) return false;
    return !empty($_SESSION['__csrf'])
        && is_string($token)
        && hash_equals($_SESSION['__csrf'], $token);
}