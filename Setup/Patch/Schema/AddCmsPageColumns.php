<?php
namespace WeltPixel\GoogleCards\Setup\Patch\Schema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class AddCmsPageColumns implements SchemaPatchInterface, PatchVersionInterface
{
    /**
     * @var SchemaSetupInterface $schemaSetup
     */
    private $schemaSetup;

    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup
    )
    {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();
        $setup = $this->schemaSetup;
        $connection = $setup->getConnection();

        $connection->addColumn(
            $setup->getTable('cms_page'),
            'og_meta_image',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'comment' => 'SEO Meta Image',
            ]
        );
        $connection->addColumn(
            $setup->getTable('cms_page'),
            'og_meta_image_alt',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'comment' => 'Open Graph Image Alt',
            ]
        );

        $this->schemaSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.6';
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
