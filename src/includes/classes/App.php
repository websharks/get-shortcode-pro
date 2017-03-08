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
    const VERSION = '170308.53060'; //v//

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
            '§default_options' => [],
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

        // if ($this->Wp->is_admin) {
        // Uncomment to enable a default menu page template.
            // add_action('admin_menu', [$this->Utils->MenuPage, 'onAdminMenu']);
        // }
    }
}
