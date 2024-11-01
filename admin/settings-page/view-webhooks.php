<div class="wrap tot-settings-page tot-webhooks">

    <h1>Webhook Activity</h1>

    <div id="tot-webhook-activity">
        <?php

        $hook_log = get_option("tot_hook_log", array());

        foreach ($hook_log as $item) {

            $notice = '';
            if (isset($item['timestamp'])) {
                $notice = '<p>Timestamp: ' . $item['timestamp'] . '</p>';
            }

            if (isset($item['status_message'])) {
                $notice = $notice . '<p>Message: ' . $item['status_message'] . '</p>';
            }

            $expected_signature = isset($item['expected_signature']) ? $item['expected_signature'] : null;
            if (isset($expected_signature)) {
                $given_signature = $item['given_signature'];
                if ($given_signature !== $expected_signature) {
                    $notice = $notice . '<p>Expected: ' . $expected_signature . '<br>Given: ' . $given_signature . '</p>';
                }
            }

            printf(
                '<div class="notice notice-%2$s">%3$s %1$s</div>',
                isset($item['body']) ? ('<pre>' . esc_html($item['body']) . '</pre>') : '',
                $item['type'],
                $notice
            );
        }

        if (!empty($hook_log)) {
            //delete_option( "tot_hook_log", array() );
        } else {
            echo '<p>No webhook activity.</p>';
        }
        ?>
    </div>
</div>