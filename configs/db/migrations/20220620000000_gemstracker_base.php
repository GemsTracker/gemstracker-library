<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemstrackerBase extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->execute("ALTER DATABASE CHARACTER SET 'utf8mb4';");
        $this->execute("ALTER DATABASE COLLATE='utf8mb4_unicode_ci';");
        
        $this->table('gems__respondent2org', [
                'id' => false,
                'primary_key' => ['gr2o_patient_nr', 'gr2o_id_organization'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gr2o_patient_nr', 'string', [
                'null' => false,
                'limit' => 15,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('gr2o_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2o_patient_nr',
            ])
            ->addColumn('gr2o_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2o_id_organization',
            ])
            ->addColumn('gr2o_email', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2o_id_user',
            ])
            ->addColumn('gr2o_mailable', 'integer', [
                'null' => false,
                'default' => '100',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gr2o_email',
            ])
            ->addColumn('gr2o_comments', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2o_mailable',
            ])
            ->addColumn('gr2o_consent', 'string', [
                'null' => false,
                'default' => 'Unknown',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2o_comments',
            ])
            ->addColumn('gr2o_reception_code', 'string', [
                'null' => false,
                'default' => 'OK',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2o_consent',
            ])
            ->addColumn('gr2o_opened', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gr2o_reception_code',
            ])
            ->addColumn('gr2o_opened_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2o_opened',
            ])
            ->addColumn('gr2o_changed', 'timestamp', [
                'null' => false,
                'after' => 'gr2o_opened_by',
            ])
            ->addColumn('gr2o_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2o_changed',
            ])
            ->addColumn('gr2o_created', 'timestamp', [
                'null' => false,
                'after' => 'gr2o_changed_by',
            ])
            ->addColumn('gr2o_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2o_created',
            ])
            ->addIndex(['gr2o_changed_by'], [
                'name' => 'gr2o_changed_by',
                'unique' => false,
            ])
            ->addIndex(['gr2o_consent'], [
                'name' => 'gr2o_consent',
                'unique' => false,
            ])
            ->addIndex(['gr2o_email'], [
                'name' => 'gr2o_email',
                'unique' => false,
            ])
            ->addIndex(['gr2o_id_organization'], [
                'name' => 'gr2o_id_organization',
                'unique' => false,
            ])
            ->addIndex(['gr2o_id_user', 'gr2o_id_organization'], [
                'name' => 'gr2o_id_user',
                'unique' => true,
            ])
            ->addIndex(['gr2o_opened'], [
                'name' => 'gr2o_opened',
                'unique' => false,
            ])
            ->addIndex(['gr2o_opened_by'], [
                'name' => 'gr2o_opened_by',
                'unique' => false,
            ])
            ->addIndex(['gr2o_reception_code'], [
                'name' => 'gr2o_reception_code',
                'unique' => false,
            ])
            ->create();
        $this->table('gems__respondent2track', [
                'id' => false,
                'primary_key' => ['gr2t_id_respondent_track'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gr2t_id_respondent_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gr2t_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t_id_respondent_track',
            ])
            ->addColumn('gr2t_id_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gr2t_id_user',
            ])
            ->addColumn('gr2t_track_info', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2t_id_track',
            ])
            ->addColumn('gr2t_start_date', 'datetime', [
                'null' => true,
                'after' => 'gr2t_track_info',
            ])
            ->addColumn('gr2t_end_date', 'datetime', [
                'null' => true,
                'after' => 'gr2t_start_date',
            ])
            ->addColumn('gr2t_end_date_manual', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gr2t_end_date',
            ])
            ->addColumn('gr2t_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t_end_date_manual',
            ])
            ->addColumn('gr2t_mailable', 'integer', [
                'null' => false,
                'default' => '100',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gr2t_id_organization',
            ])
            ->addColumn('gr2t_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gr2t_mailable',
            ])
            ->addColumn('gr2t_count', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gr2t_active',
            ])
            ->addColumn('gr2t_completed', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gr2t_count',
            ])
            ->addColumn('gr2t_reception_code', 'string', [
                'null' => false,
                'default' => 'OK',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2t_completed',
            ])
            ->addColumn('gr2t_comment', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2t_reception_code',
            ])
            ->addColumn('gr2t_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gr2t_comment',
            ])
            ->addColumn('gr2t_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t_changed',
            ])
            ->addColumn('gr2t_created', 'timestamp', [
                'null' => false,
                'after' => 'gr2t_changed_by',
            ])
            ->addColumn('gr2t_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t_created',
            ])
            ->addIndex(['gr2t_created_by'], [
                'name' => 'gr2t_created_by',
                'unique' => false,
            ])
            ->addIndex(['gr2t_id_organization'], [
                'name' => 'gr2t_id_organization',
                'unique' => false,
            ])
            ->addIndex(['gr2t_id_track'], [
                'name' => 'gr2t_id_track',
                'unique' => false,
            ])
            ->addIndex(['gr2t_id_user'], [
                'name' => 'gr2t_id_user',
                'unique' => false,
            ])
            ->addIndex(['gr2t_start_date'], [
                'name' => 'gr2t_start_date',
                'unique' => false,
            ])
            ->create();
        $this->table('gems__respondent2track2appointment', [
                'id' => false,
                'primary_key' => ['gr2t2a_id_respondent_track', 'gr2t2a_id_app_field'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gr2t2a_id_respondent_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gr2t2a_id_app_field', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2a_id_respondent_track',
            ])
            ->addColumn('gr2t2a_id_appointment', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2a_id_app_field',
            ])
            ->addColumn('gr2t2a_value_manual', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gr2t2a_id_appointment',
            ])
            ->addColumn('gr2t2a_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gr2t2a_value_manual',
            ])
            ->addColumn('gr2t2a_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2a_changed',
            ])
            ->addColumn('gr2t2a_created', 'timestamp', [
                'null' => false,
                'after' => 'gr2t2a_changed_by',
            ])
            ->addColumn('gr2t2a_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2a_created',
            ])
            ->addIndex(['gr2t2a_id_appointment'], [
                'name' => 'gr2t2a_id_appointment',
                'unique' => false,
            ])
            ->create();
        $this->table('gems__respondent2track2field', [
                'id' => false,
                'primary_key' => ['gr2t2f_id_respondent_track', 'gr2t2f_id_field'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gr2t2f_id_respondent_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gr2t2f_id_field', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2f_id_respondent_track',
            ])
            ->addColumn('gr2t2f_value', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2t2f_id_field',
            ])
            ->addColumn('gr2t2f_value_manual', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gr2t2f_value',
            ])
            ->addColumn('gr2t2f_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gr2t2f_value_manual',
            ])
            ->addColumn('gr2t2f_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2f_changed',
            ])
            ->addColumn('gr2t2f_created', 'timestamp', [
                'null' => false,
                'after' => 'gr2t2f_changed_by',
            ])
            ->addColumn('gr2t2f_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2f_created',
            ])
            ->create();
        $this->table('gems__respondent_relations', [
                'id' => false,
                'primary_key' => ['grr_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('grr_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'identity' => 'enable',
            ])
            ->addColumn('grr_id_respondent', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'grr_id',
            ])
            ->addColumn('grr_type', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_id_respondent',
            ])
            ->addColumn('grr_id_staff', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'grr_type',
            ])
            ->addColumn('grr_email', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_id_staff',
            ])
            ->addColumn('grr_phone', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_email',
            ])
            ->addColumn('grr_mailable', 'integer', [
                'null' => false,
                'default' => '100',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'grr_phone',
            ])
            ->addColumn('grr_first_name', 'string', [
                'null' => true,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_mailable',
            ])
            ->addColumn('grr_last_name', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_first_name',
            ])
            ->addColumn('grr_gender', 'char', [
                'null' => false,
                'default' => 'U',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_last_name',
            ])
            ->addColumn('grr_birthdate', 'date', [
                'null' => true,
                'after' => 'grr_gender',
            ])
            ->addColumn('grr_comments', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_birthdate',
            ])
            ->addColumn('grr_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'grr_comments',
            ])
            ->addColumn('grr_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'grr_active',
            ])
            ->addColumn('grr_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grr_changed',
            ])
            ->addColumn('grr_created', 'timestamp', [
                'null' => false,
                'after' => 'grr_changed_by',
            ])
            ->addColumn('grr_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grr_created',
            ])
            ->addIndex(['grr_id_respondent', 'grr_id_staff'], [
                'name' => 'grr_id_respondent',
                'unique' => false,
            ])
            ->create();
        $this->table('gems__respondents', [
                'id' => false,
                'primary_key' => ['grs_id_user'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('grs_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('grs_ssn', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_id_user',
            ])
            ->addColumn('grs_iso_lang', 'char', [
                'null' => false,
                'default' => 'nl',
                'limit' => 2,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_ssn',
            ])
            ->addColumn('grs_first_name', 'string', [
                'null' => true,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_iso_lang',
            ])
            ->addColumn('grs_surname_prefix', 'string', [
                'null' => true,
                'limit' => 10,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_first_name',
            ])
            ->addColumn('grs_last_name', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_surname_prefix',
            ])
            ->addColumn('grs_gender', 'char', [
                'null' => false,
                'default' => 'U',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_last_name',
            ])
            ->addColumn('grs_birthday', 'date', [
                'null' => true,
                'after' => 'grs_gender',
            ])
            ->addColumn('grs_address_1', 'string', [
                'null' => true,
                'limit' => 80,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_birthday',
            ])
            ->addColumn('grs_address_2', 'string', [
                'null' => true,
                'limit' => 80,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_address_1',
            ])
            ->addColumn('grs_zipcode', 'string', [
                'null' => true,
                'limit' => 10,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_address_2',
            ])
            ->addColumn('grs_city', 'string', [
                'null' => true,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_zipcode',
            ])
            ->addColumn('grs_iso_country', 'char', [
                'null' => false,
                'default' => 'NL',
                'limit' => 2,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_city',
            ])
            ->addColumn('grs_phone_1', 'string', [
                'null' => true,
                'limit' => 25,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_iso_country',
            ])
            ->addColumn('grs_phone_2', 'string', [
                'null' => true,
                'limit' => 25,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_phone_1',
            ])
            ->addColumn('grs_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'grs_phone_2',
            ])
            ->addColumn('grs_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grs_changed',
            ])
            ->addColumn('grs_created', 'timestamp', [
                'null' => false,
                'after' => 'grs_changed_by',
            ])
            ->addColumn('grs_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grs_created',
            ])
            ->addIndex(['grs_ssn'], [
                'name' => 'grs_ssn',
                'unique' => true,
            ])
            ->create();
        $this->table('gems__roles', [
                'id' => false,
                'primary_key' => ['grl_id_role'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('grl_id_role', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('grl_name', 'string', [
                'null' => false,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grl_id_role',
            ])
            ->addColumn('grl_description', 'string', [
                'null' => false,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grl_name',
            ])
            ->addColumn('grl_parents', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grl_description',
            ])
            ->addColumn('grl_privileges', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grl_parents',
            ])
            ->addColumn('grl_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'grl_privileges',
            ])
            ->addColumn('grl_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grl_changed',
            ])
            ->addColumn('grl_created', 'timestamp', [
                'null' => false,
                'after' => 'grl_changed_by',
            ])
            ->addColumn('grl_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grl_created',
            ])
            ->create();
        $this->table('gems__rounds', [
                'id' => false,
                'primary_key' => ['gro_id_round'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gro_id_round', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gro_id_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_id_round',
            ])
            ->addColumn('gro_id_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gro_id_track',
            ])
            ->addColumn('gro_id_survey', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_id_order',
            ])
            ->addColumn('gro_id_relationfield', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gro_id_survey',
            ])
            ->addColumn('gro_survey_name', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_id_relationfield',
            ])
            ->addColumn('gro_round_description', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_survey_name',
            ])
            ->addColumn('gro_icon_file', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_round_description',
            ])
            ->addColumn('gro_changed_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_icon_file',
            ])
            ->addColumn('gro_display_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_changed_event',
            ])
            ->addColumn('gro_valid_after_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_display_event',
            ])
            ->addColumn('gro_valid_after_source', 'string', [
                'null' => false,
                'default' => 'tok',
                'limit' => 12,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_valid_after_id',
            ])
            ->addColumn('gro_valid_after_field', 'string', [
                'null' => false,
                'default' => 'gto_valid_from',
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_valid_after_source',
            ])
            ->addColumn('gro_valid_after_unit', 'char', [
                'null' => false,
                'default' => 'M',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_valid_after_field',
            ])
            ->addColumn('gro_valid_after_length', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gro_valid_after_unit',
            ])
            ->addColumn('gro_valid_for_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_valid_after_length',
            ])
            ->addColumn('gro_valid_for_source', 'string', [
                'null' => false,
                'default' => 'nul',
                'limit' => 12,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_valid_for_id',
            ])
            ->addColumn('gro_valid_for_field', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_valid_for_source',
            ])
            ->addColumn('gro_valid_for_unit', 'char', [
                'null' => false,
                'default' => 'M',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_valid_for_field',
            ])
            ->addColumn('gro_valid_for_length', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gro_valid_for_unit',
            ])
            ->addColumn('gro_condition', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_valid_for_length',
            ])
            ->addColumn('gro_organizations', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_condition',
            ])
            ->addColumn('gro_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gro_organizations',
            ])
            ->addColumn('gro_code', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_active',
            ])
            ->addColumn('gro_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gro_code',
            ])
            ->addColumn('gro_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_changed',
            ])
            ->addColumn('gro_created', 'timestamp', [
                'null' => false,
                'after' => 'gro_changed_by',
            ])
            ->addColumn('gro_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_created',
            ])
            ->addIndex(['gro_id_order'], [
                'name' => 'gro_id_order',
                'unique' => false,
            ])
            ->addIndex(['gro_id_survey'], [
                'name' => 'gro_id_survey',
                'unique' => false,
            ])
            ->addIndex(['gro_id_track', 'gro_id_order'], [
                'name' => 'gro_id_track',
                'unique' => false,
            ])
            ->create();
        $this->table('gems__sites', [
                'id' => false,
                'primary_key' => ['gsi_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsi_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gsi_url', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsi_id',
            ])
            ->addColumn('gsi_order', 'integer', [
                'null' => false,
                'default' => '100',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gsi_url',
            ])
            ->addColumn('gsi_select_organizations', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsi_order',
            ])
            ->addColumn('gsi_organizations', 'string', [
                'null' => false,
                'default' => '||',
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsi_select_organizations',
            ])
            ->addColumn('gsi_style', 'string', [
                'null' => false,
                'default' => 'gems',
                'limit' => 15,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsi_organizations',
            ])
            ->addColumn('gsi_style_fixed', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsi_style',
            ])
            ->addColumn('gsi_iso_lang', 'char', [
                'null' => false,
                'default' => 'en',
                'limit' => 2,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsi_style_fixed',
            ])
            ->addColumn('gsi_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsi_iso_lang',
            ])
            ->addColumn('gsi_blocked', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsi_active',
            ])
            ->addColumn('gsi_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gsi_blocked',
            ])
            ->addColumn('gsi_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsi_changed',
            ])
            ->addColumn('gsi_created', 'timestamp', [
                'null' => false,
                'after' => 'gsi_changed_by',
            ])
            ->addColumn('gsi_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsi_created',
            ])
            ->addIndex(['gsi_order'], [
                'name' => 'gsi_order',
                'unique' => false,
            ])
            ->addIndex(['gsi_url'], [
                'name' => 'gsi_url',
                'unique' => true,
            ])
            ->create();
        $this->table('gems__sources', [
                'id' => false,
                'primary_key' => ['gso_id_source'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8',
                'collation' => 'utf8_general_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gso_id_source', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gso_source_name', 'string', [
                'null' => false,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_id_source',
            ])
            ->addColumn('gso_ls_url', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_source_name',
            ])
            ->addColumn('gso_ls_class', 'string', [
                'null' => false,
                'default' => 'Gems_Source_LimeSurvey1m9Database',
                'limit' => 60,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_url',
            ])
            ->addColumn('gso_ls_adapter', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_class',
            ])
            ->addColumn('gso_ls_dbhost', 'string', [
                'null' => true,
                'limit' => 127,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_adapter',
            ])
            ->addColumn('gso_ls_database', 'string', [
                'null' => true,
                'limit' => 127,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_dbhost',
            ])
            ->addColumn('gso_ls_dbport', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_MEDIUM,
                'after' => 'gso_ls_database',
            ])
            ->addColumn('gso_ls_table_prefix', 'string', [
                'null' => true,
                'limit' => 127,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_dbport',
            ])
            ->addColumn('gso_ls_username', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_table_prefix',
            ])
            ->addColumn('gso_ls_password', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_username',
            ])
            ->addColumn('gso_encryption', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_password',
            ])
            ->addColumn('gso_ls_charset', 'string', [
                'null' => true,
                'limit' => 8,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_encryption',
            ])
            ->addColumn('gso_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gso_ls_charset',
            ])
            ->addColumn('gso_status', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_active',
            ])
            ->addColumn('gso_last_synch', 'timestamp', [
                'null' => true,
                'after' => 'gso_status',
            ])
            ->addColumn('gso_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gso_last_synch',
            ])
            ->addColumn('gso_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gso_changed',
            ])
            ->addColumn('gso_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gso_changed_by',
            ])
            ->addColumn('gso_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gso_created',
            ])
            ->addIndex(['gso_ls_url'], [
                'name' => 'gso_ls_url',
                'unique' => true,
            ])
            ->addIndex(['gso_source_name'], [
                'name' => 'gso_source_name',
                'unique' => true,
            ])
            ->create();
        $this->table('gems__staff', [
                'id' => false,
                'primary_key' => ['gsf_id_user'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsf_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gsf_login', 'string', [
                'null' => false,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_id_user',
            ])
            ->addColumn('gsf_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gsf_login',
            ])
            ->addColumn('gsf_active', 'boolean', [
                'null' => true,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsf_id_organization',
            ])
            ->addColumn('gsf_id_primary_group', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsf_active',
            ])
            ->addColumn('gsf_iso_lang', 'char', [
                'null' => false,
                'default' => 'en',
                'limit' => 2,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_id_primary_group',
            ])
            ->addColumn('gsf_logout_on_survey', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsf_iso_lang',
            ])
            ->addColumn('gsf_mail_watcher', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsf_logout_on_survey',
            ])
            ->addColumn('gsf_email', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_mail_watcher',
            ])
            ->addColumn('gsf_first_name', 'string', [
                'null' => true,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_email',
            ])
            ->addColumn('gsf_surname_prefix', 'string', [
                'null' => true,
                'limit' => 10,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_first_name',
            ])
            ->addColumn('gsf_last_name', 'string', [
                'null' => true,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_surname_prefix',
            ])
            ->addColumn('gsf_gender', 'char', [
                'null' => false,
                'default' => 'U',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_last_name',
            ])
            ->addColumn('gsf_job_title', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_gender',
            ])
            ->addColumn('gsf_phone_1', 'string', [
                'null' => true,
                'limit' => 25,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_job_title',
            ])
            ->addColumn('gsf_is_embedded', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsf_phone_1',
            ])
            ->addColumn('gsf_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gsf_is_embedded',
            ])
            ->addColumn('gsf_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsf_changed',
            ])
            ->addColumn('gsf_created', 'timestamp', [
                'null' => false,
                'after' => 'gsf_changed_by',
            ])
            ->addColumn('gsf_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsf_created',
            ])
            ->addIndex(['gsf_email'], [
                'name' => 'gsf_email',
                'unique' => false,
            ])
            ->addIndex(['gsf_login', 'gsf_id_organization'], [
                'name' => 'gsf_login',
                'unique' => true,
            ])
            ->create();
        $this->table('gems__subscription_attempts', [
                'id' => false,
                'primary_key' => ['gsa_id_attempt'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsa_id_attempt', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gsa_type_attempt', 'string', [
                'null' => false,
                'limit' => 16,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsa_id_attempt',
            ])
            ->addColumn('gsa_ip_address', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsa_type_attempt',
            ])
            ->addColumn('gsa_datetime', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gsa_ip_address',
            ])
            ->addColumn('gsa_activated', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsa_datetime',
            ])
            ->create();
        $this->table('gems__survey_question_options', [
                'id' => false,
                'primary_key' => ['gsqo_id_survey', 'gsqo_name', 'gsqo_order'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsqo_id_survey', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
            ])
            ->addColumn('gsqo_name', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsqo_id_survey',
            ])
            ->addColumn('gsqo_order', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gsqo_name',
            ])
            ->addColumn('gsqo_key', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsqo_order',
            ])
            ->addColumn('gsqo_label', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsqo_key',
            ])
            ->addColumn('gsqo_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gsqo_label',
            ])
            ->addColumn('gsqo_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsqo_changed',
            ])
            ->addColumn('gsqo_created', 'timestamp', [
                'null' => false,
                'after' => 'gsqo_changed_by',
            ])
            ->addColumn('gsqo_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsqo_created',
            ])
            ->create();
        $this->table('gems__survey_questions', [
                'id' => false,
                'primary_key' => ['gsq_id_survey', 'gsq_name'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsq_id_survey', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
            ])
            ->addColumn('gsq_name', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_bin',
                'encoding' => 'utf8mb4',
                'after' => 'gsq_id_survey',
            ])
            ->addColumn('gsq_name_parent', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_bin',
                'encoding' => 'utf8mb4',
                'after' => 'gsq_name',
            ])
            ->addColumn('gsq_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gsq_name_parent',
            ])
            ->addColumn('gsq_type', 'integer', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_SMALL,
                'after' => 'gsq_order',
            ])
            ->addColumn('gsq_class', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsq_type',
            ])
            ->addColumn('gsq_group', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsq_class',
            ])
            ->addColumn('gsq_label', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsq_group',
            ])
            ->addColumn('gsq_description', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsq_label',
            ])
            ->addColumn('gsq_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gsq_description',
            ])
            ->addColumn('gsq_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsq_changed',
            ])
            ->addColumn('gsq_created', 'timestamp', [
                'null' => false,
                'after' => 'gsq_changed_by',
            ])
            ->addColumn('gsq_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsq_created',
            ])
            ->create();
        $this->table('gems__surveys', [
                'id' => false,
                'primary_key' => ['gsu_id_survey'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsu_id_survey', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gsu_survey_name', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_id_survey',
            ])
            ->addColumn('gsu_survey_description', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_survey_name',
            ])
            ->addColumn('gsu_external_description', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_survey_description',
            ])
            ->addColumn('gsu_survey_languages', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_external_description',
            ])
            ->addColumn('gsu_surveyor_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gsu_survey_languages',
            ])
            ->addColumn('gsu_surveyor_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsu_surveyor_id',
            ])
            ->addColumn('gsu_survey_pdf', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_surveyor_active',
            ])
            ->addColumn('gsu_beforeanswering_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_survey_pdf',
            ])
            ->addColumn('gsu_completed_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_beforeanswering_event',
            ])
            ->addColumn('gsu_display_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_completed_event',
            ])
            ->addColumn('gsu_id_source', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gsu_display_event',
            ])
            ->addColumn('gsu_active', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsu_id_source',
            ])
            ->addColumn('gsu_status', 'string', [
                'null' => true,
                'limit' => 127,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_active',
            ])
            ->addColumn('gsu_survey_warnings', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_status',
            ])
            ->addColumn('gsu_id_primary_group', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsu_survey_warnings',
            ])
            ->addColumn('gsu_answers_by_group', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsu_id_primary_group',
            ])
            ->addColumn('gsu_answer_groups', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_answers_by_group',
            ])
            ->addColumn('gsu_allow_export', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsu_answer_groups',
            ])
            ->addColumn('gsu_mail_code', 'integer', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsu_allow_export',
            ])
            ->addColumn('gsu_insertable', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsu_mail_code',
            ])
            ->addColumn('gsu_valid_for_unit', 'char', [
                'null' => false,
                'default' => 'M',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_insertable',
            ])
            ->addColumn('gsu_valid_for_length', 'integer', [
                'null' => false,
                'default' => '6',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gsu_valid_for_unit',
            ])
            ->addColumn('gsu_insert_organizations', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_valid_for_length',
            ])
            ->addColumn('gsu_result_field', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_insert_organizations',
            ])
            ->addColumn('gsu_agenda_result', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_result_field',
            ])
            ->addColumn('gsu_duration', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_agenda_result',
            ])
            ->addColumn('gsu_code', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_duration',
            ])
            ->addColumn('gsu_export_code', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_code',
            ])
            ->addColumn('gsu_hash', 'char', [
                'null' => true,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_export_code',
            ])
            ->addColumn('gsu_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gsu_hash',
            ])
            ->addColumn('gsu_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsu_changed',
            ])
            ->addColumn('gsu_created', 'timestamp', [
                'null' => false,
                'after' => 'gsu_changed_by',
            ])
            ->addColumn('gsu_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsu_created',
            ])
            ->addIndex(['gsu_active'], [
                'name' => 'gsu_active',
                'unique' => false,
            ])
            ->addIndex(['gsu_code'], [
                'name' => 'gsu_code',
                'unique' => false,
            ])
            ->addIndex(['gsu_id_primary_group'], [
                'name' => 'gsu_id_primary_group',
                'unique' => false,
            ])
            ->addIndex(['gsu_surveyor_active'], [
                'name' => 'gsu_surveyor_active',
                'unique' => false,
            ])
            ->create();
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
        $this->table('gems__token_attempts', [
                'id' => false,
                'primary_key' => ['gta_id_attempt'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gta_id_attempt', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gta_id_token', 'string', [
                'null' => false,
                'limit' => 9,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gta_id_attempt',
            ])
            ->addColumn('gta_ip_address', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gta_id_token',
            ])
            ->addColumn('gta_datetime', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gta_ip_address',
            ])
            ->addColumn('gta_activated', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gta_datetime',
            ])
            ->create();
        $this->table('gems__token_replacements', [
                'id' => false,
                'primary_key' => ['gtrp_id_token_new'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gtrp_id_token_new', 'string', [
                'null' => false,
                'limit' => 9,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('gtrp_id_token_old', 'string', [
                'null' => false,
                'limit' => 9,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtrp_id_token_new',
            ])
            ->addColumn('gtrp_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gtrp_id_token_old',
            ])
            ->addColumn('gtrp_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtrp_created',
            ])
            ->addIndex(['gtrp_id_token_old'], [
                'name' => 'gtrp_id_token_old',
                'unique' => false,
            ])
            ->create();
        $this->table('gems__tokens', [
                'id' => false,
                'primary_key' => ['gto_id_token'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gto_id_token', 'string', [
                'null' => false,
                'limit' => 9,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('gto_id_respondent_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_id_token',
            ])
            ->addColumn('gto_id_round', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_id_respondent_track',
            ])
            ->addColumn('gto_id_respondent', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_id_round',
            ])
            ->addColumn('gto_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_id_respondent',
            ])
            ->addColumn('gto_id_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_id_organization',
            ])
            ->addColumn('gto_id_survey', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_id_track',
            ])
            ->addColumn('gto_round_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gto_id_survey',
            ])
            ->addColumn('gto_icon_file', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gto_round_order',
            ])
            ->addColumn('gto_round_description', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gto_icon_file',
            ])
            ->addColumn('gto_id_relationfield', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gto_round_description',
            ])
            ->addColumn('gto_id_relation', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gto_id_relationfield',
            ])
            ->addColumn('gto_valid_from', 'datetime', [
                'null' => true,
                'after' => 'gto_id_relation',
            ])
            ->addColumn('gto_valid_from_manual', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gto_valid_from',
            ])
            ->addColumn('gto_valid_until', 'datetime', [
                'null' => true,
                'after' => 'gto_valid_from_manual',
            ])
            ->addColumn('gto_valid_until_manual', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gto_valid_until',
            ])
            ->addColumn('gto_mail_sent_date', 'date', [
                'null' => true,
                'after' => 'gto_valid_until_manual',
            ])
            ->addColumn('gto_mail_sent_num', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gto_mail_sent_date',
            ])
            ->addColumn('gto_start_time', 'datetime', [
                'null' => true,
                'after' => 'gto_mail_sent_num',
            ])
            ->addColumn('gto_in_source', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gto_start_time',
            ])
            ->addColumn('gto_by', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_in_source',
            ])
            ->addColumn('gto_completion_time', 'datetime', [
                'null' => true,
                'after' => 'gto_by',
            ])
            ->addColumn('gto_duration_in_sec', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_completion_time',
            ])
            ->addColumn('gto_result', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gto_duration_in_sec',
            ])
            ->addColumn('gto_comment', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gto_result',
            ])
            ->addColumn('gto_reception_code', 'string', [
                'null' => false,
                'default' => 'OK',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gto_comment',
            ])
            ->addColumn('gto_return_url', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gto_reception_code',
            ])
            ->addColumn('gto_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gto_return_url',
            ])
            ->addColumn('gto_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_changed',
            ])
            ->addColumn('gto_created', 'timestamp', [
                'null' => false,
                'after' => 'gto_changed_by',
            ])
            ->addColumn('gto_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_created',
            ])
            ->addIndex(['gto_by'], [
                'name' => 'gto_by',
                'unique' => false,
            ])
            ->addIndex(['gto_completion_time'], [
                'name' => 'gto_completion_time',
                'unique' => false,
            ])
            ->addIndex(['gto_created'], [
                'name' => 'gto_created',
                'unique' => false,
            ])
            ->addIndex(['gto_id_organization'], [
                'name' => 'gto_id_organization',
                'unique' => false,
            ])
            ->addIndex(['gto_id_respondent'], [
                'name' => 'gto_id_respondent',
                'unique' => false,
            ])
            ->addIndex(['gto_id_respondent_track', 'gto_round_order'], [
                'name' => 'gto_id_respondent_track',
                'unique' => false,
            ])
            ->addIndex(['gto_id_round'], [
                'name' => 'gto_id_round',
                'unique' => false,
            ])
            ->addIndex(['gto_id_survey'], [
                'name' => 'gto_id_survey',
                'unique' => false,
            ])
            ->addIndex(['gto_id_track'], [
                'name' => 'gto_id_track',
                'unique' => false,
            ])
            ->addIndex(['gto_in_source'], [
                'name' => 'gto_in_source',
                'unique' => false,
            ])
            ->addIndex(['gto_reception_code'], [
                'name' => 'gto_reception_code',
                'unique' => false,
            ])
            ->addIndex(['gto_round_order'], [
                'name' => 'gto_round_order',
                'unique' => false,
            ])
            ->addIndex(['gto_valid_from', 'gto_valid_until'], [
                'name' => 'gto_valid_from',
                'unique' => false,
            ])
            ->create();
        $this->table('gems__track_appointments', [
                'id' => false,
                'primary_key' => ['gtap_id_app_field'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gtap_id_app_field', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gtap_id_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gtap_id_app_field',
            ])
            ->addColumn('gtap_id_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gtap_id_track',
            ])
            ->addColumn('gtap_field_name', 'string', [
                'null' => false,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtap_id_order',
            ])
            ->addColumn('gtap_field_code', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtap_field_name',
            ])
            ->addColumn('gtap_field_description', 'string', [
                'null' => true,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtap_field_code',
            ])
            ->addColumn('gtap_to_track_info', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_field_description',
            ])
            ->addColumn('gtap_track_info_label', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_to_track_info',
            ])
            ->addColumn('gtap_required', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_track_info_label',
            ])
            ->addColumn('gtap_readonly', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_required',
            ])
            ->addColumn('gtap_filter_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtap_readonly',
            ])
            ->addColumn('gtap_after_next', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_filter_id',
            ])
            ->addColumn('gtap_min_diff_length', 'integer', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gtap_after_next',
            ])
            ->addColumn('gtap_min_diff_unit', 'char', [
                'null' => false,
                'default' => 'D',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtap_min_diff_length',
            ])
            ->addColumn('gtap_max_diff_exists', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_min_diff_unit',
            ])
            ->addColumn('gtap_max_diff_length', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gtap_max_diff_exists',
            ])
            ->addColumn('gtap_max_diff_unit', 'char', [
                'null' => false,
                'default' => 'D',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtap_max_diff_length',
            ])
            ->addColumn('gtap_uniqueness', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_max_diff_unit',
            ])
            ->addColumn('gtap_create_track', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gtap_uniqueness',
            ])
            ->addColumn('gtap_create_wait_days', 'integer', [
                'null' => false,
                'default' => '182',
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gtap_create_track',
            ])
            ->addColumn('gtap_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gtap_create_wait_days',
            ])
            ->addColumn('gtap_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtap_changed',
            ])
            ->addColumn('gtap_created', 'timestamp', [
                'null' => false,
                'after' => 'gtap_changed_by',
            ])
            ->addColumn('gtap_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtap_created',
            ])
            ->addIndex(['gtap_id_order'], [
                'name' => 'gtap_id_order',
                'unique' => false,
            ])
            ->addIndex(['gtap_id_track'], [
                'name' => 'gtap_id_track',
                'unique' => false,
            ])
            ->create();
        $this->table('gems__track_fields', [
                'id' => false,
                'primary_key' => ['gtf_id_field'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gtf_id_field', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gtf_id_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gtf_id_field',
            ])
            ->addColumn('gtf_id_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gtf_id_track',
            ])
            ->addColumn('gtf_field_name', 'string', [
                'null' => false,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_id_order',
            ])
            ->addColumn('gtf_field_code', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_field_name',
            ])
            ->addColumn('gtf_field_description', 'string', [
                'null' => true,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_field_code',
            ])
            ->addColumn('gtf_field_values', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_field_description',
            ])
            ->addColumn('gtf_field_default', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_field_values',
            ])
            ->addColumn('gtf_calculate_using', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_field_default',
            ])
            ->addColumn('gtf_field_type', 'string', [
                'null' => false,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_calculate_using',
            ])
            ->addColumn('gtf_to_track_info', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtf_field_type',
            ])
            ->addColumn('gtf_track_info_label', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtf_to_track_info',
            ])
            ->addColumn('gtf_required', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtf_track_info_label',
            ])
            ->addColumn('gtf_readonly', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtf_required',
            ])
            ->addColumn('gtf_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gtf_readonly',
            ])
            ->addColumn('gtf_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtf_changed',
            ])
            ->addColumn('gtf_created', 'timestamp', [
                'null' => false,
                'after' => 'gtf_changed_by',
            ])
            ->addColumn('gtf_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtf_created',
            ])
            ->addIndex(['gtf_id_order'], [
                'name' => 'gtf_id_order',
                'unique' => false,
            ])
            ->addIndex(['gtf_id_track'], [
                'name' => 'gtf_id_track',
                'unique' => false,
            ])
            ->create();
        $this->table('gems__tracks', [
                'id' => false,
                'primary_key' => ['gtr_id_track'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gtr_id_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gtr_track_name', 'string', [
                'null' => false,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_id_track',
            ])
            ->addColumn('gtr_external_description', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_track_name',
            ])
            ->addColumn('gtr_track_info', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_external_description',
            ])
            ->addColumn('gtr_code', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_track_info',
            ])
            ->addColumn('gtr_date_start', 'date', [
                'null' => false,
                'after' => 'gtr_code',
            ])
            ->addColumn('gtr_date_until', 'date', [
                'null' => true,
                'after' => 'gtr_date_start',
            ])
            ->addColumn('gtr_active', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtr_date_until',
            ])
            ->addColumn('gtr_survey_rounds', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gtr_active',
            ])
            ->addColumn('gtr_track_class', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_survey_rounds',
            ])
            ->addColumn('gtr_beforefieldupdate_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_track_class',
            ])
            ->addColumn('gtr_calculation_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_beforefieldupdate_event',
            ])
            ->addColumn('gtr_completed_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_calculation_event',
            ])
            ->addColumn('gtr_fieldupdate_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_completed_event',
            ])
            ->addColumn('gtr_organizations', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_fieldupdate_event',
            ])
            ->addColumn('gtr_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gtr_organizations',
            ])
            ->addColumn('gtr_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtr_changed',
            ])
            ->addColumn('gtr_created', 'timestamp', [
                'null' => false,
                'after' => 'gtr_changed_by',
            ])
            ->addColumn('gtr_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtr_created',
            ])
            ->addIndex(['gtr_active'], [
                'name' => 'gtr_active',
                'unique' => false,
            ])
            ->addIndex(['gtr_track_class'], [
                'name' => 'gtr_track_class',
                'unique' => false,
            ])
            ->addIndex(['gtr_track_name'], [
                'name' => 'gtr_track_name',
                'unique' => true,
            ])
            ->addIndex(['gtr_track_name'], [
                'name' => 'gtr_track_name_2',
                'unique' => false,
            ])
            ->create();
        $this->table('gems__translations', [
                'id' => false,
                'primary_key' => ['gtrs_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gtrs_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gtrs_table', 'string', [
                'null' => false,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtrs_id',
            ])
            ->addColumn('gtrs_field', 'string', [
                'null' => false,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtrs_table',
            ])
            ->addColumn('gtrs_keys', 'string', [
                'null' => false,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtrs_field',
            ])
            ->addColumn('gtrs_iso_lang', 'string', [
                'null' => false,
                'limit' => 6,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtrs_keys',
            ])
            ->addColumn('gtrs_translation', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtrs_iso_lang',
            ])
            ->addColumn('gtrs_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gtrs_translation',
            ])
            ->addColumn('gtrs_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtrs_changed',
            ])
            ->addColumn('gtrs_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gtrs_changed_by',
            ])
            ->addColumn('gtrs_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtrs_created',
            ])
            ->addIndex(['gtrs_field'], [
                'name' => 'gtrs_field',
                'unique' => false,
            ])
            ->addIndex(['gtrs_iso_lang'], [
                'name' => 'gtrs_iso_lang',
                'unique' => false,
            ])
            ->addIndex(['gtrs_keys'], [
                'name' => 'gtrs_keys',
                'unique' => false,
            ])
            ->addIndex(['gtrs_table'], [
                'name' => 'gtrs_table',
                'unique' => false,
            ])
            ->create();
        $this->table('gems__user_ids', [
                'id' => false,
                'primary_key' => ['gui_id_user'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gui_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gui_created', 'timestamp', [
                'null' => false,
                'after' => 'gui_id_user',
            ])
            ->create();
        $this->table('gems__user_login_attempts', [
                'id' => false,
                'primary_key' => ['gula_login', 'gula_id_organization'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gula_login', 'string', [
                'null' => false,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('gula_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gula_login',
            ])
            ->addColumn('gula_failed_logins', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gula_id_organization',
            ])
            ->addColumn('gula_last_failed', 'timestamp', [
                'null' => true,
                'after' => 'gula_failed_logins',
            ])
            ->addColumn('gula_block_until', 'timestamp', [
                'null' => true,
                'after' => 'gula_last_failed',
            ])
            ->create();
        $this->table('gems__user_logins', [
                'id' => false,
                'primary_key' => ['gul_id_user'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gul_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gul_login', 'string', [
                'null' => false,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gul_id_user',
            ])
            ->addColumn('gul_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gul_login',
            ])
            ->addColumn('gul_user_class', 'string', [
                'null' => false,
                'default' => 'NoLogin',
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gul_id_organization',
            ])
            ->addColumn('gul_can_login', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gul_user_class',
            ])
            ->addColumn('gul_two_factor_key', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gul_can_login',
            ])
            ->addColumn('gul_enable_2factor', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gul_two_factor_key',
            ])
            ->addColumn('gul_otp_count', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gul_enable_2factor',
            ])
            ->addColumn('gul_otp_requested', 'timestamp', [
                'null' => true,
                'after' => 'gul_otp_count',
            ])
            ->addColumn('gul_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gul_otp_requested',
            ])
            ->addColumn('gul_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gul_changed',
            ])
            ->addColumn('gul_created', 'timestamp', [
                'null' => false,
                'after' => 'gul_changed_by',
            ])
            ->addColumn('gul_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gul_created',
            ])
            ->addIndex(['gul_login', 'gul_id_organization'], [
                'name' => 'gul_login',
                'unique' => true,
            ])
            ->create();
        $this->table('gems__user_passwords', [
                'id' => false,
                'primary_key' => ['gup_id_user'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gup_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gup_password', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gup_id_user',
            ])
            ->addColumn('gup_reset_key', 'char', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gup_password',
            ])
            ->addColumn('gup_reset_requested', 'timestamp', [
                'null' => true,
                'after' => 'gup_reset_key',
            ])
            ->addColumn('gup_reset_required', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gup_reset_requested',
            ])
            ->addColumn('gup_last_pwd_change', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gup_reset_required',
            ])
            ->addColumn('gup_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gup_last_pwd_change',
            ])
            ->addColumn('gup_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gup_changed',
            ])
            ->addColumn('gup_created', 'timestamp', [
                'null' => false,
                'after' => 'gup_changed_by',
            ])
            ->addColumn('gup_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gup_created',
            ])
            ->addIndex(['gup_reset_key'], [
                'name' => 'gup_reset_key',
                'unique' => true,
            ])
            ->create();
    }
}
