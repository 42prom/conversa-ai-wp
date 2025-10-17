/**
 * Optimized Analytics Dashboard JavaScript
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/assets/js
 */

(function($) {
    'use strict';
    
    // Chart instances
    let charts = {
        conversations: null,
        sources: null,
        success: null,
        channels: null
    };
    
    // Color schemes for charts
    const colorSchemes = {
        conversations: {
            backgroundColor: ['rgba(76, 102, 239, 0.2)', 'rgba(67, 160, 71, 0.2)'],
            borderColor: ['rgba(76, 102, 239, 1)', 'rgba(67, 160, 71, 1)'],
            borderWidth: 2
        },
        sources: {
            backgroundColor: ['rgba(25, 118, 210, 0.7)', 'rgba(76, 102, 239, 0.7)'],
            borderColor: ['rgba(25, 118, 210, 1)', 'rgba(76, 102, 239, 1)'],
            borderWidth: 1
        },
        success: {
            backgroundColor: [
                'rgba(76, 175, 80, 0.7)',  // Excellent
                'rgba(139, 195, 74, 0.7)',  // Good
                'rgba(255, 193, 7, 0.7)',   // Average
                'rgba(255, 152, 0, 0.7)',   // Poor
                'rgba(244, 67, 54, 0.7)'    // Very Poor
            ],
            borderColor: [
                'rgba(76, 175, 80, 1)',
                'rgba(139, 195, 74, 1)',
                'rgba(255, 193, 7, 1)',
                'rgba(255, 152, 0, 1)',
                'rgba(244, 67, 54, 1)'
            ],
            borderWidth: 1
        },
        channels: {
            backgroundColor: [
                'rgba(76, 102, 239, 0.7)',
                'rgba(67, 160, 71, 0.7)',
                'rgba(255, 152, 0, 0.7)',
                'rgba(233, 30, 99, 0.7)'
            ],
            borderColor: [
                'rgba(76, 102, 239, 1)',
                'rgba(67, 160, 71, 1)',
                'rgba(255, 152, 0, 1)',
                'rgba(233, 30, 99, 1)'
            ],
            borderWidth: 1
        }
    };
    
    /**
     * Initialize the analytics dashboard
     */
    function initAnalyticsDashboard() {
        console.log('Initializing analytics dashboard');
        
        // Initialize date controls
        initDateControls();
        
        // Initialize export controls
        initExportControls();
        
        // Initialize trending queries table if it exists
        initTrendingQueriesTable();
        
        // Initialize charts if we have data
        if (typeof conversaaiAnalytics !== 'undefined' && conversaaiAnalytics.chartData) {
            initCharts();
        } else {
            console.warn('No chart data available');
        }
    }
    
    /**
     * Initialize date range controls
     */
    function initDateControls() {
        // Date preset buttons
        $('.conversaai-date-preset').on('click', function() {
            const startDate = $(this).data('start');
            const endDate = $(this).data('end');
            
            $('#conversaai-date-start').val(startDate);
            $('#conversaai-date-end').val(endDate);
            
            // Highlight active preset
            $('.conversaai-date-preset').removeClass('active');
            $(this).addClass('active');
        });
        
        // Custom date range button
        $('.conversaai-date-preset-custom').on('click', function() {
            $('.conversaai-date-preset').removeClass('active');
            $(this).addClass('active');
        });
        
        // Form submission
        $('#conversaai-analytics-form').on('submit', function() {
            // Add channel to form if selected
            const channel = $('#conversaai-channel').val();
            if (channel) {
                if (!$(this).find('input[name="channel"]').length) {
                    $(this).append('<input type="hidden" name="channel" value="' + channel + '">');
                } else {
                    $(this).find('input[name="channel"]').val(channel);
                }
            }
        });
    }
    
    /**
     * Initialize export functionality
     */
    function initExportControls() {
        $('.conversaai-export-option').on('click', function(e) {
            e.preventDefault();
            const format = $(this).data('format');
            
            // Create form and submit for export
            const $form = $('<form>', {
                'method': 'post',
                'action': window.location.href
            });
            
            $form.append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': 'export_analytics'
            }));
            
            $form.append($('<input>', {
                'type': 'hidden',
                'name': 'format',
                'value': format
            }));
            
            $form.append($('<input>', {
                'type': 'hidden',
                'name': 'start_date',
                'value': $('#conversaai-date-start').val()
            }));
            
            $form.append($('<input>', {
                'type': 'hidden',
                'name': 'end_date',
                'value': $('#conversaai-date-end').val()
            }));
            
            $form.append($('<input>', {
                'type': 'hidden',
                'name': 'channel',
                'value': $('#conversaai-channel').val()
            }));
            
            $('body').append($form);
            $form.submit();
            $form.remove();
        });
    }
    
    /**
     * Initialize trending queries table
     */
    function initTrendingQueriesTable() {
        // Check if DataTables is available and if we have a trending table
        if ($.fn.DataTable && $('.conversaai-trending-table').length) {
            // Check if there's actual data (not just a "no data" message)
            if (!$('.conversaai-trending-table tbody td.conversaai-no-data').length) {
                try {
                    $('.conversaai-trending-table').DataTable({
                        paging: false,
                        searching: false,
                        info: false,
                        order: [[1, 'desc']]
                    });
                } catch (e) {
                    console.error('Error initializing DataTable:', e);
                }
            }
        }
    }
    
    /**
     * Initialize all charts
     */
    function initCharts() {
        // Initialize conversations chart
        initConversationsChart();
        
        // Initialize sources chart
        initSourcesChart();
        
        // Initialize success distribution chart
        initSuccessDistributionChart();
        
        // Initialize channels chart
        initChannelsChart();
    }
    
    /**
     * Initialize conversations over time chart
     */
    function initConversationsChart() {
        const data = conversaaiAnalytics.chartData.conversations_chart;
        
        // Check if we have valid data
        if (!data || !data.labels || !data.labels.length) {
            showNoDataMessage('conversaai-conversations-chart');
            return;
        }
        
        const ctx = document.getElementById('conversaai-conversations-chart');
        if (!ctx) return;
        
        // Create chart
        try {
            charts.conversations = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: data.datasets[0].label || (conversaaiAnalytics.i18n ? conversaaiAnalytics.i18n.conversations : 'Conversations'),
                            data: data.datasets[0].data,
                            backgroundColor: colorSchemes.conversations.backgroundColor[0],
                            borderColor: colorSchemes.conversations.borderColor[0],
                            borderWidth: colorSchemes.conversations.borderWidth,
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: data.datasets[1].label || (conversaaiAnalytics.i18n ? conversaaiAnalytics.i18n.messages : 'Messages'),
                            data: data.datasets[1].data,
                            backgroundColor: colorSchemes.conversations.backgroundColor[1],
                            borderColor: colorSchemes.conversations.borderColor[1],
                            borderWidth: colorSchemes.conversations.borderWidth,
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    height: 250, // Fixed height for chart
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Error creating conversations chart:', e);
            showErrorMessage('conversaai-conversations-chart');
        }
    }
    
    /**
     * Initialize response sources chart
     */
    function initSourcesChart() {
        const data = conversaaiAnalytics.chartData.sources_chart;
        
        // Check if we have valid data
        if (!data || !data.labels || !data.labels.length || !data.datasets[0].data.some(val => val > 0)) {
            showNoDataMessage('conversaai-sources-chart');
            return;
        }
        
        const ctx = document.getElementById('conversaai-sources-chart');
        if (!ctx) return;
        
        // Create chart
        try {
            charts.sources = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.datasets[0].data,
                        backgroundColor: colorSchemes.sources.backgroundColor.slice(0, data.labels.length),
                        borderColor: colorSchemes.sources.borderColor.slice(0, data.labels.length),
                        borderWidth: colorSchemes.sources.borderWidth
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    height: 250, // Fixed height for chart
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Error creating sources chart:', e);
            showErrorMessage('conversaai-sources-chart');
        }
    }
    
    /**
     * Initialize success distribution chart
     */
    function initSuccessDistributionChart() {
        const data = conversaaiAnalytics.chartData.success_distribution;
        
        // Check if we have valid data
        if (!data || !data.labels || !data.labels.length || !data.datasets[0].data.some(val => val > 0)) {
            showNoDataMessage('conversaai-success-distribution-chart');
            return;
        }
        
        const ctx = document.getElementById('conversaai-success-distribution-chart');
        if (!ctx) return;
        
        // Create chart
        try {
            charts.success = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.datasets[0].data,
                        backgroundColor: colorSchemes.success.backgroundColor.slice(0, data.labels.length),
                        borderColor: colorSchemes.success.borderColor.slice(0, data.labels.length),
                        borderWidth: colorSchemes.success.borderWidth
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    height: 250, // Fixed height for chart
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Error creating success distribution chart:', e);
            showErrorMessage('conversaai-success-distribution-chart');
        }
    }
    
    /**
     * Initialize channels chart
     */
    function initChannelsChart() {
        const data = conversaaiAnalytics.chartData.channels_chart;
        
        // Check if we have valid data
        if (!data || !data.labels || !data.labels.length) {
            showNoDataMessage('conversaai-channels-chart');
            return;
        }
        
        const ctx = document.getElementById('conversaai-channels-chart');
        if (!ctx) return;
        
        // Create chart
        try {
            charts.channels = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: data.datasets[0].label || (conversaaiAnalytics.i18n ? conversaaiAnalytics.i18n.conversations : 'Conversations'),
                        data: data.datasets[0].data,
                        backgroundColor: colorSchemes.channels.backgroundColor.slice(0, data.labels.length),
                        borderColor: colorSchemes.channels.borderColor.slice(0, data.labels.length),
                        borderWidth: colorSchemes.channels.borderWidth
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    height: 250, // Fixed height for chart
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Error creating channels chart:', e);
            showErrorMessage('conversaai-channels-chart');
        }
    }
    
    /**
     * Show no data message for a chart
     */
    function showNoDataMessage(elementId) {
        const container = document.getElementById(elementId);
        if (container) {
            // Create no data message
            const noDataDiv = document.createElement('div');
            noDataDiv.className = 'conversaai-no-data';
            noDataDiv.textContent = conversaaiAnalytics.i18n ? conversaaiAnalytics.i18n.noData : 'No data available for the selected period.';
            
            // Replace the canvas with the message
            const parent = container.parentNode;
            if (parent) {
                parent.replaceChild(noDataDiv, container);
            }
        }
    }
    
    /**
     * Show error message for a chart
     */
    function showErrorMessage(elementId) {
        const container = document.getElementById(elementId);
        if (container) {
            // Create error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'conversaai-error';
            errorDiv.textContent = conversaaiAnalytics.i18n ? conversaaiAnalytics.i18n.error : 'Error loading data. Please try again.';
            
            // Replace the canvas with the message
            const parent = container.parentNode;
            if (parent) {
                parent.replaceChild(errorDiv, container);
            }
        }
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        console.log('Document ready, initializing analytics dashboard');
        initAnalyticsDashboard();
    });
    
})(jQuery);