<?php
/**
 * Monaco Skin Right Sidebar Extension
 *
 * @package MediaWiki
 * @subpackage Skins
 *
 * @author Daniel Friesen
 * @author James Haley
 */

if(!defined('MEDIAWIKI'))
  die( "This is an extension to the MediaWiki package and cannot be run standalone." );

$wgExtensionCredits['parserhook'][] = array (
  'path' => __FILE__,
  'name' => 'ContentRightSidebar',
  'author' => array('[http://mediawiki.org/wiki/User:Dantman Daniel Friesen]', '[http://doomwiki.org/wiki/User:Quasar James Haley]'),
  'descriptionmsg' => 'contentrightsidebar-desc',
  'url' => "https://github.com/haleyjd/monaco-port",
);

$wgMessagesDirs['ContentRightSidebar'] = __DIR__ . '/crs.i18n';

/**
 * Resource module for ContentRightSidebar
 * haleyjd 20140423
 */
$wgResourceModules['skins.monaco.ContentRightSidebar'] = array (
  'scripts' => 'skins/monaco/style/js/ContentRightSidebar.js',
);

define('RIGHT_SIDEBAR_START_TOKEN', "<!-- RIGHT SIDEBAR START -->");
define('RIGHT_SIDEBAR_END_TOKEN', "<!-- RIGHT SIDEBAR END -->");
define('RIGHT_SIDEBAR_WITHBOX_TOKEN', "<!-- RIGHT SIDEBAR WITHBOX -->");
define('RIGHT_SIDEBAR_TITLE_START_TOKEN', "<!-- RIGHT SIDEBAR TITLE START>");
define('RIGHT_SIDEBAR_TITLE_END_TOKEN', "<RIGHT SIDEBAR TITLE END -->");
define('RIGHT_SIDEBAR_CLASS_START_TOKEN', "<!-- RIGHT SIDEBAR CLASS START>");
define('RIGHT_SIDEBAR_CLASS_END_TOKEN', "<RIGHT SIDEBAR CLASS END -->");
define('RIGHT_SIDEBAR_CONTENT_START_TOKEN', "<!-- RIGHT SIDEBAR CONTENT START -->");
define('RIGHT_SIDEBAR_CONTENT_END_TOKEN', "<!-- RIGHT SIDEBAR CONTENT END -->");

class MonacoContentRightSidebar
{
  /**
   * BeforePageDisplay hook handler
   * @author James Haley
   */
  public static function BeforePageDisplay(&$out, &$skin)
  {
    if($skin->getSkinName() == 'monaco')
      $out->addModules(array('skins.monaco.ContentRightSidebar'));
  }

  /**
   * Register parser extensions
   */
  public static function ContentRightSidebarRegisterParser(&$parser) 
  {
    $parser->setHook('right-sidebar', 'MonacoContentRightSidebar::ContentRightSidebarTag');
    return true;
  }

  /**
   * Parser extension callback
   */
  public static function ContentRightSidebarTag($input, $arg, $parser, $frame) 
  {
    $isContentTagged = false;
    $m = array();
    if(preg_match( '#^(.*)<content>(.*?)</content>(.*)$#is', $input, $m)) 
    {
      $isContentTagged = true;

      $startUniq = $parser->uniqPrefix() . "-right-sidebar-content-start-" . Parser::MARKER_SUFFIX;
      $endUniq = $parser->uniqPrefix() . "-right-sidebar-content-end-" . Parser::MARKER_SUFFIX;
      $input = "{$m[1]}{$startUniq}{$m[2]}{$endUniq}{$m[3]}";
      $input = $parser->recursiveTagParse( $input, $frame );
      $input = str_replace($startUniq, RIGHT_SIDEBAR_CONTENT_START_TOKEN, $input);
      $input = str_replace($endUniq, RIGHT_SIDEBAR_CONTENT_END_TOKEN, $input);
    }
    else
    {
      $input = $parser->recursiveTagParse( $input, $frame );
    }

    $with_box = (isset($arg["with-box"]) ? $arg["with-box"] : (isset($arg["withbox"]) ? $arg["withbox"] : null));

    $out  = RIGHT_SIDEBAR_START_TOKEN;
    if($with_box && !in_array(strtolower($with_box), array("false", "off", "no", "none")))
    {
      $out .= RIGHT_SIDEBAR_WITHBOX_TOKEN;
    }
    if(isset($arg["title"]))
    {
      $out .= RIGHT_SIDEBAR_TITLE_START_TOKEN . urlencode($arg["title"]) . RIGHT_SIDEBAR_TITLE_END_TOKEN;
    }
    if(isset($arg["class"]))
    {
      $out .= RIGHT_SIDEBAR_CLASS_START_TOKEN . urlencode($arg["class"]) . RIGHT_SIDEBAR_CLASS_END_TOKEN;
    }
    if($isContentTagged)
    {
      $out .= $input;
    } 
    else
    {
      $out .= '<div style="float: right; clear: right; position: relative;">';
      $out .= RIGHT_SIDEBAR_CONTENT_START_TOKEN . $input . RIGHT_SIDEBAR_CONTENT_END_TOKEN;
      $out .= '</div>';
    }
    $out .= RIGHT_SIDEBAR_END_TOKEN;

    return $out;
  }

