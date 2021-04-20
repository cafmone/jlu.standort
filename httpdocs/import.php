<?php
/**
 * import
 *
 * @package jlu_standort
 * @license ../class/plugins/jlu.standort/LICENSE.TXT
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

require_once($CLASSDIR.'plugins/jlu.standort/class/jlu.standort.import.controller.class.php');
$controller = new jlu_standort_import_controller($file, $html->response(), $query, $user);

$controller->action();
?>
