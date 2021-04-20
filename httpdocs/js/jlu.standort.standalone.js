/* TREEBUILDER */
var treebuilder = {

	__crumps : {},
	__tree : [],
	__id : '',
	__cookie : 'history',

	//---------------------------------------------
	// Init
	//---------------------------------------------
	init : function() {
		if(typeof timestamp == 'undefined') { console.log('Timestamp missing'); return; }
		if(typeof identifiers == 'undefined') { console.log('Identifiers missing'); return; }
		if(typeof tree == 'undefined') { console.log('Tree missing'); return; }
		if(typeof lang == 'undefined') { console.log('Lang missing'); return;  }
		if(typeof languages == 'undefined') { console.log('Languages missing'); return;  }

		this.__canvas = document.getElementById("Canvas");
		this.__search = document.getElementById("SearchInput");

		// reset crumps
		this.__crumps = {};

		// handle id
		if(typeof id != 'undefined' && id != '') {
			this.__initCookie(id);
			this.print(id);
		} else {
			this.print();
		}
	},

	//---------------------------------------------
	// Init Cookie
	//---------------------------------------------
	__initCookie : function(id) {
		// handle cookie when id typed to url
		key = this.__cookie;
		cookie = (result = new RegExp('(?:^|; )'+encodeURIComponent(key)+'=([^;]*)').exec(document.cookie)) ? (result[1]) : '';
		if(cookie == '' && typeof tree[id] != 'undefined') {
			document.cookie = key+'='+id+'; SameSite=Strict;';
		} 
		else if(cookie != '' && typeof tree[id] != 'undefined') {
			tmp = cookie.split(',');
			index = tmp.indexOf(id);
			if(index == -1) {
				document.cookie = key+'='+cookie+','+id+'; SameSite=Strict;';
			}
		}
	},

	//---------------------------------------------
	// Print
	//---------------------------------------------
	print : function(id) {

		// close modal
		$("#LeftModal").modal('hide');

		// close search
		//this.__result.style.display = 'none';

		// show breadcrumps
		$('#Breadcrumps').css('display','block');

		// handle content
		this.__canvas.innerHTML = '&#160;';
		this.__canvas.style.display = 'block';

		if(typeof id != 'undefined') {
			$('#Breadcrumps').css('display','none');
			// wait
			this.__canvas.innerHTML = '<div id="ContentWait" class="clearfix">'+$('#Wait .modal-body').html()+'</div>';
			this.setCrumps(id);
			// call api
			this.ajax(id, lang);
			this.__id = id;
		}

		// build environment
		var i = 1;   // TODO ?
		var keys = Object.keys(identifiers);
		if(Object.keys(this.__crumps).length == 0) {
			maximum = 3;
		} else {
			maximum = Object.keys(this.__crumps).length+1;
		}

		menubox = $(document.createElement("div"));
		menubox.addClass('list-group flex-fill');
		menubox.css('width','100%');

		for (view in identifiers) {

			if (i > maximum) {
				select  = '<div>';
				select += ' <div class="input-group">';
				select += '  <input class="form-control" value="" disabled="disabeled" placeholder="'+identifiers[view]+' ...">';
				select += '  <div class="input-group-append">';
				select += '   <button type="button" tabindex="-1" class="btn btn-default dropdown-toggle disabled"></button>';
				select += '  </div>';
				select += ' </div>';
				select += '</div>';

				menubox.append(select);

			} else {
				pid = '';
				if (typeof this.__crumps[view] != 'undefined') {
					pid = this.__crumps[view].parent;
				}
				else if (Object.keys(this.__crumps).length != 0 && i == maximum) {
					pid = id;
				}

				select  = '<div>';
				select += ' <div class="input-group active" title="'+identifiers[view]+'">';
				select += '  <input class="form-control" disabled="disabled" placeholder="'+identifiers[view]+' ..." ';
				if (typeof this.__crumps[view] != 'undefined') {
					select += 'value="'+this.__crumps[view].label+'"';
				} else {
					select += '';
				}
				select += '  >';
				select += '  <div class="input-group-append">';
				select += '   <button type="button" class="btn btn-default dropdown-toggle" onclick="treebuilder.modal(\''+view+'\');"></button>';
				select += '  </div>';
				select += ' </div>';
				select += '</div>';

				group = $(document.createElement("div"));
				group.attr('id',view);
				group.addClass('list-group');
				group.attr('tabindex','0');
				group.css('display','none');
				group.css('outline','0');
				group.bind("mouseover", function(event) {
					this.focus();
				})

				// sort voodoo part 1
				container = [];
				for (tid in tree) {
					if(view == tree[tid]['v']) {
						if(pid != '' && pid != tree[tid]['p']) { continue; }
						container.push(tree[tid]['l']+'[[*]]'+tid+'[[*]]'+pid);
					}
				}

				// sort voodoo part 2
				container.sort();
				for(x in container) {
					tmp   = container[x].split('[[*]]');
					tid   = tmp[1];
					label = tmp[0];
					pid   = tmp[2];

					if(typeof this.__crumps[view] != 'undefined' && this.__crumps[view]['id'] == tid ) {
						if(i != 1) {
							str  = '<a';
							str += ' class="list-group-item list-group-item-action active"';
							str += ' href="?id='+pid+'&lang='+lang+'"';
							str += ' onclick="treebuilder.cookie('+pid+'); treebuilder.wait();"';
							str += '>';
							str += label+'<span class="close">&times;</span>';
							str += '</a>';
						} else {
							str  = '<a';
							str += ' class="list-group-item list-group-item-action active"';
							str += ' href="?id='+tid+'&lang='+lang+'"';
							str += ' onclick="treebuilder.cookie('+tid+'); treebuilder.wait();"';
							str += '>';
							str += label;
							str += '</a>';
						}
					} else {
						str  = '<a';
						str += ' class="list-group-item list-group-item-action"';
						str += ' href="?id='+tid+'&lang='+lang+'"';
						str += ' onclick="treebuilder.cookie('+tid+'); treebuilder.wait();"';
						str += '>';
						str += label;
						str += '</a>';
					}
					group.append(str);
				}

				// build menu
				menubox.append(select);
				menubox.append(group);
			}
			i = i+1;
		}

		target = $('#navbar-left');
		target.html('');
		target.append(menubox);

		// handle lang select
		langselect = $('#Langselect');
		$('.langlabel', langselect).html(languages['language']);
		if(typeof languages['language_title'] != 'undefined') {
			$('button', langselect).attr('title', languages['language_title']);
		}
		$('.dropdown-menu', langselect).html('');

		for( l in languages) {
			if(l != 'language' && l != 'language_title') {
				if(typeof id != 'undefined') {
					params = 'id='+id+'&lang='+l;
				} else {
					params = 'lang='+l;
				}
				$('.dropdown-menu', langselect).append('<a title="'+languages[l]+'"onclick="treebuilder.wait();" class="dropdown-item" href="?'+params+'">'+languages[l]+'</a>');
			}
		}

		// handle history
		this.history();
	},

	//---------------------------------------------
	// Ajax
	//---------------------------------------------
	ajax : function (id,lang) {
		html = $.ajax({
			url: 'jlu.standort.api.php',
			global: false,
			type: 'GET',
			data: '&id='+id+'&lang='+lang+'&_='+timestamp,
			dataType: "json",
			async: true,
			cache: true,
			success: function(response){
				left = document.getElementById('LeftbarContent');
				left.innerHTML = response['leftbar'];
				content = document.getElementById('Canvas');
				content.innerHTML = response['content'];
				right = document.getElementById('RightbarContent');
				right.innerHTML = response['rightbar'];
				$('#Breadcrumps').css('display','block');
			}
		});
	},

	//---------------------------------------------
	// Wait
	//---------------------------------------------
	wait : function () {
		/*
		// close modal
		$("#LeftModal").modal('hide');
		$('#Wait').modal({
			backdrop: 'static',
			keyboard: false
		})
		*/
	},

	//---------------------------------------------
	// Link
	// Copy url to clipboard
	//---------------------------------------------
	link : function () {
		url  = window.location.protocol+'//'+window.location.hostname+window.location.pathname;
		url += '?id='+this.__id+'&lang='+lang;
		input = document.createElement("input");
		input.value = url;
		input.type = 'text';
		input.style.position = 'absolute';
		input.style.top = '-2000px';
		body = document.getElementsByTagName('body')[0];
		body.appendChild(input);
		input.select();
		input.setSelectionRange(0, 99999);
		document.execCommand("copy");
		body.removeChild(input);
	},

	//---------------------------------------------
	// Cookie
	//---------------------------------------------
	cookie : function(id) {
		key = this.__cookie;
		cookie = (result = new RegExp('(?:^|; )'+encodeURIComponent(key)+'=([^;]*)').exec(document.cookie)) ? (result[1]) : '';
		if(cookie !== '') {
			tmp = cookie.split(',');
			index = tmp.indexOf(this.__id);
			if(index != -1 && index < tmp.length) {
				cookie = '';
				// handle max entries
				offset = 0;
				if(tmp.length > 14) {
					offset = 1;
				}
				for(i=offset; i<=index; i++) {
					// skip id in tmp
					if(tmp[i] != id) {
						cookie += tmp[i]+',';
					}
				}
			} else {
				// TODO ?
				cookie += ',';
			}
			id = cookie+id;
		}
		document.cookie = key+'='+id+'; SameSite=Strict;';
	},

	//---------------------------------------------
	// History
	//---------------------------------------------
	history : function() {

		nextimg = '<img src="bootstrap-icons/arrow-bar-right.svg">';
		previmg = '<img src="bootstrap-icons/arrow-bar-left.svg">';

		next = $('#History .next');
		next.html('<a class="btn btn-sm btn-default disabled">'+nextimg+'</a>');

		previous = $('#History .previous');
		previous.html('<a class="btn btn-sm btn-default disabled">'+previmg+'</a>');

		cookie = (result = new RegExp('(?:^|; )'+encodeURIComponent(this.__cookie)+'=([^;]*)').exec(document.cookie)) ? (result[1]) : '';
		if(cookie !== '') {
			tmp = cookie.split(',');
			// handle landing page
			if(this.__id != '') {
				index = tmp.indexOf(this.__id);
				if(index != -1) {
					if(typeof tmp[index+1] != 'undefined') {
						str = '<a onclick="treebuilder.wait();" class="btn btn-sm btn-default" href="?id='+tmp[index+1]+'&lang='+lang+'">'+nextimg+'</a>';
						next.html(str);
					}
					if(typeof tmp[index-1] != 'undefined') {
						str = '<a onclick="treebuilder.wait();" class="btn btn-sm btn-default" href="?id='+tmp[index-1]+'&lang='+lang+'">'+previmg+'</a>';
						previous.html(str);
					}
				}
			} else {
				if(typeof tmp[tmp.length-1] != 'undefined') {
					str = '<a onclick="treebuilder.wait();" class="btn btn-sm btn-default" href="?id='+tmp[tmp.length-1]+'&lang='+lang+'">'+previmg+'</a>';
					previous.html(str);
				}
			}
		}
	},

	//---------------------------------------------
	// Modal
	//---------------------------------------------
	modal: function(id) {

		group = $('#'+id).clone();
		group.attr('tabindex','0');
		group.css('display','block');
		group.bind("mouseover", function(event) {
			this.focus();
		})
		marked = $('.active', group)[0];

		modal = $('#LeftModal');

		searchbox = $('.form-control',modal);
		searchbox.attr('placeholder', this.__search.placeholder);
		searchbox.val('');
		searchbox.on('keyup', function() {
			var value = $(this).val().toLowerCase();
			$('#'+id+' a').filter(function() {
				$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
			});
		});

		head = $('.modal-title', modal);
		head.html(identifiers[id]);

		body = $('.modal-body', modal);
		body.html('');
		body.append(group);

		$('#LeftModal').modal({
			backdrop: true,
			keyboard: true
		})

		// handle marked focus
		if(typeof(marked) != 'undefined') {
			marked.scrollIntoView();
		}

	},

	setCrumps: function(id) {
		this.__crumps = {};
		this.__setCrumps(id);
		i = 0;
		crumps = '';
		for( view in identifiers ) {
			if(typeof this.__crumps[view] != 'undefined') {
				if(i != 0) {
					crumps += ' / ';
				}
				crumps += '<a onclick="treebuilder.wait();" href="?id='+this.__crumps[view].id+'&lang='+lang+'">'+this.__crumps[view].label+'</a>';
			}
			i = i+1;
		}
		$('#Breadcrumps').html(crumps);
	},

	__setCrumps : function(id) {
		result = tree[id];
		if(typeof result != 'undefined') {
			this.__crumps[result.v] = { 'label':result.l, 'id':id, 'parent':result.p };
			if(typeof result.p != 'undefined') {
				this.__setCrumps(result.p);
			}
		}

	},

}

