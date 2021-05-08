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
		global $wgContLang, $wgUser, $wgStyleVersion, $wgRequest, $wgTitle, $wgSitename;
		global $wgMonacoUseSitenoticeIsland;

		$skin = $this->data['skin'];
		$action = $wgRequest->getText( 'action' );
		$namespace = $wgTitle->getNamespace();

		$this->set( 'blankimg', $this->data['stylepath'] . '/Monaco/style/images/blank.gif' );

		// Suppress warnings to prevent notices about missing indexes in $this->data
		wfSuppressWarnings();
		
		$this->setupRightSidebar();
		ob_start();
		wfRunHooks( 'MonacoRightSidebar', [ $this ] );
		$this->addToRightSidebar( ob_get_contents() );
		ob_end_clean();
		
		$this->html( 'headelement' );


	$this->printAdditionalHead(); // @fixme not valid

	// this hook allows adding extra HTML just after <body> opening tag
	// append your content to $html variable instead of echoing
	$html = '';
	wfRunHooks( 'GetHTMLAfterBody', [ $this, &$html ] );
	echo $html;
?>
<div id="skiplinks"> 
	<a class="skiplink" href="#article" tabIndex=1>Skip to Content</a> 
	<a class="skiplink wikinav" href="#widget_sidebar" tabIndex=1>Skip to Navigation</a> 
</div>

	<div id="background_accent1"></div>
	<div id="background_accent2"></div>

	<!-- HEADER -->
	<?php $this->printCustomHeader(); ?>
	<div id="wikia_header" class="color2">
		<div class="monaco_shrinkwrap">
			<?php $this->printMonacoBranding(); ?>
			<?php $this->printUserData(); ?>
		</div>
	</div>

<?php if ( wfRunHooks( 'AlternateNavLinks' ) ): ?>
		<div id="background_strip" class="reset">
			<div class="monaco_shrinkwrap">

			<div id="accent_graphic1"></div>
			<div id="accent_graphic2"></div>
			</div>
		</div>
<?php endif; ?>
		<!-- /HEADER -->

		<!-- PAGE -->

	<div id="monaco_shrinkwrap_main" class="monaco_shrinkwrap with_left_sidebar<?php if ( $this->hasRightSidebar() ) { echo ' with_right_sidebar'; } ?>">
		<div id="page_wrapper">
<?php wfRunHooks( 'MonacoBeforePage', [ $this ] ); ?>
<?php $this->printBeforePage(); ?>
<?php if ( $wgMonacoUseSitenoticeIsland && $this->data['sitenotice'] ) { ?>
			<div class="page">
				<div id="siteNotice"><?php $this->html('sitenotice') ?></div>
			</div>
<?php } ?>
			<div id="wikia_page" class="page">
<?php
			$this->printMasthead();
			wfRunHooks( 'MonacoBeforePageBar', [ $this ] );
			$this->printPageBar(); ?>
					<!-- ARTICLE -->

				<article id="article" class="mw-body" role="main" aria-labelledby="firstHeading">
					<a id="top"></a>
					<?php wfRunHooks( 'MonacoAfterArticle', [ $this ] ); ?>
					<?php if ( !$wgMonacoUseSitenoticeIsland && $this->data['sitenotice'] ) { ?><div id="siteNotice"><?php $this->html( 'sitenotice' ) ?></div><?php } ?>
					<?php if ( method_exists( $this, 'getIndicators' ) ) { echo $this->getIndicators(); } ?>
					<?php $this->printFirstHeading(); ?>
					<div id="bodyContent" class="body_content">
						<h2 id="siteSub"><?php $this->msg( 'tagline' ) ?></h2>
						<?php if ( $this->data['subtitle'] ) { ?><div id="contentSub"><?php $this->html( 'subtitle' ) ?></div><?php } ?>
						<?php if ( $this->data['undelete'] ) { ?><div id="contentSub2"><?php $this->html( 'undelete' ) ?></div><?php } ?>
						<?php if ( $this->data['newtalk'] ) { ?><div class="usermessage noprint"><?php $this->html( 'newtalk' )  ?></div><?php } ?>
						<?php if ( !empty( $skin->newuemsg ) ) { echo $skin->newuemsg; } ?>

						<!-- start content -->

						<?php
						// Display content
						$this->printContent();

						$this->printCategories();
						?>
						<!-- end content -->
						<?php if ( $this->data['dataAfterContent'] ) { $this->html( 'dataAfterContent' ); } ?>
						<div class="visualClear"></div>
					</div>

				</article>
				<!-- /ARTICLE -->

			<!-- ARTICLE FOOTER -->
