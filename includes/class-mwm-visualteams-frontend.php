<?php
/**
 * Frontend functionality for MWM Visual Teams
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWM_VisualTeams_Frontend {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Only hook into WooCommerce if it's active
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_after_add_to_cart_button', array($this, 'add_frontend_scripts'));
        }
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        // Always load basic scripts
        wp_enqueue_script(
            'mwm-visualteams-frontend',
            MWM_VISUALTEAMS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            MWM_VISUALTEAMS_VERSION,
            true
        );
        
        // Only load WooCommerce specific data if WooCommerce is active
        if (class_exists('WooCommerce') && is_product()) {
            wp_localize_script('mwm-visualteams-frontend', 'mwm_visualteams', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mwm_visualteams_nonce'),
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'decimal_separator' => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'decimals' => wc_get_price_decimals()
            ));
        }
    }
    
    /**
     * Add frontend scripts to product page
     */
    public function add_frontend_scripts() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize total price calculation
            MWMVisualTeams.init();
        });
        </script>
        <?php
    }
} 