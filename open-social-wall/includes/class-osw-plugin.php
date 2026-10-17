<?php

if (!defined('ABSPATH')) {
    exit;
}

class OSW_Plugin {
    private static $instance = null;
    private $option_key = 'osw_settings';

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_shortcode('open_social_wall', array($this, 'shortcode'));
        add_action('wp_ajax_osw_refresh_cache', array($this, 'ajax_refresh_cache'));
        add_action('init', array($this, 'maybe_upgrade'));
    }

    public function maybe_upgrade() {
        $stored = get_option('osw_plugin_version', '0.0.0');
        if (version_compare($stored, OSW_VERSION, '<')) {
            update_option('osw_plugin_version', OSW_VERSION, false);
            $this->flush_cache();
        }
    }

    public function admin_menu() {
        add_options_page(
            __('Open Social Wall', 'open-social-wall'),
            __('Open Social Wall', 'open-social-wall'),
            'manage_options',
            'open-social-wall',
            array($this, 'settings_page')
        );
    }

    public function register_settings() {
        register_setting('osw_settings_group', $this->option_key, array($this, 'sanitize_settings'));
    }

    private function defaults() {
        return array(
            'mastodon_profile' => '',
            'mastodon_feed' => '',
            'pixelfed_profile' => '',
            'pixelfed_feed' => '',
            'additional_feeds' => array(),
            'limit' => 60,
            'initial_count' => 20,
            'batch_size' => 12,
            'cache_minutes' => 30,
            'max_width' => 1680,
        );
    }

    private function settings() {
        $settings = wp_parse_args(get_option($this->option_key, array()), $this->defaults());
        if (!is_array($settings['additional_feeds'])) {
            $settings['additional_feeds'] = array();
        }
        return $settings;
    }

    public function sanitize_settings($input) {
        $d = $this->defaults();
        $out = $d;
        $out['mastodon_profile'] = isset($input['mastodon_profile']) ? esc_url_raw(trim($input['mastodon_profile'])) : '';
        $out['mastodon_feed'] = isset($input['mastodon_feed']) ? esc_url_raw(trim($input['mastodon_feed'])) : '';
        $out['pixelfed_profile'] = isset($input['pixelfed_profile']) ? esc_url_raw(trim($input['pixelfed_profile'])) : '';
        $out['pixelfed_feed'] = isset($input['pixelfed_feed']) ? esc_url_raw(trim($input['pixelfed_feed'])) : '';
        $out['additional_feeds'] = array();

        if (!empty($input['additional_feeds']) && is_array($input['additional_feeds'])) {
            foreach (array_slice($input['additional_feeds'], 0, 20) as $feed) {
                if (!is_array($feed)) {
                    continue;
                }

                $name = isset($feed['name']) ? sanitize_text_field($feed['name']) : '';
                $feed_url = isset($feed['feed_url']) ? esc_url_raw(trim($feed['feed_url'])) : '';
                $profile_url = isset($feed['profile_url']) ? esc_url_raw(trim($feed['profile_url'])) : '';
                $format = isset($feed['format']) ? sanitize_key($feed['format']) : 'auto';

                if (!in_array($format, array('auto', 'rss', 'atom'), true)) {
                    $format = 'auto';
                }
                if (!$feed_url) {
                    continue;
                }
                if (!$name) {
                    $name = __('Additional feed', 'open-social-wall');
                }

                $out['additional_feeds'][] = array(
                    'id' => 'custom-' . substr(md5($feed_url), 0, 10),
                    'name' => $name,
                    'format' => $format,
                    'feed_url' => $feed_url,
                    'profile_url' => $profile_url,
                    'enabled' => !empty($feed['enabled']) ? 1 : 0,
                );
            }
        }

        $out['limit'] = isset($input['limit']) ? max(12, min(120, absint($input['limit']))) : $d['limit'];
        $out['initial_count'] = isset($input['initial_count']) ? max(6, min(36, absint($input['initial_count']))) : $d['initial_count'];
        $out['batch_size'] = isset($input['batch_size']) ? max(6, min(24, absint($input['batch_size']))) : $d['batch_size'];
        $out['cache_minutes'] = isset($input['cache_minutes']) ? max(5, min(1440, absint($input['cache_minutes']))) : $d['cache_minutes'];
        $out['max_width'] = isset($input['max_width']) ? max(900, min(2400, absint($input['max_width']))) : $d['max_width'];
        $this->flush_cache();
        return $out;
    }

    public function register_assets() {
        wp_register_style('osw-style', OSW_PLUGIN_URL . 'assets/social-wall.css', array(), OSW_VERSION);
        wp_register_script('osw-script', OSW_PLUGIN_URL . 'assets/social-wall.js', array(), OSW_VERSION, true);
    }

    private function configured_sources($settings) {
        $sources = array();

        if (!empty($settings['mastodon_feed'])) {
            $sources[] = array(
                'key' => 'mastodon',
                'label' => 'Mastodon',
                'format' => 'rss',
                'feed_url' => $settings['mastodon_feed'],
                'profile_url' => $settings['mastodon_profile'],
            );
        }

        if (!empty($settings['pixelfed_feed'])) {
            $sources[] = array(
                'key' => 'pixelfed',
                'label' => 'Pixelfed',
                'format' => 'atom',
                'feed_url' => $settings['pixelfed_feed'],
                'profile_url' => $settings['pixelfed_profile'],
            );
        }

        foreach ($settings['additional_feeds'] as $feed) {
            if (empty($feed['enabled']) || empty($feed['feed_url'])) {
                continue;
            }
            $sources[] = array(
                'key' => !empty($feed['id']) ? sanitize_key($feed['id']) : 'custom-' . substr(md5($feed['feed_url']), 0, 10),
                'label' => !empty($feed['name']) ? $feed['name'] : __('Additional feed', 'open-social-wall'),
                'format' => !empty($feed['format']) ? $feed['format'] : 'auto',
                'feed_url' => $feed['feed_url'],
                'profile_url' => !empty($feed['profile_url']) ? $feed['profile_url'] : '',
            );
        }

        return $sources;
    }

    public function shortcode($atts) {
        $settings = $this->settings();
        $atts = shortcode_atts(
            array('initial' => $settings['initial_count'], 'filters' => 'yes'),
            $atts,
            'open_social_wall'
        );

        $initial = max(1, min(36, absint($atts['initial'])));
        $posts = $this->get_posts(max($initial, absint($settings['limit'])));

        if (is_wp_error($posts)) {
            return current_user_can('manage_options') ? '<div class="osw-notice">' . esc_html($posts->get_error_message()) . '</div>' : '';
        }
        if (empty($posts)) {
            return current_user_can('manage_options') ? '<div class="osw-notice">' . esc_html__('Open Social Wall: configure at least one RSS/Atom feed under Settings → Open Social Wall.', 'open-social-wall') . '</div>' : '';
        }

        wp_enqueue_style('osw-style');
        wp_enqueue_script('osw-script');
        wp_localize_script('osw-script', 'OSW_DATA', array(
            'batchSize' => absint($settings['batch_size']),
            'labels' => array(
                'more' => __('Load more', 'open-social-wall'),
                'loading' => __('Loading…', 'open-social-wall'),
            ),
        ));

        $filter_sources = array();
        foreach ($posts as $post) {
            if (!isset($filter_sources[$post['source_key']])) {
                $filter_sources[$post['source_key']] = $post['source_label'];
            }
        }

        $has_more = count($posts) > $initial;
        $style = '--osw-max-width:' . absint($settings['max_width']) . 'px;';

        ob_start();
        ?>
        <section class="osw-wall" style="<?php echo esc_attr($style); ?>" data-osw-wall>
            <?php if ('yes' === strtolower($atts['filters']) && count($filter_sources) > 1) : ?>
                <div class="osw-filters-wrap">
                    <nav class="osw-filters" aria-label="<?php echo esc_attr__('Feed filter', 'open-social-wall'); ?>">
                        <button type="button" class="osw-filter is-active" data-filter="all"><?php esc_html_e('All', 'open-social-wall'); ?></button>
                        <?php foreach ($filter_sources as $source_key => $source_label) : ?>
                            <button type="button" class="osw-filter" data-filter="<?php echo esc_attr($source_key); ?>"><?php echo esc_html($source_label); ?></button>
                        <?php endforeach; ?>
                    </nav>
                </div>
            <?php endif; ?>

            <div class="osw-grid" data-osw-grid data-initial-count="<?php echo esc_attr($initial); ?>" data-batch-size="<?php echo esc_attr(absint($settings['batch_size'])); ?>">
                <?php foreach ($posts as $index => $post) : ?>
                    <?php echo $this->render_card($post, $index >= $initial); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endforeach; ?>
            </div>

            <div class="osw-load-more-wrap<?php echo $has_more ? '' : ' is-hidden'; ?>" data-osw-load-more-wrap>
                <button type="button" class="osw-load-more" data-osw-load-more><?php esc_html_e('Load more', 'open-social-wall'); ?></button>
                <p class="osw-load-more-status" data-osw-load-more-status aria-live="polite"></p>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    private function render_card($post, $hidden = false) {
        $source_key = !empty($post['source_key']) ? $post['source_key'] : $post['network'];
        $label = !empty($post['source_label']) ? $post['source_label'] : ucfirst($source_key);
        $classes = array('osw-card', 'osw-card--' . sanitize_html_class($source_key));
        if ($hidden) {
            $classes[] = 'is-paged-hidden';
        }
        if ('wide' === $post['layout']) {
            $classes[] = 'osw-card--wide';
        }
        if (!empty($post['media'])) {
            $classes[] = 'osw-card--media';
        }
        $date = $post['date_unix'] ? wp_date(get_option('date_format'), $post['date_unix']) : '';

        ob_start();
        ?>
        <article class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-network="<?php echo esc_attr($source_key); ?>" data-url="<?php echo esc_url($post['url']); ?>" tabindex="0" role="link" aria-label="<?php echo esc_attr(sprintf(__('Open original post from %s', 'open-social-wall'), $label)); ?>">
            <?php if (!empty($post['media'])) : ?>
                <div class="osw-media osw-media--<?php echo esc_attr(min(4, count($post['media']))); ?>">
                    <?php foreach ($post['media'] as $image) : ?>
                        <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" loading="lazy" decoding="async">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="osw-body">
                <header class="osw-meta">
                    <span class="osw-network"><?php echo esc_html($label); ?></span>
                    <?php if ($date) : ?><time datetime="<?php echo esc_attr($post['date_iso']); ?>"><?php echo esc_html($date); ?></time><?php endif; ?>
                </header>
                <?php if (!empty($post['content'])) : ?>
                    <div class="osw-content"><?php echo $post['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                <?php elseif (!empty($post['title'])) : ?>
                    <div class="osw-content"><p><?php echo esc_html($post['title']); ?></p></div>
                <?php endif; ?>
                <footer class="osw-footer"><a href="<?php echo esc_url($post['url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open original post', 'open-social-wall'); ?> <span aria-hidden="true">↗</span></a></footer>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    private function get_posts($limit) {
        $settings = $this->settings();
        $sources = $this->configured_sources($settings);
        $cache_key = 'osw_posts_' . md5(wp_json_encode(array($sources, $limit)));
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        $all = array();
        $errors = array();
        foreach ($sources as $source) {
            $items = OSW_Feed::load($source['feed_url'], $source['key'], $limit);
            if (is_wp_error($items)) {
                $errors[] = $source['label'] . ': ' . $items->get_error_message();
                continue;
            }
            foreach ($items as &$item) {
                $item['source_key'] = $source['key'];
                $item['source_label'] = $source['label'];
                $item['source_profile'] = $source['profile_url'];
                $item['source_format'] = $source['format'];
            }
            unset($item);
            $all = array_merge($all, $items);
        }

        usort($all, function ($a, $b) { return $b['date_unix'] <=> $a['date_unix']; });
        $seen = array();
        $posts = array();
        foreach ($all as $post) {
            $key = $post['url'] ?: $post['source_key'] . '|' . $post['id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $posts[] = $post;
            if (count($posts) >= $limit) {
                break;
            }
        }

        if (empty($posts) && !empty($errors)) {
            return new WP_Error('osw_feed_error', implode(' | ', $errors));
        }

        set_transient($cache_key, $posts, max(5, absint($settings['cache_minutes'])) * MINUTE_IN_SECONDS);
        update_option('osw_last_fetch', time(), false);
        if (!empty($errors)) {
            update_option('osw_last_errors', $errors, false);
        } else {
            delete_option('osw_last_errors');
        }
        return $posts;
    }

    private function flush_cache() {
        global $wpdb;
        $like_transient = $wpdb->esc_like('_transient_osw_posts_') . '%';
        $like_timeout = $wpdb->esc_like('_transient_timeout_osw_posts_') . '%';
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $like_transient, $like_timeout));
    }

    public function ajax_refresh_cache() {
        check_ajax_referer('osw_refresh_cache');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'open-social-wall')), 403);
        }
        $this->flush_cache();
        wp_send_json_success(array('message' => __('Cache cleared. The next page load will refresh the feeds.', 'open-social-wall')));
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $s = $this->settings();
        $last_fetch = absint(get_option('osw_last_fetch', 0));
        $last_errors = get_option('osw_last_errors', array());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Open Social Wall', 'open-social-wall'); ?></h1>
            <p><strong><?php echo esc_html(sprintf(__('Version %s', 'open-social-wall'), OSW_VERSION)); ?></strong> · <?php esc_html_e('Combine Mastodon, Pixelfed and additional public RSS/Atom feeds in one responsive wall.', 'open-social-wall'); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields('osw_settings_group'); ?>

                <h2><?php esc_html_e('Built-in feeds', 'open-social-wall'); ?></h2>
                <table class="form-table" role="presentation">
                    <?php $this->url_row('mastodon_profile', __('Mastodon profile', 'open-social-wall'), $s['mastodon_profile'], 'https://mastodon.example/@username'); ?>
                    <?php $this->url_row('mastodon_feed', __('Mastodon RSS feed', 'open-social-wall'), $s['mastodon_feed'], 'https://mastodon.example/@username.rss'); ?>
                    <?php $this->url_row('pixelfed_profile', __('Pixelfed profile', 'open-social-wall'), $s['pixelfed_profile'], 'https://pixelfed.example/username'); ?>
                    <?php $this->url_row('pixelfed_feed', __('Pixelfed Atom feed', 'open-social-wall'), $s['pixelfed_feed'], 'https://pixelfed.example/users/username.atom'); ?>
                </table>

                <h2><?php esc_html_e('Additional feeds', 'open-social-wall'); ?></h2>
                <p class="description"><?php esc_html_e('Add other public RSS or Atom feeds. The display name is used in the filter bar and on each card.', 'open-social-wall'); ?></p>
                <div id="osw-additional-feeds">
                    <?php foreach ($s['additional_feeds'] as $index => $feed) : ?>
                        <?php $this->additional_feed_card($index, $feed); ?>
                    <?php endforeach; ?>
                </div>
                <p><button type="button" class="button" id="osw-add-feed">+ <?php esc_html_e('Add another feed', 'open-social-wall'); ?></button></p>

                <h2><?php esc_html_e('Display and caching', 'open-social-wall'); ?></h2>
                <table class="form-table" role="presentation">
                    <?php $this->number_row('initial_count', __('Posts shown initially', 'open-social-wall'), $s['initial_count'], 6, 36); ?>
                    <?php $this->number_row('batch_size', __('Posts per “Load more”', 'open-social-wall'), $s['batch_size'], 6, 24); ?>
                    <?php $this->number_row('limit', __('Maximum feed pool', 'open-social-wall'), $s['limit'], 12, 120); ?>
                    <?php $this->number_row('cache_minutes', __('Cache duration (minutes)', 'open-social-wall'), $s['cache_minutes'], 5, 1440); ?>
                    <?php $this->number_row('max_width', __('Maximum wall width (px)', 'open-social-wall'), $s['max_width'], 900, 2400); ?>
                </table>
                <?php submit_button(); ?>
            </form>
            <hr>
            <h2><?php esc_html_e('Status', 'open-social-wall'); ?></h2>
            <p><strong><?php esc_html_e('Shortcode:', 'open-social-wall'); ?></strong> <code>[open_social_wall]</code></p>
            <p><strong><?php esc_html_e('Last feed fetch:', 'open-social-wall'); ?></strong> <?php echo $last_fetch ? esc_html(wp_date('Y-m-d H:i', $last_fetch)) : esc_html__('never', 'open-social-wall'); ?></p>
            <?php if (!empty($last_errors)) : ?><div class="notice notice-warning inline"><p><?php echo esc_html(implode(' | ', (array) $last_errors)); ?></p></div><?php endif; ?>
            <p><button type="button" class="button" id="osw-refresh-cache"><?php esc_html_e('Clear feed cache', 'open-social-wall'); ?></button> <span id="osw-refresh-result"></span></p>
        </div>

        <template id="osw-feed-template">
            <?php $this->additional_feed_card('__INDEX__', array('name' => '', 'format' => 'auto', 'feed_url' => '', 'profile_url' => '', 'enabled' => 1)); ?>
        </template>

        <style>
            .osw-admin-feed { max-width: 920px; margin: 14px 0; padding: 16px 18px; border: 1px solid #dcdcde; border-radius: 6px; background: #fff; }
            .osw-admin-feed-grid { display:grid; grid-template-columns: minmax(160px,.7fr) minmax(140px,.45fr) minmax(280px,1.4fr); gap:14px 18px; align-items:end; }
            .osw-admin-feed label { display:block; font-weight:600; margin-bottom:5px; }
            .osw-admin-feed input[type="text"], .osw-admin-feed input[type="url"], .osw-admin-feed select { width:100%; }
            .osw-admin-feed-profile { grid-column: span 2; }
            .osw-admin-feed-actions { display:flex; align-items:center; gap:14px; padding-bottom:3px; }
            @media (max-width: 782px) { .osw-admin-feed-grid { grid-template-columns:1fr; } .osw-admin-feed-profile { grid-column:auto; } }
        </style>
        <script>
        (function(){
            const holder=document.getElementById('osw-additional-feeds');
            const template=document.getElementById('osw-feed-template');
            const add=document.getElementById('osw-add-feed');
            let index=holder ? holder.querySelectorAll('.osw-admin-feed').length : 0;
            if(add&&holder&&template){add.addEventListener('click',function(){const html=template.innerHTML.replaceAll('__INDEX__',String(index++));holder.insertAdjacentHTML('beforeend',html);});}
            document.addEventListener('click',function(e){const button=e.target.closest('[data-osw-remove-feed]');if(button){const card=button.closest('.osw-admin-feed');if(card)card.remove();}});
            const b=document.getElementById('osw-refresh-cache'),r=document.getElementById('osw-refresh-result');
            if(b){b.addEventListener('click',function(){b.disabled=true;r.textContent='…';const body=new URLSearchParams({action:'osw_refresh_cache',_ajax_nonce:'<?php echo esc_js(wp_create_nonce('osw_refresh_cache')); ?>'});fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString()}).then(x=>x.json()).then(d=>{r.textContent=d.data&&d.data.message?d.data.message:'<?php echo esc_js(__('Done.', 'open-social-wall')); ?>';}).catch(()=>{r.textContent='<?php echo esc_js(__('Could not clear cache.', 'open-social-wall')); ?>';}).finally(()=>{b.disabled=false;});});}
        })();
        </script>
        <?php
    }

    private function additional_feed_card($index, $feed) {
        $name = isset($feed['name']) ? $feed['name'] : '';
        $format = isset($feed['format']) ? $feed['format'] : 'auto';
        $feed_url = isset($feed['feed_url']) ? $feed['feed_url'] : '';
        $profile_url = isset($feed['profile_url']) ? $feed['profile_url'] : '';
        $enabled = !isset($feed['enabled']) || !empty($feed['enabled']);
        $base = $this->option_key . '[additional_feeds][' . $index . ']';
        ?>
        <div class="osw-admin-feed">
            <div class="osw-admin-feed-grid">
                <div>
                    <label><?php esc_html_e('Display name', 'open-social-wall'); ?></label>
                    <input type="text" name="<?php echo esc_attr($base); ?>[name]" value="<?php echo esc_attr($name); ?>" placeholder="<?php esc_attr_e('My Blog', 'open-social-wall'); ?>">
                </div>
                <div>
                    <label><?php esc_html_e('Feed format', 'open-social-wall'); ?></label>
                    <select name="<?php echo esc_attr($base); ?>[format]">
                        <option value="auto" <?php selected($format, 'auto'); ?>><?php esc_html_e('Auto detect', 'open-social-wall'); ?></option>
                        <option value="rss" <?php selected($format, 'rss'); ?>>RSS</option>
                        <option value="atom" <?php selected($format, 'atom'); ?>>Atom</option>
                    </select>
                </div>
                <div>
                    <label><?php esc_html_e('Feed URL', 'open-social-wall'); ?></label>
                    <input type="url" name="<?php echo esc_attr($base); ?>[feed_url]" value="<?php echo esc_attr($feed_url); ?>" placeholder="https://example.com/feed/">
                </div>
                <div class="osw-admin-feed-profile">
                    <label><?php esc_html_e('Profile or website URL (optional)', 'open-social-wall'); ?></label>
                    <input type="url" name="<?php echo esc_attr($base); ?>[profile_url]" value="<?php echo esc_attr($profile_url); ?>" placeholder="https://example.com/">
                </div>
                <div class="osw-admin-feed-actions">
                    <label><input type="checkbox" name="<?php echo esc_attr($base); ?>[enabled]" value="1" <?php checked($enabled); ?>> <?php esc_html_e('Enabled', 'open-social-wall'); ?></label>
                    <button type="button" class="button-link-delete" data-osw-remove-feed><?php esc_html_e('Remove', 'open-social-wall'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    private function url_row($key, $label, $value, $placeholder) {
        ?>
        <tr><th scope="row"><label for="osw_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th><td><input class="regular-text" type="url" id="osw_<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($this->option_key); ?>[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr($placeholder); ?>"></td></tr>
        <?php
    }

    private function number_row($key, $label, $value, $min, $max) {
        ?>
        <tr><th scope="row"><label for="osw_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th><td><input type="number" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>" id="osw_<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($this->option_key); ?>[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>"></td></tr>
        <?php
    }
}
