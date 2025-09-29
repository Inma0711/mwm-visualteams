/**
 * Admin JavaScript for MWM Visual Teams
 */

jQuery(document).ready(function($) {
    
    // Initialize admin functionality
    initMWMVisualTeamsAdmin();
    
    function initMWMVisualTeamsAdmin() {
        
        // Handle the new total price calculation checkbox
        $(document).on('change', 'input[name="calculate_on_total"]', function() {
            var isChecked = $(this).is(':checked');
            var fieldWrap = $(this).closest('.option-total-calculation');
            
            if (isChecked) {
                fieldWrap.addClass('enabled');
                showTotalCalculationInfo(fieldWrap);
            } else {
                fieldWrap.removeClass('enabled');
                hideTotalCalculationInfo(fieldWrap);
            }
        });
        
        // Show info when total calculation is enabled
        function showTotalCalculationInfo(fieldWrap) {
            if (fieldWrap.find('.total-calculation-info').length === 0) {
                var info = $('<div class="total-calculation-info" style="margin-top: 10px; padding: 10px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px; color: #0066cc;">' +
                    '<strong>Informazione:</strong> Questa opzione calcolerà il prezzo sul totale del prodotto (escludendo questa opzione) invece del prezzo base.' +
                    '</div>');
                fieldWrap.append(info);
            }
        }
        
        // Hide info when total calculation is disabled
        function hideTotalCalculationInfo(fieldWrap) {
            fieldWrap.find('.total-calculation-info').remove();
        }
        
        // Initialize existing checkboxes
        $('input[name="calculate_on_total"]:checked').each(function() {
            var fieldWrap = $(this).closest('.option-total-calculation');
            fieldWrap.addClass('enabled');
            showTotalCalculationInfo(fieldWrap);
        });
        
        // Add validation for percentage fields when total calculation is enabled
        $(document).on('change', 'input[name="calculate_on_total"]', function() {
            var isChecked = $(this).is(':checked');
            var optionCostField = $(this).closest('.addon-field-grid').find('.option-cost');
            
            if (isChecked) {
                // Check if percentage is set
                var percentageField = optionCostField.find('input[type="number"]');
                if (percentageField.length > 0 && percentageField.val() === '') {
                    alert('Per utilizzare il calcolo sul prezzo totale, devi configurare una percentuale nel campo "Costo opzione".');
                    $(this).prop('checked', false);
                    return false;
                }
            }
        });
    }
}); 