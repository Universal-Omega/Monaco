<?php

class MonacoHooks {
	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @param array &$bodyAttrs
	 */
	public static function onOutputPageBodyAttributes( OutputPage $out, Skin $skin, &$bodyAttrs ) {
		$bodyAttrs['class'] .= ' color2';
		
		$action = $skin->getRequest()->getVal( 'action' );
		if ( in_array( $action, [ 'edit', 'history', 'diff', 'delete', 'protect', 'unprotect', 'submit' ] ) ) {
			$bodyAttrs['class'] .= ' action_' . $action;
		} elseif ( empty( $action ) || in_array( $action, [ 'view', 'purge' ] ) ) {
			$bodyAttrs['class'] .= ' action_view';
		}
		
		if ( $skin->showMasthead() ) {
			if ( $skin->isMastheadTitleVisible() ) {
			$bodyAttrs['class'] .= ' masthead-special';
			} else {
				$bodyAttrs['class'] .= ' masthead-regular';
			}
		}
		
		$bodyAttrs['id'] = 'body';

		if ( !$skin->getUser()->isRegistered() ) {
			$bodyAttrs['class'] .= ' loggedout';
		}

		if ( $out->getTitle()->isMainPage() ) {
			$bodyAttrs['class'] .= ' mainpage';
		}
	}
}

