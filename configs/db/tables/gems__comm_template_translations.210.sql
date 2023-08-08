
CREATE TABLE if not exists gems__comm_template_translations (
      gctt_id_template  bigint unsigned not null,
      gctt_lang      varchar(2) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' not null,
      gctt_subject      varchar(100) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null,
      gctt_body         text CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' null,


      PRIMARY KEY (gctt_id_template,gctt_lang)
   )
   ENGINE=InnoDB
   CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
