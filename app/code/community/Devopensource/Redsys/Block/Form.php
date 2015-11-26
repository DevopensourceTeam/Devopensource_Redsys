<?php
class Devopensource_Redsys_Block_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        $this->setTemplate('redsys/form.phtml');
        parent::_construct();
    }
}