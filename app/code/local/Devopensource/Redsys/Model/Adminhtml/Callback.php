<?php
class Devopensource_Redsys_Model_Adminhtml_Callback
{

    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Http')),
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Https')),
            array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('Custom Callback')),
        );
    }

}
