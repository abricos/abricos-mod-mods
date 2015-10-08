<?php
/**
 * @package Abricos
 * @subpackage Mods
 * @copyright 2011-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class ModsElementTypeMenuItem
 */
class ModsElementTypeMenuItem extends SMMenuItem {

    /**
     * @var CatalogElementType
     */
    public $elType;

    /**
     *
     * @param SMMenuItem $parent
     * @param CatalogElementType $elType
     */
    public function __construct(SMMenuItem $parent, $id, $elType, $cnt){
        parent::__construct(array(
            "id" => SMMenuItem::ToGlobalId("mods", $id),
            "pid" => $parent->id,
            "lnk" => "/mods/?tp=".$elType->name,
            // "nm" => "tp_".$elType->name,
            "tl" => ($elType->titleList." (".$cnt.")")
        ));
        $this->elType = $elType;
    }
}

class ModsElementMenuItem extends SMMenuItem {

    /**
     * @var ModsElement
     */
    public $element;

    /**
     *
     * @param SMMenuItem $parent
     * @param ModsElement $el
     */
    public function __construct(SMMenuItem $parent, $id, $el, $isLink = false){
        parent::__construct(array(
            "id" => SMMenuItem::ToGlobalId("mods", $id),
            "pid" => $parent->id,
            "lnk" => ($isLink ? $el->URI() : ""),
            "nm" => $el->name,
            "tl" => $el->title
        ));

        $this->element = $el;
    }
}

?>