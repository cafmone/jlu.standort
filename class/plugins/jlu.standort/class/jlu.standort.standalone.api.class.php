<?php
/**
 * jlu_standort_standalone_api
 *
 * @package jlu_standort
 * @license ../LICENSE.TXT
 * @version 1.0
 * @copyright Copyright (c) 2020,
 * Justus-Liebig-Universitaet Giessen
 * Dezernat E - Liegenschaften, Bau und Technik
 * Abteilung E1 - Flaechenmanagement
 * E1.3 - Projektleitung CAFM-System
 */

class jlu_standort_standalone_api
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'action';
/**
* message param
* @access public
* @var string
*/
var $message_param = 'msg';
/**
* treeurl
* path too tree.js
* @access public
* @var string
*/
var $treeurl = '';
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
* language
* default language
* @access public
* @var string
*/
var $language = 'en';
/**
* translation
* @access public
* @var array
*/

var $lang = array(
	'link1' => 'Link 1',
	'link2' => 'Link 2',
	'link3' => 'Link 3',
	'link1_title' => '',
	'link2_title' => '',
	'link3_title' => '',
	'accessibility' => 'Accessibility',
	'accessibility_title' => '',
	'usage' => 'Usage',
	'room' => 'Room %s',
	'no_image' => 'No Image found!',
	'no_building' => 'Please select a building first!',
);

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
		$this->profilesdir = PROFILESDIR;

		// handle derived language
		$this->langdir = CLASSDIR.'plugins/jlu.standort/lang/';
		if($this->file->exists(PROFILESDIR.'jlu.standort/lang/en.standort.standalone.api.ini')) {
			$this->langdir = PROFILESDIR.'jlu.standort/lang/';
		}

		// handle derived templates
		$this->tpldir = CLASSDIR.'plugins/jlu.standort/templates/';
		if($this->file->exists(PROFILESDIR.'jlu.standort/templates/jlu.standort.standalone.api.html')) {
			$this->tpldir = PROFILESDIR.'jlu.standort/templates/';
		}

		// get languages (xss)
		$languages = array();
		$files = glob($this->langdir.'*.jlu.standort.standalone.ini');
		if(is_array($files)) {
			foreach($files as $f) {
				$tmp = explode('.', basename($f));
				$languages[$tmp[0]] = $tmp[0];
			}
		}

		// filter Gui lang by languages (xss)
		$lang = $this->response->html->request()->get('lang', true);
		if(!isset($lang)) {
			$lang = $this->language;
		} else {
			if(!array_key_exists($lang, $languages)) {
				$lang = $this->language;
			}
		}
		$this->user->lang = $lang;
		$this->translation = $this->user->translate($this->lang, $this->langdir, 'jlu.standort.standalone.api.ini');

		// escape id (xss)
		$id = $this->response->html->request()->get('id');
		if($id !== '') {
			$this->id = substr(htmlspecialchars($id), 0, 30);
			$this->response->add('id',$this->id);
		}

		// Debug
		$debug = $this->response->html->request()->get('debug', true);
		if(isset($debug)) {
			$this->debug = true;
		}

		// Image types
		$this->imagetypes= array('jpg','JPG','png','PNG','svg','SVG');

		// Identifiers
		$this->identifiers = array();
		$this->levels = array();
		$tmp = $this->file->get_ini($this->langdir.$lang.'.jlu.standort.standalone.ini');
		if(is_array($tmp) && isset($tmp['identifiers'])) {
			$this->levels = array_keys($tmp['identifiers']);
			$this->identifiers = $tmp['identifiers'];
		}

		// grrr - Windows
		$this->PROFILESDIR = realpath(PROFILESDIR);

	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 */
	//--------------------------------------------
	function action() {
		if(!isset($this->id)) {
			echo 'ERROR: no ID given';
		} else {
			$action = $this->response->html->request()->get($this->actions_name);
			if($action !== '') {
				$this->response->add($this->actions_name, $action);
			}
			switch( $action ) {
				default:
				case '':
				case 'select':
					$this->select(true);
				break;
				case 'access':
					$this->access(true);
				break;
				case 'usage':
					#$this->usage(true);
				break;
				case 'image':
					$this->image(true);
				break;
				case 'download':
					$this->download(true);
				break;
			}
		}
	}

	//--------------------------------------------
	/**
	 * Accessibility (Barrierefreiheit)
	 *
	 * @access public
	 */
	//--------------------------------------------
	function access($visible = false) {
		if($visible === true && isset($this->id)) {

			// handle tree data
			$treeurl = $this->response->html->thisdir.$this->treeurl;
			if($this->file->exists($treeurl)) {
				$tree = json_decode(str_replace('var tree = ', '', $this->file->get_contents($treeurl)), true);
			}
			if(!isset($tree)) {
				$tree = array();
			}

			// Level
			$level = 0;
			if(isset($tree[$this->id])) {
				$ident = $tree[$this->id]['v'];
				$level = array_search($ident, $this->levels);
				if($level === false) {
					$level = 0;
				}
			}

			// handle id to level
			if($level > 3) {
				$id = $this->id;
				for($i = $level; $i > 3; $i--) {
					$id = $tree[$id]['p'];
				}
			}
			elseif($level === 3) {
				$id = $this->id;
			}
			elseif($level < 3) {
				echo $this->translation['no_building'];
				exit();
			}

			// handle path
			$path = $tree[$id]['l'];
			$tmp  = $id;
			for($i = 3; $i > 0; $i--) {
				$tmp  = $tree[$tmp]['p'];
				$path = $tree[$tmp]['l'].' | '.$path;
			}
			echo '<div style="padding:10px;text-align:center;">'.$path.'</div>';

			$this->response->html->help($tree[$id]);
		}
	}

	//--------------------------------------------
	/**
	 * Select
	 *
	 * @access public
	 */
	//--------------------------------------------
	function select($visible = false) {
		if($visible === true && isset($this->id)) {

			// handle tree data
			$treeurl = $this->response->html->thisdir.$this->treeurl;
			if($this->file->exists($treeurl)) {
				$tree = json_decode(str_replace('var tree = ', '', $this->file->get_contents($treeurl)), true);
			}
			if(!isset($tree)) {
				$tree = array();
			}

			// Label
			$label = '';
			if(isset($tree[$this->id])) {
				$label = $tree[$this->id]['l'];
			}

			// Identifier
			$identifier = '';
			if(isset($tree[$this->id])) {
				$ident = $tree[$this->id]['v'];
				if(isset($this->identifiers[$ident])) {
					$identifier = $ident;
				}
			}

			// get current level
			$level = '';
			if(isset($tree[$this->id])) {
				$ident = $tree[$this->id]['v'];
				$level = array_search($ident, $this->levels);
				if($level === false) {
					$level = '';
				}
			}

			// handle image
			$filetypes = $this->imagetypes;
			foreach($filetypes as $type) {
				$imgpath = $this->PROFILESDIR.'/jlu.standort/bilder/'.$this->id.'.'.$type;
				if($this->file->exists($imgpath)) {
					$type = strtolower($type);
					if($type === 'jpg') {
						$width = '';
						$size  = getimagesize($imgpath);
						if(isset($size[0])) {
							$width = ' max-width:'.$size[0].'px;';
						}
						$data  = base64_encode($this->file->get_contents($imgpath));
						$image = '<img title="'.$label.'" src="data:image/jpeg;base64, '.$data.'" style="width:100%;'.$width.' height:auto; cursor:pointer;" onclick="imagebox.init(this);">';
					}
					if($type === 'png') {
						$width = '';
						$size  = getimagesize($imgpath);
						if(isset($size[0])) {
							$width = ' max-width:'.$size[0].'px;';
						}
						$data  = base64_encode($this->file->get_contents($imgpath));
						$image = '<img title="'.$label.'" src="data:image/png;base64, '.$data.'" style="width:100%;'.$width.' height:auto; cursor:pointer;" onclick="imagebox.init(this);">';
					}
					elseif($type === 'svg') {
						$image = $this->file->get_contents($imgpath);
					}
					break;
				}
			}
			if(!isset($image)) {
				$image = $this->translation['no_image'];
			}

			// handle datadir
			$files = '';
			$datadir = $this->PROFILESDIR.'/jlu.standort/data/'.$this->id;
			if($this->file->exists($datadir)) {
				$f = $this->file->get_files($datadir);
				if(is_array($f)) {
					foreach($f as $file) {
						$a = $this->response->html->a();
						$a->label = $file['name'];
						$a->href = $this->response->get_url($this->actions_name, 'download').'&file='.urlencode($file['name']);
						$files .= $a->get_string().'<br>';
					}
				}
			}

			// handle links
			$linkspath = $this->response->html->thisdir.'cache/links.json';
			if($this->file->exists($linkspath)) {
				$tmp = json_decode($this->file->get_contents($linkspath), true);
				if(isset($tmp[$this->id]) && is_array($tmp[$this->id])) {
					$rightbar = '';
					foreach($tmp[$this->id] as $k => $link) {
						$rightbar .= '<span class="'.$k.'"><a title="'.$this->translation[$k.'_title'].'" href="'.$link.'" target="_blank">'.$this->translation[$k].'</a></span>';
					}
				}
			}

			// handle accessibility
			if($level >= 3) {
				if(!isset($rightbar)) {
					$rightbar = '';
				}
				$rightbar .= '<span class="access"><a href="#" onclick="accessbuilder.init('.$this->id.');" title="'.$this->translation['accessibility_title'].'">'.$this->translation['accessibility'].'</a></span>';
			}

			// handle usage (only level 3 and 4)
			if($level === 3 || $level === 4) {
				$usagepath = $this->response->html->thisdir.'cache/usage.json';
				if($this->file->exists($usagepath)) {

					$children = array();
					// get children
					if($level === 3) {
						$floors   = $this->__children($tree, $this->id);
						foreach($floors as $k => $floor) {
							$tmp      = $this->__children($tree, $k);
							$children = array_replace($tmp, $children);
						}
					}
					elseif($level === 4) {
						$children = $this->__children($tree, $this->id);
					}

					$tmp    = json_decode($this->file->get_contents($usagepath), true);
					$usages = array_unique($tmp);
					$used   = array();
					$rooms  = array();

					// handle available usage
					foreach($children as $k => $c) {
						if(isset($tmp[$k]) && $tmp[$k] !== '') {
							$used[] = $tmp[$k];
							if($level === 3) {
								$rooms[md5($tmp[$k])][] = '<li><a href="?id='.$k.'" onclick="usagebuilder.close();">'.$tree[$tree[$k]['p']]['l'].', '.sprintf($this->translation['room'], $tree[$k]['l']).'</a></li>';
							}
						}
					}

					$leftbar  = '<div id="UsageBox" class="noprint">';
					$leftbar .= '<label>'.$this->translation['usage'].'</label>';
					$leftbar .= '<select id="UsageSelect" class="form-control" onchange="usagebuilder.print()">';
					$leftbar .= '<option></option>';
					foreach($usages as $u) {
						if($u !== '') {
							$disabled = '';
							if(!in_array($u, $used)) {
								$disabled = ' disabled="disabeled"';
							}
							$leftbar .= '<option value="'.md5($u).'" '.$disabled.'>'.$u.'</option>';
						}
					}
					$leftbar .= '</select>';
					if($level === 3) {
						$leftbar .= '<div>';
						foreach($rooms as $k => $v) {
							$leftbar .= '<div id="'.$k.'" style="display:none;">';
							$leftbar .= '<div style="margin: 0 0 15px 0;">'.$label.' | '.$tree[$tree[$this->id]['p']]['l'].'</div>';
							$leftbar .= '<ul>';
							$leftbar .= implode('',$v);
							$leftbar .= '</ul>';
							$leftbar .= '</div>';
						}
						$leftbar .= '</div>';
					}
					$leftbar .= '</div>';

					if(isset($this->debug) && isset($children)) {
						foreach ($children as $k => $c) {
							echo '<div>"'.$k.'":"'.$c['l'].'",</div>';
						}
					}
				}
			}

			// template
			$t = $this->response->html->template($this->tpldir.'jlu.standort.standalone.api.html');
			$vars = array(
				'id' => '',
				'files' => $files,
				'image' => $image,
				'thisfile' => $this->response->html->thisfile,
				'cssurl' => $this->cssurl,
				'jsurl' => $this->jsurl,
				'imgurl' => $this->imgurl,
			);
			$t->add($vars);

			$return['content']  = $t->get_string();
			$return['rightbar'] = (isset($rightbar)) ? $rightbar : '';
			$return['leftbar']  = (isset($leftbar)) ? $leftbar : '';

			echo json_encode($return, true);
		}
	}

	//--------------------------------------------
	/**
	 * image
	 *
	 * @access public
	 * @return null
	 */
	//--------------------------------------------
	function image($visible = false) {
		if($visible === true) {
			if(isset($this->id)) {
				foreach($this->imagetypes as $type) {
					$path = $this->PROFILESDIR.'/jlu.standort/bilder/'.$this->id.'.'.$type;
					if($path !== '' && $this->file->exists($path)) {
						require_once(realpath(CLASSDIR).'/lib/file/file.mime.class.php');
						$mime = detect_mime($path);
						$file = $this->file->get_fileinfo($path);
						header("Pragma: public");
						header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
						header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
						header("Cache-Control: must-revalidate");
						header("Content-type: $mime");
						header("Content-Length: ".$file['filesize']);
						header("Content-disposition: inline; filename=".$file['name']);
						header("Accept-Ranges: ".$file['filesize']);
						flush();
						readfile($path);
						exit(0);
					}
				}
			}
		}
	}

	//--------------------------------------------
	/**
	 * download
	 *
	 * @access public
	 * @return null
	 */
	//--------------------------------------------
	function download($visible = false) {
		if($visible === true) {
			if(isset($this->id)) {
				$file = $this->response->html->request()->get('file');
				$path = $this->PROFILESDIR.'/jlu.standort/data/'.$this->id.'/'.$file;
				if($path !== '' && $this->file->exists($path)) {
					require_once(realpath(CLASSDIR).'/lib/file/file.mime.class.php');
					$mime = detect_mime($path);
					$file = $this->file->get_fileinfo($path);
					header("Pragma: public");
					header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
					header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
					header("Cache-Control: must-revalidate");
					header("Content-type: $mime");
					header("Content-Length: ".$file['filesize']);
					header("Content-disposition: attachment; filename=".$file['name']);
					header("Accept-Ranges: ".$file['filesize']);
					#ob_end_flush();
					flush();
					readfile($path);
					exit(0);
				}
			}
		}
	}

	//--------------------------------------------
	/**
	 * Get Children by id
	 *
	 * @access public
	 * @return array
	 */
	//--------------------------------------------
	function __children($tree, $id) {
		$result = array();
		foreach($tree as $k => $item) {
			if(isset($item['p']) && $item['p'] == $id) {
				$result[$k] = $item;
			}
		}
		return $result;
	}

}
?>
