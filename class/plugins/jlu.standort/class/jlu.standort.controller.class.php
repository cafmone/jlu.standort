<?php
/**
 * jlu_standort_controller
 *
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

class jlu_standort_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'standort';
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
* path to tpldir
* @access public
* @var string
*/
var $tpldir;

/* -------------- Urls -------------- */
/**
* treeurl
* path too tree.js
* @access public
* @var string
*/
var $treeurl = '';
/**
* cssurl
* path too css directory
* @access public
* @var string
*/
var $cssurl = 'css/';
/**
* imgurl
* path too image directory
* @access public
* @var string
*/
var $imgurl = 'img/';
/**
* jsurl
* path to js files
* @access public
* @var string
*/
var $jsurl = 'js/';
/**
* qrcodeurl
* baseurl for qrcodes
* @access public
* @var string
*/
var $qrcodeurl;

/* -------------- Links -------------- */
/**
* link to contact
* @access public
* @var array
*/
var $contacturl = null;
/**
* link to imprint
* @access public
* @var array
*/
var $imprinturl = null;
/**
* link to privacy notice
* @access public
* @var array
*/
var $privacynoticeurl = null;
/**
* copyright notice
* @access public
* @var array
*/
var $helppageurl = null;
/**
* copyright notice
* @access public
* @var array
*/
var $copyright = null;

/* -------------- Default id -------------- */
/**
* default if none given by request
* @access public
* @var string
*/
var $defaultid;


	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param file_handler $phppublisher
	 * @param htmlobject_response $response
	 * @param query $db
	 * @param user $user
	 */
	//--------------------------------------------
	function __construct($file, $response, $db, $user) {

		$this->file = $file;
		$this->response = $response;
		$this->db = $db;
		$this->user = $user;

		// grrr - Windows
		$this->profilesdir = realpath(PROFILESDIR).'/';
		$this->classdir    = realpath(CLASSDIR).'/';

		// handle derived language
		$this->langdir = $this->classdir.'plugins/jlu.standort/lang/';
		if($this->file->exists($this->profilesdir.'jlu.standort/lang/de.jlu.standort.index.ini')) {
			$this->langdir = $this->profilesdir.'jlu.standort/lang/';
		}

		// handle derived templates
		$this->tpldir = $this->classdir.'plugins/jlu.standort/templates/';
		if($this->file->exists($this->profilesdir.'jlu.standort/templates/jlu.standort.index.html')) {
			$this->tpldir = $this->profilesdir.'jlu.standort/templates/';
		}
		
		// debug
		$debug = $this->response->html->request()->get('debug', true);
		if(isset($debug)) {
			$this->debug = true;
		}
	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 * @param string $action
	 * @return htmlobject_tabmenu
	 */
	//--------------------------------------------
	function action($action = null) {

		$this->action = 'index';
		$ar = $this->response->html->request()->get($this->actions_name);
		if($ar !== '') {
			$this->action = $ar;
		}
		else if(isset($action)) {
			$this->action = $action;
		}
		$this->response->add($this->actions_name, $this->action);

		$content = array();
		switch( $this->action ) {
			case '':
			case 'index':
			default:
				$content = $this->index(true);
			break;
			case 'search':
				$content = $this->search(true);
			break;
		}

		#$this->response->html->help($content);
		
		return $content;
	}

	//--------------------------------------------
	/**
	 * Api
	 *
	 * @access public
	 * @param bool $visible
	 * @return htmlobject | empty
	 */
	//--------------------------------------------
	function api( $visible = false ) {
		$data = '';
		if($visible === true) {
			require_once($this->classdir.'plugins/jlu.standort/class/jlu.standort.api.class.php');
			$controller = new jlu_standort_api($this);

			$controller->cssurl = $this->cssurl;
			$controller->jsurl = $this->jsurl;
			$controller->imgurl = $this->imgurl;
			$controller->treeurl = $this->treeurl;
			$controller->language = $this->language;

			$data = $controller->action();
		}
		return $data;
	}

	//--------------------------------------------
	/**
	 * Index
	 *
	 * @access public
	 * @return htmlobject | empty
	 */
	//--------------------------------------------
	function index( $visible = false, $raw = false ) {
		$data = '';
		if($visible === true) {
		
			// handle tree
			$treeurl = $this->response->html->thisdir.$this->treeurl;
			if($this->file->exists($treeurl)) {
				$tree = json_decode(str_replace('var tree = ', '', $this->file->get_contents($treeurl)), true);
				$this->tree = $tree;
			}

			require_once($this->classdir.'plugins/jlu.standort/class/jlu.standort.index.class.php');
			$controller = new jlu_standort_index($this);
			$controller->actions_name = $this->actions_name;
			$controller->identifier_name = $this->identifier_name;
			$controller->language = $this->language;
			$controller->tree = $this->tree;
			$controller->cssurl = $this->cssurl;
			$controller->jsurl = $this->jsurl;
			$controller->imgurl = $this->imgurl;
			$controller->treeurl = $this->treeurl;
			$controller->contacturl = $this->contacturl;
			$controller->imprinturl = $this->imprinturl;
			$controller->privacynoticeurl = $this->privacynoticeurl;
			$controller->helppageurl = $this->helppageurl;
			$controller->copyright = $this->copyright;
			$controller->defaultid = $this->defaultid;

			if(isset($raw) && $raw === true) {
				$data = $controller;
			}
			elseif(!isset($raw) || $raw === false) {
				$data = $controller->action();
			}
		}
		return $data;
	}

	//--------------------------------------------
	/**
	 * Search
	 *
	 * @access public
	 * @return htmlobject | empty
	 */
	//--------------------------------------------
	function search( $visible = false ) {
		$data = '';
		if($visible === true) {
			$obj = $this->index(true, true);
			$index = $obj->action();

			require_once($this->classdir.'plugins/jlu.standort/class/jlu.standort.search.class.php');
			$controller = new jlu_standort_search($this);
			$controller->tpldir = $this->tpldir;
			$controller->actions_name = $this->actions_name;
			$controller->identifier_name = $this->identifier_name;
			$controller->language = $obj->language;
			$controller->lang = $obj->translation;
			
			$controller->tree = $obj->tree;
			
			$tmp = $controller->action();
			
			$index->add(array('canvas' => $tmp));
			$data = $index;
		}
		return $data;
	}

}
?>
