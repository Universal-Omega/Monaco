<?php

use MediaWiki\MediaWikiServices;

class MonacoTemplate extends BaseTemplate {
	/**
	 * Shortcut for building these crappy blankimg based icons that probably could
	 * have been implemented in a less ugly way.
	 * @author Daniel Friesen
	 */
	private function blankimg( $attrs = [] ) {
		return Html::element( 'img', [ 'src' => $this->data['blankimg'] ] + $attrs );
	}

	/**
	 * Make this a method so that subskins can override this if they reorganize
	 * the user header and need the more button to function.
	 * 
	 * @author Daniel Friesen
	 */
	private function useUserMore() {
		global $wgMonacoUseMoreButton;

		return $wgMonacoUseMoreButton;
	}

	public function execute() {
		global $wgUser, $wgStyleVersion, $wgRequest, $wgTitle, $wgSitename;
		global $wgMonacoUseSitenoticeIsland;

		$this->addVariables();

		$skin = $this->data['skin'];
		$action = $wgRequest->getText( 'action' );
		$namespace = $wgTitle->getNamespace();

		$this->set( 'blankimg', $this->data['stylepath'] . '/Monaco/style/images/blank.gif' );
		
		$this->setupRightSidebar();
		ob_start();
		Hooks::run( 'MonacoRightSidebar', [ $this ] );
		$this->addToRightSidebar( ob_get_contents() );
		ob_end_clean();
		
		$html = $this->get( 'headelement' );


	$html .= $this->printAdditionalHead(); // @fixme not valid

	// this hook allows adding extra HTML just after <body> opening tag
	// append your content to $html variable instead of echoing
	Hooks::run( 'GetHTMLAfterBody', [ $this, &$html ] );

$html .= '<div id="skiplinks"> 
	<a class="skiplink" href="#article" tabIndex=1>Skip to Content</a> 
	<a class="skiplink wikinav" href="#widget_sidebar" tabIndex=1>Skip to Navigation</a> 
</div>

	<div id="background_accent1"></div>
	<div id="background_accent2"></div>

	<!-- HEADER -->';
	$html .= $this->printCustomHeader();
	$html .= '<div id="wikia_header" class="color2">
		<div class="monaco_shrinkwrap">' .
			$this->printMonacoBranding() .
			$this->printUserData() .
		'</div>
	</div>';

if ( Hooks::run( 'AlternateNavLinks' ) ) {
		$html .= '<div id="background_strip" class="reset">
			<div class="monaco_shrinkwrap">

			<div id="accent_graphic1"></div>
			<div id="accent_graphic2"></div>
			</div>
		</div>';
}
	$html .= '<!-- /HEADER -->

		<!-- PAGE -->
	<div id="monaco_shrinkwrap_main" class="monaco_shrinkwrap with_left_sidebar' . ( $this->hasRightSidebar() ? ' with_right_sidebar' : null ) . '">
		<div id="page_wrapper">';
Hooks::run( 'MonacoBeforePage', [ $this ] );
$html .= $this->printBeforePage();
if ( $wgMonacoUseSitenoticeIsland && $this->data['sitenotice'] ) {
			$html .= '<div class="page">
				<div id="siteNotice">' . $this->get('sitenotice') . '</div>
			</div>';
}
		$html .= '<div id="wikia_page" class="page">' .
			$this->printMasthead();
			Hooks::run( 'MonacoBeforePageBar', [ $this ] );
			$html .= $this->printPageBar() . '
					<!-- ARTICLE -->

				<article id="content" class="mw-body" role="main" aria-labelledby="firstHeading">
					<a id="top"></a>';
					Hooks::run( 'MonacoAfterArticle', [ $this ] );
					if ( !$wgMonacoUseSitenoticeIsland && $this->data['sitenotice'] ) { $html .= '<div id="siteNotice">' . $this->get( 'sitenotice' ) . '</div>'; }
					if ( method_exists( $this, 'getIndicators' ) ) { $html .= $this->getIndicators(); }
					$html .= $this->printFirstHeading() . '
					<div id="bodyContent" class="body_content">
						<h2 id="siteSub">' . $this->getMsg( 'tagline' )->parse() . '</h2>';
						if ( $this->data['subtitle'] ) { $html .= '<div id="contentSub">' . $this->get( 'subtitle' ) . '</div>'; }
						if ( $this->data['undelete'] ) { $html .= '<div id="contentSub2">' . $this->get( 'undelete' ) . '</div>'; }
						if ( $this->data['newtalk'] ) { $html .= '<div class="usermessage noprint">' . $this->get( 'newtalk' )  . '</div>'; }
						if ( !empty( $skin->newuemsg ) ) { $html .= $skin->newuemsg; }

					$html .= '<!-- start content -->';

						// Display content
						$html .= $this->printContent();

						$html .= $this->printCategories();
			
					$html .= '<!-- end content -->';
						if ( $this->data['dataAfterContent'] ) { $html .= $this->get( 'dataAfterContent' ); }
						$html .= '<div class="visualClear"></div>
					</div>

				</article>
				<!-- /ARTICLE -->

			<!-- ARTICLE FOOTER -->';
global $wgTitle, $wgOut;
$custom_article_footer = '';
$namespaceType = '';
Hooks::run( 'CustomArticleFooter', [ &$this, &$tpl, &$custom_article_footer ] );
if ($custom_article_footer !== '') {
	$html .= $custom_article_footer;
} else {
	// default footer
	if ( $wgTitle->exists() && $wgTitle->isContentPage() && !$wgTitle->isTalkPage() ) {
		$namespaceType = 'content';
	}
	// talk footer
	elseif ( $wgTitle->isTalkPage() ) {
		$namespaceType = 'talk';
	}
	// disable footer on some namespaces
	elseif ( $namespace == NS_SPECIAL ) {
		$namespaceType = 'none';
	}

	$action = $wgRequest->getVal('action', 'view');
	if ( $namespaceType != 'none' && in_array( $action, [ 'view', 'purge', 'edit', 'history', 'delete', 'protect' ] ) ) {
		$nav_urls = $this->data['nav_urls'];
		global $wgLang;
			$html .= '<div id="articleFooter" class="reset article_footer">
				<table style="border-spacing: 0;">
					<tr>
						<td class="col1">
							<ul class="actions" id="articleFooterActions">';
		if ($namespaceType == 'talk') {
			$custom_article_footer = '';
			Hooks::run('AddNewTalkSection', array( &$this, &$tpl, &$custom_article_footer ));
			if ($custom_article_footer != '')
				 $html .= $custom_article_footer;
		} else {
			$html .= "								";
			$html .= Html::rawElement( 'li', null,
				Html::rawElement( 'a', array( "id" => "fe_edit_icon", "href" => $wgTitle->getEditURL() ),
					$this->blankimg( array( "id" => "fe_edit_img", "class" => "sprite edit", "alt" => "" ) ) ) .
				' ' .
				Html::rawElement( 'div', null,
					wfMessage('monaco-footer-improve')->rawParams(
						Html::element( 'a', array( "id" => "fe_edit_link", "href" => $wgTitle->getEditURL() ), wfMessage('monaco-footer-improve-linktext')->text() ) )->text() ) );
			$html .= "\n";
		}

		$myContext = $this->getSkin()->getContext();

		if ( $myContext->canUseWikiPage() ) {
			$wikiPage = $myContext->getWikiPage();
			$timestamp = $wikiPage->getTimestamp();
			$lastUpdate = $wgLang->date( $timestamp );
			$userId = $wikiPage->getUser();

			if ( $userId > 0 ) {
				$user = User::newFromName( $wikiPage->getUserText() );
				$userPageTitle = $user->getUserPage();
				$userPageLink = $userPageTitle->getLocalUrl();
				$userPageExists = $userPageTitle->exists();
				$userGender = $user->getOption( 'gender' );
				$feUserIcon = $this->blankimg( [ 'id' => 'fe_user_img', 'alt' => '', 'class' => ( $userGender == 'female' ? 'sprite user-female' : 'sprite user' ) ] );

				if ( $userPageExists ) {
					$feUserIcon = Html::rawElement( 'a', [ 'id' => 'fe_user_icon', 'href' => $userPageLink ], $feUserIcon );
				}

				$html .= '<li>' . $feUserIcon . '<div>';
				$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
				$html .= wfMessage( 'monaco-footer-lastedit' )->rawParams( $linkRenderer->makeLink( $userPageTitle, $user->getName(), [ 'id' => 'fe_user_link' ] ), Html::element( 'time', [ 'datetime' => wfTimestamp( TS_ISO_8601, $$timestamp ) ], $lastUpdate ) )->escaped();
			}
		}

		if ( $this->data['copyright'] ) {
			$feCopyIcon = $this->blankimg( [ 'id' => 'fe_copyright_img', 'class' => 'sprite copyright', 'alt' => '' ] );

			$html .= '<li>' . $feCopyIcon . '<div id="copyright">' . $this->get( 'copyright' ) . '</div></li>';
		}

		$html .= '</ul></td><td class="col2">';

		if ( !empty( $this->data['content_actions']['history'] ) || !empty( $nav_urls['recentchangeslinked'] ) ) {
			$html .= '<ul id="articleFooterActions3" class="actions clearfix">';

			if ( !empty( $this->data['content_actions']['history'] ) ) {
				$feHistoryIcon = $this->blankimg( [ 'id' => 'fe_history_img', 'class' => 'sprite history', 'alt' => '' ] );
				$feHistoryIcon = Html::rawElement( 'a', [ 'id' => 'fe_history_icon', 'href' => $this->data['content_actions']['history']['href'] ], $feHistoryIcon );
				$feHistoryLink = Html::rawElement( 'a', [ 'id' => 'fe_history_link', 'href' => $this->data['content_actions']['history']['href'] ], $this->data['content_actions']['history']['text'] );

				$html .= '<li id="fe_history">' . $feHistoryIcon . '<div>' . $feHistoryLink . '</div></li>';
			}

			if ( !empty( $nav_urls['recentchangeslinked'] ) ) {
				$feRecentIcon = $this->blankimg(array("id" => "fe_recent_img", "class" => "sprite recent", "alt" => ""));
				$feRecentIcon = Html::rawElement("a", array("id" => "fe_recent_icon", "href" => $nav_urls['recentchangeslinked']['href']), $feRecentIcon);
				$feRecentLink = Html::rawElement("a", array("id" => "fe_recent_link", "href" => $nav_urls['recentchangeslinked']['href']), wfMessage('recentchangeslinked')->escaped());

				$html .= '<li id="fe_recent">' . $feRecentIcon . '<div>' . $feRecentLink . '</div></li>';
			}

			$html .= '</ul>';
		}

		if ( !empty( $nav_urls['permalink'] ) || !empty( $nav_urls['whatlinkshere'] ) ) {
			$html .= '<ul id="articleFooterActions4" class="actions clearfix">';

			if ( !empty( $nav_urls['permalink'] ) ) {
				$fePermaIcon = $this->blankimg(array("id" => "fe_permalink_img", "class" => "sprite move", "alt" => ""));
				$fePermaIcon = Html::rawElement("a", array("id" => "fe_permalink_icon", "href" => $nav_urls['permalink']['href']), $fePermaIcon);
				$fePermaLink = Html::rawElement("a", array("id" => "fe_permalink_link", "href" => $nav_urls['permalink']['href']), $nav_urls['permalink']['text']);

				$html .= '<li id="fe_permalink">' . $fePermaIcon . '<div>' . $fePermaLink . '</div></li>';
			}

			if ( !empty( $nav_urls['whatlinkshere'] ) ) {
				$feWhatIcon = $this->blankimg(array("id" => "fe_whatlinkshere_img", "class" => "sprite pagelink", "alt" => ""));
				$feWhatIcon = Html::rawElement("a", array("id" => "fe_whatlinkshere_icon", "rel" => "nofollow", "href" => $nav_urls['whatlinkshere']['href']), $feWhatIcon);
				$feWhatLink = Html::rawElement("a", array("id" => "fe_whatlinkshere_link", "rel" => "nofollow", "href" => $nav_urls['whatlinkshere']['href']), wfMessage('whatlinkshere')->escaped());

				$html .= '<li id="fe_whatlinkshere">' . $feWhatIcon . '<div>' . $feWhatLink . '</div></li>';
			}
							$html .= '</ul>';
		}

		$feRandIcon = $this->blankimg( [ 'id' => 'fe_random_img', 'class' => 'sprite random', 'alt' => '' ] );
		$feRandIcon = Html::rawElement( 'a', [ 'id' => 'fe_random_icon', 'href' => Skin::makeSpecialUrl( 'Randompage' ) ], $feRandIcon );
		$feRandLink = Html::rawElement( 'a', [ 'id' => 'fe_random_link', 'href' => Skin::makeSpecialUrl( 'Randompage' ) ], wfMessage( 'viewrandompage' )->escaped() );

		$html .= '<ul class="actions clearfix" id="articleFooterActions2">';
		$html .= '<li id="fe_randompage">' . $feRandIcon . '<div>' . $feRandLink . '</div></li>';

		if ( $this->get( 'mobileview' ) !== null ) {
			$feMobileIcon = $this->blankimg( [ 'id' => 'fe_mobile_img', 'class' => 'sprite mobile', 'alt' => '' ] );
			$this->set( 'mobileview', preg_replace( '/(<a[^>]*?href[^>]*?)>/', '$1 rel="nofollow">', $this->get( 'mobileview' ) ) );

			$html .= '<li id="fe_mobile">' . $feMobileIcon . '<div>' . $this->get( 'mobileview' ) . '</div></li>';
		}

		$html .= '</ul>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';
		$html .= '</div>';
	} // end $namespaceType != 'none'
} // end else from CustomArticleFooter hook

				$html .= '<!-- /ARTICLE FOOTER -->

			</div>
			<!-- /PAGE -->

			<noscript><link rel="stylesheet" property="stylesheet" type="text/css" href="' . $this->get( 'stylepath' ) . '/Monaco/style/css/noscript.css?' . $wgStyleVersion . '" /></noscript>';
	if(!($wgRequest->getVal('action') != '' || $namespace == NS_SPECIAL)) {
		$html .= $this->get('JSloader');
		$html .= $this->get('headscripts');
	}

		$html .= '</div>' .
$this->printRightSidebar() . '
		<!-- WIDGETS -->';
			global $wgScriptPath;
		$html .= '<div id="widget_sidebar" class="reset widget_sidebar left_sidebar sidebar">
			<div id="wiki_logo" style="background-image: url(' . $this->get( 'logopath' ) . ');"><a href="' . htmlspecialchars($this->data['nav_urls']['mainpage']['href']) . '" accesskey="z" rel="home">' . $wgSitename . '</a></div>

			<!-- SEARCH/NAVIGATION -->
			<div class="widget sidebox navigation_box" id="navigation_widget" role="navigation">';

	global $wgSitename;
	$msgSearchLabel = wfMessage('Tooltip-search')->escaped();
	$searchLabel = wfMessage('Tooltip-search')->isDisabled() ? (wfMessage('ilsubmit')->escaped().' '.$wgSitename.'...') : $msgSearchLabel;

			$html .= '<div id="search_box" class="color1" role="search">
				<form action="' . $this->get('searchaction') . '" id="searchform">
					<label style="display: none;" for="searchInput">' . htmlspecialchars($searchLabel) . '</label>' .
					Html::input( 'search', '', 'search', array(
						'id' => "searchInput",
						'maxlength' => 200,
						'aria-label' => $searchLabel,
						'placeholder' => $searchLabel,
						'tabIndex' => 2,
						'aria-required' => 'true',
						'aria-flowto' => "search-button",
					) + Linker::tooltipAndAccesskeyAttribs('search') );
					global $wgSearchDefaultFulltext;
					$html .= '<input type="hidden" name="' . ( $wgSearchDefaultFulltext ? 'fulltext' : 'go' ) . '" value="1" />
					<input type="image" alt="' . htmlspecialchars(wfMessage('search')->escaped()) . '" src="' . $this->get('blankimg') . '" id="search-button" class="sprite search" tabIndex=2 />
				</form>
			</div>';
	$monacoSidebar = new MonacoSidebar();
	if(isset($this->data['content_actions']['edit'])) {
		$monacoSidebar->editUrl = $this->data['content_actions']['edit']['href'];
	}
	$html .= $monacoSidebar->getCode();

	$html .= '<table style="border-spacing: 0;" id="link_box_table">';
	//BEGIN: create dynamic box
	$showDynamicLinks = true;
	$dynamicLinksArray = array();

	global $wgRequest;
	if ( $wgRequest->getText( 'action' ) == 'edit' || $wgRequest->getText( 'action' ) == 'submit' ) {
		$showDynamicLinks = false;
	}

	if ( $showDynamicLinks ) {
		$dynamicLinksInternal = array();
		
		global $wgMonacoDynamicCreateOverride;
		$createPage = null;
		if(!wfMessage('dynamic-links-write-article-url')->isDisabled()) {
			$createPage = Title::newFromText(wfMessage('dynamic-links-write-article-url')->text());
		}
		if ( !isset($createPage) && !empty($wgMonacoDynamicCreateOverride) ) {
			$createPage = Title::newFromText($wgMonacoDynamicCreateOverride);
		}
		if ( !isset($createPage) ) {
		    
			$specialPageFactory = MediaWikiServices::getInstance()->getSpecialPageFactory();
			$specialCreatePage = $specialPageFactory->getPage('CreatePage');
			if ( $specialCreatePage && $specialCreatePage->userCanExecute($wgUser) ) {
				$createPage = SpecialPage::getTitleFor('CreatePage');
			}
		}
		if ( isset($createPage) && ( $wgUser->isAllowed('edit') || $wgUser->isAnon() ) ) {
			/* Redirect to login page instead of showing error, see Login friction project */
			$dynamicLinksInternal["write-article"] = array(
				'url' => $wgUser->isAnon() ? SpecialPage::getTitleFor('Userlogin')->getLocalURL(array("returnto"=>$createPage->getPrefixedDBkey())) : $createPage->getLocalURL(),
				'icon' => 'edit',
			);
		}
		global $wgEnableUploads, $wgUploadNavigationUrl;
		if ( ( $wgEnableUploads || $wgUploadNavigationUrl ) && ( $wgUser->isAllowed('upload') || $wgUser->isAnon() || $wgUploadNavigationUrl ) ) {
			$uploadPage = SpecialPage::getTitleFor('Upload');
			/* Redirect to login page instead of showing error, see Login friction project */
			if ( $wgUploadNavigationUrl ) {
				$url = $wgUploadNavigationUrl;
			} else {
				$url = $wgUser->isAnon() ? SpecialPage::getTitleFor('Userlogin')->getLocalURL(array("returnto"=>$uploadPage->getPrefixedDBkey())) : $uploadPage->getLocalURL();
			}
			$dynamicLinksInternal["add-image"] = array(
				'url' => $url,
				'icon' => 'photo',
			);
		}
		
		$html .= $this->extendDynamicLinks( $dynamicLinksInternal );
		Hooks::run( 'MonacoDynamicLinks', array( $this, &$dynamicLinksInternal ) );
		$html .= $this->extendDynamicLinksAfterHook( $dynamicLinksInternal );
		
		$dynamicLinksUser = array();
		foreach ( explode( "\n", wfMessage('dynamic-links')->inContentLanguage()->text() ) as $line ) {
			if ( !$line || $line[0] == ' ' )
				continue;
			$line = trim($line, '* ');
			if (!wfMessage("dynamic-links-$line-url")->isDisabled()) {
				$url = Title::newFromText(wfMessage("dynamic-links-$line-url")->text());
				if ( $url ) {
					$dynamicLinksUser[$line] = array(
						"url" => $url,
						"icon" => "edit", // @note Designers used messy css sprites so we can't really let this be customized easily
					);
				}
			}
		}
		
		foreach ( $dynamicLinksUser as $key => $value )
			$dynamicLinksArray[$key] = $value;
		foreach ( $dynamicLinksInternal as $key => $value )
			$dynamicLinksArray[$key] = $value;
	}

	if (count($dynamicLinksArray) > 0) {

	$html .= '<tbody id="link_box_dynamic">
			<tr>
				<td colspan="2">
					<ul>';
			foreach ($dynamicLinksArray as $key => $link) {
				$link['id'] = "dynamic-links-$key";
				if ( !isset($link['text']) )
					$link['text'] = wfMessage("dynamic-links-$key")->text();
			    $html .= "						";
				$html .= Html::rawElement( 'li', array( "id" => "{$link['id']}-row", "class" => "link_box_dynamic_item" ),
					Html::rawElement( 'a', array( "id" => "{$link['id']}-icon", "href" => $link['url'], "tabIndex" => -1 ),
						$this->blankimg( array( "id" => "{$link['id']}-img", "class" => "sprite {$link['icon']}", "alt" => "" ) ) ) .
					' ' .
					Html::element( 'a', array( "id" => "{$link['id']}-link", "href" => $link["url"], "tabIndex" => 3 ), $link["text"] ) );
				$html .= "\n";
			}

				$html .= '</ul>
				</td>
			</tr>
		</tbody>';
	}
	//END: create dynamic box

	//BEGIN: create static box
	$linksArrayL = $linksArrayR = array();
	$linksArray = $this->data['data']['toolboxlinks'];

	//add user specific links
	if(!empty($nav_urls['contributions'])) {
		$linksArray[] = array('href' => $nav_urls['contributions']['href'], 'text' => wfMessage('contributions')->text());
	}
	if(!empty($nav_urls['blockip'])) {
		$linksArray[] = array('href' => $nav_urls['blockip']['href'], 'text' => wfMessage('blockip')->text());
	}
	if(!empty($nav_urls['emailuser'])) {
		$linksArray[] = array('href' => $nav_urls['emailuser']['href'], 'text' => wfMessage('emailuser')->text());
	}

	if(is_array($linksArray) && count($linksArray) > 0) {
		global $wgSpecialPagesRequiredLogin;
		for ($i = 0, $max = max(array_keys($linksArray)); $i <= $max; $i++) {
			$item = isset($linksArray[$i]) ? $linksArray[$i] : false;
			//Redirect to login page instead of showing error, see Login friction project
			if ($item !== false && $wgUser->isAnon() && isset($item['specialCanonicalName']) && in_array($item['specialCanonicalName'], $wgSpecialPagesRequiredLogin)) {
				$returnto = SpecialPage::getTitleFor($item['specialCanonicalName'])->getPrefixedDBkey();
				$item['href'] = SpecialPage::getTitleFor('Userlogin')->getLocalURL(array("returnto"=>$returnto));
			}
			$i & 1 ? $linksArrayR[] = $item : $linksArrayL[] = $item;
		}
	}

	if(count($linksArrayL) > 0 || count($linksArrayR) > 0) {
		$html .= '<tbody id="link_box" class="color2 linkbox_static">
			<tr>
				<td>
					<ul>';
		if(is_array($linksArrayL) && count($linksArrayL) > 0) {
			foreach($linksArrayL as $key => $val) {
				if ($val === false) {
					$html .= '<li>&nbsp;</li>';
				} else {
						$html .= '<li><a' . ( !isset($val['internal']) || !$val['internal'] ? ' rel="nofollow" ' : null ) . 'href="' . htmlspecialchars($val['href']) . '" tabIndex=3>' . htmlspecialchars($val['text']) . '</a></li>';
				}
			}
		}
					$html .= '</ul>
				</td>
				<td>
					<ul>';
		if(is_array($linksArrayR) && count($linksArrayR) > 0) {
		    foreach($linksArrayR as $key => $val) {
				if ($val === false) {
					$html .= '<li>&nbsp;</li>';
				} else {

						$html .= '<li><a' . ( !isset($val['internal']) || !$val['internal'] ? ' rel="nofollow" ' : null ) . 'href="' . htmlspecialchars($val['href']) . '" tabIndex=3>' . htmlspecialchars($val['text']) . '</a></li>';
				}
			}
		}
						$html .= '<li style="font-size: 1px; position: absolute; top: -10000px"><a href="' . Title::newFromText('Special:Recentchanges')->getLocalURL() . '" accesskey="r">Recent changes</a><a href="' . Title::newFromText('Special:Random')->getLocalURL() . '" accesskey="x">Random page</a></li>';
					$html .= '</ul>
				</td>
			</tr>
			<!-- haleyjd 20140420: FIXME: DoomWiki.org-specific; make generic! -->
			<!--
			<tr>
				<td colspan="2" style="text-align:center;">
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="D5MLUSDXA8HMQ">
						<input type="image" src="' . $this->get('stylepath') . '/Monaco/style/images/contribute-button.png" name="submit" alt="PayPal - The safer, easier way to pay online!" style="border: 0; width:139px; margin:0;">
						<img alt="" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1" style="border: 0;">
					</form>
				</td>
			</tr>
			-->
		</tbody>';
	}
	// END: create static box
	$html .= '</table>
			</div>
			<!-- /SEARCH/NAVIGATION -->' .
		$this->printExtraSidebar();
Hooks::run( 'MonacoSidebarEnd', [ $this ] );

		$html .= '</div>
		<!-- /WIDGETS -->
	<!--/div-->';

// curse like cobranding
$html .= $this->printCustomFooter();


$html .= '</div>';

$html .= $this->get('bottomscripts'); /* JS call to runBodyOnloadHook */
Hooks::run('SpecialFooter');
		$html .= '<div id="positioned_elements" class="reset"></div>';

$html .= $this->delayedPrintCSSdownload();
$html .= $this->get( 'reporttime' );

	$html .= '</body>
</html>';
echo $html;
	} // end execute()

