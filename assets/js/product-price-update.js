jQuery(document).ready(function($) {
    console.log('MWM Product Price Update: Script loaded');
    
    // Check if mwmProductData is available
    if (typeof mwmProductData === 'undefined') {
        console.log('MWM: mwmProductData not available');
        return;
    }
    
    console.log('MWM: Product data available:', mwmProductData);
    console.log('MWM: Product ID:', mwmProductData.product_id);
    console.log('MWM: Base price:', mwmProductData.product_base_price);
    
    // Function to collect selected addons
    function collectSelectedAddons() {
        var selectedAddons = [];
        
        console.log('MWM: Collecting selected addons...');
        
        // Find all YITH WAPO addon inputs by name attribute
        $('input[name*="yith_wapo"], select[name*="yith_wapo"]').each(function() {
            var $input = $(this);
            var name = $input.attr('name');
            var value = $input.val();
            var isChecked = $input.is(':checked');
            var isSelect = $input.is('select');
            
            console.log('MWM: Found input:', name, 'value:', value, 'checked:', isChecked, 'isSelect:', isSelect);
            
            // For selects, check if the selected option has a price
            if (isSelect) {
                var $selectedOption = $input.find('option:selected');
                var hasPrice = $selectedOption.data('price') && parseFloat($selectedOption.data('price')) > 0;
                var priceMethod = $selectedOption.data('price-method');
                
                console.log('MWM: Select option data - price:', $selectedOption.data('price'), 'method:', priceMethod, 'hasPrice:', hasPrice);
                
                // Only process if it has a meaningful value and price
                if (value && value !== '' && value !== 'default' && hasPrice && priceMethod !== 'free') {
                    // Extract addon and option IDs from name like "yith_wapo[][21]"
                    var match = name.match(/yith_wapo.*?\[.*?(\d+)\]/);
                    if (match) {
                        var addonId = parseInt(match[1]);
                        var optionId = parseInt(value); // For selects, the value is the option ID
                        
                        selectedAddons.push({
                            addon_id: addonId,
                            option_id: optionId,
                            value: value
                        });
                        
                        console.log('MWM: Added select addon:', addonId, '-', optionId, '=', value);
                    }
                }
            } else {
                // For inputs (checkboxes, radio buttons), only process if checked and has value
                if (value && value !== '' && isChecked) {
                    // Extract addon and option IDs from name like "yith_wapo[][525-0]"
                    var match = name.match(/yith_wapo.*?\[.*?(\d+)-(\d+)\]/);
                    if (match) {
                        var addonId = parseInt(match[1]);
                        var optionId = parseInt(match[2]);
                        
                        selectedAddons.push({
                            addon_id: addonId,
                            option_id: optionId,
                            value: value
                        });
                        
                        console.log('MWM: Added input addon:', addonId, '-', optionId, '=', value);
                    }
                }
            }
        });
        
        console.log('MWM: Total selected addons:', selectedAddons.length);
        return selectedAddons;
    }
    
    // Function to update price display
    function updatePriceDisplay(data) {
        console.log('MWM: Updating price display with data:', data);
        
        // Update individual addon prices
        updateAddonPrices(data);
        
        // Update the summary table
        updateSummaryTable(data);
        
        // Update the main product price
        updateMainProductPrice(data);
    }
    
    
    // Function to update individual addon prices
    function updateAddonPrices(data) {
        // Update prices for addons using server data
        if (data.individual_addon_prices) {
            $('.yith-wapo-option').each(function() {
                var $option = $(this);
                var addonId = $option.data('addon-id');
                var $priceElement = $option.find('.option-price .woocommerce-Price-amount');
                
                if (addonId && $priceElement.length > 0 && data.individual_addon_prices[addonId]) {
                    var newPrice = data.individual_addon_prices[addonId];
                    if (newPrice > 0) {
                        $priceElement.html(formatPrice(newPrice));
                    }
                }
            });
        }
    }
    
    // Function to update the summary table
    function updateSummaryTable(data) {
        // Update product price
        $('#wapo-total-product-price .woocommerce-Price-amount').html(formatPrice(data.base_price));
        
        // Update total options price
        $('#wapo-total-options-price .woocommerce-Price-amount').html(formatPrice(data.total_addons_price));
        
        // Update total order price
        $('#wapo-total-order-price .woocommerce-Price-amount').html(formatPrice(data.final_total));
    }
    
    // Function to update the main product price
    function updateMainProductPrice(data) {
        // Update the main product price display
        $('.price .woocommerce-Price-amount .woocommerce-Price-amount').html(formatPrice(data.final_total));
        
        // Also update any other price displays that might exist
        $('.woocommerce-Price-amount .woocommerce-Price-amount').not('#wapo-total-product-price .woocommerce-Price-amount, #wapo-total-options-price .woocommerce-Price-amount, #wapo-total-order-price .woocommerce-Price-amount').html(formatPrice(data.final_total));
        
        console.log('MWM: Updated main product price to:', data.final_total);
    }
    
    
    // Helper function to format price
    function formatPrice(price) {
        return parseFloat(price).toFixed(2).replace('.', ',') + '&nbsp;<span class="woocommerce-Price-currencySymbol">€</span>';
    }
    
    // Function to calculate prices via AJAX
    function calculatePrices() {
        console.log('MWM: calculatePrices called');
        
        // Check if we have the required data
        if (!mwmProductData || !mwmProductData.product_id) {
            console.log('MWM: Product data not available');
            return;
        }
        
        var selectedAddons = collectSelectedAddons();
        
        if (selectedAddons.length === 0) {
            console.log('MWM: No addons selected, resetting to base price');
            // Reset to base price if no addons selected
            updatePriceDisplay({
                base_price: mwmProductData.product_base_price || 0,
                total_addons_price: 0,
                final_total: mwmProductData.product_base_price || 0,
                individual_addon_prices: {},
                formatted_prices: {
                    base_price: wc_price(mwmProductData.product_base_price || 0),
                    total_addons_price: wc_price(0),
                    final_total: wc_price(mwmProductData.product_base_price || 0)
                }
            });
            return;
        }
        
        console.log('MWM: Making AJAX call with addons:', selectedAddons);
        
        $.ajax({
            url: mwmProductData.ajax_url,
            type: 'POST',
            data: {
                action: 'mwm_calculate_addon_price_realtime',
                product_id: mwmProductData.product_id,
                selected_addons: selectedAddons,
                nonce: mwmProductData.nonce
            },
            success: function(response) {
                console.log('MWM: AJAX success:', response);
                if (response.success) {
                    updatePriceDisplay(response.data);
                } else {
                    console.log('MWM: Error calculating prices:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.log('MWM: AJAX error:', error, xhr.responseText);
            }
        });
    }
    
    // Helper function to format price (simplified version of wc_price)
    function wc_price(price) {
        return parseFloat(price).toFixed(2).replace('.', ',') + ' €';
    }
    
    // Debounce function to prevent too many calls
    var calculateTimeout;
    function debouncedCalculatePrices() {
        clearTimeout(calculateTimeout);
        calculateTimeout = setTimeout(calculatePrices, 300);
    }
    
    // Bind events to addon inputs - more specific selectors
    $(document).on('change', 'input[name*="yith_wapo"], select[name*="yith_wapo"]', function() {
        console.log('MWM: Addon selection changed on:', this);
        debouncedCalculatePrices();
    });
    
    // Also bind to click events for checkboxes and radio buttons
    $(document).on('click', 'input[type="checkbox"][name*="yith_wapo"], input[type="radio"][name*="yith_wapo"]', function() {
        console.log('MWM: Addon clicked:', this);
        debouncedCalculatePrices();
    });
    
    // Initial calculation only if we have product data
    if (mwmProductData && mwmProductData.product_id) {
        calculatePrices();
    }
});
