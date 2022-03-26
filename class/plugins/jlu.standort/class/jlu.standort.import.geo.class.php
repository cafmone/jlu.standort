<?php
/**
 * jlu_standort_import_geo
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

class jlu_standort_import_geo
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

		$file = 'jlu.standort.import.geo.ini';
		$result = $this->db->select($file,'*');
		if(is_array($result)) {

			$tree  = array();
			$views = array();
			$csv   = '';
			$summ  = 0;
			
			
			$i = 1;
			
			$buildings = array();
			
			
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
				// check key parent
				### TODO set parent empty if first in result?
				if(isset($r['parent']) && $r['parent'] !== '') {
					$key = $r['parent'];
					$matches = @preg_match('~^[A-Z]+$~', $key);
					if(!$matches) {
						echo 'ERROR: misspelled key parent "'.$key.'" in '.$file.' section ['.$ection.']';
						$this->response->html->help($r);
						exit();
					} else {
						$cols['parent'] = $key;
					}
				} else {
					echo 'ERROR: Missing or empty key parent in '.$file.' section ['.$ection.']';
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
				// check key longitude
				if(!isset($r['longitude']) || $r['longitude'] === '') {
					$cols['long'] = '';
				} else {
					$cols['long'] = $r['longitude'];
				}
				// check key latitude
				if(!isset($r['latitude']) || $r['latitude'] === '') {
					$cols['lat'] = '';
				} else {
					$cols['lat'] = $r['latitude'];
				}

				// Build file
				if($i > 1 && count($buildings) > 0 && isset($r['makefile'])) {
					if(isset($this->debug)) {
						$this->response->html->help($buildings);
						echo '<hr>';
					} else {
						foreach($buildings as $b => $build) {
							$error = $this->file->make_ini($this->PROFILESDIR.'jlu.standort/bilder/'.$b, $build, '.geo');
							if($error !== '') {
								echo 'ERROR: '.$error.'<br>';
							}
						}
					}
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
						if( $c[$cols['id']] === '' || $c[$cols['parent']] === '') {
							if(isset($this->debug)) {
								echo 'WARNING: Empty column(s) in file '.$r['file'].'. Skipping row '.$k.'.<br>';
							}
						}
						// check id is unique
						elseif(array_key_exists($c[$cols['id']], $tree)) {
							if(isset($this->debug)) {
								echo 'WARNING: ID '.$c[$cols['id']].' in file '.$r['file'].' is not unique. Skipping row '.$k.'.<br>';
							}
						}
						else {
							// escape content (xss)
							$id     = htmlEntities($c[$cols['id']], ENT_QUOTES);
							$parent = htmlEntities($c[$cols['parent']], ENT_QUOTES);
							if($i === 1) {
								if( $c[$cols['long']] === '' || $c[$cols['lat']] === '') {
									echo 'WARNING: Empty column(s) '.$cols['long'].' or '.$cols['lat'].' in file '.$r['file'].'. Skipping row '.$k.'.<br>';
									continue;
								} else {
									$buildings[$c[$cols['parent']]][$c[$cols['id']]]['long'] = $c[$cols['long']];
									$buildings[$c[$cols['parent']]][$c[$cols['id']]]['lat']  = $c[$cols['lat']];
								}
							} else {
								if($i < count($result)) {
									if(isset($buildings[$c[$cols['id']]])) {
										if(!isset($buildings[$c[$cols['parent']]])) {
											$buildings[$c[$cols['parent']]] = $buildings[$c[$cols['id']]];
										} else {
											$buildings[$c[$cols['parent']]] += $buildings[$c[$cols['id']]];
										}
										unset($buildings[$c[$cols['id']]]);
									}
								} else {
									if(isset($buildings[$c[$cols['id']]])) {
									
									#echo $c[$cols['id']];
									#$this->response->html->help($buildings[$c[$cols['id']]]);
									
									}
								}
							}
						}
					}
				} else {
					echo 'ERROR: '.$content.'.<br>';
					return;
				}
				$i++;
			}

			if(isset($this->debug)) {
				echo 'Debug finished';
			}
		}
		elseif (is_string($result) && $result !== '') {
			echo 'ERROR: '.$result;
		}
		elseif (is_bool($result) && $result === false) {
			echo 'ERROR: Unable to import. Please check syntax in '.$file;
		}
	}

	//------------------------------------------------
	/**
	 * Sort array [ids] by key [sort]
	 *
	 * @access protected
	 * @param array $ids
	 * @param string $sort
	 * @param enum $order [ASC/DESC]
	 * @return array
	 */
	//------------------------------------------------
	function __sort($ids, $sort, $order = '') {
		if($order !== '') {
			if($order == 'ASC') $sort_order = SORT_ASC;
			if($order == 'DESC') $sort_order = SORT_DESC;
		} else {
			$sort_order = SORT_ASC;
		}
		$column = array();
		reset($ids);
		foreach($ids as $val) {
			if(isset($val[$sort])) {
				$column[] = $val[$sort];
			}
		}
		if(count($ids) === count($column)) {
			array_multisort($column, $sort_order, $ids);
		}
		return $ids;
	}

}
?>
