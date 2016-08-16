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

if (empty($elList)){
    $brick->content = "";
    return;
}

$elTypeList = $cMan->ElementTypeList();

$chLogList = $cMan->ElementChangeLogList('version');

$lst = "";

for ($i = 0; $i < $chLogList->Count(); $i++){

    $chLog = $chLogList->GetByIndex($i);
    $el = $elList->Get($chLog->id);
    if (empty($el)){
        continue;
    }

    $elType = $elTypeList->Get($el->elTypeId);

    $elid = $el->id;

    $lstChLog = "";
    do {
        $chLog = $chLogList->Get($elid);
        if (!empty($chLog)){

            $dl = $chLog->dateline;
            $log = $chLog->log;
            $log = str_replace("\r\n", '<br />', $log);
            $log = str_replace("\n", '<br />', $log);

            $lstChLog .= Brick::ReplaceVarByData($v['changelog'], array(
                "v" => $chLog->ext['version'],
                'dl' => date("d", $dl)." ".rusMonth($dl)." ".date("Y", $dl),
                'chlg' => $log
            ));
            $elid = $chLog->pvElementId;
        } else {
            $elid = 0;
        }

    } while ($elid > 0);

    if (empty($lstChLog)){
        continue;
    }

    $lst .= Brick::ReplaceVarByData($v['modview'], array(
        "title" => $el->title,
        "eltypetitle" => $elType->title,
        "lnk" => $el->URI(),
        "changelog" => $lstChLog
    ));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "list" => $lst
));

// Вывод заголовка страницы.
if (!empty($el->detail->metaTitle) && $el->detail->metaTitle != "&nbsp;"){
    Brick::$builder->SetGlobalVar('meta_title', $el->detail->metaTitle);
} else if (!empty($el->title) && $el->title != "&nbsp;"){
    $metaTitle = Brick::ReplaceVarByData($v['metatitle'], array(
        "eltypetitle" => $elType->title,
        "title" => $el->title,
        "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name')
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
