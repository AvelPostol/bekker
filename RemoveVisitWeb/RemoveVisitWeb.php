
<?php
$_SERVER["DOCUMENT_ROOT"] = "/mnt/data/bitrix";
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Type;

class RemoveVisitWeb
{
    public static function GetPullDeal($ParamForSearch)
      {

        \CModule::IncludeModule('crm'); 

        $arDeals=\Bitrix\Crm\DealTable::getList($ParamForSearch)->fetchAll();

        
        $deals=[];
        foreach($arDeals as $deal){
            $deals=$deal;
        }
        if (isset($deals)) {
            return $deals;
        }
        else{
             return NULL;
        }

        return NULL;
    }

   public static function main()
   {

    global $USER;
    if ($USER->IsAuthorized()) {
        CUser::SetLastActivityDate($USER->GetID());
        $user_id = $USER->GetID();
    }

    $rsUsers = \Bitrix\Main\UserTable::GetList([
        'select' => ['*'],
        'filter' => ['ID' => $user_id]
    ])->fetch();

    $dep = \CIntranetUtils::GetUserDepartments($user_id);

    // Проверим является ли страница детальной карточкой CRM через функционал роутинга компонентов
    $engine = new \CComponentEngine();
    $page = $engine->guessComponentPath(
        '/crm/',
        ['detail' => '#entity_type#/details/#entity_id#/'],
        $variables
    );


   ////////////////////////////////////////////////////



    if (!CModule::IncludeModule('crm'))
    {
        ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
        return;
    }


    // get role list
    $arResult['PATH_TO_ROLE_ADD'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
        array(
            'role_id' => 0
        )
    );
    $arResult['ROLE'] = array();
    $obRes = CCrmRole::GetList(['ID' => 'DESC',], ['=IS_SYSTEM' => 'N']);
    while ($arRole = $obRes->Fetch())
    {
        $arRole['PATH_TO_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
            array(
                'role_id' => $arRole['ID']
            )
        );
        $arRole['PATH_TO_DELETE'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
            array(
                'role_id' => $arRole['ID']
            )),
            array('delete' => '1', 'sessid' => bitrix_sessid())
        );
        $arRole['NAME'] = htmlspecialcharsbx($arRole['NAME']);
        $arResult['ROLE'][$arRole['ID']] = $arRole;
    }

    // get role relation
    $arResult['RELATION'] = array();
    $arResult['RELATION_ENTITY'] = array();
    $obRes = CCrmRole::GetRelation();
    while ($arRelation = $obRes->Fetch())
    {
        if (isset($arResult['ROLE'][$arRelation['ROLE_ID']]))
        {
            $arResult['RELATION'][$arRelation['RELATION']] = $arRelation;
            $arResult['RELATION_ENTITY'][$arRelation['RELATION']] = true;
        }
    }

    $CAccess = new CAccess();
    $arNames = $CAccess->GetNames(array_keys($arResult['RELATION_ENTITY']));
    foreach ($arResult['RELATION'] as &$arRelation)
    {
        //Issue #43598
        $arRelation['NAME'] = htmlspecialcharsbx($arNames[$arRelation['RELATION']]['name']);
        $providerName = $arNames[$arRelation['RELATION']]['provider'];
        if(!empty($providerName))
        {
            $arRelation['NAME'] = '<b>'.htmlspecialcharsbx($providerName).':</b> '.$arRelation['NAME'];
        }
    }


    $roles = [
        '37',
        '36',
        '31',
        '11',
        '10',
        '5',
    ];

    $table = \Bitrix\Main\UserAccessTable::getList([
        'select'  => ['*'],
        'filter' => ['USER_ID' => $user_id]
    ])->FetchAll();

    $RELATION = [];
    foreach($arResult['RELATION'] as $key => $val){
        if(in_array($val['ROLE_ID'],$roles)){
            $RELATION[] = $val['RELATION'];
        }
    }

    foreach($table as $userT){
        if(!in_array($userT['ACCESS_CODE'], $RELATION)){
            if(!$USER->IsAdmin()){
                return;
            };
        }
    }



   ////////////////////////////////////////////////////



    
    

    // Если страница не является детальной карточкой CRM прервем выполенение
    if ($page !== 'detail') {
        return;
    }

    // Проверим валидность типа сущности
    $allowTypes = ['deal'];
    $variables['entity_type'] = strtolower($variables['entity_type']);
    if (!in_array($variables['entity_type'], $allowTypes, true)) {
        return;
    }

    // Проверим валидность идентификатора сущности
    $variables['entity_id'] = (int) $variables['entity_id'];
    if (0 >= $variables['entity_id']) {
        return;
    }

    $deal = self::GetPullDeal(
        [
            'select' => ['*'],
            'filter' => [
                'ID' => $variables['entity_id']
            ],
        ]
    );

    $stages = ['C11:UC_53GWRD', 'C11:UC_PVWFY0', 'C11:NEW', 'C12:NEW', 'C12:PREPARATION', 'C12:PREPAYMENT_INVOIC'];

    if(!in_array($deal['STAGE_ID'], $stages)){
        return;
    }
    
    //self::writeLog($variables);
    $assetManager = \Bitrix\Main\Page\Asset::getInstance();

    // Подключаем js файл
    $assetManager->addJs('/local/php_interface/classes/RemoveVisitWeb/js/script.js');

    $assetManager->addCss('/local/php_interface/classes/RemoveVisitWeb/css/style.css');

    // Подготовим параметры функции
    $jsParams = \Bitrix\Main\Web\Json::encode(
        $variables,
        JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE
    );

    // Инициализируем добавление таба
    $assetManager->addString('
        <script>
        BX.ready(function () {
            if (typeof initialize_foo_crm_detail_tab === "function") {
                initialize_foo_crm_detail_tab('.$jsParams.');
            }
        });
        </script>
    ');
   }
   public static function writeLog($data) {
    $logFile = '/mnt/data/bitrix/local/php_interface/classes/RemoveVisitWeb/log.txt';
    $formattedData = var_export($data, true);
    file_put_contents($logFile, '<?php $array = ' . $formattedData . ';', FILE_APPEND);
}
}