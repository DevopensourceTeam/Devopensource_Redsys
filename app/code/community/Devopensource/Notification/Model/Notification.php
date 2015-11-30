<?php
/**
* @category Devopensource
* @package Devopensource_Notification
* @author Jose Ruzafa <jose.ruzafa@devopensource.com>
* @version 0.1.0
* @copyright Copyright (c) 2015 Devopensource
* @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*/ 

class Devopensource_Notification_Model_Notification extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('devopennotify/notification');
    }

    public function parse(array $data)
    {
        foreach ($data as $_dat){

            $notification = Mage::getModel('devopennotify/notification')->getCollection()
                ->addFieldToFilter('id_message', $_dat['id_message'])
                ->count();

            if ($notification == 0) {

                $notificationNew               = Mage::getModel('devopennotify/notification');
                $notificationNew->title        = $_dat['title'];
                $notificationNew->module       = $_dat['module'];
                $notificationNew->description  = $_dat['description'];
                $notificationNew->severity     = $_dat['severity'];
                $notificationNew->id_message   = $_dat['id_message'];
                $notificationNew->url          = $_dat['url'];

                $notificationNew->save();
            }
        }
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();

        $this->_updateTimestamps();

        return $this;
    }

    protected function _updateTimestamps()
    {
        $timestamp = now();

        /**
         * If we have a brand new object, set the created timestamp.
         */
        if ($this->isObjectNew()) {
            $this->setCreatedAt($timestamp);
        }
    }

}