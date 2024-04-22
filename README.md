Monaco Skin for MediaWiki
=========================

About
-----

This is an unbranded fork of the Monaco skin originally developed by Fandom.
offline.

Compared to the original version of the skin, this fork now supports MediaWiki
version 1.39+ officially.
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
