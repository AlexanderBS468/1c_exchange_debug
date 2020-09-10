<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use Bitrix\Main;
use Bitrix\Iblock;

Main\Loader::includeModule('iblock');

$connection = Main\Application::getConnection();

$query = Iblock\PropertyTable::getList([
	'filter' => [
//		'ID' => 15199,
		'IBLOCK_ID' => [
			37,
			38
		],
		'USER_TYPE' => 'directory'
	]
]);
$updateResult = [];

while ($row = $query->fetch())
{
	$updateResult[$row["ID"]] = updateSortProp($row);
	continue;
	//todo doesn't work
	/*
	if (empty($row['USER_TYPE_SETTINGS_LIST']['TABLE_NAME'])) { continue; }

	$tableName = $row['USER_TYPE_SETTINGS_LIST']['TABLE_NAME'];

	if (!$connection->isTableExists($tableName))
	{
		$updateProperty = new CIBlockProperty();
		$updateResult[$row['ID']] = $updateProperty->Update($row['ID'], [
			'USER_TYPE' => $row['USER_TYPE'],
			'USER_TYPE_SETTINGS' =>
				[ 'TABLE_NAME' => '' ]
				+ $row['USER_TYPE_SETTINGS_LIST']
		]);
	}
	*/
}

function updateSortProp($row) {
//	pr($row["SORT"]);
	$sort = $row["SORT"] + 1;
//	pr($sort);
	$updateProperty = new CIBlockProperty();
	$result = $updateProperty->Update($row['ID'], [
		'SORT' => $sort,
		'IBLOCK_ID' => $row["IBLOCK_ID"],
		'USER_TYPE' => $row["USER_TYPE"],
	]);
//	pr([
//		'id' => $row["ID"],
//		'IBLockID' => $row["IBLOCK_ID"],
//		'sort' => $row["SORT"],
//		'sort2' => $sort,
//	]);
	return $result;
}

pr($updateResult);
