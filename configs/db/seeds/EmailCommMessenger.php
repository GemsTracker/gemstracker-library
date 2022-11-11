<?php


use Phinx\Seed\AbstractSeed;

class EmailCommMessenger extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $now = new \DateTimeImmutable();
        $data = [
            [
                'gcm_id_messenger' => 1300,
                'gcm_id_order' => 10,
                'gcm_type' => 'mail',
                'gcm_name' => 'E-mail',
                'gcm_description' => 'Send by E-mail',
                'gcm_messenger_identifier' => NULL,
                'gcm_active' => 1,
                'gcm_changed' => $now->format('Y-m-d H:i:s'),
                'gcm_changed_by' => 1,
                'gcm_created' => $now->format('Y-m-d H:i:s'),
                'gcm_created_by' => 1,
            ],
        ];

        $messengers = $this->table('gems__comm_messengers');
        $messengers->insert($data)
              ->saveData();
    }
}
