<?php
/**
 * @package Abricos
 * @subpackage Mods
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$p = &$brick->param->param;
$v = &$brick->param->var;

$cMan = ModsModule::$instance->GetManager()->cManager;

$imgWidth = bkint($p['imgw']);
$imgHeight = bkint($p['imgh']);

Abricos::GetModule('filemanager')->EnableThumbSize(array(
	array("w" => $imgWidth, "h" => $imgHeight)
));


$adr = Abricos::$adress;
$modName = $adr->dir[1];

$el = $cMan->Module($modName);

if (empty($el)){ $brick->content = ""; return; }

$uList = $cMan->UserList($el);
$files = $cMan->ElementOptionFileList($el);

// ---------------- Фото элемента -----------------
if (empty($el->foto)){
	$image = $v["imgempty"];
}else{
	$image = Brick::ReplaceVarByData($v["img"], array(
		"src" => $el->FotoSrc($imgWidth, $imgHeight)
	));
}
$image = Brick::ReplaceVarByData($image, array(
	"w" => $imgWidth,
	"h" => $imgHeight
));


$dl = $el->dateline; $upd = $el->upddate;
$user = $uList->Get($el->userid);

/* * * * * * * Скачать * * * * * */
$aTmp = explode(":", $el->ext['distrib']);
$file = $files->Get($aTmp[0]);

if (!empty($file)){
	$upd = $file->dateline;
	$file->name = $el->name;
	if (!empty($el->ext['version'])){
		$file->name .= "-".$el->ext['version'];
	}
	$file->name .= ".zip";
}

// --------------- Скриншоты -----------------
$scImgWidth = bkint($p['scimgw']);
$scImgHeight = bkint($p['scimgh']);


Abricos::GetModule('filemanager')->EnableThumbSize(array(
	array("w" => $scImgWidth, "h" => $scImgHeight)
));

$lstScreen = "";
$disScreens = "none";
$fotoList = $el->detail->fotoList;
for ($i=1; $i<$fotoList->Count(); $i++){
	$disScreens = "";
	$foto = $fotoList->GetByIndex($i);
	$lstScreen .= Brick::ReplaceVarByData($v['screen'], array(
		"title" => $el->title,
		"src" => $foto->Link($scImgWidth, $scImgHeight),
		"fsrc" => $foto->Link()
	));
}

// --------------- Зависимость -----------------
$lstDepends = ""; $sDependCount = ""; $depends = $el->detail->optionsBase['depends'];
$disDepends = "none";
$depListCfg = new CatalogElementListConfig();
$depListCfg->elnames = explode(",", $depends);
if (!empty($depends) && count($depListCfg->elnames) > 0){
	$depListBrick = Brick::$builder->LoadBrickS("mods", "module_list", null, array("p" => array(
		"cfg" => $depListCfg
	)));
	$depElList = $depListBrick->elementList;
	if (!empty($depElList) && $depElList->count() > 0){
		$sDependCount = "(".$depElList->count().")";
		$lstDepends = $depListBrick->content;
		$disDepends = "";
	}
	
}
		
$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"image" => $image,
	"title" => addslashes(htmlspecialchars($el->title)),
	"version" => $el->ext['version'],
	"ulink" => $user->URL(),
	"uname" => $user->GetUserName(),
	"disscreens" => $disScreens,
	"screencnt" => $fotoList->Count() <= 1 ? "" : "(".($fotoList->Count()-1).")",
	"screens" => $lstScreen,
	"dependscnt" => $sDependCount,
	"depends" => $lstDepends,
	"disdepends" => $disDepends,
	"distdowncnt" => !empty($file) ? $file->counter : 0,
	"downlink" => !empty($file) ? $file->URL() : "#",
	"compat" => $el->ext['compat'],
	"dateline" => date("d", $dl)." ".rusMonth($dl)." ".date("Y", $dl),
	"upddate" => date("d", $upd)." ".rusMonth($upd)." ".date("Y", $upd),
	"mindesc" => $el->ext['mindesc'],
	"link" => $el->URI()
));


$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"desc" => $el->detail->optionsBase['desc']
));
		
?>