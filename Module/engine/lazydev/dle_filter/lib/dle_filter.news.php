<?php
/**
 * Запись данных новостей
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/
 
if (!defined('DATALIFEENGINE')) {
	header('HTTP/1.1 403 Forbidden');
	die('Hacking attempt!');
}

use LazyDev\Filter\Data;
use LazyDev\Filter\Helper;
use LazyDev\Filter\Field;

include_once ENGINE_DIR . '/lazydev/dle_filter/loader.php';
include_once (DLEPlugins::Check(ENGINE_DIR . '/classes/parse.class.php'));

if (Data::get('new_search', 'config') == 1) {
	Field::updateNews();
}