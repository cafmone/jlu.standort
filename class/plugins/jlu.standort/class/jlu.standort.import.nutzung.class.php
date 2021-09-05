<?php
/**
 * jlu_standort_import_nutzung
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

class jlu_standort_import_nutzung
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
		$file = 'jlu.standort.import.nutzung.ini';
		$result = $this->db->select($file,'*');


		$search = $this->file->get_ini($this->langdir.'de.jlu.standort.standalone.nutzung.ini');


		if(is_array($result)) {

			$output = array();
			$empty = 0;

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
				// check key nutzung
				if(isset($r['nutzung']) && $r['nutzung'] !== '') {
					$key = $r['nutzung'];
					$matches = @preg_match('~^[A-Z,]+$~', $key);
					if(!$matches) {
						echo 'ERROR: misspelled key nutzung "'.$key.'" in '.$file.' section ['.$ection.']';
						$this->response->html->help($r);
						exit();
					} else {
						$tmp = explode(',',$key);
						$num_nutzung = count($tmp);
						foreach($tmp as $k => $v) {
							$cols['nutzung_'.$k] = $v;
						}
						//$cols['nutzung'] = $key;
					}
				} else {
					echo 'ERROR: Missing or empty key nutzung in '.$file.' section ['.$ection.']';
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

				// Delimiter
				$delimiter = '';
				if(isset($r['delimiter']) && $r['delimiter'] !== '') {
					$delimiter = $r['delimiter'];
				}
				// Replace
				$replace = '';
				if(isset($r['replace']) && $r['replace'] !== '') {
					$replace = $r['replace'];
				}

				// parse xlsx file
				$path = $this->PROFILESDIR.'import/'.$r['file'];

				$xlsx = new file_xlsx($this->file, $this->response->html);
				$xlsx->sheet = $r['sheet'];
				$xlsx->row   = $r['offset'];
				$xlsx->cols  = $cols;

				$output = array();
				$content = $xlsx->parse($path);
				if(is_array($content)) {
					foreach($content as $k => $c) {

						$id = $c[$cols['id']];
						if(isset($c[$cols['nutzung_0']]) && $c[$cols['nutzung_0']] !== '') {
							
							for($i=0; $i < $num_nutzung; $i++) {
								$tmp = array();
								if($delimiter !== '') {
									$tmp = explode($delimiter, $c[$cols['nutzung_'.$i]]);
								} else {
									$tmp[] = $c[$cols['nutzung_'.$i]];
								}
								foreach($tmp as $v) {
									if($replace !== '') {
										$value = preg_replace($replace, '', $v);
										$value = trim($value);
										if(isset($search[$value])) {
											$output[$id][] = $value;
										}
									} else {
										$v = trim($v);
										if(isset($search[$v])) {
											$output[$id][] = trim($v);
										}
									}
								}
							}

						} else {
							if(isset($this->debug)) {
								echo 'NOTICE: Empty column nutzung ('.$cols['nutzung_0'].') in file '.$r['file'].'. Skipping row '.$k.'.<br>';
							}
							$empty++;
							continue;
						}

					}
				} else {
					echo 'ERROR: '.$content.'.<br>';
					return;
				}
			}

			if(isset($this->debug)) {

				echo 'Debug finished. Found '.count($output).' valid in '.count($content).'. Empty cols '.$empty.'.';

			} else {

				// handle cache
				$path = $this->response->html->thisdir.'cache/';
				if($this->file->is_writeable($path)) {
					$error = $this->file->mkfile($path.'nutzung.json', json_encode($output),'w+',true);
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
