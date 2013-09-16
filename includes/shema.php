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

if ($updateManager->isInstall()){
	Abricos::GetModule('mods')->permission->Install();
	
	$ord = 100;
	$cManager->ElementOptionSave(0, array(
		"nm" => "mindesc", "tl" => "Краткое описание модуля",
		"tp" => Catalog::TP_TEXT, "ord" => $ord--
	));
	$cManager->ElementOptionSave(0, array(
		"nm" => "desc", "tl" => "Подробное описание модуля",
		"tp" => Catalog::TP_TEXT, "ord" => $ord--
	));
	$cManager->ElementOptionSave(0, array(
		"nm" => "version", "tl" => "Версия",
		"tp" => Catalog::TP_STRING, "ord" => $ord--,
		"sz" => 50
	));
	$cManager->ElementOptionSave(0, array(
		"nm" => "depends", "tl" => "Зависит от модулей",
		"tp" => Catalog::TP_ELDEPENDSNAME, "ord" => $ord--
	));
	$cManager->ElementOptionSave(0, array(
		"nm" => "distribution", "tl" => "Дистрибутив",
		"tp" => Catalog::TP_FILES, "ord" => $ord--,
		"prm" => "count=1;ftypes=zip:5242880"
	));
	
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