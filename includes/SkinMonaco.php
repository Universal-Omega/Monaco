<?php

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
	public function __construct( array $options = [] ) {
		$this->monacoConfig = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'monaco' );

		if ( version_compare( MW_VERSION, '1.36', '<' ) ) {
			// Associate template - this is replaced by `template` option in 1.36
			$this->template = MonacoTemplate::class;
		}

		parent::__construct( $options );
	}

	/**
	 * @param OutputPage $out
	 */
	public function initPage( OutputPage $out ) {
		parent::initPage( $out );

		// ResourceLoader doesn't do ie specific styles that well iirc, so we have
		// to do those manually.
		$out->addStyle( 'Monaco/style/css/monaco_ie8.css', 'screen', 'IE 8' );
		$out->addStyle( 'Monaco/style/css/monaco_gteie8.css', 'screen', 'gte IE 8');
		$out->addStyle( 'Monaco/style/css/monobook_modified.css', 'screen' );
		$out->addStyle( 'Monaco/style/css/reset_modified.css', 'screen' );
		$out->addStyle( 'Monaco/style/css/buttons.css', 'screen' );
		$out->addStyle( 'Monaco/style/css/root.css', 'screen' );
		$out->addStyle( 'Monaco/style/css/header.css', 'screen' );
		$out->addStyle( 'Monaco/style/css/article.css', 'screen' );
		$out->addStyle( 'Monaco/style/css/widgets.css', 'screen' );
		$out->addStyle( 'Monaco/style/css/modal.css', 'screen' );
		$out->addStyle( 'Monaco/style/css/footer.css', 'screen' );
		$out->addStyle( 'Monaco/style/css/star_rating.css', 'screen' );
		$out->addStyle( 'Monaco/style/css/ny.css', 'screen' );
		$out->addStyle( 'Monaco/style/css/print.css', 'print' );
		$out->addScript( 'Monaco/style/js/monaco.js' );
		
		// Likewise the masthead is a conditional feature so it's hard to include
		// inside of the ResourceLoader.
		if ( $this->showMasthead() ) {
			$out->addStyle( 'Monaco/style/css/masthead.css', 'screen' );
		}
		
		$theme = $this->monacoConfig->get( 'MonacoTheme' );
        
		if ( $this->monacoConfig->get( 'MonacoAllowUseTheme' ) ) {
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

		
		$out->addScript(
			'<!--[if IE]><script type="text/javascript' .
				'">\'abbr article aside audio canvas details figcaption figure ' .
				'footer header hgroup mark menu meter nav output progress section ' .
				'summary time video\'' .
				'.replace(/\w+/g,function(n){document.createElement(n)})</script><![endif]-->'
		);
	}

	/**
	 * @return array
	 */
	public function getDefaultModules() {
		$modules = parent::getDefaultModules();

		return $modules;
	}

	/**
	 * @return bool
	 */
	public function showMasthead() {
		if ( !$this->monacoConfig->get( 'MonacoUseMasthead' ) ) {
			return false;
		}

		return (bool)$this->getMastheadUser();
	}

	/**
	 * @return User
	 */
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

	/**
	 * @return bool
	 */
	public function isMastheadTitleVisible() {
		if ( !$this->showMasthead() ) {
			return true;
		}

		$this->getMastheadUser();

		return $this->mMastheadTitleVisible;
	}

	/**
	 * @param array $lines
	 * @return array
	 */
	public function parseToolboxLinks( $lines ) {
		$nodes = [];
		if ( is_array( $lines ) ) {
			foreach ( $lines as $line ) {
				$trimmed = trim( $line, ' *' );
				if ( strlen( $trimmed ) == 0 ) { # ignore empty lines
					continue;
				}

				$item = MonacoSidebar::parseItem( $trimmed );

				$nodes[] = $item;
			}
		}

		return $nodes;
	}

	/**
	 * @param string $message_key
	 * @return array
	 */
	public function getLines( $message_key ) {
		$revisionStore = MediaWikiServices::getInstance()->getRevisionStore();
		$revision = $revisionStore->getRevisionByTitle( Title::newFromText( $message_key, NS_MEDIAWIKI ) );
		if ( is_object( $revision ) ) {
			if ( trim( $revision->getText() ) != '' ) {
				$temp = MonacoSidebar::getMessageAsArray( $message_key );
				if ( count( $temp ) > 0 ) {
					$lines = $temp;
				}
			}
		}

		if ( empty( $lines ) ) {
			$lines = MonacoSidebar::getMessageAsArray( $message_key );
		}

		return $lines;
	}

	/**
	 * @return array
	 */
	public function getToolboxLinks() {
		return $this->parseToolboxLinks( $this->getLines( 'Monaco-toolbox' ) );
	}

	var $lastExtraIndex = 1000;

	/**
	 * @param array &$node
	 * @param array &$nodes
	 */
	public function addExtraItemsToSidebarMenu( &$node, &$nodes ) {
		$extraWords = [
			'#voted#' => [ 'highest_ratings', 'GetTopVotedArticles' ],
			'#popular#' => [ 'most_popular', 'GetMostPopularArticles' ],
			'#visited#' => [ 'most_visited', 'GetMostVisitedArticles' ],
			'#newlychanged#' => [ 'newly_changed', 'GetNewlyChangedArticles' ],
			'#topusers#' => [ 'community', 'GetTopFiveUsers' ]
		];

		if ( isset( $extraWords[ strtolower( $node['org'] ) ] ) ) {
			if ( substr( $node['org'], 0, 1 ) == '#' ) {
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
	 * @param array $lines
	 * @return array
	 */
	public function parseSidebarMenu( $lines ) {
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
	 * @return array
	 */
	public function getSidebarLinks() {
		return $this->parseSidebarMenu( $this->getLines( 'Monaco-sidebar' ) );
	}

	/**
	 * @param string $name
	 * @param bool $asArray|false
	 * @return array|string|null
	 */
	public function getTransformedArticle( $name, $asArray = false ) {
		$revisionStore = MediaWikiServices::getInstance()->getRevisionStore();
		$revision = $revisionStore->getRevisionByTitle( Title::newFromText( $name ) );
		$parser = MediaWikiServices::getInstance()->getParser();

		if ( is_object( $revision ) ) {
			$text = $revision->getText();

			if ( !empty( $text ) ) {
				$ret = $parser->transformMsg( $text, $parser->getOptions() );

				if ( $asArray ) {
					$ret = explode( "\n", $ret );
				}

				return $ret;
			}
		}

		return null;
	}
}
