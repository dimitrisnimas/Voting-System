<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_menu_page('Voting Dashboard', 'Voting', 'manage_options', 'voting-dashboard', 'render_voting_dashboard', 'dashicons-chart-bar');
});

add_action('admin_init', function () {
    register_setting('voting-settings-group', 'voting_start_time');
    register_setting('voting-settings-group', 'voting_end_time');
    register_setting('voting-settings-group', 'voting_questions', 'sanitize_questions');
});

function sanitize_questions($input) {
    foreach ($input as &$question) {
        if (isset($question['options']) && is_string($question['options'])) {
            $question['options'] = array_map('trim', explode(',', $question['options']));
        }
    }
    return $input;
}

function render_voting_dashboard() {
    global $wpdb;
    $tokens_table = $wpdb->prefix . 'voting_tokens';
    $votes_table = $wpdb->prefix . 'voting_votes';
    $invalids_table = $wpdb->prefix . 'voting_invalids';

    $total_tokens = (int)$wpdb->get_var("SELECT COUNT(*) FROM $tokens_table");
    $used_tokens = (int)$wpdb->get_var("SELECT COUNT(*) FROM $tokens_table WHERE used = 1");
    $participation = $total_tokens > 0 ? round(($used_tokens / $total_tokens) * 100) : 0;

    $questions = get_option('voting_questions', []);

    echo "<div class='wrap'><h1>Voting Dashboard</h1>";
    echo "<p><strong>Total Tokens Issued:</strong> $total_tokens</p>";
    echo "<p><strong>Tokens Used:</strong> $used_tokens</p>";
    echo "<p><strong>Participation Rate:</strong> $participation%</p>";

    echo "<form method='post' action='options.php'>";
    settings_fields('voting-settings-group');
    do_settings_sections('voting-settings-group');

    echo "<h2>Voting Settings</h2>";
    echo "<label>Start Time (Site Timezone): <input type='datetime-local' name='voting_start_time' value='" . esc_attr(get_option('voting_start_time')) . "'></label><br><br>";
    echo "<label>End Time (Site Timezone): <input type='datetime-local' name='voting_end_time' value='" . esc_attr(get_option('voting_end_time')) . "'></label><br><br>";

    echo "<h3>Questions and Options</h3>";
    echo "<div id='question-container'>";
    if (!empty($questions)) {
        foreach ($questions as $index => $question) {
            $options_str = is_array($question['options']) ? implode(', ', $question['options']) : $question['options'];
            echo "<fieldset style='border:1px solid #ccc;padding:10px;margin-bottom:10px;'>";
            echo "<label>Question: <input type='text' name='voting_questions[$index][text]' value='" . esc_attr($question['text']) . "'></label><br>";
            echo "<label>Max Choices: <input type='number' name='voting_questions[$index][max]' value='" . esc_attr($question['max']) . "' min='1'></label><br>";
            echo "<label>Options (comma-separated): <input type='text' name='voting_questions[$index][options]' value='" . esc_attr($options_str) . "'></label>";
            echo "</fieldset>";
        }
    }
    echo "</div>";
    echo "<button type='button' onclick='addQuestion()'>+ Add Question</button><br><br>";
    echo "<input type='submit' value='Save Settings' class='button button-primary'>";
    echo "</form><hr>";

    if (isset($_POST['generate_tokens']) && isset($_POST['token_count'])) {
        $count = (int)$_POST['token_count'];
        $generated_tokens = [];
        for ($i = 0; $i < $count; $i++) {
            $token = wp_generate_password(10, false);
            $wpdb->insert($tokens_table, ['token' => $token, 'used' => 0]);
            $generated_tokens[] = $token;
        }
        echo "<h2>Generated Tokens</h2><ul>";
        foreach ($generated_tokens as $t) {
            $url = site_url('/vote/?token=' . $t);
            echo "<li><code>$t</code> – <a href='$url' target='_blank'>$url</a></li>";
        }
        echo "</ul><hr>";
    }

    echo "<h2>Generate Voting Tokens</h2>
    <form method='post'>
        <label>How many tokens to generate? <input type='number' name='token_count' min='1' required></label>
        <button type='submit' name='generate_tokens' class='button'>Generate</button>
    </form><hr>";

    echo "<script>
    let questionIndex = " . count($questions) . ";
    function addQuestion() {
        const container = document.getElementById('question-container');
        const fieldset = document.createElement('fieldset');
        fieldset.style.border = '1px solid #ccc';
        fieldset.style.padding = '10px';
        fieldset.style.marginBottom = '10px';
        fieldset.innerHTML = `
            <label>Question: <input type='text' name='voting_questions[\${questionIndex}][text]'></label><br>
            <label>Max Choices: <input type='number' name='voting_questions[\${questionIndex}][max]' min='1'></label><br>
            <label>Options (comma-separated): <input type='text' name='voting_questions[\${questionIndex}][options]'></label>
        `;
        container.appendChild(fieldset);
        questionIndex++;
    }
    </script>";

    echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script><h2>Live Results</h2>";

    foreach ($questions as $qid => $question) {
        $question_id = "q" . $qid;
        $results = $wpdb->get_results($wpdb->prepare("SELECT vote_option, COUNT(*) as total FROM $votes_table WHERE question_id = %s GROUP BY vote_option", $question_id));
        $invalid_votes = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $invalids_table WHERE question_id = %s", $question_id));
        $valid_votes = array_sum(array_map(fn($r) => $r->total, $results));

        echo "<h3>" . esc_html($question['text']) . "</h3>";
        echo "<p><strong>Valid Votes:</strong> $valid_votes</p>";
        echo "<p><strong>Invalid Votes:</strong> $invalid_votes</p>";

        $labels = json_encode(array_map(fn($r) => $r->vote_option, $results));
        $data = json_encode(array_map(fn($r) => (int)$r->total, $results));

        echo "<canvas id='chart_$question_id' width='400' height='200'></canvas><script>
        const ctx_$question_id = document.getElementById('chart_$question_id').getContext('2d');
        new Chart(ctx_$question_id, {
            type: 'bar',
            data: {
                labels: $labels,
                datasets: [{
                    label: 'Votes',
                    data: $data,
                    backgroundColor: 'rgba(0, 115, 170, 0.6)'
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
        </script>";
    }

    echo "</div>";
}
