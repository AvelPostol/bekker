<?php

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);

$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/bitrix";
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");



use Bitrix\Main\Loader;
use Bitrix\Main\Type;

$resuest = $_POST;

\Bitrix\Main\Loader::IncludeModule("crm");

$ParamForSearch = [
    'filter' => [
        'ID' => '192361'
    ],
    'select' => [
        '*', 'UF_CRM_1693643868590', 'UF_*'
    ]
];

$arDeals=\Bitrix\Crm\DealTable::getList($ParamForSearch)->fetchAll();

foreach($arDeals as $deal){
    $deals=$deal;
}


$currentFields = ['UF_CRM_1693643868590' => '2243'];
$previousFields = $deals;

$newdeals = $deals;
$newdeals['UF_CRM_1693643868590'] = '2243';

$entityId = '192361';

$entityTypeId = \CCrmOwnerType::Deal;
$entityId = 192361;
$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);

$item = $factory->getItem($entityId);
if (!$item)
{
    echo 'item not found';
    return;
}

$item->setFromCompatibleData([
    'UF_CRM_1693643868590' => 2243
]);
$operation = $factory->getUpdateOperation($item);
$operation->disableAllChecks();
$result = $operation->launch();