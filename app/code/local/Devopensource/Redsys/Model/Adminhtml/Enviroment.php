<?php
class Devopensource_Redsys_Model_Adminhtml_Enviroment
{

    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Real Enviroment')),
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Test Enviroment')),
            array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('Custom Enviroment')),
        );
    }

}
