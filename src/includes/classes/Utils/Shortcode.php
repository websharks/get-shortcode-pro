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
     * Initialized?
     *
     * @since 170311.42814
     *
     * @param bool|null
     */
    protected $initialized;

    /**
     * Whitelisted atts.
     *
     * @since 170311.42814
     *
     * @param array|null
     */
    protected $whitelisted_atts;

    /**
     * Current user ID.
     *
     * @since 170311.42814
     *
     * @param int|null
     */
    protected $current_user_id;

    /**
     * Current shortcode.
     *
     * @since 170311.42814
     *
     * @param string|null
     */
    protected $current_shortcode;

    /**
     * Current raw attributes.
     *
     * @since 170311.42814
     *
     * @param array|null
     */
    protected $current_raw_atts;

    /**
     * Current attributes.
     *
     * @since 170311.42814
     *
     * @param array|null
     */
    protected $current_atts;

    /**
     * Current values.
     *
     * @since 170311.42814
     *
     * @param array|null
     */
    protected $current_values;

    /**
     * Class constructor.
     *
     * @since 170311.42814 Security.
     *
     * @param Classes\App $App Instance.
     */
    public function __construct(Classes\App $App)
    {
        parent::__construct($App);
    }

    /**
     * Maybe initialize.
     *
     * @since 160709.39379 Refactor.
     */
    protected function maybeInitialize()
    {
        if ($this->initialized) {
            return; // Did this already.
        }
        $this->initialized      = true;
        $this->whitelisted_atts = []; // Initialize.

        $whitelisted_att_lines = s::getOption('whitelisted_atts');
        $whitelisted_att_lines = preg_split('/['."\r\n".']+/u', $whitelisted_att_lines);

        foreach ($whitelisted_att_lines as $_line) {
            if (!($_line = c::mbTrim($_line, '', ':|'))) {
                continue; // Empty line.
            } elseif ($_line[0] === '#') {
                continue; // A comment line.
            }
            if (mb_strpos($_line, ':') !== false) {
                list($_att, $_values)          = explode(':', $_line, 2);
                $_values                       = explode('|', $_values);
                // NOTE: Empty values allowed here too.
                // i.e., `att=""` or just `att` by itself.
                $this->whitelisted_atts[$_att] = $_values;
            } else {
                $this->whitelisted_atts[$_att = $_line] = [];
            }
        } // unset($_line, $_att, $_values); // Housekeeping.
    }

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
        /*
         * Maybe initialize.
         */
        $this->maybeInitialize();

        /*
         * Atts/content/shortcode.
         */
        $atts      = is_array($atts) ? $atts : [];
        $content   = (string) $content;
        $shortcode = (string) $shortcode;

        /*
         * Parse attributes.
         */
        $default_atts = [
            '_id'            => '', // User or Post ID.
            '_username'      => '', // User login name.
            '_for_blog'      => '', // A specific blog ID.
            '_size'          => '', // A specific size.

            '_delimiter'     => ', ', // Delimiter if `_stringify_aos=delimit` or is empty.
            '_stringify_aos' => '', // `json-pretty`, `json`, `serialize`, `delimit`, else delimit when applicable.
            '_format'        => '', // `json-pretty`, `json`, `serialize`, else force all strings (default).
            '_sprintf'       => '', // A formatting string with replacement codes.
            '_escape'        => '', // `true|false` escape HTML?

            '_default'       => '', // Default value.
            '_no_cache'      => '', // `true|false`.
        ];
        $raw_atts = $atts; // Copy.
        $atts     = c::unescHtml($atts);
        $atts     = array_merge($default_atts, $atts);

        if ($shortcode) { // NOTE: We don't use `shortcode_atts()` on purpose.
            $atts = apply_filters('shortcode_atts_'.$shortcode, $atts, $default_atts, $raw_atts, $shortcode);
        } // However, this will still apply the filter like `shortcode_atts()` would do.

        foreach ($default_atts as $_key => $_value) {
            if (!$this->attEnabled($_key, $atts[$_key])) {
                $atts[$_key] = $default_atts[$_key];
            } // This deals w/ `_att` defaults.
        } // unset($_key, $_value); // Housekeeping.

        $atts['_id']       = (int) $atts['_id'];
        $atts['_for_blog'] = (int) $atts['_for_blog'];
        // This one is held until the end, as its behavior can change.
        // $atts['_escape']   = filter_var($atts['_escape'], FILTER_VALIDATE_BOOLEAN);
        $atts['_no_cache'] = filter_var($atts['_no_cache'], FILTER_VALIDATE_BOOLEAN);

        /*
         * Set 'current' properties.
         */
        $this->current_user_id    = (int) get_current_user_id();
        $this->current_shortcode  = $shortcode;
        $this->current_raw_atts   = $raw_atts;
        $this->current_atts       = $atts;
        $this->current_values     = [];

        /*
         * Do shortcode.
         */
        $this->getValues();
        $this->strAosValues();
        $this->formatValues();
        $this->escapeValues();
        $this->maybeNoCache();

        $atts   = &$this->current_atts;
        $values = &$this->current_values;

        $values = $values ?: [$atts['_default']];
        ksort($values, SORT_NUMERIC); // Positional indexing.
        // e.g.,`1s-user="" 2s-user="" _sprintf="%1$s %2$s"`.

        if ($atts['_sprintf']) {
            return (string) @sprintf($atts['_sprintf'], ...$values);
        } else {
            return $values[0]; // Just one.
        }
    }

    /**
     * Get WP_User object instance.
     *
     * @since 170308.52831 Initial release.
     *
     * @param string $for_att On which attribute?
     *
     * @return \WP_User|null User object.
     */
    protected function getWpUser(string $for_att)
    {
        $atts = &$this->current_atts;

        if ($atts['_id']) {
            $WP_User = new \WP_User($atts['_id']);
        } elseif ($atts['_username']) {
            $WP_User = new \WP_User(0, $atts['_username']);
        } else {
            $WP_User = wp_get_current_user();
        }
        if ($WP_User instanceof \WP_User && $WP_User->exists() && $atts['_for_blog']) {
            $WP_User->for_blog($atts['_for_blog']);
        }
        return $WP_User instanceof \WP_User ? $WP_User : null;
    }

    /**
     * Get WP_Post object instance.
     *
     * @since 170308.52831 Initial release.
     *
     * @param string $for_att On which attribute?
     *
     * @return \WP_Post|null Post object.
     */
    protected function getWpPost(string $for_att)
    {
        $atts           = &$this->current_atts;
        $WP_Post        = get_post($atts['_id'] ?: null);
        return $WP_Post = $WP_Post instanceof \WP_Post ? $WP_Post : null;
    }

    /**
     * Get WP Sharks theme instance.
     *
     * @since 170308.52831 Initial release.
     *
     * @param string $for_att On which attribute?
     *
     * @return WpSharksCore\Classes\App|null Theme.
     */
    protected function getWpSharksTheme(string $for_att)
    {
        return $GLOBALS[$this->App::CORE_CONTAINER_VAR.'_theme'] ?? null;
    }

    /**
     * For current user?
     *
     * @since 170308.52831 Initial release.
     *
     * @param string $for_att On which attribute?
     *
     * @return bool For current user?
     */
    protected function isForCurrentUser(string $for_att): bool
    {
        $atts      = &$this->current_atts;
        return $is = !$atts['_id'] && !$atts['_username'] && !$atts['_for_blog'];
    }

    /**
     * Acquire all values.
     *
     * @since 170308.52831 Initial release.
     */
    protected function getValues()
    {
        $atts   = &$this->current_atts;
        $values = &$this->current_values;

        $key_regex = '/^(?<key>[0-9]+)s\-/ui'; // `-` is the `mb_stripos()` trigger.
        $uen_regex = '/(?:^|\:)(?<uen>%|(?:raw)?urlencode)\:/ui'; // `%` and `urlencode` triggers.
        $esc_regex = '/(?:^|\:)(?<esc>esc_(?:html|attr|textarea|url|js))\:/ui'; // `esc_` trigger.
        $raw_regex = '/(?:^|\:)raw(?:_html)?\:/ui'; // `raw` trigger.

        $values = []; // Initialize acquired values.

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
            // Once all directives are parsed out of `$_att` and `$_v`,
            // check whitelisted atts and whitelisted values for security.

            if (!$this->attEnabled($_att, $_v)) {
                continue; // Not enabled by configuration.
            } // This allows a site owner to be fairly specific.

            switch ($_att) { // Based on attribute.
                case 'title':
                    $values[$_key] = wp_get_document_title();
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

                case 'get_var':
                    $values[$_key] = $_GET[$_v] ?? null;
                    $values[$_key] = c::mbTrim(c::unslash($values[$_key]));
                    break; // Break here.

                case 'post_var':
                    $values[$_key] = $_POST[$_v] ?? null;
                    $values[$_key] = c::mbTrim(c::unslash($values[$_key]));
                    break; // Break here.

                case 'request_var':
                    $values[$_key] = $_REQUEST[$_v] ?? null;
                    $values[$_key] = c::mbTrim(c::unslash($values[$_key]));
                    break; // Break here.

                case 'cookie_var':
                    $values[$_key] = $_COOKIE[$_v] ?? null;
                    break; // Break here.

                case 'query_var': // i.e., WP query.
                    $values[$_key] = $_v ? get_query_var($_v) : null;
                    break; // Break here.

                case 'server_var':
                    $values[$_key] = $_SERVER[$_v] ?? null;
                    break; // Break here.

                case 'bloginfo':
                    $values[$_key] = $_v ? get_bloginfo($_v) : null;
                    break; // Break here.

                case 'option':
                    $values[$_key] = $_v ? get_option($_v) : null;
                    break; // Break here.

                case 'theme_option':
                    if ($_v && ($_WP_Sharks_Theme = $this->getWpSharksTheme($_att))) {
                        $values[$_key] = $_WP_Sharks_Theme->s::getOption($_v);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'time':
                    $values[$_key] = s::dateI18n($_v);
                    break; // Break here.

                case 'utc_time':
                    $values[$_key] = s::dateI18nUtc($_v);
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

                case 'user': // IP data works w/ current user only.
                    if ($this->isForCurrentUser($_att)) {
                        if ($_v === 'ip') {
                            $values[$_key] = c::currentIp();
                            break; // Break here.
                        } elseif ($_v === 'ip_region') {
                            $values[$_key] = c::ipRegion(c::currentIp());
                            break; // Break here.
                        } elseif ($_v === 'ip_country') {
                            $values[$_key] = c::ipCountry(c::currentIp());
                            break; // Break here.
                        }
                    } // ↑ Do not require a WP_User object instantiation.

                    if (($_WP_User = $this->getWpUser($_att))) {
                        if ($_v) { // One property.
                            if ($_v === 'avatar_url') {
                                $values[$_key] = get_avatar_url($_WP_User->ID, ['size' => $atts['_size'] ?: 128]);
                            } elseif ($_v === 'avatar') {
                                $values[$_key] = get_avatar($_WP_User->ID, $atts['_size'] ?: 128);
                            } elseif ($_v === 'gravatar_profile_url') {
                                $values[$_key] = 'https://www.gravatar.com/'.md5($_WP_User->user_email);
                            } else {
                                $values[$_key] = $_WP_User->{'user_'.$_v} ?? $_WP_User->{$_v} ?? null;
                            }
                        } else { // All properties.
                            $values[$_key] = get_object_vars($_WP_User);
                            $values[$_key] = (object) $values[$_key];
                        }
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'user_option':
                    if ($_v && ($_WP_User = $this->getWpUser($_att))) {
                        $values[$_key] = get_user_option($_v, $_WP_User->ID);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'user_meta':
                    if ($_v && ($_WP_User = $this->getWpUser($_att))) {
                        $values[$_key] = get_user_meta($_WP_User->ID, $_v, true);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'user_meta_values':
                    if ($_v && ($_WP_User = $this->getWpUser($_att))) {
                        $values[$_key] = get_user_meta($_WP_User->ID, $_v, false);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'post': // Property (or all props).
                    if (($_WP_Post = $this->getWpPost($_att))) {
                        if ($_v) { // One property.
                            if ($_v === 'permalink') {
                                $values[$_key] = get_permalink($_WP_Post);
                            } elseif ($_v === 'title') {
                                $values[$_key] = get_the_title($_WP_Post);
                            } elseif ($_v === 'excerpt') {
                                $values[$_key] = get_the_excerpt($_WP_Post);
                            } else {
                                $values[$_key] = $_WP_Post->{'post_'.$_v} ?? $_WP_Post->{$_v} ?? null;
                            }
                        } else { // All properties.
                            $values[$_key] = get_object_vars($_WP_Post);
                            $values[$_key] = (object) $values[$_key];
                        }
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'post_published_time':
                    if (($_WP_Post = $this->getWpPost($_att))) {
                        $values[$_key] = get_the_time($_v, $_WP_Post);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'post_published_time_ago':
                    if (($_WP_Post = $this->getWpPost($_att))) {
                        $_time         = (int) get_the_time('U', $_WP_Post);
                        $_current_time = (int) current_time('timestamp');
                        $values[$_key] = c::humanTimeDiff($_time, $_current_time).' '.__('ago', 'get-shortcode');
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'post_modified_time':
                    if (($_WP_Post = $this->getWpPost($_att))) {
                        $values[$_key] = get_the_modified_time($_v, $_WP_Post);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'post_modified_time_ago':
                    if (($_WP_Post = $this->getWpPost($_att))) {
                        $_time         = (int) get_the_modified_time('U', $_WP_Post);
                        $_current_time = (int) current_time('timestamp');
                        $values[$_key] = c::humanTimeDiff($_time, $_current_time).' '.__('ago', 'get-shortcode');
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'post_meta':
                    if ($_v && ($_WP_Post = $this->getWpPost($_att))) {
                        $values[$_key] = get_post_meta($_WP_Post->ID, $_v, true);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'post_meta_values':
                    if ($_v && ($_WP_Post = $this->getWpPost($_att))) {
                        $values[$_key] = get_post_meta($_WP_Post->ID, $_v, false);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'theme_post_meta':
                    if ($_v && ($_WP_Sharks_Theme = $this->getWpSharksTheme($_att)) && ($_WP_Post = $this->getWpPost($_att))) {
                        $values[$_key] = $_WP_Sharks_Theme->s::getPostMeta($_WP_Post->ID, $_v);
                    } else {
                        $values[$_key] = null;
                    }
                    break; // Break here.

                case 'theme_post_meta_values':
                    if ($_v && ($_WP_Sharks_Theme = $this->getWpSharksTheme($_att)) && ($_WP_Post = $this->getWpPost($_att))) {
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
            if (($_esc || $_raw) && !isset($atts['_escape'][0])) {
                $atts['_escape'] = 'false'; // Disable global escape.
            }
        } // unset($_att, $_v, $_m, $_key, $_uen, $_esc, $_raw);
        // unset($_WP_User, $_WP_Post, $_WP_Sharks_Theme); // Housekeeping.
    }

    /**
     * Is an att/value enabled?
     *
     * @since 170311.42814 Security enhancements.
     *
     * @param string $att   Shortcode att.
     * @param string $value Attribute value.
     *
     * @return bool True if att/value are enabled.
     */
    protected function attEnabled(string $att, string $value): bool
    {
        // NOTE: `current` properties are not available here.
        // See: `onShortcode()` where it's called early; before `currents`.

        // Not only that, but generally speaking, they shouldn't be available here.
        // Why? Because this determines which atts are acceptable to begin with.

        if ($this->whitelisted_atts) { // If a whitelist is given.
            //
            if (isset($this->whitelisted_atts[$att]) // Attribute must exist in the list.
            // And then, if specific values are whitelisted, the value must also exist in the list.
                    && (!$this->whitelisted_atts[$att] || in_array($value, $this->whitelisted_atts[$att], true))) {
                return true; // Explicitly enabled by configuration.
                //
            } else { // Default behavior when a whitelist is given.
                return false; // Not enabled by configuration.
            }
        } else { // Default behavior when no whitelist is given.
            return true; // Not disabled by configuration.
        }
    }

    /**
     * Stringify AOS values.
     *
     * @since 170308.52831 Initial release.
     */
    protected function strAosValues()
    {
        $atts   = &$this->current_atts;
        $values = &$this->current_values;

        foreach ($values as &$_value) {
            if (is_array($_value) || is_object($_value)) {
                switch ($atts['_stringify_aos']) {
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
                        $_value = implode($atts['_delimiter'], $_value);
                        break; // Break here.

                    default: // Delimit if not stringifying the final values.
                        if (!in_array($atts['_format'], ['json-pretty', 'json', 'serialize'], true)) {
                            if (is_object($_value)) {
                                $_value = get_object_vars($_value);
                            }
                            $_value = c::oneDimension($_value);
                            $_value = implode($atts['_delimiter'], $_value);
                        }
                        break; // Break here.
                }
            }
        } // Must destroy the reference.
        unset($_value); // Destroy reference.
    }

    /**
     * Format values.
     *
     * @since 170308.52831 Initial release.
     */
    protected function formatValues()
    {
        $atts   = &$this->current_atts;
        $values = &$this->current_values;

        switch ($atts['_format']) {
            case 'json-pretty':
                if (count($values) === 1) {
                    $values = [json_encode(reset($values), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)];
                } elseif ($values) {
                    $values = [json_encode(array_values($values), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)];
                } else {
                    $values = [json_encode($atts['_default'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)];
                }
                $values[0] = is_string($values[0]) ? $values[0] : '';
                break; // Break here.

            case 'json':
                if (count($values) === 1) {
                    $values = [json_encode(reset($values))];
                } elseif ($values) {
                    $values = [json_encode(array_values($values))];
                } else {
                    $values = [json_encode($atts['_default'])];
                }
                $values[0] = is_string($values[0]) ? $values[0] : '';
                break; // Break here.

            case 'serialize':
                if (count($values) === 1) {
                    $values = [serialize(reset($values))];
                } elseif ($values) {
                    $values = [serialize(array_values($values))];
                } else {
                    $values = [serialize($atts['_default'])];
                }
                $values[0] = is_string($values[0]) ? $values[0] : '';
                break; // Break here.

            default: // Force string values.
                foreach ($values as &$_value) {
                    $_value = $_value === false ? '0' : $_value;
                    $_value = is_scalar($_value) ? (string) $_value : '';

                    if ($_value === '') { // Empty?
                        $_value = $atts['_default'];
                    }
                } // Must destroy the reference.
                unset($_value); // Destroy reference.
                break; // Break here.
        }
    }

    /**
     * Escape values.
     *
     * @since 170308.52831 Initial release.
     */
    protected function escapeValues()
    {
        $atts   = &$this->current_atts;
        $values = &$this->current_values;

        if ($atts['_escape'] === '' || $atts['_escape'] === null) {
            $atts['_escape'] = true; // Default behavior.
        }
        $atts['_escape'] = filter_var($atts['_escape'], FILTER_VALIDATE_BOOLEAN);

        if ($atts['_escape']) {
            $values = array_map('esc_html', $values);
        }
    }

    /**
     * Maybe no-cache.
     *
     * @since 170308.52831 Initial release.
     */
    protected function maybeNoCache()
    {
        $atts = &$this->current_atts;

        if ($atts['_no_cache']) {
            c::noCacheFlags(); // Disallow page caching.
        } // Tells caching plugins NOT to cache the output of current page.
    }
}
