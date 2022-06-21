<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsSystemuserSetup extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__systemuser_setup', [
                'id' => false,
                'primary_key' => ['gsus_id_user'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsus_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gsus_secret_key', 'string', [
                'null' => true,
                'limit' => 400,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsus_id_user',
            ])
            ->addColumn('gsus_create_user', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsus_secret_key',
            ])
            ->addColumn('gsus_authentication', 'string', [
                'null' => true,
                'default' => 'Gems\\User\\Embed\\Auth\\HourKeySha256',
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsus_create_user',
            ])
            ->addColumn('gsus_deferred_user_loader', 'string', [
                'null' => true,
                'default' => 'Gems\\User\\Embed\\DeferredUserLoader\\DeferredStaffUser',
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsus_authentication',
            ])
            ->addColumn('gsus_deferred_user_group', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gsus_deferred_user_loader',
            ])
            ->addColumn('gsus_redirect', 'string', [
                'null' => true,
                'default' => 'Gems\\User\\Embed\\Redirect\\RespondentShowPage',
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsus_deferred_user_group',
            ])
            ->addColumn('gsus_deferred_mvc_layout', 'string', [
                'null' => true,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsus_redirect',
            ])
            ->addColumn('gsus_deferred_user_layout', 'string', [
                'null' => true,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsus_deferred_mvc_layout',
            ])
            ->addColumn('gsus_hide_breadcrumbs', 'string', [
                'null' => true,
                'default' => '',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsus_deferred_user_layout',
            ])
            ->addColumn('gsus_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gsus_hide_breadcrumbs',
            ])
            ->addColumn('gsus_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsus_changed',
            ])
            ->addColumn('gsus_created', 'timestamp', [
                'null' => false,
                'after' => 'gsus_changed_by',
            ])
            ->addColumn('gsus_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsus_created',
            ])
            ->create();
    }
}