<?php
global $wgTitle, $wgOut;
$custom_article_footer = '';
$namespaceType = '';
wfRunHooks( 'CustomArticleFooter', [ &$this, &$tpl, &$custom_article_footer ] );
if ($custom_article_footer !== '') {
	echo $custom_article_footer;
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
?>
			<div id="articleFooter" class="reset article_footer">
				<table style="border-spacing: 0;">
					<tr>
						<td class="col1">
							<ul class="actions" id="articleFooterActions">
<?php
		if ($namespaceType == 'talk') {
			$custom_article_footer = '';
			wfRunHooks('AddNewTalkSection', array( &$this, &$tpl, &$custom_article_footer ));
			if ($custom_article_footer != '')
				echo $custom_article_footer;
		} else {
			echo "								";
			echo Html::rawElement( 'li', null,
				Html::rawElement( 'a', array( "id" => "fe_edit_icon", "href" => $wgTitle->getEditURL() ),
					$this->blankimg( array( "id" => "fe_edit_img", "class" => "sprite edit", "alt" => "" ) ) ) .
				' ' .
				Html::rawElement( 'div', null,
					wfMessage('monaco-footer-improve')->rawParams(
						Html::element( 'a', array( "id" => "fe_edit_link", "href" => $wgTitle->getEditURL() ), wfMessage('monaco-footer-improve-linktext')->text() ) )->text() ) );
			echo "\n";
		}

		// haleyjd 20140801: Rewrite to use ContextSource/WikiPage instead of wgArticle global which has been removed from MediaWiki 1.23
		$myContext = $this->getSkin()->getContext();
		if($myContext->canUseWikiPage())
		{
			$wikiPage   = $myContext->getWikiPage();
			$timestamp  = $wikiPage->getTimestamp();
			$lastUpdate = $wgLang->date($timestamp);
			$userId     = $wikiPage->getUser();
			if($userId > 0)
			{
				$user = User::newFromName($wikiPage->getUserText());
				$userPageTitle  = $user->getUserPage();
				$userPageLink   = $userPageTitle->getLocalUrl();
				$userPageExists = $userPageTitle->exists();
				$userGender     = $user->getOption("gender");
				$feUserIcon     = $this->blankimg(array( "id" => "fe_user_img", "alt" => "", "class" => ($userGender == "female" ? "sprite user-female" : "sprite user" )));
				if($userPageExists)
					$feUserIcon = Html::rawElement( 'a', array( "id" => "fe_user_icon", "href" => $userPageLink ), $feUserIcon );
?>
								<li><?php echo $feUserIcon ?> <div><?php
				// haleyjd 20171009: must use LinkRenderer for 1.28 and up
				if(class_exists('\\MediaWiki\\MediaWikiServices') && method_exists('\\MediaWiki\\MediaWikiServices', 'getLinkRenderer')) {
					$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
					echo wfMessage('monaco-footer-lastedit')->rawParams($linkRenderer->makeLink($userPageTitle, $user->getName(), array('id' => 'fe_user_link')), Html::element('time', array('datetime' => wfTimestamp(TS_ISO_8601, $$timestamp)), $lastUpdate))->escaped();
				} else {
					// TODO: remove once 1.28 is minimum supported.
					echo wfMessage('monaco-footer-lastedit')->rawParams($skin->link($userPageTitle, htmlspecialchars($user->getName()), array( "id" => "fe_user_link" )), Html::element('time', array( 'datetime' => wfTimestamp( TS_ISO_8601, $$timestamp )), $lastUpdate))->escaped();
				} ?></div></li>
<?php
			}
		}

		if($this->data['copyright'])
		{
			$feCopyIcon = $this->blankimg(array("id" => "fe_copyright_img", "class" => "sprite copyright", "alt" => ""));
?>
								<!-- haleyjd 20140425: generic copyright text support -->
								<li><?php echo $feCopyIcon ?> <div id="copyright"><?php $this->html('copyright') ?></div></li>
<?php
		}
?>
							</ul>
						</td>
						<td class="col2">
<?php            
		if(!empty($this->data['content_actions']['history']) || !empty($nav_urls['recentchangeslinked']))
		{
?>
							<ul id="articleFooterActions3" class="actions clearfix"> 
<?php
			if(!empty($this->data['content_actions']['history'])) 
			{
				$feHistoryIcon = $this->blankimg(array("id" => "fe_history_img", "class" => "sprite history", "alt" => ""));
				$feHistoryIcon = Html::rawElement("a", array("id" => "fe_history_icon", "href" => $this->data['content_actions']['history']['href']), $feHistoryIcon);
				$feHistoryLink = Html::rawElement("a", array("id" => "fe_history_link", "href" => $this->data['content_actions']['history']['href']), $this->data['content_actions']['history']['text']);
?>
								<li id="fe_history"><?php echo $feHistoryIcon ?> <div><?php echo $feHistoryLink ?></div></li>
<?php
			}
			if(!empty($nav_urls['recentchangeslinked']))
			{
				$feRecentIcon = $this->blankimg(array("id" => "fe_recent_img", "class" => "sprite recent", "alt" => ""));
				$feRecentIcon = Html::rawElement("a", array("id" => "fe_recent_icon", "href" => $nav_urls['recentchangeslinked']['href']), $feRecentIcon);
				$feRecentLink = Html::rawElement("a", array("id" => "fe_recent_link", "href" => $nav_urls['recentchangeslinked']['href']), wfMessage('recentchangeslinked')->escaped());
?>
								<li id="fe_recent"><?php echo $feRecentIcon ?> <div><?php echo $feRecentLink ?> </div></li>
<?php
			}
?>
							</ul>
<?php
		}
		if(!empty($nav_urls['permalink']) || !empty($nav_urls['whatlinkshere']))
		{
?>
							<ul id="articleFooterActions4" class="actions clearfix">
<?php
			if(!empty($nav_urls['permalink'])) 
			{
				$fePermaIcon = $this->blankimg(array("id" => "fe_permalink_img", "class" => "sprite move", "alt" => ""));
				$fePermaIcon = Html::rawElement("a", array("id" => "fe_permalink_icon", "href" => $nav_urls['permalink']['href']), $fePermaIcon);
				$fePermaLink = Html::rawElement("a", array("id" => "fe_permalink_link", "href" => $nav_urls['permalink']['href']), $nav_urls['permalink']['text']);
?>
								<li id="fe_permalink"><?php echo $fePermaIcon ?> <div><?php echo $fePermaLink ?></div></li>
<?php
			}
			if(!empty($nav_urls['whatlinkshere'])) 
			{
				$feWhatIcon = $this->blankimg(array("id" => "fe_whatlinkshere_img", "class" => "sprite pagelink", "alt" => ""));
				$feWhatIcon = Html::rawElement("a", array("id" => "fe_whatlinkshere_icon", "rel" => "nofollow", "href" => $nav_urls['whatlinkshere']['href']), $feWhatIcon);
				$feWhatLink = Html::rawElement("a", array("id" => "fe_whatlinkshere_link", "rel" => "nofollow", "href" => $nav_urls['whatlinkshere']['href']), wfMessage('whatlinkshere')->escaped());
?>
								<li id="fe_whatlinkshere"><?php echo $feWhatIcon ?> <div><?php echo $feWhatLink ?></div></li>
<?php
			}
?>
							</ul>
<?php
		}
		$feRandIcon = $this->blankimg( [ 'id' => 'fe_random_img', 'class' => 'sprite random', 'alt' => '' ] );
		$feRandIcon = Html::rawElement( 'a', [ 'id' => 'fe_random_icon', 'href' => Skin::makeSpecialUrl( 'Randompage' ) ], $feRandIcon );
		$feRandLink = Html::rawElement( 'a', [ 'id' => 'fe_random_link', 'href' => Skin::makeSpecialUrl( 'Randompage' ) ], wfMessage( 'viewrandompage' )->escaped() );
?>
							<ul class="actions clearfix" id="articleFooterActions2">
								<li id="fe_randompage"><?php echo $feRandIcon ?> <div><?php echo $feRandLink ?></div></li>
<?php
		// haleyjd 20140426: support for Extension:MobileFrontend
		if($this->get( 'mobileview' ) !== null)
		{
			$feMobileIcon = $this->blankimg( [ 'id' => 'fe_mobile_img', 'class' => 'sprite mobile', 'alt' => '' ] );
			$this->set( 'mobileview', preg_replace( '/(<a[^>]*?href[^>]*?)>/', '$1 rel="nofollow">', $this->get( 'mobileview' ) ) );
?>
								<li id="fe_mobile"><?php echo $feMobileIcon ?> <div><?php $this->html( 'mobileview' ) ?></div></li>
<?php
		}
?>
							</ul>
						</td>
					</tr>
				</table>
			</div>
<?php
	} //end $namespaceType != 'none'
} //end else from CustomArticleFooter hook
?>
				<!-- /ARTICLE FOOTER -->

			</div>
			<!-- /PAGE -->

			<noscript><link rel="stylesheet" property="stylesheet" type="text/css" href="<?php $this->text( 'stylepath' ) ?>/Monaco/style/css/noscript.css?<?php echo $wgStyleVersion ?>" /></noscript>
