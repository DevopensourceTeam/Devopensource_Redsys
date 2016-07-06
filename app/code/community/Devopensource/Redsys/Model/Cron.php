<?php

class Devopensource_Redsys_Model_Cron {

    public function cancelUnpaidOrders(){

        $enable = Mage::getStoreConfig('payment/redsys/cancel_unpaid_orders', Mage::app()->getStore());

        if(!$enable){
            return;
        }

        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('status', array('in' => array('pending')))
            ->setOrder('created_at', 'desc');

        foreach ($orders as $_order){

            $_paymentMethod = $_order->getPayment()->getMethod();

            if($_paymentMethod != Devopensource_Redsys_Model_Redsys::CODE){
                continue;
            }

            $_format             = 'Y-m-d H:i:s';
            $_dateCreatedAt      = date($_format, Mage::getModel('core/date')->timestamp($_order->getCreatedAt()));
            $_dateToday          = Mage::getModel('core/date')->date($_format);
            $_dateToCancel       = date($_format, strtotime( $_dateCreatedAt. ' + '.Mage::getStoreConfig('payment/redsys/cancel_unpaid_orders_min', Mage::app()->getStore()).' minutes'));

            if( $_dateToday > $_dateToCancel){

                $_order->cancel()->save();
            }

        }
    }
}