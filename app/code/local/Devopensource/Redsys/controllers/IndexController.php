<?php

require_once(Mage::getBaseDir('lib') . '/Redsys/apiRedsys.php');

class Devopensource_Redsys_IndexController extends Mage_Core_Controller_Front_Action {
    private $helper;

    public function redirectAction()
    {
        $this->helper = Mage::helper('devopensource_redsys');

        $_order = new Mage_Sales_Model_Order();
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $_order->loadByIncrementId($orderId);

        $nameStore = Mage::getStoreConfig('payment/redsys/namestore', Mage::app()->getStore());
        $merchantcode = Mage::getStoreConfig('payment/redsys/merchantcode', Mage::app()->getStore());
        $sha256key = Mage::getStoreConfig('payment/redsys/sha256key', Mage::app()->getStore());
        $terminal = Mage::getStoreConfig('payment/redsys/terminal', Mage::app()->getStore());
        $transaction = Mage::getStoreConfig('payment/redsys/transaction', Mage::app()->getStore());

        $productsDescription = $this->helper->getDescriptionOrder($_order);
        $urlStore            = $this->helper->getUrlStore();
        $language            = $this->helper->getLanguages();
        $currency            = $this->helper->getCurrency();

        $this->helper->stateInTpv($_order);

        $transaction_amount = number_format($_order->getTotalDue(), 2, '', '');
        $amount = (float)$transaction_amount;

        $payMethods = "C";

        $urlOK = Mage::getBaseUrl() . 'redsys/index/success';
        $urlKO = Mage::getBaseUrl() . 'redsys/index/cancel';

        $redsys = new RedsysAPI;
        $redsys->setParameter("DS_MERCHANT_AMOUNT", $amount);
        $redsys->setParameter("DS_MERCHANT_ORDER", strval($orderId));
        $redsys->setParameter("DS_MERCHANT_MERCHANTCODE", $merchantcode);
        $redsys->setParameter("DS_MERCHANT_CURRENCY", $currency);
        $redsys->setParameter("DS_MERCHANT_TRANSACTIONTYPE", $transaction);
        $redsys->setParameter("DS_MERCHANT_TERMINAL", $terminal);
        $redsys->setParameter("DS_MERCHANT_MERCHANTURL", $urlStore);
        $redsys->setParameter("DS_MERCHANT_URLOK", $urlOK);
        $redsys->setParameter("DS_MERCHANT_URLKO", $urlKO);
        $redsys->setParameter("Ds_Merchant_ConsumerLanguage", $language);
        $redsys->setParameter("Ds_Merchant_ProductDescription", $productsDescription);
        $redsys->setParameter("Ds_Merchant_Titular", $nameStore);
        $redsys->setParameter("Ds_Merchant_MerchantName", $nameStore);
        $redsys->setParameter("Ds_Merchant_PayMethods", $payMethods);

        $version = "HMAC_SHA256_V1";
        $paramsBase64 = $redsys->createMerchantParameters();
        $signatureMac = $redsys->createMerchantSignature($sha256key);

        echo ('
		        <form action="'.$this->helper->getUrlEnviroment().'" method="post" id="redsys" name="redsys">
				<input type="hidden" name="Ds_SignatureVersion" value="'.$version.'" />
				<input type="hidden" name="Ds_MerchantParameters" value="'.$paramsBase64.'" />
				<input type="hidden" name="Ds_Signature" value="'.$signatureMac.'" />
				</form>
			
				<h3> '.$this->__('Redirecting the TPV please wait...').'</h3>
				
				<script type="text/javascript">
					document.redsys.submit();
				</script>'
        );
    }

    public function callbackAction()
    {
        $this->helper = Mage::helper('devopensource_redsys');

        $params = $this->getRequest()->getPost();
        if (count($params) > 0){
            $data    = $_POST["Ds_MerchantParameters"];
            $signature_response    = $_POST["Ds_Signature"];

            $redsys = new RedsysAPI;
            $redsys->decodeMerchantParameters($data);

            $sha256key = Mage::getStoreConfig('payment/redsys/sha256key',Mage::app()->getStore());
            $signature = $redsys->createMerchantSignatureNotif($sha256key,$data);

            $amount     = $redsys->getParameter('Ds_Amount');
            $orderId      = $redsys->getParameter('Ds_Order');
            $merchantcode    = $redsys->getParameter('Ds_MerchantCode');
            $terminal  = $redsys->getParameter('Ds_Terminal');
            $response = $redsys->getParameter('Ds_Response');
            $transaction = $redsys->getParameter('Ds_TransactionType');

            $merchantcodemagento = Mage::getStoreConfig('payment/redsys/merchantcode',Mage::app()->getStore());
            $terminalmagento = Mage::getStoreConfig('payment/redsys/terminal',Mage::app()->getStore());
            $transactionmagento =Mage::getStoreConfig('payment/redsys/transaction',Mage::app()->getStore());


            if ($signature === $signature_response
                && isset($orderId)
                && $transaction == $transactionmagento
                && $merchantcode == $merchantcodemagento
                && intval(strval($terminalmagento)) == intval(strval($terminal))
            ) {
                $responsecode = intval($response);
                if ($responsecode <= 99){
                    $authorisationcode = $redsys->getParameter('Ds_AuthorisationCode');
                    $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
                    $transaction_amount = number_format($order->getTotalDue(), 2, '', '');
                    $amountOrder = (float)$transaction_amount;

                    if ($amountOrder != $amount) {
                        $order->addStatusHistoryComment($this->__("Error: Amount is diferent"),false);
                        $this->helper->stateErrorTpv($order);
                        $this->helper->restoreStock($order);
                    }

                    try {
                        $this->helper->createInvoice($order);
                        $comment = $this->__('TPV payment accepted. (response: %s, authorization: %s)',$response,$authorisationcode);
                        $this->helper->stateConfirmTpv($order,$comment);
                        $order->sendNewOrderEmail();
                    } catch (Exception $e) {
                        $order->addStatusHistoryComment($this->__("TPV Error: %s",$e->getMessage()), false);
                        $order->save();
                    }
                } else {
                    $errorMessage = $this->helper->comentarioReponse($responsecode)." ".$this->__("(response:%s)",$response);
                    $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
                    $this->helper->stateErrorTpv($order,$errorMessage);
                    $this->helper->restoreStock($order);
                }

            } else {
                $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
                $order->addStatusHistoryComment($this->__("Error: Signature is wrong"),false);
                $this->helper->stateErrorTpv($order);
                $this->helper->restoreStock($order);

            }


        }else{
            $this->_redirect('');
        }
    }

    public function cancelAction()
    {
        $this->helper = Mage::helper('devopensource_redsys');
        $session = Mage::getSingleton('checkout/session');
        $this->helper->recoveryCart();
        $session->addError($this->__('Denied transaction from Redsys.'));
        $this->_redirect('checkout/cart');

    }

    public function successAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->addsuccess($this->__('Authorized transaction'));
        $this->_redirect('checkout/onepage/success');
    }
}