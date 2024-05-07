<?php

use MediaWiki\Hook\OutputPageBodyAttributesHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\User\UserOptionsLookup;

class MonacoHooks implements
	GetPreferencesHook,
	OutputPageBodyAttributesHook
{

	private UserOptionsLookup $userOptionsLookup;
	private bool $allowedThemes;
	private string $defaultTheme;

	/**
	 * @param GlobalVarConfig $config
	 * @param UserOptionsLookup $userOptionsLookup
	 */
	public function __construct(
		GlobalVarConfig $config,
		UserOptionsLookup $userOptionsLookup
	) {
		$this->userOptionsLookup = $userOptionsLookup;
		$this->allowedThemes = $config->get( "MonacoAllowUseTheme" );
		$this->defaultTheme = $config->get( "MonacoTheme" );
	}

	/**
	 * Add the theme selector to user preferences.
	 *
	 * @param User $user
	 * @param array &$defaultPreferences
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onGetPreferences( $user, &$preferences ) {

		$ctx = RequestContext::getMain();
		$skin = $ctx->getSkin();
		$skinName = $skin->getSkinName();
		$themes = SkinMonaco::getSkinMonacoThemeList();
		$theme_key = SkinMonaco::getThemeKey();

		// Braindead code needed to make the theme *names* show up
		// Without this they show up as "0", "1", etc. in the UI
		// First themes will be translated in i18n and then sorted.

		// Get translated theme names
		$themeArray_for_sort = [];
		foreach ( $themes as $theme ) {
			$themeDisplayNameMsg = $ctx->msg( "theme-name-$skinName-$theme" );
			if ( $themeDisplayNameMsg->isDisabled() ) {
				// No i18n available for this -> use the key as-is
				$themeDisplayName = ucfirst( $theme );
			} else {
				// Use i18n; it's much nicer to display formatted theme names if and when
				// a theme name contains spaces, uppercase characters, etc.
				$themeDisplayName = $themeDisplayNameMsg->text();
			}
			$themeArray_for_sort[$theme] = $themeDisplayName;
		}

		// Sort this list and and a default element.
		asort( $themeArray_for_sort );
		$theme = 'default';
		$themeDisplayNameMsg = $ctx->msg( "theme-name-$skinName-$theme" );
		$themeDisplayName = 
			$themeDisplayNameMsg->isDisabled()
			? $theme
			: $themeDisplayName = $themeDisplayNameMsg->text();
		// Ensure that 'default' is always the 1st array item
		$themeArray = [ $themeDisplayName => $theme ];

		// Add the rest of the items.
		foreach ( $themeArray_for_sort as $theme => $themeDisplayName ) {
			$themeArray[$themeDisplayName] = $theme;
		}

		$usersTheme = $this->userOptionsLookup->getOption( $user, $theme_key, $this->defaultTheme );
		$showIf = [ '!==', 'skin', 'monaco' ];

		// The entry 'theme' conflicts with Extension:Theme.
		$preferences[$theme_key] = $this->allowedThemes
			?	[
					'type' => 'select',
					'options' => $themeArray,
					'default' => $usersTheme,
					'label-message' => 'monaco-theme-prefs-label',
					'section' => 'rendering/skin',
					'hide-if' => $showIf
				]
			:	// If the selection of themes is deactiveted,
				// show only an informative message instead
				[
					'type' => 'info',
					'label-message' => 'monaco-theme-prefs-label',
					'default' => $ctx->msg( 'theme-selection-deactivated' )->text(),
					'section' => 'rendering/skin',
					'hide-if' => $showIf
				];
	}

	/**
	 * @param OutputPage $out OutputPage which called the hook, can be used to get the real title
	 * @param Skin $skin Skin that called OutputPage::headElement
	 * @param string[] &$bodyAttrs Array of attributes for the body tag passed to Html::openElement
	 */
	public function onOutputPageBodyAttributes( $out, $skin, &$bodyAttrs ): void {
		if ( $skin->getSkinName() !== 'monaco' ) {
			return;
		}

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
