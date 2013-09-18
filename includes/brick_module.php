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

Abricos::GetModule('filemanager')->EnableThumbSize(array(array(
	"w" => $imgWidth,
	"h" => $imgHeight
)));


$adr = Abricos::$adress;
$modName = $adr->dir[1];

$el = $cMan->Module($modName);

if (empty($el)){ $brick->content = ""; return; }

$uList = $cMan->UserList($el);
$files = $cMan->ElementOptionFileList($el);

	
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

$scImgWidth = bkint($p['scimgw']);
$scImgHeight = bkint($p['scimgh']);


Abricos::GetModule('filemanager')->EnableThumbSize(array(
	array("w" => $scImgWidth, "h" => $scImgHeight)
));

$screens = "";
$fotoList = $el->detail->fotoList;
for ($i=1; $i<$fotoList->Count(); $i++){
	$foto = $fotoList->GetByIndex($i);
	$screens .= Brick::ReplaceVarByData($v['screen'], array(
		"title" => $el->title,
		"src" => $foto->Link($scImgWidth, $scImgHeight),
		"fsrc" => $foto->Link()
	));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"image" => $image,
	"title" => addslashes(htmlspecialchars($el->title)),
	"version" => $el->ext['version'],
	"ulink" => $user->URL(),
	"uname" => $user->GetUserName(),
	"screencnt" => $fotoList->Count() <= 1 ? "" : "(".($fotoList->Count()-1).")",
	"screens" => $screens,
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