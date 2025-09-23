<?php
/**
 * Plugin Activation Hook
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activation function
 */
function mwm_visualteams_activate() {
    // Create a test option to verify activation
    add_option('mwm_visualteams_activated', 'yes');
    add_option('mwm_visualteams_activation_time', current_time('timestamp'));
    
    // Add admin notice for activation
    add_action('admin_notices', 'mwm_visualteams_activation_notice');
}

/**
 * Activation notice
 */
function mwm_visualteams_activation_notice() {
    echo '<div class="notice notice-success is-dismissible">';
    echo '<p><strong>MWM Visual Teams:</strong> Plugin activado correctamente! 🎉</p>';
    echo '<p>Ahora puedes configurar las opciones de cálculo sobre precio total.</p>';
    echo '</div>';
}

// Register activation hook
register_activation_hook(__FILE__, 'mwm_visualteams_activate'); 