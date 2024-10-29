<?php
require_once __DIR__ . '/../nwc-util.php';

function nwc_api_index()
{
    global $wp_version;

    $nwc_version = nwc_version();
    $wc_version = nwc_wc_version();

    nwc_send_json([
        'version' => [
            'nwc' => $nwc_version,
            'wc' => $wc_version,
            'wp' => $wp_version
        ],
        'url' => get_bloginfo('url'),
        'currency' => get_option('woocommerce_currency'),
        'units' => [
            'weight' => get_option('woocommerce_weight_unit'),
            'dimension' => get_option('woocommerce_dimension_unit')
        ]
    ]);
}

function nwc_version()
{
    if (!function_exists('get_plugins')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    $plugin_folder = get_plugins('/neatly-woocommerce');
    $plugin_file = 'nwc-plugin.php';

    if (isset($plugin_folder[$plugin_file]['Version'])) {
        return $plugin_folder[$plugin_file]['Version'];
    } else {
        return null;
    }
}

function nwc_wc_version()
{
    if (!function_exists('get_plugins')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    $plugin_folder = get_plugins('/woocommerce');
    $plugin_file = 'woocommerce.php';

    if (isset($plugin_folder[$plugin_file]['Version'])) {
        return $plugin_folder[$plugin_file]['Version'];
    } else {
        return null;
    }
}
