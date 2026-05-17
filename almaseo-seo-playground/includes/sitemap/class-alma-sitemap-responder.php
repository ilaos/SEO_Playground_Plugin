<?php
/**
 * AlmaSEO Sitemap Responder
 * 
 * Handles routing and XML output for sitemaps
 * 
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Sitemap_Responder {
    
    /**
     * Manager instance
     */
    private $manager;
    
    /**
     * Constructor
     */
    public function __construct($manager) {
        $this->manager = $manager;
    }
    
    /**
     * Handle sitemap request
     */
    public function handle_request() {
        $sitemap_type = get_query_var('almaseo_sitemap');

        if (empty($sitemap_type)) {
            return;
        }

        // The XSL stylesheet every sitemap file points at. Served here so
        // /sitemap.xsl resolves without a physical file — must come before
        // the static-file and XML-header paths below.
        if ($sitemap_type === 'xsl') {
            $this->render_xsl();
            exit;
        }

        // Phase 4: Check for static file first
        if ($this->manager->get_storage_mode() === 'static') {
            if ($this->serve_static($sitemap_type)) {
                exit;
            }
        }
        
        // Set XML headers for dynamic response
        $this->send_headers();
        
        // Route to appropriate handler
        if ($sitemap_type === 'index') {
            $this->render_index();
        } else {
            $this->render_provider_sitemap($sitemap_type);
        }
        
        exit;
    }
    
    /**
     * Send XML headers
     *
     * Three distinct response shapes:
     *  - 'xml'         Plain XML body. Browser renders it inline.
     *  - 'xml-gzipped' XML body sent gzip-compressed purely to save bandwidth.
     *                  Content-Encoding tells the browser to transparently
     *                  decompress, so Content-Type must describe the
     *                  *decompressed* payload (application/xml). Sending
     *                  application/gzip here makes the browser treat the
     *                  response as a file and download it instead of showing
     *                  it — that was the long-standing bug.
     *  - 'gzip-file'   An explicit .xml.gz request. The gzip archive *is* the
     *                  payload, served verbatim with no Content-Encoding.
     *
     * @param string $mode One of 'xml', 'xml-gzipped', 'gzip-file'.
     */
    private function send_headers($mode = 'xml') {
        if ($mode === 'xml-gzipped') {
            header('Content-Type: application/xml; charset=UTF-8');
            header('Content-Encoding: gzip');
            header('Vary: Accept-Encoding');
        } elseif ($mode === 'gzip-file') {
            header('Content-Type: application/gzip');
        } else {
            header('Content-Type: application/xml; charset=UTF-8');
        }
        header('X-Robots-Tag: noindex, follow');
        
        // Prevent caching during development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    
    /**
     * Serve static sitemap file
     */
    private function serve_static($sitemap_type) {
        $upload_dir = wp_upload_dir();
        $storage_path = $upload_dir['basedir'] . '/almaseo/sitemaps/current/';
        
        // Build filename
        if ($sitemap_type === 'index') {
            $filename = 'sitemap.xml';
        } else {
            $page = absint(get_query_var('sitemap_page', 1));
            $filename = 'sitemap-' . $sitemap_type . '-' . $page . '.xml';
        }
        
        // Check if gzip version requested
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $wants_gzip = (strpos($request_uri, '.xml.gz') !== false);
        
        // Try gzip first if requested and enabled
        if ($wants_gzip && $this->manager->is_gzip_enabled()) {
            $filepath = $storage_path . $filename . '.gz';
            if (file_exists($filepath)) {
                // Explicit .xml.gz URL — hand back the gzip archive itself.
                $this->send_headers('gzip-file');
                readfile($filepath);
                return true;
            }
        }
        
        // Try regular XML
        $filepath = $storage_path . $filename;
        if (file_exists($filepath)) {
            // Check if client accepts gzip and we have a gzip version
            $accept_encoding = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT_ENCODING'])) : '';
            if (strpos($accept_encoding, 'gzip') !== false &&
                $this->manager->is_gzip_enabled() &&
                file_exists($filepath . '.gz')) {
                // Transparently compressed XML — browser decompresses and
                // renders it inline because Content-Type stays application/xml.
                $this->send_headers('xml-gzipped');
                readfile($filepath . '.gz');
            } else {
                // Serve regular XML
                $this->send_headers('xml');
                readfile($filepath);
            }
            return true;
        }
        
        // Check if build is in progress
        $lock = get_option('almaseo_sitemaps_build_lock');
        if ($lock && isset($lock['expires']) && $lock['expires'] > time()) {
            // Show building message
            $this->send_headers('xml');
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<!-- Sitemap is currently being rebuilt. Please check back in a few minutes. -->';
            if ($sitemap_type === 'index') {
                echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>';
            } else {
                echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
            }
            return true;
        }
        
        // File not found, fall back to dynamic
        return false;
    }
    
    /**
     * Render sitemap index
     */
    private function render_index() {
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo "\n";
        echo '<?xml-stylesheet type="text/xsl" href="' . esc_url(home_url('/sitemap.xsl')) . '"?>';
        echo "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        echo "\n";
        
        $providers = $this->manager->get_providers();
        
        foreach ($providers as $name => $provider) {
            $max_pages = $provider->get_max_pages();
            
            for ($page = 1; $page <= $max_pages; $page++) {
                echo "\t<sitemap>\n";
                echo "\t\t<loc>" . esc_url($this->get_sitemap_url($name, $page)) . "</loc>\n";
                
                // Add last modified if available
                $last_modified = $provider->get_last_modified($page);
                if ($last_modified) {
                    echo "\t\t<lastmod>" . esc_xml($last_modified) . "</lastmod>\n";
                }
                
                echo "\t</sitemap>\n";
            }
        }
        
        echo '</sitemapindex>';
    }
    
    /**
     * Render provider sitemap
     */
    private function render_provider_sitemap($provider_name) {
        $page = absint(get_query_var('sitemap_page', 1));
        $provider = $this->manager->get_provider($provider_name);
        
        if (!$provider) {
            $this->render_404();
            return;
        }
        
        // Check if page exists
        if ($page > $provider->get_max_pages()) {
            $this->render_404();
            return;
        }
        
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo "\n";
        echo '<?xml-stylesheet type="text/xsl" href="' . esc_url(home_url('/sitemap.xsl')) . '"?>';
        echo "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        echo ' xmlns:xhtml="http://www.w3.org/1999/xhtml"';
        
        // Add image namespace if provider supports images
        if (method_exists($provider, 'supports_images') && $provider->supports_images()) {
            echo ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        }
        
        // Add video namespace if provider supports videos
        if (method_exists($provider, 'supports_videos') && $provider->supports_videos()) {
            echo ' xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"';
        }
        
        // Add news namespace if provider supports news
        if (method_exists($provider, 'supports_news') && $provider->supports_news()) {
            echo ' xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"';
        }
        
        echo ">\n";
        
        // Stream URLs from provider
        $urls = $provider->get_urls($page);
        
        foreach ($urls as $url_data) {
            $this->render_url_element($url_data);
        }
        
        echo '</urlset>';
    }
    
    /**
     * Render single URL element
     */
    private function render_url_element($url_data) {
        echo "\t<url>\n";
        
        // Required: loc
        echo "\t\t<loc>" . esc_url($url_data['loc']) . "</loc>\n";
        
        // Optional: lastmod — normalize via the shared helper. Matches the
        // static writer's output exactly so the two paths can't drift.
        if (!empty($url_data['lastmod']) && function_exists('almaseo_format_lastmod')) {
            $lastmod = almaseo_format_lastmod($url_data['lastmod']);
            if ($lastmod !== '') {
                echo "\t\t<lastmod>" . esc_xml($lastmod) . "</lastmod>\n";
            }
        }
        
        // Optional: changefreq
        if (!empty($url_data['changefreq'])) {
            echo "\t\t<changefreq>" . esc_xml($url_data['changefreq']) . "</changefreq>\n";
        }

        // <priority> intentionally omitted — see matching note in
        // Alma_Sitemap_Writer::write_url(). Google ignores the field.

        // Optional: images
        if (!empty($url_data['images'])) {
            foreach ($url_data['images'] as $image) {
                $this->render_image_element($image);
            }
        }
        
        // Optional: videos
        if (!empty($url_data['videos'])) {
            foreach ($url_data['videos'] as $video) {
                $this->render_video_element($video);
            }
        }
        
        // Optional: news
        if (!empty($url_data['news'])) {
            $this->render_news_element($url_data['news']);
        }
        
        // Phase 5B: Hreflang alternates
        if (!empty($url_data['alternates'])) {
            $this->render_hreflang_elements($url_data['loc'], $url_data['alternates']);
        }
        
        echo "\t</url>\n";
    }
    
    /**
     * Render image element
     */
    private function render_image_element($image) {
        echo "\t\t<image:image>\n";
        echo "\t\t\t<image:loc>" . esc_url($image['loc']) . "</image:loc>\n";
        
        if (!empty($image['title'])) {
            echo "\t\t\t<image:title>" . esc_xml($image['title']) . "</image:title>\n";
        }
        
        if (!empty($image['caption'])) {
            echo "\t\t\t<image:caption>" . esc_xml($image['caption']) . "</image:caption>\n";
        }
        
        echo "\t\t</image:image>\n";
    }
    
    /**
     * Render video element
     */
    private function render_video_element($video) {
        echo "\t\t<video:video>\n";
        
        // Required: thumbnail_loc
        echo "\t\t\t<video:thumbnail_loc>" . esc_url($video['thumbnail_loc']) . "</video:thumbnail_loc>\n";
        
        // Required: title
        echo "\t\t\t<video:title>" . esc_xml($video['title']) . "</video:title>\n";
        
        // Required: description
        echo "\t\t\t<video:description>" . esc_xml($video['description']) . "</video:description>\n";
        
        // Required: content_loc or player_loc
        if (!empty($video['content_loc'])) {
            echo "\t\t\t<video:content_loc>" . esc_url($video['content_loc']) . "</video:content_loc>\n";
        } elseif (!empty($video['player_loc'])) {
            echo "\t\t\t<video:player_loc>" . esc_url($video['player_loc']) . "</video:player_loc>\n";
        }
        
        // Optional: duration
        if (!empty($video['duration'])) {
            echo "\t\t\t<video:duration>" . intval($video['duration']) . "</video:duration>\n";
        }
        
        // Optional: publication_date
        if (!empty($video['publication_date'])) {
            echo "\t\t\t<video:publication_date>" . esc_xml($video['publication_date']) . "</video:publication_date>\n";
        }
        
        // Optional: uploader
        if (!empty($video['uploader'])) {
            echo "\t\t\t<video:uploader info=\"" . esc_url($video['uploader_info'] ?? '') . "\">" . esc_xml($video['uploader']) . "</video:uploader>\n";
        }
        
        echo "\t\t</video:video>\n";
    }
    
    /**
     * Render news element
     */
    private function render_news_element($news) {
        echo "\t\t<news:news>\n";
        
        // Publication info
        echo "\t\t\t<news:publication>\n";
        echo "\t\t\t\t<news:name>" . esc_xml($news['publication']['name']) . "</news:name>\n";
        echo "\t\t\t\t<news:language>" . esc_xml($news['publication']['language']) . "</news:language>\n";
        echo "\t\t\t</news:publication>\n";
        
        // Publication date
        echo "\t\t\t<news:publication_date>" . esc_xml($news['publication_date']) . "</news:publication_date>\n";
        
        // Title
        echo "\t\t\t<news:title>" . esc_xml($news['title']) . "</news:title>\n";
        
        // Optional: genres
        if (!empty($news['genres'])) {
            echo "\t\t\t<news:genres>" . esc_xml($news['genres']) . "</news:genres>\n";
        }
        
        // Optional: keywords
        if (!empty($news['keywords'])) {
            echo "\t\t\t<news:keywords>" . esc_xml($news['keywords']) . "</news:keywords>\n";
        }
        
        echo "\t\t</news:news>\n";
    }
    
    /**
     * Render hreflang elements (Phase 5B)
     */
    private function render_hreflang_elements($url, $alternates) {
        $settings = $this->manager->get_settings();
        
        if (empty($settings['hreflang']['enabled'])) {
            return;
        }
        
        require_once dirname(__FILE__) . '/class-alma-hreflang.php';
        $hreflang = new Alma_Hreflang($settings['hreflang']);
        
        $xml = $hreflang->build_hreflang_xml($url, $alternates);
        echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional XML sitemap response
    }
    
    /**
     * Render the XSL stylesheet that styles sitemap output in the browser.
     *
     * Both the sitemap index (<sitemapindex>) and the per-provider sitemaps
     * (<urlset>) reference this same /sitemap.xsl, so the stylesheet handles
     * both root elements. It is pure, static XSLT 1.0 with inline CSS — no
     * PHP values are interpolated, hence the NOWDOC block.
     */
    private function render_xsl() {
        header('Content-Type: text/xsl; charset=UTF-8');
        header('X-Robots-Tag: noindex, follow');

        echo <<<'XSL'
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9">
  <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>

  <xsl:template match="/">
    <html lang="en">
      <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <meta name="robots" content="noindex,follow"/>
        <title>XML Sitemap</title>
        <style type="text/css">
          body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:#1e1e1e;margin:0;background:#f6f7f7;}
          .wrap{max-width:1000px;margin:0 auto;padding:32px 20px;}
          h1{font-size:23px;margin:0 0 6px;}
          .intro{color:#50575e;font-size:13px;margin:0 0 22px;line-height:1.6;}
          .intro a{color:#2271b1;}
          .count{font-weight:600;}
          table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #dcdcde;border-radius:6px;overflow:hidden;font-size:13px;}
          th{text-align:left;background:#f0f0f1;padding:10px 14px;font-weight:600;border-bottom:1px solid #dcdcde;}
          td{padding:9px 14px;border-bottom:1px solid #f0f0f1;word-break:break-all;vertical-align:top;}
          tr:last-child td{border-bottom:0;}
          tr:hover td{background:#f6f7f7;}
          a{color:#2271b1;text-decoration:none;}
          a:hover{text-decoration:underline;}
        </style>
      </head>
      <body>
        <div class="wrap">
          <h1>XML Sitemap</h1>
          <p class="intro">This XML sitemap is generated by <a href="https://almaseo.com">AlmaSEO</a> to help search engines crawl and index this site. It is not meant for human visitors.</p>
          <xsl:apply-templates select="sitemap:sitemapindex"/>
          <xsl:apply-templates select="sitemap:urlset"/>
        </div>
      </body>
    </html>
  </xsl:template>

  <xsl:template match="sitemap:sitemapindex">
    <p class="intro">This index references <span class="count"><xsl:value-of select="count(sitemap:sitemap)"/></span> sitemap(s).</p>
    <table>
      <tr><th>Sitemap</th><th>Last Modified</th></tr>
      <xsl:for-each select="sitemap:sitemap">
        <tr>
          <td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>
          <td><xsl:value-of select="sitemap:lastmod"/></td>
        </tr>
      </xsl:for-each>
    </table>
  </xsl:template>

  <xsl:template match="sitemap:urlset">
    <p class="intro">This sitemap contains <span class="count"><xsl:value-of select="count(sitemap:url)"/></span> URL(s).</p>
    <table>
      <tr><th>URL</th><th>Last Modified</th><th>Change Frequency</th></tr>
      <xsl:for-each select="sitemap:url">
        <tr>
          <td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>
          <td><xsl:value-of select="sitemap:lastmod"/></td>
          <td><xsl:value-of select="sitemap:changefreq"/></td>
        </tr>
      </xsl:for-each>
    </table>
  </xsl:template>
</xsl:stylesheet>
XSL;
    }

    /**
     * Render 404 response
     */
    private function render_404() {
        status_header(404);
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo "\n";
        echo '<!-- Sitemap not found -->';
    }
    
    /**
     * Get sitemap URL for provider and page
     */
    private function get_sitemap_url($provider_name, $page = 1) {
        return home_url(sprintf('/sitemap-%s-%d.xml', $provider_name, $page));
    }
    
    // Note: the private format_date() helper that used to live here was
    // lifted to includes/sitemap/helpers.php as almaseo_format_lastmod() in
    // 1.13.15 so both static and dynamic emission paths share one formatter.
}