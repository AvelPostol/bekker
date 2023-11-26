<?php
namespace BazisCM;

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);

$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/bitrix";
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

// подключение классов
require_once ('head.php');

use Bitrix\Main\Loader;
use Bitrix\Main\Type;

/**
  * Получаем сделку -> проверяем условия, получаем ID ответственного пользователя
  * Получение ID салона пользователя  
  *
  */

  class CrmCEvent
  {
      private $Base;
      private $Main;
  
      public function __construct() {
        $this->Main = new \BazisCM\Main();
        $this->Base = new \BazisCM\Workspace\Tools\Base();
        $this->Crud = new \BazisCM\Workspace\Bitrix\Crud();
      }

      public function GetManager($data) {
          if (\Bitrix\Main\Loader::IncludeModule("main")) {

              $ManagerID = $data['deal']['ASSIGNED_BY_ID'];
              $managerInfo = \Bitrix\Main\UserTable::GetList([
                  'select' => ['UF_BASIS_SALON'],
                  'filter' => ['ID' => $ManagerID] 
              ]);

              $stage = $data['deal']['STAGE_ID'];
              $category = $data['deal']['CATEGORY_ID'];

              // id   deal	user	numer	groupy  timest
              foreach ($managerInfo as $fields) {
                $manager = $fields;
              }
              
              $item['deal'] = $data['deal']['ID'];
              $item['user'] = $data['deal']['ASSIGNED_BY_ID'];
              $item['groupy'] = $manager['UF_BASIS_SALON'];
              
              $HDealitem = $this->GetHistoryDeal($data['deal']['ID']);
              
              if(isset($HDealitem) && !empty($HDealitem)){
                $HDeal = $HDealitem[0];
              }

              print_r(['$HDeal' => $HDeal]);

              if(isset($HDeal) && !empty($HDeal)){ // если по сделке уже есть записи

                print_r(['1-']);
                
                if(intval($ManagerID) !== intval($HDeal['user'])){ // если сменился ответственный 
                  print_r(['1-+']);
                  $this->Main->Conroller(['manager' => $manager, 'deal' => $data['deal'], 'test' => $item]);
                }
                
                if($HDeal['groupy'] !== $manager['UF_BASIS_SALON']){ // если сменилась группа
                  print_r(['22-']);
                  $this->Main->Conroller(['manager' => $manager, 'deal' => $data['deal'], 'test' => $item]);
                }

                if($data['deal']['UF_CRM_1694018792723'] == NULL){ // номер договора пуст
                  print_r(['33-']);
                  $this->Main->Conroller(['manager' => $manager, 'deal' => $data['deal'], 'test' => $item]);
                }

              }
              else{ // истории еще нет
                print_r(['2-']);
                // если в нужной стадии
                if ((($category == '11') && ($stage == 'C11:NEW')) || ($category == '12') && ($stage == 'C12:PREPAYMENT_INVOIC')) {
                  if($data['deal']['UF_CRM_1694018792723'] == NULL){ // номер договора пуст
                    $this->Main->Conroller(['manager' => $manager, 'deal' => $data['deal'], 'test' => $item]);
                  }
                  if (strpos($data['deal']['UF_CRM_1694018792723'], $manager['UF_BASIS_SALON']) == false) { // если сменился ответственный
                    $this->Main->Conroller(['manager' => $manager, 'deal' => $data['deal'], 'test' => $item]);
                  }

                } else { // проверяем была ли смена ответственного
                  if (strpos($data['deal']['UF_CRM_1694018792723'], $manager['UF_BASIS_SALON']) == false) { // если сменился ответственный
                    $this->Main->Conroller(['manager' => $manager, 'deal' => $data['deal'], 'test' => $item]);
                  }
                }

              }
          }
      }

      public function GetHistoryDeal($deal) {

        try{
          
          $prefix = $p['id_crm_deal'];
          $prefix_resp = $p['id_responsible'];

          // id	deal	user!	numer	groupy!	timest	

          $managerQuery = "SELECT * FROM history_deal_numer WHERE deal='$deal' ORDER BY timest DESC LIMIT 1";
          $managerResult = $this->Crud->Get(['request' => $managerQuery]);
    
          if (!$managerResult) {
              return null;
          }
    
          return $managerResult;
    
        } catch (\Exception $e) { 
            return null; 
        }
          
      }
    

      public function Check($arFields){
        $instance = new CrmCEvent();
        $manager = $instance->GetManager([
          'deal' => $arFields,
          ]);
      }

      public static function Controller(&$arFields)
      {
 
        try{
          $instance = new CrmCEvent(); 
          $manager = $instance->Check($arFields);
        }catch (Exception $e) {
          
        }
    
      }
  }