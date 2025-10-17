<?php
/**
 * Knowledge Sources page template.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap conversaai-pro-knowledge-sources">
    <h1 class="conversaai-page-header"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="conversaai-admin-banner">
        <div class="conversaai-admin-banner-content">
            <h2><?php _e('Knowledge Sources', 'conversaai-pro-wp'); ?></h2>
            <p><?php _e('Configure where your AI assistant gets its information. Index WordPress content and WooCommerce products to enhance your knowledge base.', 'conversaai-pro-wp'); ?></p>
        </div>
        <div class="conversaai-admin-banner-icon">
            <span class="dashicons dashicons-database"></span>
        </div>
    </div>
    
    <div id="message" class="updated notice is-dismissible" style="display:none;">
        <p></p>
    </div>
    
    <div id="error-message" class="error notice is-dismissible" style="display:none;">
        <p></p>
    </div>
    
    <div class="conversaai-overview-cards">
        <div class="conversaai-card">
            <h2><span class="dashicons dashicons-chart-bar"></span> <?php _e('Knowledge Base Overview', 'conversaai-pro-wp'); ?></h2>
            <div class="conversaai-stats-grid">
                <div class="conversaai-stat-item">
                    <span class="conversaai-stat-label"><?php _e('WordPress Content', 'conversaai-pro-wp'); ?></span>
                    <span class="conversaai-stat-value" id="content-count"><?php echo number_format($stats['wp_content_count'] ?? 0); ?></span>
                </div>
                
                <div class="conversaai-stat-item">
                    <span class="conversaai-stat-label"><?php _e('WooCommerce Products', 'conversaai-pro-wp'); ?></span>
                    <span class="conversaai-stat-value" id="product-count"><?php echo number_format($stats['product_count'] ?? 0); ?></span>
                </div>
                
                <div class="conversaai-stat-item">
                    <span class="conversaai-stat-label"><?php _e('Manual Entries', 'conversaai-pro-wp'); ?></span>
                    <span class="conversaai-stat-value" id="manual-count"><?php echo number_format($stats['manual_count'] ?? 0); ?></span>
                </div>
                
                <div class="conversaai-stat-item">
                    <span class="conversaai-stat-label"><?php _e('Total Entries', 'conversaai-pro-wp'); ?></span>
                    <span class="conversaai-stat-value" id="total-count"><?php echo number_format($stats['total_count'] ?? 0); ?></span>
                </div>
            </div>
            
            <div class="conversaai-last-indexed">
                <div class="conversaai-last-indexed-item">
                    <span class="conversaai-last-indexed-label"><?php _e('Content Last Indexed:', 'conversaai-pro-wp'); ?></span>
                    <span class="conversaai-last-indexed-value" id="last-content-index"><?php echo esc_html($stats['last_content_index'] ?? __('Never', 'conversaai-pro-wp')); ?></span>
                </div>
                
                <div class="conversaai-last-indexed-item">
                    <span class="conversaai-last-indexed-label"><?php _e('Products Last Indexed:', 'conversaai-pro-wp'); ?></span>
                    <span class="conversaai-last-indexed-value" id="last-product-index"><?php echo esc_html($stats['last_product_index'] ?? __('Never', 'conversaai-pro-wp')); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="conversaai-tab-container">
        <div class="conversaai-tabs">
            <div class="conversaai-tab active" data-tab="wp-content"><?php _e('WordPress Content', 'conversaai-pro-wp'); ?></div>
            <div class="conversaai-tab" data-tab="woocommerce"><?php _e('WooCommerce Products', 'conversaai-pro-wp'); ?></div>
            <div class="conversaai-tab" data-tab="settings"><?php _e('Settings', 'conversaai-pro-wp'); ?></div>
        </div>
        
        <div class="conversaai-tab-content active" id="tab-wp-content">
            <div class="conversaai-card">
                <h3><?php _e('WordPress Content Indexing', 'conversaai-pro-wp'); ?></h3>
                <p><?php _e('Index your WordPress content (pages, posts, and custom post types) to make them available to your AI assistant. The system will extract information from headings (H1, H2, H3) and content to create knowledge base entries.', 'conversaai-pro-wp'); ?></p>
                
                <div class="conversaai-actions">
                    <button id="index-content" class="button button-primary">
                        <span class="dashicons dashicons-update"></span> <?php _e('Index Content', 'conversaai-pro-wp'); ?>
                    </button>
                    
                    <button id="force-index-content" class="button">
                        <span class="dashicons dashicons-update"></span> <?php _e('Force Re-Index All', 'conversaai-pro-wp'); ?>
                    </button>
                </div>
                
                <div id="content-indexing-progress" class="conversaai-progress-bar-container" style="display:none;">
                    <div class="conversaai-progress-bar-label"><?php _e('Indexing content...', 'conversaai-pro-wp'); ?></div>
                    <div class="conversaai-progress-bar">
                        <div class="conversaai-progress-bar-inner" style="width:0%"></div>
                    </div>
                </div>
                
                <h4><?php _e('Indexed Post Types', 'conversaai-pro-wp'); ?></h4>
                <div class="conversaai-post-types">
                    <?php foreach ($post_types as $post_type): ?>
                        <?php if ($post_type->name === 'attachment' || $post_type->name === 'product') continue; ?>
                        <label class="conversaai-checkbox-label">
                            <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($post_type->name); ?>" 
                                <?php checked(in_array($post_type->name, $indexing_settings['post_types'])); ?>>
                            <?php echo esc_html($post_type->labels->name); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <h4><?php _e('Content Stats', 'conversaai-pro-wp'); ?></h4>
                <div class="conversaai-content-stats">
                    <?php 
                    $indexed_post_types = array();
                    global $wpdb;
                    $table_name = $wpdb->prefix . CONVERSAAI_PRO_KNOWLEDGE_TABLE;
                    $post_type_counts = array();
                    
                    foreach ($indexing_settings['post_types'] as $post_type) {
                        $post_type_counts[$post_type] = 0;
                    }
                    
                    $entries = $wpdb->get_results("SELECT metadata FROM $table_name WHERE metadata IS NOT NULL", ARRAY_A);
                    foreach ($entries as $entry) {
                        $metadata = json_decode($entry['metadata'], true);
                        if (is_array($metadata) && isset($metadata['source']) && $metadata['source'] === 'wp_content' && isset($metadata['post_type'])) {
                            $post_type = $metadata['post_type'];
                            if (isset($post_type_counts[$post_type])) {
                                $post_type_counts[$post_type]++;
                            }
                        }
                    }
                    
                    foreach ($indexing_settings['post_types'] as $post_type) {
                        $post_type_obj = get_post_type_object($post_type);
                        $post_type_label = $post_type_obj ? $post_type_obj->labels->name : $post_type;
                        $indexed_post_types[] = array(
                            'name' => $post_type,
                            'label' => $post_type_label,
                            'count' => $post_type_counts[$post_type],
                        );
                    }
                    ?>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Post Type', 'conversaai-pro-wp'); ?></th>
                                <th><?php _e('Entries', 'conversaai-pro-wp'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($indexed_post_types as $type): ?>
                                <tr>
                                    <td><?php echo esc_html($type['label']); ?></td>
                                    <td><?php echo number_format($type['count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="conversaai-tab-content" id="tab-woocommerce">
            <div class="conversaai-card">
                <h3><?php _e('WooCommerce Product Indexing', 'conversaai-pro-wp'); ?></h3>
                
                <?php if (!$woocommerce_active): ?>
                    <div class="notice notice-warning inline">
                        <p><?php _e('WooCommerce is not active. Please install and activate WooCommerce to index products.', 'conversaai-pro-wp'); ?></p>
                    </div>
                <?php else: ?>
                    <p><?php _e('Index your WooCommerce products to make them available to your AI assistant. The system will create multiple knowledge base entries for each product, including basic information, price details, stock status, and specifications.', 'conversaai-pro-wp'); ?></p>
                    
                    <div class="conversaai-actions">
                        <button id="index-products" class="button button-primary">
                            <span class="dashicons dashicons-update"></span> <?php _e('Index Products', 'conversaai-pro-wp'); ?>
                        </button>
                        
                        <button id="force-index-products" class="button">
                            <span class="dashicons dashicons-update"></span> <?php _e('Force Re-Index All', 'conversaai-pro-wp'); ?>
                        </button>
                    </div>
                    
                    <div id="product-indexing-progress" class="conversaai-progress-bar-container" style="display:none;">
                        <div class="conversaai-progress-bar-label"><?php _e('Indexing products...', 'conversaai-pro-wp'); ?></div>
                        <div class="conversaai-progress-bar">
                            <div class="conversaai-progress-bar-inner" style="width:0%"></div>
                        </div>
                    </div>
                    
                    <h4><?php _e('Product Stats', 'conversaai-pro-wp'); ?></h4>
                    <?php 
                    $product_counts = array();
                    foreach ($entries as $entry) {
                        $metadata = json_decode($entry['metadata'], true);
                        if (is_array($metadata) && isset($metadata['source']) && $metadata['source'] === 'woocommerce_product' && isset($metadata['entry_type'])) {
                            $type = $metadata['entry_type'];
                            $product_counts[$type] = ($product_counts[$type] ?? 0) + 1;
                        }
                    }
                    
                    if (!empty($product_counts)):
                    ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Entry Type', 'conversaai-pro-wp'); ?></th>
                                <th><?php _e('Count', 'conversaai-pro-wp'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($product_counts as $type => $count): ?>
                                <tr>
                                    <td><?php echo esc_html(ucwords(str_replace('_', ' ', $type))); ?></td>
                                    <td><?php echo number_format($count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p><?php _e('No product entries have been indexed yet.', 'conversaai-pro-wp'); ?></p>
                    <?php endif; ?>
                    
                    <!-- WooCommerce Product Display Customization - New Section -->
                    <h4><?php _e('Product Display Customization', 'conversaai-pro-wp'); ?></h4>
                    
                    <?php
                    // Get WooCommerce display settings
                    $wc_display = get_option('conversaai_pro_woocommerce_display', array(
                        'product_intro' => '"%s" is a WooCommerce product.',
                        'product_price' => 'Priced at %s.',
                        'product_link' => 'View it <a href="%1$s" target="_blank" rel="noopener noreferrer" style="color:%2$s">here</a>.',
                        'link_color' => '#4c66ef',
                        'product_question' => 'What is the product "%s"?',
                        'product_detail_question' => 'Can you describe the product "%s" in detail?',
                        'show_categories' => true,
                        'categories_format' => 'Product categories: %s.'
                    ));
                    ?>
                    
                    <div class="conversaai-woocommerce-display-settings">
                        <p><?php _e('Customize how product information is displayed in AI responses:', 'conversaai-pro-wp'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Product Introduction', 'conversaai-pro-wp'); ?></th>
                                <td>
                                    <input type="text" class="regular-text" id="product_intro" name="conversaai_pro_woocommerce_display[product_intro]" 
                                        value="<?php echo esc_attr($wc_display['product_intro'] ?? '"%s" is a WooCommerce product.'); ?>">
                                    <p class="description"><?php _e('Format: %s will be replaced with product name', 'conversaai-pro-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Price Format', 'conversaai-pro-wp'); ?></th>
                                <td>
                                    <input type="text" class="regular-text" id="product_price" name="conversaai_pro_woocommerce_display[product_price]" 
                                        value="<?php echo esc_attr($wc_display['product_price'] ?? 'Priced at %s.'); ?>">
                                    <p class="description"><?php _e('Format: %s will be replaced with formatted price', 'conversaai-pro-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Product Link', 'conversaai-pro-wp'); ?></th>
                                <td>
                                    <input type="text" class="regular-text" id="product_link" name="conversaai_pro_woocommerce_display[product_link]" 
                                        value="<?php echo esc_attr($wc_display['product_link'] ?? 'View it <a href="%1$s" target="_blank" rel="noopener noreferrer" style="color:%2$s">here</a>.'); ?>">
                                    <p class="description"><?php _e('Format: %1$s = URL, %2$s = link color', 'conversaai-pro-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Link Color', 'conversaai-pro-wp'); ?></th>
                                <td>
                                    <input type="color" id="link_color" name="conversaai_pro_woocommerce_display[link_color]" 
                                        value="<?php echo esc_attr($wc_display['link_color'] ?? '#4c66ef'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Question Format', 'conversaai-pro-wp'); ?></th>
                                <td>
                                    <input type="text" class="regular-text" id="product_question" name="conversaai_pro_woocommerce_display[product_question]" 
                                        value="<?php echo esc_attr($wc_display['product_question'] ?? 'What is the product "%s"?'); ?>">
                                    <p class="description"><?php _e('Format: %s will be replaced with product name', 'conversaai-pro-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Detailed Question Format', 'conversaai-pro-wp'); ?></th>
                                <td>
                                    <input type="text" class="regular-text" id="product_detail_question" name="conversaai_pro_woocommerce_display[product_detail_question]" 
                                        value="<?php echo esc_attr($wc_display['product_detail_question'] ?? 'Can you describe the product "%s" in detail?'); ?>">
                                    <p class="description"><?php _e('Format: %s will be replaced with product name', 'conversaai-pro-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Show Categories', 'conversaai-pro-wp'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="show_categories" name="conversaai_pro_woocommerce_display[show_categories]" 
                                            value="1" <?php checked(!empty($wc_display['show_categories'])); ?>>
                                        <?php _e('Include product categories in the response', 'conversaai-pro-wp'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Categories Format', 'conversaai-pro-wp'); ?></th>
                                <td>
                                    <input type="text" class="regular-text" id="categories_format" name="conversaai_pro_woocommerce_display[categories_format]" 
                                        value="<?php echo esc_attr($wc_display['categories_format'] ?? 'Product categories: %s.'); ?>">
                                    <p class="description"><?php _e('Format: %s will be replaced with category list', 'conversaai-pro-wp'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="conversaai-display-example">
                            <h4><?php _e('Example Response', 'conversaai-pro-wp'); ?></h4>
                            <div class="conversaai-example-preview" id="response-preview">
                                <p><?php 
                                $product_intro = sprintf($wc_display['product_intro'] ?? '"%s" is a WooCommerce product.', 'Sample Product');
                                $product_price = sprintf($wc_display['product_price'] ?? 'Priced at %s.', wc_price(49.99));
                                $categories = 'Category 1, Category 2';
                                $categories_text = '';
                                if (!empty($wc_display['show_categories'])) {
                                    $categories_text = ' ' . sprintf($wc_display['categories_format'] ?? 'Product categories: %s.', $categories);
                                }
                                $product_link = sprintf($wc_display['product_link'] ?? 'View it <a href="%1$s" target="_blank" rel="noopener noreferrer" style="color:%2$s">here</a>.', '#', $wc_display['link_color'] ?? '#4c66ef');
                                
                                echo $product_intro . ' ' . $product_price . $categories_text . ' ' . $product_link;
                                ?></p>
                            </div>
                        </div>
                        
                        <button type="button" id="save-wc-display-settings" class="button button-primary">
                            <?php _e('Save Display Settings', 'conversaai-pro-wp'); ?>
                        </button>
                        <span id="wc-display-saving-indicator" style="display:none; margin-left: 10px;">
                            <span class="spinner is-active"></span> <?php _e('Saving...', 'conversaai-pro-wp'); ?>
                        </span>
                        <span id="wc-display-saved-message" style="display:none; margin-left: 10px; color: green;">
                            <?php _e('Settings saved!', 'conversaai-pro-wp'); ?>
                        </span>
                    </div>
                    
                    <style>
                    .conversaai-example-preview {
                        background: #f9f9f9;
                        border: 1px solid #ddd;
                        padding: 15px;
                        border-radius: 4px;
                        margin-top: 10px;
                        margin-bottom: 20px;
                    }
                    </style>
                    
                    <script>
                    jQuery(document).ready(function($) {
                        // Live preview of the display settings
                        function updatePreview() {
                            const productIntro = sprintf($('#product_intro').val() || '"%s" is a WooCommerce product.', 'Sample Product');
                            const productPrice = sprintf($('#product_price').val() || 'Priced at %s.', '<?php echo wc_price(49.99); ?>');
                            const linkColor = $('#link_color').val() || '#4c66ef';
                            const productLink = sprintf($('#product_link').val() || 'View it <a href="%1$s" target="_blank" rel="noopener noreferrer" style="color:%2$s">here</a>.', '#', linkColor);
                            
                            let categoriesText = '';
                            if ($('#show_categories').is(':checked')) {
                                categoriesText = ' ' + sprintf($('#categories_format').val() || 'Product categories: %s.', 'Category 1, Category 2');
                            }
                            
                            $('#response-preview p').html(productIntro + ' ' + productPrice + categoriesText + ' ' + productLink);
                        }
                        
                        // Simple sprintf implementation for preview
                        function sprintf(format) {
                            const args = Array.prototype.slice.call(arguments, 1);
                            return format.replace(/%(\d+)\$s|%s/g, function(match, number) {
                                if (match === '%s') {
                                    return args.shift() !== undefined ? args.shift() : '';
                                }
                                const num = parseInt(number, 10) - 1;
                                return num >= 0 && num < args.length ? args[num] : '';
                            });
                        }
                        
                        // Update preview on input change
                        $('#product_intro, #product_price, #product_link, #link_color, #show_categories, #categories_format').on('input change', updatePreview);
                        
                        // Save display settings via AJAX
                        $('#save-wc-display-settings').on('click', function() {
                            const $button = $(this);
                            const $indicator = $('#wc-display-saving-indicator');
                            const $message = $('#wc-display-saved-message');
                            
                            // Disable button and show saving indicator
                            $button.prop('disabled', true);
                            $indicator.show();
                            $message.hide();
                            
                            // Collect form data
                            const formData = {
                                product_intro: $('#product_intro').val(),
                                product_price: $('#product_price').val(),
                                product_link: $('#product_link').val(),
                                link_color: $('#link_color').val(),
                                product_question: $('#product_question').val(),
                                product_detail_question: $('#product_detail_question').val(),
                                show_categories: $('#show_categories').is(':checked') ? 1 : 0,
                                categories_format: $('#categories_format').val()
                            };
                            
                            // Send AJAX request
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'conversaai_save_wc_display_settings',
                                    nonce: '<?php echo wp_create_nonce('conversaai_knowledge_sources_nonce'); ?>',
                                    settings: JSON.stringify(formData)
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $message.show().delay(3000).fadeOut();
                                    } else {
                                        alert(response.data.message || '<?php _e('Error saving settings.', 'conversaai-pro-wp'); ?>');
                                    }
                                },
                                error: function() {
                                    alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
                                },
                                complete: function() {
                                    $indicator.hide();
                                    $button.prop('disabled', false);
                                }
                            });
                        });
                    });
                    </script>
                    <!-- End of WooCommerce Product Display Customization -->
                <?php endif; ?>
            </div>
        </div>
        
        <div class="conversaai-tab-content" id="tab-settings">
            <div class="conversaai-card">
                <h3><?php _e('Indexing Settings', 'conversaai-pro-wp'); ?></h3>
                <form id="indexing-settings-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Auto-Indexing', 'conversaai-pro-wp'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_index" value="1" <?php checked($indexing_settings['auto_index']); ?>>
                                    <?php _e('Automatically index content when it is published or updated', 'conversaai-pro-wp'); ?>
                                </label>
                                <p class="description"><?php _e('When enabled, new or updated content will be automatically added to the knowledge base.', 'conversaai-pro-wp'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Exclude Categories', 'conversaai-pro-wp'); ?></th>
                            <td>
                                <?php 
                                $categories = get_categories(array('hide_empty' => false));
                                if (!empty($categories)):
                                ?>
                                <div class="conversaai-categories-select">
                                    <?php foreach ($categories as $category): ?>
                                        <label>
                                            <input type="checkbox" name="exclude_categories[]" value="<?php echo esc_attr($category->term_id); ?>" 
                                                <?php checked(in_array($category->term_id, $indexing_settings['exclude_categories'])); ?>>
                                            <?php echo esc_html($category->name); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description"><?php _e('Content in these categories will be excluded from indexing.', 'conversaai-pro-wp'); ?></p>
                                <?php else: ?>
                                <p><?php _e('No categories found.', 'conversaai-pro-wp'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary" id="save-indexing-settings">
                            <?php _e('Save Settings', 'conversaai-pro-wp'); ?>
                        </button>
                        <span id="settings-saving-indicator" style="display:none; margin-left: 10px;">
                            <span class="spinner is-active"></span> <?php _e('Saving...', 'conversaai-pro-wp'); ?>
                        </span>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce('conversaai_knowledge_sources_nonce'); ?>';
    
    $('.conversaai-tab').on('click', function() {
        $('.conversaai-tab').removeClass('active');
        $(this).addClass('active');
        $('.conversaai-tab-content').removeClass('active');
        $('#tab-' + $(this).data('tab')).addClass('active');
    });
    
    $('#index-content').on('click', function() {
        // Collect currently checked post types
        const selectedPostTypes = [];
        $('input[name="post_types[]"]:checked').each(function() {
            selectedPostTypes.push($(this).val());
        });
        indexContent(false, selectedPostTypes);
    });

    $('#force-index-content').on('click', function() {
        // Collect currently checked post types
        const selectedPostTypes = [];
        $('input[name="post_types[]"]:checked').each(function() {
            selectedPostTypes.push($(this).val());
        });
        indexContent(true, selectedPostTypes);
    });
    
    $('#index-products').on('click', function() {
        indexProducts(false);
    });
    
    $('#force-index-products').on('click', function() {
        indexProducts(true);
    });
    
    $('#indexing-settings-form').on('submit', function(e) {
        e.preventDefault();
        saveIndexingSettings();
    });
    
    function indexContent(force = false, postTypes = []) {
        $('#content-indexing-progress').show();
        $('#index-content, #force-index-content').prop('disabled', true);
        animateProgressBar('#content-indexing-progress');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_index_content',
                nonce: nonce,
                force: force,
                post_types: postTypes
            },
            success: function(response) {
                stopProgressBar('#content-indexing-progress');
                if (response.success) {
                    showMessage(response.data.message);
                    updateStats(response.data.stats);
                } else {
                    showError(response.data.message || '<?php _e('Error indexing content.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function(xhr, status, error) {
                stopProgressBar('#content-indexing-progress');
                console.log('Index Content AJAX Error:', xhr.status, xhr.responseText);
                showError('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            },
            complete: function() {
                $('#content-indexing-progress').hide();
                $('#index-content, #force-index-content').prop('disabled', false);
            }
        });
    }
    
    function indexProducts(force = false) {
        $('#product-indexing-progress').show();
        $('#index-products, #force-index-products').prop('disabled', true);
        animateProgressBar('#product-indexing-progress');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_index_products',
                nonce: nonce,
                force: force
            },
            success: function(response) {
                stopProgressBar('#product-indexing-progress');
                if (response.success) {
                    showMessage(response.data.message);
                    updateStats(response.data.stats);
                } else {
                    showError(response.data.message || '<?php _e('Error indexing products.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function(xhr, status, error) {
                stopProgressBar('#product-indexing-progress');
                console.log('Index Products AJAX Error:', xhr.status, xhr.responseText);
                showError('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            },
            complete: function() {
                $('#product-indexing-progress').hide();
                $('#index-products, #force-index-products').prop('disabled', false);
            }
        });
    }
    
    function saveIndexingSettings() {
        $('#settings-saving-indicator').show();
        $('#save-indexing-settings').prop('disabled', true);
        
        const settings = {
            post_types: [],
            auto_index: $('#indexing-settings-form input[name="auto_index"]').is(':checked') ? 1 : 0,
            exclude_categories: []
        };
        
        $('#indexing-settings-form input[name="post_types[]"]:checked').each(function() {
            settings.post_types.push($(this).val());
        });
        
        $('#indexing-settings-form input[name="exclude_categories[]"]:checked').each(function() {
            settings.exclude_categories.push($(this).val());
        });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_save_indexing_settings',
                nonce: nonce,
                settings: JSON.stringify(settings)
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message);
                } else {
                    showError(response.data.message || '<?php _e('Error saving settings.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function(xhr, status, error) {
                console.log('Save Settings AJAX Error:', xhr.status, xhr.responseText);
                showError('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            },
            complete: function() {
                $('#settings-saving-indicator').hide();
                $('#save-indexing-settings').prop('disabled', false);
            }
        });
    }
    
    function updateStats(stats) {
        $('#content-count').text((stats.wp_content_count || 0).toLocaleString());
        $('#product-count').text((stats.product_count || 0).toLocaleString());
        $('#manual-count').text((stats.manual_count || 0).toLocaleString());
        $('#total-count').text((stats.total_count || 0).toLocaleString());
        $('#last-content-index').text(stats.last_content_index || '<?php _e('Never', 'conversaai-pro-wp'); ?>');
        $('#last-product-index').text(stats.last_product_index || '<?php _e('Never', 'conversaai-pro-wp'); ?>');
    }
    
    function showMessage(message) {
        $('#message p').text(message);
        $('#message').show();
        setTimeout(function() { $('#message').fadeOut(); }, 5000);
    }
    
    function showError(message) {
        $('#error-message p').text(message);
        $('#error-message').show();
        setTimeout(function() { $('#error-message').fadeOut(); }, 5000);
    }
    
    function animateProgressBar(selector) {
        const $progressBar = $(selector).find('.conversaai-progress-bar-inner');
        $progressBar.css('width', '0%');
        let progress = 0;
        const interval = setInterval(function() {
            progress += Math.random() * 10;
            if (progress > 90) {
                progress = 90;
                clearInterval(interval);
            }
            $progressBar.css('width', progress + '%');
        }, 500);
        $progressBar.data('interval', interval);
    }
    
    function stopProgressBar(selector) {
        const $progressBar = $(selector).find('.conversaai-progress-bar-inner');
        clearInterval($progressBar.data('interval'));
        $progressBar.css('width', '100%');
        setTimeout(function() { $progressBar.css('width', '0%'); }, 1000);
    }
});
</script>

<style>
.conversaai-overview-cards { margin: 20px 0; }
.conversaai-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
.conversaai-card h2 { display: flex; align-items: center; margin-top: 0; }
.conversaai-card h2 .dashicons { margin-right: 10px; color: #4c66ef; }
.conversaai-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px; }
.conversaai-stat-item { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 4px; }
.conversaai-stat-label { display: block; margin-bottom: 10px; color: #666; }
.conversaai-stat-value { display: block; font-size: 24px; font-weight: bold; color: #4c66ef; }
.conversaai-last-indexed { margin-top: 20px; display: flex; justify-content: space-between; }
.conversaai-last-indexed-item { font-size: 13px; color: #666; }
.conversaai-last-indexed-value { font-weight: bold; }
.conversaai-tab-container { margin-top: 20px; }
.conversaai-tabs { display: flex; border-bottom: 1px solid #ddd; margin-bottom: 0; }
.conversaai-tab { padding: 10px 20px; cursor: pointer; border: 1px solid transparent; border-bottom: none; margin-bottom: -1px; background: #f5f5f5; }
.conversaai-tab.active { background: white; border-color: #ddd; border-bottom-color: white; }
.conversaai-tab-content { display: none; }
.conversaai-tab-content.active { display: block; }
.conversaai-actions { display: flex; gap: 10px; margin: 15px 0; }
.conversaai-post-types { display: flex; flex-wrap: wrap; gap: 15px; margin: 15px 0; }
.conversaai-checkbox-label { display: inline-flex; align-items: center; margin-right: 15px; }
.conversaai-progress-bar-container { margin: 15px 0; }
.conversaai-progress-bar-label { margin-bottom: 5px; font-style: italic; }
.conversaai-progress-bar { height: 10px; background: #f0f0f0; border-radius: 5px; overflow: hidden; }
.conversaai-progress-bar-inner { height: 100%; width: 0; background: #4c66ef; transition: width 0.3s ease; }
.conversaai-categories-select { display: flex; flex-wrap: wrap; gap: 10px; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; }
.conversaai-categories-select label { display: block; width: 33.333%; }
@media (max-width: 782px) {
    .conversaai-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .conversaai-categories-select label { width: 100%; }
}
.dashicons {
 line-height: 1.4;
}
</style>