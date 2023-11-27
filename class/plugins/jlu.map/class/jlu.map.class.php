<?php
/**
 * jlu_map
 *
 * @package jlu_map
 * @license ../LICENSE.TXT
 * @version 1.0
 * @copyright Copyright (c) 2022,
 * Justus-Liebig-Universitaet Giessen
 * Dezernat E - Liegenschaften, Bau und Technik
 * Abteilung E1 - Flaechenmanagement
 * E1.3 - Projektleitung CAFM-System.
 */

class jlu_map
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'map_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = 'map_msg';
/**
* identifier
* @access public
* @var string
*/
var $identifier_name = 'map_ident';

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
* tileserverurl
* path too openstreetmap tile server
* @access public
* @var string
*/
var $tileserverurl = 'https://{a-c}.tile.openstreetmap.de/{z}/{x}/{y}.png';
/**
* googleurl
* path too googleroute server
* @access public
* @var string
*/
var $googleurl = 'https://www.google.com/maps/dir//';

/**
* disclaimer 
* openStreeMap privacy disclaimer
* set to false to disable
* @access public
* @var bool
*/
var $disclaimer = true;

/**
* title
* page Title
* @access public
* @var string
*/
var $title = '';
/**
* translation
* @access public
* @var array
*/
var $language = 'de';

