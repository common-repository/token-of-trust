<?php
use TOT\Integrations\WooCommerce\Donations;
$tables = Donations::get_tables();

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <div class="tot-card">
        <div class="tot-card-header">
            <h2>Stats</h2>
        </div>
        <div class="tot-card-content">
            <div class="tot-stats">
                <div class="tot-tabs">
                    <ul id="tot-tabs-nav">
                        <?php foreach ($tables as $key => $table): ?>
                            <li><a href="#tot-tab<?php echo $key; ?>"><?php echo $table['label']; ?></a></li>
                        <?php endforeach; ?>
                    </ul> <!-- END tot-tabs-nav -->
                    <div id="tot-tabs-content">

                        <?php foreach ($tables as $key => $table): ?>

                            <div id="tot-tab<?php echo $key; ?>" class="tot-tab-content">
                                <div class="tot-total-wrapper">
                                    <h3>Total No. of orders: <?php echo count($table['orders']); ?></h3>
                                    <h3>Sum of Charitable donations: <?php echo get_woocommerce_currency_symbol().$table['sum']; ?></h3>
                                    <div class="tot-export-wrapper">

                                        <a href="?page=totsettings_donations&tot_export_table=<?php echo $key; ?>" class="tot-btn-export">
                                            export
                                        </a>
                                    </div>
                                </div>
                                <table class="wp-list-table widefat fixed striped table-view-list">
                                    <thead>
                                        <th>Date</th>
                                        <th>Order number</th>
                                        <th>Charitable amount</th>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($table['orders'] as $order): ?>
                                        <tr>
                                            <td>
                                                <?php echo $order->get_date_created(); ?>
                                            </td>
                                            <td>
                                                <?php echo '<a target="_blank" href="' . $order->get_edit_order_url() . '">' . $order->get_id() . "</a>"; ?>
                                            </td>
                                            <td>
                                                <?php echo get_woocommerce_currency_symbol() . $order->get_meta('tot_donation_value', true); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div> <!-- END tot-tabs-content -->
                </div> <!-- END tot-tabs -->
            </div>
        </div>
    </div>
</div>