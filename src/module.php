<?php
/**
 * Модуль "Каталог модулей"
 *
 * @package Abricos
 * @subpackage Mods
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Каталог модулей
 */
class ModsModule extends Ab_Module {

    /**
     * @var ModsModule
     */
    public static $instance = null;

    public $catinfo = array(
        "dbprefix" => 'mods'
    );

    private $_manager = null;

    public function __construct(){
        ModsModule::$instance = $this;

        $this->version = "0.1.4";
        $this->name = "mods";
        $this->takelink = "mods";

        $this->permission = new ModsPermission($this);
    }

    /**
     * Получить менеджер
     *
     * @return ModsManager
     */
    public function GetManager(){
        if (is_null($this->_manager)){
            require_once 'includes/manager.php';
            $this->_manager = new ModsManager($this);
        }
        return $this->_manager;
    }

    /**
     * Текущий модуль
     *
     * @var string
     */
    public $currentModuleName = '';

    public $currentScreensPage = false;

    public $currentChangelogsPage = false;

    /**
     * Текущий список типа элементов каталога
     *
     * @var string
     */
    public $currentElTypeName = '';

    public function GetContentName(){
        $adress = Abricos::$adress;

        $this->currentElTypeName = Abricos::CleanGPC('g', 'tp', TYPE_STR);

        $cPage = Abricos::CleanGPC('g', 'p', TYPE_STR);

        if ($adress->level > 2 && $adress->dir[2] == 'download'){
            return 'download';
        } else if ($cPage == 'changelogs'){
            $this->currentChangelogsPage = true;
            return 'changelogs';
        } else if ($cPage == 'screens'){
            $this->currentScreensPage = true;
            return 'screens';
        } else if ($adress->level >= 2 && empty($this->currentElTypeName)){
            $modName = $adress->dir[1];
            $cMan = $this->GetManager()->cManager;

            // TODO: необходимо организовать кеширование
            $el = $cMan->Module($modName);
            if (empty($el)){
                return '';
            }

            $this->currentModuleName = $modName;

            return 'module';
        }

        return 'modules';
    }

    /**
     * Этот модуль добавляет пункты меню в главное меню
     */
    public function Sitemap_IsMenuBuild(){
        return true;
    }

    /**
     * This module added menu item in BOS Panel
     *
     * @return bool
     */
    public function Bos_IsMenu(){
        return true;
    }

    public function Bos_IsSummary(){
        return true;
    }
}

class ModsAction {
    const VIEW = 10;
    const WRITE = 30;
    const OPERATOR = 40;
    const MODERATOR = 45;
    const ADMIN = 50;
}

class ModsPermission extends Ab_UserPermission {

    public function __construct(ModsModule $module){
        $defRoles = array(
            new Ab_UserRole(ModsAction::VIEW, Ab_UserGroup::GUEST),
            new Ab_UserRole(ModsAction::VIEW, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(ModsAction::VIEW, Ab_UserGroup::ADMIN),

            new Ab_UserRole(ModsAction::WRITE, Ab_UserGroup::ADMIN),
            new Ab_UserRole(ModsAction::OPERATOR, Ab_UserGroup::ADMIN),
            new Ab_UserRole(ModsAction::MODERATOR, Ab_UserGroup::ADMIN),

            new Ab_UserRole(ModsAction::ADMIN, Ab_UserGroup::ADMIN),
        );
        parent::__construct($module, $defRoles);
    }

    public function GetRoles(){
        return array(
            ModsAction::VIEW => $this->CheckAction(ModsAction::VIEW),
            ModsAction::WRITE => $this->CheckAction(ModsAction::WRITE),
            ModsAction::OPERATOR => $this->CheckAction(ModsAction::OPERATOR),
            ModsAction::MODERATOR => $this->CheckAction(ModsAction::MODERATOR),
            ModsAction::ADMIN => $this->CheckAction(ModsAction::ADMIN)
        );
    }
}

$modCatalog = Abricos::GetModule('catalog');
if (empty($modCatalog)){
    return;
}

$modMods = new ModsModule();

CatalogModule::$instance->Register($modMods);
Abricos::ModuleRegister($modMods);
