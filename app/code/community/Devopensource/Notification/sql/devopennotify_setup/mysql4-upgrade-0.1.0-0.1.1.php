<?php
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;

$installer->startSetup();

try{
    $installer->getConnection()->changeColumn($installer->getTable('devopennotify/notification'),'read','is_read',array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'nullable'  => false,
        'default'  => 0
    ));
}catch(Exception $e){

}

$installer->endSetup();
