
update gems__tokens set gto_mail_sent_date = null, gto_mail_sent_num = 0;

update gems__tokens set gto_mail_sent_date = date_sub(gto_mail_sent_date, INTERVAL 5 DAY), gto_mail_sent_num = 1;

update gems__log_respondent_communications set grco_created = date_sub(grco_created, INTERVAL 5 DAY), grco_changed = date_sub(grco_changed, INTERVAL 5 DAY);