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


    public function isAvailable($quote = null)
    {

        $checkResult = new StdClass;
        $isActive = (bool)(int)$this->getConfigData('active', $quote ? $quote->getStoreId() : null);

        $allowedIps = Mage::getStoreConfig('dev/restrict/allow_ips',  Mage::app()->getStore());
        if(!$isActive && !empty($allowedIps) && Mage::getStoreConfig('payment/redsys/developermode', Mage::app()->getStore()) && Mage::helper('core')->isDevAllowed()){
            $isActive=true;
        }

        $checkResult->isAvailable = $isActive;
        $checkResult->isDeniedInConfig = !$isActive; // for future use in observers
        Mage::dispatchEvent('payment_method_is_active', array(
            'result'          => $checkResult,
            'method_instance' => $this,
            'quote'           => $quote,
        ));

        if ($checkResult->isAvailable && $quote) {
            $magentoVersion = Mage::getVersion();
            if (version_compare($magentoVersion, '1.8', '>=')){
                $checkResult->isAvailable = $this->isApplicableToQuote($quote, self::CHECK_RECURRING_PROFILES);
            } else {
                $implementsRecurring = $this->canManageRecurringProfiles();
                if ($quote && !$implementsRecurring && $quote->hasRecurringItems()) {
                    $checkResult->isAvailable = false;
                }
            }
        }
        return $checkResult->isAvailable;
    }
}

