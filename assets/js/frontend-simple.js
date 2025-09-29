/**
 * Frontend JavaScript for MWM Visual Teams - SIMPLE VERSION
 */

var MWMVisualTeamsSimple = {
    
    init: function() {
        this.bindEvents();
    },
    
    bindEvents: function() {
        var self = this;
        
        // Listen for YITH add-on changes
        $(document).on('change', 'input[name*="yith_wapo"], select[name*="yith_wapo"]', function() {
            setTimeout(function() {
                self.recalculatePrices();
            }, 100);
        });
        
        // Listen for quantity changes
        $(document).on('change', '.quantity input', function() {
            setTimeout(function() {
                self.recalculatePrices();
            }, 100);
        });
    },
    
    recalculatePrices: function() {
        var self = this;
        
        // Get current product ID - try multiple methods
        var productId = $('input[name="add-to-cart"]').val() || 
                       $('input[name="product_id"]').val() || 
                       $('input[name="variation_id"]').val() ||
                       $('form.cart').data('product_id') ||
                       $('body').data('product_id');
        
        if (!productId) {
            // Try to get from URL
            var url = window.location.href;
            var match = url.match(/\/product\/([^\/]+)/);
            if (match) {
                productId = match[1];
            }
        }
        
        if (!productId) {
            return;
        }
        
        // Get selected options
        var options = [];
        $('input[name*="yith_wapo"]:checked, select[name*="yith_wapo"]').each(function() {
            var $input = $(this);
            var name = $input.attr('name');
            var value = $input.val();
            
            if (name && value) {
                // Extract addon and option IDs
                var match = name.match(/yith_wapo.*?\[.*?(\d+)(?:-(\d+))?\]/);
                if (match) {
                    var addonId = match[1];
                    var optionId = match[2] || value;
                    
                    options.push({
                        addon_id: addonId,
                        option_id: optionId,
                        value: value
                    });
                }
            }
        });
        
        if (options.length === 0) {
            return;
        }
        
        // Make AJAX call
        $.ajax({
            url: mwm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mwm_calculate_addon_price_realtime',
                product_id: productId,
                selected_addons: options,
                nonce: mwm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    self.updatePriceDisplay(response.data);
                }
            },
            error: function() {
                // Silent error handling
            }
        });
    },
    
    updatePriceDisplay: function(data) {
        // Update main product price
        if (data.final_total) {
            $('.price .woocommerce-Price-amount .woocommerce-Price-amount').html(this.formatPrice(data.final_total));
        }
        
        // Update YITH WAPO totals
        if (data.total_addons_price) {
            $('.yith-wapo-total-options, .addon-total').html(this.formatPrice(data.total_addons_price));
        }
        
        if (data.final_total) {
            $('.yith-wapo-total-order, .order-total').html(this.formatPrice(data.final_total));
        }
    },
    
    formatPrice: function(price) {
        var symbol = mwm_ajax.currency_symbol || '€';
        var decimal = mwm_ajax.decimal_separator || ',';
        var thousand = mwm_ajax.thousand_separator || '.';
        var decimals = mwm_ajax.decimals || 2;
        
        return price.toFixed(decimals).replace('.', decimal).replace(/\B(?=(\d{3})+(?!\d))/g, thousand) + ' ' + symbol;
    }
};

// Initialize when document is ready
jQuery(document).ready(function($) {
    MWMVisualTeamsSimple.init();
});