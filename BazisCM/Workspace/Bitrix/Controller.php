<?php
namespace BazisCM\Workspace\Bitrix;

  class Controller
  {
    public static function Check($p)
    {

       //   RU => ANGL
       $alfa = [
        'Т'=>'T',
        'К'=>'K',
        'Е'=>'E',
        'А'=>'A',
        'О'=>'O',
        'Р'=>'P',
        'Х'=>'X',
        'М'=>'M',
        'Н'=>'H',
        'С'=>'C',
        'В'=>'B'
      ];

      $modifiedText = NULL;

      foreach($alfa as $key_i => $val_i){
        $text = $p['ManagerUserField'];
        $modifiedText = str_replace($key_i, $val_i, $text);
      }

      if($modifiedText !== NULL){
        $p['ManagerUserField'] = $modifiedText;
      }

      $maxNumberBazis = $p['maxNumber'];
      $numer = ++$p['maxNumber'];
      $ContractUID = $p['ManagerUserField'].$p['maxNumber'];

      print_r(['cheeek1']);

      if (\Bitrix\Main\Loader::IncludeModule("crm")) {
        $found = true;
        // Поиск подстроки "K01"
        $substring = $p['ManagerUserField'];
    
        $arrNUm = [];
        
        while ($found) {
            $DealInfo = \Bitrix\Crm\DealTable::GetList([
                'select' => ['ID', 'UF_CRM_1694018792723'],
                'filter' => ['!==UF_CRM_1694018792723' => null]
            ]);
            $R = false;
            foreach($DealInfo as $key => $rec){
              $R = 1;
              $pos = strpos($rec['UF_CRM_1694018792723'], $substring);
              // Если подстрока найдена
              if ($pos !== false) {
                $result = str_replace($substring, "", $rec['UF_CRM_1694018792723']);
                $arrNUm[] = $result;
              }
            }
    
            if(!empty($arrNUm)){
              // Преобразование каждого элемента массива в число
              $numericArray = array_map('intval', $arrNUm);
              // Нахождение самого большого числа
              $maxValue = max($numericArray);
            }
            
            if ($R !== false) {
              if(isset($maxValue) && !empty($maxValue)){
                $numes = [$maxValue, $numer];
                $numerics = array_map('intval', $numes);
                $numerics = max($numerics);
                $numerics++;
                $ContractUID = $p['ManagerUserField'].$numerics;
              }
              else{
                $numer++;
                $ContractUID = $p['ManagerUserField'].$numerics;
              }   
              $found = false;
            } else {
                  $found = false;
            }
        }
    }
    
      return $ContractUID;
    }
    

    public function AddContractNumber($p){

      if (\Bitrix\Main\Loader::IncludeModule("crm")) {

          $entityId = $p['deal']['ID'];
          $entityFields = [
            'UF_CRM_1694018792723' => $p['NewNumberContract']
          ];


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


