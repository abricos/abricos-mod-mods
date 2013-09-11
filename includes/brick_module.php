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

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"image" => $image,
	"title" => addslashes(htmlspecialchars($el->title)),
	"mindesc" => $el->ext['mindesc'],
	"desc" => $el->detail->optionsBase['desc'],
	"link" => $el->URI()
));

?>