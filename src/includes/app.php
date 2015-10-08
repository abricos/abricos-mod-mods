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
        $appConfig = new CatalogAppConfig('mods');
        $appConfig->elementNameChange = true;
        $appConfig->elementNameUnique = true;
        $appConfig->elementCreateBaseTypeDisable = true;
        $appConfig->versionControl = true;

        parent::__construct($manager, $appConfig);
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