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
        error_log('MWM DEBUG: MWM_VisualTeams_Cart init() called');
        
        // Only hook into WooCommerce if it's active
        if (class_exists('WooCommerce')) {
            error_log('MWM DEBUG: WooCommerce is active, registering filters');
            
            // Hook into YITH WAPO total calculation to modify percentage calculations on total price
            add_filter('yith_wapo_get_total_by_add_ons_list', array($this, 'modify_yith_total_by_add_ons_list'), 10, 3);
            
            // Also hook into other YITH WAPO filters to debug
            add_filter('yith_wapo_calculate_addon_price', array($this, 'debug_yith_addon_price'), 5, 4);
            add_filter('yith_wapo_calculate_addon_price_on_cart', array($this, 'debug_yith_addon_price_cart'), 5, 4);
            
            // Hook into cart calculation to modify prices
            add_action('woocommerce_before_calculate_totals', array($this, 'modify_cart_totals'), 10, 1);
            
            // Hook into cart item name to modify addon descriptions
            add_filter('woocommerce_cart_item_name', array($this, 'modify_cart_item_name'), 10, 3);
            
            // Hook into cart item data for WooCommerce blocks
            add_filter('woocommerce_get_item_data', array($this, 'modify_cart_item_data'), 10, 2);
            
            
            // Filter to modify addon display in cart
            add_filter('yith_wapo_addon_display_on_cart', array($this, 'modify_addon_display_in_cart'), 10, 8);
            
            // Hook into cart to show product metadata
            add_action('woocommerce_cart_loaded_from_session', array($this, 'debug_cart_metadata'));
            add_action('woocommerce_after_cart_item_name', array($this, 'show_cart_item_metadata'), 10, 2);
            
            error_log('MWM DEBUG: All filters registered');
        } else {
            error_log('MWM DEBUG: WooCommerce not active');
        }
    }
    
    /**
     * Debug function to see if YITH addon price calculation is being called
     */
    public function debug_yith_addon_price($price, $addon, $option, $product) {
        error_log('MWM DEBUG: yith_wapo_calculate_addon_price called - Addon: ' . (isset($addon->id) ? $addon->id : 'unknown') . ', Price: ' . $price);
        return $price;
    }
    
    /**
     * Debug function to see if YITH addon price calculation on cart is being called
     */
    public function debug_yith_addon_price_cart($price, $addon, $option, $cart_item) {
        error_log('MWM DEBUG: yith_wapo_calculate_addon_price_on_cart called - Addon: ' . (isset($addon->id) ? $addon->id : 'unknown') . ', Price: ' . $price);
        return $price;
    }
    
    /**
     * Debug cart metadata when cart is loaded
     */
    public function debug_cart_metadata() {
        if (!WC()->cart || WC()->cart->is_empty()) {
            error_log('MWM DEBUG: Cart is empty');
            return;
        }
        
        error_log('MWM DEBUG: === CART METADATA DEBUG ===');
        error_log('MWM DEBUG: Cart items count: ' . WC()->cart->get_cart_contents_count());
        
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            error_log('MWM DEBUG: --- Cart Item: ' . $cart_item_key . ' ---');
            error_log('MWM DEBUG: Product ID: ' . $cart_item['product_id']);
            error_log('MWM DEBUG: Product Name: ' . $cart_item['data']->get_name());
            error_log('MWM DEBUG: Quantity: ' . $cart_item['quantity']);
            error_log('MWM DEBUG: Price: ' . $cart_item['data']->get_price());
            error_log('MWM DEBUG: Line Total: ' . $cart_item['line_total']);
            error_log('MWM DEBUG: Line Subtotal: ' . $cart_item['line_subtotal']);
            
            // Show all cart item data
            error_log('MWM DEBUG: All cart item data: ' . print_r($cart_item, true));
            
            // Show YITH WAPO specific data
            if (isset($cart_item['yith_wapo_options'])) {
                error_log('MWM DEBUG: YITH WAPO Options: ' . print_r($cart_item['yith_wapo_options'], true));
            }
            
            if (isset($cart_item['yith_wapo_total_options_price'])) {
                error_log('MWM DEBUG: YITH WAPO Total Options Price: ' . $cart_item['yith_wapo_total_options_price']);
            }
            
            // Show product metadata
            $product_meta = get_post_meta($cart_item['product_id']);
            error_log('MWM DEBUG: Product Meta: ' . print_r($product_meta, true));
            
            // Check calculate_on_total options for all addons in this product
            if (isset($cart_item['yith_wapo_options'])) {
                error_log('MWM DEBUG: === CHECKING CALCULATE_ON_TOTAL OPTIONS ===');
                foreach ($cart_item['yith_wapo_options'] as $option_group) {
                    foreach ($option_group as $key => $value) {
                        if ($key && '' !== $value) {
                            $values = YITH_WAPO::get_instance()->split_addon_and_option_ids($key, $value);
                            $addon_id = $values['addon_id'];
                            $option_name = 'mwm_calculate_on_total_' . $addon_id;
                            $calculate_on_total = get_option($option_name, '');
                            error_log('MWM DEBUG: Addon ' . $addon_id . ' (' . $key . '): ' . $calculate_on_total);
                        }
                    }
                }
                error_log('MWM DEBUG: === END CALCULATE_ON_TOTAL CHECK ===');
            }
        }
        
        error_log('MWM DEBUG: === END CART METADATA DEBUG ===');
    }
    
    
    /**
     * Show cart item metadata in the cart page
     */
    public function show_cart_item_metadata($cart_item, $cart_item_key) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        echo '<div style="background: #f0f0f0; padding: 10px; margin: 5px 0; border: 1px solid #ccc; font-size: 12px;">';
        echo '<strong>MWM Debug - Cart Item Metadata:</strong><br>';
        echo '<strong>Product ID:</strong> ' . $cart_item['product_id'] . '<br>';
        echo '<strong>Quantity:</strong> ' . $cart_item['quantity'] . '<br>';
        echo '<strong>Price:</strong> ' . $cart_item['data']->get_price() . '<br>';
        echo '<strong>Line Total:</strong> ' . $cart_item['line_total'] . '<br>';
        
        if (isset($cart_item['yith_wapo_options'])) {
            echo '<strong>YITH WAPO Options:</strong> ' . print_r($cart_item['yith_wapo_options'], true) . '<br>';
        }
        
        if (isset($cart_item['yith_wapo_total_options_price'])) {
            echo '<strong>YITH WAPO Total Options Price:</strong> ' . $cart_item['yith_wapo_total_options_price'] . '<br>';
        }
        
        echo '</div>';
    }
    
    /**
     * Modify cart item name to show correct addon prices
     */
    public function modify_cart_item_name($product_name, $cart_item, $cart_item_key) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return $product_name;
        }
        
        // Only modify if this item has YITH WAPO options
        if (!isset($cart_item['yith_wapo_options']) || empty($cart_item['yith_wapo_options'])) {
            return $product_name;
        }
        
        error_log('MWM DEBUG: modify_cart_item_name called for: ' . $product_name);
        
        // Get the original product name without addon descriptions
        $base_product_name = $cart_item['data']->get_name();
        
        // Build new description with corrected prices
        $new_description = $base_product_name;
        $addon_descriptions = array();
        
        // Get current cart item to access our calculated prices
        $current_cart_item = null;
        if (WC()->cart) {
            foreach (WC()->cart->get_cart() as $key => $item) {
                if ($key === $cart_item_key) {
                    $current_cart_item = $item;
                    break;
                }
            }
        }
        
        if (!$current_cart_item) {
            return $product_name;
        }
        
        // Calculate prices for each addon
        $base_price = $current_cart_item['yith_wapo_item_price'];
        $normal_addons_price = 0;
        $normal_addons = array();
        $total_calculation_addons = array();
        
        // First pass: separate addons and calculate normal ones
        foreach ($current_cart_item['yith_wapo_options'] as $option_group) {
            foreach ($option_group as $key => $value) {
                if ($key && '' !== $value) {
                    $values = YITH_WAPO::get_instance()->split_addon_and_option_ids($key, $value);
                    $addon_id = $values['addon_id'];
                    $option_id = $values['option_id'];
                    
                    $option_name = 'mwm_calculate_on_total_' . $addon_id;
                    $calculate_on_total = get_option($option_name, '');
                    
                    $addon_data = array(
                        'key' => $key,
                        'value' => $value,
                        'addon_id' => $addon_id,
                        'option_id' => $option_id
                    );
                    
                    if ($calculate_on_total === 'yes') {
                        $total_calculation_addons[] = $addon_data;
                    } else {
                        $normal_addons[] = $addon_data;
                        $option_price = $this->calculate_addon_price_for_total($addon_data, $base_price, $current_cart_item);
                        $normal_addons_price += $option_price;
                    }
                }
            }
        }
        
        // Calculate total for total calculation addons
        $normal_total = $base_price + $normal_addons_price;
        $total_calculation_addons_price = 0;
        
        foreach ($total_calculation_addons as $addon_data) {
            $option_price = $this->calculate_addon_price_for_total($addon_data, $normal_total, $current_cart_item);
            $total_calculation_addons_price += $option_price;
        }
        
        // Now build descriptions with correct prices
        foreach ($current_cart_item['yith_wapo_options'] as $option_group) {
            foreach ($option_group as $key => $value) {
                if ($key && '' !== $value) {
                    $values = YITH_WAPO::get_instance()->split_addon_and_option_ids($key, $value);
                    $addon_id = $values['addon_id'];
                    $option_id = $values['option_id'];
                    
                    $option_name = 'mwm_calculate_on_total_' . $addon_id;
                    $calculate_on_total = get_option($option_name, '');
                    
                    $addon_data = array(
                        'key' => $key,
                        'value' => $value,
                        'addon_id' => $addon_id,
                        'option_id' => $option_id
                    );
                    
                    // Calculate the correct price for this addon
                    if ($calculate_on_total === 'yes') {
                        $option_price = $this->calculate_addon_price_for_total($addon_data, $normal_total, $current_cart_item);
                    } else {
                        $option_price = $this->calculate_addon_price_for_total($addon_data, $base_price, $current_cart_item);
                    }
                    
                    // Get addon info for display
                    $info = yith_wapo_get_option_info($addon_id, $option_id);
                    $addon_title = isset($info['addon_title']) ? $info['addon_title'] : 'Addon ' . $addon_id;
                    $option_label = isset($info['label']) ? $info['label'] : $value;
                    
                    // Format the price
                    $formatted_price = wc_price($option_price);
                    $addon_descriptions[] = $addon_title . ': ' . $option_label . ' (' . $formatted_price . ')';
                    
                    error_log('MWM DEBUG: Addon ' . $addon_id . ' - Title: ' . $addon_title . ', Label: ' . $option_label . ', Price: ' . $option_price);
                }
            }
        }
        
        // Combine product name with addon descriptions
        if (!empty($addon_descriptions)) {
            $new_description .= '<br><small>' . implode('<br>', $addon_descriptions) . '</small>';
        }
        
        error_log('MWM DEBUG: New description: ' . $new_description);
        
        return $new_description;
    }
    
    /**
     * Modify cart item data for WooCommerce blocks
     */
    public function modify_cart_item_data($item_data, $cart_item) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return $item_data;
        }
        
        // Only modify if this item has YITH WAPO options
        if (!isset($cart_item['yith_wapo_options']) || empty($cart_item['yith_wapo_options'])) {
            return $item_data;
        }
        
        error_log('MWM DEBUG: modify_cart_item_data called');
        
        // Calculate prices for each addon
        $base_price = $cart_item['yith_wapo_item_price'];
        $normal_addons_price = 0;
        $normal_addons = array();
        $total_calculation_addons = array();
        
        // First pass: separate addons and calculate normal ones
        foreach ($cart_item['yith_wapo_options'] as $option_group) {
            foreach ($option_group as $key => $value) {
                if ($key && '' !== $value) {
                    $values = YITH_WAPO::get_instance()->split_addon_and_option_ids($key, $value);
                    $addon_id = $values['addon_id'];
                    $option_id = $values['option_id'];
                    
                    $option_name = 'mwm_calculate_on_total_' . $addon_id;
                    $calculate_on_total = get_option($option_name, '');
                    
                    $addon_data = array(
                        'key' => $key,
                        'value' => $value,
                        'addon_id' => $addon_id,
                        'option_id' => $option_id
                    );
                    
                    if ($calculate_on_total === 'yes') {
                        $total_calculation_addons[] = $addon_data;
                    } else {
                        $normal_addons[] = $addon_data;
                        $option_price = $this->calculate_addon_price_for_total($addon_data, $base_price, $cart_item);
                        $normal_addons_price += $option_price;
                    }
                }
            }
        }
        
        // Calculate total for total calculation addons
        $normal_total = $base_price + $normal_addons_price;
        
        // Debug: Show all item_data
        error_log('MWM DEBUG: item_data: ' . print_r($item_data, true));
        
        // Now modify the item_data with correct prices
        foreach ($item_data as $index => $data) {
            if (isset($data['name']) && isset($data['value'])) {
                $name = $data['name'];
                $value = $data['value'];
                
                error_log('MWM DEBUG: Processing item_data - Name: ' . $name . ', Value: ' . $value);
                
                // Check if this is a YITH WAPO addon with price
                if (strpos($value, '(+') !== false && strpos($value, '€)') !== false) {
                    error_log('MWM DEBUG: Found addon with price: ' . $value);
                    // Extract addon info from the value
                    $addon_id = null;
                    $option_id = null;
                    
                    // Try to find the addon ID from the item_data key or value
                    foreach ($cart_item['yith_wapo_options'] as $option_group) {
                        foreach ($option_group as $key => $val) {
                            if ($key && '' !== $val) {
                                $values = YITH_WAPO::get_instance()->split_addon_and_option_ids($key, $val);
                                $addon_id = $values['addon_id'];
                                $option_id = $values['option_id'];
                                
                                // Get addon info to match with display
                                $info = yith_wapo_get_option_info($addon_id, $option_id);
                                $option_label = isset($info['label']) ? $info['label'] : $val;
                                
                                error_log('MWM DEBUG: Checking addon ' . $addon_id . ' - Label: ' . $option_label . ', Value: ' . $val);
                                
                                // Check if this matches the current item_data value
                                if (strpos($value, $option_label) !== false) {
                                    error_log('MWM DEBUG: Found matching addon: ' . $addon_id . ' with label: ' . $option_label);
                                    break 2;
                                }
                            }
                        }
                    }
                    
                    if ($addon_id) {
                        $option_name = 'mwm_calculate_on_total_' . $addon_id;
                        $calculate_on_total = get_option($option_name, '');
                        
                        $addon_data = array(
                            'key' => $addon_id . '-' . $option_id,
                            'value' => $value,
                            'addon_id' => $addon_id,
                            'option_id' => $option_id
                        );
                        
                        // Calculate the correct price for this addon
                        if ($calculate_on_total === 'yes') {
                            $option_price = $this->calculate_addon_price_for_total($addon_data, $normal_total, $cart_item);
                        } else {
                            $option_price = $this->calculate_addon_price_for_total($addon_data, $base_price, $cart_item);
                        }
                        
                        // Format the new value with correct price
                        $formatted_price = wc_price($option_price);
                        $new_value = str_replace('(+' . preg_replace('/[^0-9,.]/', '', $value) . '€)', '(' . $formatted_price . ')', $value);
                        
                        $item_data[$index]['value'] = $new_value;
                        
                        error_log('MWM DEBUG: Modified addon ' . $addon_id . ' - Original: ' . $value . ', New: ' . $new_value . ', Price: ' . $option_price);
                    }
                }
            }
        }
        
        return $item_data;
    }
    
    /**
     * Modify cart totals to handle calculate_on_total addons
     */
    public function modify_cart_totals($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        error_log('MWM DEBUG: modify_cart_totals called');
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['yith_wapo_options']) && !empty($cart_item['yith_wapo_options'])) {
                error_log('MWM DEBUG: Processing cart item with YITH options: ' . $cart_item_key);
                
                // Check if this item has addons with calculate_on_total
                $has_calculate_on_total = false;
                $total_calculation_addons = array();
                $normal_addons = array();
                
                foreach ($cart_item['yith_wapo_options'] as $option_group) {
                    foreach ($option_group as $key => $value) {
                        if ($key && '' !== $value) {
                            $values = YITH_WAPO::get_instance()->split_addon_and_option_ids($key, $value);
                            $addon_id = $values['addon_id'];
                            $option_id = $values['option_id'];
                            
                            $option_name = 'mwm_calculate_on_total_' . $addon_id;
                            $calculate_on_total = get_option($option_name, '');
                            
                            $addon_data = array(
                                'key' => $key,
                                'value' => $value,
                                'addon_id' => $addon_id,
                                'option_id' => $option_id
                            );
                            
                            if ($calculate_on_total === 'yes') {
                                $has_calculate_on_total = true;
                                $total_calculation_addons[] = $addon_data;
                                error_log('MWM DEBUG: Found addon with calculate_on_total: ' . $addon_id);
                            } else {
                                $normal_addons[] = $addon_data;
                            }
                        }
                    }
                }
                
                if ($has_calculate_on_total) {
                    error_log('MWM DEBUG: Recalculating prices for item with calculate_on_total addons');
                    $this->recalculate_cart_item_with_total_calculation($cart_item_key, $cart_item, $cart, $normal_addons, $total_calculation_addons);
                }
            }
        }
    }
    
    /**
     * Recalculate cart item with total calculation addons
     */
    private function recalculate_cart_item_with_total_calculation($cart_item_key, $cart_item, $cart, $normal_addons, $total_calculation_addons) {
        $product_id = $cart_item['product_id'];
        $base_price = $cart_item['yith_wapo_item_price']; // Use the base price from YITH
        
        error_log('MWM DEBUG: Recalculating - Product: ' . $product_id . ', Base price: ' . $base_price);
        
        // Calculate normal addons first
        $normal_total = $base_price;
        $normal_addons_price = 0;
        
        foreach ($normal_addons as $addon_data) {
            $option_price = $this->calculate_addon_price_for_total($addon_data, $base_price, $cart_item);
            $normal_total += $option_price;
            $normal_addons_price += $option_price;
        }
        
        error_log('MWM DEBUG: Normal total (base + normal addons): ' . $normal_total);
        error_log('MWM DEBUG: Normal addons price: ' . $normal_addons_price);
        
        // Calculate total calculation addons using the normal total as base
        $total_calculation_addons_price = 0;
        foreach ($total_calculation_addons as $addon_data) {
            $option_price = $this->calculate_addon_price_for_total($addon_data, $normal_total, $cart_item);
            $total_calculation_addons_price += $option_price;
            
            error_log('MWM DEBUG: Total calculation addon ' . $addon_data['addon_id'] . ' price: ' . $option_price . ' (calculated on total: ' . $normal_total . ')');
        }
        
        $final_total = $normal_total + $total_calculation_addons_price;
        
        error_log('MWM DEBUG: Final calculation:');
        error_log('MWM DEBUG: - Product price: ' . $base_price);
        error_log('MWM DEBUG: - Normal addons price: ' . $normal_addons_price);
        error_log('MWM DEBUG: - Total calculation addons price: ' . $total_calculation_addons_price);
        error_log('MWM DEBUG: - Final total: ' . $final_total . ' (original was: ' . $cart_item['line_total'] . ')');
        
        // Update the cart item with new prices
        $cart->cart_contents[$cart_item_key]['yith_wapo_total_options_price'] = $normal_addons_price + $total_calculation_addons_price;
        $cart->cart_contents[$cart_item_key]['line_subtotal'] = $final_total;
        $cart->cart_contents[$cart_item_key]['line_total'] = $final_total;
        
        // Update the product price in the cart item
        $cart->cart_contents[$cart_item_key]['data']->set_price($final_total);
        
        error_log('MWM DEBUG: Updated cart item prices:');
        error_log('MWM DEBUG: - yith_wapo_total_options_price: ' . ($normal_addons_price + $total_calculation_addons_price));
        error_log('MWM DEBUG: - line_subtotal: ' . $final_total);
        error_log('MWM DEBUG: - line_total: ' . $final_total);
    }
    
    /**
     * Modify YITH WAPO total calculation by addons list to handle calculate_on_total
     */
    public function modify_yith_total_by_add_ons_list($total_price, $type_list, $cart_item) {
        error_log('MWM DEBUG: modify_yith_total_by_add_ons_list called - Total: ' . $total_price);
        
        // Get product base price
        $product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : $cart_item['item_meta']['_product_id'][0];
        $_product = wc_get_product($product_id);
        
        if (!$_product) {
            return $total_price;
        }
        
        // WooCommerce Measurement Price Calculator (compatibility)
        if (isset($cart_item['pricing_item_meta_data']['_price'])) {
            $product_price = $cart_item['pricing_item_meta_data']['_price'];
        } else {
            $product_price = yit_get_display_price($_product);
        }
        
        // Get current cart item data for debugging
        $current_cart_item = null;
        if (WC()->cart) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
                if ($item['product_id'] == $product_id) {
                    $current_cart_item = $item;
                    break;
                }
            }
        }
        
        if (!$current_cart_item) {
            error_log('MWM DEBUG: Could not find current cart item');
            return $total_price;
        }
        
        error_log('MWM DEBUG: Current cart item price: ' . $current_cart_item['yith_wapo_item_price']);
        error_log('MWM DEBUG: Current total options price: ' . $current_cart_item['yith_wapo_total_options_price']);
        error_log('MWM DEBUG: Current line total: ' . $current_cart_item['line_total']);
        
        // Separate addons into two groups: normal and calculate_on_total
        $normal_addons = array();
        $total_calculation_addons = array();
        
        foreach ($type_list as $list) {
            foreach ($list as $key => $value) {
                if ($key && '' !== $value) {
                    if (is_array($value) && isset($value[0])) {
                        $value = $value[0];
                    }
                    
                    $values = YITH_WAPO::get_instance()->split_addon_and_option_ids($key, $value);
                    $addon_id = $values['addon_id'];
                    $option_id = $values['option_id'];
                    
                    // Check if this addon has calculate_on_total enabled
                    $option_name = 'mwm_calculate_on_total_' . $addon_id;
                    $calculate_on_total = get_option($option_name, '');
                    
                    error_log('MWM DEBUG: Checking addon ' . $addon_id . ' - Option name: ' . $option_name . ', Value: ' . $calculate_on_total);
                    
                    $addon_data = array(
                        'key' => $key,
                        'value' => $value,
                        'addon_id' => $addon_id,
                        'option_id' => $option_id
                    );
                    
                    if ($calculate_on_total === 'yes') {
                        $total_calculation_addons[] = $addon_data;
                        error_log('MWM DEBUG: Addon ' . $addon_id . ' has calculate_on_total enabled');
                    } else {
                        $normal_addons[] = $addon_data;
                        error_log('MWM DEBUG: Addon ' . $addon_id . ' does NOT have calculate_on_total enabled (value: ' . $calculate_on_total . ')');
                    }
                }
            }
        }
        
        error_log('MWM DEBUG: Normal addons: ' . count($normal_addons) . ', Total calculation addons: ' . count($total_calculation_addons));
        
        // If no total calculation addons, return original total
        if (empty($total_calculation_addons)) {
            error_log('MWM DEBUG: No total calculation addons found, returning original total');
            return $total_price;
        }
        
        // Calculate total with normal addons first (using original YITH calculation)
        $normal_total = $product_price;
        $normal_addons_price = 0;
        
        foreach ($normal_addons as $addon_data) {
            $option_price = $this->calculate_addon_price_for_total($addon_data, $product_price, $cart_item);
            $normal_total += $option_price;
            $normal_addons_price += $option_price;
        }
        
        error_log('MWM DEBUG: Normal total (base + normal addons): ' . $normal_total);
        error_log('MWM DEBUG: Normal addons price: ' . $normal_addons_price);
        
        // Calculate total with calculate_on_total addons using the normal total as base
        $total_calculation_addons_price = 0;
        foreach ($total_calculation_addons as $addon_data) {
            $option_price = $this->calculate_addon_price_for_total($addon_data, $normal_total, $cart_item);
            $total_calculation_addons_price += $option_price;
            
            error_log('MWM DEBUG: Total calculation addon ' . $addon_data['addon_id'] . ' price: ' . $option_price . ' (calculated on total: ' . $normal_total . ')');
        }
        
        $final_total = $normal_total + $total_calculation_addons_price;
        
        error_log('MWM DEBUG: Final calculation:');
        error_log('MWM DEBUG: - Product price: ' . $product_price);
        error_log('MWM DEBUG: - Normal addons price: ' . $normal_addons_price);
        error_log('MWM DEBUG: - Total calculation addons price: ' . $total_calculation_addons_price);
        error_log('MWM DEBUG: - Final total: ' . $final_total . ' (original was: ' . $total_price . ')');
        
        // Update cart item prices if we're in cart context
        if (WC()->cart && isset($current_cart_item)) {
            $cart_item_key = null;
            foreach (WC()->cart->get_cart() as $key => $item) {
                if ($item['product_id'] == $product_id) {
                    $cart_item_key = $key;
                    break;
                }
            }
            
            if ($cart_item_key) {
                // Update the cart item with new prices
                WC()->cart->cart_contents[$cart_item_key]['yith_wapo_item_price'] = $product_price;
                WC()->cart->cart_contents[$cart_item_key]['yith_wapo_total_options_price'] = $normal_addons_price + $total_calculation_addons_price;
                WC()->cart->cart_contents[$cart_item_key]['line_subtotal'] = $final_total;
                WC()->cart->cart_contents[$cart_item_key]['line_total'] = $final_total;
                
                // Update the product price in the cart item
                WC()->cart->cart_contents[$cart_item_key]['data']->set_price($final_total);
                
                error_log('MWM DEBUG: Updated cart item prices:');
                error_log('MWM DEBUG: - yith_wapo_item_price: ' . $product_price);
                error_log('MWM DEBUG: - yith_wapo_total_options_price: ' . ($normal_addons_price + $total_calculation_addons_price));
                error_log('MWM DEBUG: - line_subtotal: ' . $final_total);
                error_log('MWM DEBUG: - line_total: ' . $final_total);
            }
        }
        
        return $final_total;
    }
    
    /**
     * Calculate addon price for total calculation
     */
    private function calculate_addon_price_for_total($addon_data, $base_price, $cart_item) {
        $addon_id = $addon_data['addon_id'];
        $option_id = $addon_data['option_id'];
        $value = $addon_data['value'];
        
        // Get addon info using YITH WAPO function
        $info = yith_wapo_get_option_info($addon_id, $option_id);
        
        if (!$info) {
            return 0;
        }
        
        $option_price = 0;
        $option_price_sale = 0;
        
        // Calculate price based on type
        if ('percentage' === $info['price_type']) {
            $option_percentage = floatval($info['price']);
            $option_percentage_sale = floatval($info['price_sale']);
            $option_price = ($base_price / 100) * $option_percentage;
            $option_price_sale = ($base_price / 100) * $option_percentage_sale;
        } elseif ('multiplied' === $info['price_type']) {
            $option_price = $info['price'] * $value;
            $option_price_sale = $info['price_sale'] * $value;
        } elseif ('characters' === $info['price_type']) {
            $remove_spaces = apply_filters('yith_wapo_remove_spaces', false);
            $value = $remove_spaces ? str_replace(' ', '', $value) : $value;
            $value_length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
            $option_price = floatval($info['price']) * $value_length;
            $option_price_sale = floatval($info['price_sale']) * $value_length;
        } else {
            $option_price = $info['price'];
            $option_price_sale = $info['price_sale'];
        }
        
        // Handle number addon type
        if ('number' === $info['addon_type']) {
            if ('value_x_product' === $info['price_method']) {
                $option_price = $value * $base_price;
            }
        }
        
        // Apply price method (increase/decrease)
        if ('decrease' === $info['price_method']) {
            $option_price = -1 * abs($option_price);
        }
        
        // Apply sale price if available
        if ($option_price_sale > 0 && $option_price_sale < $option_price) {
            $option_price = $option_price_sale;
        }
        
        // Apply bundle cart item filter
        $option_price = apply_filters('yith_wapo_addon_prices_on_bundle_cart_item', $option_price);
        
        error_log('MWM DEBUG: calculate_addon_price_for_total - Addon: ' . $addon_id . ', Type: ' . $info['price_type'] . ', Method: ' . $info['price_method'] . ', Price: ' . $option_price . ', Base: ' . $base_price);
        
        return (float) $option_price;
    }
    
    /**
     * Calculate addon price for total calculation (simple version)
     */
    private function calculate_addon_price_for_total_simple($addon_id, $option_id, $cart_item) {
        // Get addon info using YITH WAPO function
        $info = yith_wapo_get_option_info($addon_id, $option_id);
        
        if (!$info) {
            return 0;
        }
        
        // Calculate base price for total calculation (use original price, not modified price)
        $base_price = $cart_item['data']->get_regular_price();
        
        // Add normal addons price to base price (excluding this addon and other calculate_on_total addons)
        $normal_addons_price = 0;
        if (isset($cart_item['yith_wapo_options'])) {
            foreach ($cart_item['yith_wapo_options'] as $option_group) {
                foreach ($option_group as $key => $val) {
                    if ($key && '' !== $val) {
                        $values = YITH_WAPO::get_instance()->split_addon_and_option_ids($key, $val);
                        $current_addon_id = $values['addon_id'];
                        $current_option_id = $values['option_id'];
                        
                        // Skip if this is the same addon we're calculating
                        if ($current_addon_id == $addon_id) {
                            continue;
                        }
                        
                        // Check if this addon should calculate on total
                        $option_name = 'mwm_calculate_on_total_' . $current_addon_id;
                        $calculate_on_total = get_option($option_name, '');
                        
                        // Only add normal addons (not calculate_on_total)
                        if ($calculate_on_total !== 'yes') {
                            $addon_info = yith_wapo_get_option_info($current_addon_id, $current_option_id);
                            if ($addon_info) {
                                $addon_price = $this->calculate_single_addon_price($addon_info, $base_price);
                                $normal_addons_price += $addon_price;
                            }
                        }
                    }
                }
            }
        }
        
        // Calculate total base price (product + normal addons only)
        $total_base_price = $base_price + $normal_addons_price;
        
        error_log('MWM DEBUG: Detailed calculation for addon ' . $addon_id);
        error_log('MWM DEBUG: - Base price: ' . $base_price);
        error_log('MWM DEBUG: - Normal addons price: ' . $normal_addons_price);
        error_log('MWM DEBUG: - Total base price: ' . $total_base_price);
        error_log('MWM DEBUG: - Addon price type: ' . $info['price_type']);
        error_log('MWM DEBUG: - Addon price value: ' . $info['price']);
        
        // Calculate this addon's price based on total (but only the percentage/fixed part, not the full calculation)
        $option_price = 0;
        if ('percentage' === $info['price_type']) {
            $option_percentage = floatval($info['price']);
            $option_price = ($total_base_price / 100) * $option_percentage;
            error_log('MWM DEBUG: - Percentage calculation: (' . $total_base_price . ' / 100) * ' . $option_percentage . ' = ' . $option_price);
        } elseif ('fixed' === $info['price_type']) {
            $option_price = floatval($info['price']);
            error_log('MWM DEBUG: - Fixed price: ' . $option_price);
        } elseif ('multiplied' === $info['price_type']) {
            $option_price = $info['price'] * 1; // Default value
            error_log('MWM DEBUG: - Multiplied price: ' . $option_price);
        } elseif ('characters' === $info['price_type']) {
            $option_price = $info['price'] * 1; // Default value
            error_log('MWM DEBUG: - Characters price: ' . $option_price);
        }
        
        // Apply method (increase/decrease)
        if ('decrease' === $info['price_method']) {
            $option_price = -$option_price;
        }
        
        error_log('MWM DEBUG: calculate_addon_price_for_total_simple - Addon: ' . $addon_id . ', Base: ' . $base_price . ', Normal addons: ' . $normal_addons_price . ', Total base: ' . $total_base_price . ', Final price: ' . $option_price);
        
        return (float) $option_price;
    }
    
    /**
     * Calculate single addon price
     */
    private function calculate_single_addon_price($info, $base_price) {
        $option_price = 0;
        $value = 1; // Default value for most addon types
        
        if ('percentage' === $info['price_type']) {
            $option_percentage = floatval($info['price']);
            $option_price = ($base_price / 100) * $option_percentage;
        } elseif ('fixed' === $info['price_type']) {
            $option_price = floatval($info['price']);
        } elseif ('multiplied' === $info['price_type']) {
            $option_price = $info['price'] * $value;
        } elseif ('characters' === $info['price_type']) {
            $remove_spaces = apply_filters('yith_wapo_remove_spaces', false);
            $value = $remove_spaces ? str_replace(' ', '', $value) : $value;
            $value_length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
            $option_price = $info['price'] * $value_length;
        }
        
        // Apply method (increase/decrease)
        if ('decrease' === $info['price_method']) {
            $option_price = -$option_price;
        }
        
        return (float) $option_price;
    }
    
    /**
     * Modify addon display in cart to show correct price
     */
    public function modify_addon_display_in_cart($display, $value, $sign, $price, $regular_price, $sale_price, $addon_id, $option_id) {
        error_log('MWM DEBUG: modify_addon_display_in_cart called');
        error_log('MWM DEBUG: Addon ID: ' . $addon_id . ', Option ID: ' . $option_id . ', Value: ' . $value . ', Price: ' . $price);
        
        // Check if this addon should calculate on total
        $option_name = 'mwm_calculate_on_total_' . $addon_id;
        $calculate_on_total = get_option($option_name, '');
        
        if ($calculate_on_total === 'yes') {
            error_log('MWM DEBUG: Addon ' . $addon_id . ' should calculate on total');
            
            // Get current cart item to calculate correct price
            $cart_item = $this->get_current_cart_item();
            if ($cart_item) {
                $new_price = $this->calculate_addon_price_for_total_simple($addon_id, $option_id, $cart_item);
                
                if ($new_price && $new_price != $price) {
                    error_log('MWM DEBUG: Updating addon ' . $addon_id . ' price from ' . $price . ' to ' . $new_price);
                    
                    // Format the new price
                    $formatted_price = wc_price($new_price);
                    $new_display = $value . ' (' . wp_strip_all_tags($sign . $formatted_price) . ')';
                    
                    error_log('MWM DEBUG: New display: ' . $new_display);
                    return $new_display;
                }
            }
        }
        
        return $display;
    }
    
    /**
     * Get current cart item being processed
     */
    private function get_current_cart_item() {
        // Try to get cart item from global context
        global $woocommerce_loop;
        
        if (WC()->cart && !WC()->cart->is_empty()) {
            $cart_items = WC()->cart->get_cart();
            foreach ($cart_items as $cart_item_key => $cart_item) {
                if (isset($cart_item['yith_wapo_options'])) {
                    return $cart_item;
                }
            }
        }
        
        return null;
    }
    
} 