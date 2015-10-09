<?php
/**
 * @package Abricos
 * @subpackage Mods
 * @copyright 2011-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class ModsManager
 * @property ModsModule $module
 */
class ModsManager extends Ab_ModuleManager {

    /**
     * @var ModsManager
     */
    public static $instance;

    /**
     * @var ModsConfig
     */
    public $config;

    public function __construct(ModsModule $module){
        parent::__construct($module);

        ModsManager::$instance = $this;

        $this->config = new ModsConfig(isset(Abricos::$config['module']['mods']) ? Abricos::$config['module']['mods'] : array());
    }

    public function IsAdminRole(){
        return $this->IsRoleEnable(ModsAction::ADMIN);
    }

    public function IsModeratorRole(){
        if ($this->IsAdminRole()){
            return true;
        }
        return $this->IsRoleEnable(ModsAction::MODERATOR);
    }

    public function IsOperatorRole(){
        if ($this->IsAdminRole()){
            return true;
        }
        return $this->IsRoleEnable(ModsAction::OPERATOR);
    }

    public function IsWriteRole(){
        if ($this->IsAdminRole()){
            return true;
        }
        return $this->IsRoleEnable(ModsAction::WRITE);
    }

    public function IsViewRole(){
        if ($this->IsWriteRole()){
            return true;
        }
        return $this->IsRoleEnable(ModsAction::VIEW);
    }

    private $_app;

    public function GetApp(){
        if (empty($this->_app)){
            /** @var CatalogManager $catalogManager */
            $catalogManager = Abricos::GetModule('catalog')->GetManager();
            $catalogManager->AppClassesRequire();

            require_once 'models.php';
            require_once 'dbquery.php';
            require_once 'app.php';
            $this->_app = new ModsApp($this);
        }
        return $this->_app;
    }

    public function AJAX($d){
        return $this->GetApp()->AJAX($d);
    }

    /**
     * Использует модуль Sitemap для построения меню товаров
     *
     * @param SMMenuItem $menuItem
     */
    public function Sitemap_MenuBuild(SMMenuItem $mItem){
        return;
        require_once 'smclasses.php';

        $cMan = $this->cManager;
        $mId = 1;

        $stat = $cMan->StatisticElementList();
        $mItems = array();

        $elTypeList = $cMan->ElementTypeList();
        for ($i = 1; $i < $elTypeList->Count(); $i++){
            $elType = $elTypeList->GetByIndex($i);
            $elTpCnt = $stat->elTypeCounter[$elType->id];
            if ($elTpCnt > 0){
                $cmItem = new ModsElementTypeMenuItem($mItem, $mId++, $elType, $elTpCnt);
                $mItems[$elType->id] = $cmItem;
                if ($elType->name == ModsModule::$instance->currentElTypeName){
                    $cmItem->isSelect = true;
                }
            }
        }

        $modList = $this->cManager->ModuleList();
        $curModName = ModsModule::$instance->currentModuleName;

        if (count($mItems) >= 2){
            for ($i = 0; $i < $modList->Count(); $i++){
                $el = $modList->GetByIndex($i);
                $mTpItem = $mItems[$el->elTypeId];
                $cmItem = new ModsElementMenuItem($mTpItem, $mId++, $el, true);
                $mTpItem->childs->Add($cmItem);
                if ($el->name == $curModName){
                    $cmItem->isSelect = true;
                    $mTpItem->isSelect = true;
                    $mItem->isSelect = true;
                }
            }
            foreach ($mItems as $elTypeId => $cmItem){
                $mItem->childs->Add($cmItem);
            }
        } else {

            for ($i = 0; $i < $modList->Count(); $i++){
                $cmItem = new ModsElementMenuItem($mItem, $mId++, $modList->GetByIndex($i));
                $mItem->childs->Add($cmItem);
            }
        }

        $i18n = ModsModule::$instance->I18n();

        // скриншоты
        $cmItem = new SMMenuItem(array(
            "id" => SMMenuItem::ToGlobalId("mods", $mId++),
            "pid" => $mItem->id,
            "lnk" => "/mods/?p=screens",
            "tl" => $i18n->Translate('screen_title')
        ));
        $mItem->childs->Add($cmItem);

        if (ModsModule::$instance->currentScreensPage){
            $cmItem->isSelect = true;
            $mItem->isSelect = true;
        }

        // список изменений
        $cmItem = new SMMenuItem(array(
            "id" => SMMenuItem::ToGlobalId("mods", $mId++),
            "pid" => $mItem->id,
            "lnk" => "/mods/?p=changelogs",
            "tl" => $i18n->Translate('changelog_title')
        ));
        $mItem->childs->Add($cmItem);
        if (ModsModule::$instance->currentChangelogsPage){
            $cmItem->isSelect = true;
            $mItem->isSelect = true;
        }
    }

    public function Bos_MenuData(){
        $i18n = $this->module->I18n();
        return array(
            array(
                "name" => "mods",
                "title" => $i18n->Translate('title'),
                "role" => ModsAction::ADMIN,
                "icon" => "/modules/mods/images/logo-48x48.png",
                "url" => "mods/wspace/ws",
                "parent" => "controlPanel"
            )
        );
    }
}

class ModsConfig {

    /**
     * @var ModsConfig
     */
    public static $instance;

    /**
     * Осуществлять сборку при скачивании
     *
     * @var boolean
     */
    public $buildDownload = false;


    /**
     * Структура/правила сборки файла для скачивания.
     *
     * Пример структуры платформы Абрикос:
     * $buildStructure = array(
     *    "module" => array(
     *        "optiondepends" => true, // опциональная загрузка включая зависимости
     *        "subdir" => "{v#name}",
     *        "builddir" => "modules", // собирать в папку modules
     *        "changelog" => "CHANGELOG.txt" // генерировать changelog.txt, если его нет в исходном архиве
     *    ),
     *    "core" => array(
     *        "changelog" => "CHANGELOG.txt"
     *    ),
     *    "distrib" => array(
     *        "depends" => true, // при загрузке включить зависимые модули
     *        "changelog" => "CHANGELOG.txt"
     *    )
     * );
     *
     * @var array|null
     */
    public $buildStructure = null;

    public function __construct($cfg){
        ModsConfig::$instance = $this;

        if (empty($cfg)){
            $cfg = array();
        }

        if (isset($cfg['buildDownload'])){
            $this->buildDownload = $cfg['buildDownload'];
        }

        if (isset($cfg['buildStructure'])){
            $this->buildStructure = $cfg['buildStructure'];
        }
    }
}


?>