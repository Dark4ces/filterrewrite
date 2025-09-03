<?php
/**
 * Логика фильтра
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

namespace LazyDev\Filter;

setlocale(LC_NUMERIC, 'C');

use DLEPlugins;
use microTimer;

class Filter
{
	private static $instance = null;
	static $oldMySQL;
	static $pageFilter = 0;
	static $filterData, $filterParam, $vars, $checks, $sqlWhere, $sortByOne, $globalTag, $seoData, $fieldsVar;
	static $dleConfig, $dleDb, $dleCat, $dleMember, $dleXfields, $modConfig;
	static $pageDLE, $orderBy, $urlFilter, $catId;
	static $innerTable = '';
	static $whereTable = '';
	static $seoView;
	private static $reservedKeys = [
		'cat' => '',
		'o.cat' => '',
		'p.cat' => '',
		'sort' => '',
		'order' => ''
	];
	private static $orderByKeys = [
		'date' => 'p.date',
		'editdate' => 'e.editdate',
		'title' => 'p.title',
		'comm_num' => 'p.comm_num',
		'news_read' => 'e.news_read',
		'autor' => 'p.autor',
		'rating' => 'e.rating'
	];
	
	/**
     * Конструктор
     *
	 * @return   Filter
     */
	static function construct()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
	
    /**
     * Старт модуля
     *
     * @param    array    $vars
	 * @return   Filter
     */
    static function load($vars = [])
    {
		global $config, $db, $cat_info, $member_id;
		
		self::$dleConfig = $config;
		self::$dleDb = $db;
		self::$dleCat = $cat_info;
		self::$dleMember = $member_id;
		self::$dleXfields = xfieldsload();
		self::$modConfig = Data::receive('config');
		self::$fieldsVar = Data::receive('fields');
		self::$globalTag = [];
		
		self::$vars = $vars;
		self::$vars['data'] = $_GET['filter_data'] ?: self::$vars['data'];
		
		self::$oldMySQL = version_compare(self::$dleDb->mysql_version, '5.5.3', '<') == 1 ? false : true;
		
		self::$seoView = new View('seo');
		
		self::$sortByOne = false;
		
		return self::$instance;
    }
	
	/**
     * Получаем данные фильтра
     *
	 * @return    Filter
     */
	static function getVar()
	{
		$tmp_Data = trim(strip_tags(str_ireplace(['<?', '?>', '$', '@'], '', self::$vars['data'])));
		$tmp_Data = Helper::cleanSlash($tmp_Data);
		
		$tmp_Data = explode(self::$vars['ajax'] === true ? '&' : '/', $tmp_Data);
		
		foreach ($tmp_Data as $value) {
			$tmpValue = explode('=', $value);
			if ($tmpValue[1] != '' && $tmpValue[1] != '&') {
				$tmpValue[0] = rawurldecode($tmpValue[0]);
				if (self::$filterData[$tmpValue[0]]) {
					self::$filterData[$tmpValue[0]] .= ',' . str_replace(['+', '%20'], ' ', rawurldecode($tmpValue[1]));
				} else {
					self::$filterData[$tmpValue[0]] = str_replace(['+', '%20'], ' ', rawurldecode($tmpValue[1]));
				}

                if (self::$modConfig['new_search'] && (!self::$fieldsVar['status'][$value[0]] || self::$fieldsVar['status'][$value[0]] == 'off')) {
                    self::$modConfig['new_search'] = false;
                }
			}
		}
		
		return self::$instance;
	}
	
	/**
     * Получаем страницу DataLife Engine
     *
	 * @return    Filter
     */
	static function getPage()
	{
		if (self::$modConfig['search_xfield'] == 1) {
			if (isset($_GET['xf']) || self::$vars['ajax'] && substr_count(self::$vars['url'], 'xfsearch/')) {
				self::$checks['xfsearch'] = true;
				
				$xf = $_GET['xf'] ?: self::$vars['url'];
				$xf = self::$dleConfig['version_id'] > 13.1 ? rawurldecode($xf) : urldecode($xf);
				$xf = Helper::cleanSlash($xf);
				$xf = explode('/', $xf);
				if (isset($_GET['xf']) && count($xf) == 2) {
					$xfName = totranslit(trim($xf[0]));
					$xfValue = htmlspecialchars(strip_tags(stripslashes(trim($xf[1]))), ENT_QUOTES, self::$dleConfig['charset']);
				} elseif (self::$vars['ajax'] && $xf[2] != '' && $xf[3] != 'f' && $xf[3] != '') {
					$xfName = totranslit(trim($xf[2]));
					$xfValue = htmlspecialchars(strip_tags(stripslashes(trim($xf[3]))), ENT_QUOTES, self::$dleConfig['charset']);
				}
				
				if ($xfName && $xfValue) {
					$letInXf = false;
					$xfieldsArray = xfieldsload();
					foreach ($xfieldsArray as $xfieldArray) {
						if ($xfieldArray[0][0] == $xfName && $xfieldArray[0][6] == 1) {
							$letInXf = true;
							break;
						}
					}
					if ($letInXf) {
						self::$pageDLE = 'xfsearch/' . $xfName . '/' . (self::$dleConfig['version_id'] > 13.1 ? rawurlencode(str_replace(["&#039;", "&quot;"], ["'", '"'], $xfValue)) : urlencode(str_replace("&#039;", "'", $xfValue))) . '/';
						$xfName = self::$dleDb->safesql($xfName);
						$xfValue = self::$dleDb->safesql($xfValue);
					
						self::$whereTable = " AND xf.tagname='{$xfName}' AND xf.tagvalue='{$xfValue}'";
						self::$innerTable = "INNER JOIN " . PREFIX . "_xfsearch xf ON (xf.news_id=p.id)";
					}
				}
			}
		}
		
		if (!self::$checks && self::$modConfig['search_tag'] == 1) {
			if (isset($_GET['tag']) || self::$vars['ajax'] && substr_count(self::$vars['url'], 'tags/')) {
				self::$checks['tag'] = true;
				
				if(isset($_GET['tag'])){
					$tag = $_GET['tag'];
				} elseif(self::$vars['url']) {
					$tagTemp = explode('/', self::$vars['url']);
					if ($tagTemp[2] != 'f' && $tagTemp[2] != '') {
						$tag = $tagTemp[2];
					}
				}
				
				if ($tag) {
					$tag = self::$dleConfig['version_id'] > 13.1 ? rawurldecode($tag) : urldecode($tag);
					$tag = Helper::cleanSlash($tag);
					$tag = htmlspecialchars(strip_tags(stripslashes(trim($tag))), ENT_COMPAT, self::$dleConfig['charset']);
					$urlTag = self::$dleConfig['version_id'] > 13.1 ? rawurlencode(str_replace(["&#039;", "&quot;", "&amp;"], ["'", '"', "&"], $tag)) : urlencode($tag);
					$tag = self::$dleDb->safesql($tag);
					
					self::$pageDLE = 'tags/' . $urlTag . '/';
					self::$whereTable = " AND t.tag='{$tag}'";
					self::$innerTable = "INNER JOIN " . PREFIX . "_tags t ON (t.news_id=p.id)";
				}
			}
		}
		
		if (!self::$checks && self::$modConfig['search_cat'] == 1) {
			if (isset($_GET['cat']) || self::$vars['ajax'] && self::$vars['url'] != '') {
				self::$checks['cat'] = true;
				$cat = $_GET['cat'] ?: self::$vars['url'];
				$cat = explode('/f/', $cat);
				$cat = explode('/page', $cat[0])[0];
				$cat = Helper::cleanSlash($cat);
				$cat = explode('/', $cat);
				$cat = trim(end($cat));
				if ($cat != '') {
					$category_id = get_ID(self::$dleCat, $cat);

					if ($category_id > 0) {
						self::$catId = $category_id;
						self::$pageDLE = get_url($category_id) . '/';
						if (self::$dleConfig['version_id'] > 13.1) {
							self::$whereTable = '';
							self::$innerTable = "INNER JOIN (SELECT DISTINCT(" . PREFIX . "_post_extras_cats.news_id) FROM " . PREFIX . "_post_extras_cats WHERE cat_id IN ('" . $category_id . "')) c ON (p.id=c.news_id)";
						} else {
							if (self::$dleConfig['allow_multi_category']) {
								if (self::$oldMySQL) {
									self::$sqlWhere[] = "category REGEXP '[[:<:]](" . $category_id  . ")[[:>:]]'";
								} else {
									self::$sqlWhere[] = "category REGEXP '([[:punct:]]|^)(" . $category_id . ")([[:punct:]]|$)'";
								}
							} else {
								self::$sqlWhere[] = "category IN ('" . $category_id . "')";
							}
						}
					}
				}
			}
		}
		
		if (self::$pageDLE == '/') {
			self::$pageDLE = '';
		}
		
		return self::$instance;
	}
	
	/**
     * Разбираем данные фильтра
     *
	 * @return    Filter
     */
	static function filterOptions()
	{
		self::$filterParam = array_diff_key(self::$filterData, self::$reservedKeys);
		foreach (self::$filterParam as $key => $item) {
			$matchesTemp = $tempArray = [];
			$valueTemp = '';
			$originalKey = $key;

			$andMod = false;
			if ($key[0] == 'n' && $key[1] == '.') {
				$key = str_replace('n.', '', $key);
				if ($key) {
					$andMod = true;
				} else {
					continue;
				}
			}
			
			$firstKey = $key[0];
			$secondKey = $key[1];
			
			self::$seoView->set_block("'\\[{$key}\\](.*?)\\[/{$key}\\]'si", '\\1');
			if ($firstKey == 'r' && $secondKey == '.') {
				$tempArray = explode(';', $item);
				
				$tempArray[0] = self::typeNumber($tempArray[0]);
				self::$seoView->set('{' . $key . '-from}', $tempArray[0]);
				self::$globalTag['tag']['{filter-' . $key . '-from}'] = $tempArray[0];
                self::$globalTag['block'][] = '#\[filter-' . $key . '-from\](.*?)\[\/filter-' . $key . '-from\]#is';
                self::$globalTag['hide'][] = '#\[not-filter-' . $key . '-from\](.*?)\[\/not-filter-' . $key . '-from\]#is';
				
				if (isset($tempArray[1]) && $tempArray[1] > 0) {
					$tempArray[1] = self::typeNumber($tempArray[1]);
					self::$seoView->set('{' . $key . '-to}', $tempArray[1]);
					self::$globalTag['tag']['{filter-' . $key . '-to}'] = $tempArray[1];
                    self::$globalTag['block'][] = '#\[filter-' . $key . '-to\](.*?)\[\/filter-' . $key . '-to\]#is';
                    self::$globalTag['hide'][] = '#\[not-filter-' . $key . '-to\](.*?)\[\/not-filter-' . $key . '-to\]#is';
				}
				
				$tempArray = [];
			} else {
				self::$globalTag['tag']['{filter-' . $key . '}'] = str_replace(',', ', ', $item);
                self::$globalTag['block'][] = '#\[filter-' . $key . '\](.*?)\[\/filter-' . $key . '\]#is';
                self::$globalTag['hide'][] = '#\[not-filter-' . $key . '\](.*?)\[\/not-filter-' . $key . '\]#is';
				self::$seoView->set('{' . $key . '}', str_replace(',', ', ', $item));
			}
			
			$item = explode(',', $item);
			if ($firstKey == 'l' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('l.', '', $key)));
				if ($key) {
					foreach ($item as $value) {
						$value = self::$dleDb->safesql($value);
						$tempArray[] = "{$key} LIKE '%{$value}%'";
					}
					
					if ($andMod) {
						self::$sqlWhere[] =  '(' . implode(' AND ', $tempArray) . ')';
					} else {
						self::$sqlWhere[] =  '(' . implode(' OR ', $tempArray) . ')';
					}
				}
			} elseif ($firstKey == 'm' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('m.', '', $key)));
				if ($key) {
					if ($andMod) {
						foreach ($item as $value) {
							if (self::$oldMySQL) {
								self::$sqlWhere[] = "{$key} REGEXP '[[:<:]](" . self::$dleDb->safesql($value)  . ")[[:>:]]'";
							} else {
								self::$sqlWhere[] = "{$key} REGEXP '([[:punct:]]|^)(" . self::$dleDb->safesql($value) . ")([[:punct:]]|$)'";
							}
						}
					} else {
						$valueTemp = self::$dleDb->safesql(implode('|', $item));
						if (self::$oldMySQL) {
							self::$sqlWhere[] = "{$key} REGEXP '[[:<:]](" . $valueTemp  . ")[[:>:]]'";
						} else {
							self::$sqlWhere[] = "{$key} REGEXP '([[:punct:]]|^)(" . $valueTemp . ")([[:punct:]]|$)'";
						}
					}
				}
			}  elseif ($firstKey == 's' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('s.', '', $key)));
				if ($key) {
					foreach ($item as $value) {
						$value = self::$dleDb->safesql($value);
						$tempArray[] = "{$key}='{$value}'";
					}
					
					if ($andMod) {
						self::$sqlWhere[] =  '(' . implode(' AND ', $tempArray) . ')';
					} else {
						self::$sqlWhere[] =  '(' . implode(' OR ', $tempArray) . ')';
					}
				}
			} elseif ($firstKey == 'r' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('r.', '', $key)));
				if ($key) {
					$tempArray = explode(';', $item[0]);
					
					$tempArray[0] = self::$dleDb->safesql(self::typeNumber($tempArray[0]));
					
					if (isset($tempArray[1])) {
						$tempArray[1] = self::$dleDb->safesql(self::typeNumber($tempArray[1]));
					}
					
					if ($tempArray[1] > 0 && $tempArray[0] >= 0) {
						if ($key == 'prate') {
							if (!self::$dleConfig['rating_type']) {
								self::$sqlWhere[] = "CEIL(e.rating / e.vote_num) >= {$tempArray[0]} AND CEIL(e.rating / e.vote_num) <= {$tempArray[1]}";
							} else {
								self::$sqlWhere[] = "e.rating >= {$tempArray[0]} AND e.rating <= {$tempArray[1]}";
							}
						} else {
                            if (self::$modConfig['new_search'] == 1) {
							    self::$sqlWhere[] = "f.`xf_{$key}` >= {$tempArray[0]} AND f.`xf_{$key}` <= {$tempArray[1]}";
                            } else {
                                self::$sqlWhere[] = "ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) >= {$tempArray[0]} AND ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) <= {$tempArray[1]}";
                            }
						}
						self::$seoData['r.' . $key . '.from'] = $tempArray[0];
						self::$seoData['r.' . $key . '.to'] = $tempArray[1];
					} elseif ($tempArray[0] >= 0) {
						if ($key == 'prate') {
							if (!self::$dleConfig['rating_type']) {
								self::$sqlWhere[] = "CEIL(e.rating / e.vote_num) >= {$tempArray[0]}";
							} else {
								self::$sqlWhere[] = "e.rating >= {$tempArray[0]}";
							}
						} else {
                            if (self::$modConfig['new_search'] == 1) {
                                self::$sqlWhere[] = "f.`xf_{$key}` >= {$tempArray[0]}";
                            } else {
                                self::$sqlWhere[] = "ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) >= {$tempArray[0]}";
                            }
						}
						self::$seoData['r.' . $key . '.from'] = $tempArray[0];
					} else {
						unset(self::$filterData[$originalKey]);
					}
				}
			} elseif ($firstKey == 'j' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('j.', '', $key)));
				if ($key) {
					$matchesTemp = explode(';', $key);
					foreach ($matchesTemp as $nameKey) {
						if (substr_count($nameKey, 'p.')) {
							$nameKey = self::$dleDb->safesql(str_replace('p.', '', $nameKey));
							$valueTemp = self::$dleDb->safesql($item[0]);
							$tempArray[] = $nameKey . " LIKE '%{$valueTemp}%'";
						} else {
							$nameKey = self::$dleDb->safesql(str_replace('x.', '', $nameKey));
							$valueTemp = self::typeXfield($nameKey, $item[0]);
                            if (self::$modConfig['new_search'] == 1) {
                                $tempArray[] = "f.`xf_{$nameKey}` LIKE '%{$valueTemp}%'";
                            } else {
                                $tempArray[] = "SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '" . $nameKey . "|', -1), '||', 1) LIKE '%{$valueTemp}%'";
                            }
						}
					}
					
					self::$sqlWhere[] = '(' . implode(' OR ', $tempArray) . ')';
				}
			} elseif ($firstKey == 'f' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('f.', '', $key)));
				if ($key) {
					if ($key != 'pdate' && $key != 'pedit') {
						$item[0] = self::$dleDb->safesql(self::typeNumber($item[0]));
					}
					
					if ($key == 'prate') {
						if (!self::$dleConfig['rating_type']) {
							self::$sqlWhere[] = "CEIL(e.rating / e.vote_num) >= {$item[0]}";
						} else {
							self::$sqlWhere[] = "e.rating >= {$item[0]}";
						}
					} elseif ($key == 'pdate') {
						$item[0] = date('Y-m-d', strtotime($item[0]));
						if ($item[0] != '1970-01-01') {
							self::$sqlWhere[] = "DATE(p.date) >= '{$item[0]}'";
						} else {
							unset(self::$filterData[$originalKey]);
						}
					} elseif ($key == 'pedit') {
						$item[0] = strtotime($item[0]);
						if ($item[0]) {
							self::$sqlWhere[] = "e.editdate >= '{$item[0]}'";
						} else {
							unset(self::$filterData[$originalKey]);
						}
					} else {
                        if (self::$modConfig['new_search'] == 1) {
                            self::$sqlWhere[] = "f.`xf_{$key}` >= {$item[0]}";
                        } else {
                            self::$sqlWhere[] = "ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) >= {$item[0]}";
                        }
					}
				}
			} elseif ($firstKey == 't' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('t.', '', $key)));
				if ($key) {
					if ($key != 'pdate' && $key != 'pedit') {
						$item[0] = self::$dleDb->safesql(self::typeNumber($item[0]));
					}
					
					if ($key == 'prate') {
						if (!self::$dleConfig['rating_type']) {
							self::$sqlWhere[] = "CEIL(e.rating / e.vote_num) <= {$item[0]}";
						} else {
							self::$sqlWhere[] = "e.rating <= {$item[0]}";
						}
					} elseif ($key == 'pdate') {
						$item[0] = date('Y-m-d', strtotime($item[0]));
						if ($item[0] != '1970-01-01') {
							self::$sqlWhere[] = "DATE(p.date) <= '{$item[0]}'";
						} else {
							unset(self::$filterData[$originalKey]);
						}
					} elseif ($key == 'pedit') {
						$item[0] = strtotime($item[0]);
						if ($item[0]) {
							self::$sqlWhere[] = "e.editdate <= '{$item[0]}'";
						} else {
							unset(self::$filterData[$originalKey]);
						}
					} else {
                        if (self::$modConfig['new_search'] == 1) {
                            self::$sqlWhere[] = "f.`xf_{$key}` <= {$item[0]}";
                        } else {
                            self::$sqlWhere[] = "ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) <= {$item[0]}";
                        }
					}
				}
			} elseif ($firstKey == 'g' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('g.', '', $key)));
				if ($key) {
                    if (self::$modConfig['new_search'] == 1) {
                        self::$sqlWhere[] = "CHARACTER_LENGTH(f.`xf_{$key}`) > 0";
                    } else {
                        self::$sqlWhere[] = "CHARACTER_LENGTH(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1)) > 0 AND SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1) NOT LIKE '%|%'";
                    }
				}
			} elseif ($firstKey == 'v' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('v.', '', $key)));
				if ($key) {
					foreach ($item as $value) {
						$valueTemp = self::typeXfield($key, $value);
                        if (self::$modConfig['new_search'] == 1) {
						    $tempArray[] = "f.`xf_{$key}` NOT LIKE '%{$valueTemp}%'";
                        } else {
                            $tempArray[] = "SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1) NOT LIKE '%{$valueTemp}%'";
                        }
					}

					self::$sqlWhere[] = '(' . implode(' AND ', $tempArray) . ')';
				}
			} elseif ($firstKey == 'e' && $secondKey == '.') {
				$key = self::$dleDb->safesql(trim(str_replace('e.', '', $key)));
				if ($key) {
                    if (self::$modConfig['new_search'] == 1) {
					    self::$sqlWhere[] = "CHARACTER_LENGTH(f.`xf_{$key}`) = 0";
                    } else {
                        self::$sqlWhere[] = "CHARACTER_LENGTH(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1 )) = 0 AND SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1) NOT LIKE '%|%'";
                    }
				}
			} else {
				if ($firstKey == 'b' && $secondKey == '.') {
					$key = self::$dleDb->safesql(trim(str_replace('b.', '', $key)));
					if ($key) {
						foreach ($item as $value) {
							$valueTemp = self::typeXfield($key, $value);
                            if (self::$modConfig['new_search'] == 1) {
                                $tempArray[] = "f.`xf_{$key}` = '{$valueTemp}'";
                            } else {
                                $tempArray[] = "SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1) LIKE '{$valueTemp}'";
                            }
						}

						self::$sqlWhere[] =  '(' . implode(' OR ', $tempArray) . ')';
					}
				} else {
					$key = self::$dleDb->safesql($key);
					foreach ($item as $value) {
						$valueTemp = self::typeXfield($key, $value);
                        if (self::$modConfig['new_search'] == 1) {
                            $tempArray[] = "f.`xf_{$key}` LIKE '%{$valueTemp}%'";
                        } else {
                            $tempArray[] = "SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$key}|', -1), '||', 1) LIKE '%{$valueTemp}%'";
                        }
					}
					
					if ($andMod) {
						self::$sqlWhere[] =  '(' . implode(' AND ', $tempArray) . ')';
					} else {
						self::$sqlWhere[] =  '(' . implode(' OR ', $tempArray) . ')';
					}
				}
			}
			
			unset($matches);
			unset($tempArray);
		}
		
		// Страница фильтра
		if (substr_count(self::$vars['data'], '/page/') > 0) {
			$page = explode('/page/', self::$vars['data']);
			$page = intval(str_ireplace('/', '', $page[1]));
			if ($page > 0) {
				self::$pageFilter = $page;
				self::$seoView->set_block("'\\[filter-page\\](.*?)\\[/filter-page\\]'si", '\\1');
				self::$seoView->set('{filter-page}', self::$pageFilter);
				self::$globalTag['tag']['{filter-page}'] = self::$pageFilter;
                self::$globalTag['block'][] = '#\[filter-page\](.*?)\[\/filter-page\]#is';
                self::$globalTag['hide'][] = '#\[not-filter-page\](.*?)\[\/not-filter-page\]#is';
			} else {
				self::$pageFilter = 0;
				self::$seoView->set_block("'\\[filter-page\\](.*?)\\[/filter-page\\]'si", '');
			}
		}
		
		// Отдельные параметры
        $newCatKey = [];
		if (self::$filterData['cat']) {
			$paramCat = explode(',', self::$filterData['cat']);
			$tempArray = $tempCatName = [];
			
			foreach ($paramCat as $value) {
				if (($value = intval($value)) > 0 && self::$dleCat[$value]) {
					if (self::$catId != $value) {
						if (self::$dleConfig['allow_multi_category']) {
							if (self::$oldMySQL) {
								$tempArray[] = "category REGEXP '[[:<:]](" . $value  . ")[[:>:]]'";
							} else {
								$tempArray[] = "category REGEXP '([[:punct:]]|^)(" . $value . ")([[:punct:]]|$)'";
							}
						} else {
							$tempArray[] = "category IN ('" . $value . "')";
						}
						$newCatKey[$value] = $value;
					}
					$tempCatName[] = self::$dleCat[$value]['name'];
				}
			}
			
			if ($tempArray) {
				self::$filterData['cat'] = implode(',', $newCatKey);
				self::$sqlWhere[] = implode(' AND ', $tempArray);
			} else {
				unset(self::$filterData['cat']);
			}
			
			if ($tempCatName) {
				$tempCatName = implode(', ', $tempCatName);
				self::$globalTag['tag']['{filter-cat}'] = $tempCatName;
                self::$globalTag['block'][] = '#\[filter-cat\](.*?)\[\/filter-cat\]#is';
                self::$globalTag['hide'][] = '#\[not-filter-cat\](.*?)\[\/not-filter-cat\]#is';
				self::$seoView->set('{cat}', $tempCatName);
				self::$seoView->set_block("'\\[cat\\](.*?)\\[/cat\\]'si", '\\1');
			} else {
				self::$seoView->set('{cat}', '');
			}
			
			unset($tempArray, $tempCatName);
		} else {
			self::$seoView->set_block("'\\[cat\\](.*?)\\[/cat\\]'si", '');
		}
		
		if (self::$filterData['o.cat']) {
			$paramCat = explode(',', self::$filterData['o.cat']);
			$tempArray = $tempCatName = [];
			foreach ($paramCat as $value) {
				if (($value = intval($value)) > 0 && self::$dleCat[$value]) {
					if (self::$catId != $value) {
						$tempArray[] = $value;
                        $newCatKey[$value] = $value;
					}
					$tempCatName[] = self::$dleCat[$value]['name'];
				}
			}
			
			if ($tempArray) {
				self::$filterData['o.cat'] = implode(',', $tempArray);
				if (self::$dleConfig['allow_multi_category']) {
					if (self::$oldMySQL) {
						self::$sqlWhere[] = "category REGEXP '[[:<:]](" . implode('|', $tempArray)  . ")[[:>:]]'";
					} else {
						self::$sqlWhere[] = "category REGEXP '([[:punct:]]|^)(" . implode('|', $tempArray) . ")([[:punct:]]|$)'";
					}
				} else {
					self::$sqlWhere[] = "category IN ('" . implode("','", $tempArray) . "')";
				}
			} else {
				unset(self::$filterData['o.cat']);
			}
			
			if ($tempCatName) {
				$tempCatName = implode(', ', $tempCatName);
				self::$globalTag['tag']['{filter-o.cat}'] = $tempCatName;
                self::$globalTag['block'][] = '#\[filter-o.cat\](.*?)\[\/filter-o.cat\]#is';
                self::$globalTag['hide'][] = '#\[not-filter-o.cat\](.*?)\[\/not-filter-o.cat\]#is';
				self::$seoView->set('{o.cat}', $tempCatName);
				self::$seoView->set_block("'\\[o.cat\\](.*?)\\[/o.cat\\]'si", '\\1');
			} else {
				self::$seoView->set('{o.cat}', '');
			}
			unset($tempArray, $tempCatName);
		} else {
			self::$seoView->set_block("'\\[o.cat\\](.*?)\\[/o.cat\\]'si", '');
		}
		
		if (self::$filterData['p.cat']) {
			$paramCat = explode(',', self::$filterData['p.cat']);
			$tempArray = $tempCatName = [];

			foreach ($paramCat as $value) {
				if (($value = intval($value)) > 0 && self::$dleCat[$value]) {
					$tempCatName[] = self::$dleCat[$value]['name'];
                    foreach (self::$dleCat as $cats) {
                        if ($cats['parentid'] == $value) {
                            if (self::$catId != $cats['id'] && !in_array($cats['id'], $newCatKey)) {
                                $tempArray[] = $cats['id'];
                            }
                            $tempCatName[] = self::$dleCat[$cats['id']]['name'];
                        }
                    }
					if (self::$catId != $value) {
						$tempArray[] = $value;
					}
				}
			}
			
			if ($tempArray) {
				if (self::$dleConfig['allow_multi_category']) {
					if (self::$oldMySQL) {
						self::$sqlWhere[] = "category REGEXP '[[:<:]](" . implode('|', $tempArray)  . ")[[:>:]]'";
					} else {
						self::$sqlWhere[] = "category REGEXP '([[:punct:]]|^)(" . implode('|', $tempArray) . ")([[:punct:]]|$)'";
					}
				} else {
					self::$sqlWhere[] = "category IN ('" . implode("','", $tempArray) . "')";
				}
			} else {
				unset(self::$filterData['p.cat']);
			}
			
			if ($tempCatName) {
				$tempCatName = implode(', ', $tempCatName);
				self::$globalTag['tag']['{filter-p.cat}'] = $tempCatName;
                self::$globalTag['block'][] = '#\[filter-p.cat\](.*?)\[\/filter-p.cat\]#is';
                self::$globalTag['hide'][] = '#\[not-filter-p.cat\](.*?)\[\/not-filter-p.cat\]#is';
				self::$seoView->set('{p.cat}', $tempCatName);
				self::$seoView->set_block("'\\[p.cat\\](.*?)\\[/p.cat\\]'si", '\\1');
			} else {
				self::$seoView->set('{p.cat}', '');
			}
			
			unset($tempArray, $tempCatName);
		}  else {
			self::$seoView->set_block("'\\[p.cat\\](.*?)\\[/p.cat\\]'si", '');
		}
		
		if (self::$modConfig['exclude_categories']) {
			$tempArray = [];
			foreach (self::$modConfig['exclude_categories'] as $value) {
				if (($value = intval($value)) > 0) {
					$tempArray[] = $value;
				}
			}
			
			if ($tempArray) {
				if (self::$dleConfig['allow_multi_category']) {
					if (self::$oldMySQL) {
						self::$sqlWhere[] = "category NOT REGEXP '[[:<:]](" . implode('|', $tempArray)  . ")[[:>:]]'";
					} else {
						self::$sqlWhere[] = "category NOT REGEXP '([[:punct:]]|^)(" . implode('|', $tempArray) . ")([[:punct:]]|$)'";
					}
				} else {
					self::$sqlWhere[] = "category NOT IN ('" . implode("','", $tempArray) . "')";
				}
			}
			
			unset($tempArray);
		}

		if (self::$modConfig['excludeNews'] || self::$modConfig['hide_news'] && self::$dleMember['news_hide'] != '') {
			$tempArray = [];
			$configNews = self::$modConfig['excludeNews'] ?: [];
			if (self::$modConfig['hide_news'] && self::$dleMember['news_hide'] != '') {
				$memberHideNews = explode(',', self::$dleMember['news_hide']);
				if (count($memberHideNews) > 0) {
					$configNews = array_merge($configNews, $memberHideNews);
				}
			}
			
			foreach ($configNews as $value) {
				if (($value = intval($value)) > 0) {
					$tempArray[] = $value;
				}
			}
			
			if ($tempArray) {
				self::$sqlWhere[] = "p.id NOT IN ('" . implode("','", $tempArray) . "')";
			}
			
			unset($tempArray);
		}
		
		self::$seoView->compile('seo');
		
		return self::$instance;
	}
	
	/**
     * Получаем сортировку новостей
     *
	 * @return    Filter
     */
	static function order()
	{
		self::$orderBy = 'p.date desc';
		$sort = [];
		if (self::$filterData['sort']) {
			if (substr_count(self::$filterData['sort'], ';')) {
				self::$sortByOne = true;
			}
			$sort = explode(';', self::$filterData['sort']);
			$sort[1] = $sort[1] ?: self::$filterData['order'];
		} else {
			$sort[0] = self::$modConfig['sort_field'];
			$sort[1] = self::$modConfig['order'];
		}
		
		if ($sort[0]) {
			$sort[0] = self::$dleDb->safesql(trim($sort[0]));
			$sort[1] = $sort[1] == 'asc' ? 'asc' : 'desc';
			
			if (self::$sortByOne) {
				self::$filterData['sort'] = implode(';', $sort);
			} else {
				self::$filterData['sort'] = $sort[0];
				self::$filterData['order'] = $sort[1];
			}
			
			self::$seoView->result['seo'] = str_replace('{sort}', $sort[0], self::$seoView->result['seo']);
			self::$seoView->result['seo'] = str_replace('{order}', $sort[1], self::$seoView->result['seo']);
			
			self::$globalTag['tag']['{filter-sort}'] = $sort[0];
			self::$globalTag['block'][] = '#\[filter-sort\](.*?)\[\/filter-sort\]#is';
			self::$globalTag['hide'][] = '#\[not-filter-sort\](.*?)\[\/not-filter-sort\]#is';
			
			self::$globalTag['tag']['{filter-order}'] = $sort[1];
			self::$globalTag['block'][] = '#\[filter-order\](.*?)\[\/filter-order\]#is';
			self::$globalTag['hide'][] = '#\[not-filter-order\](.*?)\[\/not-filter-order\]#is';
			
			if (isset(self::$orderByKeys[$sort[0]])) {
				if ($sort[0] == 'rating' && !self::$dleConfig['rating_type']) {
					self::$orderBy = 'CEIL(e.rating / e.vote_num)';
				} else {
					self::$orderBy = $sort[0];
				}
			} else {
                $absOrder = false;
				if ($sort[0][0] == 'd' && $sort[0][1] == '.') {
					$sort[0] = str_replace('d.', '', $sort[0]);
                    $absOrder = true;
				}

                if (self::$modConfig['new_search'] == 1) {
                    self::$orderBy = "f.`xf_{$sort[0]}`";
                } else {
                    if ($absOrder) {
                        self::$orderBy = "ABS(SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$sort[0]}|', -1), '||', 1))";
                    } else {
                        self::$orderBy = "SUBSTRING_INDEX(SUBSTRING_INDEX(xfields, '{$sort[0]}|', -1), '||', 1)";
                    }
                }
			}
			
			self::$orderBy .= ' ' . $sort[1];
		} else {
			self::$seoView->result['seo'] = str_replace('{sort}', 'date', self::$seoView->result['seo']);
			self::$seoView->result['seo'] = str_replace('{order}', 'desc', self::$seoView->result['seo']);
			self::$globalTag['tag']['{filter-sort}'] = 'date';
			self::$globalTag['block'][] = '#\[filter-sort\](.*?)\[\/filter-sort\]#is';
			self::$globalTag['hide'][] = '#\[not-filter-sort\](.*?)\[\/not-filter-sort\]#is';
			
			self::$globalTag['tag']['{filter-order}'] = 'desc';
			self::$globalTag['block'][] = '#\[filter-order\](.*?)\[\/filter-order\]#is';
			self::$globalTag['hide'][] = '#\[not-filter-order\](.*?)\[\/not-filter-order\]#is';
			
			self::$filterData['sort'] = 'date';
			self::$filterData['order'] = 'desc';
		}
		
		self::$seoView->result['seo'] = preg_replace("'\\[sort\\](.*?)\\[/sort\\]'si", '\\1', self::$seoView->result['seo']);
		self::$seoView->result['seo'] = preg_replace("'\\[order\\](.*?)\\[/order\\]'si", '\\1', self::$seoView->result['seo']);

		return self::$instance;
	}
	
	/**
     * Получаем ссылку фильтра
     *
	 * @return    Filter
     */
	static function setUrl()
	{
		ksort(self::$filterData);
		$a = ['order' => self::$filterData['order'], 'sort' => self::$filterData['sort']];
		unset(self::$filterData['order'], self::$filterData['sort']);
		$tempArray = [];
		foreach (self::$filterData as $key => $value) {
			$tempArray[] = $key . '=' . $value;
		}

		if ($a['sort']) {
			$tempArray[] = 'sort=' . $a['sort'];
		}

		if ($a['order']) {
			$tempArray[] = 'order=' . $a['order'];
		}
		
		self::$urlFilter = self::$dleConfig['http_home_url'] . self::$pageDLE . 'f/' . str_replace([' ', '%20'], '+', implode('/', $tempArray));
		if (self::$seoData) {
			self::$filterData = self::$filterData + self::$seoData;
		}

        self::$filterData = self::$filterData + $a;

		return self::$instance;
	}
	
	/**
     * Запись статистики
     *
	 * @param    array    $param
     */
	static function setStatistics($param = [])
	{
		global $_IP, $member_id, $microTimer;
		
		$dateFilter = date('Y-m-d H:i:s', time());
		$memoryUsage = function_exists('memory_get_peak_usage') ? round(memory_get_peak_usage() / (1024*1024), 2) : 0;
		$nick = $member_id['name'] ?: '__GUEST__';
		$param['statistics'] = self::$dleDb->safesql($param['statistics']);

		$check = self::$dleDb->super_query("SELECT idFilter FROM " . PREFIX . "_dle_filter_statistics WHERE DATE(dateFilter)=DATE(NOW()) AND ip='{$_IP}' AND statistics='{$param['statistics']}'");
		if (!$check['idFilter']) {
			$allTime = $microTimer->get();
			self::$dleDb->query("INSERT INTO " . PREFIX . "_dle_filter_statistics (dateFilter, foundNews, ip, queryNumber, nick, memoryUsage, mysqlTime, templateTime, statistics, sqlQuery, allTime) VALUES ('{$dateFilter}', '{$param['foundNews']}', '{$_IP}', '{$param['queryNumber']}', '{$nick}', '{$memoryUsage}', '{$param['mysqlTime']}', '{$param['templateTime']}', '{$param['statistics']}', '{$param['sqlQuery']}','{$allTime}')");
		}
		
		if (self::$modConfig['clear_statistics'] > 0) {
            $statDay = intval(self::$modConfig['clear_statistics']);
            $andDelete = '';
            $statDay += 1;
            if ($statDay > 1) {
                $statDayEnd = $statDay - 1;
                $andDelete = "AND DATE(dateFilter) < DATE_SUB(NOW(), INTERVAL {$statDayEnd} DAY)";
            }
            self::$dleDb->query("DELETE FROM " . PREFIX . "_dle_filter_statistics WHERE DATE(dateFilter) > DATE_SUB(NOW(), INTERVAL {$statDay} DAY) {$andDelete}");
        }
	}
	
	/**
     * Правильный поиск данных в дополнительных полях [ Определенно костыль ]
     *
	 * @param	string    $xf
	 * @param	string	  $value
	 * @return	string
     */
	static function typeXfield($xf, $value)
	{
		$temp = array_filter(self::$dleXfields, function($item) use($xf) {
			return $item[0] == $xf;
		});
		
		$temp = array_values($temp);
		
		if ($temp[0][3] != 'select' && $temp[0][3] != 'image' && $temp[0][3] != 'file' && $temp[0][3] != 'htmljs' && $temp[0][8] == 0 && $temp[0][6] == 0) {
			$value = str_replace('\\', '\\\\\\\\\\', self::$dleDb->safesql($value));
		} else {
			if ($temp[0][3] == 'htmljs') {
				$value = self::$dleDb->safesql($value);
			} else {
				$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
				$value = trim(htmlspecialchars(strip_tags(stripslashes($value)), ENT_QUOTES, 'UTF-8'));
				$value = self::$dleDb->safesql($value);
			}
		}
		
		return $value;
	}
	
	/**
     * Тип числа
     *
	 * @param	mixed	$number
	 * @return	int|float
     */
	static function typeNumber($number)
	{
        $number = preg_replace('/\s+/', '', str_replace(',', '.', $number));
        $number = is_float($number) ? floatval($number) : intval($number);
		return $number;
	}

    /**
     * Тип числа
     *
     * @param	int	$count
     */
	static function redirect($count)
    {
        $endPages = ceil($count / self::$modConfig['news_number']);
        if (self::$pageFilter > $endPages) {
            $filterID = str_replace([' ', '%20'], '+', rawurldecode(self::$urlFilter . '/'));
            if ($endPages > 1) {
                $filterID .= 'page/' . $endPages;
            }
            @header("HTTP/1.0 301 Moved Permanently");
            @header("Location: {$filterID}");
            die("Redirect");
        }
    }

    /**
     * Навигация DLE 14.>0
     *
     * @return string
     */
    static function navigation()
    {
        global $tpl;

        if (($tpl->result['content'] && isset($tpl->result['navigation']) && $tpl->result['navigation'])) {
            $tpl->set('[navigation]', '');
            $tpl->set('[/navigation]', '');
            $tpl->set_block("'\\[not-navigation\\](.*?)\\[/not-navigation\\]'si", '');

            if (stripos($tpl->copy_template, '{navigation}') !== false) {
                $tpl->result['content'] = str_replace('{newsnavigation}', '', $tpl->result['content']);
                $tpl->copy_template = str_replace('{newsnavigation}', '', $tpl->copy_template);
                if ($tpl->result['navigation'] && stripos($tpl->copy_template, '{content}') !== false) {
                    $tpl->set('{navigation}', $tpl->result['navigation']);
                }
            } else {
                $tpl->result['content'] = str_replace('{newsnavigation}', $tpl->result['navigation'], $tpl->result['content']);
            }
        } else {
            $tpl->set('{navigation}', '');
            $tpl->set('[not-navigation]', '');
            $tpl->set('[/not-navigation]', '');
            $tpl->set_block("'\\[navigation\\](.*?)\\[/navigation\\]'si", '');
        }

        return $tpl->result['content'];
    }

    /**
     * Подсчет новостей
     *
     * @return string
     */
    static function sqlCount()
    {
        return "SELECT COUNT(*) as count FROM " . PREFIX . "_post p LEFT JOIN " . PREFIX . "_post_extras e ON (p.id=e.news_id) LEFT JOIN " . PREFIX . "_dle_filter_news f ON (p.id=f.newsId) " . self::$innerTable . " WHERE approve=1" . self::$whereTable;
    }

    /**
     * Выборка новостей
     *
     * @return string
     */
    static function sqlSelect()
    {
        return "SELECT p.id, p.autor, p.date, p.short_story, CHAR_LENGTH(p.full_story) as full_story, p.xfields, p.title, p.category, p.alt_name, p.comm_num, p.allow_comm, p.fixed, p.tags, e.news_read, e.allow_rate, e.rating, e.vote_num, e.votes, e.view_edit, e.editdate, e.editor, e.reason FROM " . PREFIX . "_post p LEFT JOIN " . PREFIX . "_post_extras e ON (p.id=e.news_id) LEFT JOIN " . PREFIX . "_dle_filter_news f ON (p.id=f.newsId) " . self::$innerTable . " WHERE approve=1" . self::$whereTable;
    }

    /**
     * Meta Robots
     *
     * @return string
     */
    static function metaRobots()
    {
        $key = self::$pageFilter > 0 ? 'index_second' : 'index_filter';
        if (self::$modConfig[$key] == 'noindex') {
            return "\">\n<meta name=\"robots\" content=\"noindex,nofollow";
        } elseif (self::$modConfig[$key] == 'follow') {
            return "\">\n<meta name=\"robots\" content=\"noindex,follow";
        } else {
            return "\">\n<meta name=\"robots\" content=\"index,follow";
        }
    }

	private function __construct() {}
    private function __wakeup() {}
    private function __clone() {}
    private function __sleep() {}
}
