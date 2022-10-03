<?php
/**
 * jlu_standort_index
 *
 * @package jlu_standort
 * @license ../LICENSE.TXT
 * @version 1.0
 * @copyright Copyright (c) 2022,
 * Justus-Liebig-Universitaet Giessen
 * Dezernat E - Liegenschaften, Bau und Technik
 * Abteilung E1 - Flaechenmanagement
 * E1.3 - Projektleitung CAFM-System.
 */

class jlu_standort_index
{
/**
* name of action buttons
* @access public
* @var string
*/
var $actions_name = 'standort_action';
/**
* message param
* @access public
* @var string
*/
var $message_param = 'standort_msg';
/**
* identifier
* @access public
* @var string
*/
var $identifier_name = 'standort_ident';

/* -------------- Urls -------------- */
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

/* -------------- Links -------------- */
/**
* link to contact
* @access public
* @var array
*/
var $contacturl = null;
/**
* link to imprint
* @access public
* @var array
*/
var $imprinturl = null;
/**
* link to privacy notice
* @access public
* @var array
*/
var $privacynoticeurl = null;
/**
* copyright notice
* @access public
* @var array
*/
var $helppage = null;
/**
* copyright notice
* @access public
* @var array
*/
var $copyright = null;


/**
* default if none given by request
* @access public
* @var string
*/
var $defaultid;

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
	'label' => 'Label',
	'title' => 'Title',
	'logo_alt' => 'Logo',
	'imprint' => 'Imprint',
	'imprint_title' => 'Imprint',
	'toggle_left' => 'Toggle Left Panel',
	'toggle_right' => 'Toggle Right Panel',
	'close' => 'close',
	'contact' => 'Contact',
	'contact_title' => 'Contact',
	'loading' => 'Loading ...',
	'privacynotice' => 'Privacy',
	'privacynotice_title' => 'Privacy',
	'helppage' => 'Help',
	'helppage_title' => 'Help',
	'print' => 'Print',
	'print_title' => 'Print Page',
	'link' => 'Copy Link',
	'link_title' => 'Copy url to clipboard',
	'previous' => 'Previous ID in history',
	'next' => 'Next ID in history',
	'accessibility' => 'Accessibility',
	'lang' => array(
		'language' => 'Language',
		'language_title' => 'Select Language',
		'en' => 'English',
	), 
	'map' => array(
		'title_thumb' => 'Click to enlarge image',
		'label_data' => 'Floors',
	),
	'search' => array(
		'search' => 'Search ...',
		'search_title' => 'Search',
		'tags' => 'Tags',
		'rooms' => 'Rooms',
		'help' => 'Help',
	),
);

	//--------------------------------------------
	/**
	 * Constructor
	 *
	 * @access public
	 * @param object $controller
	 */
	//--------------------------------------------
	function __construct($controller) {
	
		$this->controller = $controller;
		$this->response   = $controller->response;
		$this->user       = $controller->user;
		$this->db         = $controller->db;
		$this->file       = $controller->file;
		
		$this->langdir    = $controller->langdir;
		$this->tpldir     = $controller->tpldir;
		
		$this->profilesdir = $controller->profilesdir;
		$this->classdir    = $controller->classdir;

	}

	//--------------------------------------------
	/**
	 * Action
	 *
	 * @access public
	 */
	//--------------------------------------------
	function action() {

		// get languages (xss)
		$languages = array();
		$files = glob($this->langdir.'*.jlu.standort.index.ini');
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
		$this->translation = $this->user->translate($this->lang, $this->langdir, 'jlu.standort.index.ini');

		// set default canvas (index)
		$canvas = '&#160;';

		// escape id (xss)
		$id = $this->response->html->request()->get('id');
		if($id !== '') {
			$id = substr(htmlspecialchars($id), 0, 30);
			// handle liegenschaft
			if(isset($this->tree[$id]) && isset($this->tree[$id]['v']) && $this->tree[$id]['v'] === 'liegenschaft') {
				$children = array();
				foreach( $this->tree as $k => $v ) {
					if(isset($v['p']) && $v['p'] === $id) {
						$children[] = $k;
					}
				}
				// redirect if liegenschaft has only 1 gebauede
				if(count($children) === 1) {
					$url = $this->response->get_url('id',$children[0]).'&lang='.$lang;
					$this->response->redirect($url);
				}
			}
		} else {
			// use default id
			if(isset($this->defaultid) && $this->defaultid !== '') {
				$id = $this->defaultid;
			}
			elseif($this->file->exists(PROFILESDIR.'jlu.standort/bilder/index.html')) {
				$canvas = $this->response->html->template(PROFILESDIR.'jlu.standort/bilder/index.html');
				$vars = array(
					'cssurl' => $this->cssurl,
					'jsurl'  => $this->jsurl,
					'imgurl' => $this->imgurl,
					'lang'   => $this->user->lang,
				);
				$canvas->add($vars);
			}
			elseif($this->file->exists(PROFILESDIR.'jlu.standort/bilder/index.jpg')) {
				$canvas = '<div style="text-align:center;"><img src="jlu.standort.api.php?action=image&file=index.jpg"></div>';
			}
		}

		$timestamp = 0;
		$timefile = dirname($this->response->html->thisdir.$this->treeurl).'/timestamp.txt';
		if($this->file->exists($timefile)) {
			$timestamp = filemtime($timefile);
		}

		$script  = '<script src="'.$this->treeurl.'?_='.$timestamp.'"></script>'."\n";
		$script .= '<script language="JavaScript" type="text/javascript">'."\n";
		$script .= 'var timestamp = '.$timestamp.';'."\n";
		$script .= 'var identifiers = '.json_encode($this->translation['identifiers']).';'."\n";
		$script .= 'var lang = "'.$this->user->lang.'";'."\n";
		$script .= 'var languages = '.json_encode($this->translation['lang']).';'."\n";
		$script .= 'var id = "'.$id.'";'."\n";
		$script .= 'var maptranslate = '.json_encode($this->translation['map']).';'."\n";
		$script .= 'var action = "&'.$this->actions_name.'='.$this->controller->action.'";'."\n";
		$script .= 'var translation = {"search":"'.$this->translation['search']['search'].'"};'."\n";
		$script .= '</script>';

		$copyright = '';
		if(isset($this->copyright) && $this->copyright !== '') {
			$copyright = $this->copyright;
		}
		$contact = '';
		if(isset($this->contacturl) && $this->contacturl !== '') {
			$contact = '<a href="'.$this->contacturl.'" title="'.$this->translation['contact_title'].'">'.$this->translation['contact'].'</a>';
		}
		$privacynotice = '';
		if(isset($this->privacynoticeurl) && $this->privacynoticeurl !== '') {
			$privacynotice = '<a onclick="treebuilder.wait();" href="'.$this->privacynoticeurl.'" title="'.$this->translation['privacynotice_title'].'">'.$this->translation['privacynotice'].'</a>';
		}
		$helppage = '';
		if(isset($this->helppageurl) && $this->helppageurl !== '') {
			$helppage = '<a onclick="treebuilder.wait();" href="'.$this->helppageurl.'" title="'.$this->translation['helppage_title'].'">'.$this->translation['helppage'].'</a>';
		}
		$imprint = '';
		if(isset($this->imprinturl) && $this->imprinturl !== '') {
			$imprint = '<a onclick="treebuilder.wait();" href="'.$this->imprinturl.'" title="'.$this->translation['imprint_title'].'">'.$this->translation['imprint'].'</a>';
		}
		$print = '<a href="javascript:print()" title="'.$this->translation['print_title'].'">'.$this->translation['print'].'</a>';
		$link  = '<a href="javascript:treebuilder.link()" title="'.$this->translation['link_title'].'">'.$this->translation['link'].'</a>';


		$search = $this->response->html->a();
		$search->css = 'btn btn-default';
		$search->title = $this->translation['search']['search_title'];
		$search->href = '?'.$this->actions_name.'=search&lang='.$this->user->lang;
		$search->id = 'SearchToggler';
		$search->label = '<span class="search-icon"></span>';


		$t = $this->response->html->template($this->tpldir.'jlu.standort.index.html');
		$vars = array(
			'script' => $script,
			'thisfile' => $this->response->html->thisfile,
			'title' => $this->translation['title'],
			'logo_alt' => $this->translation['logo_alt'],
			'cssurl' => $this->cssurl,
			'jsurl' => $this->jsurl,
			'imgurl' => $this->imgurl,
			'label' => $this->translation['label'],
			'search' => $this->translation['search']['search'],
			'search_button' => $search,
			'toggle_left' => $this->translation['toggle_left'],
			'toggle_right' =>  $this->translation['toggle_right'],
			'close' =>  $this->translation['close'],
			'loading' => $this->translation['loading'],
			'previous' => $this->translation['previous'],
			'next' => $this->translation['next'],
			'accessibility' => $this->translation['accessibility'],
			'print' => $print,
			'copyright' => $copyright,
			'contact' => $contact,
			'imprint' => $imprint,
			'link' => $link,
			'privacynotice' => $privacynotice,
			'helppage' => $helppage,
			'lang' => $this->user->lang,
			'timestamp' => $timestamp,
			'canvas' => $canvas,
		);
		$t->add($vars);

		//$t->add($this->file->get_contents($this->tpldir.'pdf.viewer.html'), 'pdfviewer');

		return $t;
	}

	//--------------------------------------------
	/**
	 * Form
	 *
	 * @access public
	 * @param string $id
	 * @return htmlobject_form
	 */
	//--------------------------------------------
	function get_form($id = '') {

		$form = $this->response->get_form($this->actions_name, 'dummy', false);
		$form->id = 'languageform';
		$form->method = 'GET';
		$form->enctype = '';

		$d['id']['object']['type']            = 'htmlobject_input';
		$d['id']['object']['attrib']['type']  = 'hidden';
		$d['id']['object']['attrib']['name']  = 'id';
		$d['id']['object']['attrib']['value'] = $id;

		$lang = $this->translation['lang'];
		$d['lang']['object']['type']              = 'htmlobject_select';
		$d['lang']['object']['attrib']['name']    = 'lang';
		$d['lang']['object']['attrib']['index']   = array(0,0);
		$d['lang']['object']['attrib']['options'] = $lang;
		$d['lang']['object']['attrib']['handler'] = 'onchange="this.form.submit();"';
		if(isset($this->user->lang)) {
			$d['lang']['object']['attrib']['selected'] = array( $this->user->lang );
		}

		$form->display_errors = false;
		$form->add($d);
		return $form;
	}

}
?>
