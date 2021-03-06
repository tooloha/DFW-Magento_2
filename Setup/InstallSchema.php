<?php
/**
 * Created by Q-Solutions Studio
 * Date: 22.08.16
 *
 * @category    DataFeedWatch
 * @package     DataFeedWatch_Connector
 * @author      Lukasz Owczarczuk <lukasz@qsolutionsstudio.com>
 */

namespace DataFeedWatch\Connector\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /** @var \Magento\Framework\Setup\SchemaSetupInterface */
    public $setup;
    
    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    public $connection;
    
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface   $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     */
    public function install(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        if ($context instanceof \Magento\Framework\Setup\ModuleContextInterface) {
            $this->setup      = $setup;
            $this->connection = $this->setup->getConnection();

            $this->extendsCatalogEavAttributeTable();
            $this->createUpdatedProductsTable();
        }
    }

    /**
     *
     */
    public function extendsCatalogEavAttributeTable()
    {
        $table = $this->setup->getTable('catalog_eav_attribute');
        
        $this->createCanConfigureInheritanceColumn($table);
        $this->createInheritanceColumn($table);
        $this->createCanConfigureImportColumn($table);
        $this->createImportToDfwColumn($table);
    }
    
    /**
     * @param string $table
     */
    public function createCanConfigureInheritanceColumn($table)
    {
        $properties = [
            'type'     => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default'  => 1,
            'comment'  => 'Can configure inheritance field? 1 - YES, 0 - NO',
        ];
        $this->addColumn($table, 'can_configure_inheritance', $properties);
    }
    
    /**
     * @param       $table
     * @param       $columnName
     * @param array $properties
     */
    public function addColumn($table, $columnName, array $properties)
    {
        $this->setup->startSetup();
        if (!$this->connection->tableColumnExists($table, $columnName)) {
            $this->connection->dropColumn($table, $columnName);
        }
        $this->connection->addColumn($table, $columnName, $properties);
        $this->setup->endSetup();
    }
    
    /**
     * @param string $table
     */
    public function createInheritanceColumn($table)
    {
        $properties = [
            'type'     => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default'  => 1,
            'comment'  => 'Inheritance: 1 - Child, 2 - Parent, 3 - Child Then Parent',
        ];
        $this->addColumn($table, 'inheritance', $properties);
    }
    
    /**
     * @param string $table
     */
    public function createCanConfigureImportColumn($table)
    {
        $properties = [
            'type'     => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default'  => 1,
            'comment'  => 'Can configure import field? 1 - YES, 0 - NO',
        ];
        $this->addColumn($table, 'can_configure_import', $properties);
    }
    
    /**
     * @param string $table
     */
    public function createImportToDfwColumn($table)
    {
        $properties = [
            'type'     => Table::TYPE_SMALLINT,
            'unsigned' => true,
            'nullable' => false,
            'default'  => 1,
            'comment'  => 'Should import attribute? 1 - YES, 0 - NO',
        ];
        $this->addColumn($table, 'import_to_dfw', $properties);
    }
    
    public function createUpdatedProductsTable()
    {
        $table = $this->setup->getTable('datafeedwatch_updated_products');
        $this->setup->startSetup();
        if ($this->connection->isTableExists($table)) {
            $this->connection->dropTable($table);
        }
        $updatedProductsTable = $this->connection->newTable($table)
            ->addColumn(
                'dfw_prod_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true,
                ],
                'Product ID'
            )->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => true,
                ],
                'Updated At'
            )->addIndex(
                $this->setup->getIdxName(
                    $table,
                    ['dfw_prod_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['dfw_prod_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->setComment('Updated Products Table');
        $this->connection->createTable($updatedProductsTable);
        $this->setup->endSetup();
    }
}
