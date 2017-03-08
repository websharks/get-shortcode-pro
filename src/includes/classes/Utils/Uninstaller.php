<?php
/**
 * Uninstall utils.
 *
 * @author @jaswrks
 * @copyright WP Sharks™
 */
declare (strict_types = 1);
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
 * Uninstall utils.
 *
 * @since 170308.52831 Initial release.
 */
class Uninstaller extends SCoreClasses\SCore\Base\Core
{
    /**
     * Other uninstall routines.
     *
     * @since 170308.52831 Initial release.
     *
     * @param int $site_counter Site counter.
     */
    public function onOtherUninstallRoutines(int $site_counter)
    {
        // Do something here.
        // $this->uninstallSomething($site_counter);
        // i.e., Create protected methods in this class.
    }
}
