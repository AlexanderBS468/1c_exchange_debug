<?
//todo разработка на тестовой копии.
//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
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
use Bitrix\Highloadblock\HighloadBlockTable;

Loader::includeModule("highloadblock");

global $DB, $APPLICATION, $MESS, $DBType;

if ($_REQUEST["DEL_HL"] === 'Y' && false) {
	$hlblocks = HlBlockEntityList('all');
	pr($hlblocks);
	$resultDel = delHlBlockEntity($hlblocks);
	pr($resultDel);
}

if ($_REQUEST["TEST_PROPS"] === 'Y') {
	getHlBlocks();
}

function getHlBlocks() {
	$hlblocks = HlBlockEntityList('all');
	pr($hlblocks);

	$hlblock = HighloadBlockTable::getList(array(
		"filter" => array(
			"TABLE_NAME" => 'b_%\_',
		)))->fetchAll();
	pr($hlblock);

	$arProps = \Bitrix\Iblock\PropertyTable::getList(array(
		'select' => array('*'),
		'filter' => array(
			'IBLOCK_ID' => [20, 21],
			'USER_TYPE' => 'directory',
		)
	))->fetchAll();
	pr($arProps);

	foreach ($arProps as $prop) {
		if ($prop["USER_TYPE_SETTINGS_LIST"]["TABLE_NAME"]) {
			if (preg_match('/b_+.*_$/', $prop["USER_TYPE_SETTINGS_LIST"]["TABLE_NAME"])
				|| preg_match('/b_+.*__$/', $prop["USER_TYPE_SETTINGS_LIST"]["TABLE_NAME"]) ) {
				pr($prop);
			}
		}
	}
}

if ($_REQUEST["GO_TABLE"] === 'Y') {
	$arProps = \Bitrix\Iblock\PropertyTable::getList(array(
		'select' => array('*'),
		'filter' => array(
//			'ID' => 15152,
			'IBLOCK_ID' => [37, 38, 20, 21],
			'USER_TYPE' => 'directory',
//			'!USER_TYPE_SETTINGS' => false,
		)
	))->fetchAll();
	checkUserTypeSettingsTableName($arProps);
	checkHlblock($arProps);
}

function HlBlockEntityList($arg) {
	$hlblock = [];
	if ($arg === 'all') {
		$hlblock = HighloadBlockTable::getList()->fetchAll();
	}
	return $hlblock;
}

function delHlBlockEntity($hlblocks) {
	$result = [];
	foreach ($hlblocks as $item) {
		$result[$item["ID"]] = HighloadBlockTable::delete($item["ID"]);
	}
	return $result;
}

if ($_REQUEST["DONT_GO_THIS_FUNCTION"] === 'Y' && false) {
//	$arHlBlocks = getHlTables();
//	pr('dropTablesHL Blocks');
	//
	//$result = [];
	//foreach ($arHlBlocks as $elem) {
	//	$hlblock = HighloadBlockTable::getList(array(
	//		"filter" => array(
	//			"=TABLE_NAME" => $elem["TABLE_NAME"],
	//		)))->fetch();
	//	if (isset($hlblock["ID"]) && !empty($hlblock["ID"])) {
	//		$resHl = HighloadBlockTable::delete($hlblock["ID"]);
	//		$result[$hlblock["ID"]] = $resHl->isSuccess();
	//	}
	//}
	//pr($result);
	//foreach ($arHlBlocks as $elem) {
	//	$strSql = 'DROP TABLE ' . $elem["TABLE_NAME"];
	//	$result = $DB->Query($strSql);
	//	$res[$elem["ID"]] = $result->result;
	//	if ($result->result) {
	//		$hlblock = HighloadBlockTable::getList(array(
	//			"filter" => array(
	//				"=TABLE_NAME" => $elem["TABLE_NAME"],
	//			)))->fetch();
	//		if (isset($hlblock["ID"]) && !empty($hlblock["ID"])) {
	//			HighloadBlockTable::delete($hlblock["ID"]);
	//		}
	//	}
	//}
	//pr($res);
}

if (false) {
	$rsIblock = \Bitrix\Iblock\IblockTable::getList(array(
		'filter' => array('ID' => [
			'37',
			'38'
		]),
	))->fetchAll();
	foreach ($rsIblock as $elem) {
		$arIblock[] = $elem["ID"];
	}
	include 'files.php';
	$arProps = \Bitrix\Iblock\PropertyTable::getList(array(
		'select' => array('*'),
		'filter' => array(
			//		'ID' => 15152,
			'IBLOCK_ID' => $arIblock,
			'USER_TYPE' => 'directory',
			//		'!USER_TYPE_SETTINGS' => false,
		)
	))->fetchAll();

	if (false) {
		//pr(truncateHlBlockEntity()); die();
	}

	if (false) {
		getListFiles();
	}
	if (false) {
		// remove all
		unsetUserTypeSettings($arProps);
		die;
	}
	if (true) {
		//	$arResult = noTableName($arProps);
		checkHlblock($arProps);
	}
}

