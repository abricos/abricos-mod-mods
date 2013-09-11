<?php
/**
 * @package Abricos
 * @subpackage Mods
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

Abricos::GetModule('catalog')->GetManager();

require_once 'classes.php';

class ModsManager extends Ab_ModuleManager {

	/**
	 * @var ModsManager
	 */
	public static $instance;
	
	/**
	 * @var ModsConfig
	 */
	public $config;
	
	/**
	 * @var ModsCatalogManager
	 */
	public $cManager;
	
	private $_isRoleDisabled = false;
	
	public function __construct(ModsModule $module){
		parent::__construct($module);
		
		ModsManager::$instance = $this;
		
		$this->cManager = new ModsCatalogManager();
	}
	
	/**
	 * Отключить проверку ролей
	 */
	public function RoleDisable(){
		$this->_isRoleDisabled = true;
	}
	
	public function IsAdminRole(){
		if ($this->_isRoleDisabled){ return true; }
		return $this->IsRoleEnable(ModsAction::ADMIN);
	}
	
	public function IsWriteRole(){
		if ($this->IsAdminRole()){ return true; }
		return $this->IsRoleEnable(ModsAction::WRITE);
	}
	
	public function IsViewRole(){
		if ($this->IsWriteRole()){ return true; }
		return $this->IsRoleEnable(ModsAction::VIEW);
	}
	
	public function AJAX($d){
		$ret = $this->cManager->AJAX($d);
		if (!empty($ret)){ return $ret; }
		
		return null;
	}
	
	/**
	 * Использует модуль Sitemap для построения меню товаров
	 * 
	 * @param SMMenuItem $menuItem
	 */
	public function Sitemap_MenuBuild(SMMenuItem $mItem){
		$modList = $this->cManager->ModuleList();

		require_once 'smclasses.php';
		
		for ($i=0; $i<$modList->Count(); $i++){
			$cmItem = new ModsMenuItem($mItem, $modList->GetByIndex($i));
			$mItem->childs->Add($cmItem);
		}
	}
	
}

?>