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
		
		// get search value
		$value = $this->response->html->request()->get('search');
		if($value !== '') {
			$value = htmlentities($value);
		}

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
						// remove rooms without (..)
						preg_match('~\((.*?)\)~', $v['l'], $matches);
						if(count($matches) > 0) {
							$rooms[$v['p']][$k] = $v['l'];
						}
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
		$output = '';
		foreach($tree as $k => $v) {
			$parts = explode('|', $v);
			$floor = array();
			$room = array();
			$links = array();
			$tags = array();

			if(isset($floors[$k])) {
				$floor = $floors[$k];
				foreach($floors[$k] as $kk => $vv) {
					if(isset($rooms[$kk])) {
						sort($rooms[$kk], SORT_NUMERIC);
						$str = '|'.implode('|', $rooms[$kk]).'|';
						preg_match_all('~\|?(.*?)\)+~', $str, $matches);
						if(count($matches[1]) > 0) {
							foreach($matches[1] as $m) {
								$pos = strpos($m, '(');
								if ($pos !== false) {
									$m = '<div class="number" style="display:inline;">'.substr_replace($m, '</div>', $pos, strlen('('));
								}
								$links[] = $m.'<div class="floor" style="display:inline;"><a href="?id='.$kk.'&lang='.$this->language.'">'.$vv.'</a></div>';
							}
						}
						preg_match_all('~\((.*?)\)~', $str, $matches);
						if(count($matches[1]) > 0) {
							foreach($matches[1] as $m) {
								$room[] = $m;
							}
						}
					}
				}
			}

			// handle external tags
			if(array_key_exists($k, $etags)) {
				$tags[] = $etags[$k];
			}
			// handle gebaeude
			$strgeb = '';
			$i = 1;
			foreach($parts as $part) {
				if($i === 3) {
					$strgeb .= '<div style="display:inline;" id="'.$k.'-adress">';
				}
				$strgeb .= $part.'<br>';
				if($i === count($parts)) {
					$strgeb .= '</div>';
				}
				$i++;
			}
			// handle rooms
			$strroom = '';
			if(count($room) > 0) {
				$strroom .= '<div class="rooms float-left">';
				$strroom .= '<h5>'.$this->lang['search']['rooms'].'</h5>';
				$strroom .= '<div id="'.$k.'-rooms">'.implode('<br>', $links).'<br></div>';
				$strroom .= '</div>';
			}

			$x = $this->response->html->template($this->tpldir.'jlu.standort.search.result.html');
			$vars = array(
				'id' => $k,
				'search' => '|'.$k.'|'.$parts[count($parts)-2].'|'.$parts[count($parts)-1].'|+|'.implode('|', array_unique($room)).'|+|'.implode('|', $tags),
				'gebaeude' => $strgeb,
				'rooms' => $strroom,
				'display' => $display,
				'href' => '?id='.$k.'&lang='.$this->language,
				'label_gebaeude' => $this->lang['identifiers']['gebauede'],
				'img_title' => $this->lang['map']['title_thumb'],
				'img_src' => 'jlu.standort.api.php?action=thumb&file='.$k.'.jpg',
			);
			$x->add($vars);
			$output = $output.''.$x->get_string();
		}

		$tpl = $this->response->html->template($this->tpldir.'jlu.standort.search.html');
		$vars = array(
			'search' => $this->lang['search']['search'],
			'search_title' => $this->lang['search']['search_title'],
			'help' => $this->lang['search']['help'],
			'value' => $value,
			'close' => $this->lang['close'],
			'loading' => $this->lang['loading'],
			'max' => count($tree),
			'display' => $display,
			'imgurl' => $this->controller->imgurl,
			'output' => $output,
		);
		$tpl->add($vars);
		return $tpl->get_string();
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
				return $this->__parents($tmp['p'], $tmp['l'].'|'.$str);
			} else {
				return $str;
			}
		} else {
			return $str;
		}
	}

}
?>
