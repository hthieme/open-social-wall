<?php

if (!defined('ABSPATH')) {
    exit;
}

class OSW_Feed {
    /**
     * Loads a public RSS/Atom feed and normalizes posts.
     *
     * @param string $feed_url Feed URL.
     * @param string $network  Network key: mastodon|pixelfed.
     * @param int    $limit    Max items.
     * @return array|WP_Error
     */
    public static function load($feed_url, $network, $limit = 30) {
        if (empty($feed_url)) {
            return array();
        }

        include_once ABSPATH . WPINC . '/feed.php';

        $feed = fetch_feed($feed_url);
        if (is_wp_error($feed)) {
            return $feed;
        }

        $maxitems = $feed->get_item_quantity($limit);
        $items = $feed->get_items(0, $maxitems);
        $normalized = array();

        foreach ($items as $item) {
            $post = self::normalize_item($item, $network);
            if ($post) {
                $normalized[] = $post;
            }
        }

        return $normalized;
    }

    private static function normalize_item($item, $network) {
        $permalink = $item->get_permalink();
        $guid = $item->get_id();
        $date_unix = $item->get_date('U');
        $content = $item->get_content();
        $description = $item->get_description();

        if (!$content) {
            $content = $description;
        }

        $media = self::extract_media($item, $content);
        $clean_content = self::sanitize_content($content);

        if (!$guid) {
            $guid = $permalink ?: md5($clean_content . '|' . $date_unix);
        }

        return array(
            'network'   => sanitize_key($network),
            'id'        => (string) $guid,
            'url'       => esc_url_raw($permalink),
            'date_unix' => $date_unix ? (int) $date_unix : 0,
            'date_iso'  => $date_unix ? gmdate('c', (int) $date_unix) : '',
            'title'     => wp_strip_all_tags((string) $item->get_title()),
            'content'   => $clean_content,
            'media'     => $media,
            'layout'    => self::layout_variant($guid, $network, $media),
        );
    }

    private static function extract_media($item, $content) {
        $media = array();
        $enclosures = $item->get_enclosures();

        if (!empty($enclosures)) {
            foreach ($enclosures as $enclosure) {
                $link = $enclosure->get_link();
                $type = $enclosure->get_type();
                if (!$link || ($type && strpos($type, 'image/') !== 0)) {
                    continue;
                }
                $media[] = array('url' => esc_url_raw($link), 'alt' => '');
            }
        }

        if (empty($media) && !empty($content)) {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
            libxml_clear_errors();

            if ($loaded) {
                foreach ($dom->getElementsByTagName('img') as $img) {
                    $src = $img->getAttribute('src');
                    if ($src) {
                        $media[] = array(
                            'url' => esc_url_raw($src),
                            'alt' => sanitize_text_field($img->getAttribute('alt')),
                        );
                    }
                }
            }
        }

        $seen = array();
        $result = array();
        foreach ($media as $entry) {
            if (!$entry['url'] || isset($seen[$entry['url']])) {
                continue;
            }
            $seen[$entry['url']] = true;
            $result[] = $entry;
            if (count($result) >= 4) {
                break;
            }
        }

        return $result;
    }

    private static function sanitize_content($html) {
        if (!$html) {
            return '';
        }

        $html = preg_replace('/<img\b[^>]*>/i', '', $html);
        $allowed = array(
            'p' => array(), 'br' => array(),
            'a' => array('href' => true, 'title' => true, 'rel' => true, 'target' => true, 'class' => true),
            'span' => array('class' => true),
            'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(),
            'blockquote' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(), 'code' => array(),
        );

        $html = wp_kses($html, $allowed);
        $html = preg_replace_callback('/<a\s+([^>]*href=["\'][^"\']+["\'][^>]*)>/i', function ($matches) {
            $attrs = $matches[1];
            if (stripos($attrs, 'target=') === false) {
                $attrs .= ' target="_blank"';
            }
            if (stripos($attrs, 'rel=') === false) {
                $attrs .= ' rel="noopener noreferrer"';
            }
            return '<a ' . $attrs . '>';
        }, $html);

        return trim($html);
    }

    private static function layout_variant($guid, $network, $media) {
        if (empty($media)) {
            return 'standard';
        }

        $seed = abs(crc32((string) $guid));
        if (count($media) >= 2) {
            return 'wide';
        }
        if ($network === 'pixelfed' && ($seed % 3 === 0)) {
            return 'wide';
        }
        if ($network === 'mastodon' && ($seed % 7 === 0)) {
            return 'wide';
        }
        return 'standard';
    }
}
