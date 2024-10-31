<?php

namespace Devnet\PPH\Modules\Chart;

class Chart_Admin {
    private $module = 'chart';

    private $plugin_name;

    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Format range_selector options for select2 picker.
     *
     * @since    2.0.0
     */
    static function formatted_range_selector_options() {
        $formatted_options = [];
        $option = DEVNET_PPH_OPTIONS['chart'];
        $selected = ( isset( $option['range_selector'] ) ? array_flip( $option['range_selector'] ) : [] );
        $options = [
            'all'       => esc_html__( 'All', 'product-price-history' ),
            '7_days'    => esc_html__( 'Last 7 days', 'product-price-history' ),
            '30_days'   => esc_html__( 'Last 30 days', 'product-price-history' ),
            '3_months'  => esc_html__( 'Last 3 months', 'product-price-history' ),
            '6_months'  => esc_html__( 'Last 6 months', 'product-price-history' ),
            '12_months' => esc_html__( 'Last 12 months', 'product-price-history' ),
        ];
        foreach ( $options as $key => $label ) {
            $formatted_options[] = [
                'id'       => $key,
                'text'     => $label,
                'selected' => isset( $selected[$key] ),
            ];
        }
        return $formatted_options;
    }

    /**
     * @since    1.2.0
     */
    public static function defaults( $option_name = '' ) {
        $options = [
            'enable_chart'          => 0,
            'position'              => 'woocommerce_product_meta_start',
            'tab_title'             => esc_html__( 'Price History', 'product-price-history' ),
            'tab_priority'          => 100,
            'date_range'            => 'all',
            'range_selector'        => '',
            'min_prices_to_display' => 2,
            'exclude_above_price'   => '',
            'exclude_below_price'   => '',
            'only_for_variation'    => 0,
            'title'                 => esc_html_x( 'Price History', 'Chart title', 'product-price-history' ),
            'description'           => esc_html_x( ' ', 'Chart description', 'product-price-history' ),
            'daily_average'         => 0,
            'type'                  => 'bar',
            'border_color'          => 'rgba(130,36,227,0.5)',
            'text_color'            => 'rgba(0,0,0,1)',
            'background_color'      => 'rgba(255,255,255,0)',
            'max_width'             => '100',
            'x_axis_label'          => 'show_all',
            'summary_stats'         => '',
        ];
        return $options[$option_name] ?? '';
    }

    /**
     * @since    2.0.0
     */
    public function settings_section( $sections ) {
        $sections[] = [
            'id'    => 'devnet_pph_chart',
            'title' => esc_html__( 'Chart', 'product-price-history' ),
        ];
        return $sections;
    }

