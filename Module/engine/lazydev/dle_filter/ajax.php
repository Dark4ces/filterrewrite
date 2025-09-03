<?php
/**
* AJAX обработчик
*
* @link https://lazydev.pro/
* @author LazyDev
**/

// Error handling – keep notices quiet, but log real errors
@error_reporting(E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE);
@ini_set('display_errors', true);
@ini_set('html_errors', false);

define('DATALIFEENGINE', true);
define('ROOT_DIR', substr(dirname(__FILE__), 0, -26));
define('ENGINE_DIR', ROOT_DIR . '/engine');

use LazyDev\Filter\Helper;
use LazyDev\Filter\Data;

// Load module bootstrap
include_once ENGINE_DIR . '/lazydev/dle_filter/loader.php';

// Always set content-type
header('Content-type: text/html; charset=' . $config['charset']);

// Timezone / locale
date_default_timezone_set($config['date_adjust']);
setlocale(LC_NUMERIC, 'C');

// Core DLE functions
require_once DLEPlugins::Check(ENGINE_DIR . '/modules/functions.php');

// Skin detection
if (!empty($_REQUEST['skin'])) {
    $_REQUEST['skin'] = $_REQUEST['dle_skin'] = trim(totranslit($_REQUEST['skin'], false, false));
}

if (!empty($_REQUEST['dle_skin'])) {
    $_REQUEST['dle_skin'] = trim(totranslit($_REQUEST['dle_skin'], false, false));
    if ($_REQUEST['dle_skin'] && @is_dir(ROOT_DIR . '/templates/' . $_REQUEST['dle_skin'])) {
        $config['skin'] = $_REQUEST['dle_skin'];
    } else {
        $_REQUEST['dle_skin'] = $_REQUEST['skin'] = $config['skin'];
    }
} elseif (!empty($_COOKIE['dle_skin'])) {
    $_COOKIE['dle_skin'] = trim(totranslit((string)$_COOKIE['dle_skin'], false, false));
    if ($_COOKIE['dle_skin'] && is_dir(ROOT_DIR . '/templates/' . $_COOKIE['dle_skin'])) {
        $config['skin'] = $_COOKIE['dle_skin'];
    }
}

// Language
if ($config["lang_" . $config['skin']] && file_exists(DLEPlugins::Check(ROOT_DIR . '/language/' . $config["lang_" . $config['skin']] . '/website.lng'))) {
    include_once DLEPlugins::Check(ROOT_DIR . '/language/' . $config["lang_" . $config['skin']] . '/website.lng');
} else {
    include_once DLEPlugins::Check(ROOT_DIR . '/language/' . $config['langs'] . '/website.lng');
}

// http_home_url fallback
if (!$config['http_home_url']) {
    $config['http_home_url'] = explode("engine/lazydev/dle_filter/ajax.php", $_SERVER['PHP_SELF']);
    $config['http_home_url'] = reset($config['http_home_url']);
}

// Force SSL if needed
$isSSL = Helper::ssl();

if (strpos($config['http_home_url'], '//') === 0) {
    $config['http_home_url'] = $isSSL ? 'https:' . $config['http_home_url'] : 'http:' . $config['http_home_url'];
} elseif (strpos($config['http_home_url'], '/') === 0) {
    $config['http_home_url'] = ($isSSL ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $config['http_home_url'];
} elseif ($isSSL && stripos($config['http_home_url'], 'http://') !== false) {
    $config['http_home_url'] = str_replace('http://', 'https://', $config['http_home_url']);
}

if (substr($config['http_home_url'], -1) != '/') {
    $config['http_home_url'] .= '/';
}

// Template system
require_once DLEPlugins::Check(ENGINE_DIR . '/classes/templates.class.php');
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

define('TEMPLATE_DIR', ROOT_DIR . '/templates/' . $config['skin']);

// Login check
$is_logged = false;
require_once DLEPlugins::Check(ENGINE_DIR . '/modules/sitelogin.php');
if (!$is_logged) {
    $member_id['user_group'] = 5;
}

// AJAX vars
$vars = [
    'data'     => $_POST['data'] ?? null,
    'url'      => $_POST['url'] ?? null,
    'dle_hash' => trim(strip_tags($_POST['dle_hash'] ?? '')),
    'ajax'     => true,
];

// Security hash
if ($config['allow_registration'] && $vars['dle_hash'] != $dle_login_hash) {
    echo Helper::json(['text' => $langVar['admin']['ajax']['error'], 'error' => 'true']);
    exit;
}

// Prepare template
$tpl = new dle_template();
$tpl->dir = TEMPLATE_DIR;

// Load filter logic
include ENGINE_DIR . '/lazydev/dle_filter/index.php';
$url_page = Helper::cleanSlash($url_page) . '/';

// Miniposter integration
if (Data::get('miniposter2', 'config') && file_exists(ENGINE_DIR . '/mods/miniposter/index.php')) {
    require_once ENGINE_DIR . '/mods/miniposter/index.php';
    $tpl->result['content'] = preg_replace_callback("#\\{poster(.+?)\\}#i", 'mpic', $tpl->result['content']);
}
if (Data::get('miniposter3', 'config') && file_exists(ENGINE_DIR . '/mods/miniposter/loader.php')) {
    require_once ENGINE_DIR . '/mods/miniposter/loader.php';
    (new Miniposter())->build($tpl->result['content']);
}

// Replace theme path
$tpl->result['content'] = str_ireplace(
    '{THEME}',
    $config['http_home_url'] . 'templates/' . $config['skin'],
    $tpl->result['content']
);

// Response
$response = [
    'content'   => $tpl->result['content'],
    'url'       => $url_page,
    'title'     => $metatags['title'],
    'speedbar'  => $tpl->result['speedbar']
];

echo Helper::json($response);
