<?php
/*
=====================================================
 DLE Filter - Admin Entry Point for DLE 18.1
=====================================================
*/

if (!defined('DATALIFEENGINE')) {
    header('HTTP/1.1 403 Forbidden');
    die('Hacking attempt!');
}

// Permission check (admin only)
if (!$member_id['user_group'] || $member_id['user_group'] != 1) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied');
}

// Define module base path
define('DLE_FILTER_DIR', ENGINE_DIR . '/lazydev/dle_filter/');

// Load admin panel file
$admin_file = DLE_FILTER_DIR . 'admin/admin.php';

if (file_exists($admin_file)) {
    include $admin_file;
} else {
    die('DLE Filter admin panel not found!');
}
