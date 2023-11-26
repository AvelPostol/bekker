console.log('-=-');
function initialize_foo_crm_detail_tab(params) {
    var paramsRemoveDealVisit = params.entity_id;
    var user_i = BX.message('USER_ID');
    params = params || {};

    // Убедимся что тип сущности передан
    if  (!params.entity_type) {
        return;
    }

    // Убедимся что идентификатор сущности передан
    if  (!params.entity_id) {
        return;
    }
    var tabData = {};
    // Идентификатор вкладки
    tabData.id = 'tab_foo';

    // Наименование вкладки
    tabData.name = 'Foo tab';

    // Контент вкладки, если мы хотим чтобы во вкладке был статичный контент передаем его сюда, можно через параметры функции, в противном случае данный параметр можно опустить
    tabData.html = '<div style="color: green">Foo tab content</div>';

    var id_elem = 'call_feedback_'+paramsRemoveDealVisit;

    let button = BX.create('a', {
        attrs: {
            className: 'ui-btn ui-btn-sm ui-btn-primary remove-btn-visit',
            id: id_elem
        },
        text: 'Встреча отменена'
    });

    let commentsBlock = document.querySelectorAll('.crm-entity-section-tabs .main-buttons-box');

    // Добавим созданный пункт меню к остальным пунктам меню
    BX.append(
        button,
        commentsBlock[0]
    );

    //BX.bind(button, 'click', BX.delegate(this.showPopup, this));

    window.BXDEBUG = true;

    var oPopup = new BX.PopupWindow('call_feedback_ooo', window.body, {
        content: '<div id="mainshadow"></div>'+'<h3>Отменить встречу?</h3>',
        closeIcon: {right: "20px", top: "10px"},
        zIndex: 0,
        offsetLeft: 0,
        offsetTop: 0,
        draggable: {restrict: false},
        overlay: {backgroundColor: 'black', opacity: '80' },  /* затемнение фона */
        buttons: [
        new BX.PopupWindowButton({
            text: "Да",
            className: "popup-window-button-accept",
            events: {click: function(){
                BX.ajax.post(
                    'https://test.kitchenbecker.ru/local/php_interface/classes/RemoveVisitWeb/ajax.php',
                    {
                        arParams: paramsRemoveDealVisit,
                        user: user_i
                    },
                ),
                this.popupWindow.close(); // закрытие окна
            }}
        }),
        new BX.PopupWindowButton({
            text: "Нет",
            className: "webform-button-link-cancel",
            events: {click: function(){
            this.popupWindow.close(); // закрытие окна
            }}
        })
        ]
    });
    oPopup.setContent(BX('hideBlock'));
    BX.bindDelegate(
        document.body, 'click', {className: 'remove-btn-visit' },
            BX.proxy(function(e){
                if(!e)
                e = window.event;
                oPopup.show();
                return BX.PreventDefault(e);
            }, oPopup)
    );


}



