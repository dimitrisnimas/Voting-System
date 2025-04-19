
<?php
/**
 * Plugin Name: Voting System
 * Description: A simple voting system for WordPress with token-based access.
 * Version: 1.0
 * Author: Dimitris Nimas (DimitrisNimas.gr)
 */

// Create tables for voting
add_action('admin_init', 'create_voting_tables_once');
function create_voting_tables_once() {
    if (get_option('voting_tables_created')) return;

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $tokens_table = $wpdb->prefix . 'voting_tokens';
    $votes_table = $wpdb->prefix . 'voting_votes';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "
    CREATE TABLE $tokens_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(255) NOT NULL UNIQUE,
        used TINYINT(1) DEFAULT 0
    ) $charset_collate;

    CREATE TABLE $votes_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vote_option VARCHAR(255) NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;
    ";

    dbDelta($sql);

    add_option('voting_tables_created', true);
}

// Shortcode for voting form
function voting_shortcode() {
    ob_start();

    $now = current_time('timestamp');
    $start = strtotime('2025-05-10 10:00:00');
    $end = strtotime('2025-05-10 22:00:00');

    if ($now < $start || $now > $end) {
        echo "<p>Η ψηφοφορία δεν είναι ενεργή αυτή τη στιγμή.</p>";
        return ob_get_clean();
    }

    if (!isset($_GET['token'])) {
        echo "<p>Δεν δόθηκε έγκυρο token πρόσβασης.</p>";
        return ob_get_clean();
    }

    $token = sanitize_text_field($_GET['token']);
    global $wpdb;
    $tokens_table = $wpdb->prefix . 'voting_tokens';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tokens_table WHERE token = %s", $token));

    if (!$row) {
        echo "<p>Μη έγκυρο token.</p>";
        return ob_get_clean();
    }

    if ($row->used) {
        echo "<p>Αυτό το token έχει ήδη χρησιμοποιηθεί.</p>";
        return ob_get_clean();
    }

    if (isset($_POST['vote'])) {
        $vote_option = sanitize_text_field($_POST['vote_option']);
        $wpdb->insert($wpdb->prefix . 'voting_votes', ['vote_option' => $vote_option]);
        $wpdb->update($tokens_table, ['used' => 1], ['token' => $token]);

        echo "<p>Η ψήφος σας καταχωρήθηκε με επιτυχία. Ευχαριστούμε!</p>";
        return ob_get_clean();
    }

    ?>
    <form method="post">
        <p>Ποιος θα είναι ο επόμενος πρόεδρος του συλλόγου;</p>
        <label><input type="radio" name="vote_option" value="Κώστας" required> Κώστας</label><br>
        <label><input type="radio" name="vote_option" value="Αντώνης" required> Αντώνης</label><br><br>
        <button type="submit" name="vote">Ψήφισε</button>
    </form>
    <?php

    return ob_get_clean();
}
add_shortcode('voting_form', 'voting_shortcode');

// Shortcode for voting results
function voting_results_shortcode() {
    ob_start();
    ?>
    <div id="voting-results">
        <p>Φόρτωση αποτελεσμάτων...</p>
    </div>

    <script>
    function fetchResults() {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_voting_results')
            .then(response => response.text())
            .then(data => {
                document.getElementById('voting-results').innerHTML = data;
            });
    }

    fetchResults();
    setInterval(fetchResults, 10000); // Ανανεώνεται κάθε 10"
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('voting_results', 'voting_results_shortcode');

// AJAX handler for voting results
function get_voting_results_callback() {
    $now = current_time('timestamp');
    $end = strtotime('2025-05-10 22:00:00');

    if ($now < $end) {
        echo "<p>Τα αποτελέσματα θα εμφανιστούν μετά το πέρας της ψηφοφορίας.</p>";
        wp_die();
    }

    global $wpdb;
    $table = $wpdb->prefix . 'voting_votes';
    $results = $wpdb->get_results("SELECT vote_option, COUNT(*) as total FROM $table GROUP BY vote_option");

    $total_votes = 0;
    foreach ($results as $row) {
        $total_votes += $row->total;
    }

    echo "<h3>Αποτελέσματα</h3>";
    echo "<ul style='list-style: none; padding: 0;'>";

    foreach ($results as $row) {
        $percentage = $total_votes > 0 ? round(($row->total / $total_votes) * 100) : 0;
        echo "<li><strong>{$row->vote_option}:</strong> {$row->total} ψήφοι ({$percentage}%)</li>";
        echo "<div style='background: #eee; height: 10px; width: 100%; margin-bottom: 10px;'>
                <div style='width: {$percentage}%; background: #0073aa; height: 100%;'></div>
              </div>";
    }

    echo "<p><em>Σύνολο ψήφων: $total_votes</em></p>";

    wp_die();
}
add_action('wp_ajax_get_voting_results', 'get_voting_results_callback');
add_action('wp_ajax_nopriv_get_voting_results', 'get_voting_results_callback');
?>