function truncateHlBlockEntity() {
	global $DB;
	$strSql = 'TRUNCATE TABLE b_hlblock_entity';
	$result = $DB->Query($strSql);
	return $result->result;
}

function getListFiles() {
	$docRoot = Application::getDocumentRoot();
	$pathDir = '/upload/1c_catalog/Exchange_2020-08-18-debug/000000001';
	$arg = [
		'path' => $docRoot . $pathDir,
		'absolute_path' => true,
	];
	$objClass = new listImportFiles($arg);
	$files = $objClass->getListFiles();
	pr($files);
	$test = \Bitrix\Main\Web\Json::encode($files);
	pr($test);
	die;
}
function checkHlblockEntity($highBlockName)
{
	$hlblock = Bitrix\Highloadblock\HighloadBlockTable::getList(array(
		"filter" => array(
			"=NAME" => $highBlockName,
		)))->fetch();

	if ($hlblock) {
		pr('ERROR Entitty');
		pr($hlblock);
//		$res = Bitrix\Highloadblock\HighloadBlockTable::delete($hlblock["ID"]);
//		pr($res);
		return true;
	}
}

function checkHlblock($arProperties)
{
	pr('count properties - ' . count($arProperties));
	$arAddHlblocks = [
		'update' => [],
		'add' => [],
		'trouble' => [],
	];
	foreach($arProperties as $arProperty) {
		$serializeField = $arProperty['111'] = unserialize($arProperty["USER_TYPE_SETTINGS"]);
		propertyCustom::checkNameCodeProperty($arProperty);
		$tableName = 'b_'.strtolower($arProperty["CODE"]);
		if (strlen($arProperty["USER_TYPE_SETTINGS_LIST"]["TABLE_NAME"]) <= 0)
		{
			$hlblock = Bitrix\Highloadblock\HighloadBlockTable::getList(array(
				"filter" => array(
					"=TABLE_NAME" => $tableName,
				)))->fetch();
			$arProperty["USER_TYPE_SETTINGS_LIST"]["TABLE_NAME_NEW"] = $tableName;
			if (!$hlblock)
			{
				$highBlockName = trim($arProperty["CODE"]);
				$highBlockName = preg_replace("/([^A-Za-z0-9]+)/", "", $highBlockName);
				if ($highBlockName == "")
				{
					pr('No name');
					return false;
				}

				$highBlockName = strtoupper(substr($highBlockName, 0, 1)).substr($highBlockName, 1);
				$data = array(
					'NAME' => $highBlockName,
					'TABLE_NAME' => $tableName,
				);

				if (checkHlblockEntity($highBlockName)) {
					$arAddHlblocks['trouble'][] = [
						'data' => $data,
						'Property' => $arProperty,
					];
				} else {
					$arAddHlblocks['add'][] = [
						'data' => $data,
						'Property' => $arProperty,
					];
				}
			} else {
				$arAddHlblocks['update'][] = [
					'Property' => $arProperty,
				];
			}

		}
	}
	pr([
		'trouble' => '-------------------',
		$arAddHlblocks['trouble']
	]);
	pr([
		'add' => '-------------------',
		'count' => count($arAddHlblocks['add']),
		$arAddHlblocks['add']
	]);
	pr([
		'update' => '-------------------',
		'count' => count($arAddHlblocks['update']),
		$arAddHlblocks['update']
	]);
}

abstract class propertyCustom {
	public static function isExistPropertyCode($code, array $filter = [])
	{
		$query = \Bitrix\Iblock\PropertyTable::getList([
			'filter' =>
				[ '=CODE' => $code ]
				+ $filter,
			'limit' => 1,
			'select' => [ 'ID' ],
		]);

		return (bool)$query->fetch();
	}
	public static function unifyPropertyCode($code, array $filter = [])
	{
		$suffix = 0;
		$code = preg_replace('/_\d+$/', '', $code);

		do
		{
			++$suffix;
			$newCode = $code . '_' . $suffix;
		}
		while (static::isExistPropertyCode($newCode, $filter));

		return $newCode;
	}

	public static function checkNameCodeProperty(&$fields)
	{
		if (!isset($fields['CODE'])) { return; }

		$newCode = preg_replace('/[^A-Za-z0-9]+$/', '', $fields['CODE']);
		$newCode = preg_replace('/([^A-Za-z0-9])[^A-Za-z0-9]+/', '$1', $newCode);

		if ($newCode !== $fields['CODE'])
		{
			if (isset($fields['USER_TYPE']) && $fields['USER_TYPE'] === 'directory')
			{
				$newCode = static::unifyPropertyCode($newCode, array_filter([
					'!=ID' => isset($fields['ID']) ? (int)$fields['ID'] : null,
					'=USER_TYPE' => 'directory',
				]));
			}
			else
			{
				$newCode = static::unifyPropertyCode($newCode, array_filter([
					'!=ID' => isset($fields['ID']) ? (int)$fields['ID'] : null,
					'=IBLOCK_ID' => $fields['IBLOCK_ID'],
				]));
			}

			$fields['CODE_OLD'] = $fields["CODE"];
			$fields['CODE'] = $newCode;
		}
	}
}

