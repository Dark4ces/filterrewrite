<?php
/**
 * Глобальные условия
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

include_once __DIR__ . '/dle_filter.news.php';

if ($Conditions instanceof LazyDev\Filter\Conditions && $Filter instanceof LazyDev\Filter\Filter) {

	$tpl->result['main'] = $Conditions::realize($tpl->result['main'], $Filter::$filterData);
	$filterTags = array_keys($Filter::$globalTag['tag']);
	
	$tpl->result['main'] = preg_replace($Filter::$globalTag['block'], '\\1', $tpl->result['main']);
	$tpl->result['main'] = preg_replace($Filter::$globalTag['hide'], '', $tpl->result['main']);
	$tpl->result['main'] = preg_replace('#\[not-filter-(.+?)\](.*?)\[\/not-filter-\\1\]#is', '\\2', $tpl->result['main']);
	$tpl->result['main'] = str_replace($filterTags, $Filter::$globalTag['tag'], $tpl->result['main']);
	$tpl->result['main'] = $Conditions::clean($tpl->result['main']);
} else {
	$tpl->result['main'] = preg_replace('#\[not-filter-(.+?)\](.*?)\[\/not-filter-\\1\]#is', '', $tpl->result['main']);
	$tpl->result['main'] = preg_replace('#\[filter-(.+?)\](.*?)\[\/filter-\\1\]#is', '', $tpl->result['main']);
	$tpl->result['main'] = preg_replace('#\[filter(.+?)\](.*?)\[\/filter\]#is', '', $tpl->result['main']);
	$tpl->result['main'] = preg_replace('#\{filter(.+?)\}#is', '', $tpl->result['main']);
}