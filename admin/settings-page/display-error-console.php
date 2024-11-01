<?php

function tot_display_error_console($possibleErrors) {

    ?>

    <div class="tot-full-width" id="tot-error-summary">
        <h2>Errors</h2>
        <?php

        $noErrorsFound = true;
        foreach ($possibleErrors as $error) {
            if (is_wp_error($error) || (is_array($error) && isset($error['errors']) && !empty($error['errors']))) {
                tot_always_display_error($error);
                $noErrorsFound = false;
            }
        }

        if ($noErrorsFound) {
            ?>
            <p>No errors detected.</p>
            <?php
        }
        ?>
    </div>

    <?php

}