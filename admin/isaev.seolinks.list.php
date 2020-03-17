<?php
/**
 * @author Isaev Danil
 * @package Isaev\Seolinks
 *
 * View list elements links
 * Вывод списка элементов ссылок
 */

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Grid\Options;
use \Bitrix\Main\UI\PageNavigation;
use \Isaev\SeoLinks\Seolinkstable;
use \Bitrix\Main\Application;
use \Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
Loader::includeModule('isaev.seolinks');
\Bitrix\Main\UI\Extension::load("ui.buttons");

$tableListID = 'isaev_seolinks_list';
$gridOption = new \Bitrix\Main\Grid\Options($tableListID);

$sort = $gridOption->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
$navParam = $gridOption->GetNavParams();

$nav = new PageNavigation('request_list');

// Turn off the button all the records and set the number of elementssetPageSize
// Отключаем кнопку все записи и устанавливаем кол-во элементов
$nav->allowAllRecords(true)->setPageSize($navParam['nPageSize'])->initFromUri();

// Обработка действий _POST запросов
$request = Application::getInstance()->getContext()->getRequest();

$postAction = $request->getPost("action_button_".$tableListID);
if ($postAction == 'delete' || $request->get("action") == 'delete' && $request->get("ID_ITEM")) {
    $idItem = ($request->getPost("ID_ITEM") ? $request->getPost("ID_ITEM") : $request->get("ID_ITEM"));
    SeolinksTable::delete($idItem);
}

/**
 * Получаем все поля нашей таблицы ORM
 */
$arColumnData = SeolinksTable::getEntity()->getFields();
$arFilter = [
    'offset' => $nav->getOffset(),
    'limit'  => $nav->getLimit(),
    'order'  => $sort['sort'],
    'count_total' => true
];
$res = SeolinksTable::getList($arFilter);
$nav->setRecordCount($res->getCount());

/**
 * Выборка колонок таблицы
 */
foreach ($arColumnData as $id => $arData) {
    $arColumn[] = [
        'id' => $id, 'name' => $arData->getTitle(), 'sort' => $id, 'default' => true
    ];
    $arColumnKeys[$id] = $id;
}
/**
 * Выборка значений
 */
$arRows = [];
foreach ($res->fetchAll() as $key => $row) {
    foreach ($arColumnKeys as $id) {
        $arRows[$key]['data'][$id] = $row[$id];
        $arRows[$key]['data']['editable'] = true;
    }
    // Кнопки действий с элементами
    $arRows[$key]['actions'] = [
        [
            'ICONCLASS' => 'menu-popup-item-edit',
            'text'      => Loc::getMessage("isaev.seolinks_EDIT"),
            'onclick'   => 'document.location.href="/bitrix/admin/isaev.seolinks.edit.php?ID='.$row['ID'].'"',
            'default'   => true
        ],
        [
            'ICONCLASS' => 'menu-popup-item-copy',
            'text'      => Loc::getMessage("isaev.seolinks_COPY"),
            'onclick'   => 'document.location.href="/bitrix/admin/isaev.seolinks.edit.php?action=copy&copyID='.$row['ID'].'"'
        ],
        [
            'ICONCLASS' => 'menu-popup-item-delete',
            "TEXT"      => Loc::getMessage("isaev.seolinks_DELETE"),
            "ONCLICK"   => "if(confirm('".Loc::getMessage("isaev.seolinks_REMOVE_CONFIRM")."')) BX.Main.gridManager.getInstanceById('{$tableListID}').reloadTable('POST', {'action_button_{$tableListID}':'delete','ID_ITEM':'{$row[ID]}'});"
        ]
    ];
}
?>
<?require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");?>
<div class="adm-toolbar-panel-container">
	<div class="adm-toolbar-panel-flexible-space">
		<a href="/bitrix/admin/isaev.seolinks.edit.php" class="ui-btn ui-btn-primary"><?=Loc::getMessage("isaev.seolinks_ADD")?></a>
	</div>
</div>

<?php
/**
 * Displaying the visual part of the list
 * Вывод визуальной части  списка
 */
$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '',
    [
        'GRID_ID' => $tableListID,
        'COLUMNS' => $arColumn,
        'ROWS' => $arRows,
        'NAV_OBJECT' => $nav,
        'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '', ''),
        'PAGE_SIZES' => [
            ['NAME' => "5", 'VALUE' => '5'],
            ['NAME' => '10', 'VALUE' => '10'],
            ['NAME' => '20', 'VALUE' => '20'],
            ['NAME' => '50', 'VALUE' => '50'],
            ['NAME' => '100', 'VALUE' => '100']
        ],
        'AJAX_MODE' 				=> true,
        'SHOW_ROW_CHECKBOXES' 		=> false,
        'AJAX_OPTION_JUMP'          => false,
        'SHOW_CHECK_ALL_CHECKBOXES' => false,
        'SHOW_ROW_ACTIONS_MENU'     => true,
        'SHOW_GRID_SETTINGS_MENU'   => true,
        'SHOW_NAVIGATION_PANEL'     => true,
        'SHOW_PAGINATION'           => true,
        'SHOW_SELECTED_COUNTER'     => true,
        'SHOW_TOTAL_COUNTER'        => true,
        'SHOW_PAGESIZE'             => true,
        'SHOW_ACTION_PANEL'         => false,
        'ALLOW_COLUMNS_SORT'        => true,
        'ALLOW_COLUMNS_RESIZE'      => true,
        'ALLOW_HORIZONTAL_SCROLL'   => true,
        'ALLOW_SORT'                => true,
        'ALLOW_PIN_HEADER'          => true,
        'AJAX_OPTION_HISTORY'       => false
    ]
);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>