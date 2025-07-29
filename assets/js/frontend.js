/**
 * Frontend JavaScript for MWM Visual Teams
 */

var MWMVisualTeams = {
    
    init: function() {
        this.bindEvents();
        this.initializeTotalCalculation();
    },
    
    bindEvents: function() {
        // Listen for YITH add-on changes
        $(document).on('change', '.yith-wapo-option', function() {
            MWMVisualTeams.handleOptionChange();
        });
        
        // Listen for quantity changes
        $(document).on('change', '.quantity input', function() {
            MWMVisualTeams.handleQuantityChange();
        });
    },
    
    initializeTotalCalculation: function() {
        // Initialize any existing options with total calculation
        this.updateTotalPrice();
    },
    
    handleOptionChange: function() {
        // Update price when options change
        this.updateTotalPrice();
    },
    
    handleQuantityChange: function() {
        // Update price when quantity changes
        this.updateTotalPrice();
    },
    
    updateTotalPrice: function() {
        var self = this;
        
        // Get current product data
        var productData = this.getProductData();
        
        if (!productData) {
            return;
        }
        
        // Make AJAX call to calculate new price
        $.ajax({
            url: mwm_visualteams.ajax_url,
            type: 'POST',
            data: {
                action: 'mwm_calculate_total_price',
                nonce: mwm_visualteams.nonce,
                product_id: productData.product_id,
                options: productData.options,
                quantity: productData.quantity
            },
            success: function(response) {
                if (response.success) {
                    self.updatePriceDisplay(response.data.new_price);
                }
            },
            error: function() {
                console.log('Error calculating total price');
            }
        });
    },
    
    getProductData: function() {
        var productId = $('input[name="add-to-cart"]').val();
        var quantity = $('.quantity input').val() || 1;
        var options = {};
        
        // Get selected YITH options
        $('.yith-wapo-option:checked').each(function() {
            var optionId = $(this).attr('name');
            var optionValue = $(this).val();
            options[optionId] = optionValue;
        });
        
        return {
            product_id: productId,
            quantity: quantity,
            options: options
        };
    },
    
    updatePriceDisplay: function(newPrice) {
        // Update price display
        var formattedPrice = this.formatPrice(newPrice);
        
        // Update main price
        $('.price .amount').text(formattedPrice);
        
        // Update any other price displays
        $('.woocommerce-Price-amount').each(function() {
            if ($(this).closest('.price').length) {
                $(this).text(formattedPrice);
            }
        });
    },
    
    formatPrice: function(price) {
        var symbol = mwm_visualteams.currency_symbol;
        var decimal = mwm_visualteams.decimal_separator;
        var thousand = mwm_visualteams.thousand_separator;
        var decimals = mwm_visualteams.decimals;
        
        return symbol + price.toFixed(decimals).replace('.', decimal).replace(/\B(?=(\d{3})+(?!\d))/g, thousand);
    }
};

// Initialize when document is ready
jQuery(document).ready(function($) {
    MWMVisualTeams.init();
}); 