<?php
	if(!($wgRequest->getVal('action') != '' || $namespace == NS_SPECIAL)) {
		$this->html('JSloader');
		$this->html('headscripts');
	}
?>
		</div>
<?php $this->printRightSidebar() ?>
		<!-- WIDGETS -->
<?php 
			global $wgScriptPath; ?>
		<div id="widget_sidebar" class="reset widget_sidebar left_sidebar sidebar">
			<div id="wiki_logo" style="background-image: url(<?php echo htmlspecialchars($wgScriptPath); ?>/<? $this->html( 'logopath' ) ?>);"><a href="<?php echo htmlspecialchars($this->data['nav_urls']['mainpage']['href'])?>" accesskey="z" rel="home"><?php echo $wgSitename ?></a></div>

			<!-- SEARCH/NAVIGATION -->
			<div class="widget sidebox navigation_box" id="navigation_widget" role="navigation">
<?php
	global $wgSitename;
	$msgSearchLabel = wfMessage('Tooltip-search')->escaped();
	$searchLabel = wfMessage('Tooltip-search')->isDisabled() ? (wfMessage('ilsubmit')->escaped().' '.$wgSitename.'...') : $msgSearchLabel;
?>
			<div id="search_box" class="color1" role="search">
				<form action="<?php $this->text('searchaction') ?>" id="searchform">
					<label style="display: none;" for="searchInput"><?php echo htmlspecialchars($searchLabel) ?></label>
					<?php echo Html::input( 'search', '', 'search', array(
						'id' => "searchInput",
						'maxlength' => 200,
						'aria-label' => $searchLabel,
						'placeholder' => $searchLabel,
						'tabIndex' => 2,
						'aria-required' => 'true',
						'aria-flowto' => "search-button",
					) + Linker::tooltipAndAccesskeyAttribs('search') ); ?>
					<?php global $wgSearchDefaultFulltext; ?>
					<input type="hidden" name="<?php echo ( $wgSearchDefaultFulltext ) ? 'fulltext' : 'go'; ?>" value="1" />
					<input type="image" alt="<?php echo htmlspecialchars(wfMessage('search')->escaped()) ?>" src="<?php $this->text('blankimg') ?>" id="search-button" class="sprite search" tabIndex=2 />
				</form>
			</div>
