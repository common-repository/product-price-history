<?php

namespace Devnet\PPH\Modules\Chart;


class Edit_Product
{


    public function __construct()
    {
    }

    /**
     * Add note to pph tab/panel.
     *  
     * @since     2.3.0
     */
    public function pph_note($count)
    {
        $options = DEVNET_PPH_OPTIONS['chart'];
        $min_prices_to_display = $options['min_prices_to_display'] ?? 0;

        if ($min_prices_to_display > $count) {
            echo '<p class="pph-note">';
            echo sprintf(esc_html__("Note that the Chart won't be visible as you've set Minimum prices to display to %d, and there have been %d recorded price changes since the plugin activation", 'product-price-history'), $min_prices_to_display, $count);
            echo '</p>';
        }
    }


    /**
     * Add options to pph tab/panel.
     *  
     * @since     1.0.0
     */
    public function pph_hide_chart()
    {
        $id = get_the_ID();

        woocommerce_wp_checkbox([
            'id'          => 'pph_hide_chart',
            'value'       => get_post_meta($id, '_pph_hide_chart', true),
            'label'       => esc_html__('Hide chart', 'product-price-history'),
            'desc_tip'    => false,
        ]);
    }


    /**
     * Save product fields.
     *  
     * @since     1.0.0
     */
    public function save_fields($id, $post)
    {

        $hide_chart = isset($_POST['pph_hide_chart']) ? 'yes' : '';
        update_post_meta($id, '_pph_hide_chart', $hide_chart);
    }
}
