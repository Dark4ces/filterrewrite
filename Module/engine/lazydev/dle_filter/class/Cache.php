<?php
/**
 * Кэш фильтра
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

namespace LazyDev\Filter;

class Cache
{
    static $dir = ENGINE_DIR . '/lazydev/dle_filter/cache';

	/**
     * Берем кэш
     *
	 * @param    string    $id
	 * @return	 string|bool
     **/
	static function get($id)
	{
		global $member_id;

        $id = md5($id . $member_id['user_group']);
        $file = self::$dir . '/' . $id . '.json';
        if (file_exists($file)) {
            $response = file_get_contents($file);
            $fileDate = filemtime($file);
            $fileDate = time() - $fileDate;
            if ($fileDate > 10800) {
                $response = false;
                @unlink($file);
            }

            return $response;
        }

		return false;
	}
	
	/**
     * Сохраняем кэш
     *
	 * @param    string    $data
	 * @param    string    $id
     **/
	static function set($data, $id)
	{
		global $member_id;

        $id = md5($id . $member_id['user_group']);
        $file = self::$dir . '/' . $id . '.json';
        file_put_contents($file, $data, LOCK_EX);
        @chmod($file, 0666);
	}
	
	/**
     * Очищаем кэш
     *
     **/
	static function clear()
	{
		$cacheDir = opendir(self::$dir);
		while ($file = readdir($cacheDir)) {
			if ($file != '.htaccess' && !is_dir(self::$dir . '/' . $file)) {
				@unlink(self::$dir . '/' . $file);
			}
		}
	}
}

