<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

use Bitrix\Main;

Main\Loader::includeModule('iblock');

$updateData = [
	'IBLOCK_ID' => 38,
	'ID' => 15199,
	'SORT' => 4444,
	'USER_TYPE' => 'direcotory',
];

foreach (GetModuleEvents("iblock", "OnBeforeIBlockPropertyUpdate", true) as $event)
{
	ExecuteModuleEventEx($event, array(&$updateData));

	if ($updateData['SORT'] !== 4444)
	{
		pr($updateData,1);
		pr($event,1);
		break;
	}
}

$reflectionClass = new ReflectionClass('DefaTools_IBProp_OptionsGrid');
echo $reflectionClass->getFileName();