  /**
   * Private function for extraction of the right sidebar content from an article
   */
  private static function ExtractRightSidebarBoxes(&$html)
  {
    $boxes = array();

    while(true) 
    {
      $withBox = false;
      $title = '';
      $class = null;

      $start = strpos($html, RIGHT_SIDEBAR_START_TOKEN);
      if($start === false)
        break;
      $end = strpos($html, RIGHT_SIDEBAR_END_TOKEN, $start);
      if($end === false)
        break;
      $content = substr($html, $start, $end-$start);
      if(strpos($content, RIGHT_SIDEBAR_WITHBOX_TOKEN) !== false)
      {
        $withBox = true;
      }
      $startTitle = strpos($content, RIGHT_SIDEBAR_TITLE_START_TOKEN);
      if($startTitle !== false)
      {
        $endTitle = strpos($content, RIGHT_SIDEBAR_TITLE_END_TOKEN, $startTitle);
        if($endTitle !== false)
        {
          $title = urldecode(substr($content, $startTitle+strlen(RIGHT_SIDEBAR_TITLE_START_TOKEN), $endTitle-$startTitle-strlen(RIGHT_SIDEBAR_TITLE_START_TOKEN)));
        }
      }
      $startClass = strpos($content, RIGHT_SIDEBAR_CLASS_START_TOKEN);
      if($startClass !== false)
      {
        $endClass = strpos($content, RIGHT_SIDEBAR_CLASS_END_TOKEN, $startClass);
        if($endClass !== false)
        {
          $class = urldecode(substr($content, $startClass+strlen(RIGHT_SIDEBAR_CLASS_START_TOKEN), $endClass-$startClass-strlen(RIGHT_SIDEBAR_CLASS_START_TOKEN)));
        }
      }
      $contentStart = strpos($content, RIGHT_SIDEBAR_CONTENT_START_TOKEN);
      if($contentStart !== false)
      {
        $content = substr($content, $contentStart+strlen(RIGHT_SIDEBAR_CONTENT_START_TOKEN));
      }
      $contentEnd = strpos($content, RIGHT_SIDEBAR_CONTENT_END_TOKEN);
      if($contentStart !== false)
      {
        $content = substr($content, 0, $contentEnd);
      }
      $boxes[] = array( "with-box" => $withBox, "title" => $title, "class" => $class, "content" => $content );
      $html = substr($html, 0, $start) . substr($html, $end+strlen(RIGHT_SIDEBAR_END_TOKEN));
    }

    return $boxes;
  }

  /**
   * MonacoRightSidebar custom hook handler. This is invoked by the Monaco skin 
   * to add the right sidebar. If this hook is not invoked, then right sidebar 
   * content renders as a right-floating box inside the article.
   */
  public static function ContentRightSidebarMonacoRightSidebar( $sk )
  {
    $boxes = self::ExtractRightSidebarBoxes($sk->data['bodytext']);

    foreach($boxes as $box)
    {
      if($box["with-box"])
      {
        $attrs = array();
        if(isset($box["class"]))
        {
          $attrs["class"] = $box["class"];
        }
        $sk->sidebarBox($box["title"], $box["content"], $attrs);
      }
      else 
      {
        echo $box["content"];
      }
    }

    return true;
  }
}

// Register hooks
$wgHooks['BeforePageDisplay'  ][] = 'MonacoContentRightSidebar::BeforePageDisplay';
$wgHooks['ParserFirstCallInit'][] = 'MonacoContentRightSidebar::ContentRightSidebarRegisterParser'; 
$wgHooks['MonacoRightSidebar' ][] = 'MonacoContentRightSidebar::ContentRightSidebarMonacoRightSidebar';

