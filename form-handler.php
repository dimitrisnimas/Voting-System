<?php
if (!defined('ABSPATH')) exit;

function voting_shortcode() {
    ob_start();
    global $wpdb;

    $now = current_time('timestamp');
    $start = strtotime(get_voting_setting('voting_start', '2025-05-10 10:00:00'));
    $end = strtotime(get_voting_setting('voting_end', '2025-05-10 22:00:00'));

    if ($now < $start || $now > $end) {
        echo "<p>Η ψηφοφορία δεν είναι ενεργή αυτή τη στιγμή.</p>";
        return ob_get_clean();
    }

    if (!isset($_GET['token'])) {
        echo "<p>Δεν δόθηκε έγκυρο token πρόσβασης.</p>";
        return ob_get_clean();
    }

    $token = sanitize_text_field($_GET['token']);
    $tokens_table = $wpdb->prefix . 'voting_tokens';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tokens_table WHERE token = %s", $token));

    if (!$row || $row->used) {
        echo "<p>Μη έγκυρο ή χρησιμοποιημένο token.</p>";
        return ob_get_clean();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $max_per_question = [
            'question1' => 7,
            'question3' => 3
        ];

        $invalid = false;
        foreach ($max_per_question as $qid => $max) {
            $votes = isset($_POST[$qid]) ? (array) $_POST[$qid] : [];
            if (count($votes) > $max) {
                $wpdb->insert($wpdb->prefix . 'voting_invalids', [
                    'token' => $token,
                    'question_id' => $qid,
                    'reason' => 'Too many selections'
                ]);
                $invalid = true;
            } else {
                foreach ($votes as $vote) {
                    $wpdb->insert($wpdb->prefix . 'voting_votes', [
                        'token' => $token,
                        'question_id' => $qid,
                        'vote_option' => sanitize_text_field($vote)
                    ]);
                }
            }
        }

        $wpdb->update($tokens_table, ['used' => 1], ['token' => $token]);
        echo $invalid ? "<p>Η ψήφος καταχωρήθηκε.</p>" : "<p>Ευχαριστούμε!</p>";
        return ob_get_clean();
    }

    ?>
    <form method="post">
        <h3>Ερώτηση 1 (έως 7 επιλογές):</h3>
        <?php for ($i = 1; $i <= 10; $i++): ?>
            <label><input type="checkbox" name="question1[]" value="Επιλογή <?php echo $i; ?>"> Επιλογή <?php echo $i; ?></label><br>
        <?php endfor; ?>

        <h3>Ερώτηση 3 (έως 3 επιλογές):</h3>
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <label><input type="checkbox" name="question3[]" value="Επιλογή <?php echo $i; ?>"> Επιλογή <?php echo $i; ?></label><br>
        <?php endfor; ?>

        <button type="submit">Υποβολή</button>
    </form>
    <?php

    return ob_get_clean();
}
add_shortcode('voting_form', 'voting_shortcode');

function get_voting_setting($name, $default = '') {
    global $wpdb;
    $settings_table = $wpdb->prefix . 'voting_settings';
    $val = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM $settings_table WHERE option_name = %s", $name));
    return $val !== null ? $val : $default;
}