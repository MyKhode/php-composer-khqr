<?php
use Phinx\Migration\AbstractMigration;

class AddUpdatedAtToOrderItems extends AbstractMigration
{
    public function change(): void
    {
        $this->table('order_items')
            ->addColumn('updated_at', 'datetime', [
                'null' => true,
                'after' => 'proof_image_base64',
            ])
            ->update();
    }
}
