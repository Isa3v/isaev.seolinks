<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * @author Isaev Danil
 * @package Isaev\Seolinks
 * 
 * @param arResult['ITEMS'] - массив ссылок которые выбраны через параметра ID
 */
?>
<ul>
    <?foreach ($arResult['ITEMS'] as $arItem) {?>
        <li><a href="<?=$arItem['FROM']?>" data-id="<?=$arItem['ID']?>"><?=$arItem['TAG_NAME']?></a></li>
    <?}?>
</ul>