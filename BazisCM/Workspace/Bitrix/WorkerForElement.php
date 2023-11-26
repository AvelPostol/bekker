<?php
namespace BazisCM\Workspace\Bitrix;

// $chatManager->getFiles(['files' => $OrderInfo['data']['files'], 'orderId' => $order['id'], 'data' => $OrderInfo['data'] ]);
  class WorkerForElement
  {
    public function __construct($metod) {
        $apiKey = '02f532d7052b495dadb503a5c54d09f4';
        $this->CurlManager = new \BazisCM\Workspace\Tools\CurlManagerTest($apiKey, $metod);
    }
    
    public function getFiles($p){

        // поиск элемента списка
        $IblokItems = $this->GetIblokList($order['data']['number']);

        // если найден соответствующий элемент списка
        if($IblokItems){

          //проверяем нужно ли обновлять элемент списка/документа
          $IblokItemCheckBazis = $this->IblokItemCheck($order,$IblokItems);
    
          if(isset($IblokItemCheckBazis['orderIsChecked'])){
            print_r(['попытка обновить документ']);

            $CheckUpdate = $this->BitrixCommon->IblokItemUpdate($IblokItemCheckBazis['orderIsChecked'], $IblokItems, $IblokItemCheckBazis['updFiles']);

            if(!$CheckUpdate){
              print_r(['Fatall: не получилось обновить документ']);
            }
            else{
              print_r(['получилось обновить документ']);
            }
          }
          else{
            print_r(['документ не нуждается в обновлении']);
          }
          
        }
        else{
    
          $CheckAdd = $this->BitrixCommon->AdaptForAddIblockElem($p, $files);
    
          $Add = $this->BitrixCommon->IblokItemAdd($CheckAdd['ord']['data'], $CheckAdd['files']);
          
          if(!$Add){
            print_r(['Fatall: не получилось сгенерировать документ']);
          }
          else{
            print_r(['создан новый элемент списка']);
          }
        }
    
    }

    public function getFileInBazis($p){
        $metod = '/api-orders-exchange-public/orders/' . $p['orderId'] . '/file';
        $chatManager = new WorkerForElement($metod);
        $obj = $this->CurlManager->Get(['filename' => $file['name'],'CheckContentName' => 'file', 'ContentPars' => false]);

        return $obj;
    }

    public function IblokItemCheck($bazisOrder,$IblokItems){

        $IblokItem_base = $IblokItems[0];
        $UpdFiles = [];

        // форматируем дату создания заказа
        $timestamp = strtotime($bazisOrder['data']['dateCreate']); // Преобразовываем строку в метку времени
        $formattedDateCreate = date("d.m.Y", $timestamp);
        $bazisOrder['data']['dateCreate'] = $formattedDateCreate;

        // получаем ID сделки
        $crmDeal = $this->Check($bazisOrder['data']['number']);

        if(isset($crmDeal['ID']) && !empty($crmDeal['ID'])){
            $CRM_DEAL_ID = 'D_'.$crmDeal['ID'];
            $bazisOrder['data']['CRM_DEAL_ID'] = $CRM_DEAL_ID;
        }
        else{
            $CRM_DEAL_ID = '';
        }


        // формула составление суммы мебели со скидками
        $bazisOrder['data']['special_sum_discont'] = $this->Create_special_sum_discont($bazisOrder['data']);

        // проверяем каждое поле
        if(strval($bazisOrder['data']['id']) !== strval($IblokItem_base['PROPERTY_94_VALUE'])){
            $upd[] = 'upd1';
        }
        if(strval($bazisOrder['data']['number']) !== strval($IblokItem_base['PROPERTY_93_VALUE'])){
            $upd[] = 'upd2';
        }

        if(($bazisOrder['data']['sumPayments'].'|RUB') !== $IblokItem_base['PROPERTY_123_VALUE']){
            $upd[] = 'upd3';
        }
        if(($bazisOrder['data']['special_sum_discont'].'|RUB') !== $IblokItem_base['PROPERTY_122_VALUE']){
            $upd[] = 'upd3';
        }
        if(($bazisOrder['data']['sumWithoutDiscount'].'|RUB') !== $IblokItem_base['PROPERTY_121_VALUE']){
            $upd[] = 'upd3';
        }
        if(($bazisOrder['data']['sumWithProductDiscount'].'|RUB') !== $IblokItem_base['PROPERTY_120_VALUE']){
            $upd[] = 'upd4';
        }
        if(($bazisOrder['data']['sumTotal'].'|RUB') !== $IblokItem_base['PROPERTY_124_VALUE']){
            $upd[] = 'upd5';
        }

        if($bazisOrder['data']['dateCreate'] !== $IblokItem_base['PROPERTY_95_VALUE']){
            $upd[] = 'upd6';
        }

        if((isset($bazisOrder['data']['CRM_DEAL_ID']) && (isset($IblokItem_base['PROPERTY_97_VALUE'])))){
            if($bazisOrder['data']['CRM_DEAL_ID'] !== $IblokItem_base['PROPERTY_97_VALUE']){
                $upd[] = 'upd7';
             }
        }

        foreach($IblokItems as $IblokItem_itemy){
            $arrFilesIds[] = $IblokItem_itemy['PROPERTY_92_VALUE'];
        }

        // получим все файлы элемента из Битрикс
        $arrFileNamesBitrix = $this->GetFileInBitriX($arrFilesIds); // ['FILE_NAME', 'FILE', 'FILE_ID']

        foreach($bazisOrder['files'] as $keyFl => $file) {
            $UpdFiles[] = CheckFileToUpdate($bazisOrder,$IblokItems,$file,$arrFileNamesBitrix);
        }

        
        print_r(['$arrFileNamesBitrix' => $arrFileNamesBitrix]);
        die();

        


        /*foreach($filesBazis as $filenameBazis => $fileBazis){
            $fileID = $this->SaveFileInBitrix([$filenameBazis => $fileBazis]);
                $UpdFiles[] = [
                    'ID_DOCK' => '---',
                    'PROPERTY_92_VALUE' =>  $fileID, // $fileID
                    'name' => $filenameBazis,
                    'fileID' => $fileID, // $fileID
                ];
            $DiffFlies = true;
        }

        return ['orderIsChecked' => $bazisOrder['data'], 'upd' => $upd, 'updFiles' => $UpdFiles];*/
        
    }


    public function CheckFileToUpdate($bazisOrder,$IblokItems,$file,$arrFileNamesBitrix){

        // получим конкретный файл из Базис
        $files[$file['name']] = $this->getFileInBazis(['orderId' => $bazisOrder['orderId'], 'name' => $file['name']]);

        // соберем пул наименований
        foreach($arrFileNamesBitrix as $key => $item){
            $arrFileNamesBitrixNames[$key] = $item['FILE_NAME'];
        }

        foreach($filesBazis as $filenameBazis => $fileBazis){
            // если нет такого по наименованию
            if(!in_array($filenameBazis, $arrFileNamesBitrixNames)){
                // сохраняем в Битрикс
                $fileID = $this->SaveFileInBitrix([$filenameBazis => $fileBazis]);
                // добавляем в пул на ДОБАВЛЕНИЕ
                $UpdFiles[] = [
                    'PROPERTY_92_VALUE' =>  $fileID, // $fileID
                    'name' => $filenameBazis,
                    'fileID' => $fileID, // $fileID
                ];
                $DiffFlies = true;
            }
            else{
                // сравниваем сам файл, так как имя нашлось в битрикс
                foreach($arrFileNamesBitrix as $key_dock => $FileBitrix_item){
                    if($FileBitrix_item['FILE_NAME'] == $filenameBazis){
                        // для файлов с одинаковым именем
                        foreach($IblokItems as $arrFilesIds_item){
                            if($arrFilesIds_item['PROPERTY_92_VALUE'] == $FileBitrix_item['FILE_ID']){
                                $UpdFiles[] = [
                                    'PROPERTY_92_VALUE' =>  $FileBitrix_item['FILE_ID'],
                                    'name' => $filenameBazis,
                                    'fileID' => $arrFilesIds_item['PROPERTY_92_VALUE_ID'], 
                                ];
                            }
                        }
                    }
                }
            }
           // if($fileBazis == )
        }
            
    
            
    }

    // поиск сделок по номеру договора
    public function Check($numer)
    {

      if (\Bitrix\Main\Loader::IncludeModule("crm")) {

          $DealsInfo = \Bitrix\Crm\DealTable::GetList([
            'select' => ['ID','UF_CRM_1694018792723', '*', 'CATEGORY_ID', 'STAGE_ID'], 
            'filter' => ['UF_CRM_1694018792723' => $numer],
            'limit' => 1
          ]);
        
          $DealsInfoData = $DealsInfo->fetchAll();
          if ($DealsInfoData !== false) {
              return $DealsInfoData[0];
          }
          else{
              return NULL;
          }

      }

      return NULL;
    }

    public function DeleteFileInBitriX($aElementID){

        $obElement = \CIBlockElement::GetList(
            [],
            [
                'ID' => $aElementID,
                'IBLOCK_ID' => 5
            ],
            false,
            false,
            [
                'ID',
                'IBLOCK_ID',
            ]
        );

        if ($obFields = $obElement->GetNextElement()) {
            $arProperties = $obFields->GetProperties();
            if (!empty($arProperties)) {
                foreach ($arProperties as $sPropCode => $arPropValues) {
                    if ($sPropCode === 'FAYL') {
                        foreach ($arPropValues['VALUE'] as $iKeyValue => $sValue) {
                            if ($arPropValues['PROPERTY_VALUE_ID'][$iKeyValue] > 0) {
                                $fileID = $sValue;
                                
                                // Удаляем файл с помощью CFile::Delete
                                \CFile::Delete($fileID);
                                
                                // Устанавливаем значение свойства как пустое
                                $arDeleteList[$sPropCode][$arPropValues['PROPERTY_VALUE_ID'][$iKeyValue]] = [
                                    'VALUE' => [
                                        'del' => 'Y',
                                    ]
                                ];
                            }
                        }
                    }
                }

                if (!empty($arDeleteList)) {
                    foreach ($arDeleteList as $sPropForDelete => $arDeleteFiles) {
                        \CIBlockElement::SetPropertyValueCode(
                            $aElementID,
                            $sPropForDelete,
                            $arDeleteFiles
                        );
                    }
                }
            }
        }

    }

    // поиск INFO о мультиполях файлов в битрикс
    public function GetFileInBitriX($p){
        foreach($p as $idDock => $fileID){
            $fileInfo = \CFile::GetByID($fileID)->Fetch();
            $fileURL = 'https://bx24.kitchenbecker.ru'.$fileInfo['SRC'];
            $fileData = file_get_contents($fileURL);
            $Data[$idDock] = ['FILE_NAME' => $fileInfo['FILE_NAME'], 'FILE' => $fileData, 'FILE_ID' => $fileID];
        }
        return $Data;
    }
      
    // поиск документа/элемента в битрикс по номеру договора
    public function GetIblokList($number){

        $arFilter = [
            "IBLOCK_ID" => 5,
            "ACTIVE" => "Y",
            'PROPERTY_93' => $number,
        ];
        $arSort = [
            "ID" => "ASC", 
        ];
        $arSelect = array(
            "ID",
            "NAME",
            'PROPERTY_97', // связь с CRM -> сделка
            'PROPERTY_120', // Сумма заказа базовая (-40%): //_VALUE
            'PROPERTY_121', // Сумма заказа по салону:
            'PROPERTY_122', // сумма мебели со скидками
            'PROPERTY_123', // сумма аванса
            'PROPERTY_124', // итого
            'PROPERTY_92', // файлы-документы
            'PROPERTY_93', // номер договора
            'PROPERTY_95', // дата создания документа
            'PROPERTY_94', // ID документа
        );

        // проверяем фиьлтром есть ли элементы с таким ID ДОГОВОРА, если несколько то собираем файлы
        $rsElements = \CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);

        $ch = 0;
        while ($arElement = $rsElements->Fetch()){
            $ch = $ch + 1;
            $arrIblockItem[] = $arElement;
            if($ch > 3){
                break;
            }
    
        }
        if (!isset($arrIblockItem)) {
            return NULL;
        }
        else{
            return $arrIblockItem;
        }

    }

    public function Create_special_sum_discont($p)
    {
        $Sum1 = 0;
        $Sum2 = 0;
        
        foreach($p['items'] as $item){
            if($item['type'] == 'Дополнительные элементы'){
                $Sum1 = $Sum1 + $item['sumWithDiscount'];
            }
            if($item['type'] == 'Техника и сантехника'){
                $Sum1 = $Sum1 + $item['sumWithDiscount'];
            }
        }

        $createDiscount = ($p['sumWithDiscount'] - $Sum1) - $Sum2;
        
        if(isset($createDiscount)){
            return $createDiscount;
        }
        else{
            return NULL;
        }
 
    }

  }