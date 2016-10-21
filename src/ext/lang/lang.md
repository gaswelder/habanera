# The `lang` extension

	load_ext('lang');

The `lang` extension is a simpler alternative to `gettext`. It may be
chosen for smaller sites where setting up `gettext` wouldn't give much
advantage or on hostings where `gettext` isn't available or doesn't
work properly.

The functions that accept a `lang` argument expect it to be in the HTTP
`accept-language` format, which is:

	1*8ALPHA *( "-" 1*8ALPHA)

Examples are `en`, `en-GB`, `my-funky-dialect`.

Translation file for language `lang` is expected to be at `<appdir>/lang/<lowecase(lang)>`. That is, the translation file for language `en-GB` would be `<appdir>/lang/en-gb`.

The file format is plain text with a repeated sequence of lines:
(message line, translation line, empty line). An example would be:

	Hello
	Hey man

	How do you do?
	Wassup?

	Popular items
	Hot stuff

Translation files are loaded on demand.

The `lang` configuration parameter may be set in application INI files
to set the default language as alternative to calling `lang::set` from
the script.

* `t($msg, $lang=null)`, `lang::lookup($msg, $lang=null)`

	`t` is an alias of `lang::lookup`. The function returns the
	translation for `msg`, if there is one, or `msg` itself if there
	isn't.  If `lang` is omitted, the default language is assumed.

* `lang::have($lang)`

	Returns `true` if there is a translation file for language `lang`
	and `false` otherwise.

* `lang::set($lang)`

	Sets default language for lookups to `lang`. This overrides the
	`lang` parameter from INI files.

* `lang::get()`

	Returns the default language name used for lookups.
