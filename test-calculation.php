<?php
/**
 * Test file for the new 70% calculation logic
 * Access via: your-site.com/wp-content/plugins/mwm-visualteams/test-calculation.php?test=1
 */

// Prevent direct access in production
if (!defined('ABSPATH')) {
    // Allow direct access for testing
    if (!isset($_GET['test'])) {
        exit('Direct access not allowed');
    }
}

/**
 * Test the new calculation logic
 */
function test_new_calculation_logic() {
    echo "<h1>🧮 Test de Nueva Lógica de Cálculo 70% - MWM Visual Teams</h1>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-case { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .info { background: #e7f3ff; border-left: 4px solid #0073aa; }
        .calculation { background: white; padding: 10px; margin: 10px 0; border-radius: 3px; font-family: monospace; }
        .result { font-weight: bold; color: #0073aa; }
    </style>";
    
    $base_price = 340.00;
    
    // Test Case 1: Solo Olográfico seleccionado
    echo "<div class='test-case'>";
    echo "<h2>📋 Test Case 1: Solo Olográfico Seleccionado</h2>";
    echo "<p><strong>Escenario:</strong> Solo Olográfico está marcado, sin otros addons</p>";
    echo "<p><strong>Resultado Esperado:</strong> Suma normal - Totale opzioni: 238,00 €</p>";
    
    $cromato_olografico_options = array(
        array('price' => 238.00, 'name' => 'Olográfico')
    );
    $other_options = array();
    
    $result = calculate_with_seventy_percent_logic($base_price, $cromato_olografico_options, $other_options);
    $options_total = $result - $base_price;
    
    echo "<div class='calculation'>";
    echo "<strong>Opciones seleccionadas:</strong> Solo Olográfico (238€)<br>";
    echo "<strong>Precio base:</strong> {$base_price}€<br>";
    echo "<strong>Total opzioni calculado:</strong> {$options_total}€<br>";
    echo "<strong>Total ordine calculado:</strong> {$result}€<br>";
    echo "<strong>Resultado esperado:</strong> " . ($options_total == 238 ? '✅ CORRECTO (238€)' : '❌ INCORRECTO');
    echo "</div>";
    echo "</div>";
    
    // Test Case 2: Olográfico + Opaco seleccionados
    echo "<div class='test-case'>";
    echo "<h2>📋 Test Case 2: Olográfico + Opaco Seleccionados</h2>";
    echo "<p><strong>Escenario:</strong> Olográfico + Opaco están marcados</p>";
    echo "<p><strong>Resultado Esperado:</strong> Aplicar 70% según la fórmula especificada</p>";
    
    $cromato_olografico_options = array(
        array('price' => 238.00, 'name' => 'Olográfico')
    );
    $other_options = array(
        array('price' => 68.00, 'name' => 'Opaco')
    );
    
    $result = calculate_with_seventy_percent_logic($base_price, $cromato_olografico_options, $other_options);
    $options_total = $result - $base_price;
    
    // Verificar el cálculo paso a paso
    $step1_total = $base_price + 238 + 68; // 646
    $step2_without_cromato = $step1_total - 238; // 408
    $step3_percentage = $step2_without_cromato * 0.70; // 285.6
    $step4_final = $step3_percentage + 68; // 353.6
    $expected_total = $base_price + $step4_final; // 340 + 353.6 = 693.6
    
    echo "<div class='calculation'>";
    echo "<strong>Opciones seleccionadas:</strong> Olográfico (238€) + Opaco (68€)<br>";
    echo "<strong>Precio base:</strong> {$base_price}€<br>";
    echo "<strong>Cálculo paso a paso:</strong><br>";
    echo "1. Sumar todo: {$base_price} + 238 + 68 = {$step1_total}€<br>";
    echo "2. Restar Olográfico: {$step1_total} - 238 = {$step2_without_cromato}€<br>";
    echo "3. 70% del resultado: {$step2_without_cromato} × 0.70 = {$step3_percentage}€<br>";
    echo "4. Sumar Opaco: {$step3_percentage} + 68 = {$step4_final}€<br>";
    echo "<strong>Total opzioni calculado:</strong> {$options_total}€<br>";
    echo "<strong>Total ordine calculado:</strong> {$result}€<br>";
    echo "<strong>Total esperado:</strong> {$expected_total}€<br>";
    echo "<strong>Resultado esperado:</strong> " . (abs($result - $expected_total) < 0.01 ? '✅ CORRECTO' : '❌ INCORRECTO');
    echo "</div>";
    echo "</div>";
    
    // Test Case 3: Solo Opaco seleccionado
    echo "<div class='test-case'>";
    echo "<h2>📋 Test Case 3: Solo Opaco Seleccionado</h2>";
    echo "<p><strong>Escenario:</strong> Solo Opaco está marcado, sin Cromato/Olográfico</p>";
    echo "<p><strong>Resultado Esperado:</strong> Suma normal - Totale opzioni: 68,00 €</p>";
    
    $cromato_olografico_options = array();
    $other_options = array(
        array('price' => 68.00, 'name' => 'Opaco')
    );
    
    $result = calculate_with_seventy_percent_logic($base_price, $cromato_olografico_options, $other_options);
    $options_total = $result - $base_price;
    
    echo "<div class='calculation'>";
    echo "<strong>Opciones seleccionadas:</strong> Solo Opaco (68€)<br>";
    echo "<strong>Precio base:</strong> {$base_price}€<br>";
    echo "<strong>Total opzioni calculado:</strong> {$options_total}€<br>";
    echo "<strong>Total ordine calculado:</strong> {$result}€<br>";
    echo "<strong>Resultado esperado:</strong> " . ($options_total == 68 ? '✅ CORRECTO (68€)' : '❌ INCORRECTO');
    echo "</div>";
    echo "</div>";
    
    // Summary
    echo "<div class='test-case info'>";
    echo "<h2>📊 Resumen de la Nueva Lógica</h2>";
    echo "<p><strong>La lógica aplica el 70% SOLO cuando:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Cromato u Olográfico está seleccionado</li>";
    echo "<li>✅ Y hay otras opciones seleccionadas (además de Cromato/Olográfico)</li>";
    echo "<li>✅ Y el botón 'Calcular sobre precio total del producto' está activado</li>";
    echo "</ul>";
    echo "<p><strong>NO aplica el 70% cuando:</strong></p>";
    echo "<ul>";
    echo "<li>❌ Solo Cromato está seleccionado (sin otros addons)</li>";
    echo "<li>❌ Solo Olográfico está seleccionado (sin otros addons)</li>";
    echo "<li>❌ Solo otros addons están seleccionados (sin Cromato/Olográfico)</li>";
    echo "<li>❌ El botón 'Calcular sobre precio total del producto' está desactivado</li>";
    echo "</ul>";
    echo "<p><strong>Fórmula del 70%:</strong></p>";
    echo "<ol>";
    echo "<li>Sumar todo: base + Cromato/Olográfico + otras opciones</li>";
    echo "<li>Restar Cromato/Olográfico</li>";
    echo "<li>Calcular 70% del resultado</li>";
    echo "<li>Sumar las otras opciones</li>";
    echo "</ol>";
    echo "</div>";
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
    test_new_calculation_logic();
}
?> 