<?php
/**
* Настройка дополнительных полей в фильтре
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

use LazyDev\Filter\Admin;
use LazyDev\Filter\Data;

$allXfield = xfieldsload();
$fieldsVar = Data::receive('fields');
if ($configVar['new_search']) {
    if ($allXfield) {
        echo <<<HTML
<div class="panel panel-flat">
	<form action="" method="post" id="fieldSettings">
		<div class="panel-body" style="font-size:20px; font-weight:bold;border-bottom: 1px solid #ddd;">{$langVar['admin']['fields_descr']}</div>
		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>{$langVar['admin']['fields']['name']}</th>
						<th>{$langVar['admin']['fields']['id']}</th>
						<th>{$langVar['admin']['fields']['type']}</th>
						<th>{$langVar['admin']['fields']['status']}</th>
					</tr>
				</thead>
				<tbody>
HTML;

        $fieldsSet = [];
        foreach ($allXfield as $value) {
            if (!$fieldsVar['status'][$value[0]]) {
                $fieldsSet[$value[0]]['off'] = 'checked';
            } else {
                $fieldsSet[$value[0]][$fieldsVar['status'][$value[0]]] = 'checked';
            }
            echo <<<HTML
                <tr>
                    <td>{$value[1]}</td>
                    <td>{$value[0]}</td>
                    <td>{$langVar['admin']['fields']['xfields'][$value[3]]}</td>
                    <td style="width:450px">
                        <input class="statusn" id="number[{$value[0]}]" value="number" type="radio" name="status[{$value[0]}]" {$fieldsSet[$value[0]]['number']}>
                        <label class="status" for="number[{$value[0]}]">{$langVar['admin']['fields']['number']}</label>
                        <input class="statusd" id="double[{$value[0]}]" value="double" type="radio" name="status[{$value[0]}]" {$fieldsSet[$value[0]]['double']}>
                        <label class="status" for="double[{$value[0]}]">{$langVar['admin']['fields']['double']}</label>
                        <input class="statust" id="text[{$value[0]}]" value="text" type="radio" name="status[{$value[0]}]" {$fieldsSet[$value[0]]['text']}>
                        <label class="status" for="text[{$value[0]}]">{$langVar['admin']['fields']['text']}</label>
                        <input class="statusf" id="off[{$value[0]}]" value="off" type="radio" name="status[{$value[0]}]" {$fieldsSet[$value[0]]['off']}>
                        <label class="status" for="off[{$value[0]}]">{$langVar['admin']['fields']['off']}</label>
                    </td>
                </tr>
HTML;
        }
        echo <<<HTML
				</tbody>
			</table>
		</div>
	</form>
	<div class="panel-body" style="font-size:20px; font-weight:bold;border-bottom: 1px solid #ddd;">{$langVar['admin']['fields']['head_news']}</div>
HTML;

        $getCountNews = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_post")['count'];
        $getCountAlready = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_dle_filter_news")['count'];
        echo <<<HTML
	<div class="panel-body">{$langVar['admin']['fields']['news_descr']}</div>

	<div class="panel-body">
		<div class="progress">
			<div id="progressbar" class="progress-bar progress-blue" style="width:0%;"><span></span></div>
		</div>
	</div>
	<div class="panel-body">
		{$langVar['admin']['fields']['news_count']} {$getCountNews}<br>
		{$langVar['admin']['fields']['already_done']} {$getCountAlready}<br>
		{$langVar['admin']['fields']['now_check']} <span class="text-danger"><span id="newscount">0</span></span><br>
		{$langVar['admin']['fields']['result_check']} <span id="progress">{$langVar['admin']['fields']['not_start']}</span>
	</div>
	<div class="panel-body">
		{$langVar['admin']['fields']['save_fields']}&nbsp;&nbsp;&nbsp;<span id="save_fields" style="margin-left: 2px;"><span style="font-size: 0px;" class="badge badge-warning"><i class="fa fa-circle-o"></i></span></span><br>
		{$langVar['admin']['fields']['create_table']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span id="create_table"><span style="font-size: 0px;" class="badge badge-warning"><i class="fa fa-circle-o"></i></span></span><br>
		{$langVar['admin']['fields']['insert_data']}&nbsp;&nbsp;&nbsp;<span id="insert_data"><span style="font-size: 0px;" class="badge badge-warning"><i class="fa fa-circle-o"></i></span></span><br>
		{$langVar['admin']['fields']['create_triggers']}&nbsp;<span id="create_triggers"><span style="font-size: 0px;" class="badge badge-warning"><i class="fa fa-circle-o"></i></span></span>
	</div>
	<div class="panel-footer">
		<button id="saveFieldsButt" type="submit" class="btn bg-teal btn-sm"><i class="fa fa-floppy-o position-left"></i>{$langVar['admin']['save']}</button>
	</div>
	<input type="hidden" id="setOk" name="setOk" value="0">
</div>
HTML;

$jsAdminScript[] = <<<HTML

let totalNews = {$getCountNews};
let okStatus = '<span style="font-size: 0px;" class="badge badge-success"><i class="fa fa-check"></i></span>';
let badStatus = '<span style="font-size: 0px;" class="badge badge-danger"><i class="fa fa-close"></i></span>';

$(function() {
    $('body').on('click', '#saveFieldsButt', function(e) {
		e.preventDefault();
		let data = $('form#fieldSettings').serialize();
        $.post('engine/lazydev/' + coreAdmin.mod + '/fields_ajax.php', {data: data, action: 'saveFields', dle_hash: dle_login_hash}, function(info) {
            info = jQuery.parseJSON(info);
            if (info.status == 'ok') {
                coreAdmin.alert(info);
				Growl.info({text: '{$langVar['admin']['fields']['setNews']}'});
				$('#save_fields').html(okStatus);
				$('#saveFieldsButt').attr('disabled', 'disabled');
				setTable();
            } else {
				$('#save_fields').html(badStatus);
				$('#saveFieldsButt').attr('disabled', false);
			}
        });
		return false;
    });
});

function setTable() {
	$.post('engine/lazydev/' + coreAdmin.mod + '/fields_ajax.php', {action: 'setTable', dle_hash: dle_login_hash}, function(info) {
		info = jQuery.parseJSON(info);
		if (info.status == 'ok') {
			coreAdmin.alert(info);
			$('#create_table').html(okStatus);
			setNewsData();
		} else if (info.status == 'off') {
			coreAdmin.alert(info);
			$('#create_table').html(okStatus);
			$('#saveFieldsButt').attr('disabled', false);
		} else {
			$('#create_table').html(badStatus);
			$('#saveFieldsButt').attr('disabled', false);
		}
	});

	return false;
}

function setNewsData() {
	$('#progress').html('{$langVar['admin']['fields']['start_check']}');

	let startCount = $('#setOk').val();
	setNews(startCount);
	
	return false;
}

function setNews(startCount) {
	Growl.info({
		text: '{$langVar['admin']['fields']['start_data']}'
	});
	$.post('engine/lazydev/' + coreAdmin.mod + '/fields_ajax.php', {startCount: startCount, action: 'setNews', dle_hash: dle_login_hash}, function(data) {
		if (data) {
			if (data.status == 'ok') {
				$('#newscount').html(data.newsData);
				$('#setOk').val(data.newsData);
				let proc = Math.round((100 * data.newsData) / totalNews);
				if (proc > 100) {
					proc = 100;
				}
				
				$('#progressbar').css('width', proc + '%');

				if (data.newsData >= totalNews) {
					$('#progress').html('{$langVar['admin']['fields']['ok_check']}');
					$('#insert_data').html(okStatus);
					coreAdmin.alert(data);
					$.post('engine/lazydev/' + coreAdmin.mod + '/fields_ajax.php', {action: 'setTriggers', dle_hash: dle_login_hash}, function(info) {
						info = jQuery.parseJSON(info);
						if (info.status == 'ok') {
							coreAdmin.alert(info);
							$('#saveFieldsButt').attr('disabled', false);
							$('#create_triggers').html(okStatus);
						} else {
							$('#saveFieldsButt').attr('disabled', false);
							$('#create_triggers').html(badStatus);
						}
					});
				} else { 
					setTimeout("setNews(" + data.newsData + ")", 1000);
				}
			}

		}
	}, 'json').fail(function() {
		$('#progress').html('{$langVar['admin']['fields']['error_check']}');
		$('#saveFieldsButt').attr('disabled', false);
		$('#insert_data').html(badStatus);
	});

	return false;
}
HTML;
    } else {
        echo <<<HTML
<div class="alert alert-danger alert-styled-left alert-arrow-left alert-component text-left text-size-small"><h4>{$langVar['admin']['fields']['not_xfield']}</h4></div>
HTML;
    }
} else {
    echo <<<HTML
<div class="alert alert-danger alert-styled-left alert-arrow-left alert-component text-left text-size-small"><h4>{$langVar['admin']['fields']['disable_search']}</h4></div>
HTML;
}
?>