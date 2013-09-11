<?php 
/**
 * @package Abricos
 * @subpackage Mods
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class ModsMenuItem extends SMMenuItem {
	
	/**
	 * @var ModsModule
	 */
	public $mod;
	
	/**
	 * 
	 * @param SMMenuItem $parent
	 * @param ModsModule $el
	 */
	public function __construct(SMMenuItem $parent, $el){
		parent::__construct(array(
			"id" => SMMenuItem::ToGlobalId("mods", $el->id),
			"pid" => $parent->id,
			"nm" => $el->name,
			"tl" => $el->title
		));
		
		$this->mod = $el;
	}
}

?>