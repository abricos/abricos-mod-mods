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

$elList = $cMan->ModuleList();

if (empty($elList)){ $brick->content = ""; return; }

$scImgWidth = bkint($p['scimgw']);
$scImgHeight = bkint($p['scimgh']);
Abricos::GetModule('filemanager')->EnableThumbSize(array(
		array("w" => $scImgWidth, "h" => $scImgHeight)
));

$elTypeList = $cMan->ElementTypeList();
$elsFotos = $cMan->ElementFotoList($elList);

$lst = "";
for ($i=0;$i<$elList->Count();$i++){
	$el = $elList->GetByIndex($i);

	$elType = $elTypeList->Get($el->elTypeId);

	$lstScreen = "";
	$fotos = $elsFotos->GetGroup($el->id);
	for ($ii=1; $ii<count($fotos); $ii++){
		$foto = $fotos[$ii];
		$lstScreen .= Brick::ReplaceVarByData($v['screen'], array(
			"title" => $el->title,
			"src" => $foto->Link($scImgWidth, $scImgHeight),
			"fsrc" => $foto->Link()
		));
	}
	if (empty($lstScreen)){ continue; }
	$lst .= Brick::ReplaceVarByData($v['modview'], array(
		"title" => $el->title,
		"eltypetitle" => $elType->title,
		"lnk" => $el->URI(),
		"screens" => $lstScreen
	));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"list" => $lst
));


$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"desc" => $el->detail->optionsBase['desc']
));

// Вывод заголовка страницы.
if (!empty($el->detail->metaTitle) && $el->detail->metaTitle !="&nbsp;"){
	Brick::$builder->SetGlobalVar('meta_title', $el->detail->metaTitle);
} else if (!empty($el->title) && $el->title !="&nbsp;"){
	$metaTitle = Brick::ReplaceVarByData($v['metatitle'], array(
		"eltypetitle" => $elType->title,
		"title" => $el->title,
		"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
	));
	Brick::$builder->SetGlobalVar('meta_title', $metaTitle);
}

// Вывод ключевых слов
if (!empty($el->detail->metaKeys) && $el->detail->metaKeys !="&nbsp;"){
	Brick::$builder->SetGlobalVar('meta_keys', $el->detail->metaKeys);
}
// Вывод описания
if (!empty($el->detail->metaDesc) && $el->detail->metaDesc !="&nbsp;"){
	Brick::$builder->SetGlobalVar('meta_desc', $el->detail->metaDesc);
}

?>