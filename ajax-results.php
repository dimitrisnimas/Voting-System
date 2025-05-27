<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_get_voting_results', 'get_voting_results_callback');
add_action('wp_ajax_nopriv_get_voting_results', 'get_voting_results_callback');

function get_voting_results_callback() {
    global $wpdb;

    $now = current_time('timestamp');
    $end = strtotime(get_voting_setting('voting_end', '2025-05-10 22:00:00'));
    if ($now < $end) {
        echo "<p>Τα αποτελέσματα θα εμφανιστούν μετά τη λήξη της ψηφοφορίας.</p>";
        wp_die();
    }

    $questions = ['question1', 'question3'];
    echo "<div><h3>Αποτελέσματα</h3>";
    foreach ($questions as $qid) {
        echo "<h4>Ερώτηση: $qid</h4>";
        $results = $wpdb->get_results($wpdb->prepare("SELECT vote_option, COUNT(*) as total FROM {$wpdb->prefix}voting_votes WHERE question_id = %s GROUP BY vote_option", $qid));

        $total_votes = array_sum(array_column($results, 'total'));
        foreach ($results as $row) {
            $pct = $total_votes > 0 ? round(($row->total / $total_votes) * 100) : 0;
            echo "<div><strong>{$row->vote_option}:</strong> {$row->total} ({$pct}%)</div>";
            echo "<div style='background:#eee;height:10px;width:100%;margin-bottom:10px;'>
                <div style='width: {$pct}%; background: #0073aa; height: 100%;'></div>
            </div>";
        }
        $invalids = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}voting_invalids WHERE question_id = %s", $qid));
        echo "<p><em>Άκυρες ψήφοι: $invalids</em></p><hr>";
    }
    echo "</div>";
    wp_die();
}
