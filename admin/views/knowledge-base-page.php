<?php
/**
 * Knowledge Base admin page template.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap conversaai-pro-kb-admin">
    <h1 class="conversaai-page-header"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="conversaai-admin-banner">
        <div class="conversaai-admin-banner-content">
            <h2><?php _e('Knowledge Base Management', 'conversaai-pro-wp'); ?></h2>
            <p><?php _e('Manage the information your AI assistant uses to answer questions. Create, edit, and organize your knowledge base entries.', 'conversaai-pro-wp'); ?></p>
        </div>
        <div class="conversaai-admin-banner-icon">
            <span class="dashicons dashicons-book"></span>
        </div>
    </div>
        
    <div class="conversaai-kb-actions">
        <button id="conversaai-kb-add-new" class="button button-primary"><?php _e('Add New Entry', 'conversaai-pro-wp'); ?></button>
        
        <div class="conversaai-kb-import-export">
            <button id="conversaai-kb-import" class="button"><?php _e('Import', 'conversaai-pro-wp'); ?></button>
            <button id="conversaai-kb-export" class="button"><?php _e('Export', 'conversaai-pro-wp'); ?></button>
        </div>
    </div>
    
    <div class="conversaai-filters conversaai-kb-filters">
        <div class="conversaai-search-box">
            <label class="screen-reader-text" for="conversaai-kb-search"><?php _e('Search Knowledge Base:', 'conversaai-pro-wp'); ?></label>
            <input type="search" id="conversaai-kb-search" placeholder="<?php esc_attr_e('Search entries...', 'conversaai-pro-wp'); ?>">
            <button type="button" class="button" id="conversaai-kb-search-submit"><?php _e('Search', 'conversaai-pro-wp'); ?></button>
        </div>
        
        <div class="conversaai-filter-controls">
            <select id="conversaai-kb-topic-filter">
                <option value=""><?php _e('All Topics', 'conversaai-pro-wp'); ?></option>
                <?php foreach ($topics as $topic): ?>
                    <option value="<?php echo esc_attr($topic); ?>"><?php echo esc_html($topic); ?></option>
                <?php endforeach; ?>
            </select>
            
            <select id="conversaai-kb-status-filter">
                <option value=""><?php _e('All Statuses', 'conversaai-pro-wp'); ?></option>
                <option value="approved"><?php _e('Approved', 'conversaai-pro-wp'); ?></option>
                <option value="pending"><?php _e('Pending Approval', 'conversaai-pro-wp'); ?></option>
            </select>
            
            <select id="conversaai-kb-sort-by">
                <option value="id"><?php _e('Sort by ID', 'conversaai-pro-wp'); ?></option>
                <option value="usage_count"><?php _e('Sort by Usage', 'conversaai-pro-wp'); ?></option>
                <option value="confidence"><?php _e('Sort by Confidence', 'conversaai-pro-wp'); ?></option>
            </select>
            
            <select id="conversaai-kb-sort-order">
                <option value="DESC"><?php _e('Descending', 'conversaai-pro-wp'); ?></option>
                <option value="ASC"><?php _e('Ascending', 'conversaai-pro-wp'); ?></option>
            </select>
            
            <button type="button" class="button" id="conversaai-kb-filter-submit"><?php _e('Apply Filters', 'conversaai-pro-wp'); ?></button>
            <button type="button" class="button" id="conversaai-kb-filter-reset"><?php _e('Reset', 'conversaai-pro-wp'); ?></button>
        </div>
    </div>
    
    <div class="conversaai-kb-bulk-actions">
        <select id="conversaai-kb-bulk-action">
            <option value=""><?php _e('Bulk Actions', 'conversaai-pro-wp'); ?></option>
            <option value="approve"><?php _e('Approve', 'conversaai-pro-wp'); ?></option>
            <option value="disapprove"><?php _e('Disapprove', 'conversaai-pro-wp'); ?></option>
            <option value="delete"><?php _e('Delete', 'conversaai-pro-wp'); ?></option>
        </select>
        <button type="button" id="conversaai-kb-apply-bulk" class="button"><?php _e('Apply', 'conversaai-pro-wp'); ?></button>
        <span id="conversaai-kb-selected-count">0</span> <?php _e('entries selected', 'conversaai-pro-wp'); ?>
    </div>
    
    <div class="conversaai-kb-stats">
        <div class="conversaai-kb-stat">
            <span class="conversaai-kb-stat-label"><?php _e('Total Entries:', 'conversaai-pro-wp'); ?></span>
            <span class="conversaai-kb-stat-value" id="conversaai-kb-total-count"><?php echo number_format($kb_count); ?></span>
        </div>
    </div>
    
    <div class="conversaai-kb-table-container">
        <table class="wp-list-table widefat fixed striped conversaai-kb-table">
            <thead>
                <tr>
                    <th class="column-cb check-column">
                        <input type="checkbox" id="conversaai-kb-select-all">
                    </th>
                    <th class="column-question"><?php _e('Question', 'conversaai-pro-wp'); ?></th>
                    <th class="column-answer"><?php _e('Answer', 'conversaai-pro-wp'); ?></th>
                    <th class="column-topic"><?php _e('Topic', 'conversaai-pro-wp'); ?></th>
                    <th class="column-confidence"><?php _e('Confidence', 'conversaai-pro-wp'); ?></th>
                    <th class="column-status"><?php _e('Status', 'conversaai-pro-wp'); ?></th>
                    <th class="column-usage"><?php _e('Usage', 'conversaai-pro-wp'); ?></th>
                    <th class="column-actions"><?php _e('Actions', 'conversaai-pro-wp'); ?></th>
                </tr>
            </thead>
            <tbody id="conversaai-kb-entries">
                <?php if (empty($kb_entries)): ?>
                    <tr>
                        <td colspan="8"><?php _e('No knowledge base entries found.', 'conversaai-pro-wp'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($kb_entries as $entry): ?>
                        <tr data-id="<?php echo esc_attr($entry['id']); ?>">
                            <td class="column-cb check-column">
                                <input type="checkbox" class="conversaai-kb-select-entry" value="<?php echo esc_attr($entry['id']); ?>">
                            </td>
                            <td class="column-question"><?php echo esc_html($entry['question']); ?></td>
                            <td class="column-answer"><?php echo wp_kses_post($entry['answer']); ?></td>
                            <td class="column-topic"><?php echo esc_html($entry['topic']); ?></td>
                            <td class="column-confidence"><?php echo esc_html(number_format($entry['confidence'] * 100, 0) . '%'); ?></td>
                            <td class="column-status">
                                <?php if ($entry['approved']): ?>
                                    <span class="conversaai-status-approved"><?php _e('Approved', 'conversaai-pro-wp'); ?></span>
                                <?php else: ?>
                                    <span class="conversaai-status-pending"><?php _e('Pending', 'conversaai-pro-wp'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-usage"><?php echo esc_html($entry['usage_count']); ?></td>
                            <td class="column-actions">
                                <button type="button" class="button conversaai-kb-edit" data-id="<?php echo esc_attr($entry['id']); ?>"><?php _e('Edit', 'conversaai-pro-wp'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="conversaai-kb-pagination">
        <div class="tablenav-pages">
            <span class="displaying-num" id="conversaai-kb-displaying-num">
                <?php echo sprintf(_n('%s item', '%s items', $kb_count, 'conversaai-pro-wp'), number_format($kb_count)); ?>
            </span>
            <span class="pagination-links">
                <button type="button" class="button first-page" id="conversaai-kb-first-page" aria-label="<?php esc_attr_e('First page', 'conversaai-pro-wp'); ?>" <?php echo ($kb_count <= 20) ? 'disabled' : ''; ?>>
                    <span class="screen-reader-text"><?php _e('First page', 'conversaai-pro-wp'); ?></span>
                    <span aria-hidden="true">«</span>
                </button>
                <button type="button" class="button prev-page" id="conversaai-kb-prev-page" aria-label="<?php esc_attr_e('Previous page', 'conversaai-pro-wp'); ?>" <?php echo ($kb_count <= 20) ? 'disabled' : ''; ?>>
                    <span class="screen-reader-text"><?php _e('Previous page', 'conversaai-pro-wp'); ?></span>
                    <span aria-hidden="true">‹</span>
                </button>
                <span class="paging-input">
                    <span id="conversaai-kb-current-page">1</span>
                    <span class="tablenav-paging-text">
                        <?php _e('of', 'conversaai-pro-wp'); ?>
                        <span id="conversaai-kb-total-pages"><?php echo max(1, ceil($kb_count / 20)); ?></span>
                    </span>
                </span>
                <button type="button" class="button next-page" id="conversaai-kb-next-page" aria-label="<?php esc_attr_e('Next page', 'conversaai-pro-wp'); ?>" <?php echo ($kb_count <= 20) ? 'disabled' : ''; ?>>
                    <span class="screen-reader-text"><?php _e('Next page', 'conversaai-pro-wp'); ?></span>
                    <span aria-hidden="true">›</span>
                </button>
                <button type="button" class="button last-page" id="conversaai-kb-last-page" aria-label="<?php esc_attr_e('Last page', 'conversaai-pro-wp'); ?>" <?php echo ($kb_count <= 20) ? 'disabled' : ''; ?>>
                    <span class="screen-reader-text"><?php _e('Last page', 'conversaai-pro-wp'); ?></span>
                    <span aria-hidden="true">»</span>
                </button>
            </span>
        </div>
    </div>
</div>

<!-- Entry Modal -->
<div id="conversaai-kb-entry-modal" class="conversaai-modal" style="display: none;">
    <div class="conversaai-modal-content">
        <span class="conversaai-modal-close">&times;</span>
        <h2 id="conversaai-kb-modal-title"><?php _e('Add Knowledge Base Entry', 'conversaai-pro-wp'); ?></h2>
        
        <form id="conversaai-kb-entry-form">
            <input type="hidden" id="conversaai-kb-entry-id" value="0">
            
            <div class="conversaai-form-row">
                <label for="conversaai-kb-entry-question"><?php _e('Question:', 'conversaai-pro-wp'); ?></label>
                <input type="text" id="conversaai-kb-entry-question" class="widefat" required>
                <p class="description"><?php _e('Enter the question that users might ask.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-row">
                <label for="conversaai-kb-entry-answer"><?php _e('Answer:', 'conversaai-pro-wp'); ?></label>
                <textarea id="conversaai-kb-entry-answer" class="widefat" rows="6" required></textarea>
                <p class="description"><?php _e('Enter the answer to the question. Basic HTML is allowed.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-row">
                <label for="conversaai-kb-entry-topic"><?php _e('Topic:', 'conversaai-pro-wp'); ?></label>
                <input type="text" id="conversaai-kb-entry-topic" class="widefat" list="conversaai-kb-topics">
                <datalist id="conversaai-kb-topics">
                    <?php foreach ($topics as $topic): ?>
                        <option value="<?php echo esc_attr($topic); ?>">
                    <?php endforeach; ?>
                </datalist>
                <p class="description"><?php _e('Categorize this entry by topic. You can select an existing topic or create a new one.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-row">
                <label for="conversaai-kb-entry-confidence"><?php _e('Confidence Threshold:', 'conversaai-pro-wp'); ?></label>
                <div class="conversaai-range-control">
                    <input type="range" id="conversaai-kb-entry-confidence" min="0" max="1" step="0.05" value="0.85">
                    <span id="conversaai-kb-confidence-value">85%</span>
                </div>
                <p class="description"><?php _e('Minimum confidence level required to use this entry as an answer. Higher values require closer question matches.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-row">
                <label for="conversaai-kb-entry-approved"><?php _e('Status:', 'conversaai-pro-wp'); ?></label>
                <select id="conversaai-kb-entry-approved">
                    <option value="1"><?php _e('Approved', 'conversaai-pro-wp'); ?></option>
                    <option value="0"><?php _e('Pending Approval', 'conversaai-pro-wp'); ?></option>
                </select>
                <p class="description"><?php _e('Approved entries will be used to answer user queries. Pending entries are stored but not used.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-actions">
                <button type="button" id="conversaai-kb-generate-answer" class="button"><?php _e('Generate Answer with AI', 'conversaai-pro-wp'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Save Entry', 'conversaai-pro-wp'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Import Modal -->
<div id="conversaai-kb-import-modal" class="conversaai-modal" style="display: none;">
    <div class="conversaai-modal-content">
        <span class="conversaai-modal-close">&times;</span>
        <h2><?php _e('Import Knowledge Base', 'conversaai-pro-wp'); ?></h2>
        
        <form id="conversaai-kb-import-form">
            <div class="conversaai-form-row">
                <label for="conversaai-kb-import-file"><?php _e('File:', 'conversaai-pro-wp'); ?></label>
                <input type="file" id="conversaai-kb-import-file" accept=".csv,.json">
                <p class="description"><?php _e('Select a CSV or JSON file to import. Maximum file size: 5MB.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-row">
                <label for="conversaai-kb-import-type"><?php _e('File Type:', 'conversaai-pro-wp'); ?></label>
                <select id="conversaai-kb-import-type">
                    <option value="csv"><?php _e('CSV', 'conversaai-pro-wp'); ?></option>
                    <option value="json"><?php _e('JSON', 'conversaai-pro-wp'); ?></option>
                </select>
            </div>
            
            <div class="conversaai-form-row csv-options">
                <label>
                    <input type="checkbox" id="conversaai-kb-skip-header" checked>
                    <?php _e('Skip header row (CSV only)', 'conversaai-pro-wp'); ?>
                </label>
                <p class="description"><?php _e('If your CSV file has a header row with column names, check this box to skip it.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-row">
                <label>
                    <input type="checkbox" id="conversaai-kb-update-existing">
                    <?php _e('Update existing entries', 'conversaai-pro-wp'); ?>
                </label>
                <p class="description"><?php _e('If an imported question already exists, update it with the new data.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-row">
                <label>
                    <input type="checkbox" id="conversaai-kb-approve-all" checked>
                    <?php _e('Approve all entries', 'conversaai-pro-wp'); ?>
                </label>
                <p class="description"><?php _e('Automatically approve all imported entries.', 'conversaai-pro-wp'); ?></p>
            </div>
            
            <div class="conversaai-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Import', 'conversaai-pro-wp'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Export Modal -->
<div id="conversaai-kb-export-modal" class="conversaai-modal" style="display: none;">
    <div class="conversaai-modal-content">
        <span class="conversaai-modal-close">&times;</span>
        <h2><?php _e('Export Knowledge Base', 'conversaai-pro-wp'); ?></h2>
        
        <form id="conversaai-kb-export-form">
            <div class="conversaai-form-row">
                <label for="conversaai-kb-export-format"><?php _e('Format:', 'conversaai-pro-wp'); ?></label>
                <select id="conversaai-kb-export-format">
                    <option value="csv"><?php _e('CSV', 'conversaai-pro-wp'); ?></option>
                    <option value="json"><?php _e('JSON', 'conversaai-pro-wp'); ?></option>
                </select>
            </div>
            
            <div class="conversaai-form-row">
                <label for="conversaai-kb-export-topic"><?php _e('Topic:', 'conversaai-pro-wp'); ?></label>
                <select id="conversaai-kb-export-topic">
                    <option value=""><?php _e('All Topics', 'conversaai-pro-wp'); ?></option>
                    <?php foreach ($topics as $topic): ?>
                        <option value="<?php echo esc_attr($topic); ?>"><?php echo esc_html($topic); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="conversaai-form-row">
                <label>
                    <input type="checkbox" id="conversaai-kb-export-approved-only">
                    <?php _e('Only approved entries', 'conversaai-pro-wp'); ?>
                </label>
            </div>
            
            <div class="conversaai-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Export', 'conversaai-pro-wp'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Variables
    let currentPage = 1;
    let totalPages = parseInt($('#conversaai-kb-total-pages').text()) || 1;
    let currentSearchTerm = '';
    let currentTopic = '';
    let currentStatus = '';
    let currentSortBy = 'id';
    let currentSortOrder = 'DESC';
    let selectedEntries = [];
    
    // Initialize range slider display
    $('#conversaai-kb-entry-confidence').on('input', function() {
        const value = $(this).val();
        $('#conversaai-kb-confidence-value').text(Math.round(value * 100) + '%');
    });
    
    // Modal handling
    $('.conversaai-modal-close').on('click', function() {
        $(this).closest('.conversaai-modal').hide();
    });
    
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('conversaai-modal')) {
            $('.conversaai-modal').hide();
        }
    });
    
    // Add new entry
    $('#conversaai-kb-add-new').on('click', function() {
        // Reset form
        $('#conversaai-kb-entry-form')[0].reset();
        $('#conversaai-kb-entry-id').val('0');
        $('#conversaai-kb-modal-title').text('<?php _e('Add Knowledge Base Entry', 'conversaai-pro-wp'); ?>');
        $('#conversaai-kb-entry-confidence').val(0.85).trigger('input');
        $('#conversaai-kb-entry-approved').val('1');
        
        // Show modal
        $('#conversaai-kb-entry-modal').show();
    });
    
    // Edit entry
    $(document).on('click', '.conversaai-kb-edit', function() {
        const entryId = $(this).data('id');
        
        // Show loading state
        $('#conversaai-kb-entry-modal-title').text('<?php _e('Loading...', 'conversaai-pro-wp'); ?>');
        $('#conversaai-kb-entry-modal').show();
        
        // Fetch the entry data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_get_kb_entry',
                nonce: '<?php echo wp_create_nonce('conversaai_kb_nonce'); ?>',
                id: entryId
            },
            success: function(response) {
                if (response.success) {
                    const entry = response.data.entry;
                    
                    // Populate form
                    $('#conversaai-kb-entry-id').val(entry.id);
                    $('#conversaai-kb-entry-question').val(entry.question);
                    $('#conversaai-kb-entry-answer').val(entry.answer);
                    $('#conversaai-kb-entry-topic').val(entry.topic);
                    
                    // Parse confidence value
                    const confidence = parseFloat(entry.confidence);
                    $('#conversaai-kb-entry-confidence').val(confidence).trigger('input');
                    
                    // Set status
                    $('#conversaai-kb-entry-approved').val(entry.approved == '1' ? '1' : '0');
                    
                    // Update modal title
                    $('#conversaai-kb-modal-title').text('<?php _e('Edit Knowledge Base Entry', 'conversaai-pro-wp'); ?>');
                } else {
                    alert(response.data.message || '<?php _e('Error loading entry.', 'conversaai-pro-wp'); ?>');
                    $('#conversaai-kb-entry-modal').hide();
                }
            },
            error: function() {
                alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
                $('#conversaai-kb-entry-modal').hide();
            }
        });
    });
    
    // Save entry form submission
    $('#conversaai-kb-entry-form').on('submit', function(e) {
        e.preventDefault();
        
        const entryId = $('#conversaai-kb-entry-id').val();
        const question = $('#conversaai-kb-entry-question').val();
        const answer = $('#conversaai-kb-entry-answer').val();
        const topic = $('#conversaai-kb-entry-topic').val();
        const confidence = $('#conversaai-kb-entry-confidence').val();
        const approved = $('#conversaai-kb-entry-approved').val() === "1" ? 1 : 0;
        
        if (!question || !answer) {
            alert('<?php _e('Question and answer are required.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_save_kb_entry',
                nonce: '<?php echo wp_create_nonce('conversaai_kb_nonce'); ?>',
                id: entryId,
                question: question,
                answer: answer,
                topic: topic,
                confidence: confidence,
                approved: approved
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert(response.data.message);
                    
                    // Hide modal
                    $('#conversaai-kb-entry-modal').hide();
                    
                    // Reload entries
                    loadEntries(currentPage);
                } else {
                    alert(response.data.message || '<?php _e('Error saving entry.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            }
        });
    });
    
    // Generate answer with AI
    $('#conversaai-kb-generate-answer').on('click', function() {
        const question = $('#conversaai-kb-entry-question').val();
        
        if (!question) {
            alert('<?php _e('Please enter a question first.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        // Show loading state
        $(this).prop('disabled', true).text('<?php _e('Generating...', 'conversaai-pro-wp'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_generate_answer',
                nonce: '<?php echo wp_create_nonce('conversaai_training_nonce'); ?>',
                question: question
            },
            success: function(response) {
                if (response.success) {
                    $('#conversaai-kb-entry-answer').val(response.data.answer);
                } else {
                    alert(response.data.message || '<?php _e('Error generating answer.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            },
            complete: function() {
                $('#conversaai-kb-generate-answer').prop('disabled', false).text('<?php _e('Generate Answer with AI', 'conversaai-pro-wp'); ?>');
            }
        });
    });
    
    // Import modal
    $('#conversaai-kb-import').on('click', function() {
        $('#conversaai-kb-import-form')[0].reset();
        $('#conversaai-kb-import-modal').show();
    });
    
    // Export modal
    $('#conversaai-kb-export').on('click', function() {
        $('#conversaai-kb-export-form')[0].reset();
        $('#conversaai-kb-export-modal').show();
    });
    
    // Import form submission
    $('#conversaai-kb-import-form').on('submit', function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('conversaai-kb-import-file');
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('<?php _e('Please select a file to import.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        const file = fileInput.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('<?php _e('File is too large. Maximum size is 5MB.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        const fileType = $('#conversaai-kb-import-type').val();
        const skipHeader = $('#conversaai-kb-skip-header').is(':checked');
        const updateExisting = $('#conversaai-kb-update-existing').is(':checked');
        const approveAll = $('#conversaai-kb-approve-all').is(':checked');
        
        // Show loading overlay
        $('body').append('<div id="conversaai-processing-overlay"><div class="conversaai-processing-message"><?php _e('Importing...', 'conversaai-pro-wp'); ?></div></div>');
        
        // Read file
        const reader = new FileReader();
        reader.onload = function(e) {
            const fileContent = e.target.result;
            
            // Send to server
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_import_kb',
                    nonce: '<?php echo wp_create_nonce('conversaai_kb_nonce'); ?>',
                    file_content: fileContent,
                    file_type: fileType,
                    skip_header: skipHeader,
                    update_existing: updateExisting,
                    approve_all: approveAll
                },
                success: function(response) {
                    $('#conversaai-processing-overlay').remove();
                    
                    if (response.success) {
                        alert(response.data.message);
                        $('#conversaai-kb-import-modal').hide();
                        loadEntries(1); // Reset to first page
                    } else {
                        alert(response.data.message || '<?php _e('Error importing entries.', 'conversaai-pro-wp'); ?>');
                    }
                },
                error: function() {
                    $('#conversaai-processing-overlay').remove();
                    alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
                }
            });
        };
        
        reader.readAsText(file);
    });
    
    // Export form submission
    $('#conversaai-kb-export-form').on('submit', function(e) {
        e.preventDefault();
        
        const format = $('#conversaai-kb-export-format').val();
        const topic = $('#conversaai-kb-export-topic').val();
        const approvedOnly = $('#conversaai-kb-export-approved-only').is(':checked');
        
        // Show loading overlay
        $('body').append('<div id="conversaai-processing-overlay"><div class="conversaai-processing-message"><?php _e('Exporting...', 'conversaai-pro-wp'); ?></div></div>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_export_kb',
                nonce: '<?php echo wp_create_nonce('conversaai_kb_nonce'); ?>',
                format: format,
                topic: topic,
                approved_only: approvedOnly
            },
            success: function(response) {
                $('#conversaai-processing-overlay').remove();
                
                if (response.success) {
                    // Create a download link
                    const blob = new Blob([response.data.data], { type: response.data.mime_type });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = response.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    
                    $('#conversaai-kb-export-modal').hide();
                } else {
                    alert(response.data.message || '<?php _e('Error exporting entries.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function() {
                $('#conversaai-processing-overlay').remove();
                alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            }
        });
    });
    
    // Toggle CSV options based on file type
    $('#conversaai-kb-import-type').on('change', function() {
        if ($(this).val() === 'csv') {
            $('.csv-options').show();
        } else {
            $('.csv-options').hide();
        }
    }).trigger('change');

    // Entry selection and bulk actions
    $(document).on('change', '#conversaai-kb-select-all', function() {
        const isChecked = $(this).prop('checked');
        $('.conversaai-kb-select-entry').prop('checked', isChecked);
        updateSelectedEntries();
    });

    $(document).on('change', '.conversaai-kb-select-entry', function() {
        updateSelectedEntries();
    });

    
    function updateSelectedEntries() {
        selectedEntries = [];
        $('.conversaai-kb-select-entry:checked').each(function() {
            selectedEntries.push($(this).val());
            $(this).closest('tr').addClass('selected');
        });
        
        // Remove selected class from unchecked rows
        $('.conversaai-kb-select-entry:not(:checked)').each(function() {
            $(this).closest('tr').removeClass('selected');
        });
        
        $('#conversaai-kb-selected-count').text(selectedEntries.length);
        
        // Update select all checkbox
        const allChecked = $('.conversaai-kb-select-entry:checked').length === $('.conversaai-kb-select-entry').length;
        $('#conversaai-kb-select-all').prop('checked', allChecked && $('.conversaai-kb-select-entry').length > 0);
    }
    
    // Apply bulk action
    $('#conversaai-kb-apply-bulk').on('click', function() {
        const action = $('#conversaai-kb-bulk-action').val();
        
        if (!action) {
            alert('<?php _e('Please select an action.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        if (selectedEntries.length === 0) {
            alert('<?php _e('Please select at least one entry.', 'conversaai-pro-wp'); ?>');
            return;
        }
        
        let confirmMessage = '';
        
        switch (action) {
            case 'approve':
                confirmMessage = '<?php _e('Are you sure you want to approve the selected entries?', 'conversaai-pro-wp'); ?>';
                break;
            case 'disapprove':
                confirmMessage = '<?php _e('Are you sure you want to disapprove the selected entries?', 'conversaai-pro-wp'); ?>';
                break;
            case 'delete':
                confirmMessage = '<?php _e('Are you sure you want to delete the selected entries? This cannot be undone.', 'conversaai-pro-wp'); ?>';
                break;
        }
        
        if (confirm(confirmMessage)) {
            applyBulkAction(action, selectedEntries);
        }
    });
    
    // Apply bulk action function
    function applyBulkAction(action, entryIds) {
        if (!action || entryIds.length === 0) {
            console.error('Invalid action or no entries selected');
            return;
        }
        
        console.log('Applying bulk action:', action, 'to entries:', entryIds);
        
        // Show loading overlay
        $('body').append('<div id="conversaai-processing-overlay"><div class="conversaai-processing-message"><?php _e('Processing...', 'conversaai-pro-wp'); ?></div></div>');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_bulk_kb_action',
                nonce: '<?php echo wp_create_nonce('conversaai_kb_nonce'); ?>',
                bulk_action: action,
                entry_ids: entryIds
            },
            success: function(response) {
                $('#conversaai-processing-overlay').remove();
                console.log('Bulk action response:', response);
                
                if (response.success) {
                    alert(response.data.message);
                    
                    // Reset selected entries
                    selectedEntries = [];
                    updateSelectedEntries();
                    
                    // Reload entries
                    loadEntries(currentPage);
                } else {
                    console.error('Bulk action error:', response.data.message);
                    alert(response.data.message || '<?php _e('Error performing bulk action.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function(xhr, status, error) {
                $('#conversaai-processing-overlay').remove();
                console.error('AJAX error:', status, error, xhr.responseText);
                alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            }
        });
    }
    
    // Search functionality
    $('#conversaai-kb-search-submit').on('click', function() {
        currentSearchTerm = $('#conversaai-kb-search').val().trim();
        currentPage = 1;
        loadEntries(currentPage);
    });
    
    // Filter functionality
    $('#conversaai-kb-filter-submit').on('click', function() {
        currentTopic = $('#conversaai-kb-topic-filter').val();
        currentStatus = $('#conversaai-kb-status-filter').val();
        currentSortBy = $('#conversaai-kb-sort-by').val();
        currentSortOrder = $('#conversaai-kb-sort-order').val();
        currentPage = 1;
        loadEntries(currentPage);
    });
    
    // Reset filters
    $('#conversaai-kb-filter-reset').on('click', function() {
        $('#conversaai-kb-search').val('');
        $('#conversaai-kb-topic-filter').val('');
        $('#conversaai-kb-status-filter').val('');
        $('#conversaai-kb-sort-by').val('id');
        $('#conversaai-kb-sort-order').val('DESC');
        currentSearchTerm = '';
        currentTopic = '';
        currentStatus = '';
        currentSortBy = 'id';
        currentSortOrder = 'DESC';
        currentPage = 1;
        loadEntries(currentPage);
    });
    
    // Pagination handlers
    $('#conversaai-kb-first-page').on('click', function() {
        if ($(this).prop('disabled')) return;
        currentPage = 1;
        loadEntries(currentPage);
    });
    
    $('#conversaai-kb-prev-page').on('click', function() {
        if ($(this).prop('disabled')) return;
        currentPage--;
        loadEntries(currentPage);
    });
    
    $('#conversaai-kb-next-page').on('click', function() {
        if ($(this).prop('disabled')) return;
        currentPage++;
        loadEntries(currentPage);
    });
    
    $('#conversaai-kb-last-page').on('click', function() {
        if ($(this).prop('disabled')) return;
        currentPage = totalPages;
        loadEntries(currentPage);
    });
    
    // Function to load entries
    function loadEntries(page) {
        // Show loading state
        $('#conversaai-kb-entries').html('<tr><td colspan="8"><div class="spinner is-active" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 10px;"></div></td></tr>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_get_kb_entries',
                nonce: '<?php echo wp_create_nonce('conversaai_kb_nonce'); ?>',
                page: page,
                per_page: 20,
                topic: currentTopic,
                status: currentStatus,
                search: currentSearchTerm,
                orderby: currentSortBy,
                order: currentSortOrder
            },
            success: function(response) {
                if (response.success) {
                    // Update pagination info
                    currentPage = response.data.current_page;
                    totalPages = response.data.pages;
                    
                    $('#conversaai-kb-current-page').text(currentPage);
                    $('#conversaai-kb-total-pages').text(totalPages);
                    $('#conversaai-kb-total-count').text(response.data.total);
                    $('#conversaai-kb-displaying-num').text(
                        response.data.total === 1 
                            ? '<?php _e('1 item', 'conversaai-pro-wp'); ?>' 
                            : '<?php _e('%s items', 'conversaai-pro-wp'); ?>'.replace('%s', response.data.total)
                    );
                    
                    // Update pagination buttons
                    $('#conversaai-kb-first-page, #conversaai-kb-prev-page').prop('disabled', currentPage <= 1);
                    $('#conversaai-kb-next-page, #conversaai-kb-last-page').prop('disabled', currentPage >= totalPages);
                    
                    // Update table contents
                    if (response.data.entries.length === 0) {
                        $('#conversaai-kb-entries').html('<tr><td colspan="8"><?php _e('No knowledge base entries found.', 'conversaai-pro-wp'); ?></td></tr>');
                    } else {
                        let html = '';
                        
                        response.data.entries.forEach(function(entry) {
                            html += `
                                <tr data-id="${entry.id}">
                                    <td class="column-cb check-column">
                                        <input type="checkbox" class="conversaai-kb-select-entry" value="${entry.id}">
                                    </td>
                                    <td class="column-question">${entry.question}</td>
                                    <td class="column-answer">${entry.answer}</td>
                                    <td class="column-topic">${entry.topic || ''}</td>
                                    <td class="column-confidence">${Math.round(entry.confidence * 100)}%</td>
                                    <td class="column-status">
                                        ${entry.approved == 1 
                                            ? '<span class="conversaai-status-approved"><?php _e('Approved', 'conversaai-pro-wp'); ?></span>' 
                                            : '<span class="conversaai-status-pending"><?php _e('Pending', 'conversaai-pro-wp'); ?></span>'}
                                    </td>
                                    <td class="column-usage">${entry.usage_count}</td>
                                    <td class="column-actions">
                                        <button type="button" class="button conversaai-kb-edit" data-id="${entry.id}"><?php _e('Edit', 'conversaai-pro-wp'); ?></button>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        $('#conversaai-kb-entries').html(html);
                    }
                    
                    // Reset selection
                    selectedEntries = [];
                    $('#conversaai-kb-select-all').prop('checked', false);
                    $('#conversaai-kb-selected-count').text('0');
                } else {
                    alert(response.data.message || '<?php _e('Error loading entries.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            }
        });
    }
    
    // Load initial entries
    loadEntries(1);
});
</script>

<style>
/* Knowledge Base Admin styles */
.conversaai-kb-actions {
    display: flex;
    justify-content: space-between;
    margin: 20px 0;
}

