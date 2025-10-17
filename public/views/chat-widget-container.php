<?php
/**
 * Chat widget container template.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/public/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get settings for customization
$chat_title = isset($general_settings['chat_title']) ? esc_html($general_settings['chat_title']) : __('How can we help you?', 'conversaai-pro-wp');
$placeholder_text = isset($general_settings['placeholder_text']) ? esc_attr($general_settings['placeholder_text']) : __('Type your message...', 'conversaai-pro-wp');

// Get appearance settings
$primary_color = isset($appearance_settings['primary_color']) ? esc_attr($appearance_settings['primary_color']) : '#4c66ef';
$text_color = isset($appearance_settings['text_color']) ? esc_attr($appearance_settings['text_color']) : '#333333';
$bot_message_bg = isset($appearance_settings['bot_message_bg']) ? esc_attr($appearance_settings['bot_message_bg']) : '#f0f4ff';
$user_message_bg = isset($appearance_settings['user_message_bg']) ? esc_attr($appearance_settings['user_message_bg']) : '#e1ebff';
$font_family = isset($appearance_settings['font_family']) ? esc_attr($appearance_settings['font_family']) : 'inherit';
$border_radius = isset($appearance_settings['border_radius']) ? esc_attr($appearance_settings['border_radius']) : '8px';
$position = isset($appearance_settings['position']) ? esc_attr($appearance_settings['position']) : 'right';
$logo_url = isset($appearance_settings['logo_url']) ? esc_url($appearance_settings['logo_url']) : '';
$bot_avatar = isset($appearance_settings['bot_avatar']) ? esc_url($appearance_settings['bot_avatar']) : CONVERSAAI_PRO_PLUGIN_URL . 'public/assets/images/avatar-bot.png';
$user_avatar = isset($appearance_settings['user_avatar']) ? esc_url($appearance_settings['user_avatar']) : CONVERSAAI_PRO_PLUGIN_URL . 'public/assets/images/avatar-user.png';

// Generate unique ID for this widget instance
$widget_id = 'conversaai-pro-widget-' . uniqid();

// Get the additional appearance settings
$logo_size = isset($appearance_settings['logo_size']) ? intval($appearance_settings['logo_size']) : 28;
$toggle_icon_size = isset($appearance_settings['toggle_icon_size']) ? intval($appearance_settings['toggle_icon_size']) : 60;
$widget_border_radius = isset($appearance_settings['widget_border_radius']) ? esc_attr($appearance_settings['widget_border_radius']) : '16px';
$input_border_radius = isset($appearance_settings['input_border_radius']) ? esc_attr($appearance_settings['input_border_radius']) : '8px';
$send_button_border_radius = isset($appearance_settings['send_button_border_radius']) ? esc_attr($appearance_settings['send_button_border_radius']) : '8px';
$auto_open_delay = isset($appearance_settings['auto_open_delay']) ? intval($appearance_settings['auto_open_delay']) : 0;

// Get device-specific settings
// Desktop
$desktop_position = isset($appearance_settings['desktop_position']) ? esc_attr($appearance_settings['desktop_position']) : 'bottom-right';
$desktop_distance_x = isset($appearance_settings['desktop_distance_x']) ? intval($appearance_settings['desktop_distance_x']) : 20;
$desktop_distance_y = isset($appearance_settings['desktop_distance_y']) ? intval($appearance_settings['desktop_distance_y']) : 20;
$desktop_width = isset($appearance_settings['desktop_width']) ? intval($appearance_settings['desktop_width']) : 380;
$desktop_height = isset($appearance_settings['desktop_height']) ? intval($appearance_settings['desktop_height']) : 500;

// Tablet
$tablet_position = isset($appearance_settings['tablet_position']) ? esc_attr($appearance_settings['tablet_position']) : 'bottom-right';
$tablet_distance_x = isset($appearance_settings['tablet_distance_x']) ? intval($appearance_settings['tablet_distance_x']) : 15;
$tablet_distance_y = isset($appearance_settings['tablet_distance_y']) ? intval($appearance_settings['tablet_distance_y']) : 15;
$tablet_width = isset($appearance_settings['tablet_width']) ? intval($appearance_settings['tablet_width']) : 340;
$tablet_height = isset($appearance_settings['tablet_height']) ? intval($appearance_settings['tablet_height']) : 450;

// Mobile
$mobile_position = isset($appearance_settings['mobile_position']) ? esc_attr($appearance_settings['mobile_position']) : 'bottom-right';
$mobile_distance_x = isset($appearance_settings['mobile_distance_x']) ? intval($appearance_settings['mobile_distance_x']) : 10;
$mobile_distance_y = isset($appearance_settings['mobile_distance_y']) ? intval($appearance_settings['mobile_distance_y']) : 10;
$mobile_width = isset($appearance_settings['mobile_width']) ? esc_attr($appearance_settings['mobile_width']) : 'full';
$mobile_width_custom = isset($appearance_settings['mobile_width_custom']) ? intval($appearance_settings['mobile_width_custom']) : 300;
$mobile_height = isset($appearance_settings['mobile_height']) ? esc_attr($appearance_settings['mobile_height']) : 'full';
$mobile_height_custom = isset($appearance_settings['mobile_height_custom']) ? intval($appearance_settings['mobile_height_custom']) : 400;

// Generate position CSS based on settings for each device
function get_position_css($position, $distance_x, $distance_y) {
    $css = '';
    
    // Split position into vertical and horizontal components
    $position_parts = explode('-', $position);
    $vertical = $position_parts[0]; // 'top' or 'bottom'
    $horizontal = $position_parts[1]; // 'left', 'right', or 'center'
    
    // Reset all positions first
    $css .= "top: auto; right: auto; bottom: auto; left: auto;";
    
    // Set vertical position
    if ($vertical === 'top') {
        $css .= "top: {$distance_y}px;";
    } else { // bottom
        $css .= "bottom: {$distance_y}px;";
    }
    
    // Set horizontal position
    if ($horizontal === 'left') {
        $css .= "left: {$distance_x}px;";
    } elseif ($horizontal === 'right') {
        $css .= "right: {$distance_x}px;";
    } else { // center
        $css .= "left: 50%; transform: translateX(-50%);";
    }
    
    return $css;
}

// Generate desktop position CSS
$desktop_position_css = get_position_css($desktop_position, $desktop_distance_x, $desktop_distance_y);

// Generate tablet position CSS
$tablet_position_css = get_position_css($tablet_position, $tablet_distance_x, $tablet_distance_y);

// Generate mobile position CSS
$mobile_position_css = get_position_css($mobile_position, $mobile_distance_x, $mobile_distance_y);

$title_color = isset($appearance_settings['title_color']) ? esc_attr($appearance_settings['title_color']) : '#ffffff';

// Custom inline styles based on settings
$custom_css = "
    /* Base styles */
    #$widget_id .conversaai-pro-header {
        background-color: $primary_color;
        color: white;
    }
    
    #$widget_id .conversaai-pro-title {
        color: $title_color;
    }
    
    #$widget_id .conversaai-pro-bot-message .conversaai-pro-message-content {
        background-color: $bot_message_bg;
        color: $text_color;
        border-radius: $border_radius;
    }
    #$widget_id .conversaai-pro-user-message .conversaai-pro-message-content {
        background-color: $user_message_bg;
        color: $text_color;
        border-radius: $border_radius;
    }
    #$widget_id .conversaai-pro-send-button {
        background-color: $primary_color;
        border-radius: $send_button_border_radius;
    }
    #$widget_id .conversaai-pro-logo {
        width: {$logo_size}px;
        height: {$logo_size}px;
    }
    #$widget_id .conversaai-pro-widget {
        border-radius: $widget_border_radius;
    }
    #$widget_id .conversaai-pro-header {
        border-top-left-radius: $widget_border_radius;
        border-top-right-radius: $widget_border_radius;
    }
    #$widget_id .conversaai-pro-input {
        border-radius: $input_border_radius;
    }
    
    /* Desktop styles (default) */
    @media (min-width: 1024px) {
        #$widget_id.conversaai-pro-widget-container {
            width: {$desktop_width}px;
            max-width: 90vw;
            font-family: $font_family;
            $desktop_position_css
        }
        #$widget_id .conversaai-pro-widget {
            height: {$desktop_height}px;
            max-height: 90vh;
        }
        #$widget_id .conversaai-pro-toggle-button {
            background-color: $primary_color;
            width: {$toggle_icon_size}px;
            height: {$toggle_icon_size}px;
            $desktop_position_css
        }
    }
    
    /* Tablet styles */
    @media (min-width: 768px) and (max-width: 1023px) {
        #$widget_id.conversaai-pro-widget-container {
            width: {$tablet_width}px;
            max-width: 90vw;
            font-family: $font_family;
            $tablet_position_css
        }
        #$widget_id .conversaai-pro-widget {
            height: {$tablet_height}px;
            max-height: 90vh;
        }
        #$widget_id .conversaai-pro-toggle-button {
            background-color: $primary_color;
            width: {$toggle_icon_size}px;
            height: {$toggle_icon_size}px;
            $tablet_position_css
        }
    }
    
    /* Mobile styles */
    @media (max-width: 767px) {
        #$widget_id.conversaai-pro-widget-container {
            " . ($mobile_width === 'full' ? "width: 100%; left: 0; right: 0;" : "width: {$mobile_width_custom}px; max-width: 95vw;") . "
            font-family: $font_family;
            " . ($mobile_width === 'full' ? "" : $mobile_position_css) . "
            border-radius: " . ($mobile_width === 'full' ? "0" : $widget_border_radius) . ";
        }
        #$widget_id .conversaai-pro-widget {
            " . ($mobile_height === 'full' ? "height: 100vh; max-height: 100vh;" : "height: {$mobile_height_custom}px; max-height: 90vh;") . "
            border-radius: " . ($mobile_width === 'full' ? "0" : $widget_border_radius) . ";
        }
        #$widget_id .conversaai-pro-header {
            border-top-left-radius: " . ($mobile_width === 'full' ? "0" : $widget_border_radius) . ";
            border-top-right-radius: " . ($mobile_width === 'full' ? "0" : $widget_border_radius) . ";
        }
        #$widget_id .conversaai-pro-toggle-button {
            background-color: $primary_color;
            width: {$toggle_icon_size}px;
            height: {$toggle_icon_size}px;
            $mobile_position_css
        }
    }