	public function addVariables() {
		$skin = $this->getSkin();

		$user = $skin->getUser();

		$contLang = MediaWikiServices::getInstance()->getContentLanguage();
		$lang = $skin->getContext()->getLanguage();

		$parserCache = MediaWikiServices::getInstance()->getParserCache();

		// We want to cache populated data only if user language is same with wiki language
		$cache = $lang->getCode() == $contLang->getCode();

		if ( $cache ) {
			$key = ObjectCache::getLocalClusterInstance()->makeKey( 'MonacoDataOld' );
			$data_array = $parserCache->getCacheStorage()->get( $key );
		}

		if ( empty( $data_array ) ) {
			$data_array['toolboxlinks'] = $skin->getToolboxLinks();

			if ( $cache ) {
				$parserCache->getCacheStorage()->set( $key, $data_array, 4 * 60 * 60 /* 4 hours */ );
			}
		}

		if ( $user->isRegistered() ) {
			if ( empty( $user->mMonacoData ) || ( $this->getTitle()->getNamespace() == NS_USER && $this->getRequest()->getText( 'action' ) == 'delete' ) ) {
				$user->mMonacoData = [];

				$text = $skin->getTransformedArticle( 'User:' . $user->getName() . '/Monaco-toolbox', true );
				if ( empty( $text ) ) {
					$user->mMonacoData['toolboxlinks'] = false;
				} else {
					$user->mMonacoData['toolboxlinks'] = $skin->parseToolboxLinks( $text );
				}
			}

			if ( $user->mMonacoData['toolboxlinks'] !== false && is_array( $user->mMonacoData['toolboxlinks'] ) ) {
				$data_array['toolboxlinks'] = $user->mMonacoData['toolboxlinks'];
			}
		}

		foreach ( $data_array['toolboxlinks'] as $key => $val ) {
			if ( isset( $val['org'] ) && $val['org'] == 'whatlinkshere' ) {
				if ( isset( $this->data['nav_urls']['whatlinkshere'] ) ) {
					$data_array['toolboxlinks'][$key]['href'] = $this->data['nav_urls']['whatlinkshere']['href'];
				} else {
					unset( $data_array['toolboxlinks'][$key] );
				}
			}

			if ( isset( $val['org'] ) && $val['org'] == 'permalink' ) {
				if ( isset( $this->data['nav_urls']['permalink'] ) ) {
					$data_array['toolboxlinks'][$key]['href'] = $this->data['nav_urls']['permalink']['href'];
				} else {
					unset( $data_array['toolboxlinks'][$key] );
				}
			}
		}

		$this->set( 'data', $data_array );

		// Article content links (View, Edit, Delete, Move, etc.)
		$this->set( 'articlelinks', $this->getArticleLinks() );

		// User actions links
		$this->set( 'userlinks', $this->getUserLinks() );
	}

