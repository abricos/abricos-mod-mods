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
	
	public function IsModeratorRole(){
		if ($this->IsAdminRole()){ return true; }
		return $this->IsRoleEnable(ModsAction::MODERATOR);
	}
	
	public function IsOperatorRole(){
		if ($this->IsAdminRole()){ return true; }
		return $this->IsRoleEnable(ModsAction::OPERATOR);
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
		require_once 'smclasses.php';
		
		$cMan = $this->cManager;
		$mId = 1;
		
		$stat = $cMan->StatisticElementList();
		$mItems = array();

		$elTypeList = $cMan->ElementTypeList();
		for ($i=1;$i<$elTypeList->Count();$i++){
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
			for ($i=0; $i<$modList->Count(); $i++){
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
			foreach($mItems as $elTypeId => $cmItem){
				$mItem->childs->Add($cmItem);
			}
		}else{
			
			for ($i=0; $i<$modList->Count(); $i++){
				$cmItem = new ModsElementMenuItem($mItem, $mId++, $modList->GetByIndex($i));
				$mItem->childs->Add($cmItem);
			}
		}
		
		// скриншоты
		$cmItem = new SMMenuItem(array(
			"id" => SMMenuItem::ToGlobalId("mods", $mId++),
			"pid" => $mItem->id,
			"lnk" => "/mods/?p=screens",
			"tl" => ModsModule::$instance->lang['screen_title']
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
			"tl" => ModsModule::$instance->lang['changelog_title']
		));
		$mItem->childs->Add($cmItem);
		if (ModsModule::$instance->currentChangelogsPage){
			$cmItem->isSelect = true;
			$mItem->isSelect = true;
		}
		
	}
	
}

?>