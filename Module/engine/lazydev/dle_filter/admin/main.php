<?php
/**
* Главная страница админ панель
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

use LazyDev\Filter\Admin;

echo <<<HTML
<div class="panel panel-default">
    <div class="panel-heading">{$langVar['admin']['other']['block_menu']}</div>
    <div class="list-bordered">
HTML;
echo Admin::menu([
    [
        'link' => '?mod=' . $modLName . '&action=settings',
        'icon' => $config['http_home_url'] . 'engine/lazydev/' . $modLName . '/admin/template/assets/icons/settings.png',
        'title' => $langVar['admin']['settings_title'],
        'descr' => $langVar['admin']['settings_descr'],
    ],
	[
        'link' => '?mod=' . $modLName . '&action=statistics',
        'icon' => $config['http_home_url'] . 'engine/lazydev/' . $modLName . '/admin/template/assets/icons/statistics.png',
        'title' => $langVar['admin']['statistics_title'],
        'descr' => $langVar['admin']['statistics_descr'],
    ],
	[
        'link' => '?mod=' . $modLName . '&action=fields',
        'icon' => $config['http_home_url'] . 'engine/lazydev/' . $modLName . '/admin/template/assets/icons/fields.png',
        'title' => $langVar['admin']['fields_title'],
        'descr' => $langVar['admin']['fields_descr'],
    ]
]);
echo <<<HTML
    </div>
</div>
HTML;

?>