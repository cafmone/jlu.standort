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

		// handle tree
		$treeurl = $this->response->html->thisdir.$this->treeurl;
		if($this->file->exists($treeurl)) {
			$this->tree = json_decode(str_replace('var tree = ', '', $this->file->get_contents($treeurl)), true);
		}

	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * Important : expecting "gebauede" in tree 
	 *
	 * @access public
	 * @return htmlobject_template
	 */
	//--------------------------------------------
	function action() {

		$tree = array();
		if(isset($this->tree) && is_array($this->tree)) {
			foreach($this->tree as $k => $v) {
				if(isset($v['v']) && $v['v'] === 'gebauede') {
					$tree[$k] = $this->__parents($v['p'], $v['l']);
					#break;
				}
			}
		}
		asort($tree);
		$content = '';
		foreach($tree as $k => $v) {
			$parts = explode(' | ', $v);
		
			$content .= '<div id="'.$k.'">';
			$content .= ' <span id="search_'.$k.'">'.$k.' - '.$v.'</span>';
			$content .= ' <a href="?id='.$k.'&lang='.$this->language.'">';
			$content .= '  <div class="card">';
			$content .= '   <div class="card-body">';
			$content .= '    <div class="card-text clearfix">';
			$content .= '     <div class="float-left">';
			$content .= '     '.$parts[count($parts)-1].'<br>';
			$content .= '     '.$parts[count($parts)-2];
			$content .= '     </div>';
			$content .= '     <img class="float-right" src="jlu.standort.api.php?action=thumb&file='.$k.'.jpg">';
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
