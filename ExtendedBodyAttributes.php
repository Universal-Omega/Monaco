<?php

$wgHooks['OutputPageBodyAttributes'][] = 'egExtendedOutputPageBodyAttributes';

/**
 * @param OutputPage $out
 * @param Skin $skin
 * @param array &$bodyAttrs
 */
function egExtendedOutputPageBodyAttributes( OutputPage $out, Skin $skin, &$bodyAttrs ) {
	if ( !$skin->getUser()->isRegistered() ) {
		$bodyAttrs['class'] .= ' loggedout';
	}

	if ( $out->getTitle()->isMainPage() ) {
		$bodyAttrs['class'] .= ' mainpage';
	}
}

