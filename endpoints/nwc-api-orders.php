<?php
require_once __DIR__ . '/../nwc-sql-queries.php';
require_once __DIR__ . '/../nwc-util.php';

function nwc_api_orderstats()
{
    global $wp, $wpdb;

    if (!(isset($wp->query_vars['start_date']) || isset($wp->query_vars['end_date']))) {
        http_response_code(400);
        exit;
    }

    $chart_data = $wpdb->get_results($wpdb->prepare(NWC_API_ORDERSTATS_CHART_SQL, [
        $wp->query_vars['start_date'],
        $wp->query_vars['end_date']
    ]));

    $data1 = $wpdb->get_results($wpdb->prepare(NWC_API_ORDERSTATS_SQL1, [
        $wp->query_vars['start_date'],
        $wp->query_vars['end_date']
    ]));

    $data2 = $wpdb->get_results($wpdb->prepare(NWC_API_ORDERSTATS_SQL2, [
        $wp->query_vars['start_date'],
        $wp->query_vars['end_date']
    ]));

    $rows = array_merge((array) $data1, (array) $data2);

    $payment_types_data = $wpdb->get_results($wpdb->prepare(NWC_API_ORDERSTATS_SQL3, [
        $wp->query_vars['start_date'],
        $wp->query_vars['end_date']
    ]));

    $by_hour = $wpdb->get_results($wpdb->prepare(NWC_API_ORDERS_BY_HOUR, [
        $wp->query_vars['start_date'],
        $wp->query_vars['end_date']
    ]));

    $by_dow = $wpdb->get_results($wpdb->prepare(NWC_API_ORDERS_BY_DOW, [
        $wp->query_vars['start_date'],
        $wp->query_vars['end_date']
    ]));

    $by_country = $wpdb->get_results($wpdb->prepare(NWC_API_ORDERS_BY_COUNTRY, [
        $wp->query_vars['start_date'],
        $wp->query_vars['end_date']
    ]));

    $by_city = $wpdb->get_results($wpdb->prepare(NWC_API_ORDERS_BY_CITY, [
        $wp->query_vars['start_date'],
        $wp->query_vars['end_date']
    ]));

    $customers = $wpdb->get_results($wpdb->prepare(NWC_API_NEW_VS_USED_CUSTOMERS, [
        $wp->query_vars['start_date'],
        $wp->query_vars['end_date']
    ]));

    $results = [];
    $included_anyway = [];
    $not_orders = ['wc-refunded', 'wc-pending', 'wp-cancelled', 'wc-on-hold', 'wc-failed'];

    foreach ($rows as $row) {
        foreach ($row as $key => $val) {
            if (isset($results['total'][$key])) {
                $results[$row->post_status][$key] += is_numeric($val) ? floatval($val) : $val;

                if (!in_array($row->post_status, $not_orders)) {
                    $results['total'][$key] += is_numeric($val) ? floatval($val) : $val;
                }
            } else {
                $results[$row->post_status][$key] = is_numeric($val) ? floatval($val) : $val;

                if (!in_array($row->post_status, $not_orders)) {
                    $results['total'][$key] = is_numeric($val) ? floatval($val) : $val;
                }
            }
        }
    }

    foreach ($by_hour as &$hour) {
        $hour->hr .= ":00";
    }

    nwc_send_json([
        'count' => $results['total']['order_count'] ?: 0,
        'total' => $results['total']['order_total'] ?: 0,
        'avg_total' => $results['total']['average_order_total'] ?: 0,
        'tax' => $results['total']['order_tax'] ?: 0,
        'shipping' => $results['total']['order_shipping'] ?: 0,
        'refunded' => $results['wc-refunded']['order_total'] ?: 0,
        'discounts' => $results['total']['order_discount'] ?: 0,
        'items' => $results['total']['qty'] ?: 0,
        'items_per_order' => $results['total']['qty'] / $results['total']['order_count'] ?: 0,
        'chart_data' => nwc_order_chart_data($chart_data),
        'payment_types' => $payment_types_data,
        'orders_by_hour' => $by_hour,
        'orders_by_dow' => $by_dow,
        'orders_by_country' => $by_country,
        'orders_by_city' => $by_city,
        'customers' => !empty($customers) ? $customers[0]['percentage'] : 0
    ]);
}

