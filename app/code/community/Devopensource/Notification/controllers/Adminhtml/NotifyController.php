<?php
/**
* @category Devopensource
* @package Devopensource_Notification
* @author Jose Ruzafa <jose.ruzafa@devopensource.com>
* @version 0.1.0
* @copyright Copyright (c) 2015 Devopensource
* @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*/

class Devopensource_Notification_Adminhtml_NotifyController extends Mage_Adminhtml_Controller_Action {

    protected $_notification;

    public function readAction(){

        $id = $this->getRequest()->getParam('id');

        $this->_readNotification($id);

        //marcar como leido en el inbox de magento
        $this->_readNotificationInbox($id);

        $this->_redirectReferer();
    }

    protected function _readNotification ($idNotification){

        $notification           = Mage::getModel('devopennotify/notification')->load($idNotification);

        // Guardamos la notificacion en variable para poder ser usada en la funcion _readNotificationInbox
        $this->_notification    = $notification;

        $notification->setIsRead(1);
        $notification->save();
    }

    protected function _readNotificationInbox(){

        $notification   =  Mage::getModel('adminnotification/inbox')->load($this->_notification->getTitle(), 'title');
        $notification->setIsRead(true);
        $notification->save();
    }
}