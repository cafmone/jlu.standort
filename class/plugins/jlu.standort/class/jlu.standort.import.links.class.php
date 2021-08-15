<?php
/**
 * jlu_standort_import_links
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

class jlu_standort_import_links
{

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 */
	//--------------------------------------------
	function __construct($controller) {

		$this->PROFILESDIR = realpath(PROFILESDIR).'/';
		$this->CLASSDIR    = realpath(CLASSDIR).'/';

		require_once($this->CLASSDIR.'lib/file/file.xlsx.class.php');

		$this->response    = $controller->response;
		$this->db          = $controller->db;
		$this->file        = $controller->file;

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
		$file = 'jlu.standort.import.links.ini';
		$result = $this->db->select($file,'*');

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
				// check key zlisurl
				if(isset($r['zlisurl']) && $r['zlisurl'] !== '') {
					$key = $r['zlisurl'];
					$matches = @preg_match('~^[A-Z]+$~', $key);
					if(!$matches) {
						echo 'ERROR: misspelled key zlisurl "'.$key.'" in '.$file.' section ['.$ection.']';
						$this->response->html->help($r);
						exit();
					} else {
						$cols['zlisurl'] = $key;
					}
				} else {
					$cols['zlisurl'] = '';
				}
				// check key jlucourl
				if(isset($r['jlucourl']) && $r['jlucourl'] !== '') {
					$key = $r['jlucourl'];
					$matches = @preg_match('~^[A-Z]+$~', $key);
					if(!$matches) {
						echo 'ERROR: misspelled key jlucourl "'.$key.'" in '.$file.' section ['.$ection.']';
						$this->response->html->help($r);
						exit();
					} else {
						$cols['jlucourl'] = $key;
					}
				} else {
					$cols['jlucourl'] = '';
				}
				// check key googlemaps
				if(isset($r['googlemapsurl']) && $r['googlemapsurl'] !== '') {
					$key = $r['googlemapsurl'];
					$matches = @preg_match('~^[A-Z]+$~', $key);
					if(!$matches) {
						echo 'ERROR: misspelled key googlemapsurl "'.$key.'" in '.$file.' section ['.$ection.']';
						$this->response->html->help($r);
						exit();
					} else {
						$cols['googlemapsurl'] = $key;
					}
				} else {
					$cols['googlemapsurl'] = '';
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

				// parse xlsx file
				$path = $this->PROFILESDIR.'import/'.$r['file'];

				$xlsx = new file_xlsx($this->file, $this->response->html);
				$xlsx->sheet = $r['sheet'];
				$xlsx->row   = $r['offset'];
				$xlsx->cols  = $cols;

				$content = $xlsx->parse($path);
				if(is_array($content)) {
					foreach($content as $k => $c) {

						// check values not empty
						if( $c[$cols['zlisurl']] === '' && 
							$c[$cols['jlucourl']] === '' && 
							$c[$cols['googlemapsurl']] === '' 
						) {
							if(isset($this->debug)) {
								echo 'NOTICE: Empty column(s) in file '.$r['file'].'. Skipping row '.$k.'.<br>';
							}
						} else {
							// escape content (xss)
							$id = htmlEntities($c[$cols['id']], ENT_QUOTES);

							// TODO deactivate studip and jlucourl
							//$output[$id]['zlisurl'] = $c[$cols['zlisurl']];
							//$output[$id]['jlucourl'] = $c[$cols['jlucourl']];
							$output[$id]['googlemapsurl'] = $c[$cols['googlemapsurl']];


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
					$error = $this->file->mkfile($path.'links.json', json_encode($output),'w+',true);
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
