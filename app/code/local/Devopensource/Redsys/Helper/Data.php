<?php
class Devopensource_Redsys_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getMessage(){
        return Mage::getStoreConfig('payment/redsys/message_credit_card', Mage::app()->getStore());
    }


    public function getDescriptionOrder($_order){
        $descriptionOrder = '';
        $items = $_order->getAllVisibleItems();
        $pos = 0;

        foreach ($items as $itemId => $item) {
            $descriptionOrder .= $item->getName();
            $descriptionOrder .= " x " . $item->getQtyToInvoice();

            $pos++;
            if($pos<count($items)){
                $descriptionOrder .= " , ";
            }
        }

        return $descriptionOrder;
    }

    public function getUrlStore(){
        $url = Mage::getStoreConfig('payment/redsys/callback', Mage::app()->getStore());

        if ($url==0) {
            return Mage::getStoreConfig('web/unsecure/base_url', Mage::app()->getStore())."redsys/index/callback";
        }elseif($url==1){
            return Mage::getStoreConfig('web/secure/base_url', Mage::app()->getStore())."redsys/index/callback";
        }else{
            return Mage::getStoreConfig('payment/redsys/callbackurl', Mage::app()->getStore());
        }
    }

    public function getUrlEnviroment(){
        $enviroment = Mage::getStoreConfig('payment/redsys/enviroment', Mage::app()->getStore());

        if ($enviroment==0) {
            return "https://sis.redsys.es/sis/realizarPago/utf-8";
        }elseif($enviroment==1){
            return "https://sis-t.redsys.es:25443/sis/realizarPago/utf-8";
        }else{
            return Mage::getStoreConfig('payment/redsys/alternativeenviroment', Mage::app()->getStore());
        }
    }

    public function getLanguages(){
        $locale = substr(Mage::getStoreConfig('general/locale/code', Mage::app()->getStore()->getId()), 0, 2);
        switch ($locale) {
            case 'es':
                $language = '001';
                break;
            case 'en':
                $language = '002';
                break;
            case 'ca':
                $language = '003';
                break;
            case 'fr':
                $language = '004';
                break;
            case 'de':
                $language = '005';
                break;
            case 'nl':
                $language = '006';
                break;
            case 'it':
                $language = '007';
                break;
            case 'sv':
                $language = '008';
                break;
            case 'pt':
                $language = '009';
                break;
            case 'pl':
                $language = '011';
                break;
            case 'gl':
                $language = '012';
                break;
            case 'eu':
                $language = '013';
                break;
            default:
                $language = '001';
        }

        return $language;

    }

    public function getCurrency(){
        $currency = Mage::getStoreConfig('payment/redsys/currency', Mage::app()->getStore());

        if ($currency == "0") {
            $currency = "978";
        } else {
            $currency = "840";
        }

        return $currency;
    }

    public function stateInTpv($_order){
        $this->fixCreditCustomer();
        $status = Mage::getStoreConfig('payment/redsys/redirect_status', Mage::app()->getStore());
        $state = 'new';
        $comment = $this->__('enters TPV');
        $isCustomerNotified = true;
        $_order->setState($state, $status, $comment, $isCustomerNotified);
        $_order->save();
    }

    public function stateConfirmTpv($_order){
        $this->fixCreditCustomer();
        $status = Mage::getStoreConfig('payment/redsys/confirm_status', Mage::app()->getStore());
        $state = 'processing';
        $comment = $this->__('TPV payment accepted.');
        $isCustomerNotified = true;
        $_order->setState($state, $status, $comment, $isCustomerNotified);
        $_order->save();
    }

    public function stateErrorTpv($_order,$errorMessage=null){
        $this->fixCreditCustomer();
        $status = Mage::getStoreConfig('payment/redsys/error_status', Mage::app()->getStore());
        $state = 'canceled';
        $comment = $this->__('Error in TPV order canceled.');
        $isCustomerNotified = true;

        if($errorMessage){
            $comment = $this->__('Failed: %s',$errorMessage);
        }

        $_order->setState($state, $status, $comment, $isCustomerNotified);
        $_order->registerCancellation("")->save();
        $_order->save();
    }


    public function recoveryCart(){
        $recoveryCart = Mage::getStoreConfig('payment/redsys/recover_cart', Mage::app()->getStore());

        if($recoveryCart){
            $_order = new Mage_Sales_Model_Order();
            $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
            $_order->loadByIncrementId($orderId);

            $items = $_order->getAllVisibleItems();
            $cart = Mage::getSingleton('checkout/cart');
            foreach ($items as $itemId => $item){
                $cart->addOrderItem($item);
            }
            $cart->save();

            //@todo Volver a poner la direccion y reenviar al checkout
        }


    }

    public function restoreStock($order){
        $items = $order->getAllItems();
        if($items)
        {
            foreach($items as $item)
            {
                $quantity = $item->getQtyOrdered();
                $product_id = $item->getProductId();
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_id);
                $stockQty = $stock->getQty();
                $stock->setQty($stockQty + $quantity);

                if($stockQty + $quantity <= 0)
                {
                    $stock->setIsInStock(false);
                }

                $stock->save();
            }
        }


    }

    public function createInvoice($order){
        $autoinvoice = Mage::getStoreConfig('payment/redsys/autoinvoice', Mage::app()->getStore());
        if($autoinvoice){
            $this->fixCreditCustomer();
            $invoice = $order->prepareInvoice();
            $invoice->register()->capture();
            Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();

            $this->fixCreditCustomer();
            $order->addStatusHistoryComment($this->__('Invoice %s created', $invoice->getIncrementId()), false);
            $order->save();
        }
    }


    public function comentarioReponse($Ds_Response, $Ds_pay_method='')
    {
        switch($Ds_Response)
        {
            case '101':
                return 'Tarjeta caducada';
            case '102':
                return 'Tarjeta en excepcion transitoria o bajo sospecha de fraude';
            case '104':
                return 'Operacion no permitida para esa tarjeta o terminal';
            case '106':
                return 'Intentos de PIN excedidos';
            case '116':
                return 'Disponible insuficiente';
            case '118':
                return 'Tarjeta no registrada';
            case '125':
                return 'Tarjeta no efectiva.';
            case '129':
                return 'Codigo de seguridad (CVV2/CVC2) incorrecto';
            case '180':
                return 'Tarjeta ajena al servicio';
            case '184':
                return 'Error en la autenticacion del titular';
            case '190':
                return 'Denegacion sin especificar Motivo';
            case '191':
                return 'Fecha de caducidad erronea';
            case '201':
                return 'Transacción denegada porque la fecha de caducidad de la tarjeta que se ha informado en el pago, es anterior a la actualmente vigente';
            case '202':
                return 'Tarjeta en excepcion transitoria o bajo sospecha de fraude con retirada de tarjeta';
            case '204':
                return 'Operación no permitida para ese tipo de tarjeta';
            case '207':
                return 'El banco emisor no permite una autorización automática. Es necesario contactar telefónicamente con su centro autorizador para obtener una aprobación manual';
            case '208':
            case '209':
                return 'Tarjeta bloqueada por el banco emisor debido a que el titular le ha manifestado que le ha sido robada o perdida';
            case '208':
                return 'Es erróneo el código CVV2/CVC2 informado por el comprador';
            case '290':
                return 'Transacción denegada por el banco emisor pero sin que este dé detalles acerca del motivo';
            case '904':
                return 'Comercio no registrado en FUC.';
            case '909':
                return 'Error de sistema.';
            case '913':
                return 'Pedido repetido.';
            case '930':
                if($Ds_pay_method == 'R')
                {
                    return 'Realizado por Transferencia bancaria';
                } else
                {
                    return 'Realizado por Domiciliacion bancaria';
                }
            case '944':
                return 'Sesión Incorrecta.';
            case '950':
                return 'Operación de devolución no permitida.';
            case '9064':
                return 'Número de posiciones de la tarjeta incorrecto.';
            case '9078':
                return 'No existe método de pago válido para esa tarjeta.';
            case '9093':
                return 'Tarjeta no existente.';
            case '9094':
                return 'Rechazo servidores internacionales.';
            case '9104':
                return 'Comercio con “titular seguro” y titular sin clave de compra segura.';
            case '9218':
                return 'El comercio no permite op. seguras por entrada /operaciones.';
            case '9253':
                return 'Tarjeta no cumple el check-digit.';
            case '9256':
                return 'El comercio no puede realizar preautorizaciones.';
            case '9257':
                return 'Esta tarjeta no permite operativa de preautorizaciones.';
            case '9261':
            case '912':
            case '9912':
                return 'Emisor no disponible';
            case '9913':
                return 'Error en la confirmación que el comercio envía al TPV Virtual (solo aplicable en la opción de sincronización SOAP).';
            case '9914':
                return 'Confirmación “KO” del comercio (solo aplicable en la opción de sincronización SOAP).';
            case '9915':
                return 'A petición del usuario se ha cancelado el pago.';
            case '9928':
                return 'Anulación de autorización en diferido realizada por el SIS (proceso batch).';
            case '9929':
                return 'Anulación de autorización en diferido realizada por el comercio.';
            case '9997':
                return 'Se está procesando otra transacción en SIS con la misma tarjeta.';
            case '9998':
                return 'Operación en proceso de solicitud de datos de tarjeta.';
            case '9999':
                return 'Operación que ha sido redirigida al emisor a autenticar.';
            default:
                return 'Transaccion denegada codigo:'.$Ds_Response;
        }
    }

    private function fixCreditCustomer(){
        if(Mage::registry('change_order_status_once')) Mage::unregister("change_order_status_once");
    }

}