";

// Output custom CSS
echo '<style>' . $custom_css . '</style>';
?>

<div id="<?php echo esc_attr($widget_id); ?>" class="conversaai-pro-widget-container conversaai-pro-widget-closed" 
    data-auto-open-delay="<?php echo esc_attr($auto_open_delay); ?>"
    data-mobile-width="<?php echo esc_attr($mobile_width); ?>"
    data-mobile-height="<?php echo esc_attr($mobile_height); ?>"
    data-bot-avatar="<?php echo esc_attr($bot_avatar); ?>"
    data-user-avatar="<?php echo esc_attr($user_avatar); ?>">
    <div class="conversaai-pro-toggle-button">
        <?php if (!empty($appearance_settings['toggle_button_icon'])): ?>
            <img src="<?php echo esc_url($appearance_settings['toggle_button_icon']); ?>" alt="Chat" width="24" height="24">
        <?php else: ?>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2Z" fill="white"/>
            </svg>
        <?php endif; ?>
    </div>
    
    <div class="conversaai-pro-widget">
        <div class="conversaai-pro-header">
        <?php if (!empty($logo_url)): ?>
            <div class="conversaai-pro-logo">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" />
            </div>
        <?php endif; ?>
        <h3 class="conversaai-pro-title"><?php echo esc_html($chat_title); ?></h3>
        <div class="conversaai-pro-header-actions">
            <div class="conversaai-pro-reset-button" title="<?php esc_attr_e('Reset Conversation', 'conversaai-pro-wp'); ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 0.5C3.86 0.5 0.5 3.86 0.5 8C0.5 12.14 3.86 15.5 8 15.5C12.14 15.5 15.5 12.14 15.5 8H13.5C13.5 11.04 11.04 13.5 8 13.5C4.96 13.5 2.5 11.04 2.5 8C2.5 4.96 4.96 2.5 8 2.5C9.96 2.5 11.7 3.64 12.68 5.25L10 7H15.5V1.5L13.24 3.76C11.82 1.8 10.02 0.5 8 0.5Z" fill="white"/>
                </svg>
            </div>
            <div class="conversaai-pro-close-button" title="<?php esc_attr_e('Close Chat', 'conversaai-pro-wp'); ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 1.61143L14.3886 0L8 6.38857L1.61143 0L0 1.61143L6.38857 8L0 14.3886L1.61143 16L8 9.61143L14.3886 16L16 14.3886L9.61143 8L16 1.61143Z" fill="white"/>
                </svg>
            </div>
        </div>
    </div>
        
        <div class="conversaai-pro-messages-container">
            <div class="conversaai-pro-messages"></div>
        </div>
        
        <div class="conversaai-pro-input-container">
            <textarea class="conversaai-pro-input" placeholder="<?php echo esc_attr($placeholder_text); ?>"></textarea>
            <button class="conversaai-pro-send-button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.01 21L23 12L2.01 3L2 10L17 12L2 14L2.01 21Z" fill="white"/>
                </svg>
            </button>
        </div>
    </div>
</div>