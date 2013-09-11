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
			switch ($adress->dir[1]){
				case 'search':
					return $adress->dir[1];
			}
		}
		
		return 'modules';
		
		/*
		$lastitem = $adress->dir[count($adress->dir)-1];
		
		if (preg_match("/^product_[0-9]+/", $lastitem)){
			
			$arr = explode("_", $lastitem);
			
			// $db = $this->registry->db;
			$catManager = $this->GetCatalogManager();
			
			$this->currentProductId = intval($arr[1]);
			
			return "product";
		}
		
		// перегрузить кирпич-контент если таков есть исходя из адреса в урле
		// т.е. если идет запрос http://domain.ltd/Mods/mycat/ и в шаблоне есть файл
		// /tt/имя_шаблона/override/Mods/content/products-Mods-mycat.html, то он будет 
		// принят парсером для обработки
		// соответственно, если необходимо перегрузить только корень каталога продукции, то
		// необходимо создать файл products-Mods.html
		$newarr = $adress->dir;
		if (!empty($newarr) && count($newarr) > 0){
			$fname = "products-".implode("-", $newarr);
		}else{
			$fname = "products-Mods";
		}
		return array($fname, "products");
		/**/
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