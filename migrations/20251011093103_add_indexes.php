<?php
use Phinx\Migration\AbstractMigration;

class AddIndexes extends AbstractMigration
{
    public function change(): void
    {
        $this->table('products')->addIndex(['name'])->save();
        $this->table('license_keys')->addIndex(['license_key'], ['unique' => true])->save();
        $this->table('orders')->addIndex(['status'])->save();
    }
}
