SET @next_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations);

INSERT INTO migrations (migration, batch)
SELECT m.migration, @next_batch
FROM (
    SELECT '2025_11_14_065017_create_drivers_table' AS migration, 'drivers' AS tbl UNION ALL
    SELECT '2025_11_14_065755_create_personal_access_tokens_table', 'personal_access_tokens' UNION ALL
    SELECT '2025_11_14_084628_create_driver_steps_table', 'driver_steps' UNION ALL
    SELECT '2025_11_17_053814_create_roles_table', 'roles' UNION ALL
    SELECT '2025_11_17_053852_create_role_user_table', 'role_user' UNION ALL
    SELECT '2025_11_18_061256_create_driver_files_table', 'driver_files' UNION ALL
    SELECT '2025_12_12_130152_create_subscribers_table', 'subscribers' UNION ALL
    SELECT '2026_01_14_130644_create_attendances_table', 'attendances' UNION ALL
    SELECT '2026_01_16_125324_create_late_events_table', 'late_events' UNION ALL
    SELECT '2026_01_19_060035_create_debts_table', 'debts' UNION ALL
    SELECT '2026_01_19_131356_create_debt_reasons_table', 'debt_reasons' UNION ALL
    SELECT '2026_01_19_135115_create_user_states_table', 'user_states' UNION ALL
    SELECT '2026_01_21_122939_create_user_debt_queues_table', 'user_debt_queues' UNION ALL
    SELECT '2026_02_11_104339_create_employes_table', 'employes' UNION ALL
    SELECT '2026_03_11_131212_create_search_histories_table', 'search_histories' UNION ALL
    SELECT '2026_03_13_145719_create_employees_table', 'employees' UNION ALL
    SELECT '2026_03_13_154359_create_contacts_table', 'contacts' UNION ALL
    SELECT '2026_04_27_111606_create_material_requests_table', 'material_requests' UNION ALL
    SELECT '2026_05_20_101203_create_subscriptions_table', 'subscriptions' UNION ALL
    SELECT '2026_05_20_101214_create_admins_table', 'admins' UNION ALL
    SELECT '2026_05_20_101221_create_files_table', 'files' UNION ALL
    SELECT '2026_05_20_101234_create_telegram_states_table', 'telegram_states' UNION ALL
    SELECT '2026_05_20_101243_create_settings_table', 'settings' UNION ALL
    SELECT '2026_07_29_123006_create_companies_table', 'companies' UNION ALL
    SELECT '2026_07_29_123019_create_telegram_bots_table', 'telegram_bots' UNION ALL
    SELECT '2026_07_29_123038_create_telegram_channels_table', 'telegram_channels' UNION ALL
    SELECT '2026_07_29_123114_create_feedback_table', 'feedbacks' UNION ALL
    SELECT '2026_07_30_114803_create_appeals_table', 'appeals' UNION ALL
    SELECT '2026_07_30_114813_create_departments_table', 'departments' UNION ALL
    SELECT '2026_07_30_114822_create_department_heads_table', 'department_heads' UNION ALL
    SELECT '2026_07_30_114838_create_information_links_table', 'information_links' UNION ALL
    SELECT '2026_07_30_115602_create_contact_bot_users_table', 'contact_bot_users' UNION ALL
    SELECT '2026_08_22_162105_create_attendance_employees_table', 'attendance_employees' UNION ALL
    SELECT '2026_08_22_165423_create_attendance_sync_tables', 'attendance_sync_state' UNION ALL
    SELECT '2026_08_25_101652_create_attendance_early_leaves_table', 'attendance_early_leaves' UNION ALL
    SELECT '2026_08_25_111207_create_attendance_absences_table', 'attendance_absences' UNION ALL
    SELECT '2026_09_11_152446_create_user_steps_table', 'user_steps' UNION ALL
    SELECT '2026_09_11_165739_create_mails_table', 'mails' UNION ALL
    SELECT '2026_09_11_165828_create_telegram_users_table', 'telegram_users' UNION ALL
    SELECT '2026_09_17_102440_create_queries_table', 'queries' UNION ALL
    SELECT '2026_09_17_102501_create_query_messages_table', 'query_messages' UNION ALL
    SELECT '2026_09_17_102518_create_groups_table', 'groups'
) m
JOIN information_schema.tables t
  ON t.table_schema = DATABASE() AND t.table_name = m.tbl
WHERE m.migration NOT IN (SELECT migration FROM migrations);

INSERT INTO migrations (migration, batch)
SELECT m.migration, @next_batch
FROM (
    SELECT '2025_11_17_065619_add_phone_number_to_users_table' AS migration, 'users' AS tbl, 'phone_number' AS col UNION ALL
    SELECT '2026_01_19_134101_update_debt_reasons_table', 'debt_reasons', 'period' UNION ALL
    SELECT '2026_08_22_175335_add_company_and_waiting_company_status_to_attendance_late_events', 'attendance_late_events', 'company' UNION ALL
    SELECT '2026_09_11_161507_add_ref_mode_and_fix_custom_size_to_user_steps_table', 'user_steps', 'ref_mode'
) m
JOIN information_schema.columns c
  ON c.table_schema = DATABASE() AND c.table_name = m.tbl AND c.column_name = m.col
WHERE m.migration NOT IN (SELECT migration FROM migrations);

INSERT INTO migrations (migration, batch)
SELECT '2026_01_27_073614_change_reason_column_type', @next_batch
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'attendances' AND column_name = 'reason' AND data_type = 'text'
  AND '2026_01_27_073614_change_reason_column_type' NOT IN (SELECT migration FROM migrations);

INSERT INTO migrations (migration, batch)
SELECT '2026_09_11_153859_ensure_transport_type_column_consistency', @next_batch
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'user_steps' AND column_name = 'transport_type'
  AND '2026_09_11_153859_ensure_transport_type_column_consistency' NOT IN (SELECT migration FROM migrations);
SELECT migration, batch FROM migrations ORDER BY batch, id;
