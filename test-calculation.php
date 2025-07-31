<?php
/**
 * Test file for 70% calculation verification
 * This file can be accessed directly to test the calculation logic
 */

// Prevent direct access in production
if (!defined('ABSPATH')) {
    // Allow direct access for testing
    if (!isset($_GET['test_calculation'])) {
        exit('Direct access not allowed');
    }
}

/**
 * Test the 70% calculation function
 */
function test_seventy_percent_calculation() {
    echo "<h1>🧮 Test de Cálculo 70% - MWM Visual Teams</h1>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-case { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .calculation { background: white; padding: 10px; margin: 10px 0; border-radius: 3px; font-family: monospace; }
        .result { font-weight: bold; color: #0073aa; }
    </style>";
    
    // Test Case 1: Example from image (CANBLACK P)
    echo "<div class='test-case'>";
    echo "<h2>📋 Test Case 1: Ejemplo de la Imagen (CANBLACK P)</h2>";
    echo "<p><strong>Datos:</strong></p>";
    echo "<ul>";
    echo "<li>Precio base del producto: 340,00 €</li>";
    echo "<li>Precio de la opción: 238,00 €</li>";
    echo "<li>Porcentaje: 70%</li>";
    echo "</ul>";
    
    $base_price = 340.00;
    $option_price = 238.00;
    $percentage = 70;
    
    $result = calculate_seventy_percent($base_price, $option_price, $percentage);
    
    echo "<div class='calculation'>";
    echo "<strong>Paso 1:</strong> Precio base - Precio opción: {$base_price}€ - {$option_price}€ = " . $result['step1_result'] . "€<br>";
    echo "<strong>Paso 2:</strong> {$percentage}% del resultado: " . $result['step1_result'] . "€ × 0.70 = " . $result['percentage_amount'] . "€<br>";
    echo "<strong>Paso 3:</strong> Nuevo total: " . $result['step1_result'] . "€ + " . $result['percentage_amount'] . "€ = " . $result['new_total'] . "€<br>";
    echo "</div>";
    
    $expected_total = 173.40;
    $success = abs($result['new_total'] - $expected_total) < 0.01;
    
    echo "<div class='" . ($success ? 'success' : 'error') . "'>";
    echo "<strong>Resultado esperado:</strong> {$expected_total}€<br>";
    echo "<strong>Resultado calculado:</strong> " . $result['new_total'] . "€<br>";
    echo "<strong>Estado:</strong> " . ($success ? '✅ CORRECTO' : '❌ INCORRECTO');
    echo "</div>";
    echo "</div>";
    
    // Test Case 2: Example with different values
    echo "<div class='test-case'>";
    echo "<h2>📋 Test Case 2: Otro Ejemplo</h2>";
    echo "<p><strong>Datos:</strong></p>";
    echo "<ul>";
    echo "<li>Precio base del producto: 500,00 €</li>";
    echo "<li>Precio de la opción: 100,00 €</li>";
    echo "<li>Porcentaje: 70%</li>";
    echo "</ul>";
    
    $base_price = 500.00;
    $option_price = 100.00;
    $percentage = 70;
    
    $result = calculate_seventy_percent($base_price, $option_price, $percentage);
    
    echo "<div class='calculation'>";
    echo "<strong>Paso 1:</strong> Precio base - Precio opción: {$base_price}€ - {$option_price}€ = " . $result['step1_result'] . "€<br>";
    echo "<strong>Paso 2:</strong> {$percentage}% del resultado: " . $result['step1_result'] . "€ × 0.70 = " . $result['percentage_amount'] . "€<br>";
    echo "<strong>Paso 3:</strong> Nuevo total: " . $result['step1_result'] . "€ + " . $result['percentage_amount'] . "€ = " . $result['new_total'] . "€<br>";
    echo "</div>";
    
    $expected_total = 680.00; // 400 + (400 * 0.70) = 400 + 280 = 680
    $success = abs($result['new_total'] - $expected_total) < 0.01;
    
    echo "<div class='" . ($success ? 'success' : 'error') . "'>";
    echo "<strong>Resultado esperado:</strong> {$expected_total}€<br>";
    echo "<strong>Resultado calculado:</strong> " . $result['new_total'] . "€<br>";
    echo "<strong>Estado:</strong> " . ($success ? '✅ CORRECTO' : '❌ INCORRECTO');
    echo "</div>";
    echo "</div>";
}

/**
 * Calculate 70% percentage on base product price
 * 
 * @param float $base_price Precio base del producto
 * @param float $option_price Precio de la opción
 * @param float $percentage Porcentaje a aplicar (default 70)
 * @return array Resultado del cálculo
 */
function calculate_seventy_percent($base_price, $option_price, $percentage = 70) {
    // Paso 1: Restar el precio de la opción del precio base del producto
    $step1_result = $base_price - $option_price;
    
    // Paso 2: Calcular el porcentaje del resultado anterior
    $percentage_amount = ($step1_result * $percentage) / 100;
    
    // Paso 3: Calcular el nuevo total
    $new_total = $step1_result + $percentage_amount;
    
    return array(
        'base_price' => $base_price,
        'option_price' => $option_price,
        'step1_result' => $step1_result,
        'percentage_amount' => $percentage_amount,
        'new_total' => $new_total,
        'percentage' => $percentage
    );
}

// Run test if accessed directly
if (isset($_GET['test_calculation'])) {
    test_seventy_percent_calculation();
}
?> 