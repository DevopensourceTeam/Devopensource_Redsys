<?php
/**
* @category Devopensource
* @package Devopensource_Notification
* @author Jose Ruzafa <jose.ruzafa@devopensource.com>
* @version 0.1.0
* @copyright Copyright (c) 2015 Devopensource
* @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*/

class Devopensource_Notification_Model_Observer {

    public function preDispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            $feedModel = Mage::getModel('devopennotify/feed');
            /* @var $feedModel Mage_AdminNotification_Model_Feed */

            $feedModel->checkUpdate();
        }
    }
}