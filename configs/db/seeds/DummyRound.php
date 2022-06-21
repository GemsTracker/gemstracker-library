<?php


use Phinx\Seed\AbstractSeed;

class DummyRound extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run()
    {
        $now = new \DateTimeImmutable();
        $data = [
            [
                'gro_id_track' => 0,
                'gro_id_order' => 10,
                'gro_id_survey' => 0,
                'gro_survey_name' => 'Dummy for inserted surveys',
                'gro_round_description' => 'Dummy for inserted surveys',
                'gro_valid_after_id' => 0,
                'gro_valid_for_id' => 0,
                'gro_active' => 1,
                'gro_changed' => $now->format('Y-m-d H:i:s'),
                'gro_changed_by' => 1,
                'gro_created' => $now->format('Y-m-d H:i:s'),
                'gro_created_by' => 1,
            ],
        ];

        $rounds = $this->table('gems__rounds');
        $rounds->insert($data)
              ->saveData();
    }
}
