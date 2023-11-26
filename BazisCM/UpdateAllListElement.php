<?php
namespace BazisCM;

error_reporting(E_ERROR);
ini_set('display_errors', 1);

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);

$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/bitrix";
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

require_once ('Workspace/Tools/CurlManager.php');
require_once ('Workspace/Tools/Base.php');
require_once ('Workspace/Bitrix/Common.php');


class UpdateAllListElement {

    public function __construct($metod) {
        $apiKey = '02f532d7052b495dadb503a5c54d09f4';
        $this->CurlManager = new \BazisCM\Workspace\Tools\CurlManager($apiKey, $metod);
    
        $this->Base = new \BazisCM\Workspace\Tools\Base();
        $this->BitrixCommon = new \BazisCM\Workspace\Bitrix\Common();
      }

      public function GClients($p){
        $BitrixCommon = $this->Checks();
      }
      
      public function Checks($p)
      {

        if (\Bitrix\Main\Loader::IncludeModule("crm")) {

            $DealsInfo = \Bitrix\Crm\DealTable::GetList([
              'select' => ['ID','UF_CRM_1694018792723', '*', 'CATEGORY_ID', 'STAGE_ID'], 
            ]);

            $DealsInfoData = $DealsInfo->fetchAll();
            if ($DealsInfoData !== false) {
                return $DealsInfoData;
            }
            else{
                return NULL;
            }

        }

        return NULL;
      }

      public function Check($numer)
      {

        if (\Bitrix\Main\Loader::IncludeModule("crm")) {


            $DealsInfo = \Bitrix\Crm\DealTable::GetList([
              'select' => ['ID','UF_CRM_1694018792723', '*', 'CATEGORY_ID', 'STAGE_ID'], 
              'filter' => ['UF_CRM_1694018792723' => $numer], //$p['client']['data']['number']] //'123321'
              'limit' => 1
            ]);

            $DealsInfoData = $DealsInfo->fetchAll();
            if ($DealsInfoData !== false) {
                return $DealsInfoData;
            }
            else{
                return NULL;
            }

        }

        return NULL;
      }
            
      // поиск документа/элемента списка в битрикс по номеру договора
      public function GetIblokList(){

        $arFilter = [
            "IBLOCK_ID" => 5,
            "ACTIVE" => "Y",
        ];
        $arSort = [
            "ID" => "ASC", 
        ];
        $arSelect = array(
            "ID",
            'PROPERTY_97', // связь с CRM -> сделка
            'PROPERTY_93', // номер договора
        );

        // проверяем фиьлтром есть ли элементы списка с таким ID ДОГОВОРА, если несколько то собираем файлы
        $rsElements = \CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
        $lr = 0;
        while ($arElement = $rsElements->Fetch()){
            $crmDeal = '';

            if(isset($arElement['PROPERTY_93_VALUE']) && !empty($arElement['PROPERTY_93_VALUE'])){
                $crmDeal = $this->Check($arElement['PROPERTY_93_VALUE']);
                if($crmDeal){
                    $lr = $lr + 1;
                    $arrIblockItem[] = $arElement;
                }
            }
        }

        $update = [];
        
        foreach($arrIblockItem as $item){
           $update = $this->GDeals($item, $update);
        }

        if(isset($update) && !empty($update)){
            foreach($update as $key_u => $val_u){
                $this->IblokItemUpdate(['idcrm' => $key_u, 'svaz_val' => $val_u]);
            }
        }

        print_r(['$update' => $update]);

      }

      public function GDeals($item, $update){
       // получаем ID сделки
       $crmDeal = $this->Check($item['PROPERTY_93_VALUE']);
       if($crmDeal){
           $CRM_DEAL_ID = 'D_'.$crmDeal['ID'];
           if((isset($CRM_DEAL_ID) && (isset($item['PROPERTY_97_VALUE'])))){
            if($CRM_DEAL_ID !== $item['PROPERTY_97_VALUE']){
                $update[$item['ID']] = $item['PROPERTY_97_VALUE'];
            }
           }
       }
       return $update;
      }

      public function IblokItemUpdate($update){
        $PROP = [];
        if ($update['CRM_DEAL_ID']) {
            $PROP['97'] = $update['svaz_val'];
        }
        if($PROP){
            $arFields['PROPERTY_VALUES'] = $PROP;
        }

        $element = new \CIBlockElement;
        $updateResult = $element->Update($update['idcrm'], $arFields);

        if ($updateResult !== false) {
            echo "Элемент успешно обновлен.";
            return true;
        } else {
            echo "Ошибка при обновлении элемента: " . $element->LAST_ERROR;
            return NULL;
        }
      }
      
}

$metod = '/api-orders-exchange-public/orders';

$chatManager = new UpdateAllListElement($metod);
$orders = $chatManager->GetIblokList();
