/**
 * AlmaSEO Search Console Tab - Polish JavaScript
 * Enhanced functionality for Search Console data display
 */

(function($) {
    'use strict';

    var SearchConsoleManager = {
        
        currentSort: {
            column: 'position',
            direction: 'asc'
        },
        
        mockData: null,
        
        init: function() {
            this.bindEvents();
            this.loadSearchConsoleData();
            this.initAccessibility();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Refresh button
            $('#refresh-gsc-keywords').on('click', function() {
                self.refreshData();
            });
            
            // Export button
            $('#export-gsc-keywords').on('click', function() {
                self.exportToCSV();
            });
            
            // Table sorting
            $('.gsc-keywords-table .sort-btn').on('click', function() {
                var column = $(this).closest('th').data('sort');
                self.sortTable(column);
            });
            
            // Row hover effects
            $(document).on('mouseenter', '#gsc-keywords-tbody tr', function() {
                $(this).addClass('row-hover');
            }).on('mouseleave', '#gsc-keywords-tbody tr', function() {
                $(this).removeClass('row-hover');
            });
        },
        
        loadSearchConsoleData: function() {
            var self = this;
            
            // Show loading state
            $('#gsc-keywords-empty').hide();
            $('#gsc-keywords-content').hide();
            $('#gsc-keywords-loading').show();
            
            // Simulate API call (replace with actual API call)
            setTimeout(function() {
                self.mockData = self.generateMockData();
                self.displayData(self.mockData);
            }, 1500);
        },
        
        generateMockData: function() {
            // Mock data for demonstration
            return [
                { keyword: 'wordpress seo plugin', clicks: 245, impressions: 3200, ctr: 7.66, position: 3.2 },
                { keyword: 'seo optimization tips', clicks: 189, impressions: 2100, ctr: 9.00, position: 2.8 },
                { keyword: 'meta tags best practices', clicks: 156, impressions: 1850, ctr: 8.43, position: 4.1 },
                { keyword: 'schema markup guide', clicks: 134, impressions: 1600, ctr: 8.38, position: 5.3 },
                { keyword: 'content optimization', clicks: 98, impressions: 1200, ctr: 8.17, position: 6.7 },
                { keyword: 'keyword research tools', clicks: 87, impressions: 980, ctr: 8.88, position: 7.2 },
                { keyword: 'seo audit checklist', clicks: 76, impressions: 890, ctr: 8.54, position: 8.9 },
                { keyword: 'google search console', clicks: 65, impressions: 750, ctr: 8.67, position: 9.4 }
            ];
        },
        
        displayData: function(data) {
            var self = this;
            
            if (!data || data.length === 0) {
                $('#gsc-keywords-loading').hide();
                $('#gsc-keywords-empty').show();
                return;
            }
            
            // Sort data
            var sortedData = self.sortData(data, self.currentSort.column, self.currentSort.direction);
            
            // Build table rows
            var tbody = $('#gsc-keywords-tbody');
            tbody.empty();
            
            sortedData.forEach(function(item, index) {
                var row = self.createTableRow(item, index);
                tbody.append(row);
            });
            
            // Update summary
            self.updateSummary(data);
            
            // Update timestamp
            self.updateTimestamp();
            
            // Show content
            $('#gsc-keywords-loading').hide();
            $('#gsc-keywords-content').fadeIn();
            
            // Apply row highlighting
            self.highlightPerformers();
        },
        
        createTableRow: function(item, index) {
            var positionClass = '';
            var positionBadge = '';
            
            // Position badges
            if (item.position <= 3) {
                positionClass = 'top-position';
                positionBadge = '<span class="position-badge top-3">Top 3</span>';
            } else if (item.position <= 10) {
                positionClass = 'top-position';
                positionBadge = '<span class="position-badge top-10">Top 10</span>';
            } else if (item.position > 20) {
                positionBadge = '<span class="position-badge needs-work">Needs Work</span>';
            }
            
            // High CTR highlighting
            var rowClass = positionClass;
            if (item.ctr > 8.5) {
                rowClass += ' high-ctr';
            }
            
            var row = $('<tr>', {
                'role': 'row',
                'class': rowClass,
                'tabindex': '0',
                'data-keyword': item.keyword
            });
            
            row.html(
                '<td role="cell" class="gsc-keyword">' + self.escapeHtml(item.keyword) + '</td>' +
                '<td role="cell" class="gsc-clicks">' + item.clicks.toLocaleString() + '</td>' +
                '<td role="cell" class="gsc-impressions">' + item.impressions.toLocaleString() + '</td>' +
                '<td role="cell" class="gsc-ctr">' + item.ctr.toFixed(2) + '%</td>' +
                '<td role="cell" class="gsc-position">' + 
                    '<span class="position-value">' + item.position.toFixed(1) + '</span> ' +
                    positionBadge +
                '</td>'
            );
            
            return row;
        },
        
        sortTable: function(column) {
            var self = this;
            
            // Update sort direction
            if (self.currentSort.column === column) {
                self.currentSort.direction = self.currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                self.currentSort.column = column;
                self.currentSort.direction = column === 'keyword' ? 'asc' : 'desc';
            }
            
            // Update UI
            $('.gsc-keywords-table th').removeClass('sort-asc sort-desc').attr('aria-sort', 'none');
            var $header = $('.gsc-keywords-table th[data-sort="' + column + '"]');
            $header.addClass('sort-' + self.currentSort.direction)
                   .attr('aria-sort', self.currentSort.direction === 'asc' ? 'ascending' : 'descending');
            
            // Re-display sorted data
            if (self.mockData) {
                self.displayData(self.mockData);
            }
        },
        
        sortData: function(data, column, direction) {
            var sortedData = [...data];
            
            sortedData.sort(function(a, b) {
                var aVal = a[column];
                var bVal = b[column];
                
                if (typeof aVal === 'string') {
                    aVal = aVal.toLowerCase();
                    bVal = bVal.toLowerCase();
                }
                
                if (direction === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });
            
            return sortedData;
        },
        
        highlightPerformers: function() {
            // Already handled in createTableRow with classes
        },
        
        refreshData: function() {
            var self = this;
            var $btn = $('#refresh-gsc-keywords');
            
            // Disable button
            $btn.prop('disabled', true);
            
            // Reload data
            self.loadSearchConsoleData();
            
            // Re-enable after loading
            setTimeout(function() {
                $btn.prop('disabled', false);
            }, 2000);
            
            // Announce to screen readers
            self.announceToScreenReader('Search Console data refreshed');
        },
        
        exportToCSV: function() {
            var self = this;
            
            if (!self.mockData || self.mockData.length === 0) {
                alert('No data to export');
                return;
            }
            
            // Build CSV content
            var csv = 'Keyword,Clicks,Impressions,CTR,Position\n';
            
            self.mockData.forEach(function(item) {
                csv += '"' + item.keyword + '",' +
                       item.clicks + ',' +
                       item.impressions + ',' +
                       item.ctr + '%,' +
                       item.position + '\n';
            });
            
            // Create download link
            var blob = new Blob([csv], { type: 'text/csv' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'search-console-keywords-' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            // Announce to screen readers
            self.announceToScreenReader('Keywords exported to CSV');
        },
        
        updateSummary: function(data) {
            var totalClicks = data.reduce((sum, item) => sum + item.clicks, 0);
            var totalImpressions = data.reduce((sum, item) => sum + item.impressions, 0);
            var avgCTR = (totalClicks / totalImpressions * 100).toFixed(2);
            var avgPosition = (data.reduce((sum, item) => sum + item.position, 0) / data.length).toFixed(1);
            
            var summary = 'Total: ' + totalClicks.toLocaleString() + ' clicks, ' +
                         totalImpressions.toLocaleString() + ' impressions, ' +
                         avgCTR + '% avg CTR, ' +
                         avgPosition + ' avg position';
            
            $('#gsc-keywords-summary').html('<strong>Summary:</strong> ' + summary);
        },
        
        updateTimestamp: function() {
            var now = new Date();
            $('#gsc-last-updated')
                .data('timestamp', now.toISOString())
                .text(this.formatRelativeTime(now));
        },
        
        formatRelativeTime: function(date) {
            var now = new Date();
            var diff = now - date;
            var seconds = Math.floor(diff / 1000);
            
            if (seconds < 60) {
                return 'Just now';
            } else if (seconds < 3600) {
                var minutes = Math.floor(seconds / 60);
                return minutes + ' minute' + (minutes !== 1 ? 's' : '') + ' ago';
            } else if (seconds < 86400) {
                var hours = Math.floor(seconds / 3600);
                return hours + ' hour' + (hours !== 1 ? 's' : '') + ' ago';
            } else {
                var days = Math.floor(seconds / 86400);
                return days + ' day' + (days !== 1 ? 's' : '') + ' ago';
            }
        },
        
        initAccessibility: function() {
            var self = this;
            
            // Keyboard navigation for table rows
            $(document).on('keydown', '#gsc-keywords-tbody tr', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    var keyword = $(this).data('keyword');
                    $('#almaseo_focus_keyword').val(keyword).trigger('input');
                    self.announceToScreenReader('Keyword "' + keyword + '" selected as focus keyword');
                }
            });
            
            // Update timestamps periodically
            setInterval(function() {
                $('#gsc-last-updated').each(function() {
                    var timestamp = $(this).data('timestamp');
                    if (timestamp) {
                        $(this).text(self.formatRelativeTime(new Date(timestamp)));
                    }
                });
            }, 60000); // Update every minute
        },
        
        announceToScreenReader: function(message) {
            if (!$('#almaseo-aria-live').length) {
                $('body').append('<div id="almaseo-aria-live" class="sr-only" aria-live="polite" aria-atomic="true"></div>');
            }
            $('#almaseo-aria-live').text(message);
            setTimeout(function() {
                $('#almaseo-aria-live').text('');
            }, 1000);
        },
        
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Only initialize if Search Console tab exists
        if ($('#tab-search-console').length) {
            // Initialize when tab is activated
            $(document).on('almaseo:tab:switched', function(e, tabId) {
                if (tabId === 'search-console' && !SearchConsoleManager.mockData) {
                    SearchConsoleManager.init();
                }
            });
            
            // Initialize immediately if tab is already active
            if ($('#tab-search-console').hasClass('active')) {
                SearchConsoleManager.init();
            }
        }
    });

})(jQuery);