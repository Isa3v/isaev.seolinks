<?php
/**
 * @author Isaev Danil
 * @package Isaev\Seolinks
 */
namespace Isaev\Seolinks;

use \Bitrix\Main\Localization\Loc;
use \Isaev\Seolinks\SeolinksTable;
use \Bitrix\Main\Application;
use \Bitrix\Main\Web\Uri;
use \Bitrix\Main\Context;
use \Bitrix\Main\HttpRequest;

Loc::loadMessages(__FILE__);

class Handler
{
    /**
     * Получить текущую ссылку и подменить ее контент
     * Метод подключен к событию "OnPageStart" при установке в /install/index.php
     * Используется до иницализации контекста и позволяет подменить выводимый контент
     * ТОЛЬКО для подмены контента! Подмена мета-тегов происходит в другом событии и методе!
     */
    public function findAndSpoof()
    {
        $arServer = Context::getCurrent()->getServer()->toArray(); // Получаем массив инфо о сервере

        // Search for a link in the table
        // Поиск ссылки в таблице
        $arSpoofPage = SeolinksTable::getList([
            'select' => ['FROM','TO','REDIRECT'],       // Нам для подмены нужны только эти поля
            'filter' => [
                'LOGIC' => 'OR',                        // Используем выражение OR (ИЛИ)
                [
                    '!TO' => false,                     // Оригинал (подменяемый) не пустой
                    '=FROM' => $arServer['SCRIPT_URL'], // Ссылка для вывода подмены - текущаяя
                    '=ACTIVE' => true                   // Ссылка активна
                ],
                [
                    '=TO' => $arServer['REQUEST_URI'],  // Оригинал (подменяемый) текущая ссылка
                    '!FROM' => false,                   // Ссылка для вывода подмены - не пустая
                    '=ACTIVE' => true                   // Ссылка активна
                ]
    
            ] 
        ])->fetchRaw();
        if (!empty($arSpoofPage)) {                                                         // Если нашли в таблцие ссылку
            if ($arSpoofPage['FROM'] == $arServer['SCRIPT_URL']) {                          // Если текущая ссылка служит для вывода подмены
                self::setSpoof($arSpoofPage['TO'], $arSpoofPage['FROM']);                   // Вызываем метод подмены контента
            } elseif ($arSpoofPage['TO'] == $arServer['REQUEST_URI']) {                     // Если текущая ссылка - подменяемая
                if ($arSpoofPage['REDIRECT'] == 'Y') {                                      // Если у ссылки указан редирект с подменяемой, на подмену
                    LocalRedirect($arSpoofPage['FROM'], false, '301 Moved permanently');
                }
            }
        }
    }


    /**
     * Substituting the displayed content by reference
     * Подменяем отображаемый контент по ссылке
     *
     * @param originalLink [string] [Link with which we are pulling content]
     * @param spoofLink    [string] [The link to which we display]
     *
     * Called just prolog_before event
     * Обязательно вызывать перед prolog_before
     */
    public static function setSpoof($originalLink, $spoofLink)
    {
        // Создание объекта Uri из адреса текущей страницы:
        $context = Context::getCurrent();
        $request = $context->getRequest();
        if (!empty($originalLink) && !empty($spoofLink)) {
            $server = $context->getServer();
            $arServer = $server->toArray();
            // Заменяем значения для подмены
            $arServer['REQUEST_URI'] = $originalLink;
            $arServer['QUERY_STRING'] = parse_url($originalLink)['query'];
            parse_str($arServer['QUERY_STRING'], $arQuery); // Разбирает строку в переменные
            
            // Further we will know without queries to the database that this is a spoofed page
            // Далее мы будем без запросов знать к базе данных, что это поддельная страница
            $arServer['BXISAEVSEO_SPOOF'] = $spoofLink;
            
            // Add event before spoofing contentt
            // Событие перед подменой контента
            $event = new \Bitrix\Main\Event("isaev.seolinks", "beforeFindSpoof", $arServer);
            $event->send();
            foreach ($event->getResults() as $eventResult) {
                if ($eventResult->getType() == \Bitrix\Main\EventResult::ERROR) { // если обработчик вернул ошибку, ничего не делаем
                    continue;
                }
                $arServer = $eventResult->getParameters();
            }
            $server->set($arServer);
            $context->initialize(new HttpRequest($server, $arQuery, [], [], $_COOKIE), $context->getResponse(), $server);
            $request->getRequestUri();

            /**
             *  [Bitrix Event after]
             *  \Bitrix\Main\EventManager::getInstance()->addEventHandler('isaev.seolinks','afterSpoofing','afterSpoofingFunction');
             *  function afterSpoofingFunction($event){
             *    $event->getParameters();
             *  }
             */
            $event = new \Bitrix\Main\Event("isaev.seolinks", "afterSpoofing", ['arServer' => $arServer]);
            $event->send();
        }
    }

