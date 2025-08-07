<?php
/**
 * Test file for cart calculation logic
 * Access via: your-site.com/wp-content/plugins/mwm-visualteams/test-cart-logic.php?test=1
 */

// Prevent direct access in production
if (!defined('ABSPATH')) {
    // Allow direct access for testing
    if (!isset($_GET['test'])) {
        exit('Direct access not allowed');
    }
}

/**
 * Test the cart calculation logic
 */
function test_cart_calculation_logic() {
    echo "<h1>🛒 Test de Lógica del Carrito - MWM Visual Teams</h1>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-case { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .info { background: #e7f3ff; border-left: 4px solid #0073aa; }
        .calculation { background: white; padding: 10px; margin: 10px 0; border-radius: 3px; font-family: monospace; }
        .result { font-weight: bold; color: #0073aa; }
    </style>";
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        echo "<div class='test-case error'>";
        echo "<h2>❌ WooCommerce no está activo</h2>";
        echo "<p>Este test requiere que WooCommerce esté instalado y activo.</p>";
        echo "</div>";
        return;
    }
    
    // Check if YITH WAPO is active
    if (!class_exists('YITH_WAPO')) {
        echo "<div class='test-case error'>";
        echo "<h2>❌ YITH WooCommerce Product Add-ons no está activo</h2>";
        echo "<p>Este test requiere que YITH WAPO esté instalado y activo.</p>";
        echo "</div>";
        return;
    }
    
    echo "<div class='test-case success'>";
    echo "<h2>✅ WooCommerce y YITH WAPO están activos</h2>";
    echo "</div>";
    
    // Test YITH WAPO functions
    echo "<div class='test-case'>";
    echo "<h2>🔧 Test de Funciones YITH WAPO</h2>";
    
    $test_product_id = 1; // Use a test product ID
    
    // Test get_addon_groups_by_product
    if (method_exists(YITH_WAPO(), 'get_addon_groups_by_product')) {
        $addon_groups = YITH_WAPO()->get_addon_groups_by_product($test_product_id);
        echo "<p><strong>get_addon_groups_by_product:</strong> " . (is_array($addon_groups) ? count($addon_groups) . ' grupos encontrados' : 'No disponible') . "</p>";
    } else {
        echo "<p><strong>get_addon_groups_by_product:</strong> ❌ No disponible</p>";
    }
    
    // Test get_addons
    if (method_exists(YITH_WAPO(), 'get_addons')) {
        $all_addons = YITH_WAPO()->get_addons();
        echo "<p><strong>get_addons:</strong> " . (is_array($all_addons) ? count($all_addons) . ' addons encontrados' : 'No disponible') . "</p>";
    } else {
        echo "<p><strong>get_addons:</strong> ❌ No disponible</p>";
    }
    
    // Test database table
    global $wpdb;
    $addon_table = $wpdb->prefix . 'yith_wapo_addons';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$addon_table'") == $addon_table;
    echo "<p><strong>Tabla yith_wapo_addons:</strong> " . ($table_exists ? '✅ Existe' : '❌ No existe') . "</p>";
    
    if ($table_exists) {
        $addon_count = $wpdb->get_var("SELECT COUNT(*) FROM $addon_table");
        echo "<p><strong>Addons en base de datos:</strong> $addon_count</p>";
        
        // Show some sample addons
        $sample_addons = $wpdb->get_results("SELECT id, title FROM $addon_table LIMIT 5");
        if (!empty($sample_addons)) {
            echo "<p><strong>Muestra de addons:</strong></p>";
            echo "<ul>";
            foreach ($sample_addons as $addon) {
                echo "<li>ID: {$addon->id} - Título: {$addon->title}</li>";
            }
            echo "</ul>";
        }
    }
    
    echo "</div>";
    
    // Test cart calculation simulation
    echo "<div class='test-case'>";
    echo "<h2>🧮 Test de Simulación de Cálculo del Carrito</h2>";
    
    $base_price = 340.00;
    
    // Simulate cart item data
    $cart_item = array(
        'product_id' => $test_product_id,
        'variation_id' => 0,
        'mwm_original_price' => $base_price,
        'yith_wapo_options' => array(
            // Simulate selected options
            '1' => 'Olográfico', // Assuming addon ID 1 is Olográfico
            '2' => 'Opaco'       // Assuming addon ID 2 is Opaco
        )
    );
    
    echo "<p><strong>Simulando carrito con:</strong></p>";
    echo "<ul>";
    echo "<li>Producto ID: {$test_product_id}</li>";
    echo "<li>Precio base: {$base_price}€</li>";
    echo "<li>Opciones seleccionadas: Olográfico + Opaco</li>";
    echo "</ul>";
    
    // Test the calculation logic
    $result = simulate_cart_calculation($cart_item);
    
    echo "<div class='calculation'>";
    echo "<strong>Resultado del cálculo:</strong><br>";
    echo "- Precio base: {$base_price}€<br>";
    echo "- Total calculado: {$result['total_price']}€<br>";
    echo "- Opciones total: " . ($result['total_price'] - $base_price) . "€<br>";
    echo "- Lógica aplicada: " . ($result['applied_70_percent'] ? '70%' : 'Normal') . "<br>";
    echo "- Addons encontrados: " . count($result['addons']) . "<br>";
    echo "</div>";
    
    echo "</div>";
    
    // Summary
    echo "<div class='test-case info'>";
    echo "<h2>📊 Resumen del Test del Carrito</h2>";
    echo "<p><strong>Estado del sistema:</strong></p>";
    echo "<ul>";
    echo "<li>✅ WooCommerce: Activo</li>";
    echo "<li>✅ YITH WAPO: Activo</li>";
    echo "<li>✅ Tabla de addons: " . ($table_exists ? 'Disponible' : 'No disponible') . "</li>";
    echo "<li>✅ Funciones YITH: " . (method_exists(YITH_WAPO(), 'get_addons') ? 'Disponibles' : 'Limitadas') . "</li>";
    echo "</ul>";
    echo "<p><strong>Próximos pasos:</strong></p>";
    echo "<ul>";
    echo "<li>🔍 Verificar que los addons se detectan correctamente</li>";
    echo "<li>🔍 Probar con productos reales del sitio</li>";
    echo "<li>🔍 Verificar que los precios se calculan correctamente</li>";
    echo "</ul>";
    echo "</div>";
}

