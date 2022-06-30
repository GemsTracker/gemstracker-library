CREATE TABLE gems__comm_templates (
      gct_id_template bigint not null ,

      gct_name        varchar(100) not null,
      gct_target      varchar(32) not null,
      gct_code        varchar(64),

      gct_changed     TEXT not null default current_timestamp,
      gct_changed_by  bigint not null,
      gct_created     TEXT not null default '0000-00-00 00:00:00',
      gct_created_by  bigint not null,

      PRIMARY KEY (gct_id_template),
      UNIQUE (gct_name)
   )
   ;

CREATE TABLE gems__comm_template_translations (
      gctt_id_template  bigint not null,
      gctt_lang      varchar(2) not null,
      gctt_subject      varchar(100),
      gctt_body         text,


      PRIMARY KEY (gctt_id_template,gctt_lang)
   )
   ;