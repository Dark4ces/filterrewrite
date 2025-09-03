<?php
/**
 * Конфиг и языковый файл
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

namespace LazyDev\Filter;

class Data
{
    static private $data = [];

    /**
     * Загрузить конфиг и языковый пакет
     */
    static function load()
    {
		$path = realpath(__DIR__ . '/..');
        self::$data['config'] = include_once $path . '/data/config.php';
        self::$data['lang']   = include_once $path . '/data/lang.lng';
		self::$data['fields'] = include_once $path . '/data/fields.php';
    }

    /**
     * Вернуть массив данных
     *
     * @param   string  $key
     * @return  array
     */
    static function receive($key)
    {
        return self::$data[$key];
    }

    /**
     * Получить данные с массива по ключу
     *
     * @param    string|array   $key
     * @param    string         $type
     * @return   mixed
     */
    static public function get($key, $type)
    {
        if (is_array($key) && !empty(self::$data[$type])) {
            return Helper::multiArray(self::$data[$type], $key, count($key));
        }
		
		if (!empty(self::$data[$type][$key])) {
			return self::$data[$type][$key];
		}
		
		return false;
    }

}