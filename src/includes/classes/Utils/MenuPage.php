<?php
/**
 * Menu page utils.
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
 * Menu page utils.
 *
 * @since 170311.42814 Menu page utils.
 */
class MenuPage extends SCoreClasses\SCore\Base\Core
{
    /**
     * Adds menu pages.
     *
     * @since 170311.42814 Menu page utils.
     */
    public function onAdminMenu()
    {
        s::addMenuPageItem([
            'parent_page'   => 'options-general.php',
            'template_file' => 'admin/menu-pages/options/default.php',

            'meta_links' => ['restore' => true],
            'tabs'       => [
                'default' => sprintf(__('%1$s', 'get-shortcode'), esc_html($this->App->Config->©brand['©name'])),
            ],
        ]);
    }
}
