<?php
/**
 * @category Devopensource
 * @package Devopensource_Notification
 * @author Jose Ruzafa <jose.ruzafa@devopensource.com>
 * @version 0.1.0
 * @copyright Copyright (c) 2015 Devopensource
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;

$installer->startSetup();

$table = $installer->getConnection()->newTable($installer->getTable('devopennotify/notification'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        'identity'  => true,
    ), 'Stock ID')
    ->addColumn('title', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable' => false,
    ), 'title')
    ->addColumn('module', Varien_Db_Ddl_Table::TYPE_CHAR, null, array(
        'nullable' => false,
    ), 'module')
    ->addColumn('description', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable' => true,
    ), 'description')
    ->addColumn('severity', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' =>true,
    ), 'severity')
    ->addColumn('id_message', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' =>true,
    ), 'id_message')
    ->addColumn('url', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable' => true,
    ), 'url')
    ->addColumn('read', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false,
        'default'  => 0
    ), 'read')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'created_at');

$installer->getConnection()->createTable($table);

$installer->endSetup();