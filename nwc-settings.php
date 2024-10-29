<?php
function nwc_settings($settings)
{
    $updated = [];

    foreach ($settings as $section) {
        // We want this at the bottom of the General Settings Section
        if (isset($section['id']) && 'general_options' == $section['id'] &&
            isset($section['type']) && 'sectionend' == $section['type']) {
            $updated[] = [
                'name' => 'Neatly API Token',
                'desc' => '<br/>This is used to verify you own this WooCommerce store when you authenticate on Neatly, as well as authenticate this store each request thereafter.',
                'desc_tip' => 'Neatly will ask you to enter this information when you authenticate WooCommerce on Neatly\'s website.',
                'id' => 'nwc_api_token',
                'css' => 'width: 350px;',
                'type' => 'text'
            ];
        }

        $updated[] = $section;
    }

    return $updated;
}

add_filter('woocommerce_general_settings', 'nwc_settings');
