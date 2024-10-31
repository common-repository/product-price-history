<?php

namespace Devnet\PPH\Modules\Chart;

use Devnet\PPH\Includes\Helper as GlobalHelper;
use Devnet\PPH\Modules\Chart\Chart_Admin;
use Devnet\PPH\Includes\Compatibility\CompatibilityManager;
class Chart_Public {
    private $module = 'chart';

    private $plugin_name;

    private $full_name;

    private $version;

    private $chart_options;

    private $chart_module;

    private $has_data;

    private $multilingual;

    private $pph_data;

    private $localized_data;

    private $wrapper_id = 'pphWrapper';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->full_name = $this->plugin_name . '-' . $this->module;
        $this->multilingual = DEVNET_PPH_OPTIONS['general']['multilingual'] ?? false;
        $this->chart_options = DEVNET_PPH_OPTIONS['chart'];
        $this->chart_module = $this->chart_options['enable_chart'] ?? false;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $name = $this->full_name;
        wp_enqueue_style(
            $this->full_name,
            plugin_dir_url( __DIR__ ) . '../assets/build/public-chart.css',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $name = $this->full_name;
        $script_asset_path = plugin_dir_url( __DIR__ ) . '../assets/build/public-chart.asset.php';
        $script_info = ( file_exists( $script_asset_path ) ? include $script_asset_path : [
            'dependencies' => ['jquery'],
            'version'      => $this->version,
        ] );
        wp_register_script(
            $name,
            plugin_dir_url( __DIR__ ) . '../assets/build/public-chart.js',
            $script_info['dependencies'],
            $script_info['version'],
            true
        );
        $this->localized_data = [
            'ajaxurl'   => admin_url( 'admin-ajax.php' ),
            'wrapperId' => $this->wrapper_id,
            'strings'   => [
                'price'   => esc_html__( 'Price', 'product-price-history' ),
                'date'    => esc_html__( 'Date', 'product-price-history' ),
                'title'   => esc_html__( 'Price History', 'product-price-history' ),
                'lowest'  => esc_html__( 'Lowest:', 'product-price-history' ),
                'highest' => esc_html__( 'Highest:', 'product-price-history' ),
                'average' => esc_html__( 'Average:', 'product-price-history' ),
            ],
        ];
        // Go ahead only if Chart module enabled.
        if ( $this->chart_module ) {
            $this->pph_data = $this->single_product_price_history_data();
            // Go ahead and load scripts only if we found entries.
            if ( !empty( $this->pph_data ) ) {
                $this->has_data = true;
                $this->localized_data['pphData'] = $this->pph_data;
                $this->localized_data['productId'] = get_the_ID();
                wp_enqueue_script( $name );
                wp_localize_script( $name, 'devnet_pph_chart_data', $this->localized_data );
            }
        }
    }

    /**
     * Format pph data for chart.
     *  
     * @since     1.0.0
     */
    public function get_pph_data_by_id( $product_id, $product = null ) {
        $data = [];
        $date_range = $this->chart_options['date_range'] ?? null;
        $date_range = ( $date_range ?: 'all' );
        $range_selector = [];
        $min_prices_to_display = $this->chart_options['min_prices_to_display'] ?? 1;
        $exclude_above_price = $this->chart_options['exclude_above_price'] ?? null;
        $exclude_below_price = $this->chart_options['exclude_below_price'] ?? null;
        $border_color = $this->chart_options['border_color'] ?? null;
        $only_on_variation = $this->chart_options['only_for_variation'] ?? false;
        $text_color = $this->chart_options['text_color'] ?? 'black';
        $type = 'bar';
        $x_axis_label = 'show_all';
        // Default is empty string.
        $currency = apply_filters(
            'pph_price_history_currency',
            '',
            $product_id,
            $product
        );
        $price_history = GlobalHelper::get_price_history( $product_id, [
            'hidden'   => false,
            'currency' => $currency,
        ] );
        $data['chartRanges'] = [
            'default' => $date_range,
        ];
        $found_entries = count( $price_history );
        if ( $found_entries < $min_prices_to_display ) {
            return;
        }
        $data['display'] = true;
        if ( $product && $product->is_type( 'variable' ) && $only_on_variation ) {
            $data['display'] = false;
        }
        if ( $product ) {
            foreach ( $price_history as &$entry ) {
                $entry['regular_price'] = wc_get_price_to_display( $product, [
                    'price' => $entry['regular_price'],
                ] );
                $entry['sale_price'] = wc_get_price_to_display( $product, [
                    'price' => $entry['sale_price'],
                ] );
                $entry['price'] = wc_get_price_to_display( $product, [
                    'price' => $entry['price'],
                ] );
            }
        }
        $data['entries'] = $price_history;
        $data['chartSettings']['border_color'] = $border_color;
        $data['chartSettings']['text_color'] = $text_color;
        $data['chartSettings']['type'] = $type;
        $data['chartSettings']['x_axis_label'] = $x_axis_label;
        return $data;
    }