	private function getArticleLinks() {
		$skin = $this->getSkin();

		$links = [];

		if ( isset( $this->data['content_navigation'] ) ) {
			// Use MediaWiki 1.18's better vector based content_navigation structure
			// to organize our tabs better
			foreach ( $this->data['content_navigation'] as $section => $nav ) {
				foreach ( $nav as $key => $val ) {
					if ( isset( $val['redundant'] ) && $val['redundant'] ) {
						continue;
					}
					
					$kk = ( isset( $val['id'] ) && substr( $val['id'], 0, 3 ) == 'ca-' ) ? substr( $val['id'], 3 ) : $key;
					
					$msgKey = $kk;
					if ( $kk == 'edit' ) {
						$title = $skin->getRelevantTitle();
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
			if ( empty( $this->data['content_actions'] ) ) {
				return $links;
			}

			# @todo: might actually be useful to move this to a global var and handle this in extension files --TOR
			$force_right = [ 'userprofile', 'talk', 'TheoryTab' ];
			foreach ( $this->data['content_actions'] as $key => $val ) {
				$msgKey = $key;
				if ( $key == 'edit' ) {
					$msgKey = $skin->getTitle()->exists() || ( $skin->getTitle()->getNamespace() == NS_MEDIAWIKI && wfMessage( $skin->getTitle()->getText() )->exists() )
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

	private function getUserLinks() {
		$skin = $this->getSkin();

		$data = [];
		$request = $skin->getRequest();
		$user = $skin->getUser();

		$page = Title::newFromURL( $request->getVal( 'title', '' ) );
		$page = $request->getVal( 'returnto', $page );
		$a = [];

		if ( strval( $page ) !== '' ) {
			$a['returnto'] = $page;
			$query = $request->getVal( 'returntoquery', $skin->thisquery );
			if( $query != '' ) {
				$a['returntoquery'] = $query;
			}
		}
		$returnto = wfArrayToCGI( $a );

		if ( !$user->isRegistered() ) {
			$signUpHref = Skin::makeSpecialUrl( 'Userlogin', $returnto );
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
				'href' => $this->data['personal_urls']['userpage']['href']
			];

			$data['mytalk'] = [
				'text' => $this->data['personal_urls']['mytalk']['text'],
				'href' => $this->data['personal_urls']['mytalk']['href']
			];

			if ( isset( $this->data['personal_urls']['watchlist'] ) ) {
				$data['watchlist'] = [
					/*'text' => $this->data['personal_urls']['watchlist']['text'],*/
					'text' => wfMessage( 'prefs-watchlist' )->text(),
					'href' => $this->data['personal_urls']['watchlist']['href']
				];
			}

			// In some cases, logout will be removed explicitly (such as when it is replaced by fblogout).
			if ( isset( $this->data['personal_urls']['logout'] ) ) {
				$data['logout'] = [
					'text' => $this->data['personal_urls']['logout']['text'],
					'href' => $this->data['personal_urls']['logout']['href']
				];
			}


			$data['more']['userpage'] = [
				'text' => wfMessage( 'mypage' )->text(),
				'href' => $this->data['personal_urls']['userpage']['href']
			];

			if ( isset ( $this->data['personal_urls']['userprofile'] ) ) {
				$data['more']['userprofile'] = [
					'text' => $this->data['personal_urls']['userprofile']['text'],
					'href' => $this->data['personal_urls']['userprofile']['href']
				];
			}

			$data['more']['mycontris'] = [
				'text' => wfMessage( 'mycontris' )->text(),
				'href' => $this->data['personal_urls']['mycontris']['href']
			];

			$data['more']['preferences'] = [
				'text' => $this->data['personal_urls']['preferences']['text'],
				'href' => $this->data['personal_urls']['preferences']['href']
			];
		}

		// This function ignores anything from PersonalUrls hook which it doesn't expect.  This
		// loops lets it expect anything starting with "fb*" (because we need that for facebook connect).
		// Perhaps we should have some system to let PersonalUrls hook work again on its own?
		// - Sean Colombo
		
		foreach ( $this->data['personal_urls'] as $urlName => $urlData ) {
			if ( strpos( $urlName, 'fb' ) === 0 ) {
				$data[$urlName] = $urlData;
			}
		}

		return $data;
	}

	//@author Marooned
	function delayedPrintCSSdownload() {
		global $wgRequest;

		//regular download
		if ($wgRequest->getVal('printable')) {
			// RT #18411
			$html = $this->get('mergedCSSprint');
			// RT #25638
			$html .= "\n\t\t";
			$html .= $this->get('csslinksbottom');
			return $html;
		}
	}

	// allow subskins to tweak dynamic links
	function extendDynamicLinks( &$dynamicLinks ) {}
	function extendDynamicLinksAfterHook( &$dynamicLinks ) {}

	// allow subskins to add extra sidebar extras
	function printExtraSidebar() {}
	
	function sidebarBox( $bar, $cont, $options=array() ) {
		$titleClass = "sidebox_title";
		$contentClass = "sidebox_contents";
		if ( isset($options["widget"]) && $options["widget"] ) {
			$titleClass .= " widget_contents";
			$contentClass .= " widget_title";
		}
		
		$attrs = array( "class" => "widget sidebox" );
		if ( isset($options["id"]) ) {
			$attrs["id"] = $options["id"];
		}
		if ( isset($options["class"]) ) {
			$attrs["class"] .= " {$options["class"]}";
		}
		
		$box = "			";
		$box .= Html::openElement( 'div', $attrs );
		$box .= "\n";
		if ( isset($bar) ) {
			$box .= "				";
			$out = !wfMessage($bar)->exists() ? $bar : wfMessage($bar)->text();
			if ( $out )
				$box .= Html::element( 'h3', array( "class" => "color1 $titleClass" ), $out ) . "\n";
		}
		if ( is_array( $cont ) ) {
			$boxContent .= "					<ul>\n";
			foreach ( $cont as $key => $val ) {
				$boxContent .= "						" . $this->makeListItem($key, $val) . "\n";

			}
			$boxContent .= "					</ul>\n";
		} else {
			$boxContent = $cont;
		}
		if ( !isset($options["wrapcontents"]) || $options["wrapcontents"] ) {
			$boxContent = "				".Html::rawElement( 'div', array( "class" => $contentClass ), "\n".$boxContent."				" ) . "\n";
		}
		$box .= $boxContent;
		$box .= Xml::closeElement( 'div ');
		return $box;
	}
	
	function customBox( $bar, $cont ) {
		return $this->sidebarBox( $bar, $cont );
	}
	
	// hook for subskins
	function setupRightSidebar() {}
	
	function addToRightSidebar( $html ) {
		return $this->mRightSidebar .= $html;
	}
	
	function hasRightSidebar() {
		return (bool)trim($this->mRightSidebar);
	}
	
	// Hook for things that you only want in the sidebar if there are already things
	// inside the sidebar.
	function lateRightSidebar() {}
	
	function printRightSidebar() {
		if ( $this->hasRightSidebar() ) {
		$html .= '<!-- RIGHT SIDEBAR -->
		 <div id="right_sidebar" class="sidebar right_sidebar">' .
$this->lateRightSidebar();
Hooks::run('MonacoRightSidebar::Late', [ $this ] );
$html .= $this->mRightSidebar . '
		</div>
		<!-- /RIGHT SIDEBAR -->';
return $html;
		}
	}
	
	function printMonacoBranding() {
		ob_start();
		Hooks::run( 'MonacoBranding', array( $this ) );
		$branding = ob_get_contents();
		ob_end_clean();
		
		if ( trim($branding) ) {
			return '<div id="monacoBranding">' . $branding . '</div>';
		}
	}
	
	function printUserData() {
		$skin = $this->data['skin'];
			$html = '<div id="userData">';
		
		$custom_user_data = "";
		
		if( $custom_user_data ) {
			$html .= $custom_user_data;
		} else {
			global $wgUser;
			
			// Output the facebook connect links that were added with PersonalUrls.
			// @author Sean Colombo
			foreach($this->data['userlinks'] as $linkName => $linkData){
				// 
				if( !empty($linkData['html']) ){
					$html .= $linkData['html']; 
				}
			}
			
			if ($wgUser->isLoggedIn()) {
				$toolbar = $this->getPersonalTools();

				unset( $toolbar['preferences'] );
				unset( $toolbar['mycontris'] );
				unset( $toolbar['logout'] );
				foreach ( $toolbar as $key => $item ) {
					$html .= $this->makeListItem( $key, $item );
				}
				
				if ( $this->useUserMore() ) {
				$html .= '<span class="more hovermenu">
					<button id="headerButtonUser" class="header-button color1" tabIndex="-1">' . trim(wfMessage('moredotdotdot')->escaped(), ' .') . '<img src="' . $this->get('blankimg') . '" /></button>
					<span class="invisibleBridge"></span>
					<div id="headerMenuUser" class="headerMenu color1 reset">
						<ul>';

				foreach ( $this->data['userlinks']['more'] as $key => $link ) {
					if($key != 'userpage') { // haleyjd 20140420: Do not repeat user page here.
						$html .= Html::rawElement( 'li', array( 'id' => "header_$key" ),
							Html::element( 'a', array( 'href' => $link['href'] ), $link['text'] ) ) . "\n";
					}
				}
						$html .= '</ul>
					</div>
				</span>';

				} else {
					foreach ( $this->data['userlinks']['more'] as $key => $link ) {
						if($key != 'userpage') { // haleyjd 20140420: Do not repeat user page here.
							$html .= Html::rawElement( 'span', array( 'id' => "header_$key" ),
								Html::element( 'a', array( 'href' => $link['href'] ), $link['text'] ) ) . "\n";
						}
					}
				}
				$html .= '<span>' .
					Html::element( 'a', array( 'href' => $this->data['userlinks']['logout']['href'] ) + Linker::tooltipAndAccesskeyAttribs('pt-logout'), $this->data['userlinks']['logout']['text'] ) .
				'</span>';
			} else {
				$html .= '<span id="userLogin">
					<a class="wikia-button" id="login" href="' . htmlspecialchars($this->data['userlinks']['login']['href']) . '">' . htmlspecialchars($this->data['userlinks']['login']['text']) . '</a>
				</span>

					<a class="wikia-button" id="register" href="' . htmlspecialchars($this->data['userlinks']['register']['href']) . '">' . htmlspecialchars($this->data['userlinks']['register']['text']) . '</a>';

			}
		}
			$html .= '</div>';
			
			return $html;
	}
	
	// allow subskins to add pre-page islands
	function printBeforePage() {}

	// curse like cobranding
	function printCustomHeader() {}
	function printCustomFooter() {}

	// Made a separate method so recipes, answers, etc can override. This is for any additional CSS, Javacript, etc HTML
	// that appears within the HEAD tag
	function printAdditionalHead(){}

	function printMasthead() {
		$skin = $this->data['skin'];
		if ( !$skin->showMasthead() ) {
			return;
		}
		global $wgLang;
		$user = $skin->getMastheadUser();
		$username = $user->isAnon() ? wfMessage('masthead-anonymous-user')->text() : $user->getName();
		$editcount = $wgLang->formatNum($user->isAnon() ? 0 : $user->getEditcount());
		$html = '
			<div id="user_masthead" class="accent reset clearfix">
				<div id="user_masthead_head" class="clearfix">
					<h2>' . htmlspecialchars($username);
if ( $user->isAnon() ) {
						$html .= '<small id="user_masthead_anon">' . $user->getName() . '</small>';
} else {
						$html .= '<div id="user_masthead_scorecard" class="dark_text_1">' . htmlspecialchars($editcount) . '</div>';
}
					$html .= '</h2>
				</div>
				<ul id="user_masthead_tabs" class="nav_links">';

				foreach ( $this->data['articlelinks']['right'] as $navLink ) {
					$class = "color1";
					if ( isset($navLink["class"]) ) {
						$class .= " {$navLink["class"]}";
					}
					$html .= Html::rawElement( 'li', array( "class" => $class ),
						Html::element( 'a', array( "href" => $navLink["href"] ), $navLink["text"] ) );
				}
				$html .= '</ul>
			</div>';
		unset($this->data['articlelinks']['right']); // hide the right articlelinks since we've already displayed them
		return $html;
	}

	// Made a separate method so recipes, answers, etc can override. Notably, answers turns it off.
	function printPageBar(){
		// Allow for other skins to conditionally include it
		return $this->realPrintPageBar();
	}
	function realPrintPageBar(){
		foreach ( $this->data['articlelinks'] as $side => $links ) {
			foreach ( $links as $key => $link ) {
				$this->data['articlelinks'][$side][$key]["id"] = "ca-$key";
				if ( $side == "left" && !isset($link["icon"]) ) {
					$this->data['articlelinks'][$side][$key]["icon"] = $key;
				}
			}
		}
		
		$bar = array();
		if ( isset($this->data['articlelinks']['right']) ) {
			$bar[] = array(
				"id" => "page_tabs",
				"type" => "tabs",
				"class" => "primary_tabs",
				"links" => $this->data['articlelinks']['right'],
			);
		}
		if ( isset($this->data['articlelinks']['variants']) ) {
			$contLang = MediaWikiServices::getInstance()->getContentLanguage();

			$preferred = $contLang->getPreferredVariant();
			$bar[] = array(
				"id" => "page_variants",
				"type" => "tabs",
				"class" => "page_variants",
				"links" => array(
					array(
						"class" => 'selected',
						"text" => $contLang->getVariantname( $preferred ),
						"href" => $this->data['skin']->getTitle()->getLocalURL( '', $preferred ),
						"links" => $this->data['articlelinks']['variants'],
					)
				)
			);
		}
		$bar[] = array(
			"id" => "page_controls",
			"type" => "buttons",
			"class" => "page_controls",
			"bad_hook" => "MonacoAfterArticleLinks",
			"links" => $this->data['articlelinks']['left'],
		);
		return $this->printCustomPageBar( $bar );
	}

	var $primaryPageBarPrinted = false;
	function printCustomPageBar( $bar ) {
		global $wgMonacoCompactSpecialPages;
		$isPrimary = !$this->primaryPageBarPrinted;
		$this->primaryPageBarPrinted = true;
		
		$count = 0;
		foreach( $bar as $list ) {
			$count += count($list['links']);
		}
		$useCompactBar = $wgMonacoCompactSpecialPages && $count == 1;
		$deferredList = null;
		
		$divClass = "reset color1 page_bar clearfix";
		
		foreach( $bar as $i => $list ) {
			if ( $useCompactBar && $list["id"] == "page_tabs" && !empty($list["links"]) && isset($list["links"]['nstab-special']) ) {
				$deferredList = $list;
				$deferredList['class'] .= ' compact_page_tabs';
				$divClass .= ' compact_page_bar';
				unset($bar[$i]);
				break;
			}
		}
		
		$html = "		";
		$html .= Html::openElement( 'div', array( "id" => $isPrimary ? "page_bar" : null, "class" => $divClass ) );
		$html .= "\n";
		if ( !$useCompactBar || !isset($deferredList) ) {
			foreach ( $bar as $list ) {
				$html .= $this->printCustomPageBarList( $list );
			}
		}
		$html .= "		</div>\n";
		if ( isset($deferredList) ) {
			$html .= $this->printCustomPageBarList( $deferredList );
		}

		return $html;
	}

	function printCustomPageBarList( $list ) {
		if ( !isset($list["type"]) ) {
			$list["type"] = "buttons";
		}
		$attrs = array(
			"class" => "page_{$list["type"]}",
			"id" => $list["id"],
			"role" => /*$list["type"] == "tabs" ? "navigation" :*/ "toolbar",
		);
		if ( isset($list["class"]) && $list["class"] ) {
			$attrs["class"] .= " {$list["class"]}";
		}
		
		return $this->printCustomPageBarListLinks( $list["links"], $attrs, "			", $list["bad_hook"] );
	}
	
	function printCustomPageBarListLinks( $links, $attrs=array(), $indent='', $hook=null ) {
		$html = $indent;
		$html .= Html::openElement( 'ul', $attrs );
		$html .= "\n";
		foreach ( $links as $link ) {
			if ( isset($link["links"]) ) {
				$link["class"] = trim("{$link["class"]} hovermenu");
			}
			$liAttrs = array(
				"id" => isset($link["id"]) ? $link["id"] : null,
				"class" => isset($link["class"]) ? $link["class"] : null,
			);
			$aAttrs = array(
				"href" => $link["href"],
			);
			if ( isset($link["id"]) ) {
				$aAttrs += Linker::tooltipAndAccesskeyAttribs( $link["id"] );
			}
			$html .= "$indent	";
			$html .= Html::openElement( 'li', $liAttrs );
			if ( isset($link["icon"]) ) {
				$html .= $this->blankimg( array( "class" => "sprite {$link["icon"]}", "alt" => "" ) );
			}
			$html .= Html::element( 'a', $aAttrs, $link["text"] );
			
			if ( isset($link["links"]) ) {
				$html .= $this->blankimg();
				$html .= $this->printCustomPageBarListLinks( $link["links"], array(), "$indent	" );
			}
			
			$html .= Xml::closeElement( 'li' );
			$html .= "\n";
		}
		if ( $hook ) {
			Hooks::run( $hook );
		}
		$html .= "$indent</ul>\n";
		
		return $html;
	}

	// Made a separate method so recipes, answers, etc can override. Notably, answers turns it off.
	function printFirstHeading(){
		if ( !$this->data['skin']->isMastheadTitleVisible() ) {
			return;
		}
		$html = '<h1 id="firstHeading" class="firstHeading" aria-level="1">' . $this->get('title');
		Hooks::run( 'MonacoPrintFirstHeading' );
		$html .= '</h1>';
		return $html;
	}

	// Made a separate method so recipes, answers, etc can override.
	function printContent(){
		return $this->get('bodytext');
	}

	// Made a separate method so recipes, answers, etc can override.
	function printCategories(){
		// Display categories
		if($this->data['catlinks']) {
			return $this->get('catlinks');
		}
	}
}
