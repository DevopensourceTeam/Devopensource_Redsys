<?php
/**
* @category Devopensource
* @package Devopensource_
* @author Jose Ruzafa <jose.ruzafa@devopensource.com>
* @version 0.1.0
* @copyright Copyright (c) 2016 Devopensource
* @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*/


class Devopensource_Redsys_Model_System_Config_Source_Multiselect_Status{

    public function toOptionArray(){
        $statuses = Mage::getSingleton('sales/order_config')->getStateStatuses(array(Mage_Sales_Model_Order::STATE_PROCESSING,Mage_Sales_Model_Order::STATE_CANCELED),true);

        $options = array();
        $options[] = array(
            'value' => '',
            'label' => Mage::helper('adminhtml')->__('-- Please Select --')
        );

        foreach($statuses as $index=>$status){
            $options[] = array(
                'label'=>$status,
                'value'=>$index
            );
        }

        return $options;
    }

}