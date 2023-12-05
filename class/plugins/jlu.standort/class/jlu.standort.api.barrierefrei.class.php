<?php
/**
 * jlu_standort_api_barrierefrei
 *
 * @package jlu_standort
 * @license ../LICENSE.TXT
 * @version 1.0
 * @copyright Copyright (c) 2023,
 * Justus-Liebig-Universitaet Giessen
 * Dezernat E - Liegenschaften, Bau und Technik
 * Abteilung E1 - Flaechenmanagement
 * E1.3 - Projektleitung CAFM-System.
 */
 
 
 class jlu_standort_api_barrierefrei
{
/**
* translation
* @access public
* @var array
*/
var $lang = array(
	'tag-eingang' => 'Accessibility',
	'BE-Bemerkungen1' => 'BE-Bemerkungen1',
	'BE-Bemerkungen2' => 'BE-Bemerkungen2',
);

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param object $controller
	 */
	//--------------------------------------------
	function __construct($file, $user) {
		$this->file = $file;
		$this->user = $user;
	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 */
	//--------------------------------------------	
	function entrance($id) {

		$form = '';
		$icon = 'marker-fallback.png';
		if($this->file->exists(getcwd().'/img/marker-barrierefrei-eingang.png')) {
			$icon = 'marker-barrierefrei-eingang.png';
		}
		if($this->file->exists($this->profilesdir.'/jlu.standort/barrierefrei.eingang.csv')) {
			$file = $this->profilesdir.'jlu.standort/barrierefrei.eingang.csv';
		}
		elseif($this->file->exists($this->profilesdir.'/jlu.standort/barrierefrei.eingang.test.csv')) {
			$file = $this->profilesdir.'jlu.standort/barrierefrei.eingang.test.csv';
		}

		if(($handle = fopen($file, "r")) !== FALSE) {
			$i = 0;
			while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
				if($i === 0) {
					// handle bom
					if(isset($data[0])) {
						$data[0] = $this->file->remove_utf8_bom($data[0]);
					}
					$head = array_flip($data);
				} else {
					if($id === $data[$head['BE-Obj-ID-Campusb']]) {

						// geo
						$lon = str_replace(',','.', $data[$head['coord_y']]);
						$lat = str_replace(',','.', $data[$head['coord_x']]);

						// text
						$txt = '';
						if(isset($data[$head['BE-Bemerkungen1']]) && $data[$head['BE-Bemerkungen1']] !== '') {
							$txt  = '<b>'.$this->lang['BE-Bemerkungen1'].'</b>: '.$data[$head['BE-Bemerkungen1']].'<br>';
						}
						if(isset($data[$head['BE-Bemerkungen2']]) && $data[$head['BE-Bemerkungen2']] !== '') {
							$txt .= '<b>'.$this->lang['BE-Bemerkungen2'].'</b>: '.$data[$head['BE-Bemerkungen2']].'<br>';
						}

						$form .= '<input type="hidden" name="lang" value="'.$this->user->lang.'">';
						$form .= '<input type="hidden" name="m[be'.$i.'][lon]" value="'.$lon.'">';
						$form .= '<input type="hidden" name="m[be'.$i.'][lat]" value="'.$lat.'">';
						$form .= '<input type="hidden" name="m[be'.$i.'][title]" value="'.$data[$head['BE-Bezeichnung']].'">';
						$form .= '<input type="hidden" name="m[be'.$i.'][text]" value="'.$txt.'">';
						$form .= '<input type="hidden" name="m[be'.$i.'][icon]" value="'.$icon.'">';
						$form .= '<input type="hidden" name="m[be'.$i.'][tag]" value="'.$this->lang['tag-eingang'].'">';

						// thumb
						if(
							isset($head['BE-Obj-ID']) && 
							isset($data[$head['BE-Obj-ID']]) && 
							$data[$head['BE-Obj-ID']] !== ''
						) {
							$ident = $data[$head['BE-Obj-ID']];

							$thumb  = $this->profilesdir.'jlu.standort/thumbs/'.$ident;
							if($this->file->exists($thumb.'.jpg')) {
								$form .= '<input type="hidden" name="m[be'.$i.'][thumb]" value="jlu.standort.api.php?'.$this->actions_name.'=thumb&file='.$ident.'.jpg">';
							}
							elseif($this->file->exists($thumb.'.test.jpg')) {
								$form .= '<input type="hidden" name="m[be'.$i.'][thumb]" value="jlu.standort.api.php?'.$this->actions_name.'=thumb&file='.$ident.'.test.jpg">';
							}

							$bild  = $this->profilesdir.'jlu.standort/bilder/'.$ident;
							if($this->file->exists($bild.'.jpg')) {
								$form .= '<input type="hidden" name="m[be'.$i.'][id]" value="'.$ident.'">';
							}
						}
					}
				}
				$i++;
			}
		}

		return $form;
	}
	
}
?>
