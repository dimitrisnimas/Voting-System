<?php
/**
 * Plugin Name: Voting System
 * Description: A secure anonymous voting system with admin dashboard and live stats.
 * Version: 2.0
 * Author: Dimitris Nimas (DimitrisNimas.gr)
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'admin-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'vote-form.php';

register_activation_hook(__FILE__, function () {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}voting_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(50) UNIQUE,
        used TINYINT(1) DEFAULT 0
    ) $charset_collate;");

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}voting_votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(50),
        question_id VARCHAR(10),
        vote_option TEXT
    ) $charset_collate;");

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}voting_invalids (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(50),
        question_id VARCHAR(10)
    ) $charset_collate;");
});
