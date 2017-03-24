<?php
/**
 * Application.
 *
 * @author @jaswrks
 * @copyright WP Sharks™
 */
declare(strict_types=1);
namespace WebSharks\WpSharks\GetShortcode\Pro\Classes;

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
 * Application.
 *
 * @since 170308.52831 Initial release.
 */
class App extends SCoreClasses\App
{
    /**
     * Version.
     *
     * @since 170308.52831
     *
     * @type string Version.
     */
    const VERSION = '170324.21810'; //v//

    /**
     * Constructor.
     *
     * @since 170308.52831 Initial release.
     *
     * @param array $instance Instance args.
     */
    public function __construct(array $instance = [])
    {
        $instance_base = [
            '©di' => [
                '©default_rule' => [
                    'new_instances' => [],
                ],
            ],

            '§specs' => [
                '§in_wp'           => false,
                '§is_network_wide' => false,

                '§type'            => 'plugin',
                '§file'            => dirname(__FILE__, 4).'/plugin.php',
            ],
            '©brand' => [
                '©acronym'     => 'GETSC',
                '©name'        => '[get] Shortcode',

                '©slug'        => 'get-shortcode',
                '©var'         => 'get_shortcode',

                '©short_slug'  => 'get-sc',
                '©short_var'   => 'get_sc',

                '©text_domain' => 'get-shortcode',
            ],

            '§pro_option_keys' => [],
            '§default_options' => [
                'whitelisted_atts' => #
                    'url'."\n".
                    'title'."\n".
                    ''."\n".
                    'get_var'."\n".
                    'post_var'."\n".
                    'cookie_var'."\n".
                    'request_var'."\n".
                    'query_var'."\n".
                    'server_var:HTTP_HOST|SERVER_PORT|REQUEST_URI|PATH_INFO|HTTP_USER_AGENT|REMOTE_ADDR|HTTP_REFERER'."\n".
                    ''."\n".
                    'time'."\n".
                    'utc_time'."\n".
                    'strtotime'."\n".
                    ''."\n".
                    'md5'."\n".
                    'sha1'."\n".
                    'encrypt'."\n".
                    '#decrypt'."\n".
                    'unique_id'."\n".
                    ''."\n".
                    'user:ID|login|nicename|email|first_name|last_name|display_name|avatar|ip|ip_region|ip_country'."\n".
                    '#user_option:[replace this w/ whitelisted keys]'."\n".
                    '#user_meta:[replace this w/ whitelisted keys]'."\n".
                    '#user_meta_values:[replace this w/ whitelisted keys]'."\n".
                    ''."\n".
                    'post:parent|ID|guid|type|mime_type|name|title|permalink|excerpt|status|comment_status|comment_count|ping_status|menu_order'."\n".
                    'post_published_time'."\n".
                    'post_modified_time'."\n".
                    'post_published_time_ago'."\n".
                    'post_modified_time_ago'."\n".
                    ''."\n".
                    '#post_meta:[replace this w/ whitelisted keys]'."\n".
                    '#post_meta_values:[replace this w/ whitelisted keys]'."\n".
                    '#theme_post_meta:[replace this w/ whitelisted keys; works only with themes by WP Sharks]'."\n".
                    '#theme_post_meta_values:[replace this w/ whitelisted keys; works only with themes by WP Sharks]'."\n".
                    ''."\n".
                    'bloginfo:name|description|wpurl|url|admin_email|charset|version|html_type|text_direction|language|stylesheet_url|stylesheet_directory|template_url|pingback_url|atom_url|rdf_url|rss_url|rss2_url|comments_atom_url|comments_rss2_url'."\n".
                    'option:admin_email|blogname|blogdescription|blog_charset|date_format|default_category|home|siteurl|template|start_of_week|upload_path|users_can_register|posts_per_page|posts_per_rss'."\n".
                    '#theme_option:[replace this w/ whitelisted keys; works only with themes by WP Sharks]'."\n".
                    ''."\n".
                    '#_id'."\n".
                    '#_username'."\n".
                    '#_for_blog'."\n".
                    '_size'."\n".
                    ''."\n".
                    '_stringify_aos'."\n".
                    '_delimiter'."\n".
                    '_format'."\n".
                    '_sprintf'."\n".
                    '_escape'."\n".
                    '_default'."\n".
                    '_no_cache'."\n".
                '',
            ],
        ];
        parent::__construct($instance_base, $instance);
    }

    /**
     * Early hook setup handler.
     *
     * @since 170308.52831 Initial release.
     */
    protected function onSetupEarlyHooks()
    {
        parent::onSetupEarlyHooks();

        s::addAction('vs_upgrades', [$this->Utils->Installer, 'onVsUpgrades']);
        s::addAction('other_install_routines', [$this->Utils->Installer, 'onOtherInstallRoutines']);
        s::addAction('other_uninstall_routines', [$this->Utils->Uninstaller, 'onOtherUninstallRoutines']);
    }

    /**
     * Other hook setup handler.
     *
     * @since 170308.52831 Initial release.
     */
    protected function onSetupOtherHooks()
    {
        parent::onSetupOtherHooks();

        add_shortcode('get', [$this->Utils->Shortcode, 'onShortcode']);

        if ($this->Wp->is_admin) {
            add_action('admin_menu', [$this->Utils->MenuPage, 'onAdminMenu']);
        }
    }
}
