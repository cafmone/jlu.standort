<?php
/**
 * standort.api
 *
 * @package jlu_map
 * @license ../class/plugins/jlu.map/LICENSE.TXT
 * @version 1.0
 * @copyright Copyright (c) 2022,
 * Justus-Liebig-Universitaet Giessen
 * Dezernat E - Liegenschaften, Bau und Technik
 * Abteilung E1 - Flaechenmanagement
 */

require_once('bootstrap.php');
$PROFILESDIR = realpath(PROFILESDIR).'/';
$CLASSDIR = realpath(CLASSDIR).'/';

require_once($CLASSDIR.'lib/htmlobjects/htmlobject.class.php');
require_once($CLASSDIR.'lib/db/query.class.php');
require_once($CLASSDIR.'lib/file/file.handler.class.php');
require_once($CLASSDIR.'lib/user/user.class.php');

// init html object
$html = new htmlobject($CLASSDIR.'lib/htmlobjects/');

include_once($CLASSDIR.'inc/requestfilter.inc.php');
$html->request()->filter = $requestfilter;

// init file object
$file = new file_handler();

// init db object
$query = new query($CLASSDIR.'lib/db');
$query->db = $PROFILESDIR;
$query->type = 'file';
$db = $query;

// init user object
$user = new user($file);

require_once($CLASSDIR.'plugins/jlu.map/class/jlu.map.class.php');
$controller = new jlu_map($file, $html->response(), $query, $user);
$controller->tileserverurl = 'https://{a-c}.tile.openstreetmap.de/{z}/{x}/{y}.png';
#$controller->tileserverurl = 'http://134.176.7.40:443/{a-c}/{z}/{x}/{y}.png';
#$controller->tileserverurl = 'http://127.0.0.1/{a-c}/{z}/{x}/{y}.png';
#$controller->tileserverurl = 'https://www.uni-giessen.de/JLUgeschossplaene/{a-c}/{z}/{x}/{y}.png';
$controller->cssurl = 'css/';
$controller->jssurl = 'js/';
$controller->imgurl = 'img/';
$controller->title = 'JLU OpenStreetMap&copy;';
$controller->disclaimer = false;

echo $controller->action()->get_string();
?>
