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
            jQuery(document).ready(function($) {
                // Function to add our field
                function addMWMField() {
                    // Look for option cost fields
                    $('.ui-sortable').each(function() {
                        var $option = $(this);
                        
                        // Check if we already added our field to this option
                        if ($option.find('.field-wrap:has(label:contains("Calcular sobre precio total del producto"))').length > 0) {
                            return; // Skip if already exists
                        }
                        
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
                
                // Run when new options are added (but only once)
                $(document).on('click', '.add-checkbox, .add-option', function() {
                    setTimeout(addMWMField, 1000);
                });
                
                // Remove the setInterval to prevent continuous execution
            });
            </script>
            <?php
        }
    }
} 