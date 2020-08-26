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