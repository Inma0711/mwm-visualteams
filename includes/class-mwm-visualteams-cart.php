<?php
/**
 * Cart functionality for MWM Visual Teams
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWM_VisualTeams_Cart {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Only hook into WooCommerce if it's active
        if (class_exists('WooCommerce')) {
            // Hook into cart calculations
            add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
            add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 10, 2);
            add_action('woocommerce_before_calculate_totals', array($this, 'calculate_cart_totals'), 10, 1);
        }
    }
    
    /**
     * Add custom data to cart item
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        // Store the original product price
        $product = wc_get_product($product_id);
        if ($product) {
            $cart_item_data['mwm_original_price'] = $product->get_price();
        }
        
        return $cart_item_data;
    }
    
    /**
     * Calculate cart totals with total price calculation
     */
    public function calculate_cart_totals($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $this->calculate_item_total_price($cart_item_key, $cart_item, $cart);
        }
    }
    
    /**
     * Calculate total price for a specific cart item
     */
    private function calculate_item_total_price($cart_item_key, $cart_item, $cart) {
        $product_id = $cart_item['product_id'];
        $variation_id = $cart_item['variation_id'];
        
        // Get YITH add-ons for this product
        $addons = $this->get_product_addons($product_id);
        
        if (empty($addons)) {
            return;
        }
        
        $base_price = $cart_item['mwm_original_price'] ?? $cart_item['data']->get_price();
        $total_price = $base_price;
        $options_with_total_calculation = array();
        
        // First pass: calculate normal add-ons and identify total calculation options
        foreach ($addons as $addon) {
            if (isset($cart_item['yith_wapo_options'][$addon->id])) {
                $option_value = $cart_item['yith_wapo_options'][$addon->id];
                
                // Check if this option uses total price calculation
                if (isset($addon->settings['calculate_on_total']) && $addon->settings['calculate_on_total'] === 'yes') {
                    $options_with_total_calculation[] = array(
                        'addon' => $addon,
                        'value' => $option_value
                    );
                } else {
                    // Normal calculation
                    $option_price = $this->calculate_option_price($addon, $option_value, $base_price);
                    $total_price += $option_price;
                }
            }
        }
        
        // Second pass: calculate total price options
        foreach ($options_with_total_calculation as $option_data) {
            $addon = $option_data['addon'];
            $option_value = $option_data['value'];
            
            // Remove this option's price from total
            $option_price = $this->calculate_option_price($addon, $option_value, $base_price);
            $total_price -= $option_price;
            
            // Calculate percentage on total price
            $percentage = $this->get_option_percentage($addon, $option_value);
            $new_option_price = ($total_price * $percentage) / 100;
            
            // Add new option price to total
            $total_price += $new_option_price;
        }
        
        // Update cart item price
        $cart->cart_contents[$cart_item_key]['data']->set_price($total_price);
    }
    
    /**
     * Get product add-ons from YITH
     */
    private function get_product_addons($product_id) {
        if (!class_exists('YITH_WAPO')) {
            return array();
        }
        
        // This is a placeholder - you'll need to adapt this to your specific YITH setup
        $addons = array();
        
        // Example structure (adapt to your YITH version)
        /*
        $addon_groups = YITH_WAPO()->get_addon_groups_by_product($product_id);
        foreach ($addon_groups as $group) {
            $group_addons = YITH_WAPO()->get_addons_by_group($group->id);
            foreach ($group_addons as $addon) {
                $addons[] = $addon;
            }
        }
        */
        
        return $addons;
    }
    
    /**
     * Calculate option price
     */
    private function calculate_option_price($addon, $option_value, $base_price) {
        // This is a placeholder - adapt to your YITH pricing structure
        $price = 0;
        
        if (isset($addon->settings['price'])) {
            $price = floatval($addon->settings['price']);
        }
        
        return $price;
    }
    
    /**
     * Get option percentage
     */
    private function get_option_percentage($addon, $option_value) {
        // This is a placeholder - adapt to your YITH percentage structure
        $percentage = 0;
        
        if (isset($addon->settings['percentage'])) {
            $percentage = floatval($addon->settings['percentage']);
        }
        
        return $percentage;
    }
    
    /**
     * Get item data for display
     */
    public function get_item_data($item_data, $cart_item) {
        // Add custom data to cart item display if needed
        return $item_data;
    }
} 