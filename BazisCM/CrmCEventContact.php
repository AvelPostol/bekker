<?php
namespace BazisCM;
// подключение классов
require_once ('head.php');

use Bitrix\Main\Loader;
use Bitrix\Main\Type;

  /**
  * Проверка ДОБАВЛЕНИЯ/ОБНОВЛЕНИЯ контакта -> валидация и стандартизация номера телефона
  *
  */

  class CrmCEventContact
  {
      private $Base;
      private $Main;
  
      public function __construct() {
        $this->Main = new \BazisCM\Main();
        $this->Base = new \BazisCM\Workspace\Tools\Base();
      }

      public function formatPhoneNumber($phoneNumber) {
        // Удаляем все лишние пробелы и знаки
        $phoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
    
        // Проверяем, начинается ли номер с 89 или +89, и меняем на +79
        if (substr($phoneNumber, 0, 2) === '89') {
            $phoneNumber = '+79' . substr($phoneNumber, 2);
        }

        if (substr($phoneNumber, 0, 3) === '+89') {
          $phoneNumber = '+79' . substr($phoneNumber, 3);
        }
        if (substr($phoneNumber, 0, 3) === '+99') {
          $phoneNumber = '+79' . substr($phoneNumber, 3);
        }
        // Проверяем, начинается ли номер с 88, и меняем на +79
        if (substr($phoneNumber, 0, 2) === '88') {
            $phoneNumber = '+79' . substr($phoneNumber, 2);
        }

        if (substr($phoneNumber, 0, 2) === '79') {
          $phoneNumber = '+79' . substr($phoneNumber, 2);
      }
    
        // Проверяем, начинается ли номер с +9, и меняем на +79
        if (substr($phoneNumber, 0, 2) === '+9') {
            $phoneNumber = '+79' . substr($phoneNumber, 2);
        }

        if (substr($phoneNumber, 0, 1) === '9') {
          $phoneNumber = '+79' . substr($phoneNumber, 1);
        }

        return $phoneNumber;
    }
    
      public function Check($arFields){

        $state = false;

        $IdEntity = $arFields['ID'];

        foreach($arFields['FM']['PHONE'] as $key_id_mult_field => $PHONE_ITEM){
          // проверка существования номера телефона и получение ID контакта
          if (isset($PHONE_ITEM['VALUE']) && !empty($PHONE_ITEM['VALUE'])) {
        
            $numer = $PHONE_ITEM['VALUE'];

            // валидация номера телефона
            $numer = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($numer)->format();
            $numer = $this->formatPhoneNumber($numer);

            // формирование ячейки для обновления номеров контакта
            $FieldPhoneBox['FM']['PHONE'][$key_id_mult_field] = $PHONE_ITEM;
            $FieldPhoneBox['FM']['PHONE'][$key_id_mult_field]['VALUE'] = $numer;

            if($arFields['FM']['PHONE'][$key_id_mult_field]['VALUE'] !== $FieldPhoneBox['FM']['PHONE'][$key_id_mult_field]['VALUE']){
              $state = true;
            }
          }
        }

        if($state){
          $this->Base->writeLog(['body' => $FieldPhoneBox, 'meta' => 'SuUpdate']);
          $bCheckRight = false;
          $contactEntity = new \CCrmContact( $bCheckRight );
          $isUpdateSuccess = $contactEntity->Update(
            $IdEntity,
            $FieldPhoneBox
          );
        }

        if($isUpdateSuccess){
          $this->Base->writeLog(['body' => $isUpdateSuccess, 'meta' => 'Succsess_Contact_Update']);
        }
        else{
          $this->Base->writeLog(['body' => $arFields, 'meta' => 'Except_Contact_Update']);
        }
      }

      public static function Controller(&$arFields)
      {
        $instance = new CrmCEventContact(); 
        $manager = $instance->Check($arFields);
      }
  }