function checkUserTypeSettingsTableName($arProps)
{
	$keyTable = [
		"Y" => "OK_TABLE",
		"N" => "TROUBLE_PROP",
		"F" => "TROUBLE_PROP_TYPE",
	];
	pr(['arProperies' => $arProps]);
	foreach ($arProps as &$prop) {
		$serializeField = $prop['111'] = unserialize($prop["USER_TYPE_SETTINGS"]);
		if ($serializeField) {
//			$arUserSettings = unserialize($prop['USER_TYPE_SETTINGS']);
			if (!empty($serializeField['TABLE_NAME'])) {
				$arHlProp[$keyTable["Y"]][] = [
					'ID' => $prop["ID"],
					'TABLE_NAME' => $serializeField['TABLE_NAME'],
					'propID' => $prop["ID"],
					'propCODE' => $prop["CODE"],
				];
//				pr($arUserSettings['TABLE_NAME']);
			} else {
				$arHlProp[$keyTable["N"]][] = [
					'___________TROUBLE' => '_______________NONAME',
					'ID' => $prop["ID"],
					'TABLE_NAME' => '____________noTableName',
					'propID' => $prop["ID"],
					'propCODE' => $prop["CODE"],
				];
			}
		} else {
			$arHlProp[$keyTable["F"]][] = [
				'___________TROUBLE' => '_______________fuck type',
				'ID' => $prop["ID"],
				'propID' => $prop["ID"],
				'propCODE' => $prop["CODE"],
			];
		}
	}
	pr(['troubleTables' => $arHlProp]);
	$arHlBlocksTables = getHlTables();
	pr(['HL TABLES' => $arHlBlocksTables]);

	foreach ($arHlProp as $key => $propHl) {
		foreach ($arHlBlocksTables as $hlElement) {
			if ($propHl["TABLE_NAME"] === $hlElement["TABLE_NAME"]) {
				unset($arHlProp[$key]);
			}
		}
	}
	unset($propHl);

//	unsetUserTypeSettings($arHlProp[$keyTable["F"]]);
}

function getHlTables()
{
	$hlblock = HL\HighloadBlockTable::getList();

	$rsData = \Bitrix\Highloadblock\HighloadBlockTable::getList(
		array()
	);

	$arResult = [];

	while ($hldata = $rsData->fetch()){
//          echo 'Инфоблок не найден';
		$arResult[] = $hldata;
	}

	if (false) {
		findProperty($arResult);
	}

	return $arResult;
}

function unsetUserTypeSettings($arProps)
{
	if (empty($arProps)) { return pr('empty array HLblocks'); }
	global $DB;
	pr('unsetUserTypeSettings');

	$value = "'" . 'a:5:{s:4:"size";i:1;s:5:"width";i:0;s:5:"group";s:1:"N";s:8:"multiple";s:1:"N";s:10:"TABLE_NAME";s:0:"";}' . "'";
	foreach ($arProps as $elem) {
		$strSql = 'UPDATE b_iblock_property SET USER_TYPE_SETTINGS=' . $value . ' WHERE ID='.$elem["ID"];
		$result = $DB->Query($strSql);
		$res[$elem["ID"]] = $result->result;
	}
	pr($res);
}

function noTableName($arProps)
{
	pr('no_table_name');
	$arResultData = [];
	foreach ($arProps as $elem) {
		$dataUnserilize = unserialize($elem['USER_TYPE_SETTINGS']);
		if (empty($dataUnserilize['TABLE_NAME'])) {
			$arResultData[] = [
				'ID' => $elem["ID"],
				'NAME' => $elem["NAME"],
				'CODE' => $elem["CODE"],
				'XML_ID' => $elem["XML_ID"],
				'IBLOCK_ID' => $elem["IBLOCK_ID"],
				'USER_TYPE_SETTINGS' => unserialize($elem["USER_TYPE_SETTINGS"]),
			];
		}
	}
	pr($arResultData,1);
	pr(count($arResultData),1);
	return $arResultData;
}

// свойства инфоблока
function findProperty($arResult)
{
	foreach ($arResult as $hlElement) {
		$hlbl = $hlElement['ID'];
		$hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

		$entity = HL\HighloadBlockTable::compileEntity($hlblock);
		$entity_data_class = $entity->getDataClass();

		$rsData = $entity_data_class::getList(array(
			"select" => array("*"),
			"order" => array("ID" => "ASC"),
			"filter" => array("UF_XML_ID" => "f80ea697-5fcf-11e8-816a-00155d0c2002")
		));

		$arData = [];
		while($arData = $rsData->Fetch()){
			$arData[] = $arData;
		}
	}

	pr($arData);
}
?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>