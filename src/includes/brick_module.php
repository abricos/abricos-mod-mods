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
    array(
        "w" => $imgWidth,
        "h" => $imgHeight
    )
));

$adr = Abricos::$adress;
$modName = $adr->dir[1];

$el = $cMan->Module($modName);

if (empty($el)){
    $brick->content = "";
    return;
}


$elTypeList = $cMan->ElementTypeList();
$elType = $elTypeList->Get($el->elTypeId);

$cfgBS = null;
if (ModsConfig::$instance->buildStructure){
    $cfgBS = ModsConfig::$instance->buildStructure[$elType->name];
}

$uList = $cMan->UserList($el);
$files = $cMan->ElementOptionFileList($el);

// ---------------- Фото элемента -----------------
if (empty($el->foto)){
    $image = $v["imgempty"];
} else {
    $image = Brick::ReplaceVarByData($v["img"], array(
        "src" => $el->FotoSrc($imgWidth, $imgHeight)
    ));
}
$image = Brick::ReplaceVarByData($image, array(
    "w" => $imgWidth,
    "h" => $imgHeight
));


$dl = $el->dateline;
$user = $uList->Get($el->userid);

/* * * * * * * Скачать * * * * * */
$aTmp = explode(":", $el->ext['distrib']);
$file = $files->Get($aTmp[0]);
$downloadURI = !empty($file) ? $el->DownloadURI($file) : "#";

$downloadCount = !empty($file) ? $file->counter : 0;
$downList = $cMan->ElementDownloadInfoList();
$downInfo = $downList->Get($el->name);

if (ModsConfig::$instance->buildDownload && !empty($downInfo)){
    $downloadCount = $downInfo->counter;
}

// --------------- Скриншоты -----------------
$scImgWidth = bkint($p['scimgw']);
$scImgHeight = bkint($p['scimgh']);


Abricos::GetModule('filemanager')->EnableThumbSize(array(
    array(
        "w" => $scImgWidth,
        "h" => $scImgHeight
    )
));

$lstScreen = "";
$disScreens = "none";
$fotoList = $el->detail->fotoList;
for ($i = 1; $i < $fotoList->Count(); $i++){
    $disScreens = "";
    $foto = $fotoList->GetByIndex($i);
    $lstScreen .= Brick::ReplaceVarByData($v['screen'], array(
        "title" => $el->title,
        "src" => $foto->Link($scImgWidth, $scImgHeight),
        "fsrc" => $foto->Link()
    ));
}

// --------------- Зависимость -----------------
$lstDepends = "";
$sDependCount = "";
$depends = $el->detail->optionsBase['depends'];
$disDepends = "none";
$depListCfg = new CatalogElementListConfig();
$depListCfg->elnames = explode(",", $depends);
if (!empty($depends) && count($depListCfg->elnames) > 0){
    $depListBrick = Brick::$builder->LoadBrickS("mods", "module_list", null, array(
        "p" => array(
            "cfg" => $depListCfg
        )
    ));
    $depElList = $depListBrick->elementList;
    if (!empty($depElList) && $depElList->count() > 0){
        $sDependCount = "(".$depElList->count().")";
        $lstDepends = $depListBrick->content;
        $disDepends = "";
    }
}

// --------------- Changelog -----------------
$chLogList = $cMan->ElementChangeLogListByName($el->name, "version");
$lstChLog = "";
for ($i = 0; $i < $chLogList->Count(); $i++){
    $chLog = $chLogList->GetByIndex($i);
    $dl = $chLog->dateline;
    $log = $chLog->log;
    $log = str_replace("\r\n", '<br />', $log);
    $log = str_replace("\n", '<br />', $log);

    $lstChLog .= Brick::ReplaceVarByData($v['changelog'], array(
        "v" => $chLog->ext['version'],
        'dl' => date("d", $dl)." ".rusMonth($dl)." ".date("Y", $dl),
        'chlg' => $log
    ));
}

// ---------- Download with Depends -----------
$downdepends = "";
if (ModsConfig::$instance->buildDownload && !empty($cfgBS) && isset($cfgBS['optiondepends']) && $cfgBS['optiondepends'] && !empty($file)){
    $downdepends = Brick::ReplaceVarByData($v['downdepends'], array(
        "downlink" => $el->DownloadURI($file, true)
    ));
}
// $elType->name

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "image" => $image,
    "eltypetitle" => $elType->title,
    "title" => addslashes(htmlspecialchars($el->title)),
    "version" => $el->ext['version'],
    "ulink" => $user->URL(),
    "uname" => $user->GetUserName(),
    "changelog" => $lstChLog,
    "disscreens" => $disScreens,
    "screencnt" => $fotoList->Count() <= 1 ? "" : "(".($fotoList->Count() - 1).")",
    "screens" => $lstScreen,
    "dependscnt" => $sDependCount,
    "depends" => $lstDepends,
    "disdepends" => $disDepends,
    "distdowncnt" => $downloadCount,
    "downlink" => $downloadURI,
    "downdepends" => $downdepends,
    "compat" => $el->ext['compat'],
    "dateline" => date("d", $dl)." ".rusMonth($dl)." ".date("Y", $dl),
    "upddate" => date("d", $el->dateline)." ".rusMonth($el->dateline)." ".date("Y", $el->dateline),
    "mindesc" => $el->ext['mindesc'],
    "link" => $el->URI()
));


$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "desc" => $el->detail->optionsBase['desc'],
    "brickid" => $brick->id
));

// Вывод заголовка страницы.
if (!empty($el->detail->metaTitle) && $el->detail->metaTitle != "&nbsp;"){
    Brick::$builder->SetGlobalVar('meta_title', $el->detail->metaTitle);
} else if (!empty($el->title) && $el->title != "&nbsp;"){
    $phs = ModsModule::$instance->GetPhrases();

    $metaTitle = Brick::ReplaceVarByData($v['metatitle'], array(
        "eltypetitle" => $elType->title,
        "title" => $el->title,
        "sitename" => $phs->Get('site_name')
    ));
    Brick::$builder->SetGlobalVar('meta_title', $metaTitle);
}

// Вывод ключевых слов
if (!empty($el->detail->metaKeys) && $el->detail->metaKeys != "&nbsp;"){
    Brick::$builder->SetGlobalVar('meta_keys', $el->detail->metaKeys);
}
// Вывод описания
if (!empty($el->detail->metaDesc) && $el->detail->metaDesc != "&nbsp;"){
    Brick::$builder->SetGlobalVar('meta_desc', $el->detail->metaDesc);
}

?>