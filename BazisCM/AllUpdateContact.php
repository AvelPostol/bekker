<?php
namespace BazisCM;
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);

// подключение классов
require_once ('head.php');



  /**
  * Проверка ДОБАВЛЕНИЯ/ОБНОВЛЕНИЯ контакта -> валидация и стандартизация номера телефона
  *
  */

  class CrmCEventContact
  {
      private $Base;
      private $Main;
  
      public function __construct() {
        $this->Base = new \BazisCM\Workspace\Tools\Base();
      }

      public function formatPhoneNumber($phoneNumber) {
        // Удаляем все лишние пробелы и знаки
        $phoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
    
        // Проверяем, начинается ли номер с 89 или +89, и меняем на +79
        if (substr($phoneNumber, 0, 2) === '89' || substr($phoneNumber, 0, 3) === '+89') {
            $phoneNumber = '+79' . substr($phoneNumber, 2);
        }
    
        // Проверяем, начинается ли номер с 88, и меняем на +79
        if (substr($phoneNumber, 0, 2) === '88') {
            $phoneNumber = '+79' . substr($phoneNumber, 2);
        }

              // Проверяем, начинается ли номер с 88, и меняем на +79
        if (substr($phoneNumber, 0, 2) === '79') {
            $phoneNumber = '+79' . substr($phoneNumber, 2);
        }
    
        // Проверяем, начинается ли номер с +99, и меняем на +79
        if (substr($phoneNumber, 0, 2) === '+9') {
            $phoneNumber = '+79' . substr($phoneNumber, 2);
        }

        if (substr($phoneNumber, 0, 1) === '9') {
          $phoneNumber = '+7' . substr($phoneNumber, 1);
        }

          // Проверяем, начинается ли номер с +99, и меняем на +79
          if (substr($phoneNumber, 0, 3) !== '+79') {
            $phoneNumber = '+79' . substr($phoneNumber, 3);
        }

        return $phoneNumber;
    }

      public function Controller()
      {
        \Bitrix\Main\Loader::IncludeModule("crm");
          $bCheckRight = false;

          $ContactInfo = \Bitrix\Crm\ContactTable::GetList([
              'select' => ['ID'], 
            ]);

        while ($contact = $ContactInfo->fetch()){

          $contactId = '';
          $numer = '';
          $FieldPhoneBox = [];

             // получаем мульти поля
          $multiFields = \Bitrix\Crm\FieldMultiTable::getList([
              'filter' => [
              'ELEMENT_ID' => $contact['ID'],
              'ENTITY_ID' => 'CONTACT',
              ],
          ]);

          $contactPool[$contact['ID']] = new \CCrmContact($bCheckRight);

          $state = false;

          // adapt мульти поля
          while ($fieldItem = $multiFields->fetch())
          {
              if($fieldItem['TYPE_ID'] == 'PHONE'){
        
              $numer = $fieldItem['VALUE'];
              // валидация номера телефона
              $numer = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($numer)->format();
              $numer = $this->formatPhoneNumber($numer);
              if($FieldPhoneBox['FM']['PHONE'][$fieldItem['ID']]['VALUE'] !== $fieldItem['VALUE']){
                $state = true;

                $FieldPhoneBox['FM']['PHONE'][$fieldItem['ID']] = $PHONE_ITEM;
                $FieldPhoneBox['FM']['PHONE'][$fieldItem['ID']]['VALUE'] = $numer;
              }

              }
          }

          $contactId = $contact['ID'];
  

          if($state){
            print_r([$contactId,$FieldPhoneBox]);
            $contactPool[$contact['ID']]->Update(
              $contactId,
              $FieldPhoneBox,
            );
            unset($contactPool[$contact['ID']]);
          }

        }

      }
  }

  
  $instance = new CrmCEventContact(); 
  $instance->Controller();