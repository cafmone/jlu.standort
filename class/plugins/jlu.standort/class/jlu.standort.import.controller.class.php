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
		// handle derived language
		$this->langdir = CLASSDIR.'plugins/jlu.standort/lang/';
		if($this->file->exists(PROFILESDIR.'jlu.standort/lang/en.standort.standalone.api.ini')) {
			$this->langdir = PROFILESDIR.'jlu.standort/lang/';
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
			case 'nutzung':
				$content[] = $this->nutzung(true);
			break;
			case 'accessibility':
				$content[] = $this->accessibility(true);
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
			echo '<!DOCTYPE html><html><head><meta http-equiv="content-type" content="text/html;charset=utf-8"><title>Import</title></head><body>';
			echo '<h3>Import</h3>';
			echo '<hr>';

			echo '<b>Links</b> <a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=links">Parse</a>';
			echo '<a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=links&debug=true">Debug</a>';
			$ini = $this->file->get_ini($this->profilesdir.'jlu.standort.import.links.ini');
			if(is_array($ini)) {
				echo '<br><br>Config: ';
				echo realpath($this->profilesdir.'jlu.standort.import.links.ini');
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			}
			echo '<hr>';

			echo '<b>Nutzung</b> <a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=nutzung">Parse</a>';
			echo '<a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=nutzung&debug=true">Debug</a>';
			$ini = $this->file->get_ini($this->langdir.'de.jlu.standort.standalone.nutzung.ini');
			if(is_array($ini)) {
				echo '<br><br>Translation: ';
				echo realpath($this->langdir.'de.jlu.standort.standalone.nutzung.ini');
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			}
			$ini = $this->file->get_ini($this->profilesdir.'jlu.standort.import.nutzung.ini');
			if(is_array($ini)) {
				echo 'Config: ';
				echo realpath($this->profilesdir.'jlu.standort.import.nutzung.ini');
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			}
			echo '<hr>';

			echo '<b>Barrierefreiheit</b> <a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=accessibility">Parse</a>';
			echo '<a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=accessibility&debug=true">Debug</a>';
			$ini = $this->file->get_ini($this->langdir.'de.jlu.standort.standalone.accessibility.ini');
			if(is_array($ini)) {
				echo '<br><br>Translation: ';
				echo realpath($this->langdir.'de.jlu.standort.standalone.accessibility.ini');
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			}
			$ini = $this->file->get_ini($this->profilesdir.'jlu.standort.import.accessibility.ini');
			if(is_array($ini)) {
				echo 'Config: ';
				echo realpath($this->profilesdir.'jlu.standort.import.accessibility.ini');
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			}
			echo '<hr>';

			echo '<b>Tree</b> <a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=tree">Parse</a>';
			echo '<a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=tree&debug=true">Debug</a>';
			$ini = $this->file->get_ini($this->profilesdir.'jlu.standort.import.tree.ini');
			if(is_array($ini)) {
				echo '<br><br>Config: ';
				echo realpath($this->profilesdir.'jlu.standort.import.tree.ini');
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			}
			echo '</body></html>';
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
	 * Nutzung
	 *
	 * @access public
	 * @return null
	 */
	//--------------------------------------------
	function nutzung( $visible = false ) {
		$data = '';
		if($visible === true) {
			require_once($this->classdir.'jlu.standort.import.nutzung.class.php');
			$controller = new jlu_standort_import_nutzung($this);
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
	 * accessibility
	 *
	 * @access public
	 * @return null
	 */
	//--------------------------------------------
	function accessibility( $visible = false ) {
		$data = '';
		if($visible === true) {
			require_once($this->classdir.'jlu.standort.import.accessibility.class.php');
			$controller = new jlu_standort_import_accessibility($this);
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
