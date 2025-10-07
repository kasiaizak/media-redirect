<?php
/**
 * Plugin Name: Media Redirect to Production
 * Description: Przekierowuje URL-e mediów na domenę produkcyjną, z możliwością ustawienia własnego katalogu wp-content.
 * Version: 1.8
 * Author: Kasia Izak i ChatGPT
 */

// Filtry URL-i mediów
add_filter('wp_get_attachment_url', 'mrp_filter_url');
add_filter('wp_get_attachment_image_src', 'mrp_filter_image_src');

function mrp_filter_url($url) {
    return mrp_replace_url($url);
}

function mrp_filter_image_src($image) {
    if (is_array($image) && isset($image[0])) {
        $image[0] = mrp_replace_url($image[0]);
    }
    return $image;
}

function mrp_replace_url($url) {
    $domain = get_option('mrp_production_domain');
    $custom_wpcontent = get_option('mrp_custom_wpcontent');
    if (!$domain) return $url;

    // Ustal lokalny base URL
    $local_wpcontent_url = content_url();
    if ($custom_wpcontent) {
        $local_wpcontent_url = home_url(rtrim($custom_wpcontent, '/'));
    }

    $remote_base = rtrim($domain, '/') . '/' . trim(parse_url($local_wpcontent_url, PHP_URL_PATH), '/');

    // Zamień tylko jeśli trafia w uploads
    if (strpos($url, $local_wpcontent_url . '/uploads/') !== false) {
        return str_replace($local_wpcontent_url, $remote_base, $url);
    }

    return $url;
}

// Panel ustawień
add_action('admin_menu', function() {
    add_options_page('Media Redirect', 'Media Redirect', 'manage_options', 'media-redirect', 'mrp_settings_page');
});

add_action('admin_init', function() {
    register_setting('mrp_settings_group', 'mrp_production_domain');
    register_setting('mrp_settings_group', 'mrp_custom_wpcontent');
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
                               class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Niestandardowa ścieżka wp-content (opcjonalnie)</th>
                    <td>
                        <input type="text" name="mrp_custom_wpcontent"
                               value="<?php echo esc_attr(get_option('mrp_custom_wpcontent')); ?>"
                               placeholder="/app/wp-content"
                               class="regular-text" />
                        <p class="description">Wprowadź tylko jeśli Twój katalog `wp-content` ma inną nazwę lub ścieżkę.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_filter('wp_calculate_image_srcset', 'mrp_filter_srcset');

function mrp_filter_srcset($sources) {
    $domain = get_option('mrp_production_domain');
    $custom_wpcontent = get_option('mrp_custom_wpcontent');
    if (!$domain || !is_array($sources)) return $sources;

    $local_wpcontent_url = content_url();
    if ($custom_wpcontent) {
        $local_wpcontent_url = home_url(rtrim($custom_wpcontent, '/'));
    }

    $remote_base = rtrim($domain, '/') . '/' . trim(parse_url($local_wpcontent_url, PHP_URL_PATH), '/');

    foreach ($sources as $key => $source) {
        if (strpos($source['url'], $local_wpcontent_url . '/uploads/') !== false) {
            $sources[$key]['url'] = str_replace($local_wpcontent_url, $remote_base, $source['url']);
        }
    }

    return $sources;
}

add_filter('wp_get_attachment_image_attributes', 'mrp_filter_image_src_attribute', 10, 3);

function mrp_filter_image_src_attribute($attr, $attachment, $size) {
    $domain = get_option('mrp_production_domain');
    $custom_wpcontent = get_option('mrp_custom_wpcontent');
    if (!$domain || empty($attr['src'])) return $attr;

    $local_wpcontent_url = content_url();
    if ($custom_wpcontent) {
        $local_wpcontent_url = home_url(rtrim($custom_wpcontent, '/'));
    }

    $remote_base = rtrim($domain, '/') . '/' . trim(parse_url($local_wpcontent_url, PHP_URL_PATH), '/');

    if (strpos($attr['src'], $local_wpcontent_url . '/uploads/') !== false) {
        $attr['src'] = str_replace($local_wpcontent_url, $remote_base, $attr['src']);
    }

    return $attr;
}

add_filter('the_content', 'mrp_replace_media_urls_in_content', 99);

function mrp_replace_media_urls_in_content($content) {
    $domain = get_option('mrp_production_domain');
    $custom_wpcontent = get_option('mrp_custom_wpcontent');
    if (!$domain) return $content;

    $local_wpcontent_url = content_url();
    if ($custom_wpcontent) {
        $local_wpcontent_url = home_url(rtrim($custom_wpcontent, '/'));
    }

    $remote_base = rtrim($domain, '/') . '/' . trim(parse_url($local_wpcontent_url, PHP_URL_PATH), '/');

    return str_replace($local_wpcontent_url . '/uploads/', $remote_base . '/uploads/', $content);
}

add_action('template_redirect', function () {
    ob_start('mrp_buffer_start');
});

function mrp_buffer_start($buffer) {
    $domain = get_option('mrp_production_domain');
    $custom_wpcontent = get_option('mrp_custom_wpcontent');
    if (!$domain) return $buffer;

    $local_wpcontent_url = content_url();
    if ($custom_wpcontent) {
        $local_wpcontent_url = home_url(rtrim($custom_wpcontent, '/'));
    }

    $remote_base = rtrim($domain, '/') . '/' . trim(parse_url($local_wpcontent_url, PHP_URL_PATH), '/');

    // 🧠 REGEX: znajdź tylko URL-e do obrazków wewnątrz wp-content/uploads/
    $pattern = '#(' . preg_quote($local_wpcontent_url, '#') . '/uploads/[^"\')]+?\.(?:jpg|jpeg|png|gif|webp|svg))#i';
    $buffer = preg_replace_callback($pattern, function ($matches) use ($local_wpcontent_url, $remote_base) {
        return str_replace($local_wpcontent_url, $remote_base, $matches[0]);
    }, $buffer);

    return $buffer;
}

add_action('wp_footer', 'mrp_inject_frontend_rewrite_script');

function mrp_inject_frontend_rewrite_script() {
    // Nie rób nic, jeśli nie ustawiono domeny
    $domain = get_option('mrp_production_domain');
    if (!$domain) return;

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
