<?php
/**
 * Template.
 *
 * @author @jaswsinc
 * @copyright WP Sharks™
 */
declare(strict_types=1);
namespace WebSharks\WpSharks\GetShortcode\Pro;

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

if (!defined('WPINC')) {
    exit('Do NOT access this file directly.');
}
$Form = $this->s::menuPageForm('§save-options');
?>
<?= $Form->openTag(); ?>
    <?= $Form->openTable(
        __('Whitelisting Shortcode Attributes/Values', 'get-shortcode'),
        '<p>'.__('The field below allows you to whitelist specific [get] Shortcode Attributes and even specific <code>[get attribute="values"]</code>, thereby blacklisting all others that do not appear in your whitelist. A whitelist is optional, but recommended as a security measure.', 'get-shortcode').'</p>'.
        '<p>'.__('Why? Because some of the data you can [get] with this plugin may expose things you\'d like to keep secret in a multi-Author environment. e.g., If you have Authors besides yourself that are allowed to use Shortcodes in Posts, you don\'t want <code>[get server_var="SOMETHING_SECRET"]</code> to work.', 'get-shortcode').'</p>'.
        '<p>'.__('That\'s why this plugin comes with a default whitelist that effectively disables functionality that could be considered unsafe/private in a multi-Author environment. It\'s worth noting that the default whitelist is just fine like it is. You can customize further if you\'d like, but it\'s not necessary.', 'get-shortcode').'</p>'.
        '<p>'.__('<h3>Attribute Whitelist Syntax</h3><p>Place one Shortcode Attribute Name on each line, which adds the Attribute Name to the whitelist and allows others who can edit Posts to use that Attribute in the Shortcode. The optional <code>:</code> symbol can be used to separate the Attribute Name from a <code>|</code> pipe-delimited list of whitelisted Attribute Values. A line beginning with a <code>#</code> symbol is ignored by the parser; i.e., considered to be an internal comment only.', 'get-shortcode').'</p>'.
        '<p>'.__('<strong>Don\'t want or need a whitelist?</strong> Just empty the field completely. When this field is completely empty, all Attributes and all Attribute Values are accepted. Security checks are only performed when you <em>do</em> have a whitelist. If you don\'t have a whitelist, security checks are all bypassed entirely.', 'get-shortcode').'</p>'.
        '<p>'.__('Lines that contain only the Attribute Name will allow any Attribute Value; i.e., security checks against the <code>attribute="value"</code> are only performed when you <em>do</em> have a pipe-delimited Attribute Value whitelist — in addition to whitelisting the Attribute Name itself. In other words, if you only whitelist the Attribute Name itself, and you don\'t list any Attribute Values, then any Value is accepted.', 'get-shortcode').'</p>'.
        '<p>'.sprintf(__('Browse our <a href="%1$s" target="_blank">knowledge base</a> to learn more.', 'get-shortcode'), esc_url(s::brandUrl('/kb'))).'</p>',
        ['class' => '-display-block']
    ); ?>

        <?= $Form->hrRow(); ?>

        <?= $Form->textareaRow([
            'label' => __('Attribute Whitelist', 'get-shortcode'),
            'tip'   => __('This allows you to whitelist specific Attributes and even specific Attribute Values.', 'get-shortcode'),
            'note'  => __('One [get] Shortcode Attribute Name per line, following the syntax outlined above.', 'get-shortcode'),

            'name'  => 'whitelisted_atts',
            'value' => s::getOption('whitelisted_atts'),

            'attrs' => 'spellcheck="false"',
            'style' => 'height:calc(100vh - 200px); font-family:monospace; white-space:pre;',
        ]); ?>

    <?= $Form->closeTable(); ?>
    <?= $Form->submitButton(); ?>
<?= $Form->closeTag(); ?>
