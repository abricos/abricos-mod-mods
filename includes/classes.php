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
	
	/**
	 * Осуществлять сборку при скачивании
	 * @var boolean
	 */
	public $buildDownload = false;
	
	
	/**
	 * Структура/правила сборки файла для скачивания.
	 * 
	 * Пример структуры платформы Абрикос:
	 * $buildStructure = array(
	 * 	"module" => array(
	 * 		"subdir" => "{v#name}",
	 * 		"changelog" => "CHANGELOG.txt" // генерировать changelog.txt, если его нет в исходном архиве
	 * 	)
	 * );
	 * 
	 * @var array|null
	 */
	public $buildStructure = null;
	
	public function __construct($cfg){
		ModsConfig::$instance = $this;
		
		if (empty($cfg)){ $cfg = array(); }
		
		if (isset($cfg['buildDownload'])){
			$this->buildDownload = $cfg['buildDownload'];
		}

		if (isset($cfg['buildStructure'])){
			$this->buildStructure = $cfg['buildStructure'];
		}
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
	 * Получить собранный для скачивания файл элемента каталога
	 * 
	 * @param string $name Имя элемента
	 * @return string|null
	 */
	public function ElementBuildDownloadFile($name){
		$el = $this->Module($name);
		if (empty($el)){ return null; }
		
		$elTypeList = $this->ElementTypeList();
		$elType = $elTypeList->Get($el->elTypeId);
		$files = $this->ElementOptionFileList($el);
		
		$aTmp = explode(":", $el->ext['distrib']);
		$file = $files->Get($aTmp[0]);
		if (empty($file)){
			return null;
		}
		
		$version = $el->ext['version'];
		if (empty($version)){
			$version = $file->id;
		}
		
		$cachePath = CWD."/cache/mods/".$el->name."/".$version."/";
		
		if (!is_dir($cachePath)){
			if (!@mkdir($cachePath, 0777, true)){
				return null;
			}
		}
		
		$fmMod = Abricos::GetModule('filemanager');
		if (empty($fmMod)){ return null; }
		
		$fmMod->GetManager();
		$fmMan = FileManager::$instance;
		
		$origFile = $cachePath."origin.zip";
		if (!file_exists($origFile)){
			// сохранить файл из БД на диск
			if (!$fmMan->SaveFileTo($file->id, $origFile)){
				return null;
			};
		}
		
		// создать папку исходников
		$srcPath = $cachePath."src/";
		if (is_dir($srcPath)){
			@rmdir($srcPath);
		}
		if (!is_dir($srcPath)){
			
			if (!is_dir($srcPath)){
				if (!@mkdir($srcPath, 0777, true)){
					return $origFile;
				}
			}
		}
		// извлечь исходник
		if (count(glob($srcPath."*")) == 0){
			@($zip = new ZipArchive());
			if (empty($zip)){
				return $origFile;
			}
				
			if ($zip->open($origFile) === true){
				$zip->extractTo($srcPath);
				$zip->close();
			}
		}
		
		$bldStructs = ModsConfig::$instance->buildStructure;
		if (empty($bldStructs) || empty($bldStructs[$elType->name])){
			return $origFile;
		}
		
		$bldStruct = $bldStructs[$elType->name];
		$subDir = $srcPath;
		if (!empty($bldStruct['subdir'])){
			$subDir .= str_replace("{v#name}", $el->name, $bldStruct['subdir'])."/";
		}
		
		// сохранить changelog, если описана структура в конфиге
		if (!empty($bldStruct['changelog'])){
			$chlogFile = $subDir.$bldStruct['changelog'];
			@unlink($chlogFile);
			if (!file_exists($chlogFile) && ($handle = fopen($chlogFile, 'w'))){
				
				$chLogList = $this->ElementChangeLogListByName($el->name, "version");
				$lstChLog = "";
				for ($i=0;$i<$chLogList->Count(); $i++){
					$chLog = $chLogList->GetByIndex($i);
					$dl = $chLog->dateline;
					$log = $chLog->log;

					$lstChLog .= $el->name." ".$chLog->ext['version'].", ";
					$lstChLog .= " ". date("Y-m-d", $dl)."\n";
					$lstChLog .= "------------------------\n";
					
					$log = str_replace("\r\n",'[[rn]]', $log);
					$log = str_replace("\n",'[[rn]]', $log);
					$alog = explode("[[rn]]", $log);
					foreach($alog as $s){
						$s = trim($s);
						if (empty($s)){ continue; }
						
						$lstChLog .= $s."\n";
					}

					$lstChLog .= "\n";
						
				}
				
				fwrite($handle, $lstChLog);
				fclose($handle);
			}
		}
		
		// создать собранный архив для скачивания
		$outFile = $cachePath."out.zip";
		if (file_exists($outFile)){
			return $outFile;
		}
		$zip = new ZipArchive();
		
		if ($zip->open($outFile, ZipArchive::CREATE)){
			$srcPath = str_replace("\\", "/", realpath($srcPath)."/");
			$files = array();
			$this->ReadDir($srcPath, $files);
			foreach($files as $file){
				$fileInZip = str_replace($srcPath, "", $file);
				$zip->addFile($file, $fileInZip);
			}
			$zip->close();
		}else{
			return $origFile;
		}
		return $outFile;
	}
	
	public function ReadDir($dir, &$result){
		$dir = realpath($dir);
		$files = glob($dir.'/*');
		if (count($files) == 0){ return; }
		
		foreach($files as $file){
			if (is_dir($file)){
				// array_push($result, $file);
				$this->ReadDir($file, $result);
			}else{
				array_push($result, str_replace("\\", "/", $file));
			}
		}
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