/**
 * Simulate cart calculation (simplified version)
 */
function simulate_cart_calculation($cart_item) {
    $base_price = $cart_item['mwm_original_price'];
    $product_id = $cart_item['product_id'];
    
    // Simulate addons (in real implementation, this would come from YITH WAPO)
    $addons = array();
    
    // Simulate Olográfico addon
    $olografico_addon = new stdClass();
    $olografico_addon->id = 1;
    $olografico_addon->settings = array(
        'title' => 'Acabado',
        'calculate_on_total' => 'yes'
    );
    $olografico_addon->options = array(
        array('label' => 'Olográfico', 'price' => 238.00)
    );
    $addons[] = $olografico_addon;
    
    // Simulate Opaco addon
    $opaco_addon = new stdClass();
    $opaco_addon->id = 2;
    $opaco_addon->settings = array(
        'title' => 'Acabado',
        'calculate_on_total' => 'yes'
    );
    $opaco_addon->options = array(
        array('label' => 'Opaco', 'price' => 68.00)
    );
    $addons[] = $opaco_addon;
    
    // Simulate the calculation logic
    $selected_options = array();
    $cromato_olografico_options = array();
    $other_options = array();
    
    foreach ($addons as $addon) {
        if (isset($cart_item['yith_wapo_options'][$addon->id])) {
            $option_value = $cart_item['yith_wapo_options'][$addon->id];
            $option_price = 0;
            
            // Find price from options
            foreach ($addon->options as $option) {
                if (strtolower(trim($option['label'])) === strtolower(trim($option_value))) {
                    $option_price = floatval($option['price']);
                    break;
                }
            }
            
            $option_data = array(
                'addon' => $addon,
                'value' => $option_value,
                'price' => $option_price,
                'is_cromato_olografico' => (strpos(strtolower($option_value), 'olografico') !== false || strpos(strtolower($option_value), 'cromato') !== false)
            );
            
            $selected_options[] = $option_data;
            
            if ($option_data['is_cromato_olografico']) {
                $cromato_olografico_options[] = $option_data;
            } else {
                $other_options[] = $option_data;
            }
        }
    }
    
    // Apply the 70% logic
    $total_price = calculate_with_seventy_percent_logic($base_price, $cromato_olografico_options, $other_options);
    $applied_70_percent = !empty($cromato_olografico_options) && !empty($other_options);
    
    return array(
        'total_price' => $total_price,
        'applied_70_percent' => $applied_70_percent,
        'addons' => $addons,
        'selected_options' => $selected_options
    );
}

/**
 * Calculate price using the 70% logic (same as in the cart class)
 */
function calculate_with_seventy_percent_logic($base_price, $cromato_olografico_options, $other_options) {
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

// Run test if accessed directly
if (isset($_GET['test'])) {
    test_cart_calculation_logic();
}
?> 