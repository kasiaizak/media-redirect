<?php
/**
 * Plugin Name: Media Redirect to Production
 * Description: Redirects media URLs to the production domain, with optional local uploads fallback.
 * Version: 1.10.0
 * Author: Kasia Izak i ChatGPT
 */

// Filtry URL-i mediow.
add_filter('wp_get_attachment_url', 'mrp_filter_url');
add_filter('wp_get_attachment_image_src', 'mrp_filter_image_src');
add_filter('wp_calculate_image_srcset', 'mrp_filter_srcset');
add_filter('wp_get_attachment_image_attributes', 'mrp_filter_image_src_attribute', 10, 3);
add_filter('the_content', 'mrp_replace_media_urls_in_content', 99);

function mrp_should_rewrite_urls() {
    $production = get_option('mrp_production_domain');
    if (!$production) {
        return false;
    }

    $local = parse_url(home_url(), PHP_URL_HOST);
    $remote = parse_url($production, PHP_URL_HOST);

    return $local !== $remote;
}

function mrp_should_prefer_local_uploads() {
    return (bool) get_option('mrp_prefer_local_uploads');
}

function mrp_get_local_wpcontent_url() {
    $custom_wpcontent = trim((string) get_option('mrp_custom_wpcontent'));
    if ($custom_wpcontent !== '') {
        return home_url(rtrim($custom_wpcontent, '/'));
    }

    return content_url();
}

function mrp_get_remote_base() {
    $domain = rtrim((string) get_option('mrp_production_domain'), '/');
    $local_wpcontent_url = mrp_get_local_wpcontent_url();

    return $domain . '/' . trim((string) parse_url($local_wpcontent_url, PHP_URL_PATH), '/');
}

function mrp_is_local_upload_url($url) {
    $local_wpcontent_url = mrp_get_local_wpcontent_url();

    return strpos((string) $url, $local_wpcontent_url . '/uploads/') !== false;
}

function mrp_local_upload_file_exists($url) {
    if (!mrp_should_prefer_local_uploads() || !mrp_is_local_upload_url($url)) {
        return false;
    }

    $uploads = wp_upload_dir();
    if (empty($uploads['baseurl']) || empty($uploads['basedir'])) {
        return false;
    }

    $url_path = parse_url((string) $url, PHP_URL_PATH);
    $baseurl_path = parse_url((string) $uploads['baseurl'], PHP_URL_PATH);
    if (!$url_path || !$baseurl_path) {
        return false;
    }

    $baseurl_path = rtrim($baseurl_path, '/');
    if (strpos($url_path, $baseurl_path . '/') !== 0) {
        return false;
    }

    $relative_path = ltrim(substr($url_path, strlen($baseurl_path)), '/');
    if ($relative_path === '') {
        return false;
    }

    $file_path = trailingslashit($uploads['basedir']) . str_replace('/', DIRECTORY_SEPARATOR, rawurldecode($relative_path));
    $real_base = realpath($uploads['basedir']);
    $real_file = realpath($file_path);
    if ($real_base === false || $real_file === false) {
        return false;
    }

    $normalized_base = trailingslashit(wp_normalize_path($real_base));
    $normalized_file = wp_normalize_path($real_file);

    return strpos($normalized_file, $normalized_base) === 0 && is_file($real_file);
}

function mrp_replace_url($url) {
    if (!mrp_should_rewrite_urls() || !mrp_is_local_upload_url($url)) {
        return $url;
    }

    if (mrp_local_upload_file_exists($url)) {
        return $url;
    }

    return str_replace(mrp_get_local_wpcontent_url(), mrp_get_remote_base(), $url);
}

function mrp_rewrite_upload_urls_in_text($content) {
    if (!mrp_should_rewrite_urls()) {
        return $content;
    }

    $pattern = '#'. preg_quote(mrp_get_local_wpcontent_url(), '#') . '/uploads/[^"\'\s<>)]+#i';

    return preg_replace_callback($pattern, function ($matches) {
        return mrp_replace_url($matches[0]);
    }, $content);
}

function mrp_filter_url($url) {
    return mrp_replace_url($url);
}

