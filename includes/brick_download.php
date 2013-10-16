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

$adr = Abricos::$adress;
$a = explode("-", $adr->dir[2]);
$modName = $a[0];

$build = $cMan->ElementBuildDownloadFile($modName);

if (empty($build)){
	$brick->content = Brick::ReplaceVarByData($brick->content, array(
		"file" => $adr->dir[2]
	));
	return;
}

$cMan->ElementDownloadCounterUpdate($modName);

header('Content-type: application/zip; name='.$adr->dir[2]);

//отдаём файл архива
if (empty($build->outFile)){
	echo file_get_contents($build->origFile);
}else{
	echo file_get_contents($build->outFile);
}
exit;

?>