<?php
namespace BazisCM\Workspace\Bitrix;

/*
    Класс для обращения к битрикс
*/

  class Common
  {
      // поиск сделок по номеру договора
      public function Check($p)
      {

        if (\Bitrix\Main\Loader::IncludeModule("crm")) {
            $numer = '';

            if(isset($p['client']['data']['number']) && !empty($p['client']['data']['number'])){
                $numer = $p['client']['data']['number'];
            }
            if(isset($p['stateBl']['number']) && !empty($p['stateBl']['number'])){
                $numer = $p['stateBl']['number'];
             
            }

     

            $DealsInfo = \Bitrix\Crm\DealTable::GetList([
              'select' => ['ID','UF_CRM_1694018792723', '*', 'CATEGORY_ID', 'STAGE_ID'], 
              'filter' => ['UF_CRM_1694018792723' => $numer], //$p['client']['data']['number'] //'123321'
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
                        // Замените 'FAYL' на код свойства, которое вы хотите очистить
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

      // поиск документа/элемента списка в битрикс по номеру договора
      public function GetIblokList($p){

        $arFilter = [
            "IBLOCK_ID" => 5,
            "ACTIVE" => "Y",
            'PROPERTY_93' => $p['data']['number'],
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

        // проверяем фиьлтром есть ли элементы списка с таким ID ДОГОВОРА, если несколько то собираем файлы
        $rsElements = \CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);

        while ($arElement = $rsElements->Fetch()){
            $arrIblockItem[] = $arElement;
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
        // сохранение файлов в структуре битрикс
      public function SaveFileInBitrix($file){

            foreach($file as $filename => $file_item){
            $localFilePath = $_SERVER['DOCUMENT_ROOT'] . '/upload/iblock_saveOut/'.$filename;
            file_put_contents($localFilePath, $file_item);
            }
    
            // Проверяем, удалось ли скачать файл
            if (file_exists($localFilePath)) {
                // Теперь можно загрузить скачанный файл в Битрикс
                $fileArray = \CFile::MakeFileArray($localFilePath);
                if ($fileArray) {
                    // Файл успешно загружен
                    // Теперь можно использовать его ID для обновления элемента в Битрикс
                    $fileID = \CFile::SaveFile($fileArray, $_SERVER['DOCUMENT_ROOT'] . '/upload/iblock_saveOutIn/');
                    if ($fileID) {
                        // Файл успешно сохранен в Битрикс
                        echo "Файл успешно сохранен в Битрикс. ID файла: " . $fileID;
                        return $fileID;
                    } else {
                        echo "Ошибка при сохранении файла в Битрикс.";
                    }
                } else {
                    echo "Ошибка при создании массива файла.";
                }
            } else {
                echo "Ошибка при скачивании файла с удаленного сервера.";
                return NULL;
            }
    
    
    
      }

      public function AdaptForAddIblockElem($bazisOrder, $filesBazis){

        $timestamp = strtotime($bazisOrder['data']['dateCreate']); // Преобразовываем строку в метку времени
        $formattedDateCreate = date("d.m.Y", $timestamp);
        $bazisOrder['data']['dateCreate'] = $formattedDateCreate;

        // получаем ID сделки
        $crmDeal = $this->Check(['client' => ['data' => ['number' => $bazisOrder['data']['number']]]]);
        if($crmDeal){
            $CRM_DEAL_ID = 'D_'.$crmDeal['ID'];
            $bazisOrder['data']['CRM_DEAL_ID'] = $CRM_DEAL_ID;
        }
        else{
            $CRM_DEAL_ID = '';
        }

        // формула составление суммы мебели со скидками
        $bazisOrder['data']['special_sum_discont'] = $this->Create_special_sum_discont($bazisOrder['data']);

        foreach($filesBazis as $filenameBazis => $fileBazis){
            $fileID = $this->SaveFileInBitrix([$filenameBazis => $fileBazis]);
            $UpdFiles[] = [
                'ID_DOCK' => '---',
                'PROPERTY_92_VALUE' =>  $fileID, // $fileID
                'name' => $filenameBazis,
                'fileID' => $fileID, // $fileID
            ];
        }

        return ['ord' => $bazisOrder, 'files' => $UpdFiles];

      }

      // проверка соответствия полей документа/элемента списка из битрикс и базис
      public function IblokItemCheck($bazisOrder,$IblokItems,$filesBazis){

        $IblokItem_base = $IblokItems[0];
 
        $UpdFiles = [];

        // форматируем дату создания заказа
        $timestamp = strtotime($bazisOrder['data']['dateCreate']); // Преобразовываем строку в метку времени
        $formattedDateCreate = date("d.m.Y", $timestamp);
        $bazisOrder['data']['dateCreate'] = $formattedDateCreate;

        // получаем ID сделки
        $crmDeal = $this->Check(['client' => ['data' => ['number' => $bazisOrder['data']['number']]]]);
        if($crmDeal){
            $CRM_DEAL_ID = 'D_'.$crmDeal['ID'];
            $bazisOrder['data']['CRM_DEAL_ID'] = $CRM_DEAL_ID;
        }
        else{
            $CRM_DEAL_ID = '';
        }

        print_r(['$IblokItems' => $IblokItems]);
        print_r(['id crm' => $crmDeal['ID']]);



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

        /*
        $UpdFiles -> массив файлов для Update, 
        Структура => [
            'ID_DOCK' --- НЫНЕ НЕ ИСПОЛЬЗУЕТЯ, НО ПЕРЕДАЕТСЯ. СID обновляемого документа / файлы типо в разных документах по факту, но из пользовательской части видно как единое целое, "версия документа"
            'PROPERTY_92_VALUE' --- значение PROPERTY_92 обновляемого файла
            'name' --- имя файла
            'file' --- файл в формате base64
        ]
        */

        $this->DeleteFileInBitriX($IblokItem_itemy['ID']);


        foreach($filesBazis as $filenameBazis => $fileBazis){
            $fileID = $this->SaveFileInBitrix([$filenameBazis => $fileBazis]);
                $UpdFiles[] = [
                    'ID_DOCK' => '---',
                    'PROPERTY_92_VALUE' =>  $fileID, // $fileID
                    'name' => $filenameBazis,
                    'fileID' => $fileID, // $fileID
                ];
            $DiffFlies = true;
        }

        return ['orderIsChecked' => $bazisOrder['data'], 'upd' => $upd, 'updFiles' => $UpdFiles];

        // $Data[$idDock] = ['FILE_NAME' => $fileInfo['FILE_NAME'], 'FILE' => $fileData, 'FILE_ID' => $fileID];
       /* $arrFileNamesBitrix = $this->GetFileInBitriX($arrFilesIds);

        foreach($arrFileNamesBitrix as $key => $item){
            $arrFileNamesBitrixNames[$key] = $item['FILE_NAME'];
        }

        if(count($filesBazis) !== count($arrFileNamesBitrix)){
            $upd[] = 'upd8';
        }
      
        foreach($filesBazis as $filenameBazis => $fileBazis){
            if(!in_array($filenameBazis, $arrFileNamesBitrixNames)){
                $fileID = $this->SaveFileInBitrix([$filenameBazis => $fileBazis]);
                $UpdFiles[] = [
                    'ID_DOCK' => '---',
                    'PROPERTY_92_VALUE' =>  $fileID, // $fileID
                    'name' => $filenameBazis,
                    'fileID' => $fileID, // $fileID
                ];
                $DiffFlies = true;
            }
            else{
                foreach($arrFileNamesBitrix as $key_dock => $FileBitrix_item){
                    if($FileBitrix_item['FILE_NAME'] == $filenameBazis){
                        // $Data[$idDock] = ['FILE_NAME' => $fileInfo['FILE_NAME'], 'FILE' => $fileData, 'FILE_ID' => $fileID];
                        foreach($IblokItems as $arrFilesIds_item){
                            if($arrFilesIds_item['PROPERTY_92_VALUE'] == $FileBitrix_item['FILE_ID']){
                                $UpdFiles[] = [
                                    'ID_DOCK' => $key_dock,
                                    'PROPERTY_92_VALUE' =>  $FileBitrix_item['FILE_ID'],
                                    'name' => $filenameBazis,
                                    'fileID' => $arrFilesIds_item['PROPERTY_92_VALUE_ID'], 
                                ];
                            }
                           
                        }
                    }
                }
            }
        }

        if(isset($DiffFlies)){
            $upd[] = 'DiffFlies';
            return ['orderIsChecked' => $bazisOrder['data'], 'upd' => $upd, 'updFiles' => $UpdFiles];
        }
        
        if(isset($upd)){
            return ['orderIsChecked' => $bazisOrder['data'], 'upd' => $upd, 'updFiles' => $UpdFiles];
        }
        return NULL;*/
        
      }


      
      
      public function CorrectNumber($number){
        $roundedNumber = number_format($number, 2, '.', '');
        return $roundedNumber;
      }

      // добавление документа/элемента списка
      public function IblokItemAdd($bazisOrder, $files){

        $PROP = [];

        if ($bazisOrder['special_sum_discont']) {
          //  $PROP['122'] = $this->CorrectNumber($bazisOrder['special_sum_discont']).'|RUB';
          $PROP['122'] = '0'.'|RUB';
        }
        
        if ($bazisOrder['sumPayments']) {
            $PROP['123'] = $this->CorrectNumber($bazisOrder['sumPayments']).'|RUB';
        }
        
        if ($bazisOrder['sumWithoutDiscount']) {
            $PROP['121'] = $this->CorrectNumber($bazisOrder['sumWithoutDiscount']).'|RUB';
        }
        
        if ($bazisOrder['sumWithProductDiscount']) {
            $PROP['120'] = $this->CorrectNumber($bazisOrder['sumWithProductDiscount']).'|RUB';
        }
        
        if ($bazisOrder['sumTotal']) {
            $PROP['124'] = $this->CorrectNumber($bazisOrder['sumTotal']).'|RUB';
        }
        
        if ($bazisOrder['number']) {
            $PROP['93'] = $bazisOrder['number'];
        }
        
        if ($bazisOrder['id']) {
            $PROP['94'] = $bazisOrder['id'];
        }
        
        if ($bazisOrder['dateCreate']) {
            $PROP['95'] = $bazisOrder['dateCreate'];
        }
        
        if ($bazisOrder['CRM_DEAL_ID']) {
            $PROP['97'] = $bazisOrder['CRM_DEAL_ID'];
        }
        
        foreach($files as $file){
            $PROP['92'][$file['PROPERTY_92_VALUE']] =  $file['fileID'];
        }

        $arFields = [
            "IBLOCK_ID" => 5,
            'NAME' => $bazisOrder['number'],
        ];

        if($PROP){
            $arFields['PROPERTY_VALUES'] = $PROP;
        }

        $element = new \CIBlockElement;
        $updateResult =  $element->Add($arFields);

        if ($updateResult !== false) {
            echo "Элемент успешно обновлен.";
            return true;
        } else {
            echo "Ошибка при обновлении элемента: " . $element->LAST_ERROR;
            return NULL;
        }
      }

      //обновление документа/элемента списка
      public function IblokItemUpdate($bazisOrder,$IblokItems,$files){

        $blockItem = $IblokItems[0];

        $PROP = [];

        if ($bazisOrder['special_sum_discont']) {
            $PROP['122'] = $this->CorrectNumber($bazisOrder['special_sum_discont']).'|RUB';
        }
        
        if ($bazisOrder['sumPayments']) {
            $PROP['123'] = $this->CorrectNumber($bazisOrder['sumPayments']).'|RUB';
        }
        
        if ($bazisOrder['sumWithoutDiscount']) {
            $PROP['121'] = $this->CorrectNumber($bazisOrder['sumWithoutDiscount']).'|RUB';
        }
        
        if ($bazisOrder['sumWithProductDiscount']) {
            $PROP['120'] = $this->CorrectNumber($bazisOrder['sumWithProductDiscount']).'|RUB';
        }
        
        if ($bazisOrder['sumTotal']) {
            $PROP['124'] = $this->CorrectNumber($bazisOrder['sumTotal']).'|RUB';
        }
        
        if ($bazisOrder['number']) {
            $PROP['93'] = $bazisOrder['number'];
        }
        
        if ($bazisOrder['id']) {
            $PROP['94'] = $bazisOrder['id'];
        }
        
        if ($bazisOrder['dateCreate']) {
            $PROP['95'] = $bazisOrder['dateCreate'];
        }
        
        if ($bazisOrder['CRM_DEAL_ID']) {
            $PROP['97'] = $bazisOrder['CRM_DEAL_ID'];
        }
        
        foreach($files as $file){
            $PROP['92'][$file['PROPERTY_92_VALUE']] =  $file['fileID'];
        }

        $arFields = [
            'NAME' => $bazisOrder['number'],
        ];

        if($PROP){
            $arFields['PROPERTY_VALUES'] = $PROP;
        }
        //print_r([$blockItem['ID'], $arFields]);
        $element = new \CIBlockElement;
        $updateResult = $element->Update($blockItem['ID'], $arFields);

        if ($updateResult !== false) {
            echo "Элемент успешно обновлен.";
            return true;
        } else {
            echo "Ошибка при обновлении элемента: " . $element->LAST_ERROR;
            return NULL;
        }

      }

      /*public function CheckBit($p)
      {

        if (\Bitrix\Main\Loader::IncludeModule("crm")) {

            $DealsInfo = \Bitrix\Crm\DealTable::GetList([
              'select' => ['ID','UF_CRM_1694018792723', '*'], 
              'filter' => ['UF_CRM_1694018792723' => $p['client']['data']['number']]
            ]);

            $DealsInfoData = $DealsInfo->fetch();
            if ($DealsInfoData !== false) {
                return $DealsInfoData;
            }
            else{
                return NULL;
            }

        }

        return NULL;
      }*/

      // проверка контакта на необходимость обновления
      public function ContactGet_check($p){

        if (\Bitrix\Main\Loader::IncludeModule("crm")) {
            $ContactInfo = \Bitrix\Crm\ContactTable::GetList([
                'select' => ['*', 'PHONE', 'ADDRESS'], 
                'filter' => [$p['filter']]
              ]);
        }

        $Contact = $ContactInfo->fetch();
            if ($Contact !== false) {
        
                if($p['updateField']['house']){
                    $p['updateField']['house'] = 'д. '.$p['updateField']['house'];
                }
                
                if($p['updateField']['housing']){
                    $p['updateField']['housing'] = 'корпус '.$p['updateField']['housing'];
                }
        
                if($p['updateField']['apartment']){
                    $p['updateField']['apartment'] = 'квартира '.$p['updateField']['apartment'];
                }
        
                $adres = trim($p['updateField']['microdistrict'].' '.$p['updateField']['street'].' '.$p['updateField']['house'].' '.$p['updateField']['housing'].' '.$p['updateField']['apartment']);

                if($Contact['NAME'] !== $p['check']['name']){
                    return 'update';
                }
                if($Contact['LAST_NAME'] !== $p['check']['surname']){
                    return 'update';
                }
                if($Contact['SECOND_NAME'] !== $p['check']['patronymic']){
                    return 'update';
                }

                // получаем мульти поля
                $multiFields = \Bitrix\Crm\FieldMultiTable::getList([
                    'filter' => [
                    'ELEMENT_ID' => $p['filter']['ID'],
                    'ENTITY_ID' => 'CONTACT',
                    ],
                ]);

                $stateph = false;
                // adapt мульти поля
                while ($fieldItem = $multiFields->fetch())
                {
                    if($fieldItem['TYPE_ID'] == 'PHONE'){
                        if(($fieldItem['VALUE'] == $p['check']['phone']) || ($fieldItem['VALUE'] == $p['check']['phone2'])){
                            $stateph = true;
                        }
                    }
                }

                if($stateph == false){
                    return 'update';
                }
                if($Contact['EMAIL'] !== $p['check']['email']){
                    return 'update';
                }
                if($Contact['ADDRESS'] !== $adres){
                    return 'update';
                }

                return true;
            }
            else{
                return NULL;
            }
      }

      // обновление контакта полями из базиса
      public function ContactUpdate($p){

        // получаем мульти поля
        $multiFields = \Bitrix\Crm\FieldMultiTable::getList([
            'filter' => [
            'ELEMENT_ID' => $p['ID'],
            'ENTITY_ID' => 'CONTACT',
            ],
        ]);
        
        $ID_PHONE['PHONE'] = false;
        $ID_PHONE2['PHONE'] = false;

        if($p['updateField']['phone']){
            $p['updateField']['phone'] = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($p['updateField']['phone'])->format();
        }
        
        if($p['updateField']['phone2']){
            $p['updateField']['phone2'] = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($p['updateField']['phone2'])->format();
        }
        $fieldItemPhoneCount = 0;
        // adapt мульти поля
        while ($fieldItem = $multiFields->fetch())
        {
            
            if($fieldItem['TYPE_ID'] == 'EMAIL'){
               $ID_MAIL = $fieldItem['ID'];
            }
            if($fieldItem['TYPE_ID'] == 'PHONE'){

             

                $fieldItem['VALUE'] = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($fieldItem['VALUE'])->format();
                if(($fieldItemPhoneCount == 0) && $p['updateField']['phone']){
                    $ID_PHONE['PHONE'] = [$fieldItem['ID'] => ["VALUE_TYPE" => "WORK", 'VALUE' => $p['updateField']['phone']]];
                }
                if(($fieldItemPhoneCount == 1) && $p['updateField']['phone2']){
                    $ID_PHONE2['PHONE'] = [$fieldItem['ID'] => ["VALUE_TYPE" => "WORK", 'VALUE' => $p['updateField']['phone2']]];
                }
                $fieldItemPhoneCount++;
            }
           
        }

        if(($ID_PHONE['PHONE'] == false) && $p['updateField']['phone']){
            $ID_PHONE['PHONE'] = ['n0' => ["VALUE_TYPE" => "WORK", 'VALUE' => $p['updateField']['phone']]];
        }
        if(($ID_PHONE2['PHONE'] == false) && $p['updateField']['phone2']){
            $ID_PHONE2['PHONE'] = ['n1' => ["VALUE_TYPE" => "WORK", 'VALUE' => $p['updateField']['phone2']]];
        }

        
        /* 
        * собираем адресс
        */

        if($p['updateField']['house']){
            $p['updateField']['house'] = 'д. '.$p['updateField']['house'];
        }
        
        if($p['updateField']['housing']){
            $p['updateField']['housing'] = 'корпус '.$p['updateField']['housing'];
        }

        if($p['updateField']['apartment']){
            $p['updateField']['apartment'] = 'квартира '.$p['updateField']['apartment'];
        }

        $adres = trim($p['updateField']['microdistrict'].' '.$p['updateField']['street'].' '.$p['updateField']['house'].' '.$p['updateField']['housing'].' '.$p['updateField']['apartment']);
        
        $arrFields = [
            'NAME' => $p['updateField']['name'],
            'LAST_NAME' => $p['updateField']['surname'],
            'SECOND_NAME' => $p['updateField']['patronymic'],
            'ADDRESS_REGION' => $p['updateField']['region'],
            'ADDRESS_CITY' => $p['updateField']['city'],
            'ADDRESS' => $adres,
        ];

        if($ID_PHONE){
            foreach($ID_PHONE['PHONE'] as $key => $val){
                $arrFields['FM']['PHONE'][$key] = $val;
            }
        }
        if($ID_PHONE2){
            foreach($ID_PHONE2['PHONE'] as $key => $val){
                $arrFields['FM']['PHONE'][$key] = $val;
            }
        }
        if($ID_EMAIL){
            $arrFields['FM']['EMAIL'][$ID_EMAIL] = ["VALUE_TYPE" => "WORK", 'VALUE' => $p['updateField']['email']];
        }

        $id_cont = $p['ID'];

        $bCheckRight = false;
        $contactEntity = new \CCrmContact( $bCheckRight );
        $isUpdateSuccess = $contactEntity->Update(
            $id_cont,
            $arrFields
        );

        if($isUpdateSuccess){
            return true;
        }
        else{
            return NULL;
        }
      }
      // добавление контакта с полями из базиса // НЫНЕ НЕ ФУНКЦИОНИРУЕТ, оставлен для будующих 
      public function ContactAdd($p){

        if (\Bitrix\Main\Loader::IncludeModule("crm")) {
            $adres = trim($p['region'].' '.$p['city'].' '.$p['microdistrict'].' '.$p['street'].' '.$p['house'].' '.$p['housing'].' '.$p['apartment']);

            $result = \Bitrix\Crm\ContactTable::add([
                'NAME' => $p['name'],
                'LAST_NAME' => $p['surname'],
                'SECOND_NAME' => $p['patronymic'],
                'PHONE' => array(array('VALUE' => $p['phone'], 'VALUE_TYPE' => 'WORK')), 
                'ADDRESS' => $adres,
            ]);

            if($result){
                return true;
            }
            else{
                return NULL;
            }

   
        }

   
      }

     // обновление сделки -> конкретно используется для изменения стадии сделки
      public function UpdateCrmDeal($p){

        if (\Bitrix\Main\Loader::IncludeModule("crm")) {
            
            
            $entityId = $p['ID'];
            $entityFields = $p['updateFields'];


            $bCheckRight = false;
            $entityObject = new \CCrmDeal( $bCheckRight );
            $isUpdateSuccess = $entityObject->Update(
            $entityId,
            $entityFields,
            $bCompare = true,
            $arOptions = [
                /**
                 * ID пользователя, от лица которого выполняется действие
                 * в том числе проверка прав
                 * @var integer
                 */
                'CURRENT_USER' => \CCrmSecurityHelper::GetCurrentUserID(4),

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

        if($isUpdateSuccess){
            return true;
        }
        else{
            return NULL;
        }
          
        }

      }

      
  }
