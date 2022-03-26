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
var $lang = array();

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
		$debug   = $this->response->html->request()->get('debug', true);

		$msg = '';

		$top    = array("lon"=>0,"lat"=>0);
		$bottom = array("lon"=>0,"lat"=>0);

		$X = 0.0;
		$Y = 0.0;
		$Z = 0.0;

		$script = 'markers=[';
		if(is_array($markers)) {
			foreach($markers as $marker) {
				if(isset($marker['lon']) && isset($marker['lat'])) {
				
					// handle XSS
					$marker['lon'] = floatval($marker['lon']);
					$marker['lat'] = floatval($marker['lat']);
				
					$lat = $marker['lat'] * pi() / 180;
					$lon = $marker['lon'] * pi() / 180;
					$a = cos($lat) * cos($lon);
					$b = cos($lat) * sin($lon);
					$c = sin($lat);
					$X += $a;
					$Y += $b;
					$Z += $c;
					$script .= '["'.$marker['lon'].'","'.$marker['lat'].'"';
					
					if($marker['lat'] < $bottom['lat'] || $bottom['lat'] === 0) {
						$bottom['lon'] = $marker['lon'];
						$bottom['lat'] = $marker['lat'];
						$bottom[2] = $marker['title'];
					}
					
					if($marker['lat'] > $top['lat'] || $top['lat'] === 0) {
						$top['lon'] = $marker['lon'];
						$top['lat'] = $marker['lat'];
						$top[2] = $marker['title'];
					}
					
					(isset($marker['link'])) ? $script .= ',"'.$marker['link'].'"' : $script .= ',""';
					(isset($marker['title'])) ? $script .= ',"'.htmlentities($marker['title']).'"': $script .= ',""';
					$script .= '],';
					
				} else {
					continue;
				}
			}
		} else {
			$msg = '<div class="alert alert-info"><b>Usage</b>: ?m[0][lon]=8.67722&m[0][lat]=50.58038&m[0][title]=Hauptgeb&auml;ude&m[0][link]=http://google.com</div>';
		}
		$script .= '];';
		
		if(is_array($markers)) {
			$num = count($markers);
			$X /= $num;
			$Y /= $num;
			$Z /= $num;
			$lon = atan2($Y, $X);
			$hyp = sqrt($X * $X + $Y * $Y);
			$lat = atan2($Z, $hyp);
			$lon = $lon * 180 / pi();
			$lat = $lat * 180 / pi();
			$script .= 'center=['.$lon.','.$lat.'];';
			
			$zoom = 15;
			$distance = $this->distance($top['lon'],$top['lat'],$bottom['lon'],$bottom['lat'],'K');
			if($distance > 1000) {
				$zoom = 2;
			}
			if($distance < 1000) {
				$zoom = 6;
			}
			if($distance < 100) {
				$zoom = 9;
			}
			if($distance < 5) {
				$zoom = 14;
			}
			if($distance < 2.5) {
				$zoom = 15;
			}
			if($distance < 0.75) {
				$zoom = 16;
			}
			if($distance < 0.5) {
				$zoom = 17;
			}
			if($distance < 0.25) {
				$zoom = 18;
			}
			$script .= 'zoom='.$zoom.';';

		} else {
			$script .= 'center=[8.67722, 50.58038];';
			$script .= 'zoom=15;';
		}
		if(isset($debug)) {
			$script .= 'debug=true;';
		}
		
		$t = $this->response->html->template($this->CLASSDIR.'plugins/jlu.map/templates/jlu.map.html');
		$vars = array(
			'message' => $msg,
			'script' => $script,
			'tileserverurl' => $this->tileserverurl,
			'title' => $this->title,
			'cssurl' => $this->cssurl,
			'jsurl' => $this->jsurl,
			'imgurl' => $this->imgurl,
		);
		$t->add($vars);
		return $t;
	}
	
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
			return ($miles * 1.609344);
		}
	}

}
?>