/* SEARCHBUILDER */
var searchbuilder = {
	__input : '',
	__result : '',

	init : function() {
		this.__input  = document.getElementById("SearchInput");
		this.__result = document.getElementById("SearchResult");
		this.__content = document.getElementById("Content");
		this.__search = document.getElementById("Search");
		this.__header = document.getElementById("SearchHeader");
		this.__loader = document.getElementById("SearchLoader");
		this.__footer = document.getElementById("Footer");

		this.__content.style.display = 'none';
		this.__search.style.display = 'block';
		this.__footer.style.display = 'none';

		// trigger __init
		if(this.__result.innerHTML == '') {
			this.__loader.innerHTML = '<input id="SearchTrigger" style="position:absolute;left: -1000px;">';
			this.__result.innerHTML = '<div id="ContentWait" class="clearfix">'+$('#Wait .modal-body').html()+'</div>';
			setTimeout(function(){
				document.getElementById('SearchTrigger').onfocus = function() { searchbuilder.__init(); };
				document.getElementById('SearchTrigger').focus();
			},0);
		} else {
			this.__header.style.display = 'block';
			this.__input.focus();
		}
	},

	close : function() {
		this.__search.style.display = 'none';
		this.__content.style.display = 'block';
		this.__footer.style.display = 'block';
	},

	seek : function() {
		filter = this.__input.value;
		if(filter.length > 2) {

			this.__result.style.display = 'block';
			links = this.__searchlinks;
			regex = new RegExp(filter, "gi");
			for(var i=0; i < links.length; i++) {
				text = links[i].innerText;
				result = regex.test(text);
				if(result != false) {
					links[i].style.display = 'inline-block';
				} else {
					links[i].style.display = 'none';
				}
			}
		} else {
			this.__result.style.display = 'none';
		}
	},

	__init : function() {
		str = '';
		for(idx in tree) {
			crump = this.__set(idx);
			str += '<a';
			str += ' href="?id='+idx+'&lang='+lang+'"';
			str += ' onclick="treebuilder.cookie('+idx+');treebuilder.wait();"';
			str += ' style="display:none;"';
			str += '>';
			str += idx+' : '+crump;
			str += '</a>';
		}
		this.__result.innerHTML = str;
		// reset crumps
		this.__crumps = {};
		// set searchlinks
		this.__searchlinks = $('#'+this.__result.id+' a');
		this.__header.style.display = 'block';
		this.__input.focus();
	},

	__set : function(id) {
		this.__crumps = {};
		this.__setCrumps(id, 'search');
		i = 0;
		crumps = '';
		for( view in identifiers ) {
			if(typeof this.__crumps[view] != 'undefined') {
				if(i != 0) {
					crumps += ' / ';
				}
				crumps += this.__crumps[view].label;
			}
			i = i+1;
		}
		return crumps;
	},

	__setCrumps : function(id) {
		result = tree[id];
		if(typeof result != 'undefined') {
			this.__crumps[result.v] = { 'label':result.l, 'id':id, 'parent':result.p };
			if(typeof result.p != 'undefined') {
				this.__setCrumps(result.p);
			}
		}
	},

}

