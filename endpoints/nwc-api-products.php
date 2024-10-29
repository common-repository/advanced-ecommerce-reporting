<?php
require_once __DIR__ . '/../nwc-util.php';
require_once __DIR__ . '/../nwc-sql-queries.php';

function nwc_api_products_top()
{
    global $wp, $wpdb;

    if (!(isset($wp->query_vars['start_date']) || isset($wp->query_vars['end_date']))) {
        http_response_code(400);
        exit;
    }

    $top_products = $wpdb->get_results($wpdb->prepare(NWC_API_PRODUCTS_TOP, [
        $wp->query_vars['start_date'],
        $wp->query_vars['end_date']
    ]));

    nwc_send_json(['top_products' => $top_products]);
}
