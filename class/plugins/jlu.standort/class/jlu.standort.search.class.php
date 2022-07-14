<?php
/**
 * jlu_standort_search
 *
 * @package jlu_standort
 * @license ../LICENSE.TXT
 * @version 1.0
 * @copyright Copyright (c) 2022,
 * Justus-Liebig-Universitaet Giessen
 * Dezernat E - Liegenschaften, Bau und Technik
 * Abteilung E1 - Flaechenmanagement
 * E1.3 - Projektleitung CAFM-System.
 */

class jlu_standort_search
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'standort_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = 'standort_msg';
/**
* identifier
* @access public
* @var string
*/
var $identifier_name = 'standort_ident';

/**
* language
* default language
* @access public
* @var string
*/
var $language = 'en';
/**
* translation
* @access public
* @var array
*/
var $lang = array();

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param object $controller
	 */
	//--------------------------------------------
	function __construct($controller) {
	
		$this->controller  = $controller;
		$this->response    = $controller->response;
		$this->user        = $controller->user;
		$this->db          = $controller->db;
		$this->file        = $controller->file;
		$this->langdir     = $controller->langdir;
		$this->tpldir      = $controller->tpldir;
		$this->profilesdir = $controller->profilesdir;
		$this->classdir    = $controller->classdir;
		$this->treeurl     = $controller->treeurl;

	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * Important : expecting "gebauede",
	 * "geschoss", "raum" as ['v'] in tree 
	 *
	 * @access public
	 * @return htmlobject_template
	 */
	//--------------------------------------------
	function action() {

		$tree = array();
		$floors = array();
		$rooms = array();

		// debug
		$display = 'none';
		if(isset($this->controller->debug)) {
			$display = 'block';
		}

		// handle tree
		if(isset($this->tree) && is_array($this->tree)) {
			foreach($this->tree as $k => $v) {
				if(isset($v['v'])) {
					if($v['v'] === 'gebauede') {
						$tree[$k] = $this->__parents($v['p'], $v['l']);
					}
					elseif($v['v'] === 'geschoss') {
						$floors[$v['p']][$k] = $v['l'];
					}
					elseif($v['v'] === 'raum') {
						$rooms[$v['p']][$k] = $v['l'];
					}
				}
			}
		}
		asort($tree);

		// handle external tags
		$etags = array();
		$tagfile = $this->profilesdir.'/jlu.standort/tags.csv';
		if($this->file->exists($tagfile)) {
			$fp = @fopen($tagfile, "r");
			if($fp) {
				while (($data = fgetcsv($fp, 1000, ";", "\"")) !== FALSE) {
					if(isset($data[1])) {
						$etags[$data[0]] = $data[1];
					}
				}
				fclose($fp);
			}
		}

		// build content
		$content = '';
		foreach($tree as $k => $v) {
			$parts = explode(' | ', $v);
			$floor = array();
			$room = array();
			$tags = '';
			$crooms = 0;
			
			if(isset($floors[$k])) {
				$floor = $floors[$k];
				foreach($floors[$k] as $kk => $vv) {
					if(isset($rooms[$kk])) {
						$crooms += count($rooms[$kk]);
						$str = implode(' | ', $rooms[$kk]);
						preg_match_all('~\((.*?)\)~', $str, $matches);
						if(count($matches[1]) > 0) {
							$room = array_merge($room, $matches[1]);
						}
					}
				}
				$room = array_unique($room);
				if($tags === '' && count($room) > 0) {
					$tags .= ' * ';
				}
				$tags .= implode(' | ', $room);
			}
			
			// handle external tags
			if(array_key_exists($k, $etags)) {
				$tags .= ' x '. $etags[$k];
			}
			
			$content .= '<div id="'.$k.'" style="margin-bottom:10px;">';
			$content .= ' <span style="display:'.$display.';" id="search_'.$k.'">'.$k.' | '.$parts[count($parts)-2].' | '.$parts[count($parts)-1].' '.$tags.'</span>';
			$content .= ' <a href="?id='.$k.'&lang='.$this->language.'">';
			$content .= '  <div class="card">';
			$content .= '   <div class="card-body">';
			$content .= '    <div class="card-text clearfix">';
			$content .= '     <div class="float-left">';
			$content .= '     '.implode('<br>', $parts);
			$content .= '     </div>';
			$content .= '     <img class="float-right" title="'.$this->lang['map']['title_thumb'].'" src="jlu.standort.api.php?action=thumb&file='.$k.'.jpg" onclick="mapbuilder.image(\''.$k.'\'); return false;">';
			$content .= '    </div>';
			$content .= '   </div>';
			$content .= '  </div>';
			$content .= ' </a>';
			$content .= '</div>';
		}

		$t = $this->response->html->template($this->tpldir.'jlu.standort.search.html');
		$vars = array(
			'content' => $content,
			'search' => $this->lang['search'],
			'search_title' => $this->lang['search_title'],
			'close' => $this->lang['close'],
			'max' => count($tree),
			'display' => $display,
		);
		$t->add($vars);
		return $t;
	}
	
	//--------------------------------------------
	/**
	 * __parents
	 *
	 * @access public
	 * @param string $parent
	 * @param string $str
	 * @return string
	 */
	//--------------------------------------------
	function __parents($parent, $str) {
		if(array_key_exists($parent, $this->tree)) {
			$tmp = $this->tree[$parent];
			if(isset($tmp['p'])) {
				return $this->__parents($tmp['p'], $tmp['l'].' | '.$str);
			} else {
				return $str;
			}
		} else {
			return $str;
		}
	}

}
?>
