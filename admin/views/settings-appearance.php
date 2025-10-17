<?php
/**
 * Appearance settings tab content.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<form method="post" action="options.php" id="conversaai-appearance-settings-form" class="conversaai-settings-form">
    <?php settings_fields('conversaai_pro_appearance_settings'); ?>
    
    <div class="conversaai-appearance-preview">
        <div class="conversaai-preview-widget">
            <div class="conversaai-preview-header">
                <h3 id="preview-title"><?php echo esc_html(isset($general_settings['chat_title']) ? $general_settings['chat_title'] : __('How can we help you?', 'conversaai-pro-wp')); ?></h3>
            </div>
            <div class="conversaai-preview-messages">
                <div class="conversaai-preview-message conversaai-preview-bot-message">
                    <div class="conversaai-preview-avatar"></div>
                    <div class="conversaai-preview-message-content"><?php echo esc_html(isset($general_settings['welcome_message']) ? $general_settings['welcome_message'] : __('Hello! I\'m your AI assistant. How can I help you today?', 'conversaai-pro-wp')); ?></div>
                </div>
                <div class="conversaai-preview-message conversaai-preview-user-message">
                    <div class="conversaai-preview-message-content"><?php _e('I have a question about your services.', 'conversaai-pro-wp'); ?></div>
                    <div class="conversaai-preview-avatar"></div>
                </div>
                <div class="conversaai-preview-message conversaai-preview-bot-message">
                    <div class="conversaai-preview-avatar"></div>
                    <div class="conversaai-preview-message-content"><?php _e('Of course! I\'d be happy to help with any questions about our services. What would you like to know?', 'conversaai-pro-wp'); ?></div>
                </div>
            </div>
            <div class="conversaai-preview-input">
                <input type="text" readonly placeholder="<?php echo esc_attr(isset($general_settings['placeholder_text']) ? $general_settings['placeholder_text'] : __('Type your message...', 'conversaai-pro-wp')); ?>">
                <button><?php _e('Send', 'conversaai-pro-wp'); ?></button>
            </div>
        </div>
    </div>
    
    <table class="form-table">

                <!-- Device-specific settings -->
                    <tr>
                <th scope="row" colspan="2">
                    <h3 class="responsive-settings-header"><?php _e('Responsive Settings', 'conversaai-pro-wp'); ?></h3>
                    <div class="device-selector">
                        <button type="button" class="device-button active" data-device="desktop"><?php _e('Desktop', 'conversaai-pro-wp'); ?></button>
                        <button type="button" class="device-button" data-device="tablet"><?php _e('Tablet', 'conversaai-pro-wp'); ?></button>
                        <button type="button" class="device-button" data-device="mobile"><?php _e('Mobile', 'conversaai-pro-wp'); ?></button>
                    </div>
                </th>
            </tr>

            <!-- Desktop Settings -->
            <tbody class="device-settings device-desktop">
                <tr>
                    <th scope="row">
                        <label for="desktop_position"><?php _e('Desktop Position', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <select name="conversaai_pro_appearance_settings[desktop_position]" id="desktop_position">
                            <option value="bottom-right" <?php selected(isset($appearance_settings['desktop_position']) ? $appearance_settings['desktop_position'] : 'bottom-right', 'bottom-right'); ?>><?php _e('Bottom Right', 'conversaai-pro-wp'); ?></option>
                            <option value="bottom-left" <?php selected(isset($appearance_settings['desktop_position']) ? $appearance_settings['desktop_position'] : '', 'bottom-left'); ?>><?php _e('Bottom Left', 'conversaai-pro-wp'); ?></option>
                            <option value="top-right" <?php selected(isset($appearance_settings['desktop_position']) ? $appearance_settings['desktop_position'] : '', 'top-right'); ?>><?php _e('Top Right', 'conversaai-pro-wp'); ?></option>
                            <option value="top-left" <?php selected(isset($appearance_settings['desktop_position']) ? $appearance_settings['desktop_position'] : '', 'top-left'); ?>><?php _e('Top Left', 'conversaai-pro-wp'); ?></option>
                        </select>
                        <p class="description"><?php _e('Position of the chat widget on desktop devices.', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="desktop_distance_x"><?php _e('Horizontal Distance (px)', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="conversaai_pro_appearance_settings[desktop_distance_x]" id="desktop_distance_x" class="small-text" min="0" max="100" value="<?php echo esc_attr(isset($appearance_settings['desktop_distance_x']) ? $appearance_settings['desktop_distance_x'] : '20'); ?>">
                        <p class="description"><?php _e('Distance from the horizontal edge of the screen in pixels (left or right depending on position).', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="desktop_distance_y"><?php _e('Vertical Distance (px)', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="conversaai_pro_appearance_settings[desktop_distance_y]" id="desktop_distance_y" class="small-text" min="0" max="100" value="<?php echo esc_attr(isset($appearance_settings['desktop_distance_y']) ? $appearance_settings['desktop_distance_y'] : '20'); ?>">
                        <p class="description"><?php _e('Distance from the vertical edge of the screen in pixels (top or bottom depending on position).', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="desktop_width"><?php _e('Widget Width (px)', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="conversaai_pro_appearance_settings[desktop_width]" id="desktop_width" class="small-text" min="300" max="800" value="<?php echo esc_attr(isset($appearance_settings['desktop_width']) ? $appearance_settings['desktop_width'] : '380'); ?>">
                        <p class="description"><?php _e('Width of the chat widget in pixels for desktop devices.', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="desktop_height"><?php _e('Widget Height (px)', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="conversaai_pro_appearance_settings[desktop_height]" id="desktop_height" class="small-text" min="300" max="800" value="<?php echo esc_attr(isset($appearance_settings['desktop_height']) ? $appearance_settings['desktop_height'] : '500'); ?>">
                        <p class="description"><?php _e('Height of the chat widget in pixels for desktop devices.', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
            </tbody>

            <!-- Tablet Settings -->
            <tbody class="device-settings device-tablet" style="display: none;">
                <tr>
                    <th scope="row">
                        <label for="tablet_position"><?php _e('Tablet Position', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <select name="conversaai_pro_appearance_settings[tablet_position]" id="tablet_position">
                            <option value="bottom-right" <?php selected(isset($appearance_settings['tablet_position']) ? $appearance_settings['tablet_position'] : 'bottom-right', 'bottom-right'); ?>><?php _e('Bottom Right', 'conversaai-pro-wp'); ?></option>
                            <option value="bottom-left" <?php selected(isset($appearance_settings['tablet_position']) ? $appearance_settings['tablet_position'] : '', 'bottom-left'); ?>><?php _e('Bottom Left', 'conversaai-pro-wp'); ?></option>
                            <option value="top-right" <?php selected(isset($appearance_settings['tablet_position']) ? $appearance_settings['tablet_position'] : '', 'top-right'); ?>><?php _e('Top Right', 'conversaai-pro-wp'); ?></option>
                            <option value="top-left" <?php selected(isset($appearance_settings['tablet_position']) ? $appearance_settings['tablet_position'] : '', 'top-left'); ?>><?php _e('Top Left', 'conversaai-pro-wp'); ?></option>
                        </select>
                        <p class="description"><?php _e('Position of the chat widget on tablet devices.', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="tablet_distance_x"><?php _e('Horizontal Distance (px)', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="conversaai_pro_appearance_settings[tablet_distance_x]" id="tablet_distance_x" class="small-text" min="0" max="100" value="<?php echo esc_attr(isset($appearance_settings['tablet_distance_x']) ? $appearance_settings['tablet_distance_x'] : '15'); ?>">
                        <p class="description"><?php _e('Distance from the horizontal edge of the screen in pixels (left or right depending on position).', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="tablet_distance_y"><?php _e('Vertical Distance (px)', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="conversaai_pro_appearance_settings[tablet_distance_y]" id="tablet_distance_y" class="small-text" min="0" max="100" value="<?php echo esc_attr(isset($appearance_settings['tablet_distance_y']) ? $appearance_settings['tablet_distance_y'] : '15'); ?>">
                        <p class="description"><?php _e('Distance from the vertical edge of the screen in pixels (top or bottom depending on position).', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="tablet_width"><?php _e('Widget Width (px)', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="conversaai_pro_appearance_settings[tablet_width]" id="tablet_width" class="small-text" min="280" max="600" value="<?php echo esc_attr(isset($appearance_settings['tablet_width']) ? $appearance_settings['tablet_width'] : '340'); ?>">
                        <p class="description"><?php _e('Width of the chat widget in pixels for tablet devices.', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="tablet_height"><?php _e('Widget Height (px)', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="conversaai_pro_appearance_settings[tablet_height]" id="tablet_height" class="small-text" min="300" max="700" value="<?php echo esc_attr(isset($appearance_settings['tablet_height']) ? $appearance_settings['tablet_height'] : '450'); ?>">
                        <p class="description"><?php _e('Height of the chat widget in pixels for tablet devices.', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
            </tbody>

            <!-- Mobile Settings -->
            <tbody class="device-settings device-mobile" style="display: none;">
                <tr>
                    <th scope="row">
                        <label for="mobile_position"><?php _e('Mobile Position', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <select name="conversaai_pro_appearance_settings[mobile_position]" id="mobile_position">
                            <option value="bottom-right" <?php selected(isset($appearance_settings['mobile_position']) ? $appearance_settings['mobile_position'] : 'bottom-right', 'bottom-right'); ?>><?php _e('Bottom Right', 'conversaai-pro-wp'); ?></option>
                            <option value="bottom-left" <?php selected(isset($appearance_settings['mobile_position']) ? $appearance_settings['mobile_position'] : '', 'bottom-left'); ?>><?php _e('Bottom Left', 'conversaai-pro-wp'); ?></option>
                            <option value="top-right" <?php selected(isset($appearance_settings['mobile_position']) ? $appearance_settings['mobile_position'] : '', 'top-right'); ?>><?php _e('Top Right', 'conversaai-pro-wp'); ?></option>
                            <option value="top-left" <?php selected(isset($appearance_settings['mobile_position']) ? $appearance_settings['mobile_position'] : '', 'top-left'); ?>><?php _e('Top Left', 'conversaai-pro-wp'); ?></option>
                            <option value="bottom-center" <?php selected(isset($appearance_settings['mobile_position']) ? $appearance_settings['mobile_position'] : '', 'bottom-center'); ?>><?php _e('Bottom Center', 'conversaai-pro-wp'); ?></option>
                        </select>
                        <p class="description"><?php _e('Position of the chat widget on mobile devices.', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mobile_distance_x"><?php _e('Horizontal Distance (px)', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="conversaai_pro_appearance_settings[mobile_distance_x]" id="mobile_distance_x" class="small-text" min="0" max="50" value="<?php echo esc_attr(isset($appearance_settings['mobile_distance_x']) ? $appearance_settings['mobile_distance_x'] : '10'); ?>">
                        <p class="description"><?php _e('Distance from the horizontal edge of the screen in pixels (left or right depending on position).', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mobile_distance_y"><?php _e('Vertical Distance (px)', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="conversaai_pro_appearance_settings[mobile_distance_y]" id="mobile_distance_y" class="small-text" min="0" max="50" value="<?php echo esc_attr(isset($appearance_settings['mobile_distance_y']) ? $appearance_settings['mobile_distance_y'] : '10'); ?>">
                        <p class="description"><?php _e('Distance from the vertical edge of the screen in pixels (top or bottom depending on position).', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mobile_width"><?php _e('Widget Width', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <select name="conversaai_pro_appearance_settings[mobile_width]" id="mobile_width">
                            <option value="full" <?php selected(isset($appearance_settings['mobile_width']) ? $appearance_settings['mobile_width'] : 'full', 'full'); ?>><?php _e('Full Width', 'conversaai-pro-wp'); ?></option>
                            <option value="custom" <?php selected(isset($appearance_settings['mobile_width']) ? $appearance_settings['mobile_width'] : '', 'custom'); ?>><?php _e('Custom Width', 'conversaai-pro-wp'); ?></option>
                        </select>
                        <input type="number" name="conversaai_pro_appearance_settings[mobile_width_custom]" id="mobile_width_custom" class="small-text" min="280" max="400" value="<?php echo esc_attr(isset($appearance_settings['mobile_width_custom']) ? $appearance_settings['mobile_width_custom'] : '300'); ?>" <?php echo (isset($appearance_settings['mobile_width']) && $appearance_settings['mobile_width'] === 'custom') ? '' : 'style="display:none;"'; ?>>
                        <span class="custom-width-unit" <?php echo (isset($appearance_settings['mobile_width']) && $appearance_settings['mobile_width'] === 'custom') ? '' : 'style="display:none;"'; ?>>px</span>
                        <p class="description"><?php _e('Width of the chat widget for mobile devices. Choose full width or specify custom width.', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mobile_height"><?php _e('Widget Height', 'conversaai-pro-wp'); ?></label>
                    </th>
                    <td>
                        <select name="conversaai_pro_appearance_settings[mobile_height]" id="mobile_height">
                            <option value="full" <?php selected(isset($appearance_settings['mobile_height']) ? $appearance_settings['mobile_height'] : 'full', 'full'); ?>><?php _e('Full Height', 'conversaai-pro-wp'); ?></option>
                            <option value="custom" <?php selected(isset($appearance_settings['mobile_height']) ? $appearance_settings['mobile_height'] : '', 'custom'); ?>><?php _e('Custom Height', 'conversaai-pro-wp'); ?></option>
                        </select>
                        <input type="number" name="conversaai_pro_appearance_settings[mobile_height_custom]" id="mobile_height_custom" class="small-text" min="300" max="600" value="<?php echo esc_attr(isset($appearance_settings['mobile_height_custom']) ? $appearance_settings['mobile_height_custom'] : '400'); ?>" <?php echo (isset($appearance_settings['mobile_height']) && $appearance_settings['mobile_height'] === 'custom') ? '' : 'style="display:none;"'; ?>>
                        <span class="custom-height-unit" <?php echo (isset($appearance_settings['mobile_height']) && $appearance_settings['mobile_height'] === 'custom') ? '' : 'style="display:none;"'; ?>>px</span>
                        <p class="description"><?php _e('Height of the chat widget for mobile devices. Choose full height or specify custom height.', 'conversaai-pro-wp'); ?></p>
                    </td>
                </tr>
    </tbody>

        <tr>
            <th scope="row">
                <label for="primary_color"><?php _e('Primary Color', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="color" name="conversaai_pro_appearance_settings[primary_color]" id="primary_color" value="<?php echo esc_attr(isset($appearance_settings['primary_color']) ? $appearance_settings['primary_color'] : '#4c66ef'); ?>">
                <p class="description"><?php _e('The primary color for the chat widget header and buttons.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="text_color"><?php _e('Text Color', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="color" name="conversaai_pro_appearance_settings[text_color]" id="text_color" value="<?php echo esc_attr(isset($appearance_settings['text_color']) ? $appearance_settings['text_color'] : '#333333'); ?>">
                <p class="description"><?php _e('The main text color for messages.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="title_color"><?php _e('Title Color', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="color" name="conversaai_pro_appearance_settings[title_color]" id="title_color" value="<?php echo esc_attr(isset($appearance_settings['title_color']) ? $appearance_settings['title_color'] : '#ffffff'); ?>">
                <p class="description"><?php _e('The color for the chat title text.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="toggle_button_icon"><?php _e('Toggle Button Icon', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="text" name="conversaai_pro_appearance_settings[toggle_button_icon]" id="toggle_button_icon" class="regular-text" value="<?php echo esc_attr(isset($appearance_settings['toggle_button_icon']) ? $appearance_settings['toggle_button_icon'] : ''); ?>">
                <button type="button" class="button conversaai-media-uploader" data-target="toggle_button_icon"><?php _e('Select SVG', 'conversaai-pro-wp'); ?></button>
                <p class="description"><?php _e('URL to an SVG icon for the chat toggle button. Leave empty to use the default icon.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="bot_message_bg"><?php _e('Bot Message Background', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="color" name="conversaai_pro_appearance_settings[bot_message_bg]" id="bot_message_bg" value="<?php echo esc_attr(isset($appearance_settings['bot_message_bg']) ? $appearance_settings['bot_message_bg'] : '#f0f4ff'); ?>">
                <p class="description"><?php _e('Background color for bot messages.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="user_message_bg"><?php _e('User Message Background', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="color" name="conversaai_pro_appearance_settings[user_message_bg]" id="user_message_bg" value="<?php echo esc_attr(isset($appearance_settings['user_message_bg']) ? $appearance_settings['user_message_bg'] : '#e1ebff'); ?>">
                <p class="description"><?php _e('Background color for user messages.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="response_link_color"><?php _e('Response Link Color', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="color" id="response_link_color" name="conversaai_pro_appearance_settings[response_link_color]" 
                    value="<?php echo esc_attr(isset($appearance_settings['response_link_color']) ? $appearance_settings['response_link_color'] : '#4c66ef'); ?>">
                <p class="description"><?php _e('Color for links in AI responses.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="font_family"><?php _e('Font Family', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <select name="conversaai_pro_appearance_settings[font_family]" id="font_family">
                    <option value="inherit" <?php selected(isset($appearance_settings['font_family']) ? $appearance_settings['font_family'] : 'inherit', 'inherit'); ?>><?php _e('Use Site Font', 'conversaai-pro-wp'); ?></option>
                    <option value="Arial, sans-serif" <?php selected(isset($appearance_settings['font_family']) ? $appearance_settings['font_family'] : '', 'Arial, sans-serif'); ?>>Arial</option>
                    <option value="'Helvetica Neue', Helvetica, sans-serif" <?php selected(isset($appearance_settings['font_family']) ? $appearance_settings['font_family'] : '', "'Helvetica Neue', Helvetica, sans-serif"); ?>>Helvetica</option>
                    <option value="Georgia, serif" <?php selected(isset($appearance_settings['font_family']) ? $appearance_settings['font_family'] : '', 'Georgia, serif'); ?>>Georgia</option>
                    <option value="'Segoe UI', Tahoma, Geneva, sans-serif" <?php selected(isset($appearance_settings['font_family']) ? $appearance_settings['font_family'] : '', "'Segoe UI', Tahoma, Geneva, sans-serif"); ?>>Segoe UI</option>
                    <option value="'Roboto', sans-serif" <?php selected(isset($appearance_settings['font_family']) ? $appearance_settings['font_family'] : '', "'Roboto', sans-serif"); ?>>Roboto</option>
                </select>
                <p class="description"><?php _e('Font family for the chat widget.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="logo_url"><?php _e('Logo URL', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="text" name="conversaai_pro_appearance_settings[logo_url]" id="logo_url" class="regular-text" value="<?php echo esc_attr(isset($appearance_settings['logo_url']) ? $appearance_settings['logo_url'] : ''); ?>">
                <button type="button" class="button conversaai-media-uploader" data-target="logo_url"><?php _e('Select Image', 'conversaai-pro-wp'); ?></button>
                <p class="description"><?php _e('URL to your logo image to display in the chat header.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="bot_avatar"><?php _e('Bot Avatar URL', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="text" name="conversaai_pro_appearance_settings[bot_avatar]" id="bot_avatar" class="regular-text" value="<?php echo esc_attr(isset($appearance_settings['bot_avatar']) ? $appearance_settings['bot_avatar'] : ''); ?>">
                <button type="button" class="button conversaai-media-uploader" data-target="bot_avatar"><?php _e('Select Image', 'conversaai-pro-wp'); ?></button>
                <p class="description"><?php _e('URL to the avatar image for the bot.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="user_avatar"><?php _e('User Avatar URL', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="text" name="conversaai_pro_appearance_settings[user_avatar]" id="user_avatar" class="regular-text" value="<?php echo esc_attr(isset($appearance_settings['user_avatar']) ? $appearance_settings['user_avatar'] : ''); ?>">
                <button type="button" class="button conversaai-media-uploader" data-target="user_avatar"><?php _e('Select Image', 'conversaai-pro-wp'); ?></button>
                <p class="description"><?php _e('URL to the avatar image for the user.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="logo_size"><?php _e('Logo Size (px)', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="number" name="conversaai_pro_appearance_settings[logo_size]" id="logo_size" class="small-text" min="20" max="100" value="<?php echo esc_attr(isset($appearance_settings['logo_size']) ? $appearance_settings['logo_size'] : '28'); ?>">
                <p class="description"><?php _e('Size of the logo in the chat header (in pixels).', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="toggle_icon_size"><?php _e('Chat Icon Size (px)', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="number" name="conversaai_pro_appearance_settings[toggle_icon_size]" id="toggle_icon_size" class="small-text" min="40" max="100" value="<?php echo esc_attr(isset($appearance_settings['toggle_icon_size']) ? $appearance_settings['toggle_icon_size'] : '60'); ?>">
                <p class="description"><?php _e('Size of the chat toggle icon (in pixels).', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="widget_border_radius"><?php _e('Widget Border Radius', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="text" name="conversaai_pro_appearance_settings[widget_border_radius]" id="widget_border_radius" class="small-text" value="<?php echo esc_attr(isset($appearance_settings['widget_border_radius']) ? $appearance_settings['widget_border_radius'] : '16px'); ?>">
                <p class="description"><?php _e('Border radius for the chat widget container (e.g., 16px, 0.5rem).', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="input_border_radius"><?php _e('Input Border Radius', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="text" name="conversaai_pro_appearance_settings[input_border_radius]" id="input_border_radius" class="small-text" value="<?php echo esc_attr(isset($appearance_settings['input_border_radius']) ? $appearance_settings['input_border_radius'] : '8px'); ?>">
                <p class="description"><?php _e('Border radius for the chat input field (e.g., 8px, 0.5rem).', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="send_button_border_radius"><?php _e('Send Button Border Radius', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="text" name="conversaai_pro_appearance_settings[send_button_border_radius]" id="send_button_border_radius" class="small-text" value="<?php echo esc_attr(isset($appearance_settings['send_button_border_radius']) ? $appearance_settings['send_button_border_radius'] : '8px'); ?>">
                <p class="description"><?php _e('Border radius for the send button (e.g., 8px, 0.5rem).', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="auto_open_delay"><?php _e('Auto-Open Delay (seconds)', 'conversaai-pro-wp'); ?></label>
            </th>
            <td>
                <input type="number" name="conversaai_pro_appearance_settings[auto_open_delay]" id="auto_open_delay" class="small-text" min="0" max="300" value="<?php echo esc_attr(isset($appearance_settings['auto_open_delay']) ? $appearance_settings['auto_open_delay'] : '0'); ?>">
                <p class="description"><?php _e('Time in seconds after page load before automatically opening the chat widget. Set to 0 to disable.', 'conversaai-pro-wp'); ?></p>
            </td>
        </tr>
    </table>
    
    <div class="conversaai-settings-actions">
        <button type="submit" class="button button-primary conversaai-save-button" data-settings-group="appearance">
            <?php _e('Save Settings', 'conversaai-pro-wp'); ?>
        </button>
        <span class="conversaai-save-status"></span>
    </div>
</form>

<script>
jQuery(document).ready(function($) {
    // Preview updates
    function updatePreview() {
        var primaryColor = $('#primary_color').val();
        var textColor = $('#text_color').val();
        var titleColor = $('#title_color').val();
        var botMessageBg = $('#bot_message_bg').val();
        var userMessageBg = $('#user_message_bg').val();
        var fontFamily = $('#font_family').val();
        var widgetBorderRadius = $('#widget_border_radius').val();
        var inputBorderRadius = $('#input_border_radius').val();
        var sendButtonBorderRadius = $('#send_button_border_radius').val();
        
        // Update preview styles
        $('.conversaai-preview-header, .conversaai-preview-input button').css('background-color', primaryColor);
        $('.conversaai-preview-header h3').css('color', titleColor);
        $('.conversaai-preview-message-content').css('color', textColor);
        $('.conversaai-preview-bot-message .conversaai-preview-message-content').css('background-color', botMessageBg);
        $('.conversaai-preview-user-message .conversaai-preview-message-content').css('background-color', userMessageBg);
        $('.conversaai-preview-widget').css('font-family', fontFamily);
        
        // Update border radius properties
        $('.conversaai-preview-widget').css('border-radius', widgetBorderRadius);
        $('.conversaai-preview-input input').css('border-radius', inputBorderRadius);
        $('.conversaai-preview-input button').css('border-radius', sendButtonBorderRadius);
        $('.conversaai-preview-message-content').css('border-radius', inputBorderRadius);
    }
    
    // Update preview on change
    $('#primary_color, #text_color, #title_color, #bot_message_bg, #user_message_bg, #font_family, #widget_border_radius, #input_border_radius, #send_button_border_radius').on('change input', updatePreview);
    
    // Initialize preview
    updatePreview();
    
    // Media uploader
    $('.conversaai-media-uploader').click(function(e) {
        e.preventDefault();
        
        var button = $(this);
        var targetInput = $('#' + button.data('target'));
        
        var mediaUploader = wp.media({
            title: 'Select Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            targetInput.val(attachment.url);
        });
        
        mediaUploader.open();
    });

    // Device selector functionality
    $('.device-button').on('click', function() {
        const device = $(this).data('device');
        
        // Update active button
        $('.device-button').removeClass('active');
        $(this).addClass('active');
        
        // Show corresponding settings
        $('.device-settings').hide();
        $('.device-' + device).show();
    });

    // Mobile width/height toggle
    $('#mobile_width').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#mobile_width_custom, .custom-width-unit').show();
        } else {
            $('#mobile_width_custom, .custom-width-unit').hide();
        }
    });

    $('#mobile_height').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#mobile_height_custom, .custom-height-unit').show();
        } else {
            $('#mobile_height_custom, .custom-height-unit').hide();
        }
    });
});
</script>

<style>
/* Preview widget styling */
.conversaai-appearance-preview {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.conversaai-preview-widget {
    width: 100%;
    max-width: 400px;
    height: 400px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    background-color: white;
    display: flex;
    flex-direction: column;
    margin: 0 auto;
}

.conversaai-preview-header {
    background-color: #4c66ef;
    color: white;
    padding: 15px;
    text-align: center;
}

.conversaai-preview-header h3 {
    margin: 0;
    font-size: 16px;
}

.conversaai-preview-messages {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    background-color: #f7f9fc;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.conversaai-preview-message {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    max-width: 80%;
}

.conversaai-preview-bot-message {
    align-self: flex-start;
}

.conversaai-preview-user-message {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.conversaai-preview-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: #ddd;
    flex-shrink: 0;
}

.conversaai-preview-message-content {
    padding: 10px 12px;
    border-radius: 8px;
    background-color: #f0f4ff;
    color: #333;
}

.conversaai-preview-user-message .conversaai-preview-message-content {
    background-color: #e1ebff;
}

.conversaai-preview-input {
    display: flex;
    padding: 10px;
    background-color: white;
    border-top: 1px solid #eaedf3;
}

.conversaai-preview-input input {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #dce0e8;
    border-radius: 8px;
    font-size: 14px;
}

.conversaai-preview-input button {
    padding: 10px 15px;
    background-color: #4c66ef;
    color: white;
    border: none;
    border-radius: 8px;
    margin-left: 10px;
    cursor: pointer;
}

/* Form styling */
input[type="text"].small-text {
    width: 80px;
}

/* Device selector styling */
.responsive-settings-header {
    margin-top: 30px;
    margin-bottom: 15px;
    padding-bottom: 5px;
    border-bottom: 1px solid #ccc;
}

.device-selector {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.device-button {
    padding: 8px 15px;
    background: #f1f1f1;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.device-button.active {
    background: #4c66ef;
    color: white;
    border-color: #3951d1;
}

.device-button:hover:not(.active) {
    background: #e5e5e5;
}
</style>