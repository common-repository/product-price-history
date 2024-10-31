<?php

namespace Devnet\PPH\Modules\LowestPrice;

use Devnet\PPH\Includes\Helper as GlobalHelper;
use Devnet\PPH\Includes\Compatibility\CompatibilityManager;
class Helper {
    private static $options = DEVNET_PPH_OPTIONS['lowest_price'];

    /**
     * Get product lowest price.
     *
     * @since     1.0.0
     */
    public static function get_lowest_price( $product_id = null, $product = null ) {
        if ( !$product_id || !$product ) {
            return;
        }
        $inherit_regular = self::$options['inherit_regular'] ?? true;
        // Default is empty string.
        $currency = apply_filters(
            'pph_price_history_currency',
            '',
            $product_id,
            $product
        );
        $entries = GlobalHelper::get_price_history( $product_id, [
            'hidden'   => false,
            'currency' => $currency,
        ] );
        $entries_count = count( $entries );
        $lowest_price = self::find_lowest_price( $entries );
        if ( $inherit_regular ) {
            if ( empty( $entries ) || $entries_count === 1 ) {
                if ( $product->get_type() === 'variable' ) {
                    $lowest_price = $product->get_variation_regular_price( 'max', false );
                } else {
                    $lowest_price = $product->get_regular_price();
                }
            }
        }
        return apply_filters(
            'pph_lowest_price',
            $lowest_price,
            $product_id,
            $product
        );
    }

    /**
     * Get product lowest price ranges.
     *
     * @since     1.0.0
     */
    public static function get_variable_product_lowest_price_info( $ids = [] ) {
        $info = [
            'min'        => null,
            'max'        => null,
            'all'        => [],
            'variations' => [],
        ];
        foreach ( $ids as $id ) {
            $product = wc_get_product( $id );
            $lowest_price = self::get_lowest_price( $id, $product );
            $info['all'][] = $lowest_price;
            $info['variations'][$id] = $lowest_price;
        }
        $info['min'] = min( $info['all'] );
        $info['max'] = max( $info['all'] );
        return $info;
    }

    public static function find_lowest_price( $entries = [] ) {
        if ( count( $entries ) < 2 ) {
            return null;
            // Need at least two price changes to perform analysis.
        }
        $last_entry = array_pop( $entries );
        // This is assumed to be the sale price entry.
        // Find the start date for the 30-day period
        $thirty_days_ago = date( 'Y-m-d H:i:s', strtotime( $last_entry['date_created'] . ' -30 days' ) );
        $last_price_before_window = $entries[0]['price'];
        // Default to the first entry's price in case no prices are before the window.
        $lowest_price_within_window = PHP_INT_MAX;
        // use PHP_INT_MAX so later on we can utilize min() function
        foreach ( $entries as $entry ) {
            if ( $entry['date_created'] < $thirty_days_ago ) {
                // Update to the latest price before the window.
                $last_price_before_window = $entry['price'];
            } else {
                if ( $entry['date_created'] >= $thirty_days_ago ) {
                    // Find the lowest price within the window.
                    $lowest_price_within_window = min( $lowest_price_within_window, $entry['price'] );
                }
            }
        }
        // Determine which price to return based on availability of valid price changes.
        $lowest_price = ( $lowest_price_within_window != PHP_INT_MAX ? min( $last_price_before_window, $lowest_price_within_window ) : $last_price_before_window );
        return $lowest_price;
    }

}
