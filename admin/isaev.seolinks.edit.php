<?php
/**
 * @author Isaev Danil
 * @package Isaev\Seolinks
 * 
 * View edit element link
 * Вывод редактирования элемента ссылки
 */

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Application;
use \Bitrix\Main\Page;
use \Bitrix\Main\Config;
use \Isaev\SeoLinks\SeolinksTable;
use \Bitrix\Main\Loader;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
Loc::loadMessages(__FILE__);
Loader::includeModule('isaev.seolinks');

$selfFolderUrl = '/bitrix/admin/';
$listUrl = $selfFolderUrl."isaev.seolinks.list.php";

$instance = Application::getInstance();
$context = $instance->getContext();
$request = $context->getRequest();
$server = $context->getServer();
$documentRoot = Application::getDocumentRoot();
$arColumn = \Isaev\SeoLinks\SeolinksTable::getEntity()->getFields();
$id = (int)$request->get('ID');


$arResult = [];
$errorMessage = '';

/**
 * Обработка POST действий
 */
if ($server->getRequestMethod() == "POST" && ($request->get('save') !== null || $request->get('apply') !== null) && check_bitrix_sessid()) {
    foreach ($arColumn as $code => $column) {
        unset($value);
        if ($column->getDataType() == 'boolean') {
            $arResult[$code] = ($request->get($code) == 'Y') ? 'Y' : 'N';
        } elseif ($code == 'ID') {
            continue;
        } else {
            $arResult[$code] = $request->getPost($code);
        }
    }

    /**
     * Обработка действий _POST запросов
     */
    if ($errorMessage === '') {
        if ($id > 0) {
            $result = SeolinksTable::update($id, $arResult);
        } else {
            $result = SeolinksTable::add($arResult);
            $id = $result->getId();
        }

        if ($result->isSuccess()) {
            if (strlen($request->getPost("apply")) == 0) {
                LocalRedirect($listUrl);
            } else {
                $applyUrl = $selfFolderUrl."isaev.seolinks.edit.php?&ID=".$id;
                $applyUrl = $applyUrl;
                LocalRedirect($applyUrl);
            }
        } else {
            $errorMessage .= implode("\n", $result->getErrorMessages());
        }
    }
}

/**
 * Получаем данные если пришел ID
 * Или мы копируем
 */
if ($id > 0 && !$request->isPost()) {
    $arResult = SeolinksTable::getList(['filter' => ['=ID' => $id]])->fetchRaw();
}elseif($request->get('action') == 'copy' && $request->get('copyID')){
    $arResult = SeolinksTable::getList(['filter' => ['=ID' => $request->get('copyID')]])->fetchRaw();
    unset($arResult['ID']);
}

/**
 * Визуальная часть
 */
require($documentRoot."/bitrix/modules/main/include/prolog_admin_after.php");
$APPLICATION->SetTitle(($id > 0) ? Loc::getMessage("isaev.seolinks_EDIT", array("#ID#" => $id)) : Loc::getMessage("isaev.seolinks_ADD"));

$aTabs[] = [
    "DIV" => "edit1",
    "TAB" => Loc::getMessage("isaev.seolinks_TAB"),
    "ICON" => "sale",
    "TITLE" => ($id > 0) ? Loc::getMessage("isaev.seolinks_EDIT", array("#ID#" => $id)) : Loc::getMessage("isaev.seolinks_ADD"),
];

$tabControl = new \CAdminForm("tabControl", $aTabs);

/**
 * start navigation buttons
 */
$aMenu = array(
  array(
    "TEXT" => Loc::getMessage("isaev.seolinks_LIST"),
    "LINK" => $listUrl,
    "ICON" => "btn_list"
  )
);
if ($id > 0) {
    $aMenu[]        =  array("SEPARATOR" => "Y");
    $deleteUrl      =  $selfFolderUrl."isaev.seolinks.list.php?action=delete&ID_ITEM=".$id."&".bitrix_sessid_get();
    $copyUrl        =  $selfFolderUrl."isaev.seolinks.edit.php?action=copy&copyID=".$id."&".bitrix_sessid_get();
    $buttonAction   =  "LINK";

    $aMenu[] = array(
        "TEXT" => Loc::getMessage("isaev.seolinks_COPY"),
        $buttonAction => $copyUrl,
        "WARNING" => "N",
        "ICON" => "btn_copy"
    );
    $aMenu[] = array(
        "TEXT" => Loc::getMessage("isaev.seolinks_DELETE"),
        $buttonAction => "javascript:if(confirm('".Loc::getMessage("isaev.seolinks_CONFIRM_DELETE")."')) top.window.location.href='".$deleteUrl."';",
        "WARNING" => "Y",
        "ICON" => "btn_delete"
  );
}
$contextMenu = new \CAdminContextMenu($aMenu);
$contextMenu->Show();
if ($errorMessage !== '') {
    \CAdminMessage::ShowMessage(array("DETAILS"=>$errorMessage, "TYPE"=>"ERROR", "MESSAGE"=>Loc::getMessage("isaev.seolinks_ERROR"), "HTML"=>true));
}


/**
 * start tab edit
 */
$tabControl->BeginEpilogContent();
echo bitrix_sessid_post();
?>
<input type="hidden" name="Update" value="Y">
<input type="hidden" name="ID" value="<?=$id;?>" id="ID">
<?php
$tabControl->EndEpilogContent();
$actionUrl = $APPLICATION->GetCurPage()."?ID=".$id;
$tabControl->Begin(array("FORM_ACTION" => $actionUrl));
$tabControl->BeginNextFormTab();
$sizeColumn = 100; // Ширина колонк
// формирвем input
foreach ($arColumn as $code => $column) {
    $value = null; // Обнуляем т.к значение мы получаем из другого массива
    if ($column->getDataType() == 'boolean') {
        $value = isset($arResult[$code]) ? $arResult[$code] : 'Y';
        $tabControl->AddCheckBoxField($code, $column->getTitle().':', ($column->isRequired() ? true : false), 'Y', $value === 'Y');
    } elseif ($code == 'ID') {
        $value = $request->get($code) ? $request->get($code) : $arResult[$code];
        $tabControl->AddViewField($code, $column->getTitle().':', $value, ($column->isRequired() ? true : false));
    } elseif ($code == 'TEXT') {
        $value = $request->get($code) ? $request->get($code) : $arResult[$code];
        $tabControl->AddTextField($code, $column->getTitle().':', $value, ['cols' => $sizeColumn, 'rows' => 10], ($column->isRequired() ? true : false));
    } else {
        $value = $request->get($code) ? $request->get($code) : $arResult[$code];
        $tabControl->AddEditField($code, $column->getTitle().':', ($column->isRequired() ? true : false), array('size' => $sizeColumn), $value);
    }
}

$tabControl->Buttons(array("back_url" => $listUrl));
$tabControl->Show();
/**
 * end tab edit
 */
require($documentRoot."/bitrix/modules/main/include/epilog_admin.php");
