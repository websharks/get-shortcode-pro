<?php
/**
 * Shortcode utils.
 *
 * @author @jaswrks
 * @copyright WP Sharks™
 */
declare(strict_types=1);
namespace WebSharks\WpSharks\GetShortcode\Pro\Classes\Utils;

use WebSharks\WpSharks\GetShortcode\Pro\Classes;
use WebSharks\WpSharks\GetShortcode\Pro\Interfaces;
use WebSharks\WpSharks\GetShortcode\Pro\Traits;
#
use WebSharks\WpSharks\GetShortcode\Pro\Classes\AppFacades as a;
use WebSharks\WpSharks\GetShortcode\Pro\Classes\SCoreFacades as s;
use WebSharks\WpSharks\GetShortcode\Pro\Classes\CoreFacades as c;
#
use WebSharks\WpSharks\Core\Classes as SCoreClasses;
use WebSharks\WpSharks\Core\Interfaces as SCoreInterfaces;
use WebSharks\WpSharks\Core\Traits as SCoreTraits;
#
use WebSharks\Core\WpSharksCore\Classes as CoreClasses;
use WebSharks\Core\WpSharksCore\Classes\Core\Base\Exception;
use WebSharks\Core\WpSharksCore\Interfaces as CoreInterfaces;
use WebSharks\Core\WpSharksCore\Traits as CoreTraits;
#
use function assert as debug;
use function get_defined_vars as vars;

/**
 * Shortcode utils.
 *
 * @since 170308.52831 Initial release.
 */
class Shortcode extends SCoreClasses\SCore\Base\Core
{
    /**
     * Shortcode.
     *
     * @since 170308.52831 Initial release.
     *
     * @param array|string $atts      Shortcode attributes.
     * @param string|null  $content   Shortcode content.
     * @param string       $shortcode Shortcode name.
     */
    public function onShortcode($atts = [], $content = '', $shortcode = ''): string
    {
        $atts      = is_array($atts) ? $atts : [];
        $raw_atts  = $atts; // Copy of originals.
        $atts      = c::unescHtml($atts);
        $content   = (string) $content;
        $shortcode = (string) $shortcode;

        $default_atts = []; // None.
        $atts         = array_merge($default_atts, $atts);
        $atts         = apply_filters('shortcode_atts_'.$shortcode, $atts, $default_atts, $raw_atts, $shortcode);
        $atts         = array_map('strval', $atts); // Force all strings.

        $values = $this->getValues($atts);
        $values = $this->strAosValues($atts, $values);
        $values = $this->formatValues($atts, $values);
        $values = $this->escapeValues($atts, $values);

        $values = $values ?: [$atts['_default'] ?? ''];
        ksort($values, SORT_NUMERIC); // Positional indexing.
        // e.g.,`1s-user="" 2s-user="" _sprintf="%1$s %2$s"`.

        $this->maybeNoCache($atts);

        if (!empty($atts['_sprintf'])) {
            return (string) @sprintf($atts['_sprintf'], ...$values);
        } else {
            return $values[0]; // Just one.
        }
    }

