
update gems__tokens set gto_mail_sent_date = null, gto_mail_sent_num = 0;

update gems__tokens set gto_mail_sent_date = gto_mail_sent_date - 5, gto_mail_sent_num = 0;

update gems__log_respondent_communications set grco_created = grco_created - 5, grco_changed = grco_changed - 5;