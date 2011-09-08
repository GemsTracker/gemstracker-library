
-- Required by the Gems application framework:
-- user_id
-- user_login
-- user_password
-- user_name
-- user_group
-- user_role
-- user_locale
-- user_organization_id
-- user_organization_name
-- user_logout

CREATE OR REPLACE VIEW gems__login_info AS
    SELECT 
        gsf_id_user AS user_id, 
        gsf_login AS user_login, 
        gsf_password AS user_password, 
        gsf_email AS user_email, 
        gsf_failed_logins AS user_failed_logins,
        gsf_last_failed AS user_last_failed,
        CONCAT(COALESCE(CONCAT(gsf_first_name, ' '), ''), COALESCE(CONCAT(gsf_surname_prefix, ' '), ''), COALESCE(gsf_last_name, '')) AS user_name,
        gsf_id_primary_group AS user_group,
        ggp_role AS user_role, 
        gsf_iso_lang AS user_locale,
        gor_id_organization AS user_organization_id,
        gor_name AS user_organization_name,
        gsf_logout_on_survey as user_logout

    FROM gems__staff 
        INNER JOIN gems__groups ON gsf_id_primary_group = ggp_id_group
        INNER JOIN gems__organizations ON gsf_id_organization = gor_id_organization
    WHERE ggp_group_active = 1 AND gor_active = 1 AND gsf_active = 1
;
