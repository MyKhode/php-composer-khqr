<?php
use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class IncreasePhotoBase64Size extends AbstractMigration
{
    public function change(): void
    {
        // Change products.photo_base64 from TEXT to MEDIUMTEXT for larger base64 images
        $this->table('products')
            ->changeColumn('photo_base64', 'text', [
                'null'  => true,
                'limit' => MysqlAdapter::TEXT_MEDIUM,
            ])
            ->update();
    }
}
