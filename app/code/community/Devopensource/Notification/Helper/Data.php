<?php
/**
* @category Devopensource
* @package Devopensource_Notification
* @author Jose Ruzafa <jose.ruzafa@devopensource.com>
* @version 0.1.0
* @copyright Copyright (c) 2015 Devopensource
* @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*/ 

class Devopensource_Notification_Helper_Data extends Mage_Core_Helper_Abstract {

    public function devopensourceModulesLoaded(){

        $modules                = array_keys((array)Mage::getConfig()->getNode('modules')->children());
        $devopensourceModules   = array();

        foreach ($modules as $_module) {
            if(strpos($_module, 'Devopensource_') !== false){

                $devopensourceModules[] = array($_module, $this->getModuleVersion($_module));
            }
        }

        return $devopensourceModules;
    }

    public function getModuleVersion($_moduleName)
    {
        return (string) Mage::getConfig()->getNode('modules/'.$_moduleName.'/version');
    }
}