<?php
/**
 * @package Abricos
 * @subpackage Mods
 * @copyright 2011-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current;
$db = Abricos::$db;
$pfx = $db->prefix;

Abricos::GetModule('mods')->GetManager();
ModsManager::$instance->RolesDisable();

$cManager = ModsManager::$instance->cManager;

if ($updateManager->isInstall()){
    Abricos::GetModule('mods')->permission->Install();

    $ord = 100;
    $cManager->ElementOptionSave(0, array(
        "nm" => "mindesc",
        "tl" => "Краткое описание модуля",
        "tp" => Catalog::TP_TEXT,
        "ord" => $ord--
    ));
    $cManager->ElementOptionSave(0, array(
        "nm" => "desc",
        "tl" => "Подробное описание модуля",
        "tp" => Catalog::TP_TEXT,
        "ord" => $ord--
    ));
    $cManager->ElementOptionSave(0, array(
        "nm" => "version",
        "tl" => "Версия",
        "tp" => Catalog::TP_STRING,
        "ord" => $ord--,
        "sz" => 50
    ));
    $cManager->ElementOptionSave(0, array(
        "nm" => "compat",
        "tl" => "Совместимость",
        "tp" => Catalog::TP_STRING,
        "ord" => $ord--,
        "sz" => 50
    ));
    $cManager->ElementOptionSave(0, array(
        "nm" => "depends",
        "tl" => "Зависит от модулей",
        "tp" => Catalog::TP_ELDEPENDSNAME,
        "ord" => $ord--
    ));
    $cManager->ElementOptionSave(0, array(
        "nm" => "distrib",
        "tl" => "Дистрибутив",
        "tp" => Catalog::TP_FILES,
        "ord" => $ord--,
        "prm" => "count=1;ftypes=zip:5242880"
    ));

    $cManager->ElementTypeSave(0, array(
        "nm" => "module",
        "tl" => "Модуль",
        "tls" => "Модули"
    ));
    $cManager->ElementTypeSave(0, array(
        "nm" => "template",
        "tl" => "Шаблон",
        "tls" => "Шаблоны"
    ));
    $cManager->ElementTypeSave(0, array(
        "nm" => "distrib",
        "tl" => "Сборка",
        "tls" => "Сборки"
    ));
}

if ($updateManager->isUpdate('0.1.1') && !$updateManager->isInstall()){
    Abricos::GetModule('mods')->permission->Reinstall();
}

if ($updateManager->isUpdate('0.1.3')){
    $cManager->ElementTypeSave(0, array(
        "nm" => "core",
        "tl" => "Ядро",
        "tls" => "Ядро"
    ));
    $cManager->ElementTypeSave(0, array(
        "nm" => "tools",
        "tl" => "Утилита",
        "tls" => "Утилиты"
    ));

    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."mods_download (
			`elementname` varchar(25) NOT NULL DEFAULT '' COMMENT 'Имя элемента каталога',
			`counter` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Счетчик',

			`version` varchar(25) NOT NULL DEFAULT '' COMMENT 'Текущая версия',
			`origfile` varchar(8) NOT NULL DEFAULT '' COMMENT 'Идентификатор исходного файла',
			
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			`upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
	
			UNIQUE KEY (`elementname`)
		)".$charset);


}
?>