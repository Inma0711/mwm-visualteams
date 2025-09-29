/**
 * Frontend JavaScript for MWM Visual Teams - SIMPLIFIED
 */

var MWMVisualTeams = {
    
    init: function() {
        this.bindEvents();
    },
    
    bindEvents: function() {
        var self = this;
        
        // Prevent YITH WAPO from causing page redirects
        this.preventYithRedirects();
        
        // Listen for YITH add-on changes
        $(document).on('change', '.yith-wapo-option', function(e) {
            // Prevent default behavior that might cause redirect
            e.preventDefault();
            e.stopPropagation();
            setTimeout(function() {
                self.handleYithOptionChange();
            }, 100);
        });
        
        // Listen for any input changes that might be YITH options
        $(document).on('change', 'input[type="radio"], input[type="checkbox"]', function(e) {
            var $input = $(this);
            var $label = $input.closest('label');
            var labelText = $label.text().trim();
            
            // Check if this is a Cromato or Olografico option
            if (labelText.toLowerCase().includes('cromato') || labelText.toLowerCase().includes('olografico')) {
                // Prevent default behavior
                e.preventDefault();
                e.stopPropagation();
                setTimeout(function() {
                    self.handleYithOptionChange();
                }, 100);
            }
        });
        
        // Listen for clicks on labels that might contain Cromato or Olografico
        $(document).on('click', 'label', function(e) {
            var $label = $(this);
            var labelText = $label.text().trim();
            
            if (labelText.toLowerCase().includes('cromato') || labelText.toLowerCase().includes('olografico')) {
                // Prevent default behavior
                e.preventDefault();
                e.stopPropagation();
                setTimeout(function() {
                    self.handleYithOptionChange();
                }, 200);
            }
        });
        
        // Listen for quantity changes
        $(document).on('change', '.quantity input', function() {
            setTimeout(function() {
                self.handleYithOptionChange();
            }, 100);
        });
        
        // Listen for MODELLO CAN AM UTV selector changes
        $(document).on('change', 'select, input', function() {
            var $element = $(this);
            var $label = $element.closest('label, .form-group, .field-group').find('label, strong, b').first();
            var labelText = $label.text().trim().toUpperCase();
            
            if (labelText.includes('MODELLO') && labelText.includes('CAN AM') && labelText.includes('UTV')) {
                setTimeout(function() {
                    self.handleYithOptionChange();
                }, 100);
            }
        });
        
        // Also listen for any changes in the SUPPORTI SPECIALI section
        $(document).on('change', 'input', function(e) {
            var $input = $(this);
            var $section = $input.closest('div, section');
            
            // Check if this input is within a section that contains "SUPPORTI SPECIALI"
            if ($section.length > 0) {
                var sectionText = $section.text().toLowerCase();
                if (sectionText.includes('supporti speciali')) {
                    // Prevent default behavior
                    e.preventDefault();
                    e.stopPropagation();
                    setTimeout(function() {
                        self.handleYithOptionChange();
                    }, 100);
                }
            }
        });
    },
    
    preventYithRedirects: function() {
        // Prevent YITH WAPO from causing page redirects
        $(document).on('click', '.yith-wapo-option input, .yith-wapo-option select', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
        
        // Prevent form submission that might cause redirects
        $(document).on('submit', 'form', function(e) {
            var $form = $(this);
            if ($form.find('.yith-wapo-option').length > 0) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    },
    
    handleYithOptionChange: function() {
        var self = this;
        
        // Get the current product ID
        var productId = this.getCurrentProductId();
        if (!productId) {
            return;
        }
        
        // Get selected options
        var selectedOptions = this.getSelectedOptions();
        
        // Calculate new price
        this.calculateNewPrice(productId, selectedOptions);
    },
    
    getCurrentProductId: function() {
        // Try to get product ID from various sources
        var productId = null;
        
        // Check if we have it in a data attribute
        if ($('body').data('product-id')) {
            productId = $('body').data('product-id');
        }
        
        // Check if we have it in a meta tag
        if (!productId && $('meta[name="product-id"]').length) {
            productId = $('meta[name="product-id"]').attr('content');
        }
        
        // Check if we have it in the URL
        if (!productId) {
            var urlMatch = window.location.href.match(/\/product\/([^\/]+)/);
            if (urlMatch) {
                productId = urlMatch[1];
            }
        }
        
        // Check if we have it in a hidden input
        if (!productId && $('input[name="add-to-cart"]').length) {
            productId = $('input[name="add-to-cart"]').val();
        }
        
        return productId;
    },
    
    getSelectedOptions: function() {
        var selectedOptions = [];
        
        // Get all checked radio buttons and checkboxes
        $('input[type="radio"]:checked, input[type="checkbox"]:checked').each(function() {
            var $input = $(this);
            var $label = $input.closest('label');
            var labelText = $label.text().trim();
            
            // Check if this is a Cromato or Olografico option
            if (labelText.toLowerCase().includes('cromato') || labelText.toLowerCase().includes('olografico')) {
                selectedOptions.push({
                    name: labelText,
                    value: $input.val(),
                    element: $input
                });
            }
        });
        
        return selectedOptions;
    },
    
    calculateNewPrice: function(productId, selectedOptions) {
        var self = this;
        
        // Make AJAX call to calculate new price
        $.ajax({
            url: mwm_visualteams.ajax_url,
            type: 'POST',
            data: {
                action: 'mwm_calculate_seventy_percent',
                product_id: productId,
                selected_options: selectedOptions,
                nonce: mwm_visualteams.nonce
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
        // Update the main product price
        if (data.new_price) {
            $('.price .woocommerce-Price-amount .woocommerce-Price-amount').html(this.formatPrice(data.new_price));
        }
        
        // Update any other price displays
        $('.woocommerce-Price-amount .woocommerce-Price-amount').not('.price .woocommerce-Price-amount .woocommerce-Price-amount').html(this.formatPrice(data.new_price));
        
        // Update YITH WAPO totals if they exist
        if (data.options_price) {
            $('.yith-wapo-total-options, .addon-total').html(this.formatPrice(data.options_price));
        }
        
        if (data.total_price) {
            $('.yith-wapo-total-order, .order-total').html(this.formatPrice(data.total_price));
        }
    },
    
    formatPrice: function(price) {
        var symbol = mwm_visualteams.currency_symbol;
        var decimal = mwm_visualteams.decimal_separator;
        var thousand = mwm_visualteams.thousand_separator;
        var decimals = mwm_visualteams.decimals;
        
        // CORRECCIÓN: Poner el símbolo de la moneda a la derecha
        return price.toFixed(decimals).replace('.', decimal).replace(/\B(?=(\d{3})+(?!\d))/g, thousand) + ' ' + symbol;
    }
};

// Initialize when document is ready
jQuery(document).ready(function($) {
    MWMVisualTeams.init();
});