.conversaai-kb-import-export {
    display: flex;
    gap: 10px;
}

.conversaai-kb-filters {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 15px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.conversaai-filter-controls {
    display: flex;
    gap: 10px;
}

.conversaai-kb-bulk-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding: 10px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.conversaai-kb-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.conversaai-kb-stat {
    background: white;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.conversaai-kb-stat-label {
    font-weight: 500;
}

.conversaai-kb-stat-value {
    font-weight: bold;
    font-size: 16px;
    color: #4c66ef;
}

.conversaai-kb-table-container {
    margin-bottom: 20px;
}

.conversaai-kb-table {
    table-layout: fixed;
    padding:0px;

}

.conversaai-kb-table .column-cb {
    width: 15px;
    padding: 12px 12px;
}

.conversaai-kb-table .column-question {
    width: 20%;
}

.conversaai-kb-table .column-answer {
    width: 40%;
}

.conversaai-kb-table .column-topic {
    width: 10%;
}

.conversaai-kb-table .column-confidence,
.conversaai-kb-table .column-status,
.conversaai-kb-table .column-usage {
    width: 7%;
    text-align: center;
}

.conversaai-kb-table .column-actions {
    width: 5%;
    text-align: right;
}

.conversaai-status-approved {
    background-color: #d4edda;
    color: #155724;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.conversaai-status-pending {
    background-color: #fff3cd;
    color: #856404;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
}

/* Modal styles */
.conversaai-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.conversaai-modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 60%;
    max-width: 800px;
    position: relative;
}

.conversaai-modal-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}

.conversaai-form-row {
    margin-bottom: 15px;
}

.conversaai-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.conversaai-range-control {
    display: flex;
    align-items: center;
    gap: 10px;
}

.conversaai-range-control input {
    flex: 1;
    max-width: 300px;
}

.conversaai-form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

/* Processing overlay */
#conversaai-processing-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 100001;
}

.conversaai-processing-message {
    background-color: white;
    padding: 20px 30px;
    border-radius: 4px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
}
</style>