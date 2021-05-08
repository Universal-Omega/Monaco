<?php
/**
 * Monaco skin
 *
 * @package MediaWiki
 * @subpackage Skins
 *
 * @author Inez Korczynski <inez@wikia.com>
 * @author Christian Williams
 * @author Daniel Friesen
 * @author James Haley
 */

use MediaWiki\MediaWikiServices;

class SkinMonaco extends SkinTemplate {

	/**
	 * Overwrite few SkinTemplate methods which we don't need in Monaco
	 */
	function buildSidebar() {}
	function getCopyrightIcon() {}
	function getPoweredBy() {}
	function disclaimerLink() {}
	function privacyLink() {}
	function aboutLink() {}
	function getHostedBy() {}
	function diggsLink() {}
	function deliciousLink() {}

    /**
     * @var Config
     */
    private $monacoConfig;
    
	/** Using monaco. */
	var $skinname = 'monaco', $stylename = 'monaco',
		$template = 'MonacoTemplate', $useHeadElement = true;


	public function __construct() {
		$this->monacoConfig = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'monaco' );
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	public function initPage( OutputPage $out ) {
		global $wgHooks, $wgJsMimeType;

		SkinTemplate::initPage( $out );

		// Function addVariables will be called to populate all needed data to render skin
		$wgHooks['SkinTemplateOutputPageBeforeExec'][] = [ &$this, 'addVariables' ];

		// Load the bulk of our scripts with the MediaWiki 1.17+ resource loader
		$out->addModuleScripts( 'skins.monaco' );
		
		$out->addScript(
			'<!--[if IE]><script type="' . htmlspecialchars( $wgJsMimeType ) .
				'">\'abbr article aside audio canvas details figcaption figure ' .
				'footer header hgroup mark menu meter nav output progress section ' .
				'summary time video\'' .
				'.replace(/\w+/g,function(n){document.createElement(n)})</script><![endif]-->'
		);
	}

	/**
	 * Add specific styles for this skin
	 *
	 * Don't add common/shared.css as it's kept in allinone.css
	 *
	 * @param $out OutputPage
	 */
	public function setupSkinUserCss( OutputPage $out ) {
		parent::setupSkinUserCss( $out );
		
		// Load the bulk of our styles with the MediaWiki 1.17+ resource loader
		$out->addModuleStyles( [ 'skins.monaco', 'mediawiki.skinning.content', 'mediawiki.skinning.content.externallinks' ] );
		
		// ResourceLoader doesn't do ie specific styles that well iirc, so we have
		// to do those manually.
		$out->addStyle( 'Monaco/style/css/monaco_ie8.css', 'screen', 'IE 8' );
		$out->addStyle( 'Monaco/style/css/monaco_gteie8.css', 'screen', 'gte IE 8');
		
		// Likewise the masthead is a conditional feature so it's hard to include
		// inside of the ResourceLoader.
		if ( $this->showMasthead() ) {
			$out->addStyle( 'Monaco/style/css/masthead.css', 'screen' );
		}
		
		$theme = $this->monacoConfig->get( 'MonacoTheme' );
        
		if ( $this->monacoConfig->get( 'MonacoAllowusetheme' ) ) {
			$theme = $this->getRequest()->getText( 'usetheme', $theme );
			if ( preg_match( '/[^a-z]/', $theme ) ) {
				$theme = $this->monacoConfig->get( 'MonacoTheme' );
			}
		}

		if ( preg_match( '/[^a-z]/', $theme ) ) {
			$theme = 'sapphire';
		}
		
		// Theme is another conditional feature, we can't really resource load this
		if ( isset( $theme ) && is_string( $theme ) && $theme != 'sapphire' ) {
			$out->addStyle( "Monaco/style/{$theme}/css/main.css", 'screen' );
		}
		
		// TODO: explicit RTL style sheets are supposed to be obsolete w/ResourceLoader
		// I have no way to test this currently, however. -haleyjd
		// rtl... hmm, how do we resource load this?
		$out->addStyle( 'Monaco/style/rtl.css', 'screen', '', 'rtl' );
	}

	public function showMasthead() {
		if ( !$this->monacoConfig->get( 'MonacoUseMasthead' ) ) {
			return false;
		}

		return !!$this->getMastheadUser();
	}
	
