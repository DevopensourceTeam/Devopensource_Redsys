<?php
/**
* @category Devopensource
* @package Devopensource_Notification
* @author Jose Ruzafa <jose.ruzafa@devopensource.com>
* @version 0.1.0
* @copyright Copyright (c) 2015 Devopensource
* @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*/

class Devopensource_Notification_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template {

    public function getMessage()
    {

        $notification = Mage::getModel('devopennotify/notification')->getCollection()
            ->addFieldToFilter('read', 0);

        return $notification;
    }

}