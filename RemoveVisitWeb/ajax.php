<?php

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);

$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/bitrix";
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");



use Bitrix\Main\Loader;
use Bitrix\Main\Type;

$resuest = $_POST;


class RemoveVisitBack
{
   public static function main($resuest)
   {
      if (\Bitrix\Main\Loader::IncludeModule("crm")) {

         $entityId = $resuest['arParams'];
         $user_i = $resuest['user'];
         $entityFields = [
           'UF_CRM_1693643868590' => 2243
         ];


         $bCheckRight = false;
         $entityObject = new \CCrmDeal( $bCheckRight );
         $isUpdateSuccess = $entityObject->Update(
         $entityId,
         $entityFields,
         $arOptions = [
             /**
              * ID пользователя, от лица которого выполняется действие
              * в том числе проверка прав
              * @var integer
              */
             'CURRENT_USER' => \CCrmSecurityHelper::GetCurrentUserID($user_i),
             /**
              * Флаг системного действия. В случае true у элемента не будут
              * занесены данные о пользователе который производит действие
              * и дата изменения элемента не изменится.
              * @var boolean
              */
             'IS_SYSTEM_ACTION' => false,
             /**
              * В случае true, битрикс создаст сообщение в ленту о изменении
              * @var boolean
              */
             'REGISTER_SONET_EVENT' => true,
             /**
              * Флаг обозначающий запрет на создании записи в timeline элемента
              * о создании.
              * @var char
              */
             //'DISABLE_TIMELINE_CREATION' => 'Y'
             //
             /**
              * Флаг для вызова системных событий.
              * При установке в false не будут срабатывать событие
              * @var boolean
              */
             'ENABLE_SYSTEM_EVENTS' => true,
         ]
     );
     
     $currentFields = ['$currentFields' => ''];
     $previousFields = ['UF_CRM_1693643868590' => 2243];

     $starter = new \Bitrix\Crm\Automation\Starter(CCrmOwnerType::Deal, $entityId);
     $starter->runOnUpdate($currentFields, $previousFields);

     $entityTypeId = \CCrmOwnerType::Deal;
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

     $event = 'Пользователь '.$user_i.' отменил сделку';

        $CCrmEvent = new CCrmEvent();
        $CCrmEvent->Add(
           array(
              'USER_ID' => $user_i,
              'ENTITY_TYPE'=> 'DEAL',
              'ENTITY_ID' => $entityId,
              'EVENT_ID' => 'INFO',
              'EVENT_TEXT_1' => $event,
           )
        );

     print_r(['$isUpdateSuccess' => $isUpdateSuccess]);

     if($isUpdateSuccess){
         return true;
     }
     else{
         return NULL;
     }

     }

   // self::writeLog($resuest);
   }
   public static function writeLog($data) {
      $logFile = '/mnt/data/bitrix/local/php_interface/classes/RemoveVisitWeb/log.txt';
      $formattedData = var_export($data, true);
      file_put_contents($logFile, '<?php $array = ' . $formattedData . ';', FILE_APPEND);
   }
}

RemoveVisitBack::main($resuest);

//UF_CRM_1693643868590

