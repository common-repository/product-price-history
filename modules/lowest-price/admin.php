<?php

namespace Devnet\PPH\Modules\LowestPrice;

class LP_Admin
{

    private $module = 'lowest_price';

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }


    /**
     * @since    2.0.0
     */
    public function settings_section($sections)
    {

        $sections[] = [
            'id'    => 'devnet_pph_lowest_price',
            'title' => esc_html__('Lowest Price', 'product-price-history')
        ];

        return $sections;
    }

    /**
     * @since    1.2.0
     */
    public static function defaults($option_name = '')
    {
        $options = [
            'enable_lowest_price'    => 0,
            'only_single'            => 0,
            'only_onsale'            => 1,
            'show_regular_price'     => 1,
            'inherit_regular'        => 1,
            'variable_product_price' => 'range',
            'text'                   => esc_html__('Lowest Price in the last 30 days: {lowest_price}', 'product-price-history'),
        ];

        return $options[$option_name] ?? '';
    }

    /**
     * @since    2.0.0
     */
    public static function settings_fields($fields)
    {
        $fields['devnet_pph_lowest_price'] = [
            [
                'type'    => 'checkbox',
                'name'    => 'enable_lowest_price',
                'label'   => esc_html__('Enable', 'product-price-history'),
                'default' => self::defaults('enable_lowest_price')
            ],
            [
                'type'    => 'checkbox',
                'name'    => 'only_single',
                'label'   => esc_html__('Only on product page', 'product-price-history'),
                'default' => self::defaults('only_single')
            ],
            [
                'type'    => 'checkbox',
                'name'    => 'only_onsale',
                'label'   => esc_html__('Only when product on-sale', 'product-price-history'),
                'default' => self::defaults('only_onsale')
            ],
            [
                'type'    => 'checkbox',
                'name'    => 'show_regular_price',
                'label'   => esc_html__('Show regular price', 'product-price-history'),
                'default' => self::defaults('show_regular_price')
            ],
            [
                'type'    => 'checkbox',
                'name'    => 'inherit_regular',
                'label'   => esc_html__('Inherit from regular price', 'product-price-history'),
                'desc'    => esc_html__('When insufficient price history information is available, the regular price will be displayed as the lowest price.', 'product-price-history'),
                'default' => self::defaults('inherit_regular')
            ],
            [
                'type'    => 'select',
                'name'    => 'variable_product_price',
                'label'   => esc_html__('Variable product price', 'product-price-history'),
                'options' => [
                    'range' => esc_html__('Range (min - max)', 'product-price-history'),
                    'min'   => esc_html__('Min', 'product-price-history'),
                    'max'   => esc_html__('Max', 'product-price-history'),
                    'none'  => esc_html__('Don\'t display', 'product-price-history'),
                ],
                'default' => self::defaults('range')
            ],
            [
                'type'    => 'text',
                'name'    => 'text',
                'label'   => esc_html__('Text', 'product-price-history'),
                'desc'    => esc_html__('Placeholder for lowest price {lowest_price}', 'product-price-history'),
                'default' => self::defaults('text'),
                'sanitize_callback' => 'wp_filter_post_kses'
            ],
        ];

        return $fields;
    }

    /**
     * Output description text under panel title.
     * 
     * @since   2.3.0
     */
    public function panel_description($form)
    {

        $id = $form['id'] ?? '';

        $inner = '';

        $html = '<div class="devnet-plugin-panel-description">';


        if ('devnet_pph_lowest_price' === $id) {

            $text = __("The new Consumer Protection Act officially took effect in European Union (EU) on May 28, 2022, as published in Official Gazette No. 19/2022 The Act aims to enhance consumer protection by addressing unfair practices and modernizing rules. Key changes focus on price display for goods and services, with specific regulations for special forms of sales like special offers, sell-outs, and discounts.<br>
            For special sales, prices must be displayed in two ways: <br>the current price during the sale period and <strong>the lowest price applied for the same goods in the 30 days prior to the sale's initiation.</strong> Traders are allowed to use percentages, percentage values, or cross out the previous price, as long as both the current and lowest prices are clearly presented numerically.<br><br><em>The content of this information is not intended as legal advice and should not be interpreted as such. If you have additional questions or find any aspect unclear, please consult with your regular legal advisor for further clarification.</em>", 'product-price-history');

            $inner .= '<p>';

            $inner .= wp_kses_post($text);

            $inner .= '</p>';

            $html .= $inner;
        }

        $html .= '</div>';

        if (!$inner) {
            $html = '';
        }

        echo $html;
    }
}
