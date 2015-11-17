<?php
class Devopensource_Redsys_Model_Adminhtml_Currency
{

    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('EURO')),
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('DOLAR')),
        );
    }

}
