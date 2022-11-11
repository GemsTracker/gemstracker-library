<?php


use Phinx\Seed\AbstractSeed;

class DefaultOrganizations extends AbstractSeed
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
                'gor_id_organization' => 70,
                'gor_name' => 'New organization',
                'gor_changed' => $now->format('Y-m-d H:i:s'),
                'gor_changed_by' => 1,
                'gor_created' => $now->format('Y-m-d H:i:s'),
                'gor_created_by' => 1,
            ],
        ];

        $organizations = $this->table('gems__organizations');
        $organizations->insert($data)
              ->saveData();
    }
}
