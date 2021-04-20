<?php
/**
 * standort
 *
 * @package jlu_standort
 * @license see ../class/plugins/jlu.standort/LICENSE.TXT
 * @version 1.0
 * @copyright Copyright (c) 2020,
 * Justus-Liebig-Universitaet Giessen
 * Dezernat E - Liegenschaften, Bau und Technik
 * Abteilung E1 - Flaechenmanagement
 * E1.3 - Projektleitung CAFM-System.
 */

require_once('bootstrap.php');
$PROFILESDIR = realpath(PROFILESDIR).'/';
$CLASSDIR = realpath(CLASSDIR).'/';

require_once($CLASSDIR.'lib/htmlobjects/htmlobject.class.php');
require_once($CLASSDIR.'lib/db/query.class.php');
require_once($CLASSDIR.'lib/file/file.handler.class.php');
require_once($CLASSDIR.'plugins/jlu.standort/class/jlu.standort.user.class.php');

// init html object
$html = new htmlobject($CLASSDIR.'lib/htmlobjects/');

// init file object
$file = new file_handler();

// init db object
$query = new query($CLASSDIR.'lib/db');
$query->db = $PROFILESDIR;
$query->type = 'file';
$db = $query;

// init user object
$user = new jlu_standort_user($file);

require_once($CLASSDIR.'plugins/jlu.standort/class/jlu.standort.standalone.class.php');
$controller = new jlu_standort_standalone($file, $html->response(), $query, $user);
$controller->language = 'de';
$controller->treeurl = 'cache/tree.js';
$controller->cssurl = 'css/';
$controller->jssurl = 'js/';
$controller->imgurl = 'img/';
$controller->imprinturl = 'https://www.uni-giessen.de/ueber-uns/impressum';
$controller->privacynoticeurl = 'https://www.uni-giessen.de/ueber-uns/datenschutz';
$controller->contacturl = 'mailto:flaechenmanagement@admin.uni-giessen.de';
$controller->copyright = '&copy;JLU Gie&szlig;en '.date('Y', time());

echo $controller->action()->get_string();
?>