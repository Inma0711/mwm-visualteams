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
        $selected_options = array();
        $cromato_olografico_options = array();
        $other_options = array();
        
        // First pass: collect all selected options
        foreach ($addons as $addon) {
            if (isset($cart_item['yith_wapo_options'][$addon->id])) {
                $option_value = $cart_item['yith_wapo_options'][$addon->id];
                $option_price = $this->calculate_option_price($addon, $option_value, $base_price);
                
                $option_data = array(
                    'addon' => $addon,
                    'value' => $option_value,
                    'price' => $option_price,
                    'is_cromato_olografico' => $this->is_cromato_olografico_option($addon, $option_value)
                );
                
                $selected_options[] = $option_data;
                
                if ($option_data['is_cromato_olografico']) {
                    $cromato_olografico_options[] = $option_data;
                } else {
                    $other_options[] = $option_data;
                }
            }
        }
        
        // Check if any option uses total price calculation
        $has_total_calculation = false;
        foreach ($selected_options as $option) {
            if (isset($option['addon']->settings['calculate_on_total']) && $option['addon']->settings['calculate_on_total'] === 'yes') {
                $has_total_calculation = true;
                break;
            }
        }
        
        // If no total calculation is enabled, use normal calculation
        if (!$has_total_calculation) {
            $total_price = $base_price;
            foreach ($selected_options as $option) {
                $total_price += $option['price'];
            }
            $cart->cart_contents[$cart_item_key]['data']->set_price($total_price);
            return;
        }
        
        // Apply the new 70% logic
        $total_price = $this->calculate_with_seventy_percent_logic($base_price, $cromato_olografico_options, $other_options);
        
        // Update cart item price
        $cart->cart_contents[$cart_item_key]['data']->set_price($total_price);
    }
    
    /**
     * Calculate price using the 70% logic
     */
    private function calculate_with_seventy_percent_logic($base_price, $cromato_olografico_options, $other_options) {
        // If no Cromato/Olográfico, use normal calculation
        if (empty($cromato_olografico_options)) {
            $total_price = $base_price;
            foreach ($other_options as $option) {
                $total_price += $option['price'];
            }
            return $total_price;
        }
        
        // If only Cromato/Olográfico (no other options), use normal sum
        if (empty($other_options)) {
            $total_price = $base_price;
            foreach ($cromato_olografico_options as $option) {
                $total_price += $option['price'];
            }
            return $total_price;
        }
        
        // If Cromato/Olográfico + other options, apply 70% logic
        // Step 1: Sum everything (base + Cromato/Olográfico + other options)
        $step1_total = $base_price;
        foreach ($cromato_olografico_options as $option) {
            $step1_total += $option['price'];
        }
        foreach ($other_options as $option) {
            $step1_total += $option['price'];
        }
        
        // Step 2: Subtract Cromato/Olográfico
        $cromato_olografico_total = 0;
        foreach ($cromato_olografico_options as $option) {
            $cromato_olografico_total += $option['price'];
        }
        $step2_without_cromato = $step1_total - $cromato_olografico_total;
        
        // Step 3: Calculate 70% of the result
        $step3_percentage = $step2_without_cromato * 0.70;
        
        // Step 4: Add other options
        $other_options_total = 0;
        foreach ($other_options as $option) {
            $other_options_total += $option['price'];
        }
        $final_total = $step3_percentage + $other_options_total;
        
        return $base_price + $final_total;
    }
    
    /**
     * Get product add-ons from YITH
     */
    private function get_product_addons($product_id) {
        if (!class_exists('YITH_WAPO')) {
            return array();
        }
        
        $addons = array();
        
        try {
            // Method 1: Try to get add-on groups for this product
            if (method_exists(YITH_WAPO(), 'get_addon_groups_by_product')) {
                $addon_groups = YITH_WAPO()->get_addon_groups_by_product($product_id);
                
                if (!empty($addon_groups)) {
                    foreach ($addon_groups as $group) {
                        if (method_exists(YITH_WAPO(), 'get_addons_by_group')) {
                            $group_addons = YITH_WAPO()->get_addons_by_group($group->id);
                            
                            if (!empty($group_addons)) {
                                foreach ($group_addons as $addon) {
                                    $addons[] = $addon;
                                }
                            }
                        }
                    }
                }
            }
            
            // Method 2: Try to get all add-ons and filter by product
            if (empty($addons) && method_exists(YITH_WAPO(), 'get_addons')) {
                $all_addons = YITH_WAPO()->get_addons();
                
                if (!empty($all_addons)) {
                    foreach ($all_addons as $addon) {
                        // Check if this addon applies to this product
                        if ($this->addon_applies_to_product($addon, $product_id)) {
                            $addons[] = $addon;
                        }
                    }
                }
            }
            
            // Method 3: Direct database query as fallback
            if (empty($addons)) {
                global $wpdb;
                $addon_table = $wpdb->prefix . 'yith_wapo_addons';
                
                if ($wpdb->get_var("SHOW TABLES LIKE '$addon_table'") == $addon_table) {
                    $db_addons = $wpdb->get_results("SELECT * FROM $addon_table");
                    
                    if (!empty($db_addons)) {
                        foreach ($db_addons as $db_addon) {
                            // Convert database row to addon object
                            $addon = new stdClass();
                            $addon->id = $db_addon->id;
                            $addon->settings = maybe_unserialize($db_addon->options);
                            $addon->title = $db_addon->title ?? '';
                            $addon->options = maybe_unserialize($db_addon->options);
                            
                            if ($this->addon_applies_to_product($addon, $product_id)) {
                                $addons[] = $addon;
                            }
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            // Log error if needed
            error_log('MWM Visual Teams: Error getting addons - ' . $e->getMessage());
        }
        
        return $addons;
    }
    
    /**
     * Check if an addon applies to a specific product
     */
    private function addon_applies_to_product($addon, $product_id) {
        // Check if addon has product restrictions
        if (isset($addon->settings['products']) && !empty($addon->settings['products'])) {
            $allowed_products = $addon->settings['products'];
            if (!in_array($product_id, $allowed_products)) {
                return false;
            }
        }
        
        // Check if addon has category restrictions
        if (isset($addon->settings['categories']) && !empty($addon->settings['categories'])) {
            $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
            $allowed_categories = $addon->settings['categories'];
            
            $has_matching_category = false;
            foreach ($product_categories as $category_id) {
                if (in_array($category_id, $allowed_categories)) {
                    $has_matching_category = true;
                    break;
                }
            }
            
            if (!$has_matching_category) {
                return false;
            }
        }
        
        // If no restrictions, assume it applies to all products
        return true;
    }
    
    /**
     * Calculate option price
     */
    private function calculate_option_price($addon, $option_value, $base_price) {
        $price = 0;
        
        // Try to get price from addon settings
        if (isset($addon->settings['price'])) {
            $price = floatval($addon->settings['price']);
        }
        
        // Try to get price from options array
        if (isset($addon->options) && is_array($addon->options)) {
            foreach ($addon->options as $option) {
                if (isset($option['label']) && strtolower(trim($option['label'])) === strtolower(trim($option_value))) {
                    if (isset($option['price'])) {
                        $price = floatval($option['price']);
                        break;
                    }
                }
            }
        }
        
        // Try to get price from addon settings options
        if (isset($addon->settings['options']) && is_array($addon->settings['options'])) {
            foreach ($addon->settings['options'] as $option) {
                if (isset($option['label']) && strtolower(trim($option['label'])) === strtolower(trim($option_value))) {
                    if (isset($option['price'])) {
                        $price = floatval($option['price']);
                        break;
                    }
                }
            }
        }
        
        // If still no price found, try to extract from option_value itself
        if ($price == 0 && !empty($option_value)) {
            // Try to extract price from option value (e.g., "Cromato (+238,00 €)")
            if (preg_match('/\([+]?([0-9]+[.,]?[0-9]*)\s*€\)/', $option_value, $matches)) {
                $price = floatval(str_replace(',', '.', $matches[1]));
            }
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
     * Check if an option is Cromato or Olográfico
     */
    private function is_cromato_olografico_option($addon, $option_value) {
        // Check if the option value contains Cromato or Olográfico
        $option_lower = strtolower(trim($option_value));
        if (strpos($option_lower, 'cromato') !== false || strpos($option_lower, 'olografico') !== false) {
            return true;
        }
        
        // Check if the addon title contains Cromato or Olográfico
        if (isset($addon->settings['title'])) {
            $title_lower = strtolower(trim($addon->settings['title']));
            if (strpos($title_lower, 'cromato') !== false || strpos($title_lower, 'olografico') !== false) {
                return true;
            }
        }
        
        // Check if the addon options contain Cromato or Olográfico
        if (isset($addon->settings['options']) && is_array($addon->settings['options'])) {
            foreach ($addon->settings['options'] as $option) {
                if (isset($option['label'])) {
                    $label_lower = strtolower(trim($option['label']));
                    if (strpos($label_lower, 'cromato') !== false || strpos($label_lower, 'olografico') !== false) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get item data for display
     */
    public function get_item_data($item_data, $cart_item) {
        // Add custom data to cart item display if needed
        return $item_data;
    }
} 