<?php
use Phinx\Migration\AbstractMigration;

class CreateCoreTables extends AbstractMigration
{
    public function change(): void
    {
        // admin_users
        $this->table('admin_users')
            ->addColumn('username', 'string', ['limit' => 64])
            ->addColumn('password_hash', 'string', ['limit' => 255])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['username'], ['unique' => true])
            ->create();

        // products
        $this->table('products')
            ->addColumn('category', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('name', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('photo_base64', 'text', ['null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('info', 'text', ['null' => true])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('link', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('delivery_type', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'active'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->create();

        // license_keys
        $this->table('license_keys')
            ->addColumn('product_id', 'integer', ['signed' => false])
            ->addColumn('license_key', 'string', ['limit' => 255])
            ->addColumn('is_sold', 'boolean', ['default' => 0])
            ->addColumn('sold_at', 'datetime', ['null' => true])
            ->addColumn('order_id', 'integer', ['null' => true, 'signed' => false])
            ->addForeignKey('product_id', 'products', 'id', ['delete'=> 'CASCADE'])
            ->create();

        // orders
        $this->table('orders')
            ->addColumn('transaction_id', 'string', ['limit' => 64])
            ->addColumn('buyer_name', 'string', ['limit' => 150])
            ->addColumn('buyer_phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('buyer_telegram', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('buyer_address', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('buyer_message', 'text', ['null' => true])
            ->addColumn('total_amount', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'pending'])
            ->addColumn('delivery_status', 'string', ['limit' => 30, 'default' => 'preparing'])
            ->addColumn('receipt_base64', 'text', ['null' => true])
            ->addColumn('khqr_md5', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addIndex(['transaction_id'], ['unique' => true])
            ->create();

        // order_items
        $this->table('order_items')
            ->addColumn('order_id', 'integer', ['signed' => false])
            ->addColumn('product_id', 'integer', ['signed' => false])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('license_key_id', 'integer', ['null' => true, 'signed' => false])
            ->addForeignKey('order_id', 'orders', 'id', ['delete'=> 'CASCADE'])
            ->addForeignKey('product_id', 'products', 'id', ['delete'=> 'SET_NULL'])
            ->addForeignKey('license_key_id', 'license_keys', 'id', ['delete'=> 'SET_NULL'])
            ->create();
    }
}
