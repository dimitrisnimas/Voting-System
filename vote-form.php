<?php
if (!defined('ABSPATH')) exit;

add_shortcode('voting_form', 'render_voting_form');

function render_voting_form() {
    global $wpdb;
    $token = $_GET['token'] ?? '';
    $tokens_table = $wpdb->prefix . 'voting_tokens';
    $votes_table = $wpdb->prefix . 'voting_votes';
    $invalids_table = $wpdb->prefix . 'voting_invalids';

    // Time check
    $now = current_time('timestamp');
    $start = strtotime(get_option('voting_start_time'));
    $end = strtotime(get_option('voting_end_time'));

    if ($now < $start || $now > $end) {
        return "<p>Η ψηφοφορία δεν είναι ενεργή.</p>";
    }

    // Token check
    $valid_token = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tokens_table WHERE token = %s AND used = 0", $token));
    if (!$valid_token) return "<p>Το μοναδικό Link δεν είναι έγκυρο ή έχει χρησιμοποιηθεί.</p>";

    $questions = get_option('voting_questions', []);
    if (empty($questions)) return "<p>No active questions found.</p>";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['votes'])) {
        $invalid = false;
        foreach ($questions as $qid => $q) {
            $qid_key = "q$qid";
            $selected = $_POST['votes'][$qid_key] ?? [];
            if (!is_array($selected)) $selected = [$selected];

            if (count($selected) > $q['max']) {
                $wpdb->insert($invalids_table, [
                    'token' => $token,
                    'question_id' => $qid_key
                ]);
                $invalid = true;
            } else {
                foreach ($selected as $opt) {
                    $wpdb->insert($votes_table, [
                        'token' => $token,
                        'question_id' => $qid_key,
                        'vote_option' => sanitize_text_field($opt)
                    ]);
                }
            }
        }

        $wpdb->update($tokens_table, ['used' => 1], ['token' => $token]);

        return $invalid ? "<p>Η ψήφος σας καταγράφηκε.</p>" : "<p>Ευχαριστούμε!</p>";
    }

    ob_start();
    echo "<form method='post'>";
    foreach ($questions as $qid => $q) {
        $qid_key = "q$qid";
        echo "<fieldset><legend>" . esc_html($q['text']) . "</legend>";
        foreach ($q['options'] as $opt) {
            $input_type = $q['max'] === 1 ? 'radio' : 'checkbox';
            $name = $q['max'] === 1 ? "votes[$qid_key]" : "votes[$qid_key][]";
            echo "<label><input type='$input_type' name='$name' value='" . esc_attr($opt) . "'> " . esc_html($opt) . "</label><br>";
        }
        echo "<small>Επιλέξτε έως {$q['max']}</small></fieldset><br>";
    }
    echo "<button type='submit'>Υποβολή</button></form>";
    return ob_get_clean();
}