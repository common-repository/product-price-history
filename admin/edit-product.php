<?php

namespace Devnet\PPH\Admin;

use Devnet\PPH\Includes\Helper;
class Edit_Product {
    public function __construct() {
    }

    /**
     * Add custom tab to product tabs.
     *  
     * @since     1.0.0
     */
    public function product_data_tab( $tabs ) {
        $tabs['pph_price_history'] = [
            'label'    => esc_html__( 'Product Price History', 'product-price-history' ),
            'target'   => 'pph_price_history',
            'class'    => [],
            'priority' => 100,
        ];
        return $tabs;
    }

    /**
     * Add options to pph tab/panel.
     *  
     * @since     1.0.0
     */
    public function product_data_panels() {
        $id = get_the_ID();
        ?>

        <style>
            li.pph_price_history_tab a:before {
                content: "\f238" !important;
            }
        </style>

        <div id="pph_price_history" class="panel woocommerce_options_panel hidden">

            <?php 
        do_action( 'pph_before_editable_table' );
        ?>

            <?php 
        $this->editable_product_price_history( $id );
        ?>

        </div>

<?php 
    }

    /**
     * Build editable table.
     *  
     * @since     1.0.0
     */
    public function editable_product_price_history( $product_id, $no_entries_notice = true ) {
        $entries = Helper::get_price_history( $product_id );
        if ( !empty( $entries ) ) {
            // Get the date and time formats from the General Settings
            $date_format = get_option( 'date_format' );
            $count = count( $entries );
            do_action( 'pph_before_table_wrapper', $count );
            $limit = 1000;
            echo '<div class="pph-table-wrapper ' . esc_attr( Helper::plan( 'class' ) ) . '">';
            if ( $count > $limit ) {
                $entries = array_slice( $entries, -$limit, $limit );
                echo '<p><small>' . esc_html__( 'To ensure optimal performance, only a limited number of results are shown in the table.', 'product-price-history' ) . '</small></p>';
                printf( '<p>' . esc_html__( 'Displaying %d out of %d results.', 'product-price-history' ) . '</p>', $limit, $count );
            }
            echo '<table class="pph-table">';
            echo '<tr>';
            echo '<th class="short">' . esc_html__( 'Price', 'product-price-history' ) . '</th>';
            echo '<th class="short">' . esc_html__( 'Currency', 'product-price-history' ) . '</th>';
            echo '<th>' . esc_html__( 'Date', 'product-price-history' ) . '</th>';
            echo '<th class="short">' . esc_html__( 'Hide', 'product-price-history' ) . '</th>';
            echo '<th class="short pph-col-actions">' . esc_html__( 'Action', 'product-price-history' ) . '</th>';
            echo '</tr>';
            foreach ( $entries as $entry ) {
                $date_created = $entry['date_created'];
                if ( $date_format ) {
                    // Convert the database date to a timestamp
                    $timestamp = strtotime( $date_created );
                    // Format the date and time according to the settings
                    $formatted_date = date_i18n( $date_format, $timestamp );
                    $formatted_time = date_i18n( 'H:i:s', $timestamp );
                    $date_created = $formatted_date . ' ' . $formatted_time;
                }
                echo '<tr data-id="' . esc_attr( $entry['id'] ) . '">';
                echo '<td><input type="number" name="pph_sale_price" value="' . esc_attr( $entry['price'] ) . '" step="0.01" min="0" disabled /></td>';
                echo '<td><input type="text" name="pph_currency" value="' . esc_attr( $entry['currency'] ) . '" maxlength="3" disabled /></td>';
                echo '<td><input type="text" name="pph_date_created" value="' . esc_attr( $date_created ) . '" data-ymd-date="' . esc_attr( $entry['date_created'] ) . '" data-date-format="' . esc_attr( $date_format ) . '" placeholder="YYYY-MM-DD HH:MM:SS" disabled /></td>';
                echo '<td class="center"><input type="checkbox" name="pph_hidden" value="1" ' . checked( esc_attr( $entry['hidden'] ), true, false ) . ' /></td>';
                echo '<td class="center"><div class="pph-actions"><span class="pph-action-icon pph-edit-entry" title="Edit"></span><span class="pph-action-icon pph-delete-entry" title="Delete"></span></div></td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '<button class="pph-delete-all button" data-id="' . esc_attr( $product_id ) . '">' . esc_html__( 'Delete all entries', 'product-price-history' ) . '</button>';
            echo '</div>';
        } else {
            if ( $no_entries_notice ) {
                echo '<div style="padding: 1rem;">';
                echo esc_html__( 'No pricing data recorded since plugin activation - This message indicates that no pricing data has been saved in the database since the activation of the plugin. This can occur if the plugin was recently installed or if the product price has not changed since the activation of the plugin.', 'product-price-history' );
                echo '</div>';
            }
        }
    }

    /**
     * Output editable table to variation panel.
     *  
     * @since     1.0.0
     */
    public function variation_panel( $loop, $variation_data, $variation ) {
        $this->editable_product_price_history( $variation->ID, false );
    }

    /**
     * Create entry on price change.
     *  
     * @since     1.0.0
     */
    public function update_product( $product, $data_store ) {
        $interval = null;
        Helper::create_entry( $product, $interval );
        do_action( 'pph_after_product_object_save', $product, $interval );
    }

    /**
     * Ajax action for updating editable table fields.
     *  
     * @since     1.0.0
     */
    public function update_pph_db_row() {
        check_ajax_referer( 'pph-nonce', 'security' );
        $id = ( isset( $_POST['args']['id'] ) ? intval( $_POST['args']['id'] ) : null );
        $data = [];
        if ( isset( $_POST['args']['hidden'] ) ) {
            $data['hidden'] = absint( $_POST['args']['hidden'] ) || 0;
        }
        $response = Helper::update_entry( $id, $data );
        wp_send_json( $response );
        wp_die();
    }

    /**
     * Ajax action for deleting all product entries.
     *  
     * @since     2.1.0
     */
    public function delete_pph_product_entries() {
        check_ajax_referer( 'pph-nonce', 'security' );
        $id = ( isset( $_POST['args']['id'] ) ? intval( $_POST['args']['id'] ) : null );
        $delete = Helper::delete_product_entries( $id );
        wp_send_json( $delete );
        wp_die();
    }

    /**
     * Ajax action for deleting single product entry.
     *  
     * @since     2.1.0
     */
    public function delete_pph_entry() {
        check_ajax_referer( 'pph-nonce', 'security' );
        $id = ( isset( $_POST['args']['id'] ) ? intval( $_POST['args']['id'] ) : null );
        $delete = Helper::delete_entry( $id );
        wp_send_json( $delete );
        wp_die();
    }

    /**
     * When deleting the product or variation, delete also entries from pph table.
     *  
     * @since     1.0.0
     */
    public function remove_deleted_products_from_pph_table( $post_id, $post ) {
        if ( $post->post_type === 'product' || $post->post_type === 'product_variation' ) {
            Helper::delete_product_entries( $post_id );
        }
    }

}
