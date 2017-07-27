## $v

- Now supports arbitrary custom attributes that map to a PHP function with a `get_` prefix; e.g., `[get my_function="arg"]` maps to PHP: `get_my_function(arg)` and the shortcode returns the value that your function returns. Note that this disable by default. So to get this working you must enable the functionality explicitly in the plugin options. You must also add your functions to the attribute whitelist in the plugin options, assuming that you have a whitelist at all (which is highly recommended before using this feature).

- Now setting `$GLOBALS['current_get_shortcode_atts']` before calling upon arbitrary custom attribute handlers. This allows for global functions to adjust their output based on any custom attributes that developers would like to support arbitrarily.

## v170329.48046

- Adding support for `user="gravatar"`.
- Adding support for `user="avatar_url"`.
- Adding support for `user="gravatar_profile_url"`.
- Adding `_size=""` attribute for user avatars.

## v170311.42814

- Enhancing security.
- Adding options page.
- Adding ability to whitelist atts/values.
- Adding many new attribute/value possibilities.
- Refactoring source code to make it more robust.

## v170308.52831

- Initial release.
