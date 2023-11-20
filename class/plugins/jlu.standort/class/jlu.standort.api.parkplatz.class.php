<?php
/**
 * jlu_standort_api_parkplatz
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
 
 class jlu_standort_api_parkplatz
{
/**
* translation
* @access public
* @var array
*/
var $lang = array(
	'label' => 'Parkpl&auml;tze',
	'P-Stellplaetze' => 'P-Stellplaetze',
	'P-Schranke' => 'P-Schranke',
	'P-Bemerkungen' => 'P-Bemerkungen',
	'P-Stellplaetze-behin' => 'P-Stellplaetze-behin',
	'P-Bemerkungen-behin' => 'P-Bemerkungen-behin',
	'P-Stellplaetze-Eltern-Kind' => 'P-Stellplaetze-Eltern-Kind',
	'P-Bemerkungen-Eltern-Kind' => 'P-Bemerkungen-Eltern-Kind',
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
	function action($id) {

		$form = '';
		$icon = 'marker-fallback.png';
		if($this->file->exists(getcwd().'/img/marker-parkplatz.png')) {
			$icon = 'marker-parkplatz.png';
		}
		if($this->file->exists($this->profilesdir.'/jlu.standort/parkplatz.csv')) {
			$file = $this->profilesdir.'jlu.standort/parkplatz.csv';
		}
		elseif($this->file->exists($this->profilesdir.'/jlu.standort/parkplatz.test.csv')) {
			$file = $this->profilesdir.'jlu.standort/parkplatz.test.csv';
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
					if($id === $data[$head['P-Obj-ID-Campusb']]) {
						$lon = str_replace(',','.', $data[$head['coord_y']]);
						$lat = str_replace(',','.', $data[$head['coord_x']]);

						$txt = '';
						if(isset($data[$head['P-Stellplaetze']]) && $data[$head['P-Stellplaetze']] !== '') {
							$txt  = $this->lang['P-Stellplaetze'].': '.$data[$head['P-Stellplaetze']].'<br>';
						}
						if(isset($data[$head['P-Schranke']]) && $data[$head['P-Schranke']] !== '') {
							$txt .= $this->lang['P-Schranke'].': '.$data[$head['P-Schranke']].'<br>';
						}
						if(isset($data[$head['P-Stellplaetze-behin']]) && $data[$head['P-Stellplaetze-behin']] !== '') {
							$txt .= $this->lang['P-Stellplaetze-behin'].': '.$data[$head['P-Stellplaetze-behin']].'<br>';
						}
						if(isset($data[$head['P-Bemerkungen-behin']]) && $data[$head['P-Bemerkungen-behin']] !== '') {
							$txt .= $this->lang['P-Bemerkungen-behin'].': '.htmlentities($data[$head['P-Bemerkungen-behin']]).'<br>';
						}
						if(isset($data[$head['P-Bemerkungen']]) && $data[$head['P-Bemerkungen']] !== '') {
							$txt .= $this->lang['P-Bemerkungen'].': '.htmlentities($data[$head['P-Bemerkungen']]).'<br>';
						}
						if(isset($data[$head['P-Stellplaetze-Eltern-Kind']]) && $data[$head['P-Stellplaetze-Eltern-Kind']] !== '') {
							$txt .= $this->lang['P-Stellplaetze-Eltern-Kind'].': '.$data[$head['P-Stellplaetze-Eltern-Kind']].'<br>';
						}
						if(isset($data[$head['P-Bemerkungen-Eltern-Kind']]) && $data[$head['P-Bemerkungen-Eltern-Kind']] !== '') {
							$txt .= $this->lang['P-Bemerkungen-Eltern-Kind'].': '.htmlentities($data[$head['P-Bemerkungen-Eltern-Kind']]).'<br>';
						}

						$form .= '<input type="hidden" name="lang" value="'.$this->user->lang.'">';
						$form .= '<input type="hidden" name="m[p'.$i.'][lon]" value="'.$lon.'">';
						$form .= '<input type="hidden" name="m[p'.$i.'][lat]" value="'.$lat.'">';
						$form .= '<input type="hidden" name="m[p'.$i.'][title]" value="'.$data[$head['P-Bezeichnung']].'">';
						$form .= '<input type="hidden" name="m[p'.$i.'][text]" value="'.$txt.'">';
						$form .= '<input type="hidden" name="m[p'.$i.'][icon]" value="'.$icon.'">';
						$form .= '<input type="hidden" name="m[p'.$i.'][tag]" value="'.$this->lang['label'].'">';

						if(isset($data[$head['P-Adresse']]) && $data[$head['P-Adresse']] !== '') {
							$form .= '<input type="hidden" name="m[p'.$i.'][addr]" value="'.htmlentities($data[$head['P-Adresse']]).'">';
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