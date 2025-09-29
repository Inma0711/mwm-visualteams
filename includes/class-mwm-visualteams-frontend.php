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
        
        // Test hook that runs on ALL pages
        add_action('wp_footer', array($this, 'test_all_pages'));
        
        // Only hook into WooCommerce if it's active
        if (class_exists('WooCommerce')) {
            // Disabled to avoid conflict with product-price-update.js
            // add_action('woocommerce_after_add_to_cart_button', array($this, 'add_frontend_scripts'));
            
            // Add provisional checkboxes for 70% calculation
            add_action('woocommerce_after_single_product_summary', array($this, 'add_provisional_checkboxes'), 15);
            
            // Test hook that always executes
            add_action('wp_footer', array($this, 'test_hook'));
            
            // Add AJAX handlers
            add_action('wp_ajax_mwm_calculate_seventy_percent', array($this, 'calculate_seventy_percent'));
            add_action('wp_ajax_nopriv_mwm_calculate_seventy_percent', array($this, 'calculate_seventy_percent'));
            add_action('wp_ajax_mwm_check_total_calculation', array($this, 'check_total_calculation'));
            add_action('wp_ajax_nopriv_mwm_check_total_calculation', array($this, 'check_total_calculation'));
        }
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        // Disabled to avoid conflict with product-price-update.js
        /*
        // Load simplified JavaScript
        wp_enqueue_script(
            'mwm-visualteams-frontend',
            MWM_VISUALTEAMS_PLUGIN_URL . 'assets/js/frontend-simple.js',
            array('jquery'),
            MWM_VISUALTEAMS_VERSION,
            true
        );
        */
        
        // Only load WooCommerce specific data if WooCommerce is active
        if (class_exists('WooCommerce') && is_product()) {
            wp_localize_script('mwm-visualteams-frontend', 'mwm_ajax', array(
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
     * Add provisional checkboxes for 70% calculation
     */
    public function add_provisional_checkboxes() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        
        $product_id = $product->get_id();
        $base_price = $product->get_price();
        
        // Get dynamic option prices from YITH add-ons
        $option_prices = $this->get_product_option_prices($product_id);
        
        // Check if total calculation is enabled for any option
        $has_total_calculation = $this->check_total_calculation_status();
    }
    
    /**
     * AJAX handler for 70% calculation
     */
    public function calculate_seventy_percent() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mwm_visualteams_nonce')) {
            wp_die('Security check failed');
        }
        
        $base_price = floatval($_POST['base_price']);
        $option_price = floatval($_POST['option_price']);
        $option_name = sanitize_text_field($_POST['option_name']);
        
        // Calculate according to the CORRECT formula:
        // 1. Sottrarre il prezzo dell'opzione dal prezzo base del prodotto
        $step1_result = $base_price - $option_price;
        
        // 2. Calcolare il 70% del risultato precedente
        $step2_percentage = $step1_result * 0.70;
        
        // 3. Calcolare il nuovo totale (prezzo base - opzione + 70% del risultato)
        $step3_new_total = $step1_result + $step2_percentage;
        
        $response = array(
            'success' => true,
            'data' => array(
                'base_price' => $base_price,
                'option_price' => $option_price,
                'step1_result' => $step1_result,
                'percentage_amount' => $step2_percentage,
                'new_total' => $step3_new_total,
                'option_name' => $option_name
            )
        );
        
        wp_send_json($response);
    }
    
    /**
     * AJAX handler to check if total calculation is enabled
     */
    public function check_total_calculation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mwm_visualteams_nonce')) {
            wp_die('Security check failed');
        }
        
        // Use the function that we know works
        $enabled = $this->check_total_calculation_status();
        
        wp_send_json_success(array('enabled' => $enabled));
    }
    
    /**
     * Test hook to verify plugin is working
     */
    public function test_hook() {
        if (is_product()) {
            echo '<div style="position: fixed; top: 0; left: 0; background: red; color: white; padding: 10px; z-index: 9999;">TEST: MWM Plugin Working!</div>';
        }
    }
    
    /**
     * Test hook that runs on ALL pages
     */
    public function test_all_pages() {
        echo '<!-- MWM Plugin Test: ' . current_time('H:i:s') . ' -->';
    }
    
    /**
     * Add frontend scripts to product page
     */
    public function add_frontend_scripts() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize total price calculation
            if (typeof MWMVisualTeams !== 'undefined') {
                MWMVisualTeams.init();
            }
        });
        </script>
        <?php
    }

    /**
     * Get product option prices from YITH add-ons
     */
    private function get_product_option_prices($product_id) {
        $options = array();
        
        // Try to get YITH add-ons for this product
        if (class_exists('YITH_WAPO')) {
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
                                        $this->extract_addon_options($addon, $options);
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Method 2: Try to get all add-ons and filter by product
                if (empty($options) && method_exists(YITH_WAPO(), 'get_addons')) {
                    $all_addons = YITH_WAPO()->get_addons();
                    
                    if (!empty($all_addons)) {
                        foreach ($all_addons as $addon) {
                            // Check if this addon applies to this product
                            if ($this->addon_applies_to_product($addon, $product_id)) {
                                $this->extract_addon_options($addon, $options);
                            }
                        }
                    }
                }
                
                // Method 3: Try to get from database directly with more specific queries
                if (empty($options)) {
                    global $wpdb;
                    $addon_table = $wpdb->prefix . 'yith_wapo_addons';
                    $group_table = $wpdb->prefix . 'yith_wapo_groups';
                    
                    if ($wpdb->get_var("SHOW TABLES LIKE '$addon_table'") == $addon_table) {
                        // Check if groups table exists first
                        $group_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$group_table'") == $group_table;
                        
                        if ($group_table_exists) {
                            // Try to get addons that apply to this product or all products
                            $addons = $wpdb->get_results($wpdb->prepare(
                                "SELECT a.*, g.name as group_name 
                                 FROM $addon_table a 
                                 LEFT JOIN $group_table g ON a.group_id = g.id 
                                 WHERE a.product_id = %d OR a.product_id = 0 OR a.product_id IS NULL",
                                $product_id
                            ));
                        } else {
                            // If groups table doesn't exist, just get addons without group info
                            // Check what columns exist in the addon table first
                            $columns = $wpdb->get_results("DESCRIBE $addon_table");
                            $has_product_id = false;
                            foreach ($columns as $column) {
                                if ($column->Field === 'product_id') {
                                    $has_product_id = true;
                                    break;
                                }
                            }
                            
                            if ($has_product_id) {
                                $addons = $wpdb->get_results($wpdb->prepare(
                                    "SELECT a.*, '' as group_name 
                                     FROM $addon_table a 
                                     WHERE a.product_id = %d OR a.product_id = 0 OR a.product_id IS NULL",
                                    $product_id
                                ));
                            } else {
                                // If no product_id column, get all addons
                                $addons = $wpdb->get_results("SELECT a.*, '' as group_name FROM $addon_table a");
                            }
                        }
                        
                        foreach ($addons as $addon) {
                            $addon_options = maybe_unserialize($addon->options);
                            if (!empty($addon_options)) {
                                foreach ($addon_options as $option) {
                                    if (isset($option['price']) && !empty($option['price'])) {
                                        $options[] = array(
                                            'id' => $addon->id . '_' . sanitize_title($option['label']),
                                            'name' => $option['label'],
                                            'price' => floatval($option['price']),
                                            'group' => $addon->group_name
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Method 4: Try to get from YITH WAPO settings
                if (empty($options)) {
                    $yith_settings = get_option('yith_wapo_settings', array());
                    if (!empty($yith_settings)) {
                        $this->extract_options_from_settings($yith_settings, $product_id, $options);
                    }
                }
                
            } catch (Exception $e) {
                // Log error if needed
                error_log('MWM Visual Teams: Error getting YITH add-ons - ' . $e->getMessage());
            }
        }
        
        // If no YITH add-ons found, try to get from product meta or other sources
        if (empty($options)) {
            // Check if there are any custom fields or meta that might contain option prices
            $custom_options = get_post_meta($product_id, '_product_options', true);
            
            if (!empty($custom_options)) {
                foreach ($custom_options as $option) {
                    if (isset($option['price']) && !empty($option['price'])) {
                        $options[] = array(
                            'id' => sanitize_title($option['name']),
                            'name' => $option['name'],
                            'price' => floatval($option['price'])
                        );
                    }
                }
            }
            
            // Try other common meta keys
            $meta_keys = array('_product_addons', '_addons', '_options', '_custom_options', '_yith_wapo_options');
            foreach ($meta_keys as $meta_key) {
                $meta_options = get_post_meta($product_id, $meta_key, true);
                if (!empty($meta_options) && is_array($meta_options)) {
                    foreach ($meta_options as $option) {
                        if (isset($option['price']) && !empty($option['price'])) {
                            $options[] = array(
                                'id' => sanitize_title($option['name'] ?? $option['label'] ?? 'option'),
                                'name' => $option['name'] ?? $option['label'] ?? 'Opzione',
                                'price' => floatval($option['price'])
                            );
                        }
                    }
                }
            }
        }
        
        // If still no options found, try to parse the page content for YITH add-ons
        if (empty($options)) {
            $this->extract_options_from_page_content($options);
        }
        
        return $options;
    }
    
    /**
     * Extract options from a YITH addon
     */
    private function extract_addon_options($addon, &$options) {
        if (isset($addon->options) && !empty($addon->options)) {
            $addon_options = is_string($addon->options) ? maybe_unserialize($addon->options) : $addon->options;
            
            if (is_array($addon_options)) {
                foreach ($addon_options as $option) {
                    if (isset($option['price']) && !empty($option['price'])) {
                        $options[] = array(
                            'id' => $addon->id . '_' . sanitize_title($option['label'] ?? 'option'),
                            'name' => $option['label'] ?? 'Opzione',
                            'price' => floatval($option['price'])
                        );
                    }
                }
            }
        }
    }
    
    /**
     * Extract options from YITH settings
     */
    private function extract_options_from_settings($settings, $product_id, &$options) {
        if (isset($settings['addons']) && is_array($settings['addons'])) {
            foreach ($settings['addons'] as $addon) {
                if (isset($addon['product_id']) && ($addon['product_id'] == $product_id || $addon['product_id'] == 0)) {
                    if (isset($addon['options']) && is_array($addon['options'])) {
                        foreach ($addon['options'] as $option) {
                            if (isset($option['price']) && !empty($option['price'])) {
                                $options[] = array(
                                    'id' => sanitize_title($option['label'] ?? 'option'),
                                    'name' => $option['label'] ?? 'Opzione',
                                    'price' => floatval($option['price'])
                                );
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Extract options from page content (fallback method)
     */
    private function extract_options_from_page_content(&$options) {
        // This is a fallback method to extract options from the actual page content
        // It looks for common patterns in YITH add-ons
        
        // Try to get the current page content
        global $post;
        if ($post && $post->post_type === 'product') {
            $content = $post->post_content;
            
            // Look for YITH add-on patterns in content
            if (preg_match_all('/yith-wapo-option[^>]*value="([^"]*)"[^>]*data-price="([^"]*)"/', $content, $matches)) {
                for ($i = 0; $i < count($matches[1]); $i++) {
                    $options[] = array(
                        'id' => 'extracted_' . $i,
                        'name' => 'Opzione ' . ($i + 1),
                        'price' => floatval($matches[2][$i])
                    );
                }
            }
        }
    }
    
    /**
     * Check if an addon applies to a specific product
     */
    private function addon_applies_to_product($addon, $product_id) {
        // Check if addon has product restrictions
        if (isset($addon->product_id)) {
            if ($addon->product_id == 0 || $addon->product_id == $product_id) {
                return true;
            }
        }
        
        // Check if addon has category restrictions
        if (isset($addon->category_id)) {
            $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
            if (in_array($addon->category_id, $product_categories)) {
                return true;
            }
        }
        
        // If no restrictions, assume it applies
        return true;
    }

    /**
     * Check if total calculation is enabled for a specific option
     */
    private function is_option_total_calculation_enabled($option_id) {
        // Debug mode
        $debug_mode = isset($_GET['mwm_debug']) && $_GET['mwm_debug'] === '1';
        
        if ($debug_mode) {
            error_log('MWM Debug: Checking option_id: ' . $option_id);
        }
        
        // Extract the addon ID from the option ID
        $addon_id = null;
        
        // Try to get addon ID from YITH WAPO database
        global $wpdb;
        $addon_table = $wpdb->prefix . 'yith_wapo_addons';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$addon_table'") == $addon_table) {
            if ($debug_mode) {
                error_log('MWM Debug: YITH WAPO addons table exists');
            }
            
            // Look for addons that contain Cromato or Olografico options
            $addons = $wpdb->get_results(
                "SELECT id, options FROM $addon_table WHERE options LIKE '%Cromato%' OR options LIKE '%Olografico%'"
            );
            
            if ($debug_mode) {
                error_log('MWM Debug: Found ' . count($addons) . ' addons with Cromato/Olografico');
            }
            
            foreach ($addons as $addon) {
                if ($debug_mode) {
                    error_log('MWM Debug: Checking addon ID: ' . $addon->id);
                }
                
                $addon_options = maybe_unserialize($addon->options);
                if (is_array($addon_options)) {
                    foreach ($addon_options as $option) {
                        if (isset($option['label']) && 
                            (strtolower($option['label']) === 'cromato' || strtolower($option['label']) === 'olografico')) {
                            $addon_id = $addon->id;
                            if ($debug_mode) {
                                error_log('MWM Debug: Found matching addon ID: ' . $addon_id . ' for option: ' . $option['label']);
                            }
                            break 2;
                        }
                    }
                }
            }
        } else {
            if ($debug_mode) {
                error_log('MWM Debug: YITH WAPO addons table does not exist');
            }
        }
        
        if ($addon_id) {
            // Check if total calculation is enabled for this addon
            $calculate_on_total = get_post_meta($addon_id, '_calculate_on_total', true);
            
            if ($debug_mode) {
                error_log('MWM Debug: Meta value for addon ' . $addon_id . ': ' . $calculate_on_total);
            }
            
            return $calculate_on_total === 'yes';
        }
        
        if ($debug_mode) {
            error_log('MWM Debug: No addon ID found, returning false');
        }
        
        return false;
    }
    
    /**
     * Check if total calculation is enabled for any Cromato or Olografico option
     * This is an alternative method that doesn't depend on detected options
     */
    private function check_total_calculation_status() {
        $debug_mode = isset($_GET['mwm_debug']) && $_GET['mwm_debug'] === '1';
        
        if ($debug_mode) {
            error_log('MWM Debug: Checking total calculation status directly');
        }
        
        global $wpdb;
        $addon_table = $wpdb->prefix . 'yith_wapo_addons';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$addon_table'") == $addon_table) {
            // Get ALL addons first to see what we have
            $all_addons = $wpdb->get_results("SELECT id, options FROM $addon_table");
            
            if ($debug_mode) {
                error_log('MWM Debug: Total addons in database: ' . count($all_addons));
            }
            
            // Get addons that might contain Cromato or Olografico
            $addons = $wpdb->get_results(
                "SELECT id, options FROM $addon_table WHERE options LIKE '%Cromato%' OR options LIKE '%Olografico%'"
            );
            
            if ($debug_mode) {
                error_log('MWM Debug: Found ' . count($addons) . ' potential addons with Cromato/Olografico');
            }
            
            foreach ($addons as $addon) {
                $addon_options = maybe_unserialize($addon->options);
                if (is_array($addon_options)) {
                    foreach ($addon_options as $option) {
                        if (isset($option['label']) && 
                            (strtolower($option['label']) === 'cromato' || strtolower($option['label']) === 'olografico')) {
                            
                            // Check if this addon has total calculation enabled
                            $calculate_on_total = get_post_meta($addon->id, '_calculate_on_total', true);
                            
                            if ($debug_mode) {
                                error_log('MWM Debug: Addon ' . $addon->id . ' for ' . $option['label'] . ' has _calculate_on_total = "' . $calculate_on_total . '"');
                            }
                            
                            if ($calculate_on_total === 'yes') {
                                if ($debug_mode) {
                                    error_log('MWM Debug: Found enabled addon: ' . $addon->id . ' for ' . $option['label']);
                                }
                                return true;
                            }
                        }
                    }
                }
            }
            
            // If no specific matches found, let's check ALL addons for any _calculate_on_total = 'yes'
            if ($debug_mode) {
                error_log('MWM Debug: No specific Cromato/Olografico matches found, checking all addons...');
            }
            
            foreach ($all_addons as $addon) {
                $calculate_on_total = get_post_meta($addon->id, '_calculate_on_total', true);
                if ($calculate_on_total === 'yes') {
                    if ($debug_mode) {
                        error_log('MWM Debug: Found ANY addon with _calculate_on_total = yes: ' . $addon->id);
                    }
                    return true;
                }
            }
        }
        
        if ($debug_mode) {
            error_log('MWM Debug: No enabled addons found');
        }
        
        return false;
    }
} 