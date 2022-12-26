<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

// D7
use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Diag\Debug;

Loader::includeModule("highloadblock");

global $DB, $APPLICATION, $MESS, $DBType;

$arHlBlocks = getHlTables();
pr('drop Tables HL Blocks');

foreach ($arHlBlocks as $elem) {
	$strSql = 'DROP TABLE ' . $elem["TABLE_NAME"];
	$result = $DB->Query($strSql);
	$res[$elem["ID"]] = $result->result;
	if ($result->result) {
		$hlblock = HighloadBlockTable::getList(array(
			"filter" => array(
				"=TABLE_NAME" => $elem["TABLE_NAME"],
			)))->fetch();
		if (isset($hlblock["ID"]) && !empty($hlblock["ID"])) {
			HighloadBlockTable::delete($hlblock["ID"]);
		}
	}
}
pr('result');
pr($res);
die;

function getHlTables()
{
	$arResult = [];

	$rsData = \Bitrix\Highloadblock\HighloadBlockTable::getList(
		array()
	);

	while ($hldata = $rsData->fetch()) {
		$arResult[] = $hldata;
	}

	return $arResult;
}

//debug


//function truncateHlBlockEntity($hlblocks) {
//	global $DB;
//	$strSql = 'TRUNCATE TABLE b_hlblock_entity';
//	$result = $DB->Query($strSql);
//	return $result->result;
//}

//$hlblocks = HlBlockEntityList('all');
//$resultDel = delHlBlockEntity($hlblocks);
//pr($resultDel);
//die;
////b_hlblock_entity
//function HlBlockEntityList($arg) {
//	$hlblock = [];
//	if ($arg === 'all') {
//		$hlblock = HighloadBlockTable::getList()->fetchAll();
//	}
//	return $hlblock;
//}
//
//function delHlBlockEntity($hlblocks) {
//	$result = [];
//	foreach ($hlblocks as $item) {
//		$result[$item["ID"]] = HighloadBlockTable::delete($item["ID"]);
//	}
//	return $result;
//}