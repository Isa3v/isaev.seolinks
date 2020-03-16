<?php
/**
 * @author Isaev Danil
 * @package Isaev\Seolinks
 * 
 * This file creates a tab with this module in the administrative menu
 * Даныый файл создает в административном меню вкладку с данным модулем
 */
use \Bitrix\Main\Localization\Loc;

if(\Bitrix\Main\ModuleManager::isModuleInstalled('isaev.seolinks')){
    Loc::loadMessages(__FILE__);
    $aMenu = [
        [
            'parent_menu' => 'global_menu_services', // Родитель меню
            'sort' => 0,
            "icon" => "statistic_icon_events",
            'text' => Loc::getMessage('isaev.seolinks_list'),
            'url' => 'isaev.seolinks.list.php',
            'items_id' => 'menu_isaev_seolinks',
            // Дочерние элементы
            "items" => [ 
                [
                   "text" => Loc::getMessage("isaev.seolinks_add_new"),
                   "url" => "isaev.seolinks.edit.php",
                ],
            ]
        ],
    ];
    return $aMenu;
}
return false;
