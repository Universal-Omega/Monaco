<?php

use MediaWiki\MediaWikiServices;

class MonacoSidebar {

	const version = '0.10';

 	/**
	 * @return bool
	 */
	public static function invalidateCache() {
		$memc = ObjectCache::getLocalClusterInstance();

		$memc->delete( $memc->makeKey( 'mMonacoSidebar', self::version ) );

		return true;
	}

	public $editUrl = false;

	/**
	 * Parse one line from MediaWiki message to array with indexes 'text' and 'href'
	 *
	 * @param string $line
	 * @return array
	 */
	public static function parseItem( $line ) {
		$href = $specialCanonicalName = false;

		$line_temp = explode( '|', trim( $line, '* ' ), 3 );
		$line_temp[0] = trim( $line_temp[0], '[]' );
		if ( count( $line_temp ) >= 2 && $line_temp[1] != '' ) {
			$line = trim( $line_temp[1] );
			$link = trim( wfMessage( $line_temp[0] )->inContentLanguage()->text() );
		} else {
			$line = trim( $line_temp[0] );
			$link = trim( $line_temp[0] );
		}


		$descText = null;

		if ( count( $line_temp ) > 2 && $line_temp[2] != '' ) {
			$desc = $line_temp[2];
			if ( wfMessage( $desc )->exists() ) {
				$descText = wfMessage( $desc )->text();
			} else {
				$descText = $desc;
			}
		}

		if ( wfMessage( $line )->exists() ) {
			$text = wfMessage( $line )->text();
		} else {
			$text = $line;
		}

		if ( $link != null ) {
			if ( !wfMessage( $line_temp[0] )->exists() ) {
				$link = $line_temp[0];
			}
			if ( preg_match( '/^(?:' . wfUrlProtocols() . ')/', $link ) ) {
				$href = $link;
			} else {
				$title = Title::newFromText( $link );
				if ( $title ) {
					if ( $title->getNamespace() == NS_SPECIAL ) {
						$specialPageFactory = MediaWikiServices::getInstance()->getSpecialPageFactory();
						$dbkey = $title->getDBkey();

						list( $specialCanonicalName, /*$par*/ ) = $specialPageFactory->resolveAlias( $dbkey );

						if ( !$specialCanonicalName ) {
							$specialCanonicalName = $dbkey;
						}
					}

					$title = $title->fixSpecialName();
					$href = $title->getLocalURL();
				} else {
					$href = '#';
				}
			}
		}

		return [ 'text' => $text, 'href' => $href, 'org' => $line_temp[0], 'desc' => $descText, 'specialCanonicalName' => $specialCanonicalName ];
	}

