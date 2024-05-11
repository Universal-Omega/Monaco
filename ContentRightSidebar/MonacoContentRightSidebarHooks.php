<?php
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\ParserFirstCallInitHook;

define( 'RIGHT_SIDEBAR_START_TOKEN', '<!-- RIGHT SIDEBAR START -->' );
define( 'RIGHT_SIDEBAR_END_TOKEN', '<!-- RIGHT SIDEBAR END -->' );
define( 'RIGHT_SIDEBAR_WITHBOX_TOKEN', '<!-- RIGHT SIDEBAR WITHBOX -->' );
define( 'RIGHT_SIDEBAR_TITLE_START_TOKEN', '<!-- RIGHT SIDEBAR TITLE START>' );
define( 'RIGHT_SIDEBAR_TITLE_END_TOKEN', '<RIGHT SIDEBAR TITLE END -->');
define( 'RIGHT_SIDEBAR_CLASS_START_TOKEN', '<!-- RIGHT SIDEBAR CLASS START>' );
define( 'RIGHT_SIDEBAR_CLASS_END_TOKEN', '<RIGHT SIDEBAR CLASS END -->' );
define( 'RIGHT_SIDEBAR_CONTENT_START_TOKEN', '<!-- RIGHT SIDEBAR CONTENT START -->' );
define( 'RIGHT_SIDEBAR_CONTENT_END_TOKEN', '<!-- RIGHT SIDEBAR CONTENT END -->' );

