Monaco Skin for MediaWiki
=========================

About
-----

This is an other unbranded fork of the Monaco skin originally developed by Wikia
which is being maintained for use for my Private Wiki. It was
also previously deployed at the Orain non-profit wiki farm before it went 
offline.

Compared to the original version of the skin, this fork now supports MediaWiki
version 1.31 officially.
This codebase will usually remain up-to-date against MediaWiki, and will drop
support for older versions unconditionally once it becomes impractical to
continue to support them.

New features in this fork over `haleyjd/monaco-port` include:  

* First Version that support MediaWiki verison 1.31, but not tested
* Implementation of extending the Sidebar Menu with User and Group Sidebar Elements
  like Extension "DynamicSidebar"

This fork is maintained by Roger Meier. I do not offer support for this
software beyond basic installation help, however. Please do not contact
me requesting any customizations for your particular site. Bug reports
are however very much welcome, as are generic feature requests that 
would be useful to everyone that might use it.

Installation
------------

To install, install monaco-port into a monaco/ folder in your skins/ folder.
From the command line you can do this by cd'ing to your skins/ folder inside
your MediaWiki installation and running:

`git clone git://github.com/beleggrodion/monaco-port.git monaco`

After you have placed the skin into that folder add:

`wfLoadSkin( 'Monaco' );`

near the end of your LocalSettings.php to finish installation of the skin.

You can also include the ExtendedBodyAttributes.php code if you wish to
re-introduce the mainpage and loggedout classes that were in Wikia's version of
Monaco, doing this will actually make these css classes available globally to
all skins that are programmed using the MediaWiki 1.16 headElement code.

Additionally you can install the ContentRightSidebar extension using:

`require_once("$IP/skins/monaco/ContentRightSiebar.php");`

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
All of the code released by Wikia was made available under GPL v2.0 or later.
This license can be found in the COPYING file.
