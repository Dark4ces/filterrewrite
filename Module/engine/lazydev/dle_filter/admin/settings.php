<?php
/**
* Настройки модуля
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

use LazyDev\Filter\Data;
use LazyDev\Filter\Admin;

$allXfield = xfieldsload();
foreach ($allXfield as $value) {
    $xfieldArray[$value[0]] = $value[1];
}

$categories = CategoryNewsSelection((empty($configVar['exclude_categories']) ? 0 : $configVar['exclude_categories']));
$sortField = [
	'date' => $langVar['admin']['settings']['p.date'],
	'editdate' => $langVar['admin']['settings']['e.editdate'],
	'title' => $langVar['admin']['settings']['p.title'],
	'autor' => $langVar['admin']['settings']['p.autor'],
	'rating' => $langVar['admin']['settings']['e.rating'],
	'comm_num' => $langVar['admin']['settings']['p.comm_num'],
	'news_read' => $langVar['admin']['settings']['e.news_read']
];
if ($xfieldArray) {
	$sortField = $sortField + $xfieldArray;
	$xfieldArray = ['-' => '-'] + $xfieldArray;
}
$order = [
	'desc' => $langVar['admin']['settings']['desc'],
	'asc' => $langVar['admin']['settings']['asc']
];
$indexFilter = [
	'noindex' => $langVar['admin']['settings']['noindex'],
	'follow' => $langVar['admin']['settings']['follow'],
	'index' => $langVar['admin']['settings']['index'],
];
$codeFilter = [
    'default' => $langVar['admin']['settings']['default'],
    '404' => $langVar['admin']['settings']['404'],
];

$excludeNews = '';
if ($configVar['excludeNews']) {
    $newsId = implode(',', $configVar['excludeNews']);
    $db->query("SELECT id, title FROM " . PREFIX . "_post WHERE id IN({$newsId})");
    while ($row = $db->get_row()) {
        $row['title'] = str_replace("&quot;", '\"', $row['title']);
        $row['title'] = str_replace("&#039;", "'", $row['title']);
        $row['title'] = htmlspecialchars($row['title']);
        $excludeNews .= "<option value=\"{$row['id']}\" selected>" . $row['title'] . "</option>";
    }

}

echo <<<HTML
<form action="" method="post">
    <div class="panel panel-flat">
		<div class="navbar navbar-default navbar-component navbar-xs" style="margin-bottom: 0px;">
	        <ul class="nav navbar-nav visible-xs-block">
		        <li class="full-width text-center"><a data-toggle="collapse" data-target="#navbar-filter">
		            <i class="fa fa-bars"></i></a>
                </li>
	        </ul>
            <div class="navbar-collapse collapse" id="navbar-filter">
                <ul class="nav navbar-nav">
                    <li class="active">
						<a onclick="ChangeOption(this, 'block_1');" class="tip">
                        <i class="fa fa-cog"></i> {$langVar['admin']['settings']['main_settings']}</a>
                    </li>
					<li>
						<a onclick="ChangeOption(this, 'block_3');" class="tip">
                        <i class="fa fa-jsfiddle"></i> {$langVar['admin']['settings']['js_settings']}</a>
                    </li>
                    <li>
						<a onclick="ChangeOption(this, 'block_4');" class="tip">
                        <i class="fa fa-superpowers"></i> {$langVar['admin']['settings']['seo_settings']}</a>
                    </li>
                    <li>
						<a onclick="ChangeOption(this, 'block_2');" class="tip">
                        <i class="fa fa-ellipsis-h"></i> {$langVar['admin']['settings']['other_settings']}</a>
                    </li>
                </ul>
            </div>
        </div>
		<div id="block_1">
			<div class="panel-body" style="font-size:15px; font-weight:bold;">{$langVar['admin']['settings_descr']}</div>
			<div class="table-responsive">
				<table class="table">
HTML;
Admin::row(
    $langVar['admin']['settings']['use_new_search'],
    $langVar['admin']['settings']['use_new_search_descr'],
    Admin::checkBox('new_search', $configVar['new_search'], 'new_search')
);
Admin::row(
    $langVar['admin']['settings']['cache_filter'],
    $langVar['admin']['settings']['cache_filter_descr'],
    Admin::checkBox('cache_filter', $configVar['cache_filter'], 'cache_filter'),
	$langVar['admin']['settings']['cache_filter_helper']
);
Admin::row(
    $langVar['admin']['settings']['statistics'],
    $langVar['admin']['settings']['statistics_descr'],
    Admin::checkBox('statistics', $configVar['statistics'], 'statistics')
);
Admin::row(
    $langVar['admin']['settings']['clear_statistics'],
    $langVar['admin']['settings']['clear_statistics_descr'],
    Admin::input(['clear_statistics', 'number', $configVar['clear_statistics'] ?: 0, false, false, 0, 30])
);
Admin::row(
    $langVar['admin']['settings']['exclude_categories'],
    $langVar['admin']['settings']['exclude_categories_descr'],
    Admin::selectTag('exclude_categories[]', $categories, $langVar['admin']['settings']['categories'])
);
Admin::row(
    $langVar['admin']['settings']['exclude_news'],
    $langVar['admin']['settings']['exclude_news_descr'],
    "<div id=\"searchVal\">
        <select class=\"excludeNews\" id=\"excludeNews\" name=\"excludeNews[]\" multiple>{$excludeNews}</select>
    </div>"
);
Admin::row(
    $langVar['admin']['settings']['search_cat'],
    $langVar['admin']['settings']['search_cat_descr'],
    Admin::checkBox('search_cat', $configVar['search_cat'], 'search_cat')
);
Admin::row(
    $langVar['admin']['settings']['search_tag'],
    $langVar['admin']['settings']['search_tag_descr'],
    Admin::checkBox('search_tag', $configVar['search_tag'], 'search_tag')
);
Admin::row(
    $langVar['admin']['settings']['search_xfield'],
    $langVar['admin']['settings']['search_xfield_descr'],
    Admin::checkBox('search_xfield', $configVar['search_xfield'], 'search_xfield')
);
Admin::row(
    $langVar['admin']['settings']['news_number'],
    $langVar['admin']['settings']['news_number_descr'],
    Admin::input(['news_number', 'number', $configVar['news_number'] ?: $config['news_number'], false, false, 1, 100]),
	$langVar['admin']['settings']['news_number_helper']
);
Admin::row(
    $langVar['admin']['settings']['allow_main'],
    $langVar['admin']['settings']['allow_main_descr'],
    Admin::checkBox('allow_main', $configVar['allow_main'], 'allow_main')
);
Admin::row(
    $langVar['admin']['settings']['sort_field'],
    $langVar['admin']['settings']['sort_field_descr'],
    Admin::select(['sort_field', $sortField, true, $configVar['sort_field'], false, false]),
	$langVar['admin']['settings']['sort_field_helper_2']
);
Admin::row(
    $langVar['admin']['settings']['order'],
    $langVar['admin']['settings']['order_descr'],
    Admin::select(['order', $order, true, $configVar['order'], false, false])
);
echo <<<HTML
				</table>
			</div>
		</div>
		
	    <div id="block_3" style='display:none'>
			<div class="panel-body" style="font-size:15px; font-weight:bold;">{$langVar['admin']['settings']['js_settings']}</div>
			<div class="table-responsive">
				<table class="table">
HTML;
Admin::row(
    $langVar['admin']['settings']['ion_slider'],
    $langVar['admin']['settings']['ion_slider_descr'],
    Admin::checkBox('ion_slider', $configVar['ion_slider'], 'ion_slider')
);
Admin::row(
    $langVar['admin']['settings']['tail_select'],
    $langVar['admin']['settings']['tail_select_descr'],
    Admin::checkBox('tail_select', $configVar['tail_select'], 'tail_select')
);
Admin::row(
    $langVar['admin']['settings']['chosen_select'],
    $langVar['admin']['settings']['chosen_select_descr'],
    Admin::checkBox('chosen_select', $configVar['chosen_select'], 'chosen_select')
);
Admin::row(
    $langVar['admin']['settings']['nice_select'],
    $langVar['admin']['settings']['nice_select_descr'],
    Admin::checkBox('nice_select', $configVar['nice_select'], 'nice_select')
);
Admin::row(
    $langVar['admin']['settings']['only_button'],
    $langVar['admin']['settings']['only_button_descr'],
    Admin::checkBox('only_button', $configVar['only_button'], 'only_button')
);
Admin::row(
    $langVar['admin']['settings']['ajax_form'],
    $langVar['admin']['settings']['ajax_form_descr'],
    Admin::checkBox('ajax_form', $configVar['ajax_form'], 'ajax_form')
);
Admin::row(
    $langVar['admin']['settings']['ajax_nav'],
    $langVar['admin']['settings']['ajax_nav_descr'],
    Admin::checkBox('ajax_nav', $configVar['ajax_nav'], 'ajax_nav')
);
Admin::row(
    $langVar['admin']['settings']['hide_loading'],
    $langVar['admin']['settings']['hide_loading_descr'],
    Admin::checkBox('hide_loading', $configVar['hide_loading'], 'hide_loading')
);
Admin::row(
    $langVar['admin']['settings']['not_ajax_url'],
    $langVar['admin']['settings']['not_ajax_url_descr'],
    Admin::checkBox('not_ajax_url', $configVar['not_ajax_url'], 'not_ajax_url')
);
echo <<<HTML
				</table>
			</div>
		</div>
		
        <div id="block_4" style='display:none'>
			<div class="panel-body" style="font-size:15px; font-weight:bold;">{$langVar['admin']['settings']['seo_settings']}</div>
			<div class="table-responsive">
				<table class="table">
HTML;
Admin::row(
    $langVar['admin']['settings']['index_filter'],
    $langVar['admin']['settings']['index_filter_descr'],
    Admin::select(['index_filter', $indexFilter, true, $configVar['index_filter'], false, false])
);
Admin::row(
    $langVar['admin']['settings']['index_second'],
    $langVar['admin']['settings']['index_second_descr'],
    Admin::select(['index_second', $indexFilter, true, $configVar['index_second'], false, false])
);
Admin::row(
    $langVar['admin']['settings']['code_filter'],
    $langVar['admin']['settings']['code_filter_descr'],
    Admin::select(['code_filter', $codeFilter, true, $configVar['code_filter'], false, false])
);
Admin::row(
    $langVar['admin']['settings']['redirect_filter'],
    $langVar['admin']['settings']['redirect_filter_descr'],
    Admin::checkBox('redirect_filter', $configVar['redirect_filter'], 'redirect_filter')
);
echo <<<HTML
				</table>
			</div>
		</div>
		
		<div id="block_2" style='display:none'>
			<div class="panel-body" style="font-size:15px; font-weight:bold;">{$langVar['admin']['settings']['other_settings']}</div>
			<div class="table-responsive">
				<table class="table">
HTML;
Admin::row(
    $langVar['admin']['settings']['miniposter2'],
    $langVar['admin']['settings']['miniposter2_descr'],
    Admin::checkBox('miniposter2', $configVar['miniposter2'], 'miniposter2')
);
Admin::row(
    $langVar['admin']['settings']['miniposter3'],
    $langVar['admin']['settings']['miniposter3_descr'],
    Admin::checkBox('miniposter3', $configVar['miniposter3'], 'miniposter3')
);
Admin::row(
    $langVar['admin']['settings']['hide_news'],
    $langVar['admin']['settings']['hide_news_descr'],
    Admin::checkBox('hide_news', $configVar['hide_news'], 'hide_news')
);
echo <<<HTML
				</table>
			</div>
		</div>

		<div class="panel-footer">
			<button type="submit" class="btn bg-teal btn-raised position-left" style="background-color:#1e8bc3;">{$langVar['admin']['save']}</button>
		</div>
    </div>
</form>
HTML;

$jsAdminScript[] = <<<HTML

$(function() {
    $('body').on('submit', 'form', function(e) {
        coreAdmin.ajaxSend($('form').serialize(), 'saveOptions', false);
		return false;
    });
    
    $('body').on('click', '[data-id=tail_select], [data-id=chosen_select], [data-id=nice_select]', function(e) {
        let toogleId = $(this).data('id');
        if ($(this).hasClass('on')) {
            $(this).removeClass('on');
            $('#' + toogleId).prop('checked', false);
        } else {
            $(this).addClass('on');
            $('#' + toogleId).prop('checked', 'checked');
        }
        $('[data-id=tail_select], [data-id=chosen_select], [data-id=nice_select]').not('[data-id='+toogleId+']').removeClass('on');
        $('#tail_select, #chosen_select, #nice_select').not('#'+toogleId).prop('checked', false);
        
    });
});
function ChangeOption(obj, selectedOption) {
    $('#navbar-filter li').removeClass('active');
    $(obj).parent().addClass('active');
    $('[id*=block_]').hide();
    $('#' + selectedOption).show();

    return false;
}

let excludeNews = tail.select('.excludeNews', {
    search: true,
    multiSelectAll: true,
    placeholder: "{$langVar['admin']['seo']['enter']}",
    classNames: "default white",
    multiContainer: true,
    multiShowCount: false
});

$('#searchVal .search-input').autocomplete({
    source: function(request, response) {
        let dataName = $('#searchVal .search-input').val();
        $.post('engine/lazydev/dle_filter/admin_ajax.php', {dle_hash: "{$dle_login_hash}", query: dataName, action: 'findNews'}, function(data) {
            data = jQuery.parseJSON(data);
            let newAddItem = {};

            data.forEach(function(item) {
                newAddItem[item.value] = { key: item.value, value: item.name, description: '' };
            });
            
            [].map.call(excludeNews.e.querySelectorAll("[data-select-option='add']"), function(item) {
                item.parentElement.removeChild(item);
            });
            [].map.call(excludeNews.e.querySelectorAll("[data-select-optgroup='add']"), function(item) {
                item.parentElement.removeChild(item);
            });
            
            let getOp = excludeNews.options.items['#'];
            $.each(getOp, function(index, value) {
                if (value.selected) {
                    newAddItem[value.key] = value;
                }
            });
            
            let options = new tail.select.options(excludeNews.e, excludeNews);
            options.add(newAddItem);
            
            let map = {};
            $(options.element).find('option').each(function() {
                if (map[this.value]) {
                    $(this).remove();
                }
                map[this.value] = true;
            });
            
            excludeNews.options = options;
            excludeNews.query(dataName);
        });
        
    }
});
HTML;

?>