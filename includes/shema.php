<?php
/**
 * Схема таблиц данного модуля
 * 
 * @package Abricos
 * @subpackage Mods
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current; 
$db = Abricos::$db;
$pfx = $db->prefix;

Abricos::GetModule('mods')->GetManager();
ModsManager::$instance->RoleDisable();

$cManager = ModsManager::$instance->cManager;

function ModsOptionAppend($name, $title, $type, $size = ''){
	$cManager = ModsManager::$instance->cManager;
	
	$d = new stdClass();
	$d->nm = $name;
	$d->tl = $title;
	$d->tp = $type;
	$d->sz = $size;
	return $cManager->ElementOptionSave(0, $d);
}

if ($updateManager->isInstall()){
	Abricos::GetModule('mods')->permission->Install();
	
	ModsOptionAppend('mindesc', 'Краткое описание модуля', Catalog::TP_TEXT);
	ModsOptionAppend('desc', 'Подробное описание модуля', Catalog::TP_TEXT);
	ModsOptionAppend('version', 'Версия', Catalog::TP_STRING, 50);
	ModsOptionAppend('depends', 'Зависит от модулей', Catalog::TP_ELDEPENDSNAME);
	
	$cManager = ModsManager::$instance->cManager;
	$cManager->ElementTypeSave(0, array(
		"nm" => "module",
		"tl" => "Модуль"
	));
	$cManager->ElementTypeSave(0, array(
		"nm" => "template",
		"tl" => "Шаблон"
	));
}
?>