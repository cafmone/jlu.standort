<?php
/**
 * User
 *
 * Dummy user object
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

class user
{
/**
 * lang
 *
 * @access public
 * @param string
 */
var $lang = 'en';

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param object $fileobject
	 */
	//--------------------------------------------
	function __construct( $file ) {
		$this->file  = $file;
	}

	//--------------------------------------------
	/**
	 * Translate
	 *
	 * @access public
	 * @param array $lang array to translate
	 * @param string $dir translation files directory
	 * @param string $file translation file
	 * @return array
	 */
	//--------------------------------------------
	function translate( $lang, $dir, $file ) {
		$path = $dir.'/'.$this->lang.'.'.$file;
		$tmp  = $this->file->get_ini( $path );
		if(is_array($tmp)) {
			foreach($tmp as $k => $v) {
				if(is_array($v)) {
					foreach($v as $k2 => $v2) {
						$lang[$k][$k2] = $v2;
					}
				} else {
					$lang[$k] = $v;
				}
			}
		}
		return $lang;
	}

}
?>
