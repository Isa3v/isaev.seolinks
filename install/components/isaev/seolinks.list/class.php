<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @author Isaev Danil
 * @package Isaev\Seolinks
 */

use Bitrix\Main;
use Bitrix\Main\Localization\Loc as Loc;

class StandardElementListComponent extends CBitrixComponent
{
    protected $cacheKeys = []; // кешируемые ключи arResult
    protected $cacheAddon = []; // дополнительные параметры, от которых должен зависеть кеш
    protected $returned; // возвращаемые значени

    /**
     * выполняет логику работы компонента
     */
    public function executeComponent()
    {
        global $APPLICATION;
        try {
            $this->checkModules();
            $this->checkParams();
            if ($this->arParams['AJAX'] == 'Y') {
                $APPLICATION->RestartBuffer();
            }
            if (!$this->readDataFromCache()) {
                $this->getResult();
                $this->putDataToCache();
                $this->includeComponentTemplate();
            }
            if ($this->arParams['AJAX'] == 'Y') {
                die();
            }
            return $this->returned;
        } catch (Exception $e) {
            $this->abortDataCache();
            ShowError($e->getMessage());
        }
    }

    
    /**
     * определяет читать данные из кеша или нет
     * @return bool
     */
    protected function readDataFromCache()
    {
        global $USER;
        if ($this->arParams['CACHE_TYPE'] == 'N') {
            return false;
        }

        if (is_array($this->cacheAddon)) {
            $this->cacheAddon[] = $USER->GetUserGroupArray();
        } else {
            $this->cacheAddon = array($USER->GetUserGroupArray());
        }
        return !($this->startResultCache(false, $this->cacheAddon, md5(serialize($this->arParams))));
    }

    /**
     * кеширует ключи массива arResult
     * Сохраним вне кеша мета теги и другие необходимые данные
     */
    protected function putDataToCache()
    {
        if (is_array($this->cacheKeys) && sizeof($this->cacheKeys) > 0) {
            $this->SetResultCacheKeys($this->cacheKeys);
        }
    }

    /**
     * прерывает кеширование
     */
    protected function abortDataCache()
    {
        $this->AbortResultCache();
    }
    
    /**
     * проверяет подключение необходиимых модулей
     * @throws LoaderException
     */
    protected function checkModules()
    {
        if (!Main\Loader::includeModule('isaev.seolinks')) {
            throw new Main\LoaderException(Loc::getMessage('STANDARD_ELEMENTS_LIST_CLASS_IBLOCK_MODULE_NOT_INSTALLED'));
        }
    }
    
    /**
     * проверяет заполнение обязательных параметров
     * @throws SystemException
     */
    protected function checkParams()
    {
        if ($this->arParams['ID'] <= 0 && strlen($this->arParams['ID']) <= 0) {
            throw new Main\ArgumentNullException('ID');
        }
    }
    
    /**
     * получение результатов
     */
    protected function getResult()
    {
        if (!empty($this->arParams['SORT_FIELD1'])) {
            $sort[$this->arParams['SORT_FIELD1']] = $this->arParams['SORT_DIRECTION1'];
        } else {
            $sort = ['SORT' => 'ASC'];
        }
        if (!empty($this->arParams['SORT_FIELD2'])) {
            $sort[$this->arParams['SORT_FIELD2']] = $this->arParams['SORT_DIRECTION2'];
        }

        if (!empty($this->arParams['FIELDS'])) {
            // Если в компоненте пришли ключи, то сравним с ORM, чтоб выдавались только существующие ключи
            $select = array_intersect($this->arParams['FIELDS'], array_keys(\Isaev\Seolinks\seolinksTable::getEntity()->getFields()));
        } else {
            $select = ['ID', 'FROM', 'TAG_NAME', 'GROUP_NAME'];
        }
        
        $arResult = \Isaev\Seolinks\seolinksTable::getList(['filter' => ['=ACTIVE' => 'Y', '=ID' => $this->arParams['ID']], 'order' => $sort, 'select' => $select])->fetchAll();
        $this->arResult['ITEMS'] = $arResult;
    }
}
