<?php

class Devopensource_Redsys_Model_Redsys extends Mage_Payment_Model_Method_Abstract
{

    protected $_code = 'redsys';
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc = false;
    protected $_formBlockType = 'devopensource_redsys/form';


    public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('redsys/index/redirect', array('_secure' => true));
	}
}

