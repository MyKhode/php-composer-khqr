<?php
use Phinx\Migration\AbstractMigration;

class SeedAdmin extends AbstractMigration
{
    public function up(): void
    {
        $username = getenv('ADMIN_DEFAULT_USERNAME') ?: 'admin';
        $password = getenv('ADMIN_DEFAULT_PASSWORD') ?: 'dareach@2k25';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->table('admin_users')->insert([
            'username'      => $username,
            'password_hash' => $hash,
            'created_at'    => date('Y-m-d H:i:s')
        ])->saveData();
    }

    public function down(): void
    {
        $this->execute('DELETE FROM admin_users');
    }
}
