<?php
/**
* Дизайн админ панель
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

if (!defined('DATALIFEENGINE') || !defined('LOGGED_IN')) {
	header('HTTP/1.1 403 Forbidden');
	header('Location: ../../');
	die('Hacking attempt!');
}

use LazyDev\Filter\Data;

$night = '';
if (date('G') > 7 && date('G') < 20) {
} else {
    $night = 'dle_theme_dark';
    $styleNight = <<<HTML
<style>
.navbar-inverse {
    background: #2e3131!important;
}
.chosen-container-multi .chosen-choices li.search-field input[type="text"] {
    color: #fff;
}
.panel-body + .panel-body, .panel-body + .table, .panel-body + .table-responsive, .panel-body.has-top-border {
    border-top: 1px solid rgba(255,255,255,0.2)!important;;
}
.chosen-container-single .chosen-single span {
    color: #f2f1ef!important;
}
.dle_theme_dark .panel, .dle_theme_dark .modal-content {
    color: #ffffff!important;
    background-color: #2e3131!important;
}
.chosen-container-single .chosen-search {
    background: #403c3c!important;
}
.chosen-container-single .chosen-search input[type=text] {
    color: #000!important;
}
body.dle_theme_dark {
    background-color: #545454!important;
}
.section_icon {
    background: transparent!important;
    box-shadow: none!important;
    -webkit-box-shadow: none!important;
}
.gray-theme.fr-box.fr-basic .fr-wrapper {
    background: #2e3131!important;
}
label.status {
    background: #2e3131;
}
</style>
HTML;

}

echo <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{$langVar['admin']['title']}</title>
        <link href="engine/skins/fonts/fontawesome/styles.min.css" rel="stylesheet" type="text/css">
        <link href="engine/skins/stylesheets/application.css" rel="stylesheet" type="text/css">
        <link href="engine/lazydev/{$modLName}/admin/template/assets/style.css" rel="stylesheet" type="text/css">
        <script src="engine/skins/javascripts/application.js"></script>
        <script>
            let dle_act_lang = [{$langVar['admin']['other']['jslang']}];
            let cal_language = {
                en: {
                    months: [{$langVar['admin']['other']['jsmonth']}],
                    dayOfWeekShort: [{$langVar['admin']['other']['jsday']}]
                }
            };
            let filedefaulttext = '{$langVar['admin']['other']['jsnotgot']}';
            let filebtntext = '{$langVar['admin']['other']['jschoose']}';
            let dle_login_hash = '{$dle_login_hash}';
        </script>
        {$styleNight}
    </head>
    <body class="{$night}">
        <div id="loading-layer" class="shadow-depth3"><i class="fa fa-spinner fa-spin"></i></div>
        <div class="navbar navbar-inverse">
            <div class="navbar-header">
                <a class="navbar-brand" href="?mod={$modLName}">{$langVar['name']} v1.2.7</a>
                <ul class="nav navbar-nav visible-xs-block">
                    <li><a data-toggle="collapse" data-target="#navbar-mobile"><i class="fa fa-angle-double-down"></i></a></li>
                    <li><a class="sidebar-mobile-main-toggle"><i class="fa fa-bars"></i></a></li>
                </ul>
            </div>
            <div class="navbar-collapse collapse" id="navbar-mobile">
                <div class="navbar-right">	
                    <ul class="nav navbar-nav">
                        <li class="dropdown dropdown-language nav-item" style="display:none!important;">
                            <a class="dropdown-toggle nav-link" id="dropdown-flag" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="flag-icon mr-50 flag-icon-us"></i><span class="selected-language">English</span></a>
                            <div class="dropdown-menu" aria-labelledby="dropdown-flag">
                                <a class="dropdown-item" href="#" data-language="ru"><i class="flag-icon flag-icon-ru mr-50"></i> Русский</a>
                                <a class="dropdown-item" href="#" data-language="us"><i class="flag-icon flag-icon-us mr-50"></i> English</a>
                                <a class="dropdown-item" href="#" data-language="ua"><i class="flag-icon flag-icon-ua mr-50"></i> Українська</a>
                            </div>
                        </li>
                        <li><a href="{$PHP_SELF}?mod={$modLName}" title="{$langVar['admin']['other']['main']}">{$langVar['admin']['other']['main']}</a></li>
                        <li><a href="{$PHP_SELF}" title="{$langVar['admin']['other']['all_menu_dle']}">{$langVar['admin']['other']['all_menu_dle']}</a></li>
                        <li><a href="{$config['http_home_url']}" title="{$langVar['admin']['other']['site']}" target="_blank">{$langVar['admin']['other']['site']}</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="page-container">
            <div class="page-content">
                
                <div class="content-wrapper">
                    <div class="page-header page-header-default">
                        <div class="breadcrumb-line">
                            <ul class="breadcrumb">
                                {$speedbar}
                            </ul>
HTML;
if (Data::get('cache_filter', 'config')) {
echo <<<HTML
							<input type="button" onclick="clearCache();" class="btn bg-danger btn-sm" style="float: right;border-radius: unset;font-size: 13px;margin-top: 4px;" value="{$langVar['admin']['clear_cache']}">
HTML;
$jsAdminScript[] = <<<HTML

let clearCache = function() {
	DLEconfirm("{$langVar['admin']['accept_cache']}", "{$langVar['admin']['try']}", function() {
		coreAdmin.ajaxSend(false, 'clearCache', false);
	});
	return false;
}
HTML;
}
echo <<<HTML
                        </div>
                    </div>
                    
                    <div class="content">
HTML;

?>