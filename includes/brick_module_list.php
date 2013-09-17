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

if (is_object($p['cfg'])){
	$cfg = $p['cfg'];
}else{
	$cfg = new CatalogElementListConfig();
	$cfg->limit = $p['limit'];
}

$cfg->catids = array(0);

$adr = Abricos::$adress;
$page = $adr->dir[count($adr->dir)-1];

if (preg_match("/^page[0-9]+/", $page)){
	$page = intval(substr($page, 4));
	if ($page > 0){
		$cfg->page = $page;
	}
}
	
$elList = $cMan->ModuleList($cfg);

$brick->elementList = $elList;
if (empty($elList)){ $brick->content = ""; return; }

$uList = $cMan->UserList($elList);
$files = $cMan->ElementOptionFileList($elList);

$lst = "";
for ($i=0;$i<$elList->Count();$i++){
	$el = $elList->GetByIndex($i);
	
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
	
	$replace = array(
		"image" => $image,
		"title" => addslashes(htmlspecialchars($el->title)),
		"version" => $el->ext['version'],
		"ulink" => $user->URL(),
		"uname" => $user->GetUserName(),
		"distdowncnt" => !empty($file) ? $file->counter : 0,
		"downlink" => !empty($file) ? $file->URL() : "#",
		"compat" => $el->ext['compat'],
		"dateline" => date("d", $dl)." ".rusMonth($dl)." ".date("Y", $dl),
		"upddate" => date("d", $upd)." ".rusMonth($upd)." ".date("Y", $upd),
		"mindesc" => $el->ext['mindesc'],
		"link" => $el->URI()
	);
	
	$lst .=  Brick::ReplaceVarByData($v['row'], $replace);
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"result" => Brick::ReplaceVarByData($v['table'], array(
		"rows" => $lst
	))
));

?>