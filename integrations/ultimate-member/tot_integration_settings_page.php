<div class="wrap tot-settings-page">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form action="admin.php?page=tot_um_settings" method="post">
        <?php
        settings_fields('tot_um_settings');
        do_settings_sections('tot_um_settings');
        submit_button('Save Settings');
        ?>

    </form>
</div>