    /**
     * @since    2.0.0
     */
    public static function settings_fields( $fields ) {
        $chart = [
            [
                'type'    => 'checkbox',
                'name'    => 'enable_chart',
                'label'   => esc_html__( 'Enable', 'product-price-history' ),
                'default' => self::defaults( 'enable_chart' ),
            ],
            [
                'type'    => 'select',
                'name'    => 'position',
                'label'   => esc_html__( 'Position', 'product-price-history' ),
                'options' => [
                    'woocommerce_product_meta_start'           => esc_html__( 'Before product meta', 'product-price-history' ),
                    'woocommerce_after_single_product_summary' => esc_html__( 'After product summary', 'product-price-history' ),
                    '_disabled_1'                              => esc_html__( 'Product tab', 'product-price-history' ),
                    '_disabled_2'                              => esc_html__( 'Custom - I\'ll insert a shortcode', 'product-price-history' ),
                ],
                'default' => self::defaults( 'position' ),
            ],
            [
                'type'    => 'text',
                'name'    => 'shortcode_info__disabled',
                'label'   => esc_html__( 'Shortcode', 'product-price-history' ),
                'desc'    => esc_html( 'Copy the shortcode and integrate it into your site using your preferred editor.', 'product-price-history' ),
                'default' => '[pph-chart]',
            ],
            [
                'type'              => 'text',
                'name'              => 'tab_title__disabled',
                'label'             => esc_html__( 'Tab title', 'product-price-history' ),
                'default'           => self::defaults( 'tab_title' ),
                'sanitize_callback' => 'sanitize_text_field',
            ],
            [
                'type'              => 'number',
                'name'              => 'tab_priority__disabled',
                'label'             => esc_html__( 'Tab priority', 'product-price-history' ),
                'desc'              => esc_html__( 'Lower number mean higher priority, determining its position among other tabs.', 'product-price-history' ),
                'step'              => '1',
                'default'           => self::defaults( 'tab_priority' ),
                'sanitize_callback' => 'absint',
            ],
            [
                'type'    => 'select',
                'name'    => 'date_range',
                'label'   => esc_html__( 'Date range', 'product-price-history' ),
                'options' => [
                    'all'       => esc_html__( 'All', 'product-price-history' ),
                    '7_days'    => esc_html__( 'Last 7 days', 'product-price-history' ),
                    '30_days'   => esc_html__( 'Last 30 days', 'product-price-history' ),
                    '3_months'  => esc_html__( 'Last 3 months', 'product-price-history' ),
                    '6_months'  => esc_html__( 'Last 6 months', 'product-price-history' ),
                    '12_months' => esc_html__( 'Last 12 months', 'product-price-history' ),
                ],
                'default' => self::defaults( 'date_range' ),
            ],
            [
                'type'    => 'select2',
                'name'    => 'range_selector__disabled',
                'label'   => esc_html__( 'Range selector', 'product-price-history' ),
                'options' => [],
                'default' => self::defaults( 'range_selector' ),
            ],
            [
                'type'              => 'number',
                'name'              => 'min_prices_to_display',
                'label'             => esc_html__( 'Minimum prices to display', 'product-price-history' ),
                'step'              => '1',
                'default'           => self::defaults( 'min_prices_to_display' ),
                'sanitize_callback' => 'absint',
            ],
            [
                'type'    => 'number',
                'name'    => 'exclude_above_price__disabled',
                'label'   => esc_html__( 'Exclude prices above', 'product-price-history' ),
                'step'    => '0.01',
                'desc'    => esc_html__( 'Enter a price above which you want to exclude items from the chart.', 'product-price-history' ),
                'default' => self::defaults( 'exclude_above_price' ),
            ],
            [
                'type'    => 'number',
                'name'    => 'exclude_below_price__disabled',
                'label'   => esc_html__( 'Exclude prices below', 'product-price-history' ),
                'step'    => '0.01',
                'desc'    => esc_html__( 'Enter a price below which you want to exclude items from the chart.', 'product-price-history' ),
                'default' => self::defaults( 'exclude_below_price' ),
            ],
            [
                'type'    => 'checkbox',
                'name'    => 'daily_average__disabled',
                'label'   => esc_html__( 'Daily average price', 'product-price-history' ),
                'desc'    => esc_html__( 'Helpful when there are frequent price fluctuations within a single day', 'product-price-history' ),
                'default' => self::defaults( 'daily_average' ),
            ],
            [
                'type'    => 'checkbox',
                'name'    => 'only_for_variation',
                'label'   => esc_html__( 'Show chart on variable products only when variation is selected', 'product-price-history' ),
                'default' => self::defaults( 'only_for_variation' ),
            ],
            [
                'type'    => 'select',
                'name'    => 'chart_type',
                'label'   => esc_html__( 'Chart type', 'product-price-history' ),
                'options' => [
                    'bar'         => esc_html__( 'Bar', 'product-price-history' ),
                    '_disabled_1' => esc_html__( 'Stepped', 'product-price-history' ),
                    '_disabled_2' => esc_html__( 'Line', 'product-price-history' ),
                ],
                'default' => self::defaults( 'chart_type' ),
            ],
            [
                'type'              => 'text',
                'name'              => 'title',
                'label'             => esc_html__( 'Title', 'product-price-history' ),
                'default'           => self::defaults( 'title' ),
                'sanitize_callback' => 'sanitize_text_field',
            ],
            [
                'type'              => 'textarea',
                'name'              => 'description__disabled',
                'label'             => esc_html__( 'Description', 'product-price-history' ),
                'default'           => self::defaults( 'description' ),
                'sanitize_callback' => 'sanitize_textarea_field',
            ],
            [
                'type'     => 'select',
                'name'     => 'summary_stats',
                'label'    => esc_html__( 'Summary statistics display', 'product-price-history' ),
                'options'  => [
                    '' => esc_html__( "Don't display", 'product-price-history' ),
                ],
                'optgroup' => [[
                    'label'    => esc_html__( '-- Available in ADVANCED plan --', 'product-price-history' ),
                    'options'  => [
                        '1' => esc_html__( 'Show all', 'product-price-history' ),
                        '2' => esc_html__( 'Show only lowest and highest', 'product-price-history' ),
                        '3' => esc_html__( 'Show only average', 'product-price-history' ),
                    ],
                    'disabled' => true,
                ]],
                'desc'     => esc_html__( 'Control the visibility of summary statistics (Lowest, Highest, and Average values) displayed above the chart.', 'product-price-history' ),
                'default'  => self::defaults( 'summary_stats' ),
            ],
            [
                'type'    => 'color',
                'name'    => 'border_color',
                'label'   => esc_html__( 'Graph border color', 'product-price-history' ),
                'default' => self::defaults( 'border_color' ),
            ],
            [
                'type'              => 'number',
                'name'              => 'max_width',
                'label'             => esc_html__( 'Chart maximal width', 'product-price-history' ),
                'min'               => 0,
                'step'              => '1',
                'default'           => self::defaults( 'max_width' ),
                'sanitize_callback' => 'absint',
            ],
            [
                'type'    => 'select',
                'name'    => 'max_width_unit',
                'label'   => esc_html__( '', 'product-price-history' ),
                'options' => [
                    '%'   => '%',
                    'px'  => 'px',
                    'em'  => 'em',
                    'rem' => 'rem',
                    'vw'  => 'vw',
                    'vh'  => 'vh',
                ],
                'class'   => 'attach-units-to--max_width',
                'default' => '%',
            ],
            [
                'type'    => 'color',
                'name'    => 'text_color',
                'label'   => esc_html__( 'Chart text color', 'product-price-history' ),
                'default' => self::defaults( 'text_color' ),
            ],
            [
                'type'    => 'color',
                'name'    => 'background_color',
                'label'   => esc_html__( 'Chart background color', 'product-price-history' ),
                'default' => self::defaults( 'background_color' ),
            ],
            [
                'type'     => 'select',
                'name'     => 'x_axis_label',
                'label'    => esc_html__( 'X-Axis label options', 'product-price-history' ),
                'options'  => [
                    'show_all' => esc_html__( 'Show all', 'product-price-history' ),
                ],
                'optgroup' => [[
                    'label'    => esc_html__( '-- Available in ADVANCED plan --', 'product-price-history' ),
                    'options'  => [
                        '1' => esc_html__( 'Hide dates', 'product-price-history' ),
                        '2' => esc_html__( 'Hide dates and label', 'product-price-history' ),
                        '3' => esc_html__( 'Show 3 dates', 'product-price-history' ),
                        '4' => esc_html__( 'Show 5 dates', 'product-price-history' ),
                        '5' => esc_html__( 'Show 7 dates', 'product-price-history' ),
                        '6' => esc_html__( 'Show 10 dates', 'product-price-history' ),
                    ],
                    'disabled' => true,
                ]],
                'desc'     => esc_html__( 'Control how dates are displayed on the X-axis of the chart without reducing the actual data points. This is useful for charts with many data points to avoid clutter. Even when dates are hidden, they will still appear when hovering over specific points in the chart.', 'product-price-history' ),
                'default'  => self::defaults( 'x_axis_label' ),
            ]
        ];
        $fields['devnet_pph_chart'] = $chart;
        return $fields;
    }

}
