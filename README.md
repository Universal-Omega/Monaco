Monaco Skin for MediaWiki
=========================

About
-----

This is an unbranded fork of the Monaco skin originally developed by [Fandom](https://en.wikipedia.org/wiki/Fandom_(website)) (formerly known as Wikicities and Wikia).

Compared to the original version of the skin, this fork now supports MediaWiki
version 1.41+ officially.
This codebase will usually remain up-to-date against MediaWiki, and will drop
support for older versions unconditionally once it becomes impractical to
continue to support them.

Installation
------------

To install, install Monaco into a Monaco/ folder in your skins/ folder.
From the command line you can do this by cd'ing to your skins/ folder inside
your MediaWiki installation and running:

`git clone git://github.com/Universal-Omega/Monaco.git Monaco`

After you have placed the skin into that folder add:

`wfLoadSkin( 'Monaco' );`

near the end of your LocalSettings.php to finish installation of the skin.

Additionally you can install the Monaco/ContentRightSidebar extension using:

`wfLoadSkin( 'Monaco/ContentRightSidebar' );`

Doing so will provide you with a `<right-sidebar>...</right-sidebar>` tag which 
will create right floated content in the page that will be moved into the right
sidebar in monaco based skins. You can also use it with the args 

`<right-sidebar with-box="true" title="My Title">...</right-sidebar>`

to include that sidebar in a sidebar box. Note that a value is required for 
the `with-box` attribute when this extension is used with MediaWiki 1.25 or
later. For consistency, it is suggested that you provide this value anyway,
since it also works with earlier versions of MediaWiki.

License
-------
All of the code released by Fandom was made available under GPL v2.0 or later.
This license can be found in the LICENSE file.

Legacy
------
A fork [maintained by haleyjd](https://github.com/haleyjd/monaco-port) supports MediaWiki versions 1.24 to 1.29 officially, with verified support for 1.30+ in the works.

This is an unbranded fork of the Monaco skin originally developed by Wikia (now [Fandom](https://en.wikipedia.org/wiki/Fandom_(website))) which is being maintained for use at [DoomWiki.org](http://doomwiki.org/). It was also previously deployed at the Orain non-profit wiki farm before it went offline.

Version history
------

2.8.0 - May 11, 2024

- Themes activated
- add hook GetPreferences
- add UserOptionsLookup
- add i18n - Translations of the themes
- Compatibility with "Theme" extension


2.8.1 - Jul 21, 2024

Fix issue with Global Variables:
* MonacoSearchDefaultFulltext
* MonacoSpecialPagesRequiredLogin

Add i18n files


2.8.2 - Sep 29, 2024

Fix "Call to undefined method MonacoTemplate::getContext()" [Universal-Omega#33](https://github.com/Universal-Omega/Monaco/pull/33)

Replace "wfUrlProtocols()" with "wfUrlProtocolsWithoutProtRel()"

Substituted deprecated function "Skin::makeSpecialUrl()" into "SkinComponentUtils::makeSpecialUrl()"

Add i18n files


2.8.3 - Mar 4, 2025

- Fix - Nov 9, 2024: "Call to undefined method MonacoTemplate::getContext()" - [Universal-Omega#34](https://github.com/Universal-Omega/Monaco/pull/34)
- Fix - Jan 28, 2025: "Call to undefined method LanguageZh::getPreferredVariant()" - [Universal-Omega#35](https://github.com/Universal-Omega/Monaco/pull/35)