<?php
	$monacoSidebar = new MonacoSidebar();
	if(isset($this->data['content_actions']['edit'])) {
		$monacoSidebar->editUrl = $this->data['content_actions']['edit']['href'];
	}
	echo $monacoSidebar->getCode();

	echo '<table style="border-spacing: 0;" id="link_box_table">';
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
			$specialCreatePage = SpecialPageFactory::getPage('CreatePage');
			if ( $specialCreatePage && $specialCreatePage->userCanExecute($wgUser) ) {
				$createPage = SpecialPage::getTitleFor('CreatePage');
			}
		}
		if ( isset($createPage) && ( $wgUser->isAllowed('edit') || $wgUser->isAnon() ) ) {
			/* Redirect to login page instead of showing error, see Login friction project */
			$dynamicLinksInternal["write-article"] = array(
				'url' => $wgUser->isAnon() ? SpecialPage::getTitleFor('UserLogin')->getLocalURL(array("returnto"=>$createPage->getPrefixedDBkey())) : $createPage->getLocalURL(),
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
				$url = $wgUser->isAnon() ? SpecialPage::getTitleFor('UserLogin')->getLocalURL(array("returnto"=>$uploadPage->getPrefixedDBkey())) : $uploadPage->getLocalURL();
			}
			$dynamicLinksInternal["add-image"] = array(
				'url' => $url,
				'icon' => 'photo',
			);
		}
		
		$this->extendDynamicLinks( $dynamicLinksInternal );
		wfRunHooks( 'MonacoDynamicLinks', array( $this, &$dynamicLinksInternal ) );
		$this->extendDynamicLinksAfterHook( $dynamicLinksInternal );
		
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
?>
		<tbody id="link_box_dynamic">
			<tr>
				<td colspan="2">
					<ul>
