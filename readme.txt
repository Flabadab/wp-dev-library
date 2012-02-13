=== WP-DEV-Library ===
Contributors: atlanticbt, zaus, tnblueswirl
Donate link: http://atlanticbt.com
Tags: core, extensions, common, reuse, library, mvc, model-view-controller, options, query builder
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: trunk
License: GPLv2 or later

Provides a foundation and extensions for/to common Wordpress developer functions/tasks - creating/installing plugins, complex database queries, etc

== Description ==

Provides a foundation and extensions for/to common Wordpress developer functions or tasks, such as creating and installing plugins, making complex database queries, etc.

= Includes - MVC =
* common helpful functions:
** v (if isset use value else default)
** kv (non-destructive version of v)
** h (htmlspecialchars)
** str (check if string)
** post/get (wrapper for $_GET, $_POST or default),
** server ($_SERVER wrapper)
** url (returns current url path, optionally parsed by segment)
** mvc_filepath (get include path to mvc file)
* error handling + debugging template
* html wrapper functions
* language functions (more like loading reusable labels)
* form + validation
* the mvc stack itself

= Includes - Etc =
* custom exception class - extend this for easier catching
* HtmlRenderer - not actually sure this is used anywhere, it allows for the plugin-pattern to render html
* inflector - for singularizing/pluralizing stuff
* Singleton - extend this for singleton/factor instance
* states_and_countries - gives an array of states and countries
* wp_options_page - wrapper object for creating option pages
* wp_page_builder - wrapper object for creating WP pages
* wp_pagination - wrapper object for creating "1 - XYZ..." pagination links
* wp_plugin_installer - wrapper object for handling plugin installation actions - (un)install, (de)activate, upgrade
* wp_plugin_shell - extend this when making a plugin to get access to commonly used stuff for plugins
* wp_querybuilder - use this chainable object to create mysql queries, built-in `prepare`
* impersonation - now you can see the site as though you were another user
* payment gateway shell - a place to start when integrating e-commerce

== Installation ==

1. Upload plugin folder `wp-library` to your plugins directory (`/wp-content/plugins/`)
2. Activate plugin (not even sure if this is necessary)
3. Reference anything from the library by including the relevant file

Alternatively, you could instead create another folder at the same level as your plugins directory, called `library`.  If you do so, you must explicitly include the "routing" file `wp-library-init.php` from somewhere (i.e. your theme `functions.php`).

So your structure could look something like:
    `wp-content`
    	- `plugins`
    	- `themes`
    	- `library`


== Frequently Asked Questions ==

= How do I include files? =

You have several options:
-  **`WP_LIBRARY_DIR`**: constant path to base plugin directory (where `wp-library-init.php` resides)
-  **`WP_LIBRARY_URL`**: same as *...DIR*, but public-facing URL path
-  **`wp_library_path($file)`**: function to get library path to given file (automatically prefixes with directory slash `/`)
-  **`wp_library_url($file)`**: same as *...path*, but public-facing url to file
-  **`wp_library_include($file, $isRequired = false, $isOnce = true)`**: function to include/require/"_once a library file, optionally require or do it once
-  **`wp_is_library($file)`**: check whether the requested library file exists

By default, the common functions (`includes/common-functions.php` and `mvc/common_micromvc.php`), are included.

There's also a really simple class `WP_Library_Base` that exposes "hook" wrappers `add_hook` and `add_hook_with`, which internally call `add_filter` using a class function of the same name as a callback, so you don't need to declare `array(&$this, 'callback_func')`.

= What's the difference between the `mvc` files and `includes` files? =

MVC files include the model-view-controller stack, as well as related helper files/functions.  These files/patterns have been adapted from Xeoncross's awesome [MicroMVC][] library.  You'll need to include at least the mvc-stack `wp_mvc.php`.

The other includes are standalone helper wrappers/classes, which you can include as needed.

[MicroMVC]: http://http://micromvc.com/ "MicroMVC - model-view-controller stack in PHP by Xeoncross"


= How do I use the Lang class for labels? =

Create a language file with the extension `.lang.php`.  It should contain a `$lang` array variable, to which you add the label key and value.

1. Include the language helper `mvc/lang.micromvc.php`
2. load your label file with
		`Lang::load($file, $module, $rel)`
3. then reference your label like:
		`sprintf( Lang::get('validation_required', $module), $field)`
which would return something like:
		The (VALUE_OF_$field) field is required and cannot be empty.

`$module` is the identifier used to refer to the language group - if omitted, will use the default lang file `/mvc/ui/.lang.php`.
`$rel` is used to get the language file relative to this path -- use `__FILE__` to get from the `current-dir/ui/...` folder, omit to use the default lang file.

= How do I do XYZ? =

Coming soon!  There's a lot of stuff to cover - check out the files themselves, they should be decently documented.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the directory of the stable readme.txt, so in this case, `/tags/4.3/screenshot-1.png` (or jpg, jpeg, gif)
2. This is the second screen shot

== Changelog ==

= 0.4 =
new functionality, various bugfixes, improvements

* inflector as Singleton
* slightly better plugin installer logging
* querybuilder: more methods (update, delete, select, insert), direct query, groupby
* minor formlib css/js fixes?
* post/get/request helper functions fix WP autoescaping
* form view allows P tag
* STAMP styling for radio input
* minor lang handling change
* form validation - difference between strict digits and numeric
* minor bugfixes and tweaks for wp_mvc implementation (stripslashes, request already merged, some db interaction wrapper changes, etc)
* _new:_ user impersonation class
* _new:_ payment gateway shell + authorize.net integration
* _new:_ time helpers
* _new:_ state/country list
* fixed includes to match folder restructuring
* split off debug functions; only included if debug mode is turned on
* trying a bunch of different plugin dependency checking (bootstrap-hook is the one that works, but also including plugin-dependencies plugin metadata directives)

= 0.3 =
* turned into a plugin
* not totally tested all files
 
= 0.2.1 =
* form validation
* lang (labels)

= 0.2 =
* mvc stub

= 0.1 =
* inception!
* shared stuff
 

== Upgrade Notice ==

= 0.3 =
Moved to plugin-style instead of manual include as standalone folder.  Not completely tested

== About AtlanticBT ==

From [About AtlanticBT][].

= Our Story =

> Atlantic Business Technologies, Inc. has been in existence since the relative infancy of the Internet.  Since March of 1998, Atlantic BT has become one of the largest and fastest growing web development companies in Raleigh, NC.  While our original business goal was to develop new software and systems for the medical and pharmaceutical industries, we quickly expanded into a business that provides fully customized, functional websites and Internet solutions to small, medium and larger national businesses.

> Our President, Jon Jordan, founded Atlantic BT on the philosophy that Internet solutions should be customized individually for each client’s specialized needs.  Today we have expanded his vision to provide unique custom solutions to a growing account base of more than 600 clients.  We offer end-to-end solutions for all clients including professional business website design, e-commerce and programming solutions, business grade web hosting, web strategy and all facets of internet marketing.

= Who We Are =

> The Atlantic BT Team is made up of friendly and knowledgeable professionals in every department who, with their own unique talents, share a wealth of industry experience.  Because of this, Atlantic BT always has a specialist on hand to address each client’s individual needs.  Due to the fact that the industry is constantly changing, all of our specialists continuously study the latest trends in all aspects of internet technology.   Thanks to our ongoing research in the web designing, programming, hosting and internet marketing fields, we are able to offer our clients the most recent and relevant ideas, suggestions and services.

[About AtlanticBT]: http://www.atlanticbt.com/company "The Company Atlantic BT"
