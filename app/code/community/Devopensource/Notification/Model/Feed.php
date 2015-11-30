<?php
/**
* @category Devopensource
* @package Devopensource_Notification
* @author Jose Ruzafa <jose.ruzafa@devopensource.com>
* @version 0.1.0
* @copyright Copyright (c) 2015 Devopensource
* @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*/

class Devopensource_Notification_Model_Feed extends Mage_Core_Model_Abstract {

    protected $_frequency = 24;
    CONST DEVOPENSOURCE_URL_NOTIFICATIONS = 'http://modules.devopensource.com/checknotifications';

    public function checkUpdate()
    {
        if (($this->getFrequency() + $this->getLastUpdate()) > time()) {
            return $this;
        }

        $feedData           = array();
        $feedDataDefault    = array();
        $feedJson           = $this->getFeedData();

        if ( $feedJson ) {

            foreach ($feedJson->items as $key => $item) {
                $feedData[] = array(
                    'title'         => (string)$item->title,
                    'module'        => (string)$item->module,
                    'description'   => (string)$item->description,
                    'severity'      => (int)$item->severity,
                    'id_message'    => (string)$item->id_message,
                    'url'           => (string)$item->url
                );

                $feedDataDefault[] = array(
                    'severity'      => $feedData[$key]['severity'],
                    'date_added'    => now(),
                    'title'         => $feedData[$key]['title'],
                    'description'   => $feedData[$key]['description'],
                    'url'           => $feedData[$key]['url']
                );
            }

            if ($feedData) {
                Mage::getModel('devopennotify/notification')->parse($feedData);
                Mage::getModel('adminnotification/inbox')->parse(array_reverse($feedDataDefault));
            }

        }

        $this->setLastUpdate();

        return $this;
    }

    public function getDate($rssDate)
    {
        return gmdate('Y-m-d H:i:s', strtotime($rssDate));
    }

    public function getFrequency()
    {
        return  $this->_frequency  * 3600;
    }

    public function getLastUpdate()
    {
        return Mage::app()->loadCache('devopennotify_notifications_lastcheck');
    }

    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), 'devopennotify_notifications_lastcheck');
        return $this;
    }

    public function getFeedData()
    {

        $dataModules = Mage::helper('devopennotify')->devopensourceModulesLoaded();
        $jsonModules = json_encode($dataModules);

        $curl = new Varien_Http_Adapter_Curl();

        $curl->setConfig(array(
            'timeout'   => 2
        ));

        $headers = array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Content-Length: " .strlen($jsonModules)
        );

        $curl->write(Zend_Http_Client::POST, self::DEVOPENSOURCE_URL_NOTIFICATIONS, null, $headers, $jsonModules);

        $data = $curl->read();

        if ($data === false) {
            return false;
        }
        $data = preg_split('/^\r?$/m', $data, 2);
        $data = trim($data[1]);
        $curl->close();

        try {
            $json  = json_decode($data);
        }
        catch (Exception $e) {
            return false;
        }

        return $json;
    }

}