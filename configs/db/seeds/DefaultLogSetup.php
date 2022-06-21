<?php


use Phinx\Seed\AbstractSeed;

class DefaultLogSetup extends AbstractSeed
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
                'gls_name' => 'comm-job.cron-lock',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'comm-job.execute',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'comm-job.execute-all',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'cron.index',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'database.patch',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'database.run',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'database.run-all',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'database.run-sql',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 1,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'database.view',
                'gls_when_no_user' => 0,
                'gls_on_action' => 1,
                'gls_on_post' => 0,
                'gls_on_change' => 0,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'export.index',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'file-import.answers-import',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'index.login',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'index.logoff',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'index.resetpassword',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'participate.subscribe',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 1,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'participate.unsubscribe',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 1,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'project-information.maintenance',
                'gls_when_no_user' => 1,
                'gls_on_action' => 1,
                'gls_on_post' => 1,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'respondent.show',
                'gls_when_no_user' => 0,
                'gls_on_action' => 1,
                'gls_on_post' => 0,
                'gls_on_change' => 0,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'source.attributes',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'source.attributes-all',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'source.check',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'source.check-all',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'source.ping',
                'gls_when_no_user' => 0,
                'gls_on_action' => 1,
                'gls_on_post' => 0,
                'gls_on_change' => 0,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'source.synchronize',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'source.synchronize-all',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'survey-maintenance.check',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'survey-maintenance.check-all',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'token.answered',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'token.data-changed',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track.check-all-answers',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track.check-all-tracks',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track.check-token-answers',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track.check-track',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track.check-track-answers',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track.delete-track',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track.edit-track',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track.recalc-all-fields',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track.recalc-fields',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track-maintenance.check-all',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track-maintenance.check-track',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track-maintenance.export',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track-maintenance.import',
                'gls_when_no_user' => 1,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track-maintenance.recalc-all-fields',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'track-maintenance.recalc-fields',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'upgrade.execute-all',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'upgrade.execute-from',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'upgrade.execute-last',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'upgrade.execute-one',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
            [
                'gls_name' => 'upgrade.execute-to',
                'gls_when_no_user' => 0,
                'gls_on_action' => 0,
                'gls_on_post' => 0,
                'gls_on_change' => 1,
                'gls_changed' => $now->format('Y-m-d H:i:s'),
                'gls_changed_by' => 1,
                'gls_created' => $now->format('Y-m-d H:i:s'),
                'gls_created_by' => 1
            ],
        ];

        $logSetup = $this->table('gems__log_setup');
        $logSetup->insert($data)
              ->saveData();
    }
}
