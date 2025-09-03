<?php
/**
 * AJAX handler for DLE Filter (Admin side)
 *
 * @link https://lazydev.pro/
 * @author LazyDev
 */

@error_reporting(E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE);
@ini_set('display_errors', true);
@ini_set('html_errors', false);

define('DATALIFEENGINE', true);
define('ROOT_DIR', substr(dirname(__FILE__), 0, -26));
define('ENGINE_DIR', ROOT_DIR . '/engine');

use LazyDev\Filter\Ajax;
use LazyDev\Filter\Data;
use LazyDev\Filter\Cache;
use LazyDev\Filter\Helper;

include_once ENGINE_DIR . '/lazydev/dle_filter/loader.php';

header('Content-type: text/html; charset=' . $config['charset']);
date_default_timezone_set($config['date_adjust']);
setlocale(LC_NUMERIC, 'C');

// Core includes
require_once DLEPlugins::Check(ENGINE_DIR . '/modules/functions.php');
require_once DLEPlugins::Check(ROOT_DIR . '/language/' . $config['langs'] . '/website.lng');

dle_session();

// Load user groups
$user_group = get_vars('usergroup');
if (!$user_group) {
    $user_group = [];
    $db->query('SELECT * FROM ' . USERPREFIX . '_usergroups ORDER BY id ASC');
    while ($row = $db->get_row()) {
        foreach ($row as $key => $value) {
            $user_group[$row['id']][$key] = stripslashes($value);
        }
    }
    set_vars('usergroup', $user_group);
    $db->free();
}

// Load categories
$cat_info = get_vars('category');
if (!$cat_info) {
    $cat_info = [];
    $db->query('SELECT * FROM ' . PREFIX . '_category ORDER BY posi ASC');
    while ($row = $db->get_row()) {
        foreach ($row as $key => $value) {
            $cat_info[$row['id']][$key] = stripslashes($value);
        }
    }
    set_vars('category', $cat_info);
    $db->free();
}

// Language for current skin
if (!empty($config['lang_' . $config['skin']]) &&
    file_exists(DLEPlugins::Check(ROOT_DIR . '/language/' . $config['lang_' . $config['skin']] . '/website.lng'))) {
    include_once DLEPlugins::Check(ROOT_DIR . '/language/' . $config['lang_' . $config['skin']] . '/website.lng');
}

// Login
$is_logged = false;
require_once DLEPlugins::Check(ENGINE_DIR . '/modules/sitelogin.php');
if (!$is_logged) {
    $member_id['user_group'] = 5; // guest
}

// 🔒 Security
$action   = isset($_POST['action']) ? trim(strip_tags($_POST['action'])) : false;
$dle_hash = isset($_POST['dle_hash']) ? trim(strip_tags($_POST['dle_hash'])) : false;

// CSRF protection
if (!$dle_hash || $dle_hash !== $dle_login_hash) {
    echo Helper::json(['text' => $langVar['admin']['ajax']['error'], 'error' => true]);
    exit;
}

// Permission check: only admins (group 1) allowed
if ($member_id['user_group'] != 1) {
    echo Helper::json(['text' => $langVar['admin']['ajax']['no_access'], 'error' => true]);
    exit;
}

// Whitelist of allowed admin AJAX actions
$allowedActions = ['saveOptions', 'clearStatistics', 'clearCache', 'findNews'];

if ($action && in_array($action, $allowedActions, true)) {
    Ajax::ajaxAction($action);
} else {
    echo Helper::json(['text' => $langVar['admin']['ajax']['error'], 'error' => true]);
}
