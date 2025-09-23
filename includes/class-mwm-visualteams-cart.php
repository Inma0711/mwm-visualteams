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
            
            // Hook into WooCommerce cart calculation
            add_action('woocommerce_before_calculate_totals', array($this, 'calculate_cart_totals'), 10, 1);
            
            // Hook into YITH WAPO calculation
            add_filter('yith_wapo_calculate_addon_price', array($this, 'modify_yith_addon_price'), 10, 4);
            add_filter('yith_wapo_calculate_addon_price_on_cart', array($this, 'modify_yith_addon_price_cart'), 10, 4);
            
            // Hook into YITH WAPO total calculation
            add_filter('yith_wapo_calculate_addon_price_on_cart', array($this, 'modify_yith_total_calculation'), 20, 4);
            add_action('yith_wapo_before_calculate_totals', array($this, 'before_yith_calculate_totals'), 10, 1);
            
            // Hook into WooCommerce product price calculation
            add_filter('woocommerce_product_get_price', array($this, 'modify_product_price'), 10, 2);
            add_filter('woocommerce_product_variation_get_price', array($this, 'modify_product_price'), 10, 2);
            
            // AJAX endpoint for price calculation
            add_action('wp_ajax_mwm_calculate_price', array($this, 'ajax_calculate_price'));
            add_action('wp_ajax_nopriv_mwm_calculate_price', array($this, 'ajax_calculate_price'));
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
        
        error_log('MWM DEBUG: calculate_cart_totals called');
        error_log('MWM DEBUG: Cart has ' . count($cart->get_cart()) . ' items');
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            error_log('MWM DEBUG: Processing cart item ' . $cart_item_key);
            $this->calculate_item_total_price($cart_item_key, $cart_item, $cart);
        }
    }
    
    /**
     * Calculate total price for a specific cart item
     */
    private function calculate_item_total_price($cart_item_key, $cart_item, $cart) {
        $product_id = $cart_item['product_id'];
        $variation_id = $cart_item['variation_id'];
        
        // Debug logging
        error_log('MWM DEBUG: calculate_item_total_price called for product ' . $product_id);
        error_log('MWM DEBUG: Cart item data: ' . print_r($cart_item, true));
        
        // Get YITH add-ons for this product
        $addons = $this->get_product_addons($product_id);
        
        error_log('MWM DEBUG: Found ' . count($addons) . ' addons for product ' . $product_id);
        
        if (empty($addons)) {
            error_log('MWM DEBUG: No addons found, returning');
            return;
        }
        
        $base_price = $cart_item['mwm_original_price'] ?? $cart_item['data']->get_price();
        $selected_options = array();
        $cromato_olografico_options = array();
        $other_options = array();
        $modello_options = array();
        
        // First pass: collect all selected options
        foreach ($addons as $addon) {
            if (isset($cart_item['yith_wapo_options'][$addon->id])) {
                $option_value = $cart_item['yith_wapo_options'][$addon->id];
                $option_price = $this->calculate_option_price($addon, $option_value, $base_price);
                
                $option_data = array(
                    'addon' => $addon,
                    'value' => $option_value,
                    'price' => $option_price,
                    'is_cromato_olografico' => $this->is_cromato_olografico_option($addon, $option_value),
                    'is_modello' => $this->is_modello_option($addon, $option_value)
                );
                
                $selected_options[] = $option_data;
                
                if ($option_data['is_modello']) {
                    $modello_options[] = $option_data;
                } elseif ($option_data['is_cromato_olografico']) {
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
        
        // Apply the new 70% logic with MODELLO support
        error_log('MWM DEBUG: Before calculation - Base: ' . $base_price . ', Cromato/Olografico: ' . count($cromato_olografico_options) . ', Other: ' . count($other_options) . ', Modello: ' . count($modello_options));
        
        $total_price = $this->calculate_with_modello_logic($base_price, $cromato_olografico_options, $other_options, $modello_options);
        
        error_log('MWM DEBUG: After calculation - Total price: ' . $total_price . ', Options total: ' . ($total_price - $base_price));
        
        // Update cart item price
        $cart->cart_contents[$cart_item_key]['data']->set_price($total_price);
    }
    
    /**
     * Calculate price using the 70% logic with MODELLO support
     */
    private function calculate_with_modello_logic($base_price, $cromato_olografico_options, $other_options, $modello_options) {
        error_log('MWM DEBUG: calculate_with_modello_logic called with base: ' . $base_price);
        error_log('MWM DEBUG: Cromato/Olografico options: ' . count($cromato_olografico_options));
        error_log('MWM DEBUG: Other options: ' . count($other_options));
        error_log('MWM DEBUG: Modello options: ' . count($modello_options));
        
        // Si no hay Cromato/Olográfico, usar cálculo normal (incluyendo MODELLO)
        if (empty($cromato_olografico_options)) {
            error_log('MWM DEBUG: No Cromato/Olografico - using normal calculation');
            $total_price = $base_price;
            foreach ($other_options as $option) {
                $total_price += $option['price'];
                error_log('MWM DEBUG: Added other option: ' . $option['name'] . ' (+' . $option['price'] . ')');
            }
            foreach ($modello_options as $option) {
                $total_price += $option['price'];
                error_log('MWM DEBUG: Added modello option: ' . $option['name'] . ' (+' . $option['price'] . ')');
            }
            error_log('MWM DEBUG: Normal calculation result: ' . $total_price);
            return $total_price;
        }
        
        // Si solo Cromato/Olográfico (sin otras opciones), usar suma normal + MODELLO
        if (empty($other_options)) {
            $total_price = $base_price;
            foreach ($cromato_olografico_options as $option) {
                $total_price += $option['price'];
            }
            foreach ($modello_options as $option) {
                $total_price += $option['price'];
            }
            return $total_price;
        }
        
        // Si hay Cromato/Olográfico + otras opciones, aplicar 70% + MODELLO
        error_log('MWM DEBUG: Applying 70% calculation with MODELLO');
        
        // Paso 1: Sumar todo (base + Cromato/Olográfico + otras opciones) - SIN MODELLO
        $step1_total = $base_price;
        foreach ($cromato_olografico_options as $option) {
            $step1_total += $option['price'];
            error_log('MWM DEBUG: Added cromato/olografico: ' . $option['name'] . ' (+' . $option['price'] . ')');
        }
        foreach ($other_options as $option) {
            $step1_total += $option['price'];
            error_log('MWM DEBUG: Added other option: ' . $option['name'] . ' (+' . $option['price'] . ')');
        }
        error_log('MWM DEBUG: Step 1 total: ' . $step1_total);
        
        // Paso 2: Restar Cromato/Olográfico
        $cromato_olografico_total = 0;
        foreach ($cromato_olografico_options as $option) {
            $cromato_olografico_total += $option['price'];
        }
        $step2_without_cromato = $step1_total - $cromato_olografico_total;
        error_log('MWM DEBUG: Step 2 without cromato: ' . $step2_without_cromato);
        
        // Paso 3: Calcular 70% del resultado
        $step3_percentage = $step2_without_cromato * 0.70;
        error_log('MWM DEBUG: Step 3 (70%): ' . $step3_percentage);
        
        // Paso 4: Sumar las otras opciones
        $other_options_total = 0;
        foreach ($other_options as $option) {
            $other_options_total += $option['price'];
        }
        $final_total = $step3_percentage + $other_options_total;
        error_log('MWM DEBUG: Step 4 final (without modello): ' . $final_total);
        
        // Paso 5: Añadir MODELLO al final (sin afectar el 70%)
        $modello_total = 0;
        foreach ($modello_options as $option) {
            $modello_total += $option['price'];
            error_log('MWM DEBUG: Added modello: ' . $option['name'] . ' (+' . $option['price'] . ')');
        }
        error_log('MWM DEBUG: Modello total: ' . $modello_total);
        
        $result = $base_price + $final_total + $modello_total;
        error_log('MWM DEBUG: Final result: ' . $result);
        
        return $result;
    }
    
    /**
     * Calculate price using the 70% logic (old version - kept for compatibility)
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
     * Check if an option is MODELLO CAN AM UTV
     */
    private function is_modello_option($addon, $option_value) {
        // Check if the option value contains MODELLO, CAN AM, or UTV
        $option_lower = strtolower(trim($option_value));
        if (strpos($option_lower, 'modello') !== false || 
            strpos($option_lower, 'can am') !== false || 
            strpos($option_lower, 'utv') !== false) {
            return true;
        }
        
        // Check if the addon title contains MODELLO, CAN AM, or UTV
        if (isset($addon->settings['title'])) {
            $title_lower = strtolower(trim($addon->settings['title']));
            if (strpos($title_lower, 'modello') !== false || 
                strpos($title_lower, 'can am') !== false || 
                strpos($title_lower, 'utv') !== false) {
                return true;
            }
        }
        
        return false;
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
    
    /**
     * Modify YITH WAPO addon price calculation
     */
    public function modify_yith_addon_price($price, $addon, $option, $product) {
        error_log('MWM DEBUG: modify_yith_addon_price called - Price: ' . $price . ', Addon: ' . $addon->id . ', Option: ' . $option['label']);
        
        // Check if this is a Cromato/Olografico option
        if ($this->is_cromato_olografico_option($addon, $option['label'])) {
            error_log('MWM DEBUG: This is a Cromato/Olografico option');
            // Apply 70% calculation
            $new_price = $price * 0.70;
            error_log('MWM DEBUG: Price modified from ' . $price . ' to ' . $new_price);
            return $new_price;
        }
        
        // Check if this is a MODELLO option
        if ($this->is_modello_option($addon, $option['label'])) {
            error_log('MWM DEBUG: This is a MODELLO option - keeping original price');
            return $price;
        }
        
        return $price;
    }
    
    /**
     * Modify YITH WAPO addon price calculation on cart
     */
    public function modify_yith_addon_price_cart($price, $addon, $option, $cart_item) {
        error_log('MWM DEBUG: modify_yith_addon_price_cart called - Price: ' . $price . ', Addon: ' . $addon->id . ', Option: ' . $option['label']);
        
        // Check if this is a Cromato/Olografico option
        if ($this->is_cromato_olografico_option($addon, $option['label'])) {
            error_log('MWM DEBUG: This is a Cromato/Olografico option in cart');
            // Don't modify the price here, let the 70% calculation handle it
            return $price;
        }
        
        // Check if this is a MODELLO option
        if ($this->is_modello_option($addon, $option['label'])) {
            error_log('MWM DEBUG: This is a MODELLO option in cart');
            // Don't modify the price here, let the 70% calculation handle it
            return $price;
        }
        
        return $price;
    }
    
    /**
     * Modify YITH WAPO total calculation
     */
    public function modify_yith_total_calculation($price, $addon, $option, $cart_item) {
        error_log('MWM DEBUG: modify_yith_total_calculation called - Price: ' . $price . ', Addon: ' . $addon->id . ', Option: ' . $option['label']);
        
        // Check if this is a Cromato/Olografico option
        if ($this->is_cromato_olografico_option($addon, $option['label'])) {
            error_log('MWM DEBUG: This is a Cromato/Olografico option in total calculation');
            // Don't modify the price here, let the 70% calculation handle it
            return $price;
        }
        
        // Check if this is a MODELLO option
        if ($this->is_modello_option($addon, $option['label'])) {
            error_log('MWM DEBUG: This is a MODELLO option in total calculation');
            // Don't modify the price here, let the 70% calculation handle it
            return $price;
        }
        
        return $price;
    }
    
    /**
     * Before YITH WAPO calculate totals
     */
    public function before_yith_calculate_totals($cart_item) {
        error_log('MWM DEBUG: before_yith_calculate_totals called for product: ' . $cart_item['product_id']);
        
        // Check if we have YITH WAPO options
        if (isset($cart_item['yith_wapo_options']) && !empty($cart_item['yith_wapo_options'])) {
            error_log('MWM DEBUG: YITH WAPO options found in cart item');
            
            // Get YITH add-ons for this product
            $addons = $this->get_product_addons($cart_item['product_id']);
            
            if (!empty($addons)) {
                $selected_options = array();
                $cromato_olografico_options = array();
                $other_options = array();
                $modello_options = array();
                
                // Process selected options
                foreach ($addons as $addon) {
                    if (isset($cart_item['yith_wapo_options'][$addon->id])) {
                        $option_value = $cart_item['yith_wapo_options'][$addon->id];
                        $option_price = $this->calculate_option_price($addon, $option_value, $cart_item['data']->get_price());
                        
                        $option_data = array(
                            'addon' => $addon,
                            'value' => $option_value,
                            'price' => $option_price,
                            'is_cromato_olografico' => $this->is_cromato_olografico_option($addon, $option_value),
                            'is_modello' => $this->is_modello_option($addon, $option_value)
                        );
                        
                        $selected_options[] = $option_data;
                        
                        if ($option_data['is_modello']) {
                            $modello_options[] = $option_data;
                        } elseif ($option_data['is_cromato_olografico']) {
                            $cromato_olografico_options[] = $option_data;
                        } else {
                            $other_options[] = $option_data;
                        }
                    }
                }
                
                // Check if total calculation is enabled
                $has_total_calculation = false;
                foreach ($selected_options as $option) {
                    if (isset($option['addon']->settings['calculate_on_total']) && $option['addon']->settings['calculate_on_total'] === 'yes') {
                        $has_total_calculation = true;
                        break;
                    }
                }
                
                if ($has_total_calculation) {
                    error_log('MWM DEBUG: Total calculation enabled, applying 70% logic');
                    $new_price = $this->calculate_with_modello_logic($cart_item['data']->get_price(), $cromato_olografico_options, $other_options, $modello_options);
                    error_log('MWM DEBUG: New price calculated: ' . $new_price);
                    
                    // Store the calculated price for later use
                    $cart_item['mwm_calculated_price'] = $new_price;
                }
            }
        }
    }
    
    /**
     * Modify product price based on selected options
     */
    public function modify_product_price($price, $product) {
        // Only work on product pages and if we're not in admin
        if (is_admin() || !is_product()) {
            return $price;
        }
        
        error_log('MWM DEBUG: modify_product_price called - Price: ' . $price . ', Product ID: ' . $product->get_id());
        
        // Check if we have YITH WAPO options selected
        if (isset($_POST['yith_wapo_options']) && !empty($_POST['yith_wapo_options'])) {
            error_log('MWM DEBUG: YITH WAPO options found in POST: ' . print_r($_POST['yith_wapo_options'], true));
            
            // Get YITH add-ons for this product
            $addons = $this->get_product_addons($product->get_id());
            
            if (!empty($addons)) {
                $selected_options = array();
                $cromato_olografico_options = array();
                $other_options = array();
                $modello_options = array();
                
                // Process selected options
                foreach ($addons as $addon) {
                    if (isset($_POST['yith_wapo_options'][$addon->id])) {
                        $option_value = $_POST['yith_wapo_options'][$addon->id];
                        $option_price = $this->calculate_option_price($addon, $option_value, $price);
                        
                        $option_data = array(
                            'addon' => $addon,
                            'value' => $option_value,
                            'price' => $option_price,
                            'is_cromato_olografico' => $this->is_cromato_olografico_option($addon, $option_value),
                            'is_modello' => $this->is_modello_option($addon, $option_value)
                        );
                        
                        $selected_options[] = $option_data;
                        
                        if ($option_data['is_modello']) {
                            $modello_options[] = $option_data;
                        } elseif ($option_data['is_cromato_olografico']) {
                            $cromato_olografico_options[] = $option_data;
                        } else {
                            $other_options[] = $option_data;
                        }
                    }
                }
                
                // Check if total calculation is enabled
                $has_total_calculation = false;
                foreach ($selected_options as $option) {
                    if (isset($option['addon']->settings['calculate_on_total']) && $option['addon']->settings['calculate_on_total'] === 'yes') {
                        $has_total_calculation = true;
                        break;
                    }
                }
                
                if ($has_total_calculation) {
                    error_log('MWM DEBUG: Total calculation enabled, applying 70% logic');
                    $new_price = $this->calculate_with_modello_logic($price, $cromato_olografico_options, $other_options, $modello_options);
                    error_log('MWM DEBUG: New price calculated: ' . $new_price);
                    return $new_price;
                }
            }
        }
        
        return $price;
    }
    
    /**
     * AJAX endpoint for price calculation
     */
    public function ajax_calculate_price() {
        error_log('MWM DEBUG: AJAX calculate_price called');
        
        // Get the product ID and options from POST
        $product_id = intval($_POST['product_id']);
        $options = $_POST['options'] ?? array();
        
        error_log('MWM DEBUG: Product ID: ' . $product_id . ', Options: ' . print_r($options, true));
        
        if (!$product_id) {
            wp_die('Invalid product ID');
        }
        
        // Get the product
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_die('Product not found');
        }
        
        $base_price = $product->get_price();
        error_log('MWM DEBUG: Base price: ' . $base_price);
        
        // Get YITH add-ons for this product
        $addons = $this->get_product_addons($product_id);
        
        if (empty($addons)) {
            error_log('MWM DEBUG: No addons found');
            wp_send_json_success(array('price' => $base_price));
        }
        
        $selected_options = array();
        $cromato_olografico_options = array();
        $other_options = array();
        $modello_options = array();
        
        // Process selected options
        foreach ($addons as $addon) {
            if (isset($options[$addon->id])) {
                $option_value = $options[$addon->id];
                $option_price = $this->calculate_option_price($addon, $option_value, $base_price);
                
                $option_data = array(
                    'addon' => $addon,
                    'value' => $option_value,
                    'price' => $option_price,
                    'is_cromato_olografico' => $this->is_cromato_olografico_option($addon, $option_value),
                    'is_modello' => $this->is_modello_option($addon, $option_value)
                );
                
                $selected_options[] = $option_data;
                
                if ($option_data['is_modello']) {
                    $modello_options[] = $option_data;
                } elseif ($option_data['is_cromato_olografico']) {
                    $cromato_olografico_options[] = $option_data;
                } else {
                    $other_options[] = $option_data;
                }
            }
        }
        
        // Check if total calculation is enabled
        $has_total_calculation = false;
        foreach ($selected_options as $option) {
            if (isset($option['addon']->settings['calculate_on_total']) && $option['addon']->settings['calculate_on_total'] === 'yes') {
                $has_total_calculation = true;
                break;
            }
        }
        
        if ($has_total_calculation) {
            error_log('MWM DEBUG: Total calculation enabled, applying 70% logic');
            $new_price = $this->calculate_with_modello_logic($base_price, $cromato_olografico_options, $other_options, $modello_options);
            error_log('MWM DEBUG: New price calculated: ' . $new_price);
            wp_send_json_success(array('price' => $new_price));
        } else {
            // Normal calculation
            $total_price = $base_price;
            foreach ($selected_options as $option) {
                $total_price += $option['price'];
            }
            error_log('MWM DEBUG: Normal calculation result: ' . $total_price);
            wp_send_json_success(array('price' => $total_price));
        }
    }
} 