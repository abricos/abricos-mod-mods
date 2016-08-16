<?php
/**
 * @package Abricos
 * @subpackage Mods
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

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

class ModsElement extends CatalogElement {

    public function URI(){
        return "/mods/".$this->name."/";
    }

    public function DownloadURI(CatalogFile $file, $withDepends = false){
        $file->name = $this->name;
        $version = "";
        if (!empty($this->ext['version'])){
            $file->name .= "-".$this->ext['version'];
            $version = $this->ext['version'];
        }
        $file->name .= ".zip";

        $downloadURI = $file->URL();
        if (ModsConfig::$instance->buildDownload){
            $downloadURI = "/mods/".$this->name."/download/".$this->name."-";
            if (empty($version)){
                $downloadURI .= $file->id;
            } else {
                $downloadURI .= $version;
            }
            $downloadURI .= ".zip";
            if ($withDepends){
                $downloadURI .= "?depends=true";
            }
        }
        return $downloadURI;
    }
}

class ModsElementList extends CatalogElementList {

    /**
     * @return ModsElement
     */
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }

    public function GetByName($elName){
        $cnt = $this->Count();

        for ($i = 0; $i < $cnt; $i++){
            $el = $this->GetByIndex($i);
            if ($el->name == $elName){
                return $el;
            }
        }
        return null;
    }

    public function ToAJAX(){
        return parent::ToAJAX(ModsCatalogManager::$instance);
    }
}

class ModsDownloadInfo extends AbricosItem {

    public $counter;
    public $version;

    public function __construct($d){
        $this->id = strval($d['nm']);
        $this->counter = intval($d['cnt']);
        $this->version = intval($d['vs']);
    }
}

class ModsDownloadInfoList extends AbricosList {

    /**
     * @return ModsDownloadInfo
     */
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }

    /**
     * @return ModsDownloadInfo
     */
    public function Get($name){
        return parent::Get($name);
    }
}

class ModsBuildInfo {

    /**
     * @var ModsElement
     */
    public $element;

    public $cachePath = '';
    public $origFile = '';
    public $srcPath = '';
    public $changelogFile = '';
    public $outFile = '';

    public function __construct(ModsElement $el){
        $this->element = $el;
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

        $this->CatalogElementClass = 'ModsElement';
        $this->CatalogElementListClass = 'ModsElementList';

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

        if (empty($el)){
            return null;
        }

        $ext = $el->detail->optionsBase;

        $el->ext['version'] = $ext['version'];
        $el->ext['distrib'] = $ext['distrib'];
        $el->ext['compat'] = $ext['compat'];
        $el->ext['mindesc'] = $ext['mindesc'];
        $el->ext['depends'] = $ext['depends'];

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
        $cfg->extFields->Add($optionsBase->GetByName("depends"));

        return $this->ElementList($cfg);
    }

    public function ElementDownloadCounterUpdate($name){
        if (!$this->IsViewRole()){
            return null;
        }

        ModsQuery::ElementDownloadCounterUpdate($this->db, $name);
    }

    private $_cacheDownList;

    /**
     * Информация загрузки по элементу каталога
     *
     * @param string $name
     * @return ModsDownloadInfoList
     */
    public function ElementDownloadInfoList($clearCache = false){
        if (!$this->IsViewRole()){
            return null;
        }

        if ($clearCache){
            $this->_cacheDownList = null;
        }
        if (!empty($this->_cacheDownList)){
            return $this->_cacheDownList;
        }

        $list = new ModsDownloadInfoList();
        $rows = ModsQuery::ElementDownloadInfoList($this->db);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add(new ModsDownloadInfo($d));
        }

