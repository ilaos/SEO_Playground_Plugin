/**
 * 404 Intelligence – Enhanced UI Components
 *
 * Adds spike alerts panel, redirect suggestions panel,
 * and impact badges to the 404 Logs page.
 *
 * @package AlmaSEO
 * @since   7.6.0
 */

jQuery(function ($) {
    'use strict';

    if (typeof almaseo404 === 'undefined') return;

    var apiUrl = almaseo404.apiUrl;
    var nonce  = almaseo404.nonce;

    /* ============================================================
     *  SPIKE ALERTS PANEL
     * ============================================================ */
    function loadSpikes() {
        $.ajax({
            url: apiUrl + '/spikes',
            method: 'GET',
            headers: { 'X-WP-Nonce': nonce },
            success: function (spikes) {
                renderSpikes(spikes);
            }
        });
    }

    function renderSpikes(spikes) {
        var container = $('#almaseo-404-spikes');
        if (!container.length) return;

        if (!spikes || !spikes.length) {
            container.html('<p class="almaseo-404-no-spikes">No traffic spikes detected in the last 24 hours.</p>');
            return;
        }

        var html = '<ul class="almaseo-404-spike-list">';
        spikes.forEach(function (spike) {
            html += '<li class="almaseo-404-spike-item">';
            html += '<span class="almaseo-404-spike-path">' + escapeHtml(spike.path) + '</span>';
            html += '<span class="almaseo-404-spike-badge">' + spike.spike_ratio + 'x spike</span>';
            html += '<span class="almaseo-404-spike-detail">' + spike.hits_24h + ' hits today (avg: ' + spike.daily_avg + '/day)</span>';
            html += '</li>';
        });
        html += '</ul>';
        container.html(html);
    }

    /* ============================================================
     *  SUGGESTION PANEL
     * ============================================================ */
    $(document).on('click', '.almaseo-404-suggest-btn', function () {
        var id = $(this).data('id');
        var btn = $(this);
        btn.prop('disabled', true).text('Loading...');

        $.ajax({
            url: apiUrl + '/' + id + '/suggestions',
            method: 'GET',
            headers: { 'X-WP-Nonce': nonce },
            success: function (suggestions) {
                showSuggestionPanel(id, suggestions, btn);
            },
            error: function () {
                btn.prop('disabled', false).text('Suggestions');
            }
        });
    });

    function showSuggestionPanel(logId, suggestions, triggerBtn) {
        // Remove existing panels.
        $('.almaseo-404-suggestion-panel').remove();

        var row = triggerBtn.closest('tr');
        triggerBtn.prop('disabled', false).text('Suggestions');

        if (!suggestions || !suggestions.length) {
            var noPanel = $('<tr class="almaseo-404-suggestion-panel"><td colspan="8">' +
                '<div class="almaseo-404-suggest-box">' +
                '<p>No redirect suggestions found for this path.</p>' +
                '<button class="button almaseo-404-suggest-close">Close</button>' +
                '</div></td></tr>');
            row.after(noPanel);
            return;
        }

        var html = '<tr class="almaseo-404-suggestion-panel"><td colspan="8">';
        html += '<div class="almaseo-404-suggest-box">';
        html += '<h4>Redirect Suggestions</h4>';
        html += '<ul class="almaseo-404-suggest-list">';

        suggestions.forEach(function (s) {
            html += '<li>';
            html += '<a href="' + escapeHtml(s.url) + '" target="_blank">' + escapeHtml(s.title || s.url) + '</a>';
            html += '<span class="almaseo-404-suggest-score">' + Math.round(s.score) + '% match</span>';
            html += '<span class="almaseo-404-suggest-reason">' + escapeHtml(s.reason) + '</span>';
            html += '<button class="button button-small almaseo-404-use-suggestion" data-log-id="' + logId + '" data-url="' + escapeHtml(s.url) + '">Use as redirect</button>';
            html += '</li>';
        });

        html += '</ul>';
        html += '<button class="button almaseo-404-suggest-close">Close</button>';
        html += '</div></td></tr>';

        row.after(html);
    }

    $(document).on('click', '.almaseo-404-suggest-close', function () {
        $(this).closest('.almaseo-404-suggestion-panel').remove();
    });

    $(document).on('click', '.almaseo-404-use-suggestion', function () {
        var url = $(this).data('url');
        var logId = $(this).data('log-id');

        // Navigate to redirect creation with pre-filled source + target.
        var redirectUrl = almaseo404.redirectUrl + '&action=add';

        // Fetch the log data first to get the source path.
        $.ajax({
            url: apiUrl + '/' + logId + '/to-redirect',
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce },
            success: function (data) {
                window.location.href = redirectUrl + '&source=' + encodeURIComponent(data.source) + '&target=' + encodeURIComponent(url);
            },
            error: function () {
                window.location.href = redirectUrl;
            }
        });
    });

    /* ============================================================
     *  HIGH IMPACT PANEL
     * ============================================================ */
    function loadHighImpact() {
        $.ajax({
            url: apiUrl + '/high-impact',
            method: 'GET',
            headers: { 'X-WP-Nonce': nonce },
            success: function (items) {
                renderHighImpact(items);
            }
        });
    }

    function renderHighImpact(items) {
        var container = $('#almaseo-404-high-impact');
        if (!container.length) return;

        if (!items || !items.length) {
            container.html('<p class="almaseo-404-no-impact">No impact data available yet. Impact data is pushed from the AlmaSEO dashboard when connected to Google Search Console.</p>');
            return;
        }

        var html = '<table class="widefat striped almaseo-404-impact-table">';
        html += '<thead><tr><th>Path</th><th>Impact</th><th>Impressions</th><th>Clicks</th><th>Hits</th></tr></thead>';
        html += '<tbody>';

        items.forEach(function (item) {
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(item.path) + '</strong></td>';
            html += '<td><span class="almaseo-404-impact-badge">' + parseFloat(item.impact_score).toFixed(1) + '</span></td>';
            html += '<td>' + formatNumber(item.impressions || 0) + '</td>';
            html += '<td>' + formatNumber(item.clicks || 0) + '</td>';
            html += '<td>' + formatNumber(item.hits || 0) + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.html(html);
    }

    /* ============================================================
     *  INIT
     * ============================================================ */
    loadSpikes();
    loadHighImpact();

    /* ─── Utilities ─── */
    function escapeHtml(text) {
        if (!text) return '';
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
});
