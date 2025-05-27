<?php
// Hook for creating tables on plugin activation
register_activation_hook(__FILE__, 'create_voting_tables_once');

// Create tables for voting
function create_voting_tables_once() {
    if (get_option('voting_tables_created')) return;

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Define table names
    $tokens_table = $wpdb->prefix . 'voting_tokens';
    $votes_table = $wpdb->prefix . 'voting_votes';
    $invalids_table = $wpdb->prefix . 'voting_invalids';  // Optional for invalid votes

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Token Table
    $sql_tokens = "
    CREATE TABLE $tokens_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(255) NOT NULL UNIQUE,
        used TINYINT(1) DEFAULT 0
    ) $charset_collate;
    ";
    dbDelta($sql_tokens);

    // Votes Table
    $sql_votes = "
    CREATE TABLE $votes_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vote_option VARCHAR(255) NOT NULL,
        question_id INT NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;
    ";
    dbDelta($sql_votes);

    // Invalids Table (Optional)
    $sql_invalids = "
    CREATE TABLE $invalids_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vote_option VARCHAR(255) NOT NULL,
        question_id INT NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;
    ";
    dbDelta($sql_invalids);

    // Set the flag that tables have been created
    add_option('voting_tables_created', true);
}