	public function getMastheadUser() {
		$title = $this->getTitle();

		if ( !isset( $this->mMastheadUser ) ) {
			$ns = $title->getNamespace();
			if ( $ns == NS_USER || $ns == NS_USER_TALK ) {
				$this->mMastheadUser = User::newFromName( strtok( $title->getText(), '/' ), false );
				$this->mMastheadTitleVisible = false;
			} else {
				$this->mMastheadUser = false;
				$this->mMastheadTitleVisible = true; // title is visible anyways if we're not on a masthead using page
			}
		}

		return $this->mMastheadUser;
	}
	
	public function isMastheadTitleVisible() {
		if ( !$this->showMasthead() ) {
			return true;
		}

		$this->getMastheadUser();

		return $this->mMastheadTitleVisible;
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 * @author Christian Williams
 	 * @author Daniel Friesen <http://daniel.friesen.name/>
	 * Added this functionality to MediaWiki, may need to add a patch to MW 1.16 and older
	 * This allows the skin to add body attributes while still integrating with
	 * MediaWiki's new headelement code. I modified the original Monaco code to
	 * use this cleaner method. I did not port loggedout or mainpage as these are
	 * generic, I added a separate hook so that a generic extension can be made
	 * to add those universally to all new skins.
	 */
	public function addToBodyAttributes( $out, &$bodyAttrs ) {
		$bodyAttrs['class'] .= ' color2';
		
		$action = $this->getRequest()->getVal( 'action' );
		if ( in_array( $action, [ 'edit', 'history', 'diff', 'delete', 'protect', 'unprotect', 'submit' ] ) ) {
			$bodyAttrs['class'] .= ' action_' . $action;
		} elseif ( empty( $action ) || in_array( $action, [ 'view', 'purge' ] ) ) {
			$bodyAttrs['class'] .= ' action_view';
		}
		
		if ( $this->showMasthead() ) {
			if ( $this->isMastheadTitleVisible() ) {
				$bodyAttrs['class'] .= ' masthead-special';
			} else {
				$bodyAttrs['class'] .= ' masthead-regular';
			}
		}
		
		$bodyAttrs['id'] = 'body';
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	public function addVariables( &$obj, &$tpl ) {
		global $wgLang, $wgContLang, $parserMemc;

		$user = $this->getUser();

		// We want to cache populated data only if user language is same with wiki language
		$cache = $wgLang->getCode() == $wgContLang->getCode();

		if ( $cache ) {
			$key = wfMemcKey( 'MonacoDataOld' );
			$data_array = $parserMemc->get( $key );
		}

		if ( empty( $data_array ) ) {
			$data_array['toolboxlinks'] = $this->getToolboxLinks();

			if ( $cache ) {
				$parserMemc->set( $key, $data_array, 4 * 60 * 60 /* 4 hours */ );
			}
		}

		if ( $user->isRegistered() ) {
			if ( empty( $user->mMonacoData ) || ( $this->getTitle()->getNamespace() == NS_USER && $this->getRequest()->getText( 'action' ) == 'delete' ) ) {
				$user->mMonacoData = [];

				$text = $this->getTransformedArticle( 'User:' . $user->getName() . '/Monaco-toolbox', true );
				if ( empty( $text ) ) {
					$user->mMonacoData['toolboxlinks'] = false;
				} else {
					$user->mMonacoData['toolboxlinks'] = $this->parseToolboxLinks( $text );
				}
			}

			if ( $user->mMonacoData['toolboxlinks'] !== false && is_array( $user->mMonacoData['toolboxlinks'] ) ) {
				$data_array['toolboxlinks'] = $user->mMonacoData['toolboxlinks'];
			}
		}

		foreach ( $data_array['toolboxlinks'] as $key => $val ) {
			if ( isset( $val['org'] ) && $val['org'] == 'whatlinkshere' ) {
				if ( isset( $tpl->data['nav_urls']['whatlinkshere'] ) ) {
					$data_array['toolboxlinks'][$key]['href'] = $tpl->data['nav_urls']['whatlinkshere']['href'];
				} else {
					unset( $data_array['toolboxlinks'][$key] );
				}
			}

			if ( isset( $val['org'] ) && $val['org'] == 'permalink' ) {
				if ( isset( $tpl->data['nav_urls']['permalink'] ) ) {
					$data_array['toolboxlinks'][$key]['href'] = $tpl->data['nav_urls']['permalink']['href'];
				} else {
					unset( $data_array['toolboxlinks'][$key] );
				}
			}
		}

		$tpl->set( 'data', $data_array );

		// Article content links (View, Edit, Delete, Move, etc.)
		$tpl->set( 'articlelinks', $this->getArticleLinks( $tpl ) );

		// User actions links
		$tpl->set( 'userlinks', $this->getUserLinks( $tpl ) );

		return true;
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function parseToolboxLinks( $lines ) {
		$nodes = [];
		if ( is_array( $lines ) ) {
			foreach ( $lines as $line ) {
				$trimmed = trim($line, ' *');
				if ( strlen( $trimmed ) == 0 ) { # ignore empty lines
					continue;
				}

				$item = MonacoSidebar::parseItem($trimmed);

				$nodes[] = $item;
			}
		}

		return $nodes;
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getLines( $message_key ) {
		$revision = Revision::newFromTitle( Title::newFromText( $message_key, NS_MEDIAWIKI ) );
		if ( is_object( $revision ) ) {
			if ( trim( $revision->getText() ) != '' ) {
				$temp = MonacoSidebar::getMessageAsArray( $message_key );
				if ( count( $temp ) > 0 ) {
					$lines = $temp;
				}
			}
		}

		if ( empty($lines ) ) {
			$lines = MonacoSidebar::getMessageAsArray( $message_key );
		}

		return $lines;
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getToolboxLinks() {
		return $this->parseToolboxLinks($this->getLines('Monaco-toolbox'));
	}

	var $lastExtraIndex = 1000;

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function addExtraItemsToSidebarMenu( &$node, &$nodes ) {
		$extraWords = [
			'#voted#' => [ 'highest_ratings', 'GetTopVotedArticles' ],
			'#popular#' => [ 'most_popular', 'GetMostPopularArticles' ],
			'#visited#' => [ 'most_visited', 'GetMostVisitedArticles' ],
			'#newlychanged#' => [ 'newly_changed', 'GetNewlyChangedArticles' ],
			'#topusers#' => [ 'community', 'GetTopFiveUsers' ]
		];

		if ( isset( $extraWords[ strtolower( $node['org'] ) ] ) ) {
			if ( substr( $node['org'],0,1 ) == '#' ) {
				if ( strtolower( $node['org'] ) == strtolower( $node['text'] ) ) {
					$node['text'] = wfMessage( trim( strtolower( $node['org'] ), ' *' ) )->text();
				}

				$node['magic'] = true;
			}

			$results = DataProvider::$extraWords[strtolower($node['org'])][1]();
			$results[] = [ 'url' => SpecialPage::getTitleFor( 'Top/'.$extraWords[ strtolower( $node['org'] ) ][0] )->getLocalURL(), 'text' => strtolower( wfMessage( 'moredotdotdot' )->text() ), 'class' => 'Monaco-sidebar_more' ];

			if ( $this->getUser()->isAllowed( 'editinterface' ) ) {
				if ( strtolower( $node['org'] ) == '#popular#' ) {
					$results[] = [ 'url' => Title::makeTitle( NS_MEDIAWIKI, 'Most popular articles' )->getLocalUrl(), 'text' => wfMessage( 'monaco-edit-this-menu' )->text(), 'class' => 'Monaco-sidebar_edit' ];
				}
			}

			foreach ( $results as $key => $val ) {
				$node['children'][] = $this->lastExtraIndex;
				$nodes[$this->lastExtraIndex]['text'] = $val['text'];
				$nodes[$this->lastExtraIndex]['href'] = $val['url'];

				if ( !empty( $val['class'] ) ) {
					$nodes[$this->lastExtraIndex]['class'] = $val['class'];
				}

				$this->lastExtraIndex++;
			}
		}
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function parseSidebarMenu( $lines ) {
		$nodes = [];
		$nodes[] = [];
		$lastDepth = 0;
		$i = 0;

		if ( is_array( $lines ) ) {
			foreach ( $lines as $line ) {
				if ( strlen( $line ) == 0 ) { # ignore empty lines
					continue;
				}

				$node = MonacoSidebar::parseItem( $line );
				$node['depth'] = strrpos( $line, '*' ) + 1;

				if ( $node['depth'] == $lastDepth ) {
					$node['parentIndex'] = $nodes[$i]['parentIndex'];
				} elseif ( $node['depth'] == $lastDepth + 1 ) {
					$node['parentIndex'] = $i;
				} else {
					for ( $x = $i; $x >= 0; $x-- ) {
						if ( $x == 0 ) {
							$node['parentIndex'] = 0;
							break;
						}

						if ( $nodes[$x]['depth'] == $node['depth'] - 1 ) {
							$node['parentIndex'] = $x;
							break;
						}
					}
				}

				if ( substr( $node['org'],0,1 ) == '#' ) {
					$this->addExtraItemsToSidebarMenu( $node, $nodes );
				}

				$nodes[$i+1] = $node;
				$nodes[ $node['parentIndex'] ]['children'][] = $i+1;
				$lastDepth = $node['depth'];
				$i++;
			}
		}

		return $nodes;
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getSidebarLinks() {
		return $this->parseSidebarMenu( $this->getLines( 'Monaco-sidebar' ) );
	}

	/**
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getTransformedArticle( $name, $asArray = false ) {
		global $wgParser, $wgMessageCache;

		$revision = Revision::newFromTitle( Title::newFromText( $name ) );

		if ( is_object( $revision ) ) {
			$text = $revision->getText();

			if ( !empty( $text ) ) {
				$ret = $wgParser->transformMsg( $text, $wgMessageCache->getParserOptions() );

				if ( $asArray ) {
					$ret = explode( "\n", $ret );
				}

				return $ret;
			}
		}

		return null;
	}

	/**
	 * Create arrays containing articles links (separated arrays for left and right part)
	 * Based on data['content_actions']
	 *
	 * @return array
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getArticleLinks( $tpl ) {
		$links = [];

		if ( isset( $tpl->data['content_navigation'] ) ) {
			// Use MediaWiki 1.18's better vector based content_navigation structure
			// to organize our tabs better
			foreach ( $tpl->data['content_navigation'] as $section => $nav ) {
				foreach ( $nav as $key => $val ) {
					if ( isset( $val['redundant'] ) && $val['redundant'] ) {
						continue;
					}
					
					$kk = ( isset( $val['id'] ) && substr( $val['id'], 0, 3 ) == 'ca-' ) ? substr( $val['id'], 3 ) : $key;
					
					$msgKey = $kk;
					if ( $kk == 'edit' ) {
						$title = $this->getRelevantTitle();
						$msgKey = $title->exists() || ( $title->getNamespace() == NS_MEDIAWIKI && !wfMessage( $title->getText() )->inContentLanguage()->isBlank() )
							? 'edit' : 'create';
					}
					
					// @note We know we're in 1.18 so we don't need to pass the second param to wfEmptyMsg anymore
					$tabText = wfMessage( "monaco-tab-{$msgKey}" )->text();
					if ( $tabText && $tabText != '-' && wfMessage( "monaco-tab-{$msgKey}" )->exists() ) {
						$val['text'] = $tabText;
					}
					
					switch ( $section ) {
						case 'namespaces':
							$side = 'right';
							break;
						case 'variants':
							$side = 'variants';
							break;
						default:
							$side = 'left';
							break;
					}

					$links[$side][$kk] = $val;
				}
			}
		} else {
			// rarely ever happens, but it does
			if ( empty( $tpl->data['content_actions'] ) ) {
				return $links;
			}

			# @todo: might actually be useful to move this to a global var and handle this in extension files --TOR
			$force_right = [ 'userprofile', 'talk', 'TheoryTab' ];
			foreach ( $tpl->data['content_actions'] as $key => $val ) {
				$msgKey = $key;
				if ( $key == 'edit' ) {
					$msgKey = $this->mTitle->exists() || ( $this->mTitle->getNamespace() == NS_MEDIAWIKI && wfMessage( $this->mTitle->getText() )->exists() )
						? 'edit' : 'create';
				}

				$tabText = wfMessage( "monaco-tab-{$msgKey}" )->text();
				if ( $tabText && $tabText != '-' && wfMessage( "monaco-tab-{$msgKey}" )->exists() ) {
					$val['text'] = $tabText;
				}

				if ( strpos( $key, 'varlang-' ) === 0 ) {
					$links['variants'][$key] = $val;
				} elseif ( strpos( $key, 'nstab-' ) === 0 || in_array( $key, $force_right ) ) {
					$links['right'][$key] = $val;
				} else {
					$links['left'][$key] = $val;
				}
			}
		}

		if ( isset( $links['left'] ) ) {
			foreach ( $links['left'] as $key => &$v ) {
				/* Fix icons */
				if ( $key == 'unprotect' ) {
					// unprotect uses the same icon as protect
					$v['icon'] = 'protect';
				} elseif ( $key == 'undelete' ) {
					// undelete uses the same icon as delelte
					$v['icon'] = 'delete';
				} elseif ( $key == 'purge' ) {
					$v['icon'] = 'refresh';
				} elseif ( $key == 'addsection' ) {
					$v['icon'] = 'talk';
				}
			}
		}

		return $links;
	}

	/**
	 * Generate links for user menu - depends on if user is logged in or not
	 *
	 * @return array
	 * @author Inez Korczynski <inez@wikia.com>
	 */
	private function getUserLinks( $tpl ) {
		$data = [];
		$request = $this->getRequest();
		$user = $this->getUser();

		$page = Title::newFromURL( $request->getVal( 'title', '' ) );
		$page = $request->getVal( 'returnto', $page );
		$a = [];

		if ( strval( $page ) !== '' ) {
			$a['returnto'] = $page;
			$query = $request->getVal( 'returntoquery', $this->thisquery );
			if( $query != '' ) {
				$a['returntoquery'] = $query;
			}
		}
		$returnto = wfArrayToCGI( $a );

		if ( !$user->isRegistered() ) {
			$signUpHref = Skin::makeSpecialUrl( 'UserLogin', $returnto );
			$data['login'] = [
				'text' => wfMessage( 'login' )->text(),
				'href' => $signUpHref . '&type=login'
			];

			$data['register'] = [
				'text' => wfMessage( 'pt-createaccount' )->text(),
				'href' => $signUpHref . '&type=signup'
			];

		} else {
			$data['userpage'] = [
				'text' => $user->getName(),
				'href' => $tpl->data['personal_urls']['userpage']['href']
			];

			$data['mytalk'] = [
				'text' => $tpl->data['personal_urls']['mytalk']['text'],
				'href' => $tpl->data['personal_urls']['mytalk']['href']
			];

			if ( isset( $tpl->data['personal_urls']['watchlist'] ) ) {
				$data['watchlist'] = [
					/*'text' => $tpl->data['personal_urls']['watchlist']['text'],*/
					'text' => wfMessage( 'prefs-watchlist' )->text(),
					'href' => $tpl->data['personal_urls']['watchlist']['href']
				];
			}

			// In some cases, logout will be removed explicitly (such as when it is replaced by fblogout).
			if ( isset( $tpl->data['personal_urls']['logout'] ) ) {
				$data['logout'] = [
					'text' => $tpl->data['personal_urls']['logout']['text'],
					'href' => $tpl->data['personal_urls']['logout']['href']
				];
			}


			$data['more']['userpage'] = [
				'text' => wfMessage( 'mypage' )->text(),
				'href' => $tpl->data['personal_urls']['userpage']['href']
			];

			if ( isset ( $tpl->data['personal_urls']['userprofile'] ) ) {
				$data['more']['userprofile'] = [
					'text' => $tpl->data['personal_urls']['userprofile']['text'],
					'href' => $tpl->data['personal_urls']['userprofile']['href']
				];
			}

			$data['more']['mycontris'] = [
				'text' => wfMessage( 'mycontris' )->text(),
				'href' => $tpl->data['personal_urls']['mycontris']['href']
			];

			$data['more']['preferences'] = [
				'text' => $tpl->data['personal_urls']['preferences']['text'],
				'href' => $tpl->data['personal_urls']['preferences']['href']
			];
		}

		// This function ignores anything from PersonalUrls hook which it doesn't expect.  This
		// loops lets it expect anything starting with "fb*" (because we need that for facebook connect).
		// Perhaps we should have some system to let PersonalUrls hook work again on its own?
		// - Sean Colombo
		
		foreach ( $tpl->data['personal_urls'] as $urlName => $urlData ) {
			if ( strpos( $urlName, 'fb' ) === 0 ) {
				$data[$urlName] = $urlData;
			}
		}

		return $data;
	}
}
