<?php
use Phinx\Migration\AbstractMigration;

class AddDeliveryToOrderItems extends AbstractMigration
{
    public function change(): void
    {
        $this->table('order_items')
            ->addColumn('delivery_status', 'string', ['limit' => 30, 'default' => 'preparing'])
            ->addColumn('proof_image_base64', 'text', ['null' => true])
            ->update();
    }
}
