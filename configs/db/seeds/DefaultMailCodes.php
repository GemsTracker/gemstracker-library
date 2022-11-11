<?php


use Phinx\Seed\AbstractSeed;

class DefaultMailCodes extends AbstractSeed
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
                'gmc_id' => 0,
                'gmc_mail_to_target' => 'No',
                'gmc_mail_cause_target' => 'Never mail',
                'gmc_for_surveys' => 0,
                'gmc_changed' => $now->format('Y-m-d H:i:s'),
                'gmc_changed_by' => 1,
                'gmc_created' => $now->format('Y-m-d H:i:s'),
                'gmc_created_by' => 1
            ],
            [
                'gmc_id' => 100,
                'gmc_mail_to_target' => 'Yes',
                'gmc_mail_cause_target' => 'Mail',
                'gmc_for_surveys' => 1,
                'gmc_changed' => $now->format('Y-m-d H:i:s'),
                'gmc_changed_by' => 1,
                'gmc_created' => $now->format('Y-m-d H:i:s'),
                'gmc_created_by' => 1
            ],
        ];

        $mailCodes = $this->table('gems__mail_codes');
        $mailCodes->insert($data)
              ->saveData();
    }
}
