<?php
/**
 * Условия
 *
 * @link https://lazydev.pro/
 * @author LazyDev <email@lazydev.pro>
 **/

namespace LazyDev\Filter;
setlocale(LC_NUMERIC, 'C');

class Conditions
{
    private static $stringLength = [];
    private static $instance = null;
    private static $row;

	/**
     * Одиночка
     *
     * @return   Conditions
     **/
    static function construct()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

	/**
     * Вызов обработки условий
     *
     * @param    string    	$template
	 * @param    array|bool	$row
     *
     * @return   string
     **/
    static function realize($template, $row = false)
    {
        self::$row = $row;
        if (strpos($template, '[filter ') !== false) {
            $template = preg_replace_callback("#\\[filter (.+?)\\](.*?)\\[/filter\\]#umis",
                [self::$instance, 'conditions'],
            $template);
        }

        return $template;
    }

	/**
     * Удаление оставшихся тегов
     *
     * @param    string    	$a
     * @return   string
     **/
	static function clean($a)
	{	
		if (strpos($a, '{filter') !== false) {
			$a = preg_replace('#{filter(.+?)}#is', '', $a);
		}
		
		if (strpos($a, '[filter') !== false) {
			$a = preg_replace('#\[filter-(.+?)\](.*?)\[\/filter-\\1\]#is', '', $a);
			$a = preg_replace('#\[filter(.+?)\](.*?)\[\/filter\\1\]#is', '', $a);
		}
		
		return $a;
	}

	/**
     * Удаление оставшихся тегов
     *
     * @param    array	$a
     * @return   array
     **/
	static function cleanArray($a)
	{
		foreach ($a as $k => $v) {
			if (strpos($a[$k], '[/') !== false) {
				$a[$k] = preg_replace('#\[(.+?)\](.*?)\[\/\\1\]#is', '', $a[$k]);
			}
			
			if (strpos($a[$k], '{') !== false) {
				$a[$k] = preg_replace('#{(.+?)}#is', '', $a[$k]);
			}
		}
		
		return $a;
	}

	/**
     * Проход по условиям
     *
     * @param    array	$pregArray
     * @return   string
     **/
    static function conditions($pregArray)
    {
        $globalKey = '';
        if (strpos($pregArray[0], '[filter ') === false) {
            if (preg_match_all("#\[filter([0-9]+)#is", $pregArray[0], $foundKey)) {
                $globalKey = $foundKey[1][0];
            }
        }

        if (strpos($pregArray[2], '[elif' . $globalKey) !== false) {
            $pregArray[2] = preg_replace("#\\[elif{$globalKey} (.+?)\\](.+?)\\[/elif{$globalKey}\\]#umis", '', $pregArray[2]);
        }

        if (strpos($pregArray[2], '[else') !== false) {
            if (strpos($pregArray[2], '[else' . $globalKey . ']')) {
                $pregArray[2] = $pregArray[2] . '[/filter' . $globalKey . ']';
            }

            $pregArray[2] = preg_replace("#\\[else{$globalKey}\\](.+?)\\[/filter{$globalKey}\\]#umis", '', $pregArray[2]);
        }

        $checkIf = self::conditionsRealize($pregArray[1], $pregArray[2]);

        if ($checkIf !== false) {
            return $checkIf;
        }

        if (strpos($pregArray[0], '[elif' . $globalKey) !== false) {
            preg_match_all("#\\[elif{$globalKey} (.+?)\\](.+?)\\[/elif{$globalKey}\\]#umis", $pregArray[0] , $pregElif);
            for ($i = 0; $i < count($pregElif); $i++) {
                $checkElif = self::conditionsRealize($pregElif[1][$i], $pregElif[2][$i]);
                if ($checkElif !== false) {
                    return $checkElif;
                }
            }
        }

        if (strpos($pregArray[0], '[else' . $globalKey) !== false) {
            preg_match_all("#\\[else{$globalKey}\\](.+?)\\[/filter{$globalKey}\\]#umis", $pregArray[0], $pregElse);
            $pregElse[1][0] = self::matchNesting($pregElse[1][0]);

            return $pregElse[1][0];
        }

        return '';
    }