class MonacoContentRightSidebarHooks implements
	BeforePageDisplayHook,
	ParserFirstCallInitHook
{

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		if ( $skin->getSkinName() === 'monaco' ) {
			$out->addModules( [ 'ext.MonacoContentRightSidebar' ] );
		}
	}

	/**
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {	public static function onParserFirstCallInit( Parser $parser )  {
		$parser->setHook( 'right-sidebar', [ __CLASS__, 'contentRightSidebarTag' ] );
	}

	/**
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string
	 */
	public static function contentRightSidebarTag( $input, $args, $parser, $frame )  {
		$isContentTagged = false;
		$m = [];

		if ( preg_match( '#^(.*)<content>(.*?)</content>(.*)$#is', $input, $m ) ) {
			$isContentTagged = true;

			$startUniq = $parser->uniqPrefix() . '-right-sidebar-content-start-' . Parser::MARKER_SUFFIX;
			$endUniq = $parser->uniqPrefix() . '-right-sidebar-content-end-' . Parser::MARKER_SUFFIX;
			$input = "{$m[1]}{$startUniq}{$m[2]}{$endUniq}{$m[3]}";
			$input = $parser->recursiveTagParse( $input, $frame );
			$input = str_replace( $startUniq, RIGHT_SIDEBAR_CONTENT_START_TOKEN, $input );
			$input = str_replace( $endUniq, RIGHT_SIDEBAR_CONTENT_END_TOKEN, $input );
		} else {
			$input = $parser->recursiveTagParse( $input, $frame );
		}

		$with_box = ( isset( $args['with-box'] ) ? $args['with-box'] : ( isset( $args['withbox'] ) ? $args['withbox'] : null ) );

		$out  = RIGHT_SIDEBAR_START_TOKEN;

		if ( $with_box && !in_array( strtolower( $with_box ), [ 'false', 'off', 'no', 'none' ] ) ) {
			$out .= RIGHT_SIDEBAR_WITHBOX_TOKEN;
		}

		if ( isset( $args['title'] ) ) {
			$out .= RIGHT_SIDEBAR_TITLE_START_TOKEN . urlencode( $args['title'] ) . RIGHT_SIDEBAR_TITLE_END_TOKEN;
		}

		if ( isset( $args['class'] ) ) {
			$out .= RIGHT_SIDEBAR_CLASS_START_TOKEN . urlencode( $args['class'] ) . RIGHT_SIDEBAR_CLASS_END_TOKEN;
		}

		if ( $isContentTagged ) {
			$out .= $input;
		} else {
			$out .= '<div style="float: right; clear: right; position: relative;">';
			$out .= RIGHT_SIDEBAR_CONTENT_START_TOKEN . $input . RIGHT_SIDEBAR_CONTENT_END_TOKEN;
			$out .= '</div>';
		}

		$out .= RIGHT_SIDEBAR_END_TOKEN;

		return $out;
	}

	/**
	 * @param string &$html
	 * @return array
	 */
	private static function extractRightSidebarBoxes( &$html ) {
		$boxes = [];

		while ( true ) {
			$withBox = false;
			$title = '';
			$class = null;

			$start = strpos( $html, RIGHT_SIDEBAR_START_TOKEN );
			if ( $start === false ) {
				break;
			}

			$end = strpos( $html, RIGHT_SIDEBAR_END_TOKEN, $start );
			if ( $end === false ) {
				break;
			}

			$content = substr( $html, $start, $end-$start );
			if ( strpos( $content, RIGHT_SIDEBAR_WITHBOX_TOKEN ) !== false ) {
				$withBox = true;
			}

			$startTitle = strpos( $content, RIGHT_SIDEBAR_TITLE_START_TOKEN );
			if ( $startTitle !== false ) {
				$endTitle = strpos( $content, RIGHT_SIDEBAR_TITLE_END_TOKEN, $startTitle );
				if ( $endTitle !== false ) {
					$title = urldecode( substr( $content, $startTitle+strlen( RIGHT_SIDEBAR_TITLE_START_TOKEN ), $endTitle-$startTitle-strlen( RIGHT_SIDEBAR_TITLE_START_TOKEN ) ) );
				}
			}

			$startClass = strpos( $content, RIGHT_SIDEBAR_CLASS_START_TOKEN );
			if ( $startClass !== false ) {
				$endClass = strpos( $content, RIGHT_SIDEBAR_CLASS_END_TOKEN, $startClass );
				if ( $endClass !== false ) {
					$class = urldecode( substr( $content, $startClass+strlen( RIGHT_SIDEBAR_CLASS_START_TOKEN ), $endClass-$startClass-strlen( RIGHT_SIDEBAR_CLASS_START_TOKEN ) ) );
				}
			}

			$contentStart = strpos( $content, RIGHT_SIDEBAR_CONTENT_START_TOKEN );
			if ( $contentStart !== false ) {
				$content = substr( $content, $contentStart+strlen( RIGHT_SIDEBAR_CONTENT_START_TOKEN ) );
			}

			$contentEnd = strpos( $content, RIGHT_SIDEBAR_CONTENT_END_TOKEN );
			if ( $contentStart !== false ) {
				$content = substr( $content, 0, $contentEnd );
			}

			$boxes[] = [ 'with-box' => $withBox, 'title' => $title, 'class' => $class, 'content' => $content ];
			$html = substr( $html, 0, $start ) . substr( $html, $end+strlen( RIGHT_SIDEBAR_END_TOKEN ) );
		}

		return $boxes;
	}

	 /**
	 * MonacoRightSidebar custom hook handler. This is invoked by the Monaco skin 
	 * to add the right sidebar. If this hook is not invoked, then right sidebar 
	 * content renders as a right-floating box inside the article.
	 *
	 * @param Skin $skin
	 * @return string
	 */
	public static function onMonacoRightSidebar( $skin ) {
		$boxes = self::extractRightSidebarBoxes( $skin->data['bodytext'] );

		foreach ( $boxes as $box ) {
			if ( $box['with-box'] ) {
				$attrs = [];
				if ( isset( $box['class'] ) ) {
					$attrs['class'] = $box['class'];
				}

				return $skin->sidebarBox( $box['title'], $box['content'], $attrs );
			} else {
				return $box['content'];
			}
		}
	}
}