/**
* translation
* @access public
* @var array
*/
var $lang = array(
	'label_usage' => 'Usage',
	'label_google_route' => 'Route',
	'title_google_route' => 'Click to open Google Route',
	'title_center_map' => 'Center map',
	'title_thumb' => 'Click to open page',
	'title_address' => 'Click here to show only this building on the map',
	'title_floors' => 'xxx',
	'title_zoom_in' => 'Zoom in',
	'title_zoom_out' => 'Zoom out',
	'disclaimer' => array(
		'disclaimer' => 'This site loads data from the free map service <a href="https://www.openstreetmap.org" target="_blank">OpenStreetMap&copy;</a><br><br>Proceed?',
		'button_yes' => 'Yes',
		'button_no' => 'No',
	)
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
		$this->CLASSDIR    = realpath(CLASSDIR).'/';

		// handle derived language
		$this->langdir = $this->CLASSDIR.'plugins/jlu.map/lang/';
		if($this->file->exists($this->PROFILESDIR.'jlu.map/lang/de.jlu.map.ini')) {
			$this->langdir = $this->PROFILESDIR.'jlu.map/lang/';
		}

		// get languages (xss)
		$languages = array();
		$files = glob($this->langdir.'*.jlu.map.ini');
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
		$this->lang = $this->user->translate($this->lang, $this->langdir, 'jlu.map.ini');
	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 */
	//--------------------------------------------
	function action() {
	
		$markers = $this->response->html->request()->get('m', true);
		$zoom    = $this->response->html->request()->get('zoom', true);
		$debug   = $this->response->html->request()->get('debug', true);

		// handle center map
		$top    = array("lon" => 0, "lat" => 0);
		$bottom = array("lon" => 0, "lat" => 0);
		$left   = array("lon" => 0, "lat" => 0);
		$right  = array("lon" => 0, "lat" => 0);

		// handle privacy
		$confirm = false;
		if(isset($_COOKIE['useMap']) && $_COOKIE['useMap'] === 'true' ) {
			$confirm = true;
		} else {
			if(isset($this->disclaimer) && $this->disclaimer === false) {
				if(intval(PHP_MAJOR_VERSION.PHP_MINOR_VERSION) >= 73) {
					$cookie_options = array (
						'expires' => 0,
						'path' => '/',
						'secure' => false,
						'httponly' => false,
						'samesite' => 'Strict'
					);
					setcookie('useMap', 'true', $cookie_options);
				} else {
					setcookie('useMap', 'true', 0, '/', '', false, false);
				}
				$confirm = true;
			}
		}

		if($confirm === true) {
			$help = '';
			$tags = array();
			$script = 'markers=[';
			if(is_array($markers)) {
				foreach($markers as $marker) {
					if(isset($marker['lon']) && isset($marker['lat'])) {
					
						// handle XSS
						$marker['lon'] = floatval($marker['lon']);
						$marker['lat'] = floatval($marker['lat']);

						$script .= '{"lon":"'.$marker['lon'].'","lat":"'.$marker['lat'].'"';
						(isset($marker['title'])) ? $script .= ',"title":"'.htmlentities($marker['title']).'"': '"title":""';
						(isset($marker['link']))  ? $script .= ',"link":"'.htmlspecialchars($marker['link']).'"' : '"link":""';
						(isset($marker['addr']))  ? $script .= ',"addr":"'.htmlentities($marker['addr']).'"': '"addr":""';
						(isset($marker['thumb'])) ? $script .= ',"thumb":"'.htmlspecialchars($marker['thumb']).'"': '"thumb":""';
						(isset($marker['icon']))  ? $script .= ',"icon":"'.htmlentities($marker['icon']).'"': '"icon":""';
						(isset($marker['id']))    ? $script .= ',"id":"'.htmlspecialchars($marker['id']).'"': '"id":""';
						if(isset($marker['text'])){
							// test ?m[0][lon]=8.67722&m[0][lat]=50.58038&m[0][title]=cc&m[0][text]=<b>Text</b> <span>me</span> in " " <b>bold</b> <u>test</u> empty
							$tmp = preg_replace('~(.*)\<(br|b|i|u|p)>(.*)~U', "$1[[$2]]$3", $marker['text'] );
							$tmp = preg_replace('~(.*)\</(b|i|u|p)>(.*)~U', "$1[[/$2]]$3", $tmp );
							$tmp = htmlentities($tmp);
							$tmp = preg_replace('~(.*)\[\[(br|b|i|u|p)\]\](.*)~U', "$1<$2>$3", $tmp );
							$tmp = preg_replace('~(.*)\[\[/(b|i|u|p)\]\](.*)~U', "$1</$2>$3", $tmp );
							$script .= ',"text":"'.$tmp.'"';
						} else {
							$script .= ',"text":""';
						}
						if(isset($marker['tag'])) {
							$tag = htmlentities($marker['tag']);
							$script .= ',"tag":"'.$tag.'"';
							$tags[$tag] = $tag;
							
						} else {
							$script .= ',"tag":""';
						}
						$script .= '},';
						
						// handle top
						if($marker['lat'] > $top['lat'] || $top['lat'] === 0) {
							$top['lon'] = $marker['lon'];
							$top['lat'] = $marker['lat'];
						}
						// handle bottom
						if($marker['lat'] < $bottom['lat'] || $bottom['lat'] === 0) {
							$bottom['lon'] = $marker['lon'];
							$bottom['lat'] = $marker['lat'];
						}
						// handle left
						if($marker['lon'] < $left['lon'] || $left['lon'] === 0) {
							$left['lon'] = $marker['lon'];
							$left['lat'] = $marker['lat'];
						}
						// handle right
						if($marker['lon'] > $right['lon'] || $right['lon'] === 0) {
							$right['lon'] = $marker['lon'];
							$right['lat'] = $marker['lat'];
						}
						
					} else {
						// nothing to do
						continue;
					}
					
				}
			} else {
				// no markers - some help
				$t = $this->response->html->template($this->CLASSDIR.'plugins/jlu.map/templates/jlu.map.help.html');
				$vars = array(
					'title' => $this->title,
					'cssurl' => $this->cssurl,
					'jsurl' => $this->jsurl,
					'imgurl' => $this->imgurl,
					'label_usage' => $this->lang['label_usage'],
				);
				$t->add($vars);
				$help = $t->get_string();
			}
			$script .= '];';
		
			if(is_array($markers)) {
				//  handle center
				$lon = $left['lon'] - ( ($left['lon'] - $right['lon']) / 2);
				$lat = $bottom['lat'] - ( ($bottom['lat'] - $top['lat']) / 2);

				$script .= "\n".'center=['.$lon.','.$lat.'];';
				if(!isset($zoom)) {
					$script .= "\n".'var resolution='.( $this->distance($top['lon'],$top['lat'],$bottom['lon'],$bottom['lat'])).';';
					$script .= "\n".'zoom=15;';
				} else {
					$script .= "\n".'zoom='.intval($zoom).';';
				}
			} else {
				$script .= "\n".'center=[8.67722, 50.58038];';
				$script .= "\n".'zoom=15;';
			}
			if(isset($debug)) {
				$script .= "\n".'debug=true;';
			}


#$tags['x'] = 'X';

			if(count($tags) > 1) {
				$script .= "\n".'tags=["'.implode('","',$tags).'"];';
			}
			
			
			
			
			$t = $this->response->html->template($this->CLASSDIR.'plugins/jlu.map/templates/jlu.map.html');
			$vars = array(
				'help' => $help,
				'script' => $script,
				'tileserverurl' => $this->tileserverurl,
				'googleurl' => $this->googleurl,
				'title' => $this->title,
				'cssurl' => $this->cssurl,
				'jsurl' => $this->jsurl,
				'imgurl' => $this->imgurl,
				'label_google_route' => $this->lang['label_google_route'],
				'title_google_route' => $this->lang['title_google_route'],
				'title_thumb' => $this->lang['title_thumb'],
				'title_address' => $this->lang['title_address'],
				'title_floors' => $this->lang['title_floors'],
				'title_zoom_in' => $this->lang['title_zoom_in'],
				'title_zoom_out' => $this->lang['title_zoom_out'],
				'title_center_map' => $this->lang['title_center_map'],
			);
			$t->add($vars);
			return $t;

		} else {
			// handle disclaimer
			$t = $this->response->html->template($this->CLASSDIR.'plugins/jlu.map/templates/jlu.map.disclaimer.html');
			$vars = array(
				'title' => $this->title,
				'cssurl' => $this->cssurl,
				'jsurl' => $this->jsurl,
				'imgurl' => $this->imgurl,
			);
			$t->add($this->lang['disclaimer']);
			$t->add($vars);
			return $t;
		}
	}

	//--------------------------------------------
	/**
	 * Distance
	 *
	 * @access public
	 */
	//--------------------------------------------	
	function distance($lon1, $lat1, $lon2, $lat2) {
		if (($lat1 == $lat2) && ($lon1 == $lon2)) {
			return 0;
		}
		else {
			$theta = $lon1 - $lon2;
			$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
			$dist = acos($dist);
			$dist = rad2deg($dist);
			$miles = $dist * 60 * 1.1515;
			//return ($miles * 1.609344);
			return $dist * 360;
		}
	}

}
?>
