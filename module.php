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
 * Интернет-магазин
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
	
	public function ModsModule(){
		$this->version = "0.1";
		$this->name = "mods";
		$this->takelink = "mods";
		ModsModule::$instance = $this;
		
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
	
	public function GetContentName(){
		$adress = Abricos::$adress;
		
		if ($adress->level >= 2){
			$modName = $adress->dir[1];
			$cMan = $this->GetManager()->cManager;
			
			// TODO: необходимо организовать кеширование
			$el = $cMan->Module($modName);
			if (empty($el)){ return ''; }
			
			return 'module';
		}
		
		return 'modules';
	}
	
	/**
	 * Этот модуль добавляет пункты меню в главное меню
	 */
	public function Sitemap_IsMenuBuild(){ return true; }
	
}

class ModsAction {
	const VIEW = 10;
	const WRITE = 30;
	const ADMIN = 50;
}

class ModsPermission extends Ab_UserPermission {

	public function ModsPermission(ModsModule $module){
		$defRoles = array(
			new Ab_UserRole(ModsAction::VIEW, Ab_UserGroup::GUEST),
			new Ab_UserRole(ModsAction::VIEW, Ab_UserGroup::REGISTERED),
			new Ab_UserRole(ModsAction::VIEW, Ab_UserGroup::ADMIN),

			new Ab_UserRole(ModsAction::WRITE, Ab_UserGroup::ADMIN),

			new Ab_UserRole(ModsAction::ADMIN, Ab_UserGroup::ADMIN),
		);
		parent::__construct($module, $defRoles);
	}

	public function GetRoles(){
		return array(
			ModsAction::VIEW => $this->CheckAction(ModsAction::VIEW),
			ModsAction::WRITE => $this->CheckAction(ModsAction::WRITE),
			ModsAction::ADMIN => $this->CheckAction(ModsAction::ADMIN)
		);
	}
}

$modCatalog = Abricos::GetModule('catalog');
if (empty($modCatalog)){ return; }

$modMods = new ModsModule();

CatalogModule::$instance->Register($modMods);
Abricos::ModuleRegister($modMods);

?>