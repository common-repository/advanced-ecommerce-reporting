<?php
global $wpdb;

define("NWC_API_PRODUCTS_TOP", "SELECT
    (SELECT post_title FROM {$wpdb->prefix}posts p WHERE p.ID = a.product_id) AS name,
    a.qty,
    a.total
FROM (
    SELECT
        (SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta woim1 WHERE woim1.order_item_id = woi.order_item_id AND woim1.meta_key='_product_id') AS product_id,
        SUM((SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta woim2 WHERE woim2.order_item_id = woi.order_item_id AND woim2.meta_key='_qty')) AS qty,
        SUM((SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta woim3 WHERE woim3.order_item_id = woi.order_item_id AND woim3.meta_key='_line_total')) AS total
    FROM {$wpdb->prefix}woocommerce_order_items woi JOIN (SELECT ID FROM {$wpdb->prefix}posts WHERE post_type='shop_order' AND DATE(post_date) BETWEEN %s AND %s) p1 ON p1.ID = woi.order_id
    WHERE woi.order_item_type='line_item' GROUP BY product_id
) a ORDER BY total DESC, qty DESC LIMIT 25");

define("NWC_API_ORDERSTATS_CHART_SQL", "SELECT
    DATE(p.post_date) AS ordered,
    p.post_status,
    COUNT(*) AS order_count,
    SUM((SELECT meta_value FROM {$wpdb->prefix}postmeta pm1 WHERE pm1.post_id = p.ID AND pm1.meta_key='_order_total')) AS order_total,
    SUM((SELECT meta_value FROM {$wpdb->prefix}postmeta pm2 WHERE pm2.post_id = p.ID AND pm2.meta_key='_order_tax')) AS order_tax,
    SUM((SELECT meta_value FROM {$wpdb->prefix}postmeta pm3 WHERE pm3.post_id = p.ID AND pm3.meta_key='_order_shipping')) AS order_shipping,
    SUM((SELECT meta_value FROM {$wpdb->prefix}postmeta pm4 WHERE pm4.post_id = p.ID AND pm4.meta_key='_order_shipping_tax')) AS order_shipping_tax,
    SUM((SELECT meta_value FROM {$wpdb->prefix}postmeta pm5 WHERE pm5.post_id = p.ID AND pm5.meta_key='_cart_discount')) AS order_discount,
    SUM((SELECT meta_value FROM {$wpdb->prefix}postmeta pm6 WHERE pm6.post_id = p.ID AND pm6.meta_key='_cart_discount_tax')) AS order_discount_tax
FROM {$wpdb->prefix}posts p WHERE p.post_type='shop_order' AND DATE(p.post_date) BETWEEN %s AND %s GROUP BY DATE(p.post_date), post_status");

define("NWC_API_ORDERSTATS_SQL1", "SELECT
    p.post_status,
    COUNT(*) AS order_count,
    AVG((SELECT meta_value FROM {$wpdb->prefix}postmeta pm1 WHERE pm1.post_id = p.ID AND pm1.meta_key='_order_total')) AS average_order_total,
    SUM((SELECT meta_value FROM {$wpdb->prefix}postmeta pm2 WHERE pm2.post_id = p.ID AND pm2.meta_key='_order_total')) AS order_total,
    SUM((SELECT meta_value FROM {$wpdb->prefix}postmeta pm3 WHERE pm3.post_id = p.ID AND pm3.meta_key='_order_tax')) AS order_tax,
    SUM((SELECT meta_value FROM {$wpdb->prefix}postmeta pm4 WHERE pm4.post_id = p.ID AND pm4.meta_key='_order_shipping')) AS order_shipping,
    SUM((SELECT meta_value FROM {$wpdb->prefix}postmeta pm5 WHERE pm5.post_id = p.ID AND pm5.meta_key='_order_shipping_tax')) AS order_shipping_tax,
    SUM((SELECT meta_value FROM {$wpdb->prefix}postmeta pm6 WHERE pm6.post_id = p.ID AND pm6.meta_key='_cart_discount')) AS order_discount,
    SUM((SELECT meta_value FROM {$wpdb->prefix}postmeta pm7 WHERE pm7.post_id = p.ID AND pm7.meta_key='_cart_discount_tax')) AS order_discount_tax
FROM {$wpdb->prefix}posts p WHERE p.post_type='shop_order' AND DATE(p.post_date) BETWEEN %s AND %s GROUP BY p.post_status");

define("NWC_API_ORDERSTATS_SQL2", "SELECT
    p1.post_status,
    COUNT(*) AS line_items,
    SUM((SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta woim1 WHERE woim1.order_item_id = woi.order_item_id AND woim1.meta_key='_qty')) AS qty,
    AVG((SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta woim2 WHERE woim2.order_item_id = woi.order_item_id AND woim2.meta_key='_qty')) AS avg_qty
FROM {$wpdb->prefix}woocommerce_order_items woi LEFT JOIN
(SELECT ID, post_status, post_date FROM {$wpdb->prefix}posts p WHERE p.post_type='shop_order') p1 ON woi.order_id = p1.ID
WHERE woi.order_item_type='line_item' AND DATE(p1.post_date) BETWEEN %s AND %s GROUP BY p1.post_status");

define("NWC_API_ORDERSTATS_SQL3", "SELECT
    pm.payment_method,
    SUM(woim1.qty) AS qty,
    SUM(woim2.total) AS total
FROM {$wpdb->prefix}posts p LEFT JOIN
(SELECT post_id, meta_value AS payment_method FROM {$wpdb->prefix}postmeta WHERE meta_key='_payment_method') pm ON pm.post_id = p.ID LEFT JOIN
(SELECT order_item_id, order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type='line_item') woi ON woi.order_id = p.ID LEFT JOIN
(SELECT order_item_id, meta_value AS qty FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key='_qty') woim1 ON woim1.order_item_id = woi.order_item_id LEFT JOIN
(SELECT order_item_id, meta_value AS total FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key='_line_total') woim2 ON woim2.order_item_id = woi.order_item_id
WHERE p.post_type='shop_order' AND DATE(p.post_date) BETWEEN %s AND %s AND payment_method IS NOT NULL
GROUP BY payment_method ORDER BY total DESC, qty DESC LIMIT 25");

define("NWC_API_ORDERS_BY_HOUR", "SELECT
    HOUR(p.post_date) AS hr,
    COUNT(woim1.qty) AS qty,
    SUM(woim2.total) AS total
FROM {$wpdb->prefix}posts p LEFT JOIN
(SELECT order_item_id, order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type='line_item') woi ON woi.order_id = p.ID LEFT JOIN
(SELECT order_item_id, meta_value AS qty FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key='_qty') woim1 ON woim1.order_item_id = woi.order_item_id LEFT JOIN
(SELECT order_item_id, meta_value AS total FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key='_line_total') woim2 ON woim2.order_item_id = woi.order_item_id
WHERE p.post_type='shop_order' AND DATE(p.post_date) BETWEEN %s AND %s
GROUP BY hr ORDER BY total DESC, qty DESC LIMIT 24");

define("NWC_API_ORDERS_BY_DOW", "SELECT
    DATE_FORMAT(p.post_date, '%%W') AS dow,
    COUNT(woim1.qty) AS qty,
    SUM(woim2.total) AS total
FROM {$wpdb->prefix}posts p LEFT JOIN
(SELECT order_item_id, order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type='line_item') woi ON woi.order_id = p.ID LEFT JOIN
(SELECT order_item_id, meta_value AS qty FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key='_qty') woim1 ON woim1.order_item_id = woi.order_item_id LEFT JOIN
(SELECT order_item_id, meta_value AS total FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key='_line_total') woim2 ON woim2.order_item_id = woi.order_item_id
WHERE p.post_type='shop_order' AND DATE(p.post_date) BETWEEN '2016-03-01' AND '2016-03-14'
GROUP BY dow ORDER BY total DESC, qty DESC LIMIT 7");

define("NWC_API_ORDERS_BY_COUNTRY", "SELECT
    pm.country,
    COUNT(woim1.qty) AS qty,
    SUM(woim2.total) AS total
FROM {$wpdb->prefix}posts p LEFT JOIN
(SELECT post_id, meta_value AS country FROM {$wpdb->prefix}postmeta WHERE meta_key='_billing_country') pm ON pm.post_id = p.ID LEFT JOIN
(SELECT order_item_id, order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type='line_item') woi ON woi.order_id = p.ID LEFT JOIN
(SELECT order_item_id, meta_value AS qty FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key='_qty') woim1 ON woim1.order_item_id = woi.order_item_id LEFT JOIN
(SELECT order_item_id, meta_value AS total FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key='_line_total') woim2 ON woim2.order_item_id = woi.order_item_id
WHERE p.post_type='shop_order' AND DATE(p.post_date) BETWEEN %s AND %s AND country != ''
GROUP BY country ORDER BY total DESC, qty DESC LIMIT 25");

define("NWC_API_ORDERS_BY_CITY", "SELECT
    pm.city,
    COUNT(woim1.qty) AS qty,
    SUM(woim2.total) AS total
FROM {$wpdb->prefix}posts p LEFT JOIN
(SELECT post_id, meta_value AS city FROM {$wpdb->prefix}postmeta WHERE meta_key='_billing_city') pm ON pm.post_id = p.ID LEFT JOIN
(SELECT order_item_id, order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type='line_item') woi ON woi.order_id = p.ID LEFT JOIN
(SELECT order_item_id, meta_value AS qty FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key='_qty') woim1 ON woim1.order_item_id = woi.order_item_id LEFT JOIN
(SELECT order_item_id, meta_value AS total FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key='_line_total') woim2 ON woim2.order_item_id = woi.order_item_id
WHERE p.post_type='shop_order' AND DATE(p.post_date) BETWEEN %s AND %s AND city != ''
GROUP BY city ORDER BY total DESC, qty DESC LIMIT 25");

define("NWC_API_NEW_VS_USED_CUSTOMERS", "SELECT
    IFNULL((existing_customers / new_customers * 100), 0) AS percentage
FROM (
    SELECT
        COUNT(*) AS new_customers,
        (SUM(a.addresses) - COUNT(*)) AS existing_customers,
        SUM(a.addresses) AS total_orders
    FROM (
        SELECT
            COUNT(pm1.meta_value) AS addresses
        FROM {$wpdb->prefix}_posts p LEFT JOIN
        (SELECT post_id, meta_value FROM {$wpdb->prefix}_postmeta pm WHERE pm.meta_key='_billing_email') pm1 ON pm1.post_id = p.ID
        WHERE p.post_type='shop_order' AND DATE(p.post_date) BETWEEN %s AND %s
        GROUP BY pm1.meta_value
    ) a
) a");

define("NWC_API_ORDER_SEARCH", "SELECT
    q.name, q.email, q.phone, q.address, q.city, q.country_code, q.postcode
FROM (
    SELECT
        p.ID,
        p.post_date,
        woi.order_items,
        woi.order_value,
        CONCAT_WS(' ',
            (SELECT meta_value FROM {$wpdb->prefix}postmeta pm WHERE p.ID = pm.post_id AND pm.meta_key='_billing_first_name'),
            (SELECT meta_value FROM {$wpdb->prefix}postmeta pm WHERE p.ID = pm.post_id AND pm.meta_key='_billing_last_name')
        ) AS name,
        (SELECT meta_value FROM {$wpdb->prefix}postmeta pm WHERE p.ID = pm.post_id AND pm.meta_key='_billing_email') AS email,
        (SELECT meta_value FROM {$wpdb->prefix}postmeta pm WHERE p.ID = pm.post_id AND pm.meta_key='_billing_phone') AS phone,
        CONCAT_WS(', ',
            (SELECT meta_value FROM {$wpdb->prefix}postmeta pm WHERE p.ID = pm.post_id AND pm.meta_key='_billing_address_1'),
            (SELECT meta_value FROM {$wpdb->prefix}postmeta pm WHERE p.ID = pm.post_id AND pm.meta_key='_billing_address_2')
        ) AS address,
        (SELECT meta_value FROM {$wpdb->prefix}postmeta pm WHERE p.ID = pm.post_id AND pm.meta_key='_billing_city') AS city,
        (SELECT meta_value FROM {$wpdb->prefix}postmeta pm WHERE p.ID = pm.post_id AND pm.meta_key='_billing_state') AS region,
        (SELECT meta_value FROM {$wpdb->prefix}postmeta pm WHERE p.ID = pm.post_id AND pm.meta_key='_billing_country') AS country_code,
        (SELECT meta_value FROM {$wpdb->prefix}postmeta pm WHERE p.ID = pm.post_id AND pm.meta_key='_billing_postcode') AS postcode
    FROM {$wpdb->prefix}posts p
    JOIN (
        SELECT
            woi.order_id,
            SUM((SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta woim WHERE woi.order_item_id = woim.order_item_id AND woim.meta_key='_qty')) AS order_items,
            SUM((SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta woim WHERE woi.order_item_id = woim.order_item_id AND woim.meta_key='_line_total')) AS order_value
        FROM {$wpdb->prefix}woocommerce_order_items woi WHERE woi.order_item_type='line_item'
        GROUP BY woi.order_id
    ) woi ON p.ID = woi.order_id
    WHERE p.post_type='shop_order' ORDER BY p.post_date DESC
) q");
