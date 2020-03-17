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
     * Getting the current page without calling the $APPLICATION and $_GLOBAL
     * Получение текущей страницы без вызова $APPLICATION и $_GLOBAL
     * @param query [boolen] [if true, it will _get] [если true, отдаст ссылку с get запросом]
     */
    public static function getCurrentPage($query = false)
    {
        $request = Context::getCurrent()->getRequest();
        $uri = new Uri($request->getRequestUri());
        // Check if GET Requests
        // Проверяем отдавать ли GET запрос
        if ($query === true) {
            $curPage = $uri->getUri();
        } else {
            $curPage = $uri->getPath();
        }
        return urldecode($curPage);
    }

    /**
     * Get the current link and checks for its presence in the link spoofing table
     * Получить текущую ссылку и подменить ее контент
     * Метод подключен к событию "OnPageStart" при установке в /install/index.php
     * Используется до иницализации контекста и позволяет подменить выводимый контент 
     */
    public function findAndSpoof()
    {
        // Current page and Get request
        // Текущая ссылка без GET запроса
        $curPage = self::getCurrentPage(false);

        // Search for a link in the table
        // Поиск ссылки в таблице
        $arSpoofPage = SeolinksTable::getList(['filter' => ['=ACTIVE' => true, '!TO' => false, '=FROM' => $curPage], 'select' => ['FROM','TO']])->fetchRaw();
        if (!empty($arSpoofPage)) {
            self::setSpoof($arSpoofPage['TO'], $arSpoofPage['FROM']);
        } elseif ($arOriginalPage = SeolinksTable::getList(['filter' => ['=ACTIVE' => true, '=TO' => $curPage, '!FROM' => false], 'select' => ['FROM','TO','REDIRECT']])->fetchRaw()) {
            if ($arOriginalPage['REDIRECT'] == 'Y') {
                LocalRedirect($arOriginalPage['FROM'], false, '301 Moved permanently');
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
            $arServer['REQUEST_URI'] = $originalLink;
            $arServer['QUERY_STRING'] = parse_url($originalLink)['query'];
            parse_str($arServer['QUERY_STRING'], $arQuery);
            
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

        // Если это подмененная ссылка
        $currentPage = $arServer['BXISAEVSEO_SPOOF'];
        
        // Если нет, то узнаем и о ней.
        if (empty($currentPage)) {
            $currentPage = self::getCurrentPage(true);
        }

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
            if (!empty($arResult['META_H1'] && $arResult['ADD_CHAIN'])) {
                $arMeta['chain_item'] = ['url' => $currentPage, 'title' => $originalCurPage['UF_NEW']];
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
            if (!empty($arMeta['chain_item']['url'] && $arMeta['chain_item']['title'])) {
                $APPLICATION->AddChainItem($arMeta['chain_item']['url'], $arMeta['chain_item']['title']);
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
}
