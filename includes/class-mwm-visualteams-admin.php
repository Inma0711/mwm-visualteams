<?php
/**
 * Admin functionality for MWM Visual Teams
 */

if (!defined('ABSPATH')) {
    exit;
}

class MWM_VisualTeams_Admin {
    
    public function __construct() {
        add_action('admin_init', array($this, 'init'));
    }
    
    public function init() {
        // Add JavaScript to inject our field into YITH
        add_action('admin_footer', array($this, 'add_yith_injection_script'));
        
        // Add custom CSS for the new field
        add_action('admin_head', array($this, 'add_admin_custom_css'));
        
        // Save the calculate_on_total field when addon is saved
        add_action('yith_wapo_save_addon', array($this, 'save_calculate_on_total_field'), 10, 2);
        add_action('save_post', array($this, 'save_calculate_on_total_field_generic'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_mwm_get_calculate_on_total', array($this, 'ajax_get_calculate_on_total'));
        add_action('wp_ajax_mwm_save_calculate_on_total', array($this, 'ajax_save_calculate_on_total'));
    }
    

    

    
    /**
     * Add custom CSS for the new field
     */
    public function add_admin_custom_css() {
        ?>
        <style>

        
        /* YITH Plugin Framework On/Off Switch Styles */
        .on_off {
            display: none;
        }
        
        .yith-plugin-fw-onoff {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 20px;
            background: #ccc;
            border-radius: 10px;
            cursor: pointer;
            position: relative;
            transition: background-color 0.3s ease;
        }
        
        .on_off:checked + .yith-plugin-fw-onoff {
            background: #0073aa;
        }
        
        .yith-plugin-fw-onoff_handle {
            position: absolute;
            left: 1px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s ease;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, .1), 0 1px 2px -1px rgba(0, 0, 0, .1);
            display: flex;
            align-items: center;
            justify-content: center;
            top: 1px;
        }
        
        .yith-plugin-fw-onoff_handle::before {
            content: none !important;
        }
        
        .on_off:checked + .yith-plugin-fw-onoff .yith-plugin-fw-onoff_handle {
            transform: translateX(20px);
        }
        
        .yith-plugin-fw-onoff_icon {
            width: 15px;
            height: 15px;
            transition: opacity 0.3s ease;
        }
        
        .yith-plugin-fw-onoff_icon--on {
            opacity: 0;
            fill: none;
            stroke: #8d9c2c;
            stroke-width: 2.5;
            left: auto !important;
        }
        
        .yith-plugin-fw-onoff_icon--off {
            opacity: 1;
            width: 9px;
            height: 9px;
            stroke: currentColor;
            stroke-width: 3;
            color: #94a3b8;
        }
        
        .on_off:checked + .yith-plugin-fw-onoff .yith-plugin-fw-onoff_icon--on {
            opacity: 1;
        }
        
        .on_off:checked + .yith-plugin-fw-onoff .yith-plugin-fw-onoff_icon--off {
            opacity: 0;
        }
        
        .yith-plugin-fw-onoff_zero-width-space {
            width: 0;
            height: 0;
            overflow: hidden;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .mwm-total-calculation-field {
                padding: 12px;
                margin: 12px 0;
            }
            
            .mwm-field-label {
                font-size: 13px;
            }
        }
        </style>
        <?php
    }
    

    
    /**
     * Add JavaScript to inject our field into YITH
     */
    public function add_yith_injection_script() {
        // Only run on YITH pages
        if (isset($_GET['page']) && strpos($_GET['page'], 'yith_wapo') !== false) {
            ?>
            <script type="text/javascript">
            // Define mwm_ajaxurl globally
            var mwm_ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            jQuery(document).ready(function($) {
                // Function to add our field
                function addMWMField() {
                    // First check if we're in the "SUPPORTI SPECIALI" section
                    var $titleField = $('input[name="title"]');
                    var isSupportiSpeciali = false;
                    
                    // Try different selectors for the title field
                    if ($titleField.length === 0) {
                        $titleField = $('input[name*="title"]');
                    }
                    
                    if ($titleField.length === 0) {
                        $titleField = $('input[placeholder*="title"], input[placeholder*="titolo"]');
                    }
                    
                    if ($titleField.length === 0) {
                        // Look for any input near a "Titolo" or "Title" label
                        $titleField = $('label:contains("Titolo"), label:contains("Title")').next('input');
                    }
                    
                    if ($titleField.length === 0) {
                        // Last resort: look for any input that might be the title
                        $titleField = $('input[type="text"]').first();
                    }
                    
                    if ($titleField.length > 0) {
                        isSupportiSpeciali = ($titleField.val() === 'SUPPORTI SPECIALI');
                    }
                    
                    // Only proceed if we're in SUPPORTI SPECIALI
                    if (!isSupportiSpeciali) {
                        // Remove any existing fields if we're not in SUPPORTI SPECIALI
                        $('.field-wrap:has(label:contains("Calcular sobre precio total del producto"))').remove();
                        return;
                    }
                    
                    // Look for option cost fields
                    var $sortableElements = $('.ui-sortable');
                    
                    $sortableElements.each(function(index) {
                        var $option = $(this);
                        
                        // Check if we already added our field to this option
                        if ($option.find('.field-wrap:has(label:contains("Calcular sobre precio total del producto"))').length > 0) {
                            return; // Skip if already exists
                        }
                        
                        // Check if this is a checkbox option - try multiple strategies
                        var isCheckbox = false;
                        
                        // Strategy 1: Look for checkbox headers
                        var $checkboxHeader = $option.find('.option-header:contains("CHECKBOX")');
                        if ($checkboxHeader.length === 0) {
                            $checkboxHeader = $option.find('.option-header:contains("Checkbox")');
                        }
                        if ($checkboxHeader.length === 0) {
                            $checkboxHeader = $option.find('.option-header:contains("checkbox")');
                        }
                        if ($checkboxHeader.length === 0) {
                            $checkboxHeader = $option.find('.option-header:contains("CHECK")');
                        }
                        
                        // Strategy 2: Look for checkbox inputs
                        var $checkboxInputs = $option.find('input[type="checkbox"]');
                        
                        // Strategy 3: Look for any element containing "checkbox" text
                        var $checkboxText = $option.find('*:contains("checkbox"), *:contains("Checkbox"), *:contains("CHECKBOX")');
                        
                        // Strategy 4: Look for specific classes that might indicate checkboxes
                        var $checkboxClasses = $option.find('.checkbox, .check, .option-checkbox, .addon-checkbox');
                        
                        // Determine if this is a checkbox based on any strategy
                        if ($checkboxHeader.length > 0 || $checkboxInputs.length > 0 || $checkboxText.length > 0 || $checkboxClasses.length > 0) {
                            isCheckbox = true;
                        } else {
                            return; // Skip if not a checkbox
                        }
                        
                        // Get the checkbox label to check if it's "Cromato" or "Olografico"
                        var $labelField = $option.find('input[name*="label"]');
                        var checkboxLabel = '';
                        if ($labelField.length > 0) {
                            checkboxLabel = $labelField.val();
                        }
                        
                        // For now, show for ALL checkboxes in SUPPORTI SPECIALI to test
                        // TODO: Later restrict to only "Cromato" and "Olografico"
                        
                        // Look for the option cost section
                        var $costSection = $option.find('.option-cost');
                        
                        if ($costSection.length > 0) {
                            // Create our field
                            var mwmField = '<div class="field-wrap addon-field-grid">';
                            mwmField += '<label>Calcular sobre precio total del producto</label>';
                            mwmField += '<div class="field">';
                            mwmField += '<div class="yith-plugin-fw-field-wrapper yith-plugin-fw-onoff-field-wrapper">';
                            mwmField += '<div class="yith-plugin-fw-onoff-container">';
                            mwmField += '<input type="checkbox" name="calculate_on_total" value="yes" class="on_off" />';
                            mwmField += '<span class="yith-plugin-fw-onoff">';
                            mwmField += '<span class="yith-plugin-fw-onoff_handle">';
                            mwmField += '<svg class="yith-plugin-fw-onoff_icon yith-plugin-fw-onoff_icon--on" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="#8d9c2c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M6 10l3 3 5-5"/></svg>';
                            mwmField += '<svg class="yith-plugin-fw-onoff_icon yith-plugin-fw-onoff_icon--off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" role="img"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';
                            mwmField += '</span>';
                            mwmField += '</span>';
                            mwmField += '</div>';
                            mwmField += '</div>';
                            mwmField += '</div>';
                            mwmField += '<span class="description">Habilita para calcular el precio de esta opción sobre el precio total del producto en lugar del precio base.</span>';
                            mwmField += '</div>';
                            
                            // Insert after the cost section
                            $costSection.after(mwmField);
                        }
                    });
                }
                
                // Run immediately
                addMWMField();
                
                // Run again after a delay to ensure YITH is loaded
                setTimeout(addMWMField, 1000);
                setTimeout(addMWMField, 2000);
                
                // Run when new options are added (but only once)
                $(document).on('click', '.add-checkbox, .add-option', function() {
                    setTimeout(addMWMField, 1000);
                });
                
                // Run when title changes
                $(document).on('input', 'input[name="title"]', function() {
                    setTimeout(addMWMField, 500);
                });
                
                // Run when checkbox labels change
                $(document).on('input', 'input[name*="label"]', function() {
                    setTimeout(addMWMField, 500);
                });
                
                // Run when page content changes (for dynamic content)
                $(document).on('DOMNodeInserted', '.ui-sortable', function() {
                    setTimeout(addMWMField, 500);
                });
                
                // Force visual state after YITH finishes rendering
                $(document).on('DOMNodeInserted', '.yith-plugin-fw-onoff', function() {
                    var $toggle = $(this);
                    var $checkbox = $toggle.find('input[name="calculate_on_total"]');
                    
                    if ($checkbox.length > 0 && $checkbox.is(':checked')) {
                        setTimeout(function() {
                            $toggle.addClass('on').removeClass('off');
                            $toggle.find('.yith-plugin-fw-onoff_handle').css('transform', 'matrix(1, 0, 0, 1, 25, 0)');

                        }, 100);
                    }
                });
                

                
                // Monitor for YITH re-rendering and restore checkbox state
                $(document).on('DOMNodeInserted', 'input[name="calculate_on_total"]', function() {
                    var $checkbox = $(this);
                    var addonId = getCurrentAddonId();
                    

                    
                    if (addonId) {
                        // Check if this checkbox should be checked
                        $.ajax({
                            url: mwm_ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'mwm_get_calculate_on_total',
                                addon_id: addonId,
                                nonce: '<?php echo wp_create_nonce("mwm_visualteams_nonce"); ?>'
                            },
                            success: function(response) {
                                if (response.success && response.data === 'yes') {
                                    setTimeout(function() {
                                        // Set the property without triggering change event
                                        $checkbox.prop('checked', true);
                                        
                                        // Force visual update with multiple approaches
                                        $checkbox.closest('.yith-plugin-fw-onoff').addClass('on').removeClass('off');
                                        $checkbox.closest('.yith-plugin-fw-onoff_handle').css('transform', 'matrix(1, 0, 0, 1, 25, 0)');
                                        
                                        // Also trigger YITH's own visual update
                                        $checkbox.trigger('click').trigger('change');
                                        

                                    }, 50);
                                }
                            }
                        });
                    }
                });
                
                // Handle checkbox change and save immediately via AJAX
                $(document).on('change', 'input[name="calculate_on_total"]', function(e) {
                    // Prevent recursive triggers
                    if (e.isTrigger) {
                        return;
                    }
                    
                    var $checkbox = $(this);
                    var isChecked = $checkbox.is(':checked');
                    var addonId = getCurrentAddonId();
                    

                    
                    if (addonId) {
                        // Save immediately via AJAX
                        $.ajax({
                            url: mwm_ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'mwm_save_calculate_on_total',
                                addon_id: addonId,
                                value: isChecked ? 'yes' : 'no',
                                nonce: '<?php echo wp_create_nonce("mwm_visualteams_nonce"); ?>'
                            },
                            success: function(response) {
                                // Success - value saved
                            },
                            error: function(xhr, status, error) {
                                // Error - could not save
                            }
                        });
                    }
                });
                
                // Load saved values for existing addons
                loadSavedValues();
                
                function loadSavedValues() {
                    // Get the current addon ID from the URL or form
                    var addonId = getCurrentAddonId();
                    
                    if (addonId) {
                        // Make AJAX call to get saved value
                        $.ajax({
                            url: mwm_ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'mwm_get_calculate_on_total',
                                addon_id: addonId,
                                nonce: '<?php echo wp_create_nonce("mwm_visualteams_nonce"); ?>'
                            },
                            success: function(response) {
                                if (response.success && response.data === 'yes') {
                                    // Set the checkbox state with a delay to ensure YITH has finished rendering
                                    setTimeout(function() {
                                        $('input[name="calculate_on_total"]').prop('checked', true);
                                        
                                        // Also force the visual state by triggering the toggle
                                        $('input[name="calculate_on_total"]').each(function() {
                                            var $checkbox = $(this);
                                            var $toggle = $checkbox.closest('.yith-plugin-fw-onoff');
                                            var $handle = $checkbox.closest('.yith-plugin-fw-onoff_handle');
                                            
                                            if ($checkbox.is(':checked')) {
                                                $toggle.addClass('on').removeClass('off');
                                                $handle.css('transform', 'matrix(1, 0, 0, 1, 25, 0)');
                                            } else {
                                                $toggle.removeClass('on').addClass('off');
                                                $handle.css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
                                            }
                                        });
                                        

                                    }, 100);
                                }
                            },
                            error: function(xhr, status, error) {
                                // Error - could not load
                            }
                        });
                    }
                }
                
                function getCurrentAddonId() {
                    // Try to get addon ID from URL
                    var urlParams = new URLSearchParams(window.location.search);
                    var post = urlParams.get('post');
                    if (post) return post;
                    
                    // Try to get addon_id from URL
                    var addonId = urlParams.get('addon_id');
                    if (addonId) return addonId;
                    
                    // Try to get from form
                    var form = $('form').first();
                    if (form.length > 0) {
                        var postInput = form.find('input[name="post_ID"]');
                        if (postInput.length > 0) {
                            return postInput.val();
                        }
                    }
                    
                    // Try alternative selectors
                    var altInput = $('input[name="post_id"]');
                    if (altInput.length > 0) {
                        return altInput.val();
                    }
                    
                    // Try to get from hidden inputs
                    var hiddenInputs = $('input[type="hidden"]');
                    hiddenInputs.each(function() {
                        var $input = $(this);
                        var name = $input.attr('name');
                        var value = $input.val();
                        if (name && (name.includes('id') || name.includes('ID')) && value) {
                            return value;
                        }
                    });
                    
                    // Try to get from the current addon element
                    var $currentAddon = $('.addon-element.active, .addon-element.selected, .addon-element:visible').first();
                    if ($currentAddon.length > 0) {
                        var dataId = $currentAddon.attr('data-id');
                        if (dataId) return dataId;
                        
                        var id = $currentAddon.attr('id');
                        if (id && id.includes('addon-')) {
                            var extractedId = id.replace('addon-', '');
                            return extractedId;
                        }
                    }
                    
                    // Try to get from any addon element
                    var $anyAddon = $('.addon-element').first();
                    if ($anyAddon.length > 0) {
                        var dataId = $anyAddon.attr('data-id');
                        if (dataId) return dataId;
                    }
                    
                    return null;
                }
                
                // Remove the setInterval to prevent continuous execution
            });
            </script>
            <?php
        }
    }
    
    /**
     * Save the calculate_on_total field when addon is saved
     */
    public function save_calculate_on_total_field($addon_id, $addon_data) {
        error_log('MWM Visual Teams: YITH save hook triggered for addon ID: ' . $addon_id);
        error_log('MWM Visual Teams: POST data: ' . print_r($_POST, true));
        
        if (isset($_POST['calculate_on_total'])) {
            $calculate_on_total = sanitize_text_field($_POST['calculate_on_total']);
            update_post_meta($addon_id, '_calculate_on_total', $calculate_on_total);
            error_log('MWM Visual Teams: YITH save - Saved calculate_on_total: ' . $calculate_on_total);
        } else {
            delete_post_meta($addon_id, '_calculate_on_total');
            error_log('MWM Visual Teams: YITH save - Deleted calculate_on_total (not found in POST)');
        }
    }
    
    /**
     * Generic save function for any post type
     */
    public function save_calculate_on_total_field_generic($post_id, $post) {
        // Only process YITH WAPO addons
        if ($post->post_type !== 'yith_wapo_addon') {
            return;
        }
        
        error_log('MWM Visual Teams: Generic save for post ID: ' . $post_id);
        
        if (isset($_POST['calculate_on_total'])) {
            $calculate_on_total = sanitize_text_field($_POST['calculate_on_total']);
            update_post_meta($post_id, '_calculate_on_total', $calculate_on_total);
            error_log('MWM Visual Teams: Generic save - Saved calculate_on_total: ' . $calculate_on_total);
        } else {
            delete_post_meta($post_id, '_calculate_on_total');
            error_log('MWM Visual Teams: Generic save - Deleted calculate_on_total');
        }
    }
    
    /**
     * AJAX handler to get calculate_on_total value
     */
    public function ajax_get_calculate_on_total() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mwm_visualteams_nonce')) {
            wp_die('Security check failed');
        }
        
        $addon_id = intval($_POST['addon_id']);
        $calculate_on_total = get_post_meta($addon_id, '_calculate_on_total', true);
        
        error_log('MWM Visual Teams: AJAX request for addon ID: ' . $addon_id . ', value: ' . $calculate_on_total);
        
        wp_send_json_success($calculate_on_total);
    }
    
    /**
     * AJAX handler to save calculate_on_total value
     */
    public function ajax_save_calculate_on_total() {
        error_log('MWM Debug: AJAX save function called');
        error_log('MWM Debug: POST data: ' . print_r($_POST, true));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mwm_visualteams_nonce')) {
            error_log('MWM Debug: Nonce verification failed');
            wp_die('Security check failed');
        }
        
        $addon_id = intval($_POST['addon_id']);
        $calculate_on_total = sanitize_text_field($_POST['value']);
        
        error_log('MWM Debug: Addon ID: ' . $addon_id . ', Value: ' . $calculate_on_total);
        
        $result = update_post_meta($addon_id, '_calculate_on_total', $calculate_on_total);
        
        error_log('MWM Debug: Update result: ' . ($result ? 'success' : 'failed'));
        error_log('MWM Visual Teams: AJAX save for addon ID: ' . $addon_id . ', value: ' . $calculate_on_total);
        
        wp_send_json_success('Saved');
    }
} 