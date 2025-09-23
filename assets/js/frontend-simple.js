/**
 * Frontend JavaScript for MWM Visual Teams - AJAX PRICE CALCULATION
 * This calls our PHP endpoint to calculate prices with 70% logic
 */

var MWMVisualTeams = {
    
    init: function() {
        this.bindEvents();
    },
    
    bindEvents: function() {
        var self = this;
        
        // Event listener for form changes
        $(document).on('change', 'select, input[type="radio"], input[type="checkbox"]', function() {
            self.showCalculationStatus();
            // Trigger price recalculation after a short delay
            setTimeout(function() {
                self.recalculatePrices();
            }, 100);
        });
    },
    
    showCalculationStatus: function() {
        // Simple status update
        var $status = $('#mwm-status-text');
        if ($status.length) {
            $status.text('🔄 Recalculando...');
            setTimeout(function() {
                $status.text('✅ Cálculo actualizado');
            }, 1000);
        }
    },
    
    recalculatePrices: function() {
        var self = this;
        
        console.log('MWM DEBUG: recalculatePrices called');
        
        // Get current product ID - try multiple methods
        var productId = $('input[name="add-to-cart"]').val() || 
                       $('input[name="product_id"]').val() || 
                       $('input[name="variation_id"]').val() ||
                       $('form.cart').data('product_id') ||
                       $('body').data('product_id');
        
        console.log('MWM DEBUG: Product ID found:', productId);
        
        if (!productId) {
            console.log('MWM DEBUG: No product ID found, trying to get from URL');
            // Try to get from URL
            var url = window.location.href;
            var match = url.match(/\/product\/([^\/]+)/);
            if (match) {
                // This might be a slug, not an ID, but let's try
                productId = match[1];
                console.log('MWM DEBUG: Product ID from URL:', productId);
            }
        }
        
        if (!productId) {
            console.log('MWM DEBUG: Still no product ID found');
            return;
        }
        
        // Get selected options
        var options = {};
        $('select, input[type="radio"]:checked, input[type="checkbox"]:checked').each(function() {
            var $this = $(this);
            var name = $this.attr('name');
            var value = $this.val();
            
            console.log('MWM DEBUG: Found input:', name, '=', value);
            
            if (name && value && name.includes('yith_wapo')) {
                // Extract addon ID from name (e.g., "yith_wapo_options[123]")
                var match = name.match(/yith_wapo_options\[(\d+)\]/);
                if (match) {
                    options[match[1]] = value;
                    console.log('MWM DEBUG: Added option:', match[1], '=', value);
                }
            }
        });
        
        console.log('MWM DEBUG: Final options:', options);
        
        if (Object.keys(options).length === 0) {
            console.log('MWM DEBUG: No YITH WAPO options found');
            return;
        }
        
        // Call our AJAX endpoint
        console.log('MWM DEBUG: Calling AJAX...');
        $.ajax({
            url: mwm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mwm_calculate_price',
                product_id: productId,
                options: options,
                nonce: mwm_ajax.nonce
            },
            success: function(response) {
                console.log('MWM DEBUG: AJAX success:', response);
                if (response.success) {
                    console.log('MWM DEBUG: Price calculated:', response.data.price);
                    self.updatePriceDisplay(response.data.price);
                } else {
                    console.log('MWM DEBUG: Error calculating price:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.log('MWM DEBUG: AJAX error:', error, xhr.responseText);
            }
        });
    },
    
    updatePriceDisplay: function(newPrice) {
        // Update price display elements
        $('.price .amount, .woocommerce-Price-amount, .price .woocommerce-Price-amount').each(function() {
            var $this = $(this);
            var currentText = $this.text();
            var newText = currentText.replace(/[\d,]+[.,]\d{2}/, newPrice.toFixed(2));
            $this.text(newText);
        });
        
        // Update total price if it exists
        $('.yith-wapo-total-price, .total-price').each(function() {
            $(this).text(newPrice.toFixed(2) + ' €');
        });
    }
};

// Simple initialization
jQuery(document).ready(function($) {
    console.log('MWM DEBUG: Script loaded and ready');
    console.log('MWM DEBUG: mwm_ajax available:', typeof mwm_ajax !== 'undefined');
    if (typeof mwm_ajax !== 'undefined') {
        console.log('MWM DEBUG: AJAX URL:', mwm_ajax.ajax_url);
    }
    MWMVisualTeams.init();
});
