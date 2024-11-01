<div class="wrap tot-settings-page">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form action="admin.php?page=totsettings" method="post">
        <div class="tot-left-col">

            <?php
//                settings_fields('tot_settings_email');
//                do_settings_sections('tot_settings_email');
            ?>

            <?php
                settings_fields('tot_settings_tracking');
                do_settings_sections('tot_settings_tracking');
            ?>

            <?php
                settings_fields('tot_settings_verification_gates');
                do_settings_sections('tot_settings_verification_gates');
            ?>

            <?php
                settings_fields('tot_settings_approval');
                do_settings_sections('tot_settings_approval');
            ?>

            <?php
                settings_fields('tot_settings_disabled_pages');
                do_settings_sections('tot_settings_disabled_pages');
            ?>

			<?php
			settings_fields('tot_settings_generated_pages');
			do_settings_sections('tot_settings_generated_pages');
			?>

			<?php
			settings_fields('tot_settings_advanced_general');
			do_settings_sections('tot_settings_advanced_general');
			?>

            <?php
                submit_button('Save Settings');
            ?>

        </div>
    </form>
</div>