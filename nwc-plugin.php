<?php
/*
Plugin Name: Neatly Woo Commerce Extension
Plugin URI: https://neatly.io/woocommerce
Description: Gather metrics from your Woo Commerce store and view them in Neatly
Version: 1.0.1
Author: Neatly.io
Author URI: https://neatly.io
Requires at least: 3.2
Tested up to: 4.2
*/

require_once 'nwc-api-endpoint.php';
require_once 'nwc-settings.php';

function nwc_init()
{
    add_rewrite_rule('^neatly-wc/?$', 'index.php?nwc-api-route=/', 'top');
    add_rewrite_rule('^neatly-wc/([A-Za-z0-9_]*)/?$', 'index.php?nwc-api-route=$matches[1]', 'top');

    flush_rewrite_rules();
}

function nwc_parse_request()
{
    global $wp;

    if (isset($wp->query_vars['nwc-api-route'])) {
        nwc_api_route($wp->query_vars['nwc-api-route']);
    }
}

function nwc_filter_query_vars($vars)
{
    $vars[] = 'nwc-api-route';
    $vars[] = 'nwc-token';
    $vars[] = 'start_date';
    $vars[] = 'end_date';
    $vars[] = 'items_cond';
    $vars[] = 'items';
    $vars[] = 'value_cond';
    $vars[] = 'value';
    $vars[] = 'email';
    $vars[] = 'city';
    $vars[] = 'country';
    $vars[] = 'region';
    $vars[] = 'postcode';
    return $vars;
}

add_action('init', 'nwc_init');
add_action('parse_request', 'nwc_parse_request');
add_filter('query_vars', 'nwc_filter_query_vars');
