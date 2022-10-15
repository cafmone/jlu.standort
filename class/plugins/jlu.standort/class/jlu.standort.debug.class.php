<?php
/**
 * jlu_standort_debug
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

class jlu_standort_debug
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

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param object $controller
	 */
	//--------------------------------------------
	function __construct($controller) {
	
		$this->controller = $controller;
		$this->response   = $controller->response;
		$this->user       = $controller->user;
		$this->db         = $controller->db;
		$this->file       = $controller->file;
		
		$this->langdir    = $controller->langdir;
		$this->tpldir     = $controller->tpldir;
		
		$this->profilesdir = $controller->profilesdir;
		$this->classdir    = $controller->classdir;

	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 */
	//--------------------------------------------
	function action() {

		$t = $this->response->html->template($this->tpldir.'jlu.standort.debug.html');
		$vars = array(
			'title' => 'Debug',
			'cssurl' => $this->cssurl,
			'jsurl' => $this->jsurl,
			'imgurl' => $this->imgurl,
		);
		$t->add($vars);
		return $t;
	}

}
?>