function nwc_order_chart_data($orders)
{
    global $wp;

    $dates = nwc_get_dates($wp->query_vars['start_date'], $wp->query_vars['end_date']);

    foreach ($dates as $date) {
        $data[$date] = [
            'count' => 0,
            'total' => 0,
            'tax' => 0,
            'shipping' => 0,
            'refunded' => 0,
            'discounts' => 0
        ];
    }

    foreach ($orders as $order) {
        switch ($order->post_status) {
            case 'wc-refunded':
                $data[$order->ordered]['refunded'] += $order->order_total;
                break;
            default:
                $data[$order->ordered]['count'] += $order->order_count;
                $data[$order->ordered]['total'] += $order->order_total;
                $data[$order->ordered]['tax'] += $order->order_tax;
                $data[$order->ordered]['shipping'] += $order->order_shipping;
                $data[$order->ordered]['discounts'] += $order->order_discount;
                break;
        }
    }

    return $data;
}

function nwc_api_ordersearch()
{
    global $wp, $wpdb;
    $query = NWC_API_ORDER_SEARCH;
    $limit = 250;
    $where = [];

    if (isset($wp->query_vars['items_cond']) && isset($wp->query_vars['items'])) {
        switch ($wp->query_vars['items_cond']) {
            case 'eq':
            default:
                $where[] = $wpdb->prepare("q.order_items = %d", [$wp->query_vars['items']]);
                break;
            case 'gt':
                $where[] = $wpdb->prepare("q.order_items > %d", [$wp->query_vars['items']]);
                break;
            case 'lt':
                $where[] = $wpdb->prepare("q.order_items < %d", [$wp->query_vars['items']]);
                break;
            case 'neq':
                $where[] = $wpdb->prepare("q.order_items <> %d", [$wp->query_vars['items']]);
                break;
        }
    }

    if (isset($wp->query_vars['value_cond']) && isset($wp->query_vars['value'])) {
        switch ($wp->query_vars['value_cond']) {
            case 'eq':
            default:
                $where[] = $wpdb->prepare("q.order_value = %d", [$wp->query_vars['value']]);
                break;
            case 'gt':
                $where[] = $wpdb->prepare("q.order_value > %d", [$wp->query_vars['value']]);
                break;
            case 'lt':
                $where[] = $wpdb->prepare("q.order_value < %d", [$wp->query_vars['value']]);
                break;
            case 'neq':
                $where[] = $wpdb->prepare("q.order_value <> %d", [$wp->query_vars['items']]);
                break;
        }
    }

    if (isset($wp->query_vars['start_date']) && isset($wp->query_vars['end_date'])) {
        $where[] = $wpdb->prepare("q.post_date_gmt BETWEEN %s AND %s", [$wp->query_vars['start_date'], $wp->query_vars['end_date']]);
    }

    if (isset($wp->query_vars['email'])) {
        $where[] = $wpdb->prepare("q.email LIKE %s", ['%' . $wp->query_vars['email'] . '%']);
    }

    if (isset($wp->query_vars['city'])) {
        $where[] = $wpdb->prepare("q.city LIKE %s", ['%' . $wp->query_vars['city'] . '%']);
    }

    if (isset($wp->query_vars['country'])) {
        if ($wp->query_vars['country'] !== 'ALL') {
            $where[] = $wpdb->prepare("q.country_code = %s", [$wp->query_vars['country']]);
        }
    }

    if (isset($wp->query_vars['region'])) {
        $where[] = $wpdb->prepare("q.region LIKE %s", ['%' . $wp->query_vars['region'] . '%']);
    }

    if (isset($wp->query_vars['postcode'])) {
        $where[] = $wpdb->prepare("q.postcode LIKE %s", ['%' . $wp->query_vars['postcode'] . '%']);
    }

    if (isset($wp->query_vars['page'])) {
        $page = (intval($wp->query_vars['page']) - 1);
        $offset = $page * $limit;
    } else {
        $page = 0;
        $offset = 0;
    }

    $data = $wpdb->get_results($query . ' WHERE ' . implode(' AND ', $where) . ' LIMIT ' . $offset . ',' . $limit);
    nwc_send_json($data);
}