function mrp_filter_image_src($image) {
    if (is_array($image) && isset($image[0])) {
        $image[0] = mrp_replace_url($image[0]);
    }

    return $image;
}

// Panel ustawien.
add_action('admin_menu', function() {
    add_options_page('Media Redirect', 'Media Redirect', 'manage_options', 'media-redirect', 'mrp_settings_page');
});

add_action('admin_init', function() {
    register_setting('mrp_settings_group', 'mrp_production_domain');
    register_setting('mrp_settings_group', 'mrp_custom_wpcontent');
    register_setting('mrp_settings_group', 'mrp_prefer_local_uploads');
});

function mrp_settings_page() {
    ?>
    <div class="wrap">
        <h1>Ustawienia Media Redirect</h1>
        <form method="post" action="options.php">
            <?php settings_fields('mrp_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Domena produkcyjna (z https://)</th>
                    <td>
                        <input type="text" name="mrp_production_domain"
                               value="<?php echo esc_attr(get_option('mrp_production_domain')); ?>"
                               placeholder="https://domena.pl"
                               class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Niestandardowa sciezka wp-content (opcjonalnie)</th>
                    <td>
                        <input type="text" name="mrp_custom_wpcontent"
                               value="<?php echo esc_attr(get_option('mrp_custom_wpcontent')); ?>"
                               placeholder="/app"
                               class="regular-text" />
                        <p class="description">Wprowadz tylko jesli Twoj katalog `wp-content` ma inna nazwe lub sciezke.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Preferuj lokalne pliki z uploads</th>
                    <td>
                        <input type="hidden" name="mrp_prefer_local_uploads" value="0" />
                        <label>
                            <input type="checkbox" name="mrp_prefer_local_uploads" value="1" <?php checked(get_option('mrp_prefer_local_uploads'), 1); ?> />
                            Jesli plik fizycznie istnieje w lokalnym katalogu `uploads`, zostaw lokalny URL zamiast przekierowania.
                        </label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function mrp_filter_srcset($sources) {
    if (!mrp_should_rewrite_urls() || !is_array($sources)) {
        return $sources;
    }

    foreach ($sources as $key => $source) {
        if (!empty($source['url'])) {
            $sources[$key]['url'] = mrp_replace_url($source['url']);
        }
    }

    return $sources;
}

function mrp_filter_image_src_attribute($attr, $attachment, $size) {
    if (!mrp_should_rewrite_urls() || empty($attr['src'])) {
        return $attr;
    }

    $attr['src'] = mrp_replace_url($attr['src']);

    return $attr;
}

function mrp_replace_media_urls_in_content($content) {
    return mrp_rewrite_upload_urls_in_text($content);
}

add_action('template_redirect', function () {
    ob_start('mrp_buffer_start');
});

function mrp_buffer_start($buffer) {
    return mrp_rewrite_upload_urls_in_text($buffer);
}

add_action('wp_footer', 'mrp_inject_frontend_rewrite_script');

function mrp_inject_frontend_rewrite_script() {
    if (!mrp_should_rewrite_urls() || mrp_should_prefer_local_uploads()) {
        return;
    }

    $domain = get_option('mrp_production_domain');
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
      const prodDomain = '<?php echo esc_js(rtrim($domain, '/')); ?>';
      const localHost = window.location.origin;

      document.querySelectorAll("img, source").forEach(el => {
        if (el.src && el.src.includes('/uploads/')) {
          el.src = el.src.replace(localHost, prodDomain);
        }
        if (el.srcset && el.srcset.includes('/uploads/')) {
          el.srcset = el.srcset.replaceAll(localHost, prodDomain);
        }
        if (el.dataset.src && el.dataset.src.includes('/uploads/')) {
          el.dataset.src = el.dataset.src.replace(localHost, prodDomain);
        }
        if (el.dataset.bg && el.dataset.bg.includes('/uploads/')) {
          el.dataset.bg = el.dataset.bg.replace(localHost, prodDomain);
        }
        if (el.style && el.style.backgroundImage && el.style.backgroundImage.includes('/uploads/')) {
          el.style.backgroundImage = el.style.backgroundImage.replace(localHost, prodDomain);
        }
      });
    });
    </script>
    <?php
}
