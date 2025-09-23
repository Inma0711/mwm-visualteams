<?php
/**
 * Test file to verify plugin loading
 */

// Load WordPress
require_once('../../../wp-load.php');

echo '<h1>Plugin Test</h1>';

// Check if our plugin class exists
if (class_exists('MWM_VisualTeams_Plugin')) {
    echo '<p style="color: green;">✅ MWM_VisualTeams_Plugin class exists</p>';
} else {
    echo '<p style="color: red;">❌ MWM_VisualTeams_Plugin class does NOT exist</p>';
}

// Check if WooCommerce is active
if (class_exists('WooCommerce')) {
    echo '<p style="color: green;">✅ WooCommerce is active</p>';
} else {
    echo '<p style="color: red;">❌ WooCommerce is NOT active</p>';
}

// Check if YITH WAPO is active
if (is_plugin_active('yith-woocommerce-product-add-ons/init.php')) {
    echo '<p style="color: green;">✅ YITH WAPO is active</p>';
} else {
    echo '<p style="color: red;">❌ YITH WAPO is NOT active</p>';
}

// Check if our plugin is active
if (is_plugin_active('mwm-visualteams/mwm-visualteams.php')) {
    echo '<p style="color: green;">✅ MWM Visual Teams plugin is active</p>';
} else {
    echo '<p style="color: red;">❌ MWM Visual Teams plugin is NOT active</p>';
}

// Test if we can create an instance
try {
    $plugin = new MWM_VisualTeams_Plugin();
    echo '<p style="color: green;">✅ Plugin instance created successfully</p>';
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Error creating plugin instance: ' . $e->getMessage() . '</p>';
}

echo '<h2>Active Plugins:</h2>';
$active_plugins = get_option('active_plugins');
foreach ($active_plugins as $plugin) {
    echo '<p>' . $plugin . '</p>';
}
?>

