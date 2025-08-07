/**
 * Frontend JavaScript for MWM Visual Teams
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
    
    /**
     * Prevent YITH WAPO from causing page redirects
     */
    preventYithRedirects: function() {
        var self = this;
        
        // Check if total calculation is enabled before preventing redirects
        $.ajax({
            url: mwm_visualteams.ajax_url,
            type: 'POST',
            data: {
                action: 'mwm_check_total_calculation',
                nonce: mwm_visualteams.nonce
            },
            success: function(response) {
                if (response.success && response.data.enabled) {
                    self.setupRedirectPrevention();
                } else {
                    // NO prevenir redirecciones - dejar que YITH WAPO funcione normalmente
                }
            },
            error: function(xhr, status, error) {
                // NO prevenir redirecciones - dejar que YITH WAPO funcione normalmente
            }
        });
    },
    
    /**
     * Setup redirect prevention (only called when button is enabled)
     */
    setupRedirectPrevention: function() {
        // Prevent form submissions
        $(document).off('submit.mwm-yith-prevent').on('submit.mwm-yith-prevent', 'form', function(e) {
            var $form = $(this);
            var formAction = $form.attr('action') || '';
            var formMethod = $form.attr('method') || 'get';
            
            // If this is a YITH WAPO form or contains YITH elements, prevent submission
            if ($form.find('.yith-wapo-option, .yith-wapo-block').length > 0 || 
                formAction.includes('yith') || 
                $form.hasClass('yith-wapo-form')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
        
        // Prevent button clicks that might cause redirects
        $(document).off('click.mwm-yith-prevent').on('click.mwm-yith-prevent', 'button, input[type="submit"], input[type="button"]', function(e) {
            var $button = $(this);
            var buttonText = $button.text().toLowerCase();
            var $form = $button.closest('form');
            
            // If this button is in a YITH WAPO form or has YITH-related text, prevent click
            if ($form.find('.yith-wapo-option, .yith-wapo-block').length > 0 || 
                buttonText.includes('add to cart') || 
                buttonText.includes('aggiungi al carrello') ||
                $form.hasClass('yith-wapo-form')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
        
        // Prevent any AJAX calls that might cause page changes
        $(document).off('click.mwm-yith-ajax-prevent').on('click.mwm-yith-ajax-prevent', 'a[href*="add-to-cart"], a[href*="cart"]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
    },
    
    /**
     * Calculate 70% percentage on base product price
     */
    calculateSeventyPercent: function() {
        var self = this;
        var checkedOption = $('input[name^="mwm_option_"]:checked');
        
        if (checkedOption.length === 0) {
            // No option selected, reset display
            $('#mwm-calculation-details').html('Selecciona una opción para ver el cálculo...');
            $('#mwm-new-total-price').text('-');
            return;
        }
        
        var optionPrice = parseFloat(checkedOption.val());
        var optionName = checkedOption.data('option-name');
        var basePrice = window.mwmBasePrice || 0;
        
        if (basePrice <= 0) {
            return;
        }
        
        // Make AJAX call to calculate 70% percentage
        $.ajax({
            url: mwm_visualteams.ajax_url,
            type: 'POST',
            data: {
                action: 'mwm_calculate_seventy_percent',
                nonce: mwm_visualteams.nonce,
                base_price: basePrice,
                option_price: optionPrice,
                option_name: optionName
            },
            success: function(response) {
                if (response.success) {
                    self.displaySeventyPercentCalculation(response.data);
                }
            },
            error: function(xhr, status, error) {
                // Handle error silently
            }
        });
    },
    
    /**
     * Display 70% calculation results
     */
    displaySeventyPercentCalculation: function(data) {
        var details = '';
        details += '<strong>Opción seleccionada:</strong> ' + data.option_name + '<br>';
        details += '<strong>Precio de la opción:</strong> ' + this.formatPrice(data.option_price) + '<br>';
        details += '<strong>Precio base del producto:</strong> ' + this.formatPrice(data.base_price) + '<br>';
        details += '<br><em>Fórmula aplicada:</em><br>';
        details += '1. Precio base - Precio opción: ' + this.formatPrice(data.base_price) + ' - ' + this.formatPrice(data.option_price) + ' = ' + this.formatPrice(data.step1_result) + '<br>';
        details += '2. 70% del resultado: ' + this.formatPrice(data.step1_result) + ' × 0.70 = ' + this.formatPrice(data.percentage_amount) + '<br>';
        details += '3. Nuevo total: ' + this.formatPrice(data.step1_result) + ' + ' + this.formatPrice(data.percentage_amount) + ' = <span style="color: #0073aa; font-weight: bold;">' + this.formatPrice(data.new_total) + '</span>';
        
        $('#mwm-calculation-details').html(details);
        $('#mwm-new-total-price').text(this.formatPrice(data.new_total));
    },
    
    /**
     * Handle YITH option changes and apply 70% calculation if enabled
     */
    handleYithOptionChange: function() {
        var self = this;
        
        // Try multiple sources for base price
        var basePrice = window.mwmBasePrice || 0;
        
        if (basePrice <= 0) {
            // Try to get from WooCommerce price display
            var $priceElement = $('.price .amount, .woocommerce-Price-amount');
            if ($priceElement.length > 0) {
                var priceText = $priceElement.first().text();
                var priceMatch = priceText.match(/[0-9,]+\.?[0-9]*/);
                if (priceMatch) {
                    basePrice = parseFloat(priceMatch[0].replace(',', '.'));
                }
            }
        }
        
        if (basePrice <= 0) {
            return;
        }
        
        // Get selected YITH options - try multiple selectors
        var selectedOptions = [];
        
        // Method 1: Check for checked radio buttons and checkboxes
        $('input[type="radio"]:checked, input[type="checkbox"]:checked').each(function() {
            var $option = $(this);
            var $label = $option.closest('label');
            var optionName = '';
            var optionPrice = 0;
            
            // Try to get option name from label
            if ($label.length > 0) {
                optionName = $label.text().trim();
            } else {
                // Try to get from nearby text
                optionName = $option.closest('.yith-wapo-option, .addon-field').text().trim();
            }
            
            // Extract price from text
            var priceMatch = optionName.match(/\+([0-9,]+\.?[0-9]*)\s*€/);
            if (priceMatch) {
                optionPrice = parseFloat(priceMatch[1].replace(',', '.'));
                optionName = optionName.replace(/\+[0-9,]+\.?[0-9]*\s*€/, '').trim();
            }
            
            // Ahora capturamos TODAS las opciones seleccionadas, no solo Cromato/Olografico
            selectedOptions.push({
                name: optionName,
                originalPrice: optionPrice,
                element: $option
            });
        });
        
        // Method 2: Check specifically for YITH WAPO options
        if (selectedOptions.length === 0) {
            $('.yith-wapo-option:checked, input[name*="yith_wapo"]:checked').each(function() {
                var $option = $(this);
                var $label = $option.closest('label');
                var optionName = $label.text().trim();
                var optionPrice = 0;
                
                // Extract price from label text
                var priceMatch = optionName.match(/\+([0-9,]+\.?[0-9]*)\s*€/);
                if (priceMatch) {
                    optionPrice = parseFloat(priceMatch[1].replace(',', '.'));
                    optionName = optionName.replace(/\+[0-9,]+\.?[0-9]*\s*€/, '').trim();
                }
                
                // Ahora capturamos TODAS las opciones seleccionadas
                selectedOptions.push({
                    name: optionName,
                    originalPrice: optionPrice,
                    element: $option
                });
            });
        }
        
        // Method 3: Check for any checked inputs within SUPPORTI SPECIALI section
        if (selectedOptions.length === 0) {
            $('h3, h4, h5, h6, strong, b').each(function() {
                var $heading = $(this);
                if ($heading.text().toLowerCase().includes('supporti speciali')) {
                    $heading.closest('div').find('input:checked').each(function() {
                        var $option = $(this);
                        var $label = $option.closest('label');
                        var optionName = $label.text().trim();
                        var optionPrice = 0;
                        
                        // Extract price from label text
                        var priceMatch = optionName.match(/\+([0-9,]+\.?[0-9]*)\s*€/);
                        if (priceMatch) {
                            optionPrice = parseFloat(priceMatch[1].replace(',', '.'));
                            optionName = optionName.replace(/\+[0-9,]+\.?[0-9]*\s*€/, '').trim();
                        }
                        
                        // Ahora capturamos TODAS las opciones seleccionadas
                        selectedOptions.push({
                            name: optionName,
                            originalPrice: optionPrice,
                            element: $option
                        });
                    });
                }
            });
        }
        
        // Check if we have at least one main option (Cromato/Olografico)
        var hasMainOption = selectedOptions.some(function(option) {
            return option.name.toLowerCase().includes('cromato') || option.name.toLowerCase().includes('olografico');
        });
        
        if (hasMainOption) {
            $.ajax({
                url: mwm_visualteams.ajax_url,
                type: 'POST',
                data: {
                    action: 'mwm_check_total_calculation',
                    nonce: mwm_visualteams.nonce
                },
                success: function(response) {
                    if (response.success && response.data.enabled) {
                        self.applySeventyPercentCalculation(selectedOptions, basePrice);
                    } else {
                        // NO hacer nada - dejar que YITH WAPO maneje los precios normalmente
                        return;
                    }
                },
                error: function(xhr, status, error) {
                    // NO hacer nada - dejar que YITH WAPO maneje los precios normalmente
                    return;
                }
            });
        } else {
            // NO hacer nada - dejar que YITH WAPO maneje los precios normalmente
            return;
        }
    },
    
    /**
     * Apply 70% calculation to selected options
     */
    applySeventyPercentCalculation: function(selectedOptions, basePrice) {
        // Separar Cromato/Olográfico de otras opciones
        var cromatoOlograficoOptions = [];
        var otherOptions = [];
        
        selectedOptions.forEach(function(option) {
            if (option.name.toLowerCase().includes('cromato') || option.name.toLowerCase().includes('olografico')) {
                cromatoOlograficoOptions.push(option);
            } else {
                otherOptions.push(option);
            }
        });
        
        // Si no hay Cromato/Olográfico, aplicar cálculo normal
        if (cromatoOlograficoOptions.length === 0) {
            this.applyNormalCalculation(selectedOptions, basePrice);
            return;
        }
        
        // Si solo hay Cromato/Olográfico (sin otras opciones), aplicar suma normal
        if (otherOptions.length === 0) {
            var totalPrice = basePrice;
            cromatoOlograficoOptions.forEach(function(option) {
                totalPrice += option.originalPrice;
            });
            
            var optionsTotal = totalPrice - basePrice;
            this.updatePriceDisplay(basePrice, optionsTotal, totalPrice);
            return;
        }
        
        // Si hay Cromato/Olográfico + otras opciones, aplicar 70%
        // Paso 1: Sumar todo (base + Cromato/Olográfico + otras opciones)
        var step1_total = basePrice;
        cromatoOlograficoOptions.forEach(function(option) {
            step1_total += option.originalPrice;
        });
        otherOptions.forEach(function(option) {
            step1_total += option.originalPrice;
        });
        
        // Paso 2: Restar Cromato/Olográfico
        var cromatoOlograficoTotal = 0;
        cromatoOlograficoOptions.forEach(function(option) {
            cromatoOlograficoTotal += option.originalPrice;
        });
        var step2_without_cromato = step1_total - cromatoOlograficoTotal;
        
        // Paso 3: Calcular 70% del resultado
        var step3_percentage = step2_without_cromato * 0.70;
        
        // Paso 4: Sumar las otras opciones
        var otherOptionsTotal = 0;
        otherOptions.forEach(function(option) {
            otherOptionsTotal += option.originalPrice;
        });
        var finalTotal = step3_percentage + otherOptionsTotal;
        
        // Actualizar la interfaz
        this.updatePriceDisplay(basePrice, finalTotal, basePrice + finalTotal);
    },
    
    /**
     * Apply normal calculation to selected options
     */
    applyNormalCalculation: function(selectedOptions, basePrice) {
        var totalOptionsPrice = 0;
        
        selectedOptions.forEach(function(option) {
            totalOptionsPrice += option.originalPrice;
        });
        
        var newTotalPrice = basePrice + totalOptionsPrice;
        this.updatePriceDisplay(basePrice, totalOptionsPrice, newTotalPrice);
    },
    
    /**
     * Update price display in Totale opzioni and Totale ordine
     */
    updatePriceDisplay: function(basePrice, optionsPrice, totalPrice) {
        var formattedOptionsPrice = this.formatPrice(optionsPrice);
        var formattedTotalPrice = this.formatPrice(totalPrice);
        
        // Update Totale opzioni - mostrar el precio de las opciones calculado
        var $optionsElement = $('#wapo-total-options-price');
        if ($optionsElement.length > 0) {
            $optionsElement.text(formattedOptionsPrice);
        }
        
        // Update Totale ordine - mostrar el precio total del pedido
        var $totalElement = $('#wapo-total-order-price');
        if ($totalElement.length > 0) {
            $totalElement.text(formattedTotalPrice);
        }
        
        // Also try alternative selectors in case the IDs are different
        var $alternativeOptions = $('.wapo-total-options td, .totale-opzioni, .total-options, [data-total-options]');
        if ($alternativeOptions.length > 0 && $optionsElement.length === 0) {
            $alternativeOptions.each(function() {
                $(this).text(formattedOptionsPrice);
            });
        }
        
        var $alternativeTotal = $('.wapo-total-order td, .totale-ordine, .total-order, [data-total-order]');
        if ($alternativeTotal.length > 0 && $totalElement.length === 0) {
            $alternativeTotal.each(function() {
                $(this).text(formattedTotalPrice);
            });
        }
        
        // También intentar con selectores más genéricos de YITH WAPO
        $('.yith-wapo-total-options, .addon-total').each(function() {
            $(this).text(formattedOptionsPrice);
        });
        
        $('.yith-wapo-total-order, .order-total').each(function() {
            $(this).text(formattedTotalPrice);
        });
    },
    
    /**
     * Detect YITH options from the page and create dynamic checkboxes
     */
    detectYithOptions: function() {
        var self = this;
        var detectedOptions = [];
        
        // Look specifically for SUPPORTI SPECIALI section on current page only
        $('h3, h4, h5, h6, strong, b').each(function() {
            var $heading = $(this);
            if ($heading.text().toLowerCase().includes('supporti speciali')) {
                // Look for Cromato and Olografico options specifically within this section
                var $section = $heading.closest('div, section, .yith-wapo-block');
                if ($section.length === 0) {
                    $section = $heading.parent();
                }
                
                $section.find('label').each(function() {
                    var $label = $(this);
                    var labelText = $label.text().trim();
                    
                    // Check for Cromato
                    if (labelText.toLowerCase().includes('cromato')) {
                        var priceMatch = labelText.match(/\+([0-9,]+\.?[0-9]*)\s*€/);
                        if (priceMatch) {
                            var optionPrice = parseFloat(priceMatch[1].replace(',', '.'));
                            detectedOptions.push({
                                id: 'cromato',
                                name: 'Cromato',
                                price: optionPrice,
                                element: $label.find('input')
                            });
                        }
                    }
                    
                    // Check for Olografico
                    if (labelText.toLowerCase().includes('olografico')) {
                        var priceMatch = labelText.match(/\+([0-9,]+\.?[0-9]*)\s*€/);
                        if (priceMatch) {
                            var optionPrice = parseFloat(priceMatch[1].replace(',', '.'));
                            detectedOptions.push({
                                id: 'olografico',
                                name: 'Olografico',
                                price: optionPrice,
                                element: $label.find('input')
                            });
                        }
                    }
                });
                
                // Also check for any input elements directly
                $section.find('input[type="radio"], input[type="checkbox"]').each(function() {
                    var $input = $(this);
                    var $label = $input.closest('label');
                    var labelText = $label.text().trim();
                    
                    // Check for Cromato
                    if (labelText.toLowerCase().includes('cromato')) {
                        var priceMatch = labelText.match(/\+([0-9,]+\.?[0-9]*)\s*€/);
                        if (priceMatch) {
                            var optionPrice = parseFloat(priceMatch[1].replace(',', '.'));
                            // Check if we already have this option
                            var exists = detectedOptions.some(function(opt) {
                                return opt.name === 'Cromato' && opt.price === optionPrice;
                            });
                            if (!exists) {
                                detectedOptions.push({
                                    id: 'cromato',
                                    name: 'Cromato',
                                    price: optionPrice,
                                    element: $input
                                });
                            }
                        }
                    }
                    
                    // Check for Olografico
                    if (labelText.toLowerCase().includes('olografico')) {
                        var priceMatch = labelText.match(/\+([0-9,]+\.?[0-9]*)\s*€/);
                        if (priceMatch) {
                            var optionPrice = parseFloat(priceMatch[1].replace(',', '.'));
                            // Check if we already have this option
                            var exists = detectedOptions.some(function(opt) {
                                return opt.name === 'Olografico' && opt.price === optionPrice;
                            });
                            if (!exists) {
                                detectedOptions.push({
                                    id: 'olografico',
                                    name: 'Olografico',
                                    price: optionPrice,
                                    element: $input
                                });
                            }
                        }
                    }
                });
            }
        });
        
        // If we found the specific options, create dynamic checkboxes
        if (detectedOptions.length > 0) {
            self.createDynamicCheckboxes(detectedOptions);
        }
    },
    
    /**
     * Create dynamic checkboxes for detected options
     */
    createDynamicCheckboxes: function(options) {
        var container = $('.mwm-provisional-checkboxes');
        var existingCheckboxes = container.find('.mwm-checkbox-group');
        
        // Remove existing fallback checkboxes
        existingCheckboxes.remove();
        
        // Add detected options (only Cromato and Olografico)
        options.forEach(function(option, index) {
            var checkboxHtml = '<div class="mwm-checkbox-group" style="margin-bottom: 10px;">' +
                '<label style="display: block; margin-bottom: 5px;">' +
                '<input type="checkbox" name="mwm_option_' + option.id + '" ' +
                'value="' + option.price + '" ' +
                'data-option-name="' + option.name + '" ' +
                'style="margin-right: 8px;">' +
                '<strong>' + option.name + ' (+' + this.formatPrice(option.price) + ')</strong> - Aplicar 70% (detectado de SUPPORTI SPECIALI)' +
                '</label>' +
                '</div>';
            
            container.find('.mwm-calculation-results').before(checkboxHtml);
        }.bind(this));
        
        // Re-bind events
        $('input[name^="mwm_option_"]').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $('input[name^="mwm_option_"]').not(this).prop('checked', false);
            }
            MWMVisualTeams.calculateSeventyPercent();
        });
        
        // Show success message
        var successMsg = '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px;">' +
            '<p style="margin: 0; color: #155724;">✅ Se detectaron las opciones de SUPPORTI SPECIALI automáticamente:</p>' +
            '<ul style="margin: 5px 0 0 20px; color: #155724;">';
        
        options.forEach(function(option) {
            successMsg += '<li>' + option.name + ' (+' + this.formatPrice(option.price) + ')</li>';
        }.bind(this));
        
        successMsg += '</ul></div>';
        
        container.find('h3').after(successMsg);
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