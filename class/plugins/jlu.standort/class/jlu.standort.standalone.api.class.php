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
* qrcodeurl
* baseurl for qrcodes
* @access public
* @var string
*/
var $qrcodeurl;

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
	'print' => 'Print',
	'print_title' => 'Print Page',
	'save' => 'save',
	'qrcode' => 'Qrcode',
	'qrcode_title' => 'Qrcode title',
	'zlisurl' => 'Link 1',
	'zlisurl_title' => '',
	'jlucourl' => 'Link 2',
	'jlucourl_title' => '',
	'googlemapsurl' => 'Google Maps',
	'googlemapsurl_title' => '',
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

		// grrr - Windows
		$this->PROFILESDIR = realpath(PROFILESDIR).'/';
		$this->CLASSDIR = realpath(CLASSDIR).'/';

		// handle derived language
		$this->langdir = $this->CLASSDIR.'plugins/jlu.standort/lang/';
		if($this->file->exists($this->PROFILESDIR.'jlu.standort/lang/de.jlu.standort.standalone.api.ini')) {
			$this->langdir = $this->PROFILESDIR.'jlu.standort/lang/';
		}

		// handle derived templates
		$this->tpldir = $this->CLASSDIR.'plugins/jlu.standort/templates/';
		if($this->file->exists($this->PROFILESDIR.'jlu.standort/templates/jlu.standort.standalone.api.html')) {
			$this->tpldir = $this->PROFILESDIR.'jlu.standort/templates/';
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

		// Identifiers
		$this->identifiers = array();
		$this->levels = array();
		$tmp = $this->file->get_ini($this->langdir.$lang.'.jlu.standort.standalone.ini');
		if(is_array($tmp) && isset($tmp['identifiers'])) {
			$this->levels = array_keys($tmp['identifiers']);
			$this->identifiers = $tmp['identifiers'];
		}


	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 */
	//--------------------------------------------
	function action() {
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
			case 'thumb':
				$this->image(true, 'thumbs');
			break;
			case 'download':
				$this->download(true);
			break;
			case 'pdf':
				$this->pdf(true);
			break;
			case 'qrcode':
				$this->qrcode(true);
			break;
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

			$content = json_decode($this->file->get_contents($this->response->html->thisdir.'cache/accessibility.json'), true);
			$lang = $this->file->get_ini($this->langdir.'de.jlu.standort.standalone.accessibility.ini');
			$lang = $this->user->translate($lang, $this->langdir, 'jlu.standort.standalone.accessibility.ini');

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

			$t = $this->response->html->template($this->tpldir.'jlu.standort.standalone.api.accessibility.html');
			$t->add($path, 'path');
			foreach($lang['headlines'] as $k => $v) {
				$t->add('<h4>'.$v.'</h4>', 'headline_'.$k);
			}

			foreach($lang['gebaeude'] as $k => $v) {
				$t->add('', 'gebaeude_'.$k);
			}

			if(isset($content[$id])) {
				$current = $content[$id];
				foreach($lang['gebaeude'] as $k => $v) {
					if(isset($current[$k]) && $current[$k] !== '') {
						$str  = '<div style="margin: 0 0 8px 0">';
						$str .= '<strong>'.$v.':</strong> ';
						$str .= '<span>'.$current[$k].'</span>';
						$str .= '</div>';
						$t->add($str, 'gebaeude_'.$k);
					}
				}
			}

			$str = '';
			$floors = $this->__children($tree, $id);
			if(is_array($floors)) {
				foreach($floors as $key => $floor) {
					$rooms = $this->__children($tree, $key);
					if(is_array($rooms)) {
						foreach($rooms as $k => $room) {
							if(isset($content[$k])) {
								$str .= '<div style="margin: 0 0 8px 0">';
								$str .= '<div>'.$floor['l'].', '.sprintf($this->translation['room'], $room['l']) .'</div>';
								foreach($lang['raum'] as $l => $v) {
									if(isset($content[$k][$l])) {
										$str .= '<strong>'.$v.':</strong> '.$content[$k][$l].'<br>';
									}
								}
								$str .= '</div>';
							}
						}
					}
				}
			}
			if($str === '') {
 				$t->add('','headline_raum');
			}
			$t->add($str, 'rooms');

			echo $t->get_string();

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
			
			// handle map disclaimer
			$usemap = false;
			if(!isset($_COOKIE['useMap'])) {
				$usemap = true;
			}
			elseif($_COOKIE['useMap'] === 'true') {
				$usemap = true;
			}
## TODO
			// change imageid to parent if level = 2 (liegenschaft)
			$imageid = $this->id;
			if($level === 2) {
				if(isset($tree[$this->id]['p'])) {
					$imageid = $tree[$this->id]['p'];
				}
			}
			// change imageid to parent if level = 5 (room)
			if($level === 5) {
				if(isset($tree[$this->id]['p'])) {
					$imageid = $tree[$this->id]['p'];
				}
			}
			// handle image
			$path = $this->PROFILESDIR.'/jlu.standort/bilder';
			$files = $this->file->get_files($path);

			foreach($files as $file) {
				if(preg_match('~^'.$imageid.'~', $file['name'])) {
					$imgpath = $file['path'];
					$type = strtolower($file['extension']);

					if($usemap === true && $type === 'geo') {
						$geo = $this->file->get_ini($imgpath);
						if(is_array($geo)) {
							$url = '';
							$c = 0;
							$form = '<form id="MapForm" action="jlu.map.php" target="MapFrame" methode="POST">';
							foreach($geo as $k => $v) {
								if(array_key_exists($k, $tree)) {
									$form .= '<input type="hidden" name="lang" value="'.$this->user->lang.'">';
									$form .= '<input type="hidden" name="m['.$c.'][lon]" value="'.$v['long'].'">';
									$form .= '<input type="hidden" name="m['.$c.'][lat]" value="'.$v['lat'].'">';
									$form .= '<input type="hidden" name="m['.$c.'][title]" value="'.$tree[$k]['l'].'">';
									#$form .= '<input type="hidden" name="m['.$c.'][link]" value="'.$this->qrcodeurl.'?id='.$k.'&lang='.$this->user->lang.'">'
									$form .= '<input type="hidden" name="m['.$c.'][link]" value="?id='.$k.'&lang='.$this->user->lang.'">';
									$form .= '<input type="hidden" name="m['.$c.'][id]" value="'.$k.'">';
									if(isset($tree[$tree[$k]['p']]['l'])) {
										$form .= '<input type="hidden" name="m['.$c.'][addr]" value="'.$tree[$tree[$k]['p']]['l'].'">';
									}
									if($this->file->exists($this->PROFILESDIR.'/jlu.standort/thumbs/'.$k.'.jpg')) {
										$form .= '<input type="hidden" name="m['.$c.'][thumb]" value="jlu.standort.api.php?'.$this->actions_name.'=thumb&file='.$k.'.jpg">';
									}
									elseif($this->file->exists($this->PROFILESDIR.'/jlu.standort/thumbs/'.$k.'.png')) {
										$form .= '<input type="hidden" name="m['.$c.'][thumb]" value="jlu.standort.api.php?'.$this->actions_name.'=thumb&file='.$k.'.png">';
									}
									elseif($this->file->exists($this->PROFILESDIR.'/jlu.standort/thumbs/noimage.jpg')) {
										$form .= '<input type="hidden" name="m['.$c.'][thumb]" value="jlu.standort.api.php?'.$this->actions_name.'=thumb&file=noimage.jpg">';
									}
									$c++;
								}
							}
							$form .= '</form>';
							$image = $form.'<div id="MapFrame" style="width:calc(100% -1px);height:calc(70vh);"></div>';
						}
					}
					elseif($type === 'jpg' || $type === 'png') {
						$width = '';
						$size  = getimagesize($imgpath);
						if(isset($size[0])) {
							$width = ' max-width:'.$size[0].'px;';
						}
						$image  = '<img title="'.$label.'" ';
						$image .= 'src="jlu.standort.api.php?action=image&file='.$file['name'].'" ';
						$image .= 'style="width:100%;'.$width.' height:auto; cursor:pointer;" ';
						$image .= 'onclick="imagebox.init(this);">';
					}
					elseif($type === 'pdf') {

						$image  = '<div class="iframe">';
						$image .= '<iframe src="pdf.viewer.html?file='.urlencode('jlu.standort.api.php?action=image&file=').''.$file['name'].'"></iframe>';
						#$data  = base64_encode($this->file->get_contents($imgpath));
						#$image .= '<iframe src="data:application/pdf;base64,'.$data.'"></iframe>';
						#$image .= '<object data="data:application/pdf;base64,'.$data.'" type="application/pdf" style="heigth:300px;width:100%;" ></object>';
						#$image .= '<embed src="data:application/pdf;base64,'.$data.'" type="application/pdf" style="heigth:300px;width:100%;" ></embed>';
						$image .= '</div>';
					}
					elseif($type === 'svg') {
						$image = $this->file->get_contents($imgpath);
					}
					// one try only
					break;
				}
			}
			if(!isset($image)) {
				$image  = '<div style="text-align:center;">';
				$image .= '<img title="'.$this->translation['no_image'].'" ';
				$image .= 'src="jlu.standort.api.php?action=image&file=noimage.jpg" ';
				$image .= 'style="" ';
				$image .= '><br>';
				$image .= $this->translation['no_image'];
				$image .= '</div>';
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

			// init rightbar container
			$rightbar = '';

			// handle qrcode
			$a = $this->response->html->a();
			$a->label = $this->translation['qrcode'];
			$a->title = $this->translation['qrcode_title'];
			$a->href = '#';
			$a->handler = 'onclick="qrcodebuilder.print();"';

			$url  = $this->response->html->thisfile;
			$url .= '?'.$this->actions_name.'=qrcode';
			$url .= '&id='.$this->id;
			$url .= '&lang='.$this->user->lang;

			$rightbar .= '<span class="qrcode">'.$a->get_string().'</span>';
			$rightbar .= '<div id="QRCODE" style="display:none;">';
			$rightbar .= $this->qrcode(true, false);
			$rightbar .= '<div style="margin-top:15px;"><a href="'.$url.'">'.$this->translation['save'].'</a></div>';
			$rightbar .= '</div>';


			// handle links
			$linkspath = $this->response->html->thisdir.'cache/links.json';
			if($this->file->exists($linkspath)) {
				$tmp = json_decode($this->file->get_contents($linkspath), true);
				// get available links
				if(is_array($tmp) && count($tmp) > 0 ) {
					$links = $tmp[key($tmp)];
				}
			}

			if(isset($links)) {
				foreach($links as $k => $link) {
					$linkid = $this->id;
					// handle stud.ip not restricted to level 3
					if($k !== 'zlisurl') {
						// handle link maximum = level 3
						if($level >= 3) {
							$linkid = $this->__building($tree, $this->id);
						}
					} else {
						// choose gebauede if view is geschoss
						if($level == 4) {
							$linkid = $this->__building($tree, $this->id);
						}
					}
					// build link
					if(isset($tmp[$linkid]) && $tmp[$linkid][$k] !== '') {
						$rightbar .= '<span class="'.$k.'"><a title="'.$this->translation[$k.'_title'].'" href="'.$tmp[$linkid][$k].'" target="_blank">'.$this->translation[$k].'</a></span>';
					} else {
						$rightbar .= '<span class="'.$k.'"><a class="disabled" title="'.$this->translation[$k.'_title'].'">'.$this->translation[$k].'</a></span>';
					}
				}
			}

			// handle right bar pdf
			$pdfid = $this->id;

			// change pdfid to parent if level = 5 (room)
			if($level === 5) {
				if(isset($tree[$this->id]['p'])) {
					$pdfid = $tree[$this->id]['p'];
				}
			}

			$pdfpath = $this->PROFILESDIR.'/jlu.standort/pdf/'.$pdfid.'.pdf';
			if($this->file->exists($pdfpath)) {
				$a = $this->response->html->a();
				$a->label = $this->translation['print'];
				$a->title = $this->translation['print_title'];
				$a->href = $this->response->get_url('id',$pdfid).'&'.$this->actions_name.'=pdf&file='.urlencode($pdfid.'.pdf');
				$rightbar .= '<span class="print">'.$a->get_string().'</span>';
			} else {
				$a = $this->response->html->a();
				$a->label = $this->translation['print'];
				$a->title = $this->translation['print_title'];
				$a->css = 'disabled';
				$rightbar .= '<span class="print">'.$a->get_string().'</span>';
			}

			// handle accessibility
			if($level >= 3) {
				$rightbar .= '<span class="access"><a href="#" onclick="accessbuilder.init('.$this->id.');" title="'.$this->translation['accessibility_title'].'">'.$this->translation['accessibility'].'</a></span>';
			} else {
				$rightbar .= '<span class="access"><a class="disabled" title="'.$this->translation['accessibility_title'].'">'.$this->translation['accessibility'].'</a></span>';
			}


			// handle usage (only level 3, 4 and 5)
			if($level === 3 || $level === 4 || $level === 5) {
				$usagepath = $this->response->html->thisdir.'cache/nutzung.json';
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
					elseif($level === 5) {
						$children = $this->__children($tree, $tree[$this->id]['p']);
					}

					$tmp = json_decode($this->file->get_contents($usagepath), true);
					$lang = $this->file->get_ini($this->langdir.'de.jlu.standort.standalone.nutzung.ini');

					$usages = $this->user->translate($lang, $this->langdir, 'jlu.standort.standalone.nutzung.ini');
					asort($usages);

					$used   = array();
					$rooms  = array();

					// handle available usage
					foreach($children as $k => $c) {
						if(isset($tmp[$k]) && $tmp[$k] !== '') {
							if($level === 3 || $level === 4 || $level === 5) {
								foreach($tmp[$k] as $n) {
									$used[] = $n;
									$rooms[md5($n)][] = '<li><a href="?id='.$k.'" onclick="usagebuilder.close();">'.$tree[$tree[$k]['p']]['l'].', '.sprintf($this->translation['room'], $tree[$k]['l']).'</a></li>';
								}
							}
						}
					}

					$leftbar  = '<div id="UsageBox" class="noprint">';
					$leftbar .= '<label>'.$this->translation['usage'].'</label>';
					$leftbar .= '<select id="UsageSelect" class="form-control selectpicker" title="&#160;" onchange="usagebuilder.print()">';
					foreach($usages as $k => $u) {
						if($u !== '') {
							$disabled = '';
							if(!in_array($k, $used)) {
								$disabled = ' disabled="disabeled"';
							}
							$leftbar .= '<option value="'.md5($k).'" '.$disabled.'>'.$u.'</option>';
						}
					}
					$leftbar .= '</select>';
					if($level === 3 || $level === 4 || $level === 5) {
						$leftbar .= '<div>';
						foreach($rooms as $k => $v) {
							$leftbar .= '<div id="'.$k.'" style="display:none;">';
							if($level !== 5) {
								$leftbar .= '<div style="margin: 0 0 15px 0;">'.$label.' | '.$tree[$tree[$this->id]['p']]['l'].'</div>';
							} else {
								$label  = $tree[$tree[$this->id]['p']]['l'];
								$label .= ' | '.$tree[$tree[$tree[$this->id]['p']]['p']]['l'];
								$leftbar .= '<div style="margin: 0 0 15px 0;">'.$label.'</div>';
							}
							$leftbar .= '<ul>';
							$leftbar .= implode('',$v);
							$leftbar .= '</ul>';
							$leftbar .= '</div>';
						}
						$leftbar .= '</div>';
					}
					$leftbar .= '</div>';

					if(isset($this->debug) && isset($children)) {
						echo '<div>Children</div>';
						$this->response->html->help($children);
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

			if(isset($this->debug)) {
				$this->response->html->help($return);
			} else {
				echo json_encode($return, true);
			}
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
	function image($visible = false, $folder = 'bilder') {
		if($visible === true) {
			$file = $this->response->html->request()->get('file');
			$path = $this->PROFILESDIR.'/jlu.standort/'.$folder.'/'.$file;
			if(!$this->file->exists($path)) {
				$path = $this->PROFILESDIR.'/jlu.standort/'.$folder.'/noimage.jpg';
				if(!$this->file->exists($path)) {
					exit(0);
				}
			}
			$file = $this->file->get_fileinfo($path);
			require_once(realpath(CLASSDIR).'/lib/file/file.mime.class.php');
			$mime = detect_mime($file['path']);
			header("Pragma: public");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: must-revalidate");
			header("Content-type: $mime");
			header("Content-Length: ".$file['filesize']);
			header("Content-disposition: inline; filename=".$file['name']);
			header("Accept-Ranges: ".$file['filesize']);
			flush();
			readfile($file['path']);
			exit(0);
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
	 * qrcode
	 *
	 * @access public
	 * @return null
	 */
	//--------------------------------------------
	function qrcode($visible = false, $print = true) {
		if($visible === true) {
			if(isset($this->id)) {

				if(!isset($this->qrcodeurl)) {
					$url  = $_SERVER['REQUEST_SCHEME'].'://';
					$url .= $_SERVER['SERVER_NAME'];
					if($this->response->html->thisurl !== '') {
						$url .= $this->response->html->thisurl.'/';
					}
				} else {
					$url = $this->qrcodeurl;
				}

				$url .= '?id='.$this->id;
				if(isset($this->user->lang)) {
					$url .= '&lang='.$this->user->lang;
				}

				require_once($this->CLASSDIR.'lib/pdf/tcpdf/tcpdf_barcodes_2d.php');

				// set the barcode content and type
				$obj = new TCPDF2DBarcode($url, 'QRCODE,L');

				if($print === true) {
					$code = $obj->getBarcodePngData(5,5,array(0,0,0));

					// send headers
					header('Content-Type: image/png');
					header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
					header('Pragma: public');
					header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
					header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
					header("Content-disposition: attachment; filename=JLU.Qrcode.".$this->id.".png");
					echo $code;

				} else {
					$code = $obj->getBarcodeSVGcode(5,5,'black');
					return $code;
				}
			}
		}
	}

	//--------------------------------------------
	/**
	 * PDF download
	 *
	 * @access public
	 * @return null
	 */
	//--------------------------------------------
	function pdf($visible = false) {
		if($visible === true) {
			if(isset($this->id)) {

				// handle tree data
				$treeurl = $this->response->html->thisdir.$this->treeurl;
				if($this->file->exists($treeurl)) {
					$tree = json_decode(str_replace('var tree = ', '', $this->file->get_contents($treeurl)), true);
				}
				if(!isset($tree)) {
					$tree = array();
				}

				if(isset($tree[$this->id])) {
					if(isset($tree[$this->id]['l']) && $tree[$this->id]['l'] !== '') {
						$name = '';
						if(isset($tree[$this->id]['p'])) {
							$geb = $tree[$this->id]['p'];
							// handle liegenschaft
							if(isset($tree[$geb]['p'])) {
								$lie = $tree[$geb]['p'];
								if(isset($tree[$lie]['l'])) {
									$name .= $tree[$lie]['l'].' - ';
								}
							}
							// handle gebaeude
							if(isset($tree[$geb]['l'])) {
								$name .= $tree[$geb]['l'].' - ';
							}
						}
						$name .= $tree[$this->id]['l'].'.pdf';
						$name = html_entity_decode($name);

						// remove ,
						$name = str_replace(',','',$name);

						$file = $this->response->html->request()->get('file');
						
						$path = $this->PROFILESDIR.'/jlu.standort/pdf/'.$file;
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
							header("Content-disposition: attachment; filename=".$name);
							header("Accept-Ranges: ".$file['filesize']);
							#ob_end_flush();
							flush();
							readfile($path);
							exit(0);
						}
					}
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

	//--------------------------------------------
	/**
	 * Get Building 
	 *
	 * @access public
	 * @return array
	 */
	//--------------------------------------------
	function __building($tree, $id) {
		if(isset($tree[$id]) && $tree[$id]['p'] !== '') {
			if($tree[$id]['v'] !== 'gebauede') {
				$id = $tree[$id]['p'];
				if(isset($tree[$id]) && $tree[$id]['p'] !== '') {
					if($tree[$id]['v'] !== 'gebauede') {
						return $tree[$id]['p'];
					} else {
						return $id;
					}
				}
			} else {
				return $id;
			}
		}
	}

}
?>