    /**
     * Get formatted pph data on single product page.
     *  
     * @since     1.0.0
     */
    public function single_product_price_history_data() {
        $data = [];
        if ( function_exists( 'is_product' ) && is_product() ) {
            global $product;
            if ( empty( $product ) || !is_a( $product, 'WC_Product' ) ) {
                $product = wc_get_product( get_the_id() );
            }
            if ( $product->get_meta( '_pph_hide_chart' ) ) {
                return;
            }
            $data = $this->get_pph_data_by_id( $product->get_id(), $product );
        }
        return $data;
    }

    /**
     * output html wrapper where chart will initiate..
     *  
     * @since     1.0.0
     */
    public function price_history_output( $product_id = '' ) {
        if ( $this->chart_module ) {
            $max_width_unit = $this->chart_options['max_width_unit'] ?? '%';
            $bg_color = $this->chart_options['background_color'] ?? 'transparent';
            $max_width = ( isset( $this->chart_options['max_width'] ) ? $this->chart_options['max_width'] . $max_width_unit : '100%' );
            $title = $this->chart_options['title'] ?? Chart_Admin::defaults( 'title' );
            $description = $this->chart_options['description'] ?? Chart_Admin::defaults( 'description' );
            if ( $this->multilingual ) {
                $title = Chart_Admin::defaults( 'title' );
                $description = Chart_Admin::defaults( 'description' );
            }
            $style = '--pph-chart--background-color:' . $bg_color . ';';
            $style .= '--pph-chart--max-width:' . $max_width . ';';
            $wrapper_id = $this->wrapper_id;
            if ( $product_id ) {
                $wrapper_id .= '--' . $product_id;
            }
            $html = '<div id="' . esc_attr( $wrapper_id ) . '" class="pph-wrapper" data-product-id="' . esc_attr( $product_id ) . '" style="' . esc_attr( $style ) . '">';
            if ( trim( $title ) ) {
                $html .= '<span class="pph-chart-title">' . esc_html( $title ) . '</span>';
            }
            $html .= '</div>';
            return $html;
        }
    }

    /**
     * Ajax action for fetching variation price history data.
     *  
     * @since     1.0.0
     */
    public function get_variation_price_history() {
        $variation_id = ( isset( $_GET['args']['id'] ) ? intval( $_GET['args']['id'] ) : null );
        $variation_product = wc_get_product( $variation_id );
        $data = $this->get_pph_data_by_id( $variation_id, $variation_product );
        wp_send_json( $data );
        wp_die();
    }

    /**
     * Ajax action for fetching all variations price history data.
     *  
     * @since     2.5.0
     */
    public function get_all_variations_price_history() {
        $parent_id = ( isset( $_GET['args']['parent_id'] ) ? intval( $_GET['args']['parent_id'] ) : null );
        $args = [
            'parent' => $parent_id,
            'type'   => 'variation',
        ];
        $variations = wc_get_products( $args );
        $output = [];
        foreach ( $variations as $variation ) {
            $variation_id = $variation->get_id();
            $data = $this->get_pph_data_by_id( $variation_id, $variation );
            $output[$variation_id] = $data['entries'] ?? [];
        }
        wp_send_json( $output );
        wp_die();
    }

    /**
     * 
     * @since   2.5.0
     */
    public function echo_price_history_output() {
        echo $this->price_history_output();
    }

}
