<?php
function nwc_verify_apitoken($apitoken)
{
    $nwc_apitoken = get_option('nwc_api_token', null);

    if (is_null($nwc_apitoken) || $apitoken !== $nwc_apitoken) {
        header(' ', true, 403);
        nwc_send_json([
            'error' => 403,
            'message' => 'The API token received from Neatly did not match the token given to the Neatly WooCommerce Extension'
        ]);
    }
}

function nwc_send_json($json)
{
    header('Content-Type: application/json');

    if (is_array($json) || is_object($json)) {
        $json = nwc_hydrate_numbers($json);
    }

    echo json_encode($json, (JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    exit;
}

function nwc_hydrate_numbers($json)
{
    foreach ($json as $key => &$item) {
        if (is_numeric($item) && $key !== 'phone') {
            $item = floatval($item);
        } elseif (is_array($item) || is_object($item)) {
            $item = nwc_hydrate_numbers($item);
        }
    }

    return $json;
}

function nwc_get_dates($start, $end)
{
    $start = date('Y-m-d', strtotime($start));
    $end = date('Y-m-d', strtotime($end));

    $dates[] = $start;
    $current = $start;

    while ($current < $end) {
        $current = date('Y-m-d', strtotime('+1 day', strtotime($current)));
        $dates[] = $current;
    }

    return $dates;
}
