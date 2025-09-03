<?php
/**
* Класс AJAX обработки админ панели
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

namespace LazyDev\Filter;

class Ajax
{
    /**
     * Определяем AJAX действие
     *
     * @param    string    $action
     *
     **/
    static function ajaxAction($action)
    {
        switch ($action) {
            case 'saveOptions':
                self::saveOptions();
                break;
			case 'clearStatistics':
				self::clearStatistics();
				break;
			case 'clearCache':
				self::clearCache();
				break;
            case 'findNews':
                self::findNews();
                break;
        }
    }

    /**
     * Сохраняем настройки
     *
     **/
    static function saveOptions()
    {
        $arrayConfig = Helper::unserializeJs($_POST['data']);
        $handler = fopen(ENGINE_DIR . '/lazydev/' . Helper::$modName . '/data/config.php', 'w');
        fwrite($handler, "<?php\n\n//DLE Filter by LazyDev\n\nreturn ");
        fwrite($handler, var_export($arrayConfig, true));
        fwrite($handler, ";\n");
        fclose($handler);
        echo Helper::json(['text' => Data::get(['admin', 'ajax', 'options_save'], 'lang')]);
    }
	
	/**
     * Очищаем статистику
     *
     **/
    static function clearStatistics()
    {
        global $db;
		
		$db->query("TRUNCATE " . PREFIX . "_dle_filter_statistics");
		echo Helper::json(['text' => Data::get(['admin', 'ajax', 'clear_statistics'], 'lang')]);
    }
	
	/**
     * Очищаем кэш
     *
     **/
    static function clearCache()
    {
		Cache::clear();
		echo Helper::json(['text' => Data::get(['admin', 'ajax', 'clear_cache'], 'lang')]);
    }

    /**
     * Поиск новостей
     *
     **/
    static function findNews()
    {
        global $db, $config;

        if (preg_match("/[\||\<|\>|\"|\!|\?|\$|\@|\/|\\\|\&\~\*\+]/", $_POST['query']) || !$_POST['query']) {
            exit;
        }

        $query = $db->safesql(htmlspecialchars(strip_tags(stripslashes(trim($_POST['query']))), ENT_QUOTES, $config['charset']));
        $db->query("SELECT id, title as name FROM " . PREFIX . "_post WHERE `title` LIKE '%{$query}%' AND approve ORDER BY date DESC LIMIT 15");

        $search = [];

        while ($row = $db->get_row()) {
            $row['name'] = str_replace("&quot;", '\"', $row['name']);
            $row['name'] = str_replace("&#039;", "'", $row['name']);
            $row['name'] = htmlspecialchars($row['name']);

            $search[] = ['value' => $row['id'], 'name' => $row['name']];
        }

        echo json_encode($search);
    }
}
