<?php
/**
 * @author Isaev Danil
 * @package Isaev\Seolinks
 */
namespace Isaev\Seolinks;

class UserTypeSeo extends \CUserTypeString
{
    /**
     * @return array
     * The method returns an array describing the behavior of the custom property.
     * Метод возвращает массив описывающий поведение пользовательского свойства
     */
    public function getUserTypeDescription()
    {
        return array(
            'USER_TYPE_ID' => 'isaev_seolinks',                 // Уникальное название польз. поля
            'CLASS_NAME' => __CLASS__,                          // Обработчик польз. поля
            'DESCRIPTION' => 'SEO-Ссылки привзяка к разделу',   // Название поля
            'BASE_TYPE' => 'S',                                 // Тип поля 
        );
    }
 
    /**
     * User field output in section editing
     * Вывод польз. поля в редактировании раздела
     * @param $arUserField
     * @param $arHtmlControl
     * @return string
     */
    public function GetEditFormHTML($arUserField, $arHtmlControl)
    {
        // Проверяем активные у этого раздела ссылки и декодируем html сущности
        if (!empty($arHtmlControl['VALUE'])) {
            $value = (string) html_entity_decode($arHtmlControl['VALUE'], ENT_QUOTES);
            $arHtmlControl['VALUE'] = explode(";", $value);
        }
        // Запрашиваем все активные ссылки у которых заполнено название тега.
        $arResult = \Isaev\Seolinks\seolinksTable::getList(['filter' => ['!TAG_NAME' => false, '=ACTIVE' => 'Y'], 'select' => ['TAG_NAME', 'ID', 'FROM']])->fetchAll();
        
        // Созадем select элемент нашего свойства. 
        $result = '<select name="'.$arHtmlControl['NAME'].'[]" multiple size="'.$arUserField['SETTINGS']['SIZE'].'">';
        foreach ($arResult as $arItem) {
            $itemCurrent = null; // Ищем активные ссылки
            if (in_array($arItem['ID'], $arHtmlControl['VALUE'])) {
                $itemCurrent = 'selected';
            }
            // Выводим элемент
            $result .= "<option value='{$arItem[ID]}' {$itemCurrent}>";
            $result .= "[{$arItem[ID]}] {$arItem[TAG_NAME]} ({$arItem[FROM]})";
            $result .= "</option>";
        }
        $result .= '</select>';
        return $result;
    }
 
    /**
     * Actions when saving user fields
     * Действия при сохранении польз. поля
     * @param $arUserField
     * @param $value
     * @return string
     */
    public function OnBeforeSave($arUserField, $value)
    {
        return implode(";", $value); // При сохранении объединяем элементы массива в строку. (Т.к польз. поле - строка)
    }

    /**
     * Display in the list of sections (administration panel)
     * Вывод в списке разделов (панель администрирования)
     * @param $arUserField
     * @param $arHtmlControl
     * @return string
     */
    public function GetAdminListEditHTML($arUserField, $arHtmlControl)
    {
        return self::GetEditFormHTML($arUserField, $arHtmlControl); // Делаем его таким же как в редактировании
    }

    /**
     * The method should return safe HTML for displaying the property value in the list of elements of the administrative part.
     * Метод должен вернуть безопасный HTML отображения значения свойства в списке элементов административной части. 
     * @param array $arUserField
     * @param array $arHtmlControl
     * @return string
     */
    public function GetAdminListViewHTML($arUserField, $arHtmlControl)
    {
        return parent::GetAdminListViewHTML($arUserField, $arHtmlControl); // Оставим стандартный вывод поля - строка (CUserTypeString)
    }
    
    /**
     * User settings fields when creating it
     * Настройки польз. поля при его создании
     * @param array|bool $arUserField
     * @param array $arHtmlControl
     * @param $bVarsFromForm
     * @return string
     */
    public function GetSettingsHTML($arUserField, $arHtmlControl, $bVarsFromForm)
    {
        return parent::GetSettingsHTML($arUserField, $arHtmlControl, $bVarsFromForm); // Оставим стандартный вывод поля - строка (CUserTypeString)
    }
}
