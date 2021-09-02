<?php
namespace Codilar\Afbt\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{


    /**
     * Update codilar_afbt_index table in db.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $setup->getConnection()->modifyColumn(
            $installer->getTable('codilar_afbt_index'),
            'asp_ids',
            [
                'type' => Table::TYPE_TEXT,
                10000
            ]
        );
        $installer->endSetup();
    }
}