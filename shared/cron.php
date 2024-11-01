<?php
use TOT\Settings;
/**
 * Scrap FAQ page daily
 */
add_action('init', function () {
	if (Settings::get_setting('tot_field_disable_generating_faq')) {
		wp_clear_scheduled_hook( 'tot_get_faq_page' );
	} elseif ( ! wp_next_scheduled( 'tot_get_faq_page' ) ) {
		wp_schedule_event( time() + 1, 'daily', 'tot_get_faq_page' );
	}
});

add_action ( 'tot_get_faq_page', 'tot_scrap_faq_page');

function tot_scrap_faq_page() {
	if (!is_tot_connection_ok() || Settings::get_setting('tot_field_disable_generating_faq')) {
		return;
	}

	$domain = tot_get_setting_prod_domain();
	$url = tot_production_origin() . '/com/' . $domain;
	$response = wp_remote_get(tot_production_origin() . '/com/' . $domain);
	if (is_wp_error($response)) {
		return;
	}

	$body = wp_remote_retrieve_body($response);
	$dom = new DOMDocument();

	// stop reporting warning for this
	libxml_use_internal_errors(true);
	$dom->loadHTML('<?xml encoding="UTF-8">' . $body);
	$xpath = new DOMXPath($dom);
	libxml_clear_errors();

	$className = 'faq-section';
	$faqSection = $xpath->query("//*[contains(@class, '$className')]");

	$privacyId = 'privacy';
	$privacySection = $xpath->query("//*[@id='$privacyId']");

	$result = "<div id='tot-faq'>";
	if ($faqSection->length > 0) {
		$result .= $dom->saveHTML($faqSection->item(0));
	}

	if ($privacySection->length > 0) {
		$result .= $dom->saveHTML($privacySection->item(0));
	}
	$result .= "For more information about <a target='_blank' href='https://tokenoftrust.com/'>Token of Trust</a> identity verification, view our <a target='_blank' href='$url'>FAQ page</a>.";
	$result .= "</div>";

	$page_title = 'Token of Trust FAQ';
	$args = array(
		'post_type'              => 'page',
		'title'                  => $page_title,
		'post_status'            => 'all',
		'posts_per_page'         => 1,
		'no_found_rows'          => true,
		'order'                  => 'ASC',
	);
	$query = new WP_Query($args);
	$page = ! empty( $query->post ) ? $query->post : null;

	if ($page) {
		wp_update_post(array(
			'ID' => $page->ID,
			'post_content' => $result,
			'post_status' => 'publish'
		));
	}
}