    /**
     * Acquire all values.
     *
     * @since 170308.52831 Initial release.
     *
     * @param array|string $atts Shortcode atts.
     *
     * @return array An array of all values.
     */
    protected function getValues(array &$atts): array
    {
        $get_wp_user = function (string $for_att) use ($atts) {
            if (!empty($atts['_id'])) {
                return new \WP_User((int) $atts['_id']);
            } elseif (!empty($atts['_username'])) {
                return new \WP_User(0, $atts['_username']);
            }
            return wp_get_current_user();
        };
        $get_wp_post = function (string $for_att) use ($atts) {
            if (!empty($atts['_id'])) {
                return get_post((int) $atts['_id']);
            }
            return get_post();
        };
        $get_wps_theme = function (string $for_att) use ($atts) {
            return $GLOBALS[$this->App::CORE_CONTAINER_VAR.'_theme'] ?? null;
        };
        $key_regex = '/^(?<key>[0-9]+)s\-/ui'; // `-` is the `mb_stripos()` trigger.
        $uen_regex = '/(?:^|\:)(?<uen>%|(?:raw)?urlencode)\:/ui'; // `%` and `urlencode` triggers.
        $esc_regex = '/(?:^|\:)(?<esc>esc_(?:html|attr|textarea|url|js))\:/ui'; // `esc_` trigger.
        $raw_regex = '/(?:^|\:)raw(?:_html)?\:/ui'; // `raw` trigger.

        $values = []; // Initialize array of all acquired values.

        foreach ($atts as $_att => $_v) { // Acquire values.
            if (is_int($_att)) {
                $_att = $_v;
                $_v   = '';
            } // Positional attribute.

            if (!$_att || !is_string($_att)) {
                continue; // Bypass.
            } elseif ($_att[0] === '_') {
                continue; // Bypass.
            }
            if (mb_stripos($_att, '-') !== false && preg_match($key_regex, $_att, $_m)) {
                $_key = (int) $_m['key']; // Positional key.
                if (!($_att = preg_replace($key_regex, '', $_att))) {
                    continue; // Empty w/o numeric key prefix.
                } // e.g.,`1s-user="" 2s-user="" _sprintf="%1$s %2$s"`.
            } else {
                $_key = count($values); // Default key.
            }
            $_uen = $_esc = $_raw = false; // Initialize these.

            if ((mb_stripos($_v, '%') !== false || mb_stripos($_v, 'urlencode') !== false) && preg_match($uen_regex, $_v, $_m)) {
                $_uen = $_m['uen'] === '%' ? 'urlencode' : $_m['uen'];
                $_v   = preg_replace($uen_regex, '', $_v);
            }
            if (mb_stripos($_v, 'esc_') !== false && preg_match($esc_regex, $_v, $_m)) {
                $_esc = $_m['esc']; // A function by name.
                $_v   = preg_replace($esc_regex, '', $_v);
            }
            if (mb_stripos($_v, 'raw') !== false && preg_match($raw_regex, $_v, $_m)) {
                $_raw = true; // Simply a flag.
                $_v   = preg_replace($raw_regex, '', $_v);
            }
            switch ($_att) { // Based on attribute.
                case 'doc_title':
                    $values[$_key] = wp_get_document_title();
                    break; // Break here.

                case 'id': // i.e., Post ID.
                    if (($_WP_Post = $get_wp_post($_att))) {
                        $values[$_key] = $_WP_Post->ID;
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'title':
                    if (($_WP_Post = $get_wp_post($_att))) {
                        $values[$_key] = get_the_title($_WP_Post);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'permalink':
                    if (($_WP_Post = $get_wp_post($_att))) {
                        $values[$_key] = get_permalink($_WP_Post);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'url': // Or component in URL.
                    switch ($_v) {
                        case 'scheme':
                            $values[$_key] = c::currentScheme();
                            break; // Break here.

                        case 'host':
                            $values[$_key] = c::currentHost();
                            break; // Break here.

                        case 'root-host':
                            $values[$_key] = c::currentRootHost();
                            break; // Break here.

                        case 'port':
                            $values[$_key] = c::currentPort();
                            break; // Break here.

                        case 'uri':
                            $values[$_key] = c::currentUri();
                            break; // Break here.

                        case 'path':
                            $values[$_key] = c::currentPath();
                            break; // Break here.

                        case '': // Full URL.
                        default: // Default behavior.
                            $values[$_key] = c::currentUrl();
                            break; // Break here.
                    }
                    break; // Break here.

                case 'request_var':
                    $values[$_key] = $_REQUEST[$_v] ?? null;
                    $values[$_key] = c::mbTrim(c::unslash($values[$_key]));
                    break; // Break here.

                case 'server_var': // @TODO Security.
                    $values[$_key] = $_SERVER[$_v] ?? null;
                    break; // Break here.

                case 'bloginfo': // @TODO Security.
                    $values[$_key] = $_v ? get_bloginfo($_v) : null;
                    break; // Break here.

                case 'option': // @TODO Security.
                    $values[$_key] = $_v ? get_option($_v) : null;
                    break; // Break here.

                case 'theme_option': // @TODO Security.
                    if ($_v && ($_WP_Sharks_Theme = $get_wps_theme($_att))) {
                        $values[$_key] = $_WP_Sharks_Theme->s::getOption($_v);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'year':
                    $values[$_key] = s::dateI18nUtc('Y');
                    break; // Break here.

                case 'date':
                case 'time':
                case 'utc_date':
                case 'utc_time':
                    $values[$_key] = s::dateI18nUtc($_v);
                    break; // Break here.

                case 'local_date':
                case 'local_time':
                    $values[$_key] = s::dateI18n($_v);
                    break; // Break here.

                case 'strtotime':
                    $values[$_key] = strtotime($_v ?: 'now');
                    break; // Break here.

                case 'unique_id':
                    $values[$_key] = c::uniqueId($_v);
                    break; // Break here.

                case 'md5':
                    $values[$_key] = md5($_v);
                    break; // Break here.

                case 'sha1':
                    $values[$_key] = sha1($_v);
                    break; // Break here.

                case 'encrypt':
                    $values[$_key] = $_v ? c::encrypt($_v) : null;
                    break; // Break here.

                case 'decrypt':
                    $values[$_key] = $_v ? c::decrypt($_v) : null;
                    break; // Break here.

                case 'user': // Property (or all props).
                    if (($_WP_User = $get_wp_user($_att))) {
                        if ($_v) { // One property.
                            $values[$_key] = $_WP_User->{$_v} ?? null;
                        } else { // All properties.
                            $values[$_key] = get_object_vars($_WP_User);
                            $values[$_key] = (object) $values[$_key];
                        }
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'user_ip':
                    $values[$_key] = c::currentIp();
                    break; // Break here.

                case 'user_ip_region':
                    $values[$_key] = c::ipRegion(c::currentIp());
                    break; // Break here.

                case 'user_ip_country':
                    $values[$_key] = c::ipCountry(c::currentIp());
                    break; // Break here.

                case 'user_property':
                    if ($_v && ($_WP_User = $get_wp_user($_att))) {
                        $values[$_key] = $_WP_User->{$_v} ?? null;
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'user_option':
                    if ($_v && ($_WP_User = $get_wp_user($_att))) {
                        $values[$_key] = get_user_option($_v, $_WP_User->ID);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'user_meta':
                    if ($_v && ($_WP_User = $get_wp_user($_att))) {
                        $values[$_key] = get_user_meta($_WP_User->ID, $_v, true);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'user_metas':
                case 'user_meta_values':
                    if ($_v && ($_WP_User = $get_wp_user($_att))) {
                        $values[$_key] = get_user_meta($_WP_User->ID, $_v, false);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'post': // Property (or all props).
                    if (($_WP_Post = $get_wp_post($_att))) {
                        if ($_v) { // One property.
                            $values[$_key] = $_WP_Post->{$_v} ?? null;
                        } else { // All properties.
                            $values[$_key] = get_object_vars($_WP_Post);
                            $values[$_key] = (object) $values[$_key];
                        }
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'post_property':
                    if ($_v && ($_WP_Post = $get_wp_post($_att))) {
                        $values[$_key] = $_WP_Post->{$_v} ?? null;
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'post_meta':
                    if ($_v && ($_WP_Post = $get_wp_post($_att))) {
                        $values[$_key] = get_post_meta($_WP_Post->ID, $_v, true);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'post_metas':
                case 'post_meta_values':
                    if ($_v && ($_WP_Post = $get_wp_post($_att))) {
                        $values[$_key] = get_post_meta($_WP_Post->ID, $_v, false);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'theme_post_meta':
                    if ($_v && ($_WP_Sharks_Theme = $get_wps_theme($_att)) && ($_WP_Post = $get_wp_post($_att))) {
                        $values[$_key] = $_WP_Sharks_Theme->s::getPostMeta($_WP_Post->ID, $_v);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'theme_post_metas':
                case 'theme_post_meta_values':
                    if ($_v && ($_WP_Sharks_Theme = $get_wps_theme($_att)) && ($_WP_Post = $get_wp_post($_att))) {
                        $values[$_key] = $_WP_Sharks_Theme->s::collectPostMeta($_WP_Post->ID, $_v);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                default: // Not possible.
                    $values[$_key] = null;
                    break; // Break here.
            }
            if ($_uen && $values[$_key] && is_scalar($values[$_key])) {
                $values[$_key]   = $values[$_key] === false ? '0' : $values[$_key];
                $values[$_key]   = $_uen((string) $values[$_key]);
            }
            if ($_esc && $values[$_key] && is_scalar($values[$_key])) {
                $values[$_key]   = $values[$_key] === false ? '0' : $values[$_key];
                $values[$_key]   = $_esc((string) $values[$_key]);
            }
            if ($_esc || $_raw) { // Per-attribute detection.
                // On a per-attribute basis, change default behavior.
                // `_escape="?"` is the same as `_escape="false"` et al.
                $atts['_escape'] = $atts['_escape'] ?? '?';
            }
        } // unset($_att, $_v, $_m, $_key, $_uen, $_esc, $_raw);
        // unset($_WP_User, $_WP_Post, $_WP_Sharks_Theme); // Housekeeping.

        return $values; // All acquired values.
    }

    /**
     * Stringify AOS values.
     *
     * @since 170308.52831 Initial release.
     *
     * @param array $atts   Shortcode atts.
     * @param array $values Array of all values.
     *
     * @return array All stringified AOS values.
     */
    protected function strAosValues(array &$atts, array $values): array
    {
        foreach ($values as &$_value) {
            if (is_array($_value) || is_object($_value)) {
                switch ($atts['_stringify_aos'] ?? '') {
                    case 'json-pretty':
                        $_value = json_encode($_value, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                        $_value = is_string($_value) ? $_value : '';
                        break; // Break here.

                    case 'json':
                        $_value = json_encode($_value);
                        $_value = is_string($_value) ? $_value : '';
                        break; // Break here.

                    case 'serialize':
                        $_value = serialize($_value);
                        $_value = is_string($_value) ? $_value : '';
                        break; // Break here.

                    case 'delimit':
                        if (is_object($_value)) {
                            $_value = get_object_vars($_value);
                        }
                        $_value = c::oneDimension($_value);
                        $_value = implode($atts['_delimiter'] ?? ', ', $_value);
                        break; // Break here.

                    default: // Delimit if not stringifying the final values.
                        if (!in_array($atts['_format'] ?? '', ['json-pretty', 'json', 'serialize'], true)) {
                            if (is_object($_value)) {
                                $_value = get_object_vars($_value);
                            }
                            $_value = c::oneDimension($_value);
                            $_value = implode($atts['_delimiter'] ?? ', ', $_value);
                        }
                        break; // Break here.
                }
            }
        } // Must destroy the reference.
        unset($_value); // Destroy reference.

        return $values; // All transformed values.
    }

    /**
     * Format values.
     *
     * @since 170308.52831 Initial release.
     *
     * @param array $atts   Shortcode atts.
     * @param array $values Array of all values.
     *
     * @return string[] All formatted string values.
     */
    protected function formatValues(array &$atts, array $values): array
    {
        switch ($atts['_format'] ?? '') {
            case 'json-pretty':
                if (count($values) === 1) {
                    $values = [json_encode(reset($values), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)];
                } elseif ($values) {
                    $values = [json_encode(array_values($values), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)];
                } else {
                    $values = [json_encode($atts['_default'] ?? '', JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)];
                }
                $values[0] = is_string($values[0]) ? $values[0] : '';
                break; // Break here.

            case 'json':
                if (count($values) === 1) {
                    $values = [json_encode(reset($values))];
                } elseif ($values) {
                    $values = [json_encode(array_values($values))];
                } else {
                    $values = [json_encode($atts['_default'] ?? '')];
                }
                $values[0] = is_string($values[0]) ? $values[0] : '';
                break; // Break here.

            case 'serialize':
                if (count($values) === 1) {
                    $values = [serialize(reset($values))];
                } elseif ($values) {
                    $values = [serialize(array_values($values))];
                } else {
                    $values = [serialize($atts['_default'] ?? '')];
                }
                $values[0] = is_string($values[0]) ? $values[0] : '';
                break; // Break here.

            default: // Force string values.
                foreach ($values as &$_value) {
                    $_value = $_value === false ? '0' : $_value;
                    $_value = is_scalar($_value) ? (string) $_value : '';

                    if ($_value === '') { // Empty?
                        $_value = $atts['_default'] ?? '';
                    }
                } // Must destroy the reference.
                unset($_value); // Destroy reference.
                break; // Break here.
        }
        return $values; // All formatted (string) values.
    }

    /**
     * Escape values.
     *
     * @since 170308.52831 Initial release.
     *
     * @param array $atts   Shortcode atts.
     * @param array $values Array of all values.
     *
     * @return string[] Possibly-escaped string values.
     */
    protected function escapeValues(array &$atts, array $values): array
    {
        $escape = $atts['_escape'] ?? '';
        $escape = $escape === '?' ? false : $escape;
        $escape = $escape === '' ? true : $escape;

        if (filter_var($escape, FILTER_VALIDATE_BOOLEAN)) {
            $values = array_map('esc_html', $values);
        }
        return $values; // Possibly-escaped string values.
    }

    /**
     * Maybe no-cache.
     *
     * @since 170308.52831 Initial release.
     *
     * @param array|string $atts Shortcode atts.
     */
    protected function maybeNoCache(array &$atts)
    {
        if (filter_var($atts['_no_cache'] ?? '', FILTER_VALIDATE_BOOLEAN)) {
            c::noCacheFlags(); // Disallow page caching.
        } // Tells caching plugins NOT to cache the output of current page.
    }
}
