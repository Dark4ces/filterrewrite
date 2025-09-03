<?php
/**
* Настройки модуля
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

use LazyDev\Filter\Data;
use LazyDev\Filter\Admin;
use LazyDev\Filter\Helper;

$allXfield = xfieldsload();
foreach ($allXfield as $value) {
    $xfieldArray[$value[0]] = $value[1];
}

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
}

$order = [
	'desc' => $langVar['admin']['settings']['desc'],
	'asc' => $langVar['admin']['settings']['asc']
];

if (Data::get('statistics', 'config')) {
	$allFilterData = $db->super_query("SELECT COUNT(*) as count FROM ". PREFIX . "_dle_filter_statistics")['count'];
	$dataPerPage = 25;
	if (isset($_REQUEST['cstart']) && $_REQUEST['cstart']) {
		$cstart = intval($_REQUEST['cstart']);
	} else {
		if (!isset($cstart) || $cstart < 1) {
			$cstart = 0;
		} else {
			$cstart = ($cstart - 1) * $dataPerPage;
		}
	}
	$i = $cstart;
	
	$sql = $db->query("SELECT * FROM " . PREFIX . "_dle_filter_statistics ORDER BY dateFilter DESC LIMIT {$cstart},{$dataPerPage}");
	if ($db->num_rows()) {
echo <<<HTML
<div class="panel panel-flat">
	<div class="panel-body" style="font-size:20px; font-weight:bold;border-bottom: 1px solid #ddd;">
		{$langVar['admin']['statistics_descr']}
		<input type="button" onclick="clearStatistics();" class="btn bg-warning btn-sm" style="float: right;border-radius: unset;font-size: 13px;" value="{$langVar['admin']['statistics']['clear']}">
	</div>
	<div class="table-responsive">
		<table class="table">
			<thead>
				<tr>
					<th>#</th>
					<th>{$langVar['admin']['statistics']['date']}</th>
					<th>{$langVar['admin']['statistics']['nick']}</th>
					<th>{$langVar['admin']['statistics']['ip']}</th>
					<th>{$langVar['admin']['statistics']['news']}</th>
					<th>{$langVar['admin']['statistics']['options']}</th>
					<th>{$langVar['admin']['statistics']['stat']}</th>
				</tr>
			</thead>
			<tbody>

HTML;
$numRow = 0;
$statList = $paramList = [];
while ($row = $db->get_row($sql)) {
	$i++;
	if ($row['mysqlTime'] == -1) {
		$row['mysqlTime'] = $langVar['admin']['statistics']['cache'];
	} else {
		$row['mysqlTime'] = round($row['mysqlTime'], 5) . ' ' . $langVar['admin']['statistics']['sec'];
	}
	
	if ($row['templateTime'] == -1) {
		$row['templateTime'] = $langVar['admin']['statistics']['cache'];
	} else {
		$row['templateTime'] = round($row['templateTime'], 5) . ' ' . $langVar['admin']['statistics']['sec'];
	}
	$statList[$row['idFilter']] = <<<HTML
	<tr>
		<td>{$langVar['admin']['statistics']['allTime']}</td>
		<td>{$row['allTime']} {$langVar['admin']['statistics']['sec']}</td>
	</tr>
	<tr>
		<td>{$langVar['admin']['statistics']['memory']}</td>
		<td>{$row['memoryUsage']} {$langVar['admin']['statistics']['mb']}</td>
	</tr>
	<tr>
		<td>{$langVar['admin']['statistics']['mysqlTime']}</td>
		<td>{$row['mysqlTime']}</td>
	</tr>
	<tr>
		<td>{$langVar['admin']['statistics']['templateTime']}</td>
		<td>{$row['templateTime']}</td>
	</tr>
	<tr>
		<td>{$langVar['admin']['statistics']['sqlQuery']}</td>
		<td><pre><code style="white-space: pre-wrap;">{$row['sqlQuery']}</code></pre></td>
	</tr>
HTML;
	
	
	$statisticsArray = explode('/f/', $row['statistics']);
	
	$statisticsArray[0] = str_replace($config['http_home_url'], '', $statisticsArray[0]);
	if (substr_count($statisticsArray[0], 'xfsearch/')) {
		$xf = explode('/', $statisticsArray[0]);
		if (count($xf) == 2) {
			$xfName = totranslit(trim($xf[0]));
			$xfName = $xfieldArray[$xfName] ?: $langVar['admin']['statistics']['not_detected'] . ' ' . $xfName;
			$xfValue = htmlspecialchars(strip_tags(stripslashes(trim($xf[1]))), ENT_QUOTES, $config['charset']);
			$paramList[$row['idFilter']] .= "<tr><td>{$langVar['admin']['statistics']['xf_page']} {$xfName}</td><td>{$xfValue}</td></tr>";
		}
	} elseif (substr_count($statisticsArray[0], 'tags/')) {
		$tagTemp = explode('/', $statisticsArray[0]);
		if (count($tagTemp) == 2) {
			$tagValue = htmlspecialchars(strip_tags(stripslashes(trim($tagTemp[1]))), ENT_QUOTES, $config['charset']);
			$paramList[$row['idFilter']] .= "<tr><td>{$langVar['admin']['statistics']['tag_page']}</td><td>{$tagValue}</td></tr>";
		}
	} elseif ($statisticsArray[0] != '') {
		$cat = explode('/', $statisticsArray[0]);
		$cat = trim(end($cat));
		if ($cat != '') {
			$category_id = Helper::getCategoryId($cat_info, $cat);
			if ($category_id > 0) {
				$catValue = $cat_info[$category_id]['name'];
				$paramList[$row['idFilter']] .= "<tr><td>{$langVar['admin']['statistics']['cat_page']}</td><td>{$catValue}</td></tr>";
			}
		}
	}
	
	$statisticsArray[1] = Helper::cleanSlash($statisticsArray[1]);
	$paramFilterArray = explode('/', $statisticsArray[1]);
	
	foreach ($paramFilterArray as $item) {
		$nameField = '';
		$tmp_Value = explode('=', $item);
		if (!isset($tmp_Value[1])) {
			continue;
		}
		
		if ($tmp_Value[0][0] == 'n' && $tmp_Value[0][1] == '.') {
			$tmp_Value[0] = str_replace('n.', '', $tmp_Value[0]);
		}

		$firstKey = $tmp_Value[0][0];
		$secondKey = $tmp_Value[0][1];

		if ($firstKey == 'r' && $secondKey == '.') {
			$tmp_Value[0] = str_replace('r.', '', $tmp_Value[0]);
			$tempArray = explode(';', $tmp_Value[1]);
			
			if ($tmp_Value[0] == 'prate') {
				$nameField = $langVar['admin']['statistics']['fields']['rating'];
			} else {
				$nameField = $xfieldArray[$tmp_Value[0]] ?: false;
			}
			
			if (!$nameField) {
				$nameField = $langVar['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[0];
			}
			
			$tempArray[0] = preg_replace('/\s+/', '', str_replace(',', '.', $tempArray[0]));
			$tempArray[0] = is_float($tempArray[0]) ? floatval($tempArray[0]) : intval($tempArray[0]);

			if ($tempArray[1]) {
                $tempArray[1] = preg_replace('/\s+/', '', str_replace(',', '.', $tempArray[1]));
                $tempArray[1] = is_float($tempArray[1]) ? floatval($tempArray[1]) : intval($tempArray[1]);
            }

			if (isset($tempArray[1]) && $tempArray[1] > 0) {
				$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$langVar['admin']['statistics']['from']}: {$tempArray[0]}<br>{$langVar['admin']['statistics']['to']}: {$tempArray[1]}</td></tr>";
			} else {
				$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$langVar['admin']['statistics']['from']}: {$tempArray[0]}</td></tr>";
			}
		} elseif (($firstKey == 'l' || $firstKey == 'm' || $firstKey == 's') && $secondKey == '.') {
			$tmp_Value[0] = str_replace(['l.', 'm.', 's.'], '', $tmp_Value[0]);
			$nameField = $langVar['admin']['statistics']['fields'][$tmp_Value[0]] ?: $langVar['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[0];
			if ($nameField) {
				$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$tmp_Value[1]}</td></tr>";
			}
		} elseif ($firstKey == 'j' && $secondKey == '.') {
			$tmp_Value[0] = str_replace('j.', '', $tmp_Value[0]);
			$matchesTemp = explode(';', $tmp_Value[0]);
			$tAr = [];
			foreach ($matchesTemp as $nameKey) {
				if (substr_count($nameKey, 'p.')) {
					$nameKey = str_replace('p.', '', $nameKey);
					$tAr[] = $langVar['admin']['statistics']['fields'][$nameKey] ?: $langVar['admin']['statistics']['not_detected'] . ' ' . $nameKey;
				} else {
					$nameKey = str_replace('x.', '', $nameKey);
					$tAr[] = $xfieldArray[$nameKey] ?: $langVar['admin']['statistics']['not_detected'] . ' ' . $nameKey;
				}
			}
			
			$nameField = implode(', ', $tAr);
			$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$tmp_Value[1]}</td></tr>";
		} elseif (in_array($firstKey, ['g', 'v', 'e', 'b']) && $secondKey == '.') {
			$tmp_Value[0] = str_replace(['g.', 'v.', 'e.', 'b.'], '', $tmp_Value[0]);
            $nameField = $xfieldArray[$tmp_Value[0]] ?: $langVar['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[0];
			$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$tmp_Value[1]}</td></tr>";
		} elseif ($firstKey == 'f' && $secondKey == '.') {
            $tmp_Value[0] = str_replace('f.', '', $tmp_Value[0]);
            if (in_array($tmp_Value[0], ['prate', 'pdate', 'pedit'])) {
                $nameField = $langVar['admin']['statistics']['from'] . ': ' . $langVar['admin']['statistics']['f'][$tmp_Value[0]];
            } else {
                $nameField = $xfieldArray[$tmp_Value[0]] ? $langVar['admin']['statistics']['from'] . ': ' . $xfieldArray[$tmp_Value[0]] : $langVar['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[0];
            }
            $paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$tmp_Value[1]}</td></tr>";
        } elseif ($firstKey == 't' && $secondKey == '.') {
            $tmp_Value[0] = str_replace('t.', '', $tmp_Value[0]);
            if (in_array($tmp_Value[0], ['prate', 'pdate', 'pedit'])) {
                $nameField = $langVar['admin']['statistics']['to'] . ': ' . $langVar['admin']['statistics']['f'][$tmp_Value[0]];
            } else {
                $nameField = $xfieldArray[$tmp_Value[0]] ? $langVar['admin']['statistics']['to'] . ': ' . $xfieldArray[$tmp_Value[0]] : $langVar['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[0];
            }
            $paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$tmp_Value[1]}</td></tr>";
        } elseif ($tmp_Value[0] == 'cat' || $tmp_Value[0] == 'o.cat') {
			$paramCat = explode(',', $tmp_Value[1]);
			$nameField = $langVar['admin']['statistics']['fields']['category'];
			$tAr = [];
			foreach ($paramCat as $value) {
				if (($value = intval($value)) > 0 && $cat_info[$value]) {
					$tAr[] = $cat_info[$value]['name'];
				}
			}
			$catName = implode(', ', $tAr);
			$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$catName}</td></tr>";
		} elseif ($tmp_Value[0] == 'p.cat') {
			$paramCat = explode(',', $tmp_Value[1]);
			$nameField = $langVar['admin']['statistics']['fields']['category'];
			$tAr = [];
			foreach ($paramCat as $value) {
				if (($value = intval($value)) > 0 && $cat_info[$value]) {
					$tAr[] = $cat_info[$value]['name'];
					foreach ($cat_info as $cats) {
						if ($cats['parentid'] == $value) {
							$tAr[] = $cat_info[$cats['id']]['name'];
						}
					}
				}
			}
			$catName = implode(', ', $tAr);
			$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$catName}</td></tr>";
		} elseif ($tmp_Value[0] == 'sort') {
			if (substr_count($tmp_Value[1], ';')) {
				$sortByOne = true;
			}
			$sort = explode(';', $tmp_Value[1]);
			$sort[0] = str_replace('d.', '', $sort[0]);
			
			$nameField = $sortField[$sort[0]] ?: $langVar['admin']['statistics']['not_detected'] . ' ' . $sort[0];
			if (isset($sort[1])) {
				$nameField .= ' ' . ($order[$sort[1]] ?: $langVar['admin']['statistics']['not_detected'] . ' ' . $sort[1]);
			}
			$paramList[$row['idFilter']] .= "<tr><td>{$langVar['admin']['statistics']['sort']}</td><td>{$nameField}</td></tr>";
		} elseif ($tmp_Value[0] == 'order') {
			$nameField = ' ' . ($order[$tmp_Value[1]] ?: $langVar['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[1]);
			$paramList[$row['idFilter']] .= "<tr><td>{$langVar['admin']['statistics']['order']}</td><td>{$nameField}</td></tr>";
		} else {
			$nameField = $xfieldArray[$tmp_Value[0]] ?: $langVar['admin']['statistics']['not_detected'] . ' ' . $tmp_Value[0];
			$paramList[$row['idFilter']] .= "<tr><td>{$nameField}</td><td>{$tmp_Value[1]}</td></tr>";
		}
	}
$row['nick'] = $row['nick'] == '__GUEST__' ? $langVar['admin']['statistics']['guest'] : stripslashes($row['nick']);
$foundNews = $row['foundNews'] == 1 ? "<i style=\"color:green!important;\" class=\"fa fa-check\"></i>" : "<i style=\"color:red!important;\" class=\"fa fa-remove\"></i>";
echo <<<HTML
				<tr>
					<td>{$i}</td>
					<td>{$row['dateFilter']}</td>
					<td>{$row['nick']}</td>
					<td>{$row['ip']}</td>
					<td>{$foundNews}</td>
					<td><input type="button" class="btn bg-success btn-sm" style="border-radius: unset;" value="{$langVar['admin']['statistics']['look_param']}" onclick="showDataFilter({$row['idFilter']}, 0)"></td>
					<td><input type="button" class="btn bg-info btn-sm" style="border-radius: unset;" value="{$langVar['admin']['statistics']['look_stat']}" onclick="showDataFilter({$row['idFilter']}, 1)"></td>
				</tr>
HTML;
}

$jsParam = Helper::json($paramList);
$jsStat = Helper::json($statList);
echo <<<HTML

			</tbody>
		</table>
	</div>

</div>
HTML;
$navigation = '';
if ($allFilterData > $dataPerPage) {

	if ($cstart > 0) {
		$previous = $cstart - $dataPerPage;
		$navigation .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=statistics&cstart={$previous}\" title=\"{$lang['edit_prev']}\"><i class=\"fa fa-backward\"></i></a></li>";
	}

	$enpages_count = @ceil($allFilterData / $dataPerPage);
	$enpages_start_from = 0;
	$enpages = '';

	if ($enpages_count <= 10) {
		for ($j = 1; $j <= $enpages_count; $j++) {
			if ($enpages_start_from != $cstart) {
				$enpages .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=statistics&cstart={$enpages_start_from}\">{$j}</a></li>";
			} else {
				$enpages .= "<li class=\"active\"><span>{$j}</span></li>";
			}

			$enpages_start_from += $dataPerPage;
		}
		$navigation .= $enpages;
	} else {
		$start = 1;
		$end = 10;

		if ($cstart > 0) {
			if (($cstart / $dataPerPage) > 4) {
				$start = @ceil($cstart / $dataPerPage) - 3;
				$end = $start + 9;

				if ($end > $enpages_count) {
					$start = $enpages_count - 10;
					$end = $enpages_count - 1;
				}

				$enpages_start_from = ($start - 1) * $dataPerPage;
			}
		}

		if ($start > 2) {
			$enpages .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=statistics\">1</a></li> <li><span>...</span></li>";
		}

		for ($j = $start; $j <= $end; $j++) {
			if ($enpages_start_from != $cstart) {
				$enpages .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=statistics&cstart={$enpages_start_from}\">{$j}</a></li>";
			} else {
				$enpages .= "<li class=\"active\"><span>{$j}</span></li>";
			}

			$enpages_start_from += $dataPerPage;
		}

		$enpages_start_from = ($enpages_count - 1) * $dataPerPage;
		$enpages .= "<li><span>...</span></li><li><a href=\"$PHP_SELF?mod=dle_filter&action=statistics&cstart={$enpages_start_from}\">{$enpages_count}</a></li>";

		$navigation .= $enpages;

	}

	if ($allFilterData > $i) {
		$how_next = $allFilterData - $i;
		if ($how_next > $dataPerPage) {
			$how_next = $dataPerPage;
		}
		
		$navigation .= "<li><a href=\"$PHP_SELF?mod=dle_filter&action=statistics&cstart={$i}\" title=\"{$lang['edit_next']}\"><i class=\"fa fa-forward\"></i></a></li>";
	}

	echo "<ul class=\"pagination pagination-sm mb-20\">".$navigation."</ul>";
}

$jsAdminScript[] = <<<HTML

let jsonParam = {$jsParam};
let jsonStat = {$jsStat};
let showDataFilter = function(i, b) {
	$("#dlepopup").remove();
	
	let title = b == 1 ? "{$langVar['admin']['statistics']['watch_stat']}" : "{$langVar['admin']['statistics']['watch_param']}";
	let columnTitle = b == 1 ? "{$langVar['admin']['statistics']['data']}" : "{$langVar['admin']['statistics']['field']}";
	let contentFilter = b == 1 ? jsonStat[i] : jsonParam[i];
	if (contentFilter) {
		$("body").append("<div id='dlepopup' class='dle-alert' title='"+ title + i + "' style='display:none'><div class='panel panel-flat'><div class='table-responsive'><table class='table'><thead><tr><th style='width:250px;'>"+columnTitle+"</th><th>{$langVar['admin']['statistics']['value']}</th></tr></thead><tbody>"+contentFilter+"</tbody></table></div></div></div>");

		$('#dlepopup').dialog({
			autoOpen: true,
			width: 800,
			resizable: false,
			dialogClass: "modalfixed dle-popup-alert",
			buttons: {
				"{$langVar['admin']['statistics']['close']}": function() { 
					$(this).dialog("close");
					$("#dlepopup").remove();							
				} 
			}
		});

		$('.modalfixed.ui-dialog').css({position:"fixed", maxHeight:"600px", overflow:"auto"});
		$('#dlepopup').dialog( "option", "position", { my: "center", at: "center", of: window } );
	}
};

let clearStatistics = function() {
	DLEconfirm("{$langVar['admin']['statistics']['accept_clear']}", "{$langVar['admin']['try']}", function() {
		coreAdmin.ajaxSend(false, 'clearStatistics', false);
	});
	return false;
}
HTML;
	} else {
echo <<<HTML
<div class="alert alert-danger alert-styled-left alert-arrow-left alert-component text-left">
	<h4>{$langVar['admin']['statistics']['attention']}</h4>
	{$langVar['admin']['statistics']['attention_text_2']}
</div>
HTML;
	}
} else {
echo <<<HTML
<div class="alert alert-danger alert-styled-left alert-arrow-left alert-component text-left">
	<h4>{$langVar['admin']['statistics']['attention']}</h4>
	{$langVar['admin']['statistics']['attention_text']}
</div>
HTML;
}

?>