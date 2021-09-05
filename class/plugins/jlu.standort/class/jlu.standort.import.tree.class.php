<?php
/**
 * jlu_standort_import_tree
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

class jlu_standort_import_tree
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

		$file = 'jlu.standort.import.tree.ini';
		$result = $this->db->select($file,'*');
		if(is_array($result)) {

			$tree  = array();
			$views = array();
			$csv   = '';
			$summ  = 0;
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
				// check key label
				if(isset($r['label']) && $r['label'] !== '') {
					$key = $r['label'];
					$matches = @preg_match('~^[A-Z,]+$~', $key);
					if(!$matches) {
						echo 'ERROR: misspelled key label "'.$key.'" in '.$file.' section ['.$ection.']';
						$this->response->html->help($r);
						exit();
					} else {
						$tmp = explode(',',$key);
						$num_labels = count($tmp);
						foreach($tmp as $k => $v) {
							$cols['label_'.$k] = $v;
						}
					}
				} else {
					echo 'ERROR: Missing or empty key label in '.$file.' section ['.$ection.']';
					$this->response->html->help($r);
					exit();
				}

				// check key order
				if(isset($r['order'])) {
					if($r['order'] !== '') {
						$key = $r['order'];
						$matches = @preg_match('~^[A-Z]+$~', $key);
						if(!$matches) {
							echo 'ERROR: misspelled key order "'.$key.'" in '.$file.' section ['.$ection.']';
							$this->response->html->help($r);
							exit();
						} else {
							$cols['order'] = $key;
						}
					}
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
				// check key view
				if(!isset($r['view']) || $r['view'] === '') {
					echo 'ERROR: Missing or empty key view in '.$file.' section ['.$ection.']';
					exit();
				} else {
					$key = $r['view'];
					$matches = @preg_match('~^[a-z]+$~', $key);
					if(!$matches) {
						echo 'ERROR: Misspelled key view "'.$key.'" in '.$file.' section ['.$ection.']';
						$this->response->html->help($r);
						exit();
					} else {
						if(array_key_exists($key, $views)) {
							echo 'ERROR: Key view "'.$key.'" in '.$file.' section ['.$ection.'] is not unique.';
							$this->response->html->help($r);
							exit();
						} else {
							// handle views
							$views[$key] = $key;
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
						if( $c[$cols['id']] === '' || $c[$cols['parent']] === '' || $c[$cols['label_0']] === '') {
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
						// check parent exists
						elseif($ection > 0 && !array_key_exists($c[$cols['parent']], $tree)) {
							if(isset($this->debug)) {
								echo 'WARNING: Parent '.$c[$cols['parent']].' for ID '.$c[$cols['id']].'  in file '.$r['file'].' not found. Skipping row '.$k.'.<br>';
							}
						} else {
							// escape content (xss)
							$id     = htmlEntities($c[$cols['id']], ENT_QUOTES);
							$parent = htmlEntities($c[$cols['parent']], ENT_QUOTES);

							$label  = '';
							for($i=0; $i < $num_labels; $i++) {
								if($i > 0) {
									if($i === 1) {
										$label .= ',';
									}
									$label .= ' ';
								}
								$label .= htmlEntities($c[$cols['label_'.$i]], ENT_QUOTES);
							}

							// handle tree
							$tree[$id]['p'] = $parent;
							$tree[$id]['v'] = $r['view'];
							$tree[$id]['l'] = $label;

							// handle order
							if(isset($r['order'])) {
								if($r['order'] === '') {
									$tree[$id]['o'] = $k;
								} else {
									if(isset($c[$cols['order']]) && $c[$cols['order']] !== '') {
										$tree[$id]['o'] = $c[$cols['order']];
									}
								}
							}
						}
					}
					$summ = $summ + count($content);
				} else {
					echo 'ERROR: '.$content.'.<br>';
					return;
				}
			}

			if(isset($this->debug)) {

				echo 'Debug finished. Found '.count($tree).' valid entries.';
				if(count($tree) !== $summ) {
					// handle count warning
					echo '<br>WARNING: Tree length ('.count($tree).') does not match expected result from xlsx ('.$summ.'). Missing '.($summ - count($tree)).'.';
				}

			} else {
				// handle cache
				$path = $this->response->html->thisdir.'cache/';
				if($this->file->is_writeable($path)) {
					$error = $this->file->mkfile($path.'timestamp.txt',time(),'w+',true);
					if($error === '') {
						$error = $this->file->mkfile($path.'views.txt', implode(',',$views), 'w+', true);
						if($error === '') {
							$error = $this->file->mkfile($path.'tree.js', 'var tree = '.json_encode($tree),'w+',true);
							if($error === '') {
								echo 'Import successful. Imported '.count($tree).'.';
								// handle count warning
								if(count($tree) !== $summ) {
									echo '<br>WARNING: Tree length ('.count($tree).') does not match expected result from xlsx ('.$summ.'). Missing '.($summ - count($tree)).'.';
								}
							} else {
								echo $error;
							}
						} else {
							echo $error;
						}
					} else {
						echo $error;
					}
				} else {
					echo 'ERROR: Folder '.$path.' is readonly.';
				}
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
