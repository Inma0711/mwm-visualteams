<?php
/**
 * Plugin Name: MWM Visual Teams - Product Options Extension
 * Plugin URI: https://visualteams.com
 * Description: Extensión para YITH WooCommerce Product Add-ons que permite calcular opciones sobre el precio total del producto
 * Version: 1.0.0
 * Author: MWM Visual Teams
 * Author URI: https://visualteams.com
 * Text Domain: mwm-visualteams
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MWM_VISUALTEAMS_VERSION', '1.0.0');
define('MWM_VISUALTEAMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MWM_VISUALTEAMS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MWM_VISUALTEAMS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include activation file
require_once MWM_VISUALTEAMS_PLUGIN_PATH . 'activate.php';

/**
 * Main plugin class
 */
class MWM_VisualTeams_Plugin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load plugin files
        $this->load_files();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
        }
        
        // Check if YITH WooCommerce Product Add-ons is active
        if (!class_exists('YITH_WAPO')) {
            add_action('admin_notices', array($this, 'yith_addons_missing_notice'));
        }
    }
    
    /**
     * Load required files
     */
    private function load_files() {
        // Include core files
        if (file_exists(MWM_VISUALTEAMS_PLUGIN_PATH . 'includes/class-mwm-visualteams-admin.php')) {
            require_once MWM_VISUALTEAMS_PLUGIN_PATH . 'includes/class-mwm-visualteams-admin.php';
        }
        if (file_exists(MWM_VISUALTEAMS_PLUGIN_PATH . 'includes/class-mwm-visualteams-cart.php')) {
            require_once MWM_VISUALTEAMS_PLUGIN_PATH . 'includes/class-mwm-visualteams-cart.php';
        }
        if (file_exists(MWM_VISUALTEAMS_PLUGIN_PATH . 'includes/class-mwm-visualteams-frontend.php')) {
            require_once MWM_VISUALTEAMS_PLUGIN_PATH . 'includes/class-mwm-visualteams-frontend.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add basic test notice
        add_action('admin_notices', array($this, 'basic_test_notice'));
        
        // Add test to all pages
        add_action('wp_footer', array($this, 'footer_test'));
        add_action('admin_footer', array($this, 'footer_test'));
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // Initialize classes
        try {
            if (class_exists('MWM_VisualTeams_Admin')) {
                new MWM_VisualTeams_Admin();
            }
            if (class_exists('MWM_VisualTeams_Cart')) {
                new MWM_VisualTeams_Cart();
            }
            if (class_exists('MWM_VisualTeams_Frontend')) {
                new MWM_VisualTeams_Frontend();
            }
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>MWM Visual Teams Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
                echo '</div>';
            });
        }
    }
    
    /**
     * Basic test notice
     */
    public function basic_test_notice() {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>MWM Visual Teams:</strong> Plugin principal funcionando! ✅</p>';
        echo '<p>Hora de carga: ' . current_time('H:i:s') . '</p>';
        echo '</div>';
    }
    
    /**
     * Footer test
     */
    public function footer_test() {
        echo '<!-- MWM Visual Teams Plugin Loaded at: ' . current_time('H:i:s') . ' -->';
    }
    
    /**
     * Admin scripts
     */
    public function admin_scripts() {
        wp_enqueue_script(
            'mwm-visualteams-admin',
            MWM_VISUALTEAMS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MWM_VISUALTEAMS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'mwm-visualteams-admin',
            MWM_VISUALTEAMS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MWM_VISUALTEAMS_VERSION
        );
    }
    
    /**
     * Frontend scripts
     */
    public function frontend_scripts() {
        wp_enqueue_script(
            'mwm-visualteams-frontend',
            MWM_VISUALTEAMS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            MWM_VISUALTEAMS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'mwm-visualteams-frontend',
            MWM_VISUALTEAMS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            MWM_VISUALTEAMS_VERSION
        );
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p>' . 
             __('MWM Visual Teams requiere que WooCommerce esté instalado y activado.', 'mwm-visualteams') . 
             '</p></div>';
    }
    
    /**
     * YITH Add-ons missing notice
     */
    public function yith_addons_missing_notice() {
        echo '<div class="error"><p>' . 
             __('MWM Visual Teams requiere que YITH WooCommerce Product Add-ons esté instalado y activado.', 'mwm-visualteams') . 
             '</p></div>';
    }
}

// Initialize the plugin
new MWM_VisualTeams_Plugin(); 