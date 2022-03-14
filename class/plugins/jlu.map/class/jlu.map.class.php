<?php
/**
 * jlu_map
 *
 * @package jlu_map
 * @license ../LICENSE.TXT
 * @version 1.0
 * @copyright Copyright (c) 2022,
 * Justus-Liebig-Universitaet Giessen
 * Dezernat E - Liegenschaften, Bau und Technik
 * Abteilung E1 - Flaechenmanagement
 * E1.3 - Projektleitung CAFM-System.
 */

class jlu_map
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'map_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = 'map_msg';
/**
* identifier
* @access public
* @var string
*/
var $identifier_name = 'map_ident';

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
* tileserverurl
* path too openstreetmap tile server
* @access public
* @var string
*/
var $tileserverurl = 'https://{a-c}.tile.openstreetmap.de/{z}/{x}/{y}.png';
/**
* title
* page Title
* @access public
* @var string
*/
var $title = '';
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
	 * @param file $file
	 * @param htmlobject_response $response
	 * @param query $db
	 * @param user $user
	 */
	//--------------------------------------------
	function __construct($file, $response, $db, $user) {
		$this->response    = $response;
		$this->user        = $user;
		$this->db          = $db;
		$this->file        = $file;
		
		// grrr - Windows
		$this->PROFILESDIR = realpath(PROFILESDIR).'/';
		$this->CLASSDIR = realpath(CLASSDIR).'/';
	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 */
	//--------------------------------------------
	function action() {
	
		$markers = $this->response->html->request()->get('m', true);
		#$this->response->html->help($markers);

		$msg = '';
		$script = 'markers=[';
		if(is_array($markers)) {
			foreach($markers as $marker) {
				if(isset($marker['long']) && isset($marker['lat'])) {
					$script .= '["'.floatval($marker['long']).'","'.floatval($marker['lat']).'"';
				} else {
					continue;
				}
				if(isset($marker['link'])) {
					$script .= ',"'.$marker['link'].'"';
				} else {
					$script .= ',""';
				}
				if(isset($marker['title'])) {
					$script .= ',"'.htmlentities($marker['title']).'"';
				} else {
					$script .= ',""';
				}
				$script .= '],';
			}
		} else {
			$msg = '<div class="alert alert-info"><b>Usage</b>: ?m[0][long]=8.67722&m[0][lat]=50.58038&m[0][title]=Hauptgeb&auml;ude&m[0][link]=http://google.com</div>';
		}
		$script .= '];';

		$t = $this->response->html->template($this->CLASSDIR.'plugins/jlu.map/templates/jlu.map.html');
		$vars = array(
			'message' => $msg,
			'script' => $script,
			'tileserverurl' => $this->tileserverurl,
			'title' => $this->title,
			'cssurl' => $this->cssurl,
			'jsurl' => $this->jsurl,
			'imgurl' => $this->imgurl,
		);
		$t->add($vars);

		return $t;
	}


}
?>