    /**
     * Метод назначени на выполнение события "OnEpilog" при установке в /install/index.php
     * Служит для замены мета-тегов
     */
    public static function setMeta()
    {
        // Find out if the page is spoofed
        // Узнаем является ли страница подмененной
        $arServer = Context::getCurrent()->getServer()->toArray();
        // Берем или подмененную ссылку или оригинальную, если нет подмененной
        $currentPage = (!empty($arServer['BXISAEVSEO_SPOOF']) ? $arServer['BXISAEVSEO_SPOOF'] : $arServer['REQUEST_URI']);
        // Ищем ссылку в таблице
        $arResult = SeolinksTable::getList(['filter' => ['=ACTIVE' => true, '=FROM' => $currentPage]])->fetchRaw();

        if (!empty($arResult)) {
            global $APPLICATION;
            $arMeta = [];

            // <h1>
            if (!empty($arResult['META_H1'])) {
                $arMeta['h1'] = $arResult['META_H1'];
            }
            // <title>
            if (!empty($arResult['META_TITLE'])) {
                $arMeta['title'] = $arResult['META_TITLE'];
            }
            // <meta name="description">
            if (!empty($arResult['META_DESCRIPTION'])) {
                $arMeta['description'] = $arResult['META_DESCRIPTION'];
            }
            // Add bread crumbs
            // Добавляем хлебные крошки
            if (!empty($arResult['META_H1']) && $arResult['CHAIN_ITEM'] == 'Y') {
                $arMeta['chain_item'] = ['title' => $arResult['META_H1']];
            }

            /**
              *  [Bitrix Event before Meta]
              *  An event for the ability to add tags
              *  Событие для возможности добавления тегов
              *  Example:
              *  \Bitrix\Main\EventManager::getInstance()->addEventHandler('isaev.seolinks','beforeMeta','beforeMetaFunction');
              *  function beforeMetaFunction($event){
              *    $arMeta = $event->getParameters();
              *    $arResult['description'] = 'test';
              *    return $arResult;
              *  }
              */
            $event = new \Bitrix\Main\Event("isaev.seolinks", "beforeMeta", $arMeta);
            $event->send();
            foreach ($event->getResults() as $eventResult) {
                if ($eventResult->getType() == \Bitrix\Main\EventResult::ERROR) {
                    continue;
                }
                $arMeta = $eventResult->getParameters();
            }
            
            // Set meta tags
            // Устанавливаем мета-теги
            if (!empty($arMeta['chain_item']['title'])) {
                $APPLICATION->AddChainItem($arMeta['chain_item']['title']);
            }
            foreach ($arMeta as $name => $value) {
                if ($name == 'h1') {
                    $APPLICATION->SetTitle($value);
                } else {
                    $APPLICATION->SetPageProperty($name, $value);
                }
            }
        }
    }

    /**
     * Метод назначени на выполнение события "OnPanelCreate" при установке в /install/index.php
     * Добляет в панели управления кнопки управления СЕО-ссылками
     */
    public static function eventSetButtonPanel()
    {
        global $APPLICATION;
        // Путь к редактированию элементов
        $linkModuleEdit = '/bitrix/admin/isaev.seolinks.edit.php';
        $linkModuleList = '/bitrix/admin/isaev.seolinks.list.php';

        // Find out if the page is spoofed
        // Узнаем является ли страница подмененной
        $arServer = Context::getCurrent()->getServer()->toArray();
        // Берем или подмененную ссылку или оригинальную, если нет подмененной
        $currentPage = (!empty($arServer['BXISAEVSEO_SPOOF']) ? $arServer['BXISAEVSEO_SPOOF'] : $arServer['REQUEST_URI']);

        $arResult = SeolinksTable::getList(['filter' => ['=ACTIVE' => true, '=FROM' => $currentPage], 'select' => ['ID']])->fetchRaw();

        // Добавляем подпункты кнопки
        $arMenu = [];
        $arMenu[] = [
            "TEXT"      => Loc::getMessage('isaev.seolinks_HANDLER_LIST'),
            "ACTION"    => "window.location = '{$linkModuleList}'",
        ];

        if (!empty($arResult['ID'])) {      // Если это SEO-ссылка
            $hrefButton = "{$linkModuleEdit}?&ID={$arResult[ID]}";
            $icon = 'bx-panel-short-url-icon';
            $text = Loc::getMessage('isaev.seolinks_HANDLER_EDIT');
            $arMenu[] = [
                "TEXT"      => Loc::getMessage('isaev.seolinks_HANDLER_ADD'),
                "ACTION"    => "window.location = '{$linkModuleEdit}'",
            ];
        } else {      // На любой другой странице
            $hrefButton = "{$linkModuleEdit}?fields[TO]={$currentPage}";
            $icon = "icon-wizard";
            $text = Loc::getMessage('isaev.seolinks_HANDLER_ADD');
        }

        // У нас всегда будет кнопка добавить ссылку
        $APPLICATION->AddPanelButton(
            [
                "ID"        => 53134,       // Определяет уникальность кнопки (Рандомный ID)
                "TEXT"      => $text,       // Текст кнопки
                "MAIN_SORT" => 3000,        // Индекс сортировки для групп кнопок
                "SORT"      => 5,           // Сортировка внутри группы
                "HREF"      => $hrefButton, // Ссылка
                "ICON"      => $icon,       // Название CSS-класса с иконкой кнопки
                "MENU"      => $arMenu      // Массив меню
            ]
        );
    }
}
