<?php

class TestSource extends \Phinx\Seed\AbstractSeed
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
        $data = [
            [
                'gso_source_name' => 'test1',
                'gso_ls_url' => 'http://gems-ls.test',
                'gso_ls_class' => 'LimeSurvey5m00Database',
                'gso_ls_adapter' => 'Mysqli',
                'gso_ls_database' => 'pulse_ls',
                'gso_ls_table_prefix' => 'ls__',
                'gso_changed_by' => 1,
                'gso_created_by' => 1
            ],
        ];

        $sources = $this->table('gems__sources');
        $sources->insert($data)
            ->saveData();
    }
}