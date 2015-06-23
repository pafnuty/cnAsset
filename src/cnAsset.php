<?php
use \Bitrix\Main\Page\Asset;

/*
 * cnAsset
 *
 * Автозагрузка js и css скриптов из указанной папки в шаблон сайта,
 * через стандартные функции D7 битрикса addCss и addJs или standalone использовании
 * Сделано для облегчения работы верстальщика и программиста.
 * @version 2.2.0
 * @date 23.06.2015
 * @author Павел Белоусов <pb@infoexpert.ru>
 * ---------------------------------------
 * Использование:
 * Положить скрипт в папку /local/php_interface/
 * Подключить в init.php:
 * 	require_once ('cnAsset.php');
 * В нужном месте header.php шаблона сайта пишем:
 *	cnAsset::add(
 *		array(
 *			// Массив с папками, из которых будем тянуть скрипты и стили.
 *          // вложенные папки не сканируются
 *			'/local/js/', 
 *			'/local/css/'
 *		), 
 *		array(
 *			// Массив с префиксами файлов, исключаемых из автозагрузки.
 *			'main'
 *		)
 *	);
 * Можно ещё вот так:
 * <?cnAsset::add(array('/local/css/'))?>
 */

class cnAsset {

	private function __construct() {
	}

	/**
	 * @param array $folders
	 * @param array $excludes
	 */
	public static function add($folders, $excludes = array('-', '_')) {
		// Дополняем переданные префиксы префиксами по умолчанию
		$excludes = array_merge($excludes, array('-', '_'));

		// Получаем реальные пути к папкам
		$folders = self::getRealPath($folders);

		// Определяемся с окружением (bitrix или просто вёрстка)
		$isD7 = class_exists('\Bitrix\Main\Page\Asset');

		// Добавляем скрипты и стили
		self::addAssets($folders, $excludes, $isD7);
	}

	/**
	 * @param array $array
	 *
	 * @return mixed
	 */
	protected static function getRealPath($array) {

		foreach ($array as $k => $path) {
			$array[$k] = $_SERVER['DOCUMENT_ROOT'] . $path;
		}

		return $array;

	}

	/**
	 * @param array $arPath
	 * @param array $excludes
	 * @param bool  $isD7
	 */
	public static function addAssets($arPath, $excludes, $isD7) {
		foreach ($arPath as $folder) {
			// Сканируем папку
			$f           = scandir($folder);
			// Получаем относительный путь
			$localFolder = str_replace($_SERVER['DOCUMENT_ROOT'], '', $folder);
			// Пробегаем по массиву файлов
			foreach ($f as $file) {
				// Берём только те файлы, у которых нет исключающего префикса
				if (!self::strposArr($file, $excludes)) {
					// Берём только css и js файлы
					if (preg_match("/(.*?)\\.(css|js)$/im", $file, $matches)) {
						// Для localhost добавляем параметр т.к. файлы кешируются браузером
						$v = (!$isD7) ? fileatime($folder . $file) : 1 ;
						switch ($matches[2]) {
							case 'css':
								// добавляем css-файл
								self::addCss($localFolder . $matches[0], $isD7, $v);
								break;

							case 'js':
								// добавляем js-файл
								self::addJs($localFolder . $matches[0], $isD7, $v);
								break;
						}
					}
				}
			}
		}
	}

	/**
	 * @param string $file
	 * @param bool   $isD7
	 * @param string $v
	 */
	
	public static function addCss($file, $isD7, $v = '1') {
		if ($isD7) {
			// Добавляем css-файл средствами D7 bitrix
			Asset::getInstance()->addCss($file);
		} else {
			// Добавляем css-файл при чистой вёрстке
			echo '<link rel="stylesheet" href="' . $file . '?v=' . $v . '" />';
			echo "\n\t\t"; // Это для удобства восприятия исх.кода
		}

	}

	/**
	 * @param string $file
	 * @param bool   $isD7
	 * @param string $v
	 */
	public static function addJs($file, $isD7, $v = '1') {
		if ($isD7) {
			// Добавляем js-файл средствами D7 bitrix
			Asset::getInstance()->addJs($file);
		} else {
			// Добавляем css-файл при чистой вёрстке
			echo '<script src="' . $file . '?v=' . $v . '"></script>';
			echo "\n\t\t"; // Это для удобства восприятия исх.кода
		}

	}

	/**
	 * Небольшое улучшение strpos()
	 *
	 * @param  string $str - строка в кторой будем искать
	 * @param  array  $arr - массив, совпадения с которым ищем.
	 *
	 * @return bool
	 * @author Павел Белоусов <pb@info-expert.ru>
	 */
	protected static function strposArr($str, $arr) {
		foreach ($arr as $v) {
			if (($pos = strpos($str, $v)) !== false && $pos == '0') {
				return true;
			}
		}

		return false;
	}

}