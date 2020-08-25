<?
//todo разработка на тестовой копии.
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

// D7
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;
use Bitrix\Main\Diag\Debug;


//Loader::includeModule("highloadblock");

global $DB, $APPLICATION, $MESS, $DBType;

class listImportFiles {
	private $arResulter = [];
	private $option = [];

	public function __construct($arg) {
		$this->option = [
			'path' => $arg["path"],
			'absolute_path' => $arg["absolute_path"],
		];

		$dir = new Directory($this->option['path']);
		if ($dir->isExists()) {
			$arDir = $dir->getChildren();
			self::getParentFiles($arDir);
			foreach($arDir as $dirItem){
				self::getFiles($dirItem);
				if ($dirItem->isDirectory()) {
					$this->arResulter['DIRECTORY'][] = $dirItem;
					self::getChildDir($dirItem);
				}
			}
		};
	}

	public function getParentFiles(&$arDir) {
		foreach($arDir as $keyDirItem => &$dirItem){
			if ($dirItem->isFile() && preg_match('/.*\.xml$/', $dirItem->getName())) {
				if ($this->option['absolute_path']) {
					$pattern = '/' . preg_replace('/\//', '\/', \Bitrix\Main\Application::getDocumentRoot() . '/upload/1c_catalog')  . '/';
					$this->arResulter['FILES'][] = preg_replace($pattern, '', $dirItem->getPath());
				} else {
					$this->arResulter['FILES'][] = $dirItem->getPath();
				}
				unset($arDir[$keyDirItem]);
			}
		}
	}

	public function getListFiles() {
		return $this->arResulter["FILES"];
	}

	protected function getFiles($dirItem) {
		if ($dirItem->isFile() && preg_match('/.*\.xml$/', $dirItem->getName())) {
			if ($this->option['absolute_path']) {
				$pattern = '/' . preg_replace('/\//', '\/', \Bitrix\Main\Application::getDocumentRoot() . '/upload/1c_catalog')  . '/';
				$this->arResulter['FILES'][] = preg_replace($pattern, '', $dirItem->getPath());
			} else {
				$this->arResulter['FILES'][] = $dirItem->getPath();
			}
		}
	}

	protected function getChildDir($dirItem) {
		$dirChild = new Directory($dirItem->getPath());
		if ($dirChild->isExists()) {
			$arDir = $dirChild->getChildren();
			foreach($arDir as $dirItem){
				self::getFiles($dirItem);
				if ($dirItem->isDirectory()) {
					$this->arResulter['DIRECTORY'][] = $dirItem;
					self::getChildDir($dirItem);
				}
			}
		}
	}

}

class importDebugAlexBS {
	static $arResult = [];

	public static function functionСycle($arFiles) {
		foreach ($arFiles as $file)
		{
			$data = static::functionOne($file);
			static::$arResult += array_merge(static::$arResult, $data);
		}
		return static::$arResult;
	}

	public static function functionOne($filePath) {
		$result = [];
		if (file_exists($filePath)) {
			$xml = simplexml_load_file($filePath);
			foreach ($xml->Каталог->Товары->Товар as $row) {
				$result[] = [
					'XML_ID' => $row->Ид->__toString(),
					'NAME' => $row->Наименование->__toString(),
				];
			}
		} else {
			exit('Не удалось открыть файл test.xml.');
		}
		return $result;
	}
}

function getListFiles() {
	$docRoot = Application::getDocumentRoot();
//	$pathDir = '/upload/1c_catalog/Exchange_2020-08-18-debug/000000001';
	$pathDir = '/upload/1c_catalog/Reports/Exchange_(1234)2020-08-24/000000001';
	$arg = [
		'path' => $docRoot . $pathDir,
		'absolute_path' => true,
	];
	$objClass = new listImportFiles($arg);
	$files = $objClass->getListFiles();
	//pr($files);
	$test = \Bitrix\Main\Web\Json::encode($files);
	echo $test;
}
/*
?>

<script>
	var end = false;
	var res = false;
	var i = 0;
	var files = ["\/Reports\/Exchange_(1234)2020-08-21\/000000001\/import.xml","\/Reports\/Exchange_(1234)2020-08-21\/000000001\/1\/offers.xml","\/Reports\/Exchange_(1234)2020-08-21\/000000001\/1\/import.xml","\/Reports\/Exchange_(1234)2020-08-21\/000000001\/1\/rests.xml","\/Reports\/Exchange_(1234)2020-08-21\/000000001\/1\/prices.xml","\/Reports\/Exchange_(1234)2020-08-21\/000000001\/2\/offers.xml","\/Reports\/Exchange_(1234)2020-08-21\/000000001\/2\/import.xml"];

	for (let i = 0; i < files.length; i++) {
		var res = false;
		res = query_1c('/bitrix/admin/1c_exchange.php?type=catalog&mode=import&filename=' + files[i]);

		setTimeout(function() {
			query_1c('/bitrix/admin/1c_exchange.php?type=catalog&mode=import&filename=' + files[i]);
			console.log( i + ": " + files[i] );
		}, 3000 * i);
	}
</script>
<?php
*/
getListFiles();

//$res = importDebugAlexBS::functionСycle($files);
//
//function unique_array_by_key($array, $key)
//{
//	$temp_array = array();
//	$i = 0;
//	$key_array = array();
//	foreach ($array as $val)
//	{
//		if (!in_array($val[$key], $key_array))
//		{
//			$key_array[$i] = $val[$key];
//			$temp_array[$i] = $val;
//		}
//		$i++;
//	}
//	return $temp_array;
//}
//
//pr(unique_array_by_key($res, "XML_ID"));


require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");