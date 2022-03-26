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
		$this->profilesdir = realpath(PROFILESDIR).'/';
		$this->classdir = realpath(CLASSDIR).'/';

		// handle derived language
		$this->langdir = $this->classdir.'plugins/jlu.standort/lang/';
		if($this->file->exists($this->profilesdir.'jlu.standort/lang/de.jlu.standort.standalone.ini')) {
			$this->langdir = $this->profilesdir.'jlu.standort/lang/';
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
			case 'geo':
				$content[] = $this->geo(true);
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

			// Links
			echo '<b>Links</b> <a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=links">Parse</a>';
			echo '<a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=links&debug=true">Debug</a>';
			echo '<br><br>Config: ';
			echo $this->profilesdir.'jlu.standort.import.links.ini';
			$ini = $this->file->get_ini($this->profilesdir.'jlu.standort.import.links.ini');
			if(is_array($ini)) {
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			}
			echo '<hr>';

			// Geo
			echo '<b>Geodaten</b> <a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=geo">Parse</a>';
			echo '<a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=geo&debug=true">Debug</a>';
			echo '<br><br>Config: ';
			echo $this->profilesdir.'jlu.standort.import.geo.ini';
			$ini = $this->file->get_ini($this->profilesdir.'jlu.standort.import.geo.ini');
			if(is_array($ini)) {
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			}
			echo '<hr>';

			// Nutzung
			echo '<b>Nutzung</b> <a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=nutzung">Parse</a>';
			echo '<a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=nutzung&debug=true">Debug</a>';
			echo '<br><br>Translation: ';
			echo $this->langdir.'de.jlu.standort.standalone.nutzung.ini';
			$ini = $this->file->get_ini($this->langdir.'de.jlu.standort.standalone.nutzung.ini');
			if(is_array($ini)) {
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			} else {
				echo '<br><br><b>Error</b>: Did not find translation in '.$this->langdir.'de.jlu.standort.standalone.nutzung.ini<br><br>';
			}
			echo 'Config: ';
			echo $this->profilesdir.'jlu.standort.import.nutzung.ini';
			$ini = $this->file->get_ini($this->profilesdir.'jlu.standort.import.nutzung.ini');
			if(is_array($ini)) {
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			}
			echo '<hr>';

			// Access
			echo '<b>Barrierefreiheit</b> <a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=accessibility">Parse</a>';
			echo '<a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=accessibility&debug=true">Debug</a>';
			echo '<br><br>Translation: ';
			echo $this->langdir.'de.jlu.standort.standalone.accessibility.ini';
			$ini = $this->file->get_ini($this->langdir.'de.jlu.standort.standalone.accessibility.ini');
			if(is_array($ini)) {
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			} else {
				echo '<br><br><b>Error</b>: Did not find translation in '.$this->langdir.'de.jlu.standort.standalone.accessibility.ini<br><br>';
			}
			$ini = $this->file->get_ini($this->profilesdir.'jlu.standort.import.accessibility.ini');
			if(is_array($ini)) {
				echo 'Config: ';
				echo $this->profilesdir.'jlu.standort.import.accessibility.ini';
				echo '<pre>';
				print_r($ini);
				echo '</pre>';
			}
			echo '<hr>';

			// Tree
			echo '<b>Tree</b> <a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=tree">Parse</a>';
			echo '<a style="display:inline-block;margin-left:20px;" href="'.$this->response->html->thisfile.'?'.$this->actions_name.'=tree&debug=true">Debug</a>';
			echo '<br><br>Config: ';
			echo $this->profilesdir.'jlu.standort.import.tree.ini';
			$ini = $this->file->get_ini($this->profilesdir.'jlu.standort.import.tree.ini');
			if(is_array($ini)) {
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
			require_once($this->classdir.'plugins/jlu.standort/class/jlu.standort.import.tree.class.php');
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
			require_once($this->classdir.'plugins/jlu.standort/class/jlu.standort.import.nutzung.class.php');
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
			require_once($this->classdir.'plugins/jlu.standort/class/jlu.standort.import.links.class.php');
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
			require_once($this->classdir.'plugins/jlu.standort/class/jlu.standort.import.accessibility.class.php');
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
	 * geo
	 *
	 * @access public
	 * @return null
	 */
	//--------------------------------------------
	function geo( $visible = false ) {
		$data = '';
		if($visible === true) {
			require_once($this->classdir.'plugins/jlu.standort/class/jlu.standort.import.geo.class.php');
			$controller = new jlu_standort_import_geo($this);
			$controller->tpldir = $this->tpldir;
			$controller->actions_name = $this->actions_name;
			$controller->identifier_name = $this->identifier_name;
			$data = $controller->action();
		}
		return $data;
	}


}
?>