	/**
     * Проход по &&, ||
     *
     * @param    string		$condition
	 * @param    string		$return
     * @return   string|bool
     **/
    static function conditionsRealize($condition, $return)
    {
        $countCheck = 0;

        if (substr_count($condition, '||')) {
            $conditionOrArray = explode(' || ', $condition);
            for ($i = 0; $i < count($conditionOrArray); $i++) {

                if (substr_count($conditionOrArray[$i], '&&')) {
                    $conditionAndArray = explode(' && ', $conditionOrArray[$i]);

                    for ($j = 0; $j < $conditionAndArray; $j++) {
                        if (self::conditionsMatching($conditionAndArray[$j])) {
                            $countCheck++;
                        }
                    }

                    if ($countCheck == count($conditionAndArray)) {
                        $return = self::matchNesting($return);
                        return $return;
                    } else {
                        $countCheck = 0;
                    }
                } elseif (self::conditionsMatching($conditionOrArray[$i])) {
                    $return = self::matchNesting($return);
                    return $return;
                }
            }
        } elseif (substr_count($condition, '&&')) {
            $conditionAndArray = explode(' && ', $condition);
            for ($i = 0; $i < count($conditionAndArray); $i++) {
                if (self::conditionsMatching($conditionAndArray[$i])) {
                    $countCheck++;
                } else {
                    return false;
                }
            }
            if ($countCheck == count($conditionAndArray)) {
                $return = self::matchNesting($return);
                return $return;
            }
        } elseif (self::conditionsMatching($condition)) {
            $return = self::matchNesting($return);
            return $return;
        }

        return false;
    }

	/**
     * Работа с проверкой условия
     *
     * @param    string		$condition
     * @return   bool
     **/
    static function conditionsMatching($condition)
    {
        preg_match("#(.+?)(>=|<=|<|>|!==|!=|==|=|!~|~)(.+?)$#uis", $condition, $conditionMatching);
		
		self::$stringLength = [];

        if (self::$row[$conditionMatching[1]]) {
            $conditionMatching[1] = self::$row[$conditionMatching[1]];
        } else {
			return false;
		}
		
        if (self::$row[$conditionMatching[3]]) {
            $conditionMatching[1] = self::$row[$conditionMatching[3]];
        }

		$conditionMatching[1] = self::returnType($conditionMatching[1]);
		$conditionMatching[3] = self::returnType($conditionMatching[3]);

        switch ($conditionMatching[2]) {
            case '>':
                $conditionMatching[1] = self::$stringLength[0] ?: $conditionMatching[1];
                $conditionMatching[3] = self::$stringLength[1] ?: $conditionMatching[3];
                return $conditionMatching[1] > $conditionMatching[3];
                break;
            case '>=':
                $conditionMatching[1] = self::$stringLength[0] ?: $conditionMatching[1];
                $conditionMatching[3] = self::$stringLength[1] ?: $conditionMatching[3];
                return $conditionMatching[1] >= $conditionMatching[3];
                break;
            case '<':
                $conditionMatching[1] = self::$stringLength[0] ?: $conditionMatching[1];
                $conditionMatching[3] = self::$stringLength[1] ?: $conditionMatching[3];
                return $conditionMatching[1] < $conditionMatching[3];
                break;
            case '<=':
                $conditionMatching[1] = self::$stringLength[0] ?: $conditionMatching[1];
                $conditionMatching[3] = self::$stringLength[1] ?: $conditionMatching[3];
                return $conditionMatching[1] <= $conditionMatching[3];
                break;
            case '==':
            case '!==':
                $conditionMatching[1] = explode(',', $conditionMatching[1]);
                $conditionMatching[3] = explode(',', $conditionMatching[3]);
                $countMatch = 0;
                foreach ($conditionMatching[3] as $valMatch) {
                    if (in_array($valMatch, $conditionMatching[1])) {
                        $countMatch++;
                    }
                }
                if ($conditionMatching[2] == '==') {
                    return $countMatch == count($conditionMatching[3]);
                }
                return $countMatch == count($conditionMatching[3]) ? false : true;
                break;
            case '=':
                return $conditionMatching[1] == $conditionMatching[3];
                break;
            case '!=':
                return $conditionMatching[1] != $conditionMatching[3];
                break;
            case '~':
                return dle_strpos($conditionMatching[1], $conditionMatching[3], 'UTF-8') === false ? false : true;
                break;
            case '!~':
                return dle_strpos($conditionMatching[1], $conditionMatching[3], 'UTF-8') === false ? true : false;
                break;
        }

        return false;
    }

	/**
     * Тип данных
     *
     * @param    mixed	$var
     * @return   mixed
     **/
    static function returnType($var)
    {
        if (is_numeric($var)) {
            if (is_int($var)) {
                $var = intval($var);
            } else {
                $var = floatval($var);
            }
        } elseif (is_string($var)) {
            $var = trim($var);
            self::$stringLength[] = mb_strlen($var, 'UTF-8');
        }

        return $var;
    }


	/**
     * Вложенные условия
     *
     * @param    string		$condition
     * @return   string
     **/
    static function matchNesting($condition)
    {
        if (preg_match_all("#\[filter([0-9]+)#is", $condition, $nestingIf)) {
            foreach ($nestingIf[1] as $key) {
                $condition = preg_replace_callback("#\\[filter{$key} (.+?)\\](.*?)\\[/filter{$key}\\]#umis",
                    [self::$instance, 'conditions'],
                $condition);
            }
        }

        return $condition;
    }

    private function __construct() {}
    private function __wakeup() {}
    private function __clone() {}
    private function __sleep() {}
}