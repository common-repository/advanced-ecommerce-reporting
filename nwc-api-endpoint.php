<?php
require_once 'endpoints/nwc-api-index.php';
require_once 'endpoints/nwc-api-orders.php';
require_once 'endpoints/nwc-api-products.php';
require_once 'nwc-util.php';

function nwc_api_route($route)
{
    global $wp;

    nwc_verify_apitoken($wp->query_vars['nwc-token']);

    switch ($route) {
        case '/':
            nwc_api_index();
            break;
        case 'top_products':
            nwc_api_products_top();
            break;
        case 'order_stats':
            nwc_api_orderstats();
            break;
        case 'order_search':
            nwc_api_ordersearch();
            break;
        default:
            http_response_code(404);
            exit;
    }
}
