<?
use \Bitrix\Main\ModuleManager;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\Application;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

Class isaev_seolinks extends CModule {
    const MODULE_ID = "isaev.seolinks";
    public $MODULE_ID = "isaev.seolinks";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public function __construct() {
        $arModuleVersion = array();
        include(dirname(__FILE__) . "/version.php");
        $this->MODULE_VERSION      = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME         = Loc::getMessage("isaev.seolinks_MODULE_NAME");
        $this->MODULE_DESCRIPTION  = Loc::getMessage("isaev.seolinks_MODULE_DESC");
        $this->PARTNER_NAME        = Loc::getMessage("isaev.seolinks_PARTNER_NAME");
        $this->PARTNER_URI         = Loc::getMessage("isaev.seolinks_PARTNER_URI");
    }
    public function installEvents() {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler("main", "OnPageStart", self::MODULE_ID, "\Isaev\Seolinks\Handler", "findAndSpoof");
        $eventManager->registerEventHandler('main', 'OnUserTypeBuildList',  self::MODULE_ID, "\Isaev\Seolinks\UserTypeSeo", "getUserTypeDescription");
        $eventManager->registerEventHandler("main", "OnEpilog", self::MODULE_ID, "\Isaev\Seolinks\Handler", "setMeta");
        return true;
    }
    public function unInstallEvents() {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler("main", "OnPageStart", self::MODULE_ID, "\Isaev\Seolinks\Handler", "findAndSpoof");
        $eventManager->unRegisterEventHandler('main', 'OnUserTypeBuildList',  self::MODULE_ID, "\Isaev\Seolinks\UserTypeSeo", "getUserTypeDescription");
        $eventManager->unRegisterEventHandler("main", "OnEpilog", self::MODULE_ID, "\Isaev\Seolinks\Handler", "setMeta");
        return true;
    }

    public function installDb() {
        /**
         * Check exists table
         * Include module and create DateBase ORM
         */
        Loader::includeModule($this->MODULE_ID);
        $tableName = \Isaev\Seolinks\SeolinksTable::getTableName();
        if (Loader::includeModule($this->MODULE_ID)) {
            if (\Bitrix\Main\Application::getConnection()->isTableExists($tableName) === false) {
                \Isaev\Seolinks\SeolinksTable::getEntity()->createDbTable();
            }
        }
        return true;
    }
    public function unInstallDb() {
       /**
         * Check exists table
         * Remove DB module
         */
        Loader::includeModule($this->MODULE_ID);
        if (Loader::includeModule($this->MODULE_ID)) {
            $tableName = \Isaev\Seolinks\SeolinksTable::getTableName();
            $connection = Application::getInstance()->getConnection();
            if (\Bitrix\Main\Application::getConnection()->isTableExists($tableName) !== false) {
                $connection->dropTable($tableName);
            }
        }
        return true;
    }
    public function doInstall() {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDb();
        $this->installEvents();
        $path = \Bitrix\Main\Loader::getLocal('/modules/isaev.seolinks/install/admin');
        CopyDirFiles($path, Application::getDocumentRoot() . "/bitrix/admin", true, true);

        // Компонент установка
        $componentPath = \Bitrix\Main\Loader::getLocal('/modules/isaev.seolinks/install/components');
        CopyDirFiles($componentPath, Application::getDocumentRoot()."/bitrix/components", true, true);
        return true;
    }
    public function doUninstall() {
        $this->unInstallEvents();
        $this->unInstallDb();
        $path = \Bitrix\Main\Loader::getLocal('/modules/isaev.seolinks/install/admin');
        DeleteDirFiles($path, Application::getDocumentRoot() . "/bitrix/admin");

        $componentPath = \Bitrix\Main\Loader::getLocal('/modules/isaev.seolinks/install/components');
        DeleteDirFiles($componentPath, Application::getDocumentRoot()."/bitrix/components");
        ModuleManager::unRegisterModule($this->MODULE_ID);
        return true;
    }
}
?>