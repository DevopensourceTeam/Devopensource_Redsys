<?php

require_once(Mage::getBaseDir('lib') . '/Redsys/apiRedsys.php');

class Devopensource_Redsys_IndexController extends Mage_Core_Controller_Front_Action {
    private $helper;

    public function redirectAction()
    {
        $this->helper = Mage::helper('devopensource_redsys');
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $_order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        if($_order->getState() != 'new' && $_order->getStatus() != 'pending' ) {
            $response = Mage::app()->getResponse();
            $response->setRedirect(Mage::getBaseUrl());
            $response->sendResponse();
            exit;
        }

        $nameStore = Mage::getStoreConfig('payment/redsys/namestore', Mage::app()->getStore());
        $merchantcode = Mage::getStoreConfig('payment/redsys/merchantcode', Mage::app()->getStore());
        $sha256key = Mage::getStoreConfig('payment/redsys/sha256key', Mage::app()->getStore());
        $terminal = Mage::getStoreConfig('payment/redsys/terminal', Mage::app()->getStore());
        $transaction = Mage::getStoreConfig('payment/redsys/transaction', Mage::app()->getStore());

        $productsDescription = $this->helper->getDescriptionOrder($_order);
        $urlStore            = $this->helper->getUrlStore();
        $language            = $this->helper->getLanguages();
        $currency            = $this->helper->getCurrency($_order);

        $this->helper->stateInTpv($_order);

        Mage::dispatchEvent('redsys_redirect',  array('order' => $_order));

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

            $redsys     = new RedsysAPI;
            $decodeData = $redsys->decodeMerchantParameters($data);

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
                    }

                    try {
                        $comment = $this->__('TPV payment accepted. (response: %s, authorization: %s)',$response,$authorisationcode);
                        $order->sendNewOrderEmail();
                        $this->helper->stateConfirmTpv($order,$comment);

                        $this->helper->createTransaction($order,$decodeData);
                        $this->helper->createInvoice($order);

                        Mage::dispatchEvent('redsys_payment_accepted',  array('order' => $order));
                    } catch (Exception $e) {
                        $order->addStatusHistoryComment($this->__("TPV Error: %s",$e->getMessage()), false);
                        $order->save();
                    }
                } else {
                    $errorMessage = $this->helper->comentarioReponse($responsecode)." ".$this->__("(response:%s)",$response);
                    $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
                    $this->helper->stateErrorTpv($order,$errorMessage);
                }

            } else {
                $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
                $order->addStatusHistoryComment($this->__("Error: Signature is wrong"),false);
                $this->helper->stateErrorTpv($order);
            }


        }else{
            $this->_redirect('');
        }
    }

    public function cancelAction()
    {
        $this->helper = Mage::helper('devopensource_redsys');

        $error = $this->__('Denied transaction from Redsys.');

        $session = Mage::getSingleton('checkout/session');
        $_orderIncId = $session->getData('last_real_order_id');
        $_order = Mage::getModel('sales/order')->loadByIncrementId($_orderIncId);
        
        Mage::dispatchEvent('redsys_payment_cancel',  array('session' => $session));

        $this->helper->recoveryCart($_order);
        $session->addError($error);

        $this->_redirect('checkout/cart');
    }

    public function successAction()
    {
        $session = Mage::getSingleton('checkout/session');
        Mage::dispatchEvent('redsys_payment_success', array('session' => $session));
        $session->addsuccess($this->__('Authorized transaction'));
        $this->_redirect('checkout/onepage/success');
    }
}
