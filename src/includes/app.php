<?php
/**
 * @package Abricos
 * @subpackage Mods
 * @copyright 2011-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class ModsApp
 *
 * @property ModsManager $manager
 */
class ModsApp extends CatalogApp {

    public function __construct(ModsManager $manager){
        parent::__construct($manager);

        $config = $this->Config();
        $config->dbPrefix = 'mods';
        $config->elementNameChange = true;
        $config->elementNameUnique = true;
        $config->elementCreateBaseTypeDisable = true;
        $config->versionControl = true;
    }

    public function IsAdminRole(){
        return $this->manager->IsAdminRole();
    }

    public function IsModeratorRole(){
        return $this->manager->IsModeratorRole();
    }

    public function IsOperatorRole(){
        return $this->manager->IsOperatorRole();
    }

    public function IsWriteRole(){
        return $this->manager->IsWriteRole();
    }

    public function IsViewRole(){
        return $this->manager->IsViewRole();
    }
}

?>