/* IMAGEBOX */
var imagebox = {

	//---------------------------------------------
	// Init
	//---------------------------------------------
	init : function(image) {
		clone = image.cloneNode(true);
		$('#ImageModal').modal({
			backdrop: true,
			keyboard: true,
			focus: true,
		})
		$('#ImageModal').modal('handleUpdate')
		$('#ImageBox').html(clone);
		$('#ImageModal .modal-dialog').css('maxWidth', clone.style.maxWidth);
		clone.style.cursor = 'default';
		clone.tabIndex = '-1';
		clone.focus();
	},
}

/* USAGEBUILDER */
var usagebuilder = {

	//---------------------------------------------
	// print
	//---------------------------------------------
	print : function() {
		this.select = document.getElementById('UsageSelect');
		id = this.select.options[this.select.selectedIndex].value;
		if(id !== '') {
			clone = document.getElementById(id).cloneNode(true);
			clone.style.display = 'block';
			$('#UsageModal').modal({
				backdrop: true,
				keyboard: true,
				focus: true,
			})
			$('#UsageCanvas').html(clone);
		}
	},
	close : function() {
		$('#UsageModal').modal('hide');
		treebuilder.wait();
	}
}

/* ACCESSIBILITY */
var accessbuilder = {

	//---------------------------------------------
	// Init
	//---------------------------------------------
	init : function(id) {
		this.__canvas = document.getElementById("AccessCanvas");
		this.__canvas.innerHTML = '<div id="ContentWait" class="clearfix">'+$('#Wait .modal-body').html()+'</div>';
		this.modal(id, lang);
	},

	//---------------------------------------------
	// Modal
	//---------------------------------------------
	modal: function(id) {
		$('#AccessModal').modal({
			backdrop: true,
			keyboard: true,
			focus: true,
		})
		this.ajax(id, lang);
	},

	//---------------------------------------------
	// Ajax
	//---------------------------------------------
	ajax : function (id,lang) {
		html = $.ajax({
			url: 'jlu.standort.api.php',
			global: false,
			type: 'GET',
			data: '&action=access&id='+id+'&lang='+lang+'&_='+timestamp,
			dataType: "html",
			async: true,
			cache: true,
			success: function(response){
				content = document.getElementById('AccessCanvas');
				content.innerHTML = response;
			}
		});
	},
}