        $this->_cacheDownList = $list;
        return $list;
    }

    private $_cacheElementListBuild;

    /**
     * @return ModsElementList
     */
    public function ElementListForBuild(){
        if (!empty($this->_cacheElementListBuild)){
            return $this->_cacheElementListBuild;
        }

        $this->_cacheElementListBuild = $this->ModuleList();

        return $this->_cacheElementListBuild;
    }

    private $_cacheElementFileListBuild;

    /**
     * @return CatalogFileList
     */
    public function ElementOptionFileListForBuild(){
        if (!empty($this->_cacheElementFileListBuild)){
            return $this->_cacheElementFileListBuild;
        }
        $elList = $this->ElementListForBuild();
        $files = $this->ElementOptionFileList($elList);
        $this->_cacheElementFileListBuild = $files;

        return $files;
    }

    /**
     * Сформировать список всех зависимых модулей от модуля $el (рекурсивно)
     *
     * @param ModsElement $el
     * @param array $result
     */
    public function ElementFullDependList(ModsElement $el, &$result){

        $elList = $this->ElementListForBuild();

        $aDepends = explode(",", $el->ext['depends']);
        for ($i = 0; $i < count($aDepends); $i++){
            $nm = trim($aDepends[$i]);
            if (empty($nm) || (isset($result[$nm]) && $result[$nm])){
                continue;
            }
            $result[$nm] = true;
            $dEl = $elList->GetByName($nm);
            if (!empty($dEl)){
                $this->ElementFullDependList($dEl, $result);
            }
        }
    }

    private $_cacheSafeRecursKey;
    private $_cacheElementBuildKey = array();

    public function ElementBuildKey(ModsElement $el, $withDepends){

        if (isset($this->_cacheElementBuildKey[$el->id]) && !empty($this->_cacheElementBuildKey[$el->id])){
            return $this->_cacheElementBuildKey[$el->id];
        }

        if (isset($this->_cacheSafeRecursKey[$el->id]) && $this->_cacheSafeRecursKey[$el->id]){
            return "";
        }
        $this->_cacheSafeRecursKey[$el->id] = true;

        $elList = $this->ElementListForBuild();
        $files = $this->ElementOptionFileListForBuild();

        $aTmp = explode(":", $el->ext['distrib']);
        $file = $files->Get($aTmp[0]);
        if (empty($file)){
            return "";
        }

        $key = $file->id.$el->ext['version'];

        if ($withDepends){
            $depends = array();
            $this->ElementFullDependList($el, $depends);

            foreach ($depends as $sDName => $val){
                if ($sDName == $el->name){
                    continue;
                }
                $dEl = $elList->GetByName($sDName);
                $key .= $this->ElementBuildKey($dEl, $withDepends);
            }
        }

        $key = md5($key);

        $this->_cacheElementBuildKey[$el->id] = $key;

        return $key;
    }

    /**
     * Получить собранный для скачивания файл элемента каталога
     *
     * @param string $name Имя элемента
     * @return ModsBuildInfo
     */
    public function ElementBuildDownloadFile($name, $withDepends = false){
        // защита от бесконечной рекурсии
        $this->_cacheSafeRecursBuild = array();
        $this->_cacheSafeRecursKey = array();

        return $this->ElementBuildDownloadFileMethod($name, $withDepends);
    }

    private $_cacheSafeRecursBuild;
    private $_cacheElementBuild = array();

    protected function ElementBuildDownloadFileMethod($name, $withDepends = false){

        if (isset($this->_cacheSafeRecursBuild[$name]) && $this->_cacheSafeRecursBuild[$name]){
            return null;
        }
        $this->_cacheSafeRecursBuild[$name] = true;

        $elList = $this->ElementListForBuild();
        if (empty($elList)){
            return null;
        }

        $el = $elList->GetByName($name);
        if (empty($el)){
            return null;
        }

        $elTypeList = $this->ElementTypeList();
        $elType = $elTypeList->Get($el->elTypeId);
        $files = $this->ElementOptionFileListForBuild();

        $aTmp = explode(":", $el->ext['distrib']);
        $file = $files->Get($aTmp[0]);
        if (empty($file)){
            return null;
        }

        if (!empty($this->_cacheElementBuild[$name])){
            return $this->_cacheElementBuild[$name];
        }

        $build = new ModsBuildInfo($el);
        $this->_cacheElementBuild[$name] = $build;

        // сгенерировать уникальный ключ сборки
        $key = $this->ElementBuildKey($el, $withDepends);

        $cachePath = CWD."/cache/mods/".$el->name."/";
        $buildDirName = "build".($withDepends ? "-deps" : "-one");

        // проверить предыдущие сборки, если есть - удалить их
        $atDirs = glob($cachePath.$buildDirName."*");
        $buildDirName .= "-".$key;

        if (is_array($atDirs)){
            for ($i = 0; $i < count($atDirs); $i++){
                $pi = pathinfo($atDirs[$i]);

                if ($pi['basename'] == $buildDirName){
                    continue;
                }
                $this->RemoveDir($atDirs[$i]);
            }
        }

        $cachePath .= $buildDirName."/";

        $build->cachePath = $cachePath;
        if (!is_dir($cachePath) && !@mkdir($cachePath, 0777, true)){
            return null;
        }

        // собранный архив для загрузки
        $outFile = $cachePath."out.zip";
        if (file_exists($outFile)){
            $build->outFile = $outFile;
            return $build;
        }

        $fmMod = Abricos::GetModule('filemanager');
        if (empty($fmMod)){
            return null;
        }

        $fmMod->GetManager();
        $fmMan = FileManager::$instance;

        $origFile = $cachePath."origin.zip";
        if (!file_exists($origFile)){
            // сохранить файл из БД на диск
            if (!$fmMan->SaveFileTo($file->id, $origFile)){
                return null;
            };
        }
        $build->origFile = $origFile;

        $bldStruct = null;
        $bldStructs = ModsConfig::$instance->buildStructure;
        if (!empty($bldStructs) && !empty($bldStructs[$elType->name])){
            $bldStruct = $bldStructs[$elType->name];
        }

        // создать папку исходников
        $srcPath = $cachePath."src/";
        if ($withDepends && !empty($bldStruct)){
            $srcPath .= $this->NormalizePath($bldStruct['builddir']."/");
        }
        $build->srcPath = $srcPath;

        if (!is_dir($srcPath) && !@mkdir($srcPath, 0777, true)){
            return $build;
        }

        // извлечь исходник
        if ($this->DirIsEmpty($srcPath)){
            @($zip = new ZipArchive());
            if (empty($zip)){
                return $build;
            }

            if ($zip->open($origFile) === true){
                $zip->extractTo($srcPath);
                $zip->close();
            }
        }

        if (empty($bldStruct)){
            return $build;
        }

        $subDir = $srcPath;
        if (!empty($bldStruct['subdir'])){
            $subDir .= str_replace("{v#name}", $el->name, $bldStruct['subdir'])."/";
        }

        if (!is_dir($subDir) && !@mkdir($subDir, 0777, true)){
            return $build;
        }

        // сохранить changelog, если описана структура в конфиге
        if (!empty($bldStruct['changelog'])){
            $chlogFile = $subDir.$bldStruct['changelog'];
            if (!file_exists($chlogFile) && ($handle = fopen($chlogFile, 'wb'))){

                $chLogList = $this->ElementChangeLogListByName($el->name, "version");
                $lstChLog = "";
                for ($i = 0; $i < $chLogList->Count(); $i++){
                    $chLog = $chLogList->GetByIndex($i);
                    $dl = $chLog->dateline;
                    $log = $chLog->log;

                    $lstChLog .= $el->name." ".$chLog->ext['version'].", ";
                    $lstChLog .= " ".date("Y-m-d", $dl)."\n";
                    $lstChLog .= "------------------------\n";

                    $log = str_replace("\r\n", '[[rn]]', $log);
                    $log = str_replace("\n", '[[rn]]', $log);
                    $alog = explode("[[rn]]", $log);
                    foreach ($alog as $s){
                        $s = trim($s);
                        if (empty($s)){
                            continue;
                        }

                        $lstChLog .= $s."\n";
                    }
                    $lstChLog .= "\n";
                }

                fwrite($handle, $lstChLog);
                fclose($handle);
            }
            $build->changelogFile = $chlogFile;
        }

        // включить зависимые модули в сборку
        if ($bldStruct['depends'] || $withDepends){
            $depends = array();
            $this->ElementFullDependList($el, $depends);
            foreach ($depends as $sDName => $val){
                if ($sDName == $el->name){
                    continue;
                }
                $dEl = $elList->GetByName($sDName);
                if (empty($dEl)){
                    continue;
                }

                $dBuild = $this->ElementBuildDownloadFileMethod($dEl->name, true);
                if (empty($dBuild) || empty($dBuild->outFile)){
                    continue;
                }

                $this->DirCopy($dBuild->cachePath."src/", $cachePath."src/");
            }
        }

        $zip = new ZipArchive();

        if ($zip->open($outFile, ZipArchive::CREATE)){
            $srcPath = $this->NormalizePath($build->cachePath."src/");
            $files = array();
            $this->ReadDir($srcPath, $files);
            foreach ($files as $file){
                $fileInZip = str_replace($srcPath, "", $file);
                $zip->addFile($file, $fileInZip);
            }
            $zip->close();
        } else {
            return $build;
        }

        $build->outFile = $outFile;
        return $build;
    }

    public function ReadDir($sDir, &$result){
        $sDir = $this->NormalizePath($sDir."/");
        if (!is_dir($sDir)){
            return;
        }

        $dir = dir($sDir);
        while (false !== ($entry = $dir->read())){ // удаление файлов
            if ($entry == "." || $entry == ".." || empty($entry)){
                continue;
            }
            $file = $this->NormalizePath($sDir."/".$entry);
            if (is_dir($file)){
                $this->ReadDir($file, $result);
            } else {
                array_push($result, $file);
            }
        }
    }

    /**
     * Находится ли папка в папке кеш?
     * Проверка, чтобы случайно что нить не грохнуть или создать за ее пределами.
     *
     * @param string $dir
     */
    public function DirCheckInCache($dir){
        $dir = $this->NormalizePath($dir."/");
        $cachePath = $this->NormalizePath(CWD."/cache/mods/");
        if (strpos($dir, $cachePath) === false){
            return false;
        }
        return true;
    }

    private function RemoveDirMethod($rmdir){
        $dir = $this->NormalizePath($rmdir."/");
        $dir = dir($rmdir);

        while (false !== ($entry = $dir->read())){ // удаление файлов
            if ($entry == "." || $entry == ".." || empty($entry)){
                continue;
            }
            $obj = $rmdir."/".$entry;
            if (is_dir($obj)){
                continue;
            }
            @unlink($obj);
        }
        while (false !== ($entry = $dir->read())){ // удаление папок
            if ($entry == "." || $entry == ".." || empty($entry)){
                continue;
            }
            $obj = $rmdir."/".$entry;
            if (!is_dir($obj)){
                continue;
            }
            $this->RemoveDirMethod($obj);
        }

        @rmdir($rmdir);
    }

    public function RemoveDir($dir){
        if (!$this->DirCheckInCache($dir)){
            return false;
        }
        $this->RemoveDirMethod($dir);
    }

    public function NormalizePath($path){
        $path = str_replace("\\", "/", $path);
        $path = preg_replace('/\/+/', '/', $path);
        return $path;
    }

    public function DirIsEmpty($sDir){
        $sDir = $this->NormalizePath($sDir."/");

        $dir = dir($sDir);

        while (false !== ($entry = $dir->read())){
            if ($entry == "." || $entry == ".." || empty($entry)){
                continue;
            }
            return false;
        }
        return true;
    }

    public function DirCopy($srcdir, $dstdir){
        $srcdir = $this->NormalizePath($srcdir);
        $dstdir = $this->NormalizePath($dstdir);

        if (is_dir($srcdir)){
            @mkdir($dstdir, 0777, true);

            $dir = dir($srcdir);

            while (false !== ($entry = $dir->read())){
                if ($entry == "." || $entry == ".." || empty($entry)){
                    continue;
                }
                $srcSub = $srcdir."/".$entry;
                $dstSub = $dstdir."/".$entry;

                if (is_dir($srcSub)){
                    $this->DirCopy($srcSub, $dstSub);
                } else {
                    if (file_exists($dstSub)){
                        continue;
                    }
                    @copy($srcSub, $dstSub);
                }
            }
        } else {
            if (file_exists($dstdir)){
                return;
            }
            @copy($srcdir, $dstdir);
        }
    }

    public function OnElementAppendByOperator($elementid){
        $el = $this->Element($elementid);
        if (empty($el)){
            return;
        }

        $elTypeList = $this->ElementTypeList();
        $elType = $elTypeList->get($el->elTypeId);

        $brick = Brick::$builder->LoadBrickS('mods', 'templates', null, null);
        $host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
        $elLink = "http://".$host.$el->URI();
        $email = SystemModule::$instance->GetPhrases()->Get('admin_mail');

        if (empty($email)){
            return;
        }

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
            "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name')
        ));
        Abricos::Notify()->SendMail($email, $subject, $body);
    }

    public function OnElementModer($elementid){
        $el = $this->Element($elementid, true);
        if (empty($el)){
            return;
        }

        $elTypeList = $this->ElementTypeList();
        $elType = $elTypeList->get($el->elTypeId);

        $brick = Brick::$builder->LoadBrickS('mods', 'templates', null, null);
        $host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
        $elLink = "http://".$host.$el->URI();

        $user = $this->UserByElement($el);
        if (empty($user)){
            return;
        }

        $email = $user->email;
        if (empty($email)){
            return;
        }

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
            "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name')
        ));
        Abricos::Notify()->SendMail($email, $subject, $body);
    }

}
