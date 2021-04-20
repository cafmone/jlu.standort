<?php
/**
 * jlu_standort_import_controller
 *
 *
 * @package jlu_standort
 * @license ../LICENSE.TXT
 * @version 1.0
 * @copyright Copyright (c) 2020,
 * Justus-Liebig-Universitaet Giessen
 * Dezernat E - Liegenschaften, Bau und Technik
 * Abteilung E1 - Flaechenmanagement
 * E1.3 - Projektleitung CAFM-System.
 */

class jlu_standort_import_controller
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'import';
/**
* message param
* @access public
* @var string
*/
var $message_param = 'import_msg';
/**
* identifier
* @access public
* @var string
*/
var $identifier_name = 'import_ident';
/**
* path to tpldir
* @access public
* @var string
*/
var $tpldir;

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
		$this->profilesdir = PROFILESDIR;
		$this->classdir = CLASSDIR.'plugins/jlu.standort/class/';
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
		$this->action = '';
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
			default:
				$content[] = $this->help(true);
			break;
			case 'tree':
				$content[] = $this->tree(true);
			break;
			case 'links':
				$content[] = $this->links(true);
			break;
		}

	}

	//--------------------------------------------
	/**
	 * Help
	 *
	 * @access public
	 * @return array
	 */
	//--------------------------------------------
	function help( $visible = false ) {
		$data = '';
		if($visible === true) {
			echo '<h3>Import</h3>';
			echo '<hr>';
			echo '<a href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=links">Links</a>';
			$ini = $this->file->get_ini($this->profilesdir.'jlu.standort.import.links.ini');
			if(is_array($ini)) {
				echo '<br><br>Config: ';
				echo realpath($this->profilesdir.'jlu.standort.import.links.ini');
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			}
			echo '<hr>';
			echo '<a href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=tree">Tree</a>';
			$ini = $this->file->get_ini($this->profilesdir.'jlu.standort.import.tree.ini');
			if(is_array($ini)) {
				echo '<br><br>Config: ';
				echo realpath($this->profilesdir.'jlu.standort.import.tree.ini');
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			}
		}
	}

	//--------------------------------------------
	/**
	 * Tree
	 *
	 * @access public
	 * @return null
	 */
	//--------------------------------------------
	function tree( $visible = false ) {
		$data = '';
		if($visible === true) {
			require_once($this->classdir.'jlu.standort.import.tree.class.php');
			$controller = new jlu_standort_import_tree($this);
			$controller->tpldir = $this->tpldir;
			$controller->actions_name = $this->actions_name;
			$controller->identifier_name = $this->identifier_name;
			$data = $controller->action();
		}
		return $data;
	}

	//--------------------------------------------
	/**
	 * Links
	 *
	 * @access public
	 * @return null
	 */
	//--------------------------------------------
	function links( $visible = false ) {
		$data = '';
		if($visible === true) {
			require_once($this->classdir.'jlu.standort.import.links.class.php');
			$controller = new jlu_standort_import_links($this);
			$controller->tpldir = $this->tpldir;
			$controller->actions_name = $this->actions_name;
			$controller->identifier_name = $this->identifier_name;
			$data = $controller->action();
		}
		return $data;
	}


	//--------------------------------------------
	/**
	 * Download
	 *
	 * @access protected
	 * @return null
	 */
	//--------------------------------------------

/*
	function __download( $visible = false ) {
		if($visible === true) {
			$path = $this->response->html->request()->get('file');
			$path = $this->basedir.''.$path;

			if($this->file->exists($path)) {
				require_once(CLASSDIR.'/lib/file/file.mime.class.php');
				$file = $this->file->get_fileinfo($path);
				$mime = detect_mime($file['path']);

				header("Pragma: public");
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
				header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
				header("Cache-Control: must-revalidate");
				header("Content-type: $mime");
				header("Content-Length: ".$file['filesize']);
				header("Content-disposition: attachment; filename=".$file['name']);
				header("Accept-Ranges: ".$file['filesize']);
				flush();
				readfile($path);
				exit(0);
			}
		}
	}
*/

}
?>
