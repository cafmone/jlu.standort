<?php
/**
 * jlu_standort_import_accessibility
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

class jlu_standort_import_accessibility
{

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 */
	//--------------------------------------------
	function __construct($controller) {

		$this->PROFILESDIR = $controller->profilesdir;
		$this->CLASSDIR    = $controller->classdir;

		require_once($this->CLASSDIR.'lib/file/file.xlsx.class.php');

		$this->response    = $controller->response;
		$this->db          = $controller->db;
		$this->file        = $controller->file;

		$this->langdir     = $controller->langdir;

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
	 * @return htmlobject_template
	 */
	//--------------------------------------------
	function action($action = null) {
		$file = 'jlu.standort.import.accessibility.ini';
		$result = $this->db->select($file,'*');

		$search = $this->file->get_ini($this->langdir.'de.jlu.standort.accessibility.ini');

		if(is_array($result)) {

			$output  = array();

			foreach($result as $ection => $r) {

				// handle import.ini values
				$cols = array();

				// check key id
				if(isset($r['id']) && $r['id'] !== '') {
					$key = $r['id'];
					$matches = @preg_match('~^[A-Z]+$~', $key);
					if(!$matches) {
						echo 'ERROR: misspelled key id "'.$key.'" in '.$file.' section ['.$ection.']';
						$this->response->html->help($r);
						exit();
					} else {
						$cols['id'] = $key;
					}
				} else {
					echo 'ERROR: Missing or empty key id in '.$file.' section ['.$ection.']';
					$this->response->html->help($r);
					exit();
				}

				// check key file
				if(!isset($r['file']) || $r['file'] === '') {
					echo 'ERROR: Missing or empty key file in '.$file.' section ['.$ection.']';
					exit();
				}
				// check key sheet
				if(!isset($r['sheet']) || $r['sheet'] === '') {
					echo 'ERROR: Missing or empty key sheet in '.$file.' section ['.$ection.']';
					exit();
				}
				// check key offset
				if(!isset($r['offset']) || $r['offset'] === '') {
					echo 'ERROR: Missing or empty key offset in '.$file.' section ['.$ection.']';
					exit();
				}
				// check keys
				if(is_array($search)) {

					if(isset($search[$ection])) {
						$ar = $search[$ection];
						foreach($ar as $k => $v) {
							if(!isset($r[$k]) || $r[$k] === '') {
								echo 'ERROR: Missing or empty key '.$k.' in '.$file.' section ['.$ection.']';
								exit();
							} else {
								$cols[$k] = $r[$k];
							}
						}
					}
				} else {
					echo 'ERROR: Missing or empty translation';
					exit();
				}

				// parse xlsx file
				$path = $this->PROFILESDIR.'import/'.$r['file'];

				$xlsx = new file_xlsx($this->file, $this->response->html);
				$xlsx->sheet = $r['sheet'];
				$xlsx->row   = $r['offset'];
				$xlsx->cols  = $cols;

				$content = $xlsx->parse($path);
				if(is_array($content)) {
					foreach($content as $k => $c) {
						$id = htmlEntities($c[$cols['id']], ENT_QUOTES);
						foreach($search[$ection] as $k => $v) {
							if(isset($c[$cols[$k]]) && $c[$cols[$k]] !== '') {
								$output[$id][$k] = $c[$cols[$k]];
							}
						}
					}
				} else {
					echo 'ERROR: '.$content.'.<br>';
					return;
				}
			}

			if(isset($this->debug)) {

				echo 'Debug finished. Found '.count($output).' valid.';

			} else {

				// handle cache
				$path = $this->response->html->thisdir.'cache/';
				if($this->file->is_writeable($path)) {
					$error = $this->file->mkfile($path.'accessibility.json', json_encode($output),'w+',true);
					if($error === '') {
							echo 'Successfully imported '.count($output).'.';
					} else {
						echo $error;
					}
				} else {
					echo 'ERROR: Folder '.$path.' is readonly.';
				}

			}
		}
	}

}
?>
