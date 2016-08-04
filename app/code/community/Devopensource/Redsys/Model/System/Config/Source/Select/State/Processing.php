<?php
/**
* @category Devopensource
* @package Devopensource_
* @author Jose Ruzafa <jose.ruzafa@devopensource.com>
* @version 0.1.0
* @copyright Copyright (c) 2016 Devopensource
* @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*/


class Devopensource_Redsys_Model_System_Config_Source_Select_State_Processing{
    public function toOptionArray(){
        $statuses = Mage::getSingleton('sales/order_config')->getStateStatuses(array(Mage_Sales_Model_Order::STATE_PROCESSING),true);
        return $statuses;
    }
}