<?php
			foreach ($dynamicLinksArray as $key => $link) {
				$link['id'] = "dynamic-links-$key";
				if ( !isset($link['text']) )
					$link['text'] = wfMessage("dynamic-links-$key")->text();
				echo "						";
				echo Html::rawElement( 'li', array( "id" => "{$link['id']}-row", "class" => "link_box_dynamic_item" ),
					Html::rawElement( 'a', array( "id" => "{$link['id']}-icon", "href" => $link['url'], "tabIndex" => -1 ),
						$this->blankimg( array( "id" => "{$link['id']}-img", "class" => "sprite {$link['icon']}", "alt" => "" ) ) ) .
					' ' .
					Html::element( 'a', array( "id" => "{$link['id']}-link", "href" => $link["url"], "tabIndex" => 3 ), $link["text"] ) );
				echo "\n";
			}
?>
					</ul>
				</td>
			</tr>
		</tbody>
<?php
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
				$item['href'] = SpecialPage::getTitleFor('UserLogin')->getLocalURL(array("returnto"=>$returnto));
			}
			$i & 1 ? $linksArrayR[] = $item : $linksArrayL[] = $item;
		}
	}

	if(count($linksArrayL) > 0 || count($linksArrayR) > 0) {
?>
		<tbody id="link_box" class="color2 linkbox_static">
			<tr>
				<td>
					<ul>
<?php
		if(is_array($linksArrayL) && count($linksArrayL) > 0) {
			foreach($linksArrayL as $key => $val) {
				if ($val === false) {
					echo '<li>&nbsp;</li>';
				} else {
?>
						<li><a<?php if ( !isset($val['internal']) || !$val['internal'] ) { ?> rel="nofollow"<?php } ?> href="<?php echo htmlspecialchars($val['href']) ?>" tabIndex=3><?php echo htmlspecialchars($val['text']) ?></a></li>
<?php
				}
			}
		}
?>
					</ul>
				</td>
				<td>
					<ul>
<?php
		if(is_array($linksArrayR) && count($linksArrayR) > 0) {
		    foreach($linksArrayR as $key => $val) {
				if ($val === false) {
					echo '<li>&nbsp;</li>';
				} else {
?>
						<li><a<?php if ( !isset($val['internal']) || !$val['internal'] ) { ?> rel="nofollow"<?php } ?> href="<?php echo htmlspecialchars($val['href']) ?>" tabIndex=3><?php echo htmlspecialchars($val['text']) ?></a></li>
<?php
				}
			}
		}
?>
						<li style="font-size: 1px; position: absolute; top: -10000px"><a href="<?php echo Title::newFromText('Special:Recentchanges')->getLocalURL() ?>" accesskey="r">Recent changes</a><a href="<?php echo Title::newFromText('Special:Random')->getLocalURL() ?>" accesskey="x">Random page</a></li>
					</ul>
				</td>
			</tr>
			<!-- haleyjd 20140420: FIXME: DoomWiki.org-specific; make generic! -->
			<!--
			<tr>
				<td colspan="2" style="text-align:center;">
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="D5MLUSDXA8HMQ">
						<input type="image" src="<?php $this->text('stylepath') ?>/Monaco/style/images/contribute-button.png" name="submit" alt="PayPal - The safer, easier way to pay online!" style="border: 0; width:139px; margin:0;">
						<img alt="" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1" style="border: 0;">
					</form>
				</td>
			</tr>
			-->
		</tbody>
<?php
	}
	// END: create static box
