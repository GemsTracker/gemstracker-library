<?php


use Phinx\Seed\AbstractSeed;

class DefaultConsents extends AbstractSeed
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
                'gco_description' => 'Yes',
                'gco_order' => 10,
                'gco_code' => 'consent given',
                'gco_changed' => $now->format('Y-m-d H:i:s'),
                'gco_changed_by' => 1,
                'gco_created' => $now->format('Y-m-d H:i:s'),
                'gco_created_by' => 1,
            ],
            [
                'gco_description' => 'No',
                'gco_order' => 20,
                'gco_code' => 'do not use',
                'gco_changed' => $now->format('Y-m-d H:i:s'),
                'gco_changed_by' => 1,
                'gco_created' => $now->format('Y-m-d H:i:s'),
                'gco_created_by' => 1,
            ],
            [
                'gco_description' => 'Unknown',
                'gco_order' => 30,
                'gco_code' => 'do not use',
                'gco_changed' => $now->format('Y-m-d H:i:s'),
                'gco_changed_by' => 1,
                'gco_created' => $now->format('Y-m-d H:i:s'),
                'gco_created_by' => 1,
            ],
        ];

        $consents = $this->table('gems__consents');
        $consents->insert($data)
              ->saveData();
    }
}
