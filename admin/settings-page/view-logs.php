<div class="wrap tot-settings-page tot-logs">

    <h1>Debug Logs</h1>

    <div id="tot-logs">
        <?php
        wp_cache_delete("tot_logs", 'options');
        $logs = get_option("tot_logs", array());
        foreach ($logs as $item) {

            $notice = '';
            if (isset($item['head'])) {
                $notice .= '<h3>' . $item['head'] . '</h3>';
            }
            if (isset($item['timestamp'])) {
                $notice .= '<p>Timestamp: ' . $item['timestamp'] . '</p>';
            }

            printf(
                '<div class="notice notice-%2$s">%3$s %1$s</div>',
                isset($item['body']) && $item['body'] ? ('<pre>' . str_replace(['&lt;b&gt;', '&lt;/b&gt;'], ['<b>', '</b>'],esc_html($item['body'])) . '</pre>') : '',
                $item['type'],
                $notice
            );
        }

        if (empty($logs)){
            echo '<p>No Messages have been logged.</p>';
        }
        ?>
    </div>
</div>