?>
	</table>
			</div>
			<!-- /SEARCH/NAVIGATION -->
<?php		$this->printExtraSidebar(); ?>
<?php		wfRunHooks( 'MonacoSidebarEnd', [ $this ] ); ?>

		</div>
		<!-- /WIDGETS -->
	<!--/div-->
<?php

// curse like cobranding
$this->printCustomFooter();
?>

<?php

echo '</div>';

$this->html('bottomscripts'); /* JS call to runBodyOnloadHook */
wfRunHooks('SpecialFooter');
?>
		<div id="positioned_elements" class="reset"></div>
<?php
$this->delayedPrintCSSdownload();
$this->html( 'reporttime' );
?>

	</body>
</html>
<?php
	} // end execute()

	//@author Marooned
	function delayedPrintCSSdownload() {
		global $wgRequest;

		//regular download
		if ($wgRequest->getVal('printable')) {
			// RT #18411
			$this->html('mergedCSSprint');
			// RT #25638
			echo "\n\t\t";
			$this->html('csslinksbottom');
		} else {
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
		echo $box;
	}
	
	function customBox( $bar, $cont ) {
		$this->sidebarBox( $bar, $cont );
	}
	
	// hook for subskins
	function setupRightSidebar() {}
	
	function addToRightSidebar($html) {
		$this->mRightSidebar .= $html;
	}
	
	function hasRightSidebar() {
		return (bool)trim($this->mRightSidebar);
	}
	
	// Hook for things that you only want in the sidebar if there are already things
	// inside the sidebar.
	function lateRightSidebar() {}
	
	function printRightSidebar() {
		if ( $this->hasRightSidebar() ) {
?>
		<!-- RIGHT SIDEBAR -->
		<div id="right_sidebar" class="sidebar right_sidebar">
<?php $this->lateRightSidebar(); ?>
<?php wfRunHooks('MonacoRightSidebar::Late', array($this)); ?>
<?php echo $this->mRightSidebar ?>
		</div>
		<!-- /RIGHT SIDEBAR -->
<?php
		}
	}
	
	function printMonacoBranding() {
		ob_start();
		wfRunHooks( 'MonacoBranding', array( $this ) );
		$branding = ob_get_contents();
		ob_end_clean();
		
		if ( trim($branding) ) { ?>
			<div id="monacoBranding">
<?php echo $branding; ?>
			</div>
<?php
		}
	}
	
	function printUserData() {
		$skin = $this->data['skin'];
		?>
			<div id="userData">
<?php
		
		$custom_user_data = "";
		if( !wfRunHooks( 'CustomUserData', array( &$this, &$tpl, &$custom_user_data ) ) ){
			wfDebug( __METHOD__ . ": CustomUserData messed up skin!\n" );
		}
		
		if( $custom_user_data ) {
			echo $custom_user_data;
		} else {
			global $wgUser;
			
			// Output the facebook connect links that were added with PersonalUrls.
			// @author Sean Colombo
			foreach($this->data['userlinks'] as $linkName => $linkData){
				// 
				if( !empty($linkData['html']) ){
					echo $linkData['html']; 
				}
			}
			
			if ($wgUser->isLoggedIn()) {
				// haleyjd 20140420: This needs to use $key => $value syntax to get the proper style for the elements!
				foreach( array( "username" => "userpage", "mytalk" => "mytalk", "watchlist" => "watchlist" ) as $key => $value ) {
					echo "				" . Html::rawElement( 'span', array( 'id' => "header_$key" ),
						Html::element( 'a', array( 'href' => $this->data['userlinks'][$value]['href'] ) + Linker::tooltipAndAccesskeyAttribs("pt-$value"), $this->data['userlinks'][$value]['text'] ) ) . "\n";
				}
				
			?>
<?php
				if ( $this->useUserMore() ) { ?>
				<span class="more hovermenu">
					<button id="headerButtonUser" class="header-button color1" tabIndex="-1"><?php echo trim(wfMessage('moredotdotdot')->escaped(), ' .') ?><img src="<?php $this->text('blankimg') ?>" /></button>
					<span class="invisibleBridge"></span>
					<div id="headerMenuUser" class="headerMenu color1 reset">
						<ul>
<?php
				foreach ( $this->data['userlinks']['more'] as $key => $link ) {
					if($key != 'userpage') { // haleyjd 20140420: Do not repeat user page here.
						echo Html::rawElement( 'li', array( 'id' => "header_$key" ),
							Html::element( 'a', array( 'href' => $link['href'] ), $link['text'] ) ) . "\n";
					}
				} ?>
						</ul>
					</div>
				</span>
<?php
				} else {
					foreach ( $this->data['userlinks']['more'] as $key => $link ) {
						if($key != 'userpage') { // haleyjd 20140420: Do not repeat user page here.
							echo Html::rawElement( 'span', array( 'id' => "header_$key" ),
								Html::element( 'a', array( 'href' => $link['href'] ), $link['text'] ) ) . "\n";
						}
					} ?>
<?php
				} ?>
				<span>
					<?php echo Html::element( 'a', array( 'href' => $this->data['userlinks']['logout']['href'] ) + Linker::tooltipAndAccesskeyAttribs('pt-logout'), $this->data['userlinks']['logout']['text'] ); ?>
				</span>
<?php
			} else {
?>
				<span id="userLogin">
					<a class="wikia-button" id="login" href="<?php echo htmlspecialchars($this->data['userlinks']['login']['href']) ?>"><?php echo htmlspecialchars($this->data['userlinks']['login']['text']) ?></a>
				</span>

					<a class="wikia-button" id="register" href="<?php echo htmlspecialchars($this->data['userlinks']['register']['href']) ?>"><?php echo htmlspecialchars($this->data['userlinks']['register']['text']) ?></a>

<?php
			}
		} ?>
			</div>
<?php
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
		?>
			<div id="user_masthead" class="accent reset clearfix">
				<div id="user_masthead_head" class="clearfix">
					<h2><?php echo htmlspecialchars($username); ?>
<?php if ( $user->isAnon() ) { ?>
						<small id="user_masthead_anon"><?php echo $user->getName(); ?></small>
<?php } else { ?>
						<div id="user_masthead_scorecard" class="dark_text_1"><?php echo htmlspecialchars($editcount); ?></div>
<?php } ?>
					</h2>
				</div>
				<ul id="user_masthead_tabs" class="nav_links">
<?php
				foreach ( $this->data['articlelinks']['right'] as $navLink ) {
					$class = "color1";
					if ( isset($navLink["class"]) ) {
						$class .= " {$navLink["class"]}";
					}
					echo Html::rawElement( 'li', array( "class" => $class ),
						Html::element( 'a', array( "href" => $navLink["href"] ), $navLink["text"] ) );
				} ?>
				</ul>
			</div>
<?php
		unset($this->data['articlelinks']['right']); // hide the right articlelinks since we've already displayed them
	}

	// Made a separate method so recipes, answers, etc can override. Notably, answers turns it off.
	function printPageBar(){
		// Allow for other skins to conditionally include it
		$this->realPrintPageBar();
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
			global $wgContLang;
			$preferred = $wgContLang->getPreferredVariant();
			$bar[] = array(
				"id" => "page_variants",
				"type" => "tabs",
				"class" => "page_variants",
				"links" => array(
					array(
						"class" => 'selected',
						"text" => $wgContLang->getVariantname( $preferred ),
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
		$this->printCustomPageBar( $bar );
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
		
		echo "		";
		echo Html::openElement( 'div', array( "id" => $isPrimary ? "page_bar" : null, "class" => $divClass ) );
		echo "\n";
		if ( !$useCompactBar || !isset($deferredList) ) {
			foreach ( $bar as $list ) {
				$this->printCustomPageBarList( $list );
			}
		}
		echo "		</div>\n";
		if ( isset($deferredList) ) {
			$this->printCustomPageBarList( $deferredList );
		}
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
		
		$this->printCustomPageBarListLinks( $list["links"], $attrs, "			", $list["bad_hook"] );
	}
	
	function printCustomPageBarListLinks( $links, $attrs=array(), $indent='', $hook=null ) {
		echo $indent;
		echo Html::openElement( 'ul', $attrs );
		echo "\n";
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
			echo "$indent	";
			echo Html::openElement( 'li', $liAttrs );
			if ( isset($link["icon"]) ) {
				echo $this->blankimg( array( "class" => "sprite {$link["icon"]}", "alt" => "" ) );
			}
			echo Html::element( 'a', $aAttrs, $link["text"] );
			
			if ( isset($link["links"]) ) {
				echo $this->blankimg();
				$this->printCustomPageBarListLinks( $link["links"], array(), "$indent	" );
			}
			
			echo Xml::closeElement( 'li' );
			echo "\n";
		}
		if ( $hook ) {
			wfRunHooks( $hook );
		}
		echo "$indent</ul>\n";
	}

	// Made a separate method so recipes, answers, etc can override. Notably, answers turns it off.
	function printFirstHeading(){
		if ( !$this->data['skin']->isMastheadTitleVisible() ) {
			return;
		}
		?><h1 id="firstHeading" class="firstHeading" aria-level="1"><?php $this->html('title');
		wfRunHooks( 'MonacoPrintFirstHeading' );
		?></h1><?php
	}

	// Made a separate method so recipes, answers, etc can override.
	function printContent(){
		$this->html('bodytext');
	}

	// Made a separate method so recipes, answers, etc can override.
	function printCategories(){
		// Display categories
		if($this->data['catlinks']) {
			$this->html('catlinks');
		}
	}

}
