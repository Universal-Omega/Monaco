<?php

if ( function_exists( 'wfLoadSkin' ) ) {
	wfLoadSkin( 'Monaco' );

	$wgMessagesDirs['Monaco'] = __DIR__ . '/i18n';

	wfWarn(
		'Deprecated PHP entry point used for Monaco skin. Please use wfLoadSkin instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);

	return;
} else {
	die( 'This version of the Monaco skin requires MediaWiki 1.35+' );
}
