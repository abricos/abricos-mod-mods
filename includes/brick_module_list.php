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

$man = ModsModule::$instance->GetManager()->cManager;

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
	
$elList = $man->ModuleList($cfg);
$brick->elementList = $elList;
if (empty($elList)){ $brick->content = ""; return; }

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

	$replace = array(
		"image" => $image,
		"title" => addslashes(htmlspecialchars($el->title)),
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