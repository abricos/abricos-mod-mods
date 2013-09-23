<?php 
/**
 * @package Abricos
 * @subpackage Mods
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

// require_once 'dbquery.php';

class ModsConfig {
	
	/**
	 * @var ModsConfig
	 */
	public static $instance;
	
	public function __construct($cfg){
		ModsConfig::$instance = $this;
		
		if (empty($cfg)){ $cfg = array(); }
	}
}

class ModsElement extends CatalogElement {
	
	public function URI(){
		return "/mods/".$this->name."/";
	}
}

class ModsElementList extends CatalogElementList {
	public function ToAJAX(){
		return parent::ToAJAX(ModsCatalogManager::$instance);
	}
}

class ModsCatalogManager extends CatalogModuleManager {
	
	/**
	 * @var ModsCatalogManager
	 */
	public static $instance = null;
	
	/**
	 * @var ModsManager
	 */
	public $manager;
	
	public function __construct(){
		$this->manager = ModsManager::$instance;
		
		ModsCatalogManager::$instance = $this;

		parent::__construct("mods");
		
		$this->CatalogElementClass	= ModsElement;
		$this->CatalogElementListClass = ModsElementList;
		
		// разрешить изменять имя элемента
		$this->cfgElementNameChange = true;
		$this->cfgElementNameUnique = true;
		$this->cfgElementCreateBaseTypeDisable = true;
		$this->cfgVersionControl = true;
		
	}
	
	public function IsAdminRole(){
		return $this->manager->IsAdminRole();
	}
	
	public function IsModeratorRole(){
		return $this->manager->IsModeratorRole();
	}
	
	public function IsOperatorRole(){
		return $this->manager->IsOperatorRole();
	}
	
	public function IsWriteRole(){
		return $this->manager->IsWriteRole();
	}
	
	public function IsViewRole(){
		return $this->manager->IsViewRole();
	}
	
	/**
	 * @param integer $modName
	 * @return ModsElement
	 */
	public function Module($name){
		$el = $this->ElementByName($name);
		
		if (empty($el)){ return null; }
		
		$ext = $el->detail->optionsBase;
		
		$el->ext['version'] = $ext['version'];
		$el->ext['distrib'] = $ext['distrib'];
		$el->ext['compat'] = $ext['compat'];
		$el->ext['mindesc'] = $ext['mindesc'];
		
		return $el;
	}
	
	/**
	 * @param mixed $cfg
	 * @return ModsElementList
	 */
	public function ModuleList($cfg = null){
		if (empty($cfg)){
			$cfg = new CatalogElementListConfig();
			$cfg->catids = array(0);
		}

		$optionsBase = $this->ElementTypeList()->Get(0)->options;
		
		// $ordOpt = $cfg->orders->AddByOption($optionsBase->GetByName("price"));
		// $ordOpt->zeroDesc = true;
		
		$cfg->extFields->Add($optionsBase->GetByName("mindesc"));
		$cfg->extFields->Add($optionsBase->GetByName("version"));
		$cfg->extFields->Add($optionsBase->GetByName("compat"));
		$cfg->extFields->Add($optionsBase->GetByName("distrib"));
		
		return $this->ElementList($cfg);
	}
	
	public function OnElementAppendByOperator($elementid){
		$el = $this->Element($elementid);
		if (empty($el)){ return; }
		
		$elTypeList =  $this->ElementTypeList();
		$elType = $elTypeList->get($el->elTypeId);
		
		$brick = Brick::$builder->LoadBrickS('mods', 'templates', null, null);
		$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
		$elLink = "http://".$host.$el->URI();
		$email = Brick::$builder->phrase->Get('sys', 'admin_mail');
		
		if (empty($email)){ return; }
		
		$subject = Brick::ReplaceVarByData($brick->param->var['elnewmodersubj'], array(
			"tptl" => $elType->title,
			"tl" => $el->title
		));
		$body = Brick::ReplaceVarByData($brick->param->var['elnewmoder'], array(
			"email" => $email,
			"ellnk" => $elLink,
			"tptl" => $elType->title,
			"tl" => $el->title,
			"unm" => $this->user->info['username'],
			"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
		));
		Abricos::Notify()->SendMail($email, $subject, $body);
	}
	
	public function OnElementModer($elementid){
		$el = $this->Element($elementid, true);
		if (empty($el)){ return; }
		
		$elTypeList =  $this->ElementTypeList();
		$elType = $elTypeList->get($el->elTypeId);
		
		$brick = Brick::$builder->LoadBrickS('mods', 'templates', null, null);
		$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
		$elLink = "http://".$host.$el->URI();
		
		$user = $this->UserByElement($el);
		if (empty($user)){ return; }
		
		$email = $user->email;
		if (empty($email)){ return; }
		
		$subject = Brick::ReplaceVarByData($brick->param->var['elmodersubj'], array(
			"tptl" => $elType->title,
			"tl" => $el->title
		));
		$body = Brick::ReplaceVarByData($brick->param->var['elmoder'], array(
			"email" => $email,
			"ellnk" => $elLink,
			"tptl" => $elType->title,
			"tl" => $el->title,
			"unm" => $this->user->info['username'],
			"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
		));
		Abricos::Notify()->SendMail($email, $subject, $body);		
	}
	
}


?>