<?php
use Phinx\Migration\AbstractMigration;

class AddProductFlags extends AbstractMigration
{
    public function change(): void
    {
        $this->table('products')
            ->addColumn('is_hidden', 'boolean', ['default' => 0])
            ->addColumn('is_sold_out', 'boolean', ['default' => 0])
            ->update();
    }
}
