<?php
/**
* Вспомогательный класс шаблонизатора
*
* @link https://lazydev.pro/
* @author LazyDev <email@lazydev.pro>
**/

namespace LazyDev\Filter;

use dle_template;

class View extends dle_template
{
    /**
    * Конструктор
    *
    * @param    string    $tpl
    * @param    string    $dleModule
    * @param    string    $template
    **/
	public function __construct($tpl = '', $dleModule = '', $template = '')
	{
		parent::__construct();
		$this->dir = TEMPLATE_DIR . '/lazydev/dle_filter';
		if ($tpl) {
            if (file_exists($this->dir . '/' . $template . '.tpl')) {
                $tpl = $template;
            } elseif (file_exists($this->dir . '/' . $tpl . '_' . $dleModule . '.tpl')) {
                $tpl = $tpl . '_' . $dleModule;
            }

			$this->load_template($tpl . '.tpl');
		}
	}
}
