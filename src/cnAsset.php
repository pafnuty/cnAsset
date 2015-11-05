<?php
namespace Codenails\Tools;
/*
 * cnAsset
 *
 * Автозагрузка js и css скриптов из указанной папки в шаблон сайта,
 * через стандартные функции D7 битрикса addCss и addJs или standalone использовании
 * Сделано для облегчения работы верстальщика и программиста.
 * @version 2.4.0
 * @date 05.11.2015
 * @author Павел Белоусов
 * @link https://github.com/pafnuty/cnAsset
 * @license MIT
 * ---------------------------------------
 * Использование:
 * Положить скрипт в папку /local/codenails/tools/
 * Подключить в init.php:
 * require_once ($_SERVER['DOCUMENT_ROOT'] . '/local/codenails/tools/cnAsset.php');
 * В нужном месте header.php шаблона сайта пишем:
 *	\Codenails\Tools\cnAsset::add(
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
 * <?\Codenails\Tools\cnAsset::add(array('/local/css/'))?>
 */

class cnAsset {

	private function __construct() {}

	private static function isD7() {
		return class_exists('\Bitrix\Main\Page\Asset');
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

		// Добавляем скрипты и стили
		self::addAssets($folders, $excludes);
	}

	/**
	 * @param string $filePath
	 */
	public static function addFile($filePath) {
		$file        = basename($filePath);
		$folder      = $_SERVER['DOCUMENT_ROOT'] . str_replace($file, '', $filePath);
		$localFolder = str_replace($_SERVER['DOCUMENT_ROOT'], '', $folder);
		
		self::processFile($folder, $localFolder, $file);
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
	 */
	public static function addAssets($arPath, $excludes) {
		foreach ($arPath as $folder) {
			// Сканируем папку
			$f = scandir($folder);
			// Получаем относительный путь
			$localFolder = str_replace($_SERVER['DOCUMENT_ROOT'], '', $folder);
			// Пробегаем по массиву файлов
			foreach ($f as $file) {
				self::processFile($folder, $localFolder, $file, $excludes);
			}
		}
	}

	/**
	 * @param string $folder
	 * @param string $localFolder
	 * @param string $file
	 * @param array  $excludes
	 */
	public static function processFile($folder, $localFolder, $file, $excludes = array()) {
		// Берём только те файлы, у которых нет исключающего префикса
		if (!self::strposArr($file, $excludes)) {

			// Берём только css и js файлы
			if (preg_match("/(.*?)\\.(css|js)$/im", $file, $matches)) {
				// Для localhost добавляем параметр т.к. файлы кешируются браузером
				$v = (!self::isD7()) ? fileatime($folder . $file) : 1;
				switch ($matches[2]) {
					case 'css':
						// добавляем css-файл
						self::addCss($localFolder . $matches[0], $v);
						break;

					case 'js':
						// добавляем js-файл
						self::addJs($localFolder . $matches[0], $v);
						break;
				}
			}
		}
	}

	/**
	 * @param string $file
	 * @param string $v
	 */

	public static function addCss($file, $v = '1') {
		if (self::isD7()) {
			// Добавляем css-файл средствами D7 bitrix
			\Bitrix\Main\Page\Asset::getInstance()->addCss($file);
		} else {
			// Добавляем css-файл при чистой вёрстке
			echo '<link rel="stylesheet" href="' . $file . '?v=' . $v . '" />';
			echo "\n\t\t"; // Это для удобства восприятия исх.кода
		}

	}

	/**
	 * @param string $file
	 * @param string $v
	 */
	public static function addJs($file, $v = '1') {
		if (self::isD7()) {
			// Добавляем js-файл средствами D7 bitrix
			\Bitrix\Main\Page\Asset::getInstance()->addJs($file);
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