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
            console.log('YITH WAPO option change detected');
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
                console.log('Cromato/Olografico option change detected:', labelText);
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
                console.log('Cromato/Olografico label clicked:', labelText);
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
            console.log('Quantity change detected');
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
                    console.log('Input change detected in SUPPORTI SPECIALI section');
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
        // Prevent form submissions
        $(document).off('submit.mwm-yith-prevent').on('submit.mwm-yith-prevent', 'form', function(e) {
            var $form = $(this);
            var formAction = $form.attr('action') || '';
            var formMethod = $form.attr('method') || 'get';
            
            // If this is a YITH WAPO form or contains YITH elements, prevent submission
            if ($form.find('.yith-wapo-option, .yith-wapo-block').length > 0 || 
                formAction.includes('yith') || 
                $form.hasClass('yith-wapo-form')) {
                console.log('Preventing YITH WAPO form submission');
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
                console.log('Preventing YITH WAPO button click:', buttonText);
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
        
        // Prevent any AJAX calls that might cause page changes
        $(document).off('click.mwm-yith-ajax-prevent').on('click.mwm-yith-ajax-prevent', 'a[href*="add-to-cart"], a[href*="cart"]', function(e) {
            console.log('Preventing YITH WAPO cart link click');
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
            console.error('Base price not available');
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
                } else {
                    console.error('Error calculating 70% percentage:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
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
        
        console.log('=== YITH Option Change Detected ===');
        
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
                    console.log('Extracted base price from DOM:', basePrice);
                }
            }
        }
        
        if (basePrice <= 0) {
            console.error('Base price not available from any source');
            return;
        }
        
        console.log('Using base price:', basePrice);
        
        // Get selected YITH options - try multiple selectors
        var selectedOptions = [];
        console.log('Checking for checked YITH options...');
        
        // Method 1: Check for checked radio buttons and checkboxes
        $('input[type="radio"]:checked, input[type="checkbox"]:checked').each(function() {
            var $option = $(this);
            var $label = $option.closest('label');
            var optionName = '';
            var optionPrice = 0;
            
            // Try to get option name from label
            if ($label.length > 0) {
                optionName = $label.text().trim();
                console.log('Found checked option (method 1):', optionName);
            } else {
                // Try to get from nearby text
                optionName = $option.closest('.yith-wapo-option, .addon-field').text().trim();
                console.log('Found checked option (method 1 fallback):', optionName);
            }
            
            // Extract price from text
            var priceMatch = optionName.match(/\+([0-9,]+\.?[0-9]*)\s*€/);
            if (priceMatch) {
                optionPrice = parseFloat(priceMatch[1].replace(',', '.'));
                optionName = optionName.replace(/\+[0-9,]+\.?[0-9]*\s*€/, '').trim();
                console.log('Extracted price:', optionPrice, 'for option:', optionName);
            }
            
            // Check if this is Cromato or Olografico
            if (optionName.toLowerCase().includes('cromato') || optionName.toLowerCase().includes('olografico')) {
                selectedOptions.push({
                    name: optionName,
                    originalPrice: optionPrice,
                    element: $option
                });
                console.log('✅ Added Cromato/Olografico option:', optionName, 'with price:', optionPrice);
            }
        });
        
        // Method 2: Check specifically for YITH WAPO options
        if (selectedOptions.length === 0) {
            $('.yith-wapo-option:checked, input[name*="yith_wapo"]:checked').each(function() {
                var $option = $(this);
                var $label = $option.closest('label');
                var optionName = $label.text().trim();
                var optionPrice = 0;
                
                console.log('Found checked YITH option (method 2):', optionName);
                
                // Extract price from label text
                var priceMatch = optionName.match(/\+([0-9,]+\.?[0-9]*)\s*€/);
                if (priceMatch) {
                    optionPrice = parseFloat(priceMatch[1].replace(',', '.'));
                    optionName = optionName.replace(/\+[0-9,]+\.?[0-9]*\s*€/, '').trim();
                    console.log('Extracted price:', optionPrice, 'for option:', optionName);
                }
                
                // Check if this is Cromato or Olografico
                if (optionName.toLowerCase().includes('cromato') || optionName.toLowerCase().includes('olografico')) {
                    selectedOptions.push({
                        name: optionName,
                        originalPrice: optionPrice,
                        element: $option
                    });
                    console.log('✅ Added Cromato/Olografico option:', optionName, 'with price:', optionPrice);
                }
            });
        }
        
        // Method 3: Check for any checked inputs within SUPPORTI SPECIALI section
        if (selectedOptions.length === 0) {
            $('h3, h4, h5, h6, strong, b').each(function() {
                var $heading = $(this);
                if ($heading.text().toLowerCase().includes('supporti speciali')) {
                    console.log('Found SUPPORTI SPECIALI section, checking for checked options...');
                    
                    $heading.closest('div').find('input:checked').each(function() {
                        var $option = $(this);
                        var $label = $option.closest('label');
                        var optionName = $label.text().trim();
                        var optionPrice = 0;
                        
                        console.log('Found checked option in SUPPORTI SPECIALI:', optionName);
                        
                        // Extract price from label text
                        var priceMatch = optionName.match(/\+([0-9,]+\.?[0-9]*)\s*€/);
                        if (priceMatch) {
                            optionPrice = parseFloat(priceMatch[1].replace(',', '.'));
                            optionName = optionName.replace(/\+[0-9,]+\.?[0-9]*\s*€/, '').trim();
                            console.log('Extracted price:', optionPrice, 'for option:', optionName);
                        }
                        
                        // Check if this is Cromato or Olografico
                        if (optionName.toLowerCase().includes('cromato') || optionName.toLowerCase().includes('olografico')) {
                            selectedOptions.push({
                                name: optionName,
                                originalPrice: optionPrice,
                                element: $option
                            });
                            console.log('✅ Added Cromato/Olografico option:', optionName, 'with price:', optionPrice);
                        }
                    });
                }
            });
        }
        
        console.log('Selected options count:', selectedOptions.length);
        console.log('Selected options:', selectedOptions);
        
        // If we have selected options, check if total calculation is enabled
        if (selectedOptions.length > 0) {
            console.log('Checking if total calculation is enabled...');
            $.ajax({
                url: mwm_visualteams.ajax_url,
                type: 'POST',
                data: {
                    action: 'mwm_check_total_calculation',
                    nonce: mwm_visualteams.nonce
                },
                success: function(response) {
                    console.log('Total calculation check response:', response);
                    if (response.success && response.data.enabled) {
                        console.log('✅ Applying 70% calculation');
                        self.applySeventyPercentCalculation(selectedOptions, basePrice);
                    } else {
                        console.log('❌ Applying normal calculation');
                        self.applyNormalCalculation(selectedOptions, basePrice);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error checking total calculation:', error);
                    console.log('Fallback to normal calculation');
                    self.applyNormalCalculation(selectedOptions, basePrice);
                }
            });
        } else {
            console.log('No selected options, resetting to base price');
            self.updatePriceDisplay(basePrice, 0, basePrice);
        }
        
        console.log('=== End YITH Option Change ===');
    },
    
    /**
     * Apply 70% calculation to selected options
     */
    applySeventyPercentCalculation: function(selectedOptions, basePrice) {
        console.log('Applying 70% calculation with base price:', basePrice);
        var totalOptionsPrice = 0;
        var step1Result = 0; // Para almacenar el resultado del paso 1
        
        selectedOptions.forEach(function(option) {
            console.log('Processing option:', option.name, 'with original price:', option.originalPrice);
            
            // Apply 70% calculation formula
            var step1 = basePrice - option.originalPrice; // Restar opción del precio base
            var step2 = step1 * 0.70; // 70% del resultado
            var newOptionPrice = step2; // Nuevo precio de la opción
            
            console.log('Step 1 (base - option):', basePrice, '-', option.originalPrice, '=', step1);
            console.log('Step 2 (70% of result):', step1, '* 0.70 =', step2);
            console.log('New option price:', newOptionPrice);
            
            totalOptionsPrice += newOptionPrice;
            step1Result = step1; // Guardamos el resultado del paso 1
        });
        
        // CORRECCIÓN: El total final debe ser: (base - option) + (70% del resultado)
        // Es decir: step1Result + totalOptionsPrice
        var finalTotalPrice = step1Result + totalOptionsPrice;
        
        console.log('Final calculation - Base:', basePrice, 'Step1 result:', step1Result, 'Options (70%):', totalOptionsPrice, 'Final Total:', finalTotalPrice);
        console.log('Formula: (', basePrice, '- option) + (70% of result) =', step1Result, '+', totalOptionsPrice, '=', finalTotalPrice);
        this.updatePriceDisplay(basePrice, totalOptionsPrice, finalTotalPrice);
    },
    
    /**
     * Apply normal calculation to selected options
     */
    applyNormalCalculation: function(selectedOptions, basePrice) {
        console.log('Applying normal calculation with base price:', basePrice);
        var totalOptionsPrice = 0;
        
        selectedOptions.forEach(function(option) {
            console.log('Adding option:', option.name, 'with price:', option.originalPrice);
            totalOptionsPrice += option.originalPrice;
        });
        
        var newTotalPrice = basePrice + totalOptionsPrice;
        console.log('Normal calculation - Base:', basePrice, 'Options:', totalOptionsPrice, 'Total:', newTotalPrice);
        this.updatePriceDisplay(basePrice, totalOptionsPrice, newTotalPrice);
    },
    
    /**
     * Update price display in Totale opzioni and Totale ordine
     */
    updatePriceDisplay: function(basePrice, optionsPrice, totalPrice) {
        console.log('Updating price display - Base:', basePrice, 'Options:', optionsPrice, 'Total:', totalPrice);
        
        var formattedOptionsPrice = this.formatPrice(optionsPrice);
        var formattedTotalPrice = this.formatPrice(totalPrice);
        
        console.log('Formatted prices - Options:', formattedOptionsPrice, 'Total:', formattedTotalPrice);
        
        // CORRECCIÓN: Totale opzioni debe mostrar el resultado final del cálculo (173.40)
        // Totale ordine debe mostrar: precio base + resultado final del cálculo
        
        // Update Totale opzioni - use the correct YITH WAPO ID
        // CORRECCIÓN: Mostrar el resultado final del cálculo en Totale opzioni
        var $optionsElement = $('#wapo-total-options-price');
        if ($optionsElement.length > 0) {
            console.log('Found YITH WAPO options price element, updating:', $optionsElement.text(), '->', formattedTotalPrice);
            $optionsElement.text(formattedTotalPrice);
        } else {
            console.log('YITH WAPO options price element not found');
        }
        
        // Update Totale ordine - use the correct YITH WAPO ID
        // CORRECCIÓN: Totale ordine = precio base + resultado final del cálculo
        var finalOrderTotal = basePrice + totalPrice;
        var formattedFinalOrderTotal = this.formatPrice(finalOrderTotal);
        
        var $totalElement = $('#wapo-total-order-price');
        if ($totalElement.length > 0) {
            console.log('Found YITH WAPO total price element, updating:', $totalElement.text(), '->', formattedFinalOrderTotal);
            $totalElement.text(formattedFinalOrderTotal);
        } else {
            console.log('YITH WAPO total price element not found');
        }
        
        // Also try alternative selectors in case the IDs are different
        var $alternativeOptions = $('.wapo-total-options td, .totale-opzioni, .total-options');
        if ($alternativeOptions.length > 0 && $optionsElement.length === 0) {
            console.log('Found alternative options elements:', $alternativeOptions.length);
            $alternativeOptions.each(function() {
                console.log('Updating alternative options element:', $(this).text(), '->', formattedTotalPrice);
                $(this).text(formattedTotalPrice);
            });
        }
        
        var $alternativeTotal = $('.wapo-total-order td, .totale-ordine, .total-order');
        if ($alternativeTotal.length > 0 && $totalElement.length === 0) {
            console.log('Found alternative total elements:', $alternativeTotal.length);
            $alternativeTotal.each(function() {
                console.log('Updating alternative total element:', $(this).text(), '->', formattedFinalOrderTotal);
                $(this).text(formattedFinalOrderTotal);
            });
        }
        
        // Prevent any form submission or page redirect
        $('form').off('submit.mwm-prevent').on('submit.mwm-prevent', function(e) {
            console.log('Preventing form submission to stay on current page');
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
        
        // Also prevent any click events that might cause navigation
        $('input[type="submit"], button[type="submit"]').off('click.mwm-prevent').on('click.mwm-prevent', function(e) {
            console.log('Preventing submit button click to stay on current page');
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
        
        console.log('Price update completed - Options (final result):', formattedTotalPrice, 'Total (base + final):', formattedFinalOrderTotal);
        console.log('Final display: Totale opzioni =', formattedTotalPrice, ', Totale ordine =', formattedFinalOrderTotal);
        console.log('Calculation: Base (', basePrice, ') + Final Result (', totalPrice, ') = Order Total (', finalOrderTotal, ')');
    },
    
    /**
     * Detect YITH options from the page and create dynamic checkboxes
     */
    detectYithOptions: function() {
        var self = this;
        var detectedOptions = [];
        
        console.log('=== Detecting YITH Options for Current Page ===');
        
        // Look specifically for SUPPORTI SPECIALI section on current page only
        $('h3, h4, h5, h6, strong, b').each(function() {
            var $heading = $(this);
            if ($heading.text().toLowerCase().includes('supporti speciali')) {
                console.log('Found SUPPORTI SPECIALI heading on current page');
                
                // Look for Cromato and Olografico options specifically within this section
                var $section = $heading.closest('div, section, .yith-wapo-block');
                if ($section.length === 0) {
                    $section = $heading.parent();
                }
                
                console.log('Searching within section:', $section.attr('class') || 'no-class');
                
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
                            console.log('Found Cromato option with price:', optionPrice, 'in current page');
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
                            console.log('Found Olografico option with price:', optionPrice, 'in current page');
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
                                console.log('Found Cromato option with price:', optionPrice, 'in current page (input method)');
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
                                console.log('Found Olografico option with price:', optionPrice, 'in current page (input method)');
                            }
                        }
                    }
                });
            }
        });
        
        console.log('Total detected options for current page:', detectedOptions.length);
        
        // If we found the specific options, create dynamic checkboxes
        if (detectedOptions.length > 0) {
            self.createDynamicCheckboxes(detectedOptions);
        } else {
            console.log('No Cromato or Olografico options found on current page');
        }
        
        console.log('=== End YITH Options Detection ===');
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