	/**
	 * @param string $messageKey
	 * @return array|null
	 */
	public static function getMessageAsArray( $messageKey ) {
		$message = trim( wfMessage( $messageKeyv)->inContentLanguage()->text() );

		if ( !wfMessage( $messageKey )->inContentLanguage()->isBlank() ) {
			$lines = explode( "\n", $message );

			if ( count( $lines ) > 0) {
				return $lines;
			}
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function getCode() {
		global $wgUser, $wgTitle, $wgRequest;

		$memc = ObjectCache::getLocalClusterInstance();

		$contLang = MediaWikiServices::getInstance()->getContentLanguage();
		$lang = RequestContext::getMain()->getLanguage();
        
		$cache = $lang->getCode() == $contLang->getCode();
		if ( $cache ) {
			$key = $memc->makeKey( 'mMonacoSidebar', self::version );
			$menu = $memc->get( $key );
		}

		if ( empty( $menu ) ) {
			$menu = $this->getMenu( $this->getMenuLines() );

			if ( $cache ) {
				$memc->set( $key, $menu, 60 * 60 * 8 );
			}
		}

		return $menu;
	}

	/**
	 * @return array
	 */
	public function getMenuLines() {
		/* # if a local copy exists, try to use that first
		$revision = Revision::newFromTitle( Title::newFromText( 'Monaco-sidebar', NS_MEDIAWIKI ) );
		if ( is_object( $revision ) && trim( $revision->getText() ) != '' ) {
			$lines = self::getMessageAsArray( 'Monaco-sidebar' );
		} */

		# if we STILL have no menu lines, fall back to just loading the default from the message system
		if ( empty( $lines ) ) {
			$lines = self::getMessageAsArray( 'Monaco-sidebar' );
		}

		return $lines;
	}

	/**
	 * @param array $nodes
	 * @param array $children
	 * @return string
	 */
	public function getSubMenu( $nodes, $children ) {
		$menu = '';

		foreach ( $children as $key => $val ) {
			$link_html = htmlspecialchars($nodes[$val]['text']);
			if ( !empty( $nodes[$val]['children'] ) ) {
				$link_html .= '<em>&rsaquo;</em>';
			}
			
			$menu_item =
				Html::rawElement( 'a', [
						'href' => !empty( $nodes[$val]['href'] ) ? $nodes[$val]['href'] : '#',
						'class' => $nodes[$val]['class'],
						'tabIndex' => 3,
						'rel' => $nodes[$val]['internal'] ? null : 'nofollow'
					], $link_html ) . "\n";
			if ( !empty( $nodes[$val]['children'] ) ) {
				$menu_item .= $this->getSubMenu( $nodes, $nodes[$val]['children'] );
			}

			$menu .=
				Html::rawElement( 'li', [ 'class' => 'menu-item' ], $menu_item );
		}

		$menu = Html::rawElement( 'ul', [ 'class' => 'sub-menu widget' ], $menu );

		return $menu;
	}

	public function getMenu($lines, $userMenu = false) {
		global $wgScript;

        $nodes = $this->parseSidebar( $lines );
        
		if ( count( $nodes ) > 0 ) {
			
			Hooks::run( 'MonacoSidebarGetMenu', [ &$nodes ] );
			
			$mainMenu = array();
			foreach($nodes[0]['children'] as $key => $val) {
				if(isset($nodes[$val]['children'])) {
					$mainMenu[$val] = $nodes[$val]['children'];
				}
				if(isset($nodes[$val]['magic'])) {
					$mainMenu[$val] = $nodes[$val]['magic'];
				}
				if(isset($nodes[$val]['href']) && $nodes[$val]['href'] == 'editthispage') $menu .= '<!--b-->';
				$menu .= '<li id="menu-item_'.$val.'" class="menu-item';
				if ( !empty($nodes[$val]['children']) || !empty($nodes[$val]['magic']) ) {
					$menu .= ' with-sub-menu';
				}
				$menu .= '">';
				$menu .= '<a id="a-menu-item_'.$val.'" href="'.(!empty($nodes[$val]['href']) ? htmlspecialchars($nodes[$val]['href']) : '#').'"';
				if ( !isset($nodes[$val]['internal']) || !$nodes[$val]['internal'] )
					$menu .= ' rel="nofollow"';
				$menu .= ' tabIndex=3>'.htmlspecialchars($nodes[$val]['text']);
				if ( !empty($nodes[$val]['children']) || !empty($nodes[$val]['magic']) ) {
					$menu .= '<em>&rsaquo;</em>';
				}
				$menu .= '</a>';
				if ( !empty($nodes[$val]['children']) || !empty($nodes[$val]['magic']) ) {
					$menu .= $this->getSubMenu($nodes, $nodes[$val]['children']);
				}
				$menu .= '</li>';
				if(isset($nodes[$val]['href']) && $nodes[$val]['href'] == 'editthispage') $menu .= '<!--e-->';
			}
			
			$classes = array();
			if ( $userMenu )
				$classes[] = 'userMenu';
			$classes[] = 'hover-navigation';
			$menu = Html::rawElement( 'ul', null, $menu );
			$menu = Html::rawElement( 'nav', array( 'id' => 'navigation', 'class' => implode(' ', $classes) ), $menu );

			if($this->editUrl) {
				$menu = str_replace('href="editthispage"', 'href="'.$this->editUrl.'"', $menu);
			} else {
				$menu = preg_replace('/<!--b-->(.*)<!--e-->/U', '', $menu);
			}

			if(isset($nodes[0]['magicWords'])) {
				$magicWords = $nodes[0]['magicWords'];
				$magicWords = array_unique($magicWords);
				sort($magicWords);
			}

			$menuHash = hash('md5', serialize($nodes));

			foreach($nodes as $key => $val) {
				if(!isset($val['depth']) || $val['depth'] == 1) {
					unset($nodes[$key]);
				}
				unset($nodes[$key]['parentIndex']);
				unset($nodes[$key]['depth']);
				unset($nodes[$key]['original']);
			}

			$nodes['mainMenu'] = $mainMenu;
			if(!empty($magicWords)) {
				$nodes['magicWords'] = $magicWords;
			}

			$memc = ObjectCache::getLocalClusterInstance();

			$memc->set( $menuHash, $nodes, 60 * 60 * 24 * 3 ); // three days

			return $menu;
		}
	}

	public function handleMagicWord(&$node) {
		$original_lower = strtolower($node['original']);
		if(in_array($original_lower, array('#voted#', '#popular#', '#visited#', '#newlychanged#', '#topusers#'))) {
			if($node['text'][0] == '#') {
				$node['text'] = wfMessage(trim($node['original'], ' *'))->text(); // TODO: That doesn't make sense to me
			}
			$node['magic'] = trim($original_lower, '#');
			return true;
		} else if(substr($original_lower, 1, 8) == 'category') {
			$param = trim(substr($node['original'], 9), '#');
			if(is_numeric($param)) {
				$category = $this->getBiggestCategory($param);
				$name = $category['name'];
			} else {
				$name = substr($param, 1);
			}
			if($name) {
				$node['href'] = Title::makeTitle(NS_CATEGORY, $name)->getLocalURL();
				if($node['text'][0] == '#') {
					$node['text'] = str_replace('_', ' ', $name);
				}
				$node['magic'] = 'category'.$name;
				return true;
			}
		}

		return false;
	}

    /**
     * Grab the sidebar for the current user
     * User:<username>/Monaco-sidebar
     *
     * Adapted from Extension:DynamicSidebar
     * 
     * @param User $user
     * @return array|string
     **/
    private function doUserSidebar( User $user ) {
		$username = $user->getName();

 		// does 'User:<username>/Sidebar' page exist?
		$title = Title::makeTitle( NS_USER, $username . '/Monaco-sidebar' );
		if ( !$title->exists() ) {
			// Remove this sidebar if not
			return '';
		}

		$revid = $title->getLatestRevID();
		$a = new Article( $title, $revid );

		return explode( "\n", ContentHandler::getContentText( $a->getPage()->getContent() ) );
    }

	/**
	 * Grabs the sidebar for the current user's groups
	 *
	 * @param User $user
	 * @return array|string
	 */
	private static function doGroupSidebar( User $user ) {
		// Get group membership array.
		$groups = $user->getEffectiveGroups();
		Hooks::run( 'DynamicSidebarGetGroups', [ &$groups ] );
		// Did we find any groups?
		if ( count( $groups ) == 0 ) {
			// Remove this sidebar if not
			return '';
		}

		$text = '';
		foreach ( $groups as $group ) {          
			// Form the path to the article:
			// MediaWiki:Monaco-sidebar/<group>
			$title = Title::makeTitle( NS_MEDIAWIKI, 'Monaco-sidebar/Group:' . $group );
			if ( !$title->exists() ) {
				continue;
			}

			$revid = $title->getLatestRevID();
			$a = new Article( $title, $revid );
			$text .= ContentHandler::getContentText( $a->getPage()->getContent() ) . "\n";

		}

		return explode( "\n", $text );
	}

    /**
     * Parse Sidebar Lines
     *
     * @param array $lines
     * @return array
     */
    public function parseSidebar( $lines ) {
        global $wgUser;
   
  		$nodes = [];
		$lastDepth = 0;
		$i = 0;
		if(is_array($lines) && count($lines) > 0) {
			foreach($lines as $line) {
				if(trim($line) === '') {
					continue; // ignore empty lines
				}
                
				$node = $this->parseSidebarLine($line);
                $node = $this->addDepthParentToNode($line,$node,$nodes,$i,$lastDepth);
               
                // expand to user sidebar
                if($node['original'] == "USER-SIDEBAR")
                    {
                        $this->processSpecialSidebar($this->doUserSidebar($wgUser),$lastDepth, $nodes, $i);
                        // we don't add the placeholder, we add the menu which is behind it
                        continue;
                    }
                // expand to group sidebar
                if($node['original'] == "GROUP-SIDEBAR")
                    {
                        $this->processSpecialSidebar($this->doGroupSidebar($wgUser),$lastDepth, $nodes, $i);
                        // we don't add the placeholder, we add the menu which is behind it
                        continue;
                    }
                
				if($node['original'] == 'editthispage') {
					$node['href'] = 'editthispage';
					if($node['depth'] == 1) {
						$nodes[0]['editthispage'] = true; // we have to know later if there is editthispage special word used in first level
					}
				} else if(!empty( $node['original'] ) && $node['original'][0] == '#') {
					if($this->handleMagicWord($node)) {
						$nodes[0]['magicWords'][] = $node['magic'];
						if($node['depth'] == 1) {
							$nodes[0]['magicWord'] = true; // we have to know later if there is any magic word used if first level
						}
					} else {
						continue;
					}
				}

                $i = $this->addNodeToSidebar($node,$nodes,$i,$lastDepth);
            }
		}
        
		return $nodes;      
    }

	/**
	 * Parse Line of Sidebar
	 *
	 * @param string $line
	 * @param array $ret
	 */
	public function parseSidebarLine( $line ) {
		$lineTmp = explode( '|', trim( $line, '* ' ), 2 );
		$lineTmp[0] = trim( $lineTmp[0], '[]' ); // for external links defined as [http://example.com] instead of just http://example.com

		$internal = false;

		if ( count( $lineTmp ) == 2 && $lineTmp[1] != '' ) {
			$link = trim( wfMessage( $lineTmp[0] )->inContentLanguage()->text() );
			$line = trim( $lineTmp[1] );
		} else {
			$link = trim ( $lineTmp[0] );
			$line = trim ( $lineTmp[0] );
		}

		if ( wfMessage( $line )->exists() ) {
			$text = wfMessage( $line )->text();
		} else {
			$text = $line;
		}

		if ( !wfMessage( $lineTmp[0] )->exists() ) {
			$link = $lineTmp[0];
		}

		if ( preg_match( '/^(?:' . wfUrlProtocols() . ')/', $link ) ) {
			$href = $link;
		} else {
			if ( empty( $link ) ) {
				$href = '#';
			} elseif ( $link[0] == '#' ) {
				$href = '#';
			} else {
				$title = Title::newFromText( $link );
				if ( is_object( $title ) ) {
					$href = $title->fixSpecialName()->getLocalURL();
					$internal = true;
				} else {
					$href = '#';
				}
			}
		}

		$ret = [ 'original' => $lineTmp[0], 'text' => $text ];
		$ret['href'] = $href;
		$ret['internal'] = $internal;

		return $ret;
	}

    /**
     * Process a list of elements and add them to the corrent position in the current menu
     *
     * @param array $lines
     * @param int
     * @param array $nodes
     * @param int $i
     */
    public function processSpecialSidebar( $lines, &$lastDepth, &$nodes, &$i ) {
        if ( is_array( $lines ) && count( $lines) > 0 ) {
            foreach ( $lines as $line ) {
                if ( trim( $line ) === '' ) {
                    continue; // skip empty lines, goto next line
                }
                
                // convert line into small array
                $node = $this->parseSidebarLine( $line );

                $node = $this->addDepthParentToNode( $line, $node, $nodes, $i, $lastDepth );
                $i = $this->addNodeToSidebar( $node, $nodes, $i, $lastDepth );
            }
        }

        return;
    }

    /**
     * Calculate and add the depth of the current node.
     * Set the array index of the parent node to the current node
     *
     * @param string $line
     * @param array $node
     * @param array $nodes
     * @param int $index
     * @param int $lastDepth
     * @return array $node 
     */
    public function addDepthParentToNode( $line, $node, &$nodes, &$index, &$lastDepth ) {
        // calculate the depth of this node in the menu
        $node['depth'] = strrpos( $line, '*' ) + 1;
        
        if ( $node['depth'] == $lastDepth ) {
            $node['parentIndex'] = $nodes[$index]['parentIndex'];
        } elseif ( $node['depth'] == $lastDepth + 1 ) {
            $node['parentIndex'] = $index;
        } else {
            for ( $x = $index; $x >= 0; $x-- ) {
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

        return $node;
    }

	/**
	 * Add Node as newest Item of the Menu
	 *
	 * @param array $node
	 * @param array $nodes
	 * @param int $index
	 * @param int $lastDepth
	 * @return int $i
	 */
	public function addNodeToSidebar( $node, &$nodes, $index, &$lastDepth ) {
		$nodes[$index+1] = $node;
		$nodes[ $node['parentIndex'] ]['children'][] = $index+1;
		$lastDepth = $node['depth'];
		$index++;

		return $index;
	}

	private $biggestCategories;

	public function getBiggestCategory( $index ) {
		global $wgBiggestCategoriesBlacklist;

		$memc = ObjectCache::getLocalClusterInstance();

		$limit = max( $index, 15 );
		if ( $limit > count( $this->biggestCategories ) ) {
			$key = $memc->makeKey( 'biggest', $limit );
			$data = $memc->get( $key );
			if ( empty( $data ) ) {
				$filterWordsA = [];
				foreach ( $wgBiggestCategoriesBlacklist as $word ) {
					$filterWordsA[] = '(cl_to not like "%'.$word.'%")';
				}

				$dbr = wfGetDB( DB_REPLICA );
				$tables = [ 'categorylinks' ];
				$fields = [ 'cl_to, COUNT(*) AS cnt' ];
				$where = count( $filterWordsA ) > 0 ? [ implode( ' AND ', $filterWordsA ) ] : [];
				$options = [ 'ORDER BY' => 'cnt DESC', 'GROUP BY' => 'cl_to', 'LIMIT' => $limit ];
				$res = $dbr->select( $tables, $fields, $where, __METHOD__, $options );
				$categories = [];
				while ( $row = $dbr->fetchObject( $res ) ) {
					$this->biggestCategories[] = [ 'name' => $row->cl_to, 'count' => $row->cnt ];
				}

				$memc->set( $key, $this->biggestCategories, 60 * 60 * 24 * 7 );
			} else {
				$this->biggestCategories = $data;
			}
		}

		return isset( $this->biggestCategories[$index-1] ) ? $this->biggestCategories[$index-1] : null;
	}
}
