<?php

// Save this as add_migrations.php in your project root

$pdo = new PDO('mysql:host=localhost;dbname=learqwfo_testdb', 'root', '');

$migrations = [
    '0001_01_01_000000_create_users_table',
    '0001_01_01_000001_create_cache_table',
    '0001_01_01_000002_create_jobs_table',
    '2024_08_19_100113_create_personal_access_tokens_table',
    '2024_08_19_115154_create_accounts_table',
    '2024_08_19_122841_create_withdrawals_table',
    '2024_08_20_180439_create_transactions_table',
    '2024_08_21_094427_create_vendors_table',
    '2024_08_21_095539_create_products_table',
    '2024_08_21_102348_create_reviews_table',
    '2024_08_21_103255_add_review_id_foreign_to_products_table',
    '2024_08_25_174147_create_referrals_table',
    '2024_08_31_075245_create_affiliates_table',
    '2024_08_31_075326_create_vendor_status_table',
    '2024_10_17_214005_create_temporary_users_table',
    '2024_10_30_062127_add_fields_to_vendors_table',
    '2024_10_30_081232_make_vendor_name_nullable',
    '2024_10_30_101952_add_vendor_id_to_products_table',
    '2024_11_04_153953_create_sales_table',
    '2024_11_05_071844_modify_affiliate_id_to_string_in_sales_table',
    '2024_11_24_165724_add_currency_to_users_table',
    '2024_11_26_042507_add_file_and_images_to_products_table',
    '2024_11_26_104758_create_access_tokens_table',
    '2024_12_10_095009_add_is_onboarded_to_transactions_table',
    '2024_12_22_200512_add_type_to_withdrawals_table',
    '2024_12_30_171023_add_bankcode_to_users_table',
    '2024_12_30_184249_add_bankcode_to_users_table',
    '2025_02_06_145738_add_errors_column_to_transactions_table'
];

$stmt = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, 1) ON DUPLICATE KEY UPDATE batch = 1");

foreach ($migrations as $migration) {
    $stmt->execute([$migration]);
    echo "Added or updated migration: $migration\n";
}

echo "All migrations have been added to the migrations table.\n";