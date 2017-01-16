<?php

class Devopensource_Redsys_Model_Cron {

    public function cancelUnpaidOrders(){

        $enable = Mage::getStoreConfig('payment/redsys/cancel_unpaid_orders', Mage::app()->getStore());

        if(!$enable){
            return;
        }

        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('status', array('in' => array('pending')))
        // ->addFieldToFilter('state', array('in' => array('new')))
            ->setOrder('created_at', 'desc');

        foreach ($orders as $_order){

            $_paymentMethod = $_order->getPayment()->getMethod();

            Mage::log( $_order->getIncrementId(), null, 'check_payment.log');

            if($_paymentMethod != Devopensource_Redsys_Model_Redsys::CODE){
                continue;
            }

            $_format             = 'Y-m-d H:i:s';
            $_dateCreatedAt      = date($_format, Mage::getModel('core/date')->timestamp($_order->getCreatedAt()));
            $_dateToday          = Mage::getModel('core/date')->date($_format);
            $_dateToCancel       = date($_format, strtotime( $_dateCreatedAt. ' + '.Mage::getStoreConfig('payment/redsys/cancel_unpaid_orders_min', Mage::app()->getStore()).' minutes'));

            Mage::log( $_order->getIncrementId(), null, 'redsys_unpaid_orders_date.log');
            Mage::log( $_order->getCreatedAt(), null, 'redsys_unpaid_orders_date.log');
            Mage::log($_dateToCancel, null, 'redsys_unpaid_orders_date.log');
            Mage::log($_dateToday, null, 'redsys_unpaid_orders_date.log');
            Mage::log($_dateCreatedAt, null, 'redsys_unpaid_orders_date.log');

            if( $_dateToday > $_dateToCancel){


                $comment = "cancelado por cron, pedido no pagado desde redsys";
                $_order->getPayment()->cancel();
                $_order->registerCancellation($comment);
                Mage::dispatchEvent('order_cancel_after', array('order' => $_order));
                $_order->save();

                Mage::log($_order->getIncrementId().' cancelado, pedido no pagado desde redsys', null, 'redsys_unpaid_orders.log');
            }

        }
    }
}