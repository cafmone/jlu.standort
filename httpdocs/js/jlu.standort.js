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

		if(typeof id != 'undefined') {
			// handle content
			this.__canvas.innerHTML = '&#160;';
			this.__canvas.style.display = 'block';

			$('#Breadcrumps').css('display','none');
			// wait
			this.__canvas.innerHTML = '<div id="ContentWait" class="clearfix">'+$('#Wait .modal-body').html()+'</div>';
			this.setCrumps(id);
			// call api
			this.ajax(id, lang);
			this.__id = id;
		} else {
			//this.__canvas.innerHTML = '<div style="text-align:center;"><img src="jlu.standort.api.php?action=image&file=index.jpg"></div>';
			$('.copylink').hide();
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
				select  = '<div class="menu-wrapper" id="'+view+'-select">';
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

				select  = '<div class="menu-wrapper" id="'+view+'-select">';
				select += ' <div class="input-group active" title="'+identifiers[view]+'">';
				select += '  <input class="form-control" disabled="disabled" placeholder="'+identifiers[view]+' ..." ';
				if (typeof this.__crumps[view] != 'undefined') {
					label = this.__crumps[view].label;
					// handle raum
					if( view === 'raum' ) {
						label = identifiers[view]+' '+label;
					}
					// handle liegenschaft
					else if( view === 'liegenschaft' ) {
						tmp = label.split(',');
						label = tmp[0];
					}
					select += 'value="'+label+'"';
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
				order = false;
				container = [];
				for (tid in tree) {
					if(view == tree[tid]['v']) {
						if(pid != '' && pid != tree[tid]['p']) { continue; }
						label = tree[tid]['l'];
						// handle raum
						view === 'raum' ? label = identifiers[view]+' '+label : null;
						out = label+'[[*]]'+tid+'[[*]]'+pid;
						if (typeof tree[tid]['o'] !== 'undefined') {
							out = label+'[[*]]'+tid+'[[*]]'+pid+'[[*]]'+tree[tid]['o'];
							order = true;
						}
						//console.log(out);
						container.push(out);
					}
				}

				// handle container not empty
				if(container.length > 0) {

					// sort voodoo part 2
					if(order === false) {
						container.sort( sortAlphaNum );
					}
					else if(order === true) {
						container.sort( sortPos );
					}

					for(x in container) {
						tmp   = container[x].split('[[*]]');
						tid   = tmp[1];
						label = tmp[0];
						pid   = tmp[2];

						// remove zip from liegenschaft
						if(view === 'liegenschaft') {
							tmp = label.split(',');
							label = tmp[0];
						}

						if(typeof this.__crumps[view] != 'undefined' && this.__crumps[view]['id'] == tid ) {
							if(i != 1) {
								str  = '<a';
								str += ' class="list-group-item list-group-item-action active"';
								str += ' href="?id='+pid+'&lang='+lang+'"';
								//str += ' onclick="treebuilder.cookie('+pid+'); treebuilder.wait();"';
								str += ' onclick="treebuilder.wait();"';
								str += '>';
								str += label+'<span class="close">&times;</span>';
								str += '</a>';
							} else {
								str  = '<a';
								str += ' class="list-group-item list-group-item-action active"';
								str += ' href="?id='+tid+'&lang='+lang+'"';
								//str += ' onclick="treebuilder.cookie('+tid+'); treebuilder.wait();"';
								str += ' onclick="treebuilder.wait();"';
								str += '>';
								str += label;
								str += '</a>';
							}
						} else {
							str  = '<a';
							str += ' class="list-group-item list-group-item-action"';
							str += ' href="?id='+tid+'&lang='+lang+'"';
							//str += ' onclick="treebuilder.cookie('+tid+'); treebuilder.wait();"';
							str += ' onclick="treebuilder.wait();"';
							str += '>';
							str += label;
							str += '</a>';
						}
						group.append(str);
					}
					// build menu
					menubox.append(select);
					menubox.append(group);

				} else {
					select  = '<div id="'+view+'-select">';
					select += ' <div class="input-group">';
					select += '  <input class="form-control" value="" disabled="disabeled" placeholder="'+identifiers[view]+' ...">';
					select += '  <div class="input-group-append">';
					select += '   <button type="button" tabindex="-1" class="btn btn-default dropdown-toggle disabled"></button>';
					select += '  </div>';
					select += ' </div>';
					select += '</div>';
					menubox.append(select);
				}
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
					params = 'id='+id+'&lang='+l+action;
				} else {
					params = 'lang='+l+action;
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
				$('.selectpicker').selectpicker();

				// handle map
				map = document.getElementById('MapForm');
				if(map) {
					mapbuilder.print();
				}
				
				// handle svg
				svg = document.getElementById('SVGimg');
				if(svg) {
					svgbuilder.print();
				}
				
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

		next = $('#History .next');
		next.html('<a class="btn btn-sm btn-default disabled"></a>');

		previous = $('#History .previous');
		previous.html('<a class="btn btn-sm btn-default disabled"></a>');

		cookie = (result = new RegExp('(?:^|; )'+encodeURIComponent(this.__cookie)+'=([^;]*)').exec(document.cookie)) ? (result[1]) : '';
		if(cookie !== '') {
			tmp = cookie.split(',');
			// handle landing page
			if(this.__id != '') {
				index = tmp.indexOf(this.__id);
				if(index != -1) {
					if(typeof tmp[index+1] != 'undefined') {
						str = '<a onclick="treebuilder.wait();" class="btn btn-sm btn-default" href="?id='+tmp[index+1]+'&lang='+lang+'"></a>';
						next.html(str);
					}
					if(typeof tmp[index-1] != 'undefined') {
						str = '<a onclick="treebuilder.wait();" class="btn btn-sm btn-default" href="?id='+tmp[index-1]+'&lang='+lang+'"></a>';
						previous.html(str);
					}
				}
			} else {
				if(typeof tmp[tmp.length-1] != 'undefined') {
					str = '<a onclick="treebuilder.wait();" class="btn btn-sm btn-default" href="?id='+tmp[tmp.length-1]+'&lang='+lang+'"></a>';
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
		searchbox.attr('placeholder', translation.search);
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
				label = this.__crumps[view].label;
				// handle room
				view === 'raum' ? label = identifiers[view]+' '+label : null;

				crumps += '<a onclick="treebuilder.wait();" href="?id='+this.__crumps[view].id+'&lang='+lang+'">'+label+'</a>';
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

	//---------------------------------------------
	// Init
	//---------------------------------------------
	init : function() {
		this.__input  = document.getElementById("SearchInput");
		this.__result = document.getElementById("SearchResult");
		this.__content = document.getElementById("Content");
		this.__search = document.getElementById("Search");
		this.__header = document.getElementById("SearchHeader");
		this.__loader = document.getElementById("SearchLoader");
		this.__count = document.getElementById("SearchCount");
		this.__ids = document.getElementById("SearchActive");
		this.__header.style.display = 'block';
		this.__input.focus();
	},

	//---------------------------------------------
	// Seek
	//---------------------------------------------
	seek : function() {
		needle = this.__input.value;
		hits   = 0;
		if(needle.length > 2) {

			// GUI
			this.__ids.innerHTML = '';
			this.__result.style.display = 'block';

			// handle needle escapes
			needle = needle.replace(/\./gi, '\\.');
			needle = needle.replace(/\+/gi, '\\+');
			needle = needle.replace(/\?/gi, '\\?');
			needle = needle.replace(/\|/gi, '\\|');
			needle = needle.replace(/\^/gi, '\\^');
			needle = needle.replace(/\$/gi, '\\$');
			needle = needle.replace(/\(/gi, '\\(');
			needle = needle.replace(/\)/gi, '\\)');
			needle = needle.replace(/\{/gi, '\\{');
			needle = needle.replace(/\}/gi, '\\}');
			needle = needle.replace(/\[/gi, '\\[');
			needle = needle.replace(/\]/gi, '\\]');

			// handle wildcard
			needle = needle.replace(/\*/gi, '[^<\|]+?');
			needle = needle.replace(/\[\^<\|\]\+\?$/gi, '[^<\|]+');
			needle = needle.replace(/^\[\^<\|\]\+\?/gi, '[<\|]?[^<\|]+?');
			//console.log(needle);

			// haystack
			haystack = $('#'+this.__result.id+' span');

			regex = new RegExp(needle, "i");
			for(var i=0; i < haystack.length; i++) {

				// get string to search in
				tt = haystack[i].innerHTML;

				// remove highlite
				tt = tt.replace(/<strong>(.*)<\/strong>/i, '$1');

				// remove span from text to search
				text = tt.replace(/(<span>[^<>]*<\/span>)/i, "");
				result = regex.test(text);
				if(result !== false) {
					parent = haystack[i].parentElement.id;

					// add highlite to haystack string
					ex = new RegExp('('+needle+')', "i");
					haystack[i].innerHTML = tt.replace(ex, '<strong>$1</strong>');
					haystack[i].parentElement.style.display = 'block';

					halt = false;
					// handle hit highlite in building
					adress = document.getElementById(parent+'-adress');
						if(adress !== null) {
						tmp = adress.innerHTML;
						tmp = tmp.replace(/<strong>|<\/strong>/gi,'');
						c = tmp.length;
						tmp = tmp.replace(ex, '<strong>$1</strong>');
						if(c < tmp.length) {
							halt = true;
						}
						adress.innerHTML = tmp;
						//console.log(adress.innerHTML);
					}

					// handle hit highlite in rooms
					rooms = document.getElementById(parent+'-rooms');
					if(rooms !== null) {
						tmp = rooms.innerHTML;
						tmp = tmp.replace(/<strong>|<\/strong>/gi,'');
						ex  = new RegExp('('+needle+')', "gi");
						if(halt === false) {
							c = tmp.length;
							tmp = tmp.replace(ex, '<strong>$1</strong>');
							if(c < tmp.length) {
								halt = true;
							}
						}
						rooms.innerHTML = tmp;
						//console.log(rooms.innerHTML);
					}

					// handle hit highlite in tags
					tags = document.getElementById(parent+'-tags');
					if(tags !== null) {
						tmp = tags.innerHTML;
						tmp = tmp.replace(/<strong>|<\/strong>/gi,'');
						ex  = new RegExp('('+needle+')', "gi");
						if(halt === false) {
							c = tmp.length;
							tmp = tmp.replace(ex, '<strong>$1</strong>');
							if(c < tmp.length) {
								halt = true;
							}
						}
						tags.innerHTML = tmp;
						//console.log(tags.innerHTML);
					}

					// store id for later use
					if(this.__ids.innerHTML === '') {
						this.__ids.innerHTML = parent;
					} else {
						this.__ids.innerHTML = this.__ids.innerHTML+','+ parent;
					}

					// count hits
					hits++;
				} else {
					haystack[i].parentElement.style.display = 'none';
				}
			}
			this.__count.innerHTML = hits;
		} else {
			this.__count.innerHTML = 0;
			this.__ids.innerHTML = '';
			this.__result.style.display = 'none';
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
	// Print
	//---------------------------------------------
	print : function() {
		this.select = document.getElementById('UsageSelect');
		id = this.select.options[this.select.selectedIndex].value;
		label = this.select.options[this.select.selectedIndex].innerHTML;
		if(id !== '') {
			$('.selectpicker').selectpicker('val', '');
			clone = document.getElementById(id).cloneNode(true);
			clone.style.display = 'block';
			$('#UsageModal').modal({
				backdrop: true,
				keyboard: true,
				focus: true,
			})
			$('#UsageCanvas').html(clone);
			$('#UsageModal .modal-title').html(label);
			$('.close').trigger('focus');
		}
	},
	
	//---------------------------------------------
	// Close
	//---------------------------------------------
	close : function() {
		$('.selectpicker').selectpicker('val', '');
		$('#UsageModal').modal('hide');
	}
}

/* MAPBUILDER */
var mapbuilder = {

	//---------------------------------------------
	// Print
	//---------------------------------------------
	print : function () {
		var form_data = $('#MapForm').serializeArray();
		$('#MapFrame').load('jlu.map.php', form_data);
	},
	
	//---------------------------------------------
	// Close
	//---------------------------------------------
	load : function (id) {
		children = [];
		if(typeof tree[id] != 'undefined') {
			for( i in tree) {
				if(tree[i]['p'] == id) {
					out   = tree[i]['l']+'[[*]]'+i+'[[*]]'+tree[i]['p'];
					if (typeof tree[i]['o'] !== 'undefined') {
						out   = tree[i]['l']+'[[*]]'+i+'[[*]]'+tree[i]['p']+'[[*]]'+tree[i]['o'];
						order = true;
					}
					children.push(out);
				}
			}
		}
		str = '';
		if(children.length > 0) {
			str += '<strong>'+maptranslate['label_data']+'</strong>';
			// sort voodoo
			if(order === false) {
				children.sort( sortAlphaNum );
			}
			else if(order === true) {
				children.sort( sortPos );
			}
			for(i in children) {
				tmp = children[i].split('[[*]]');
				str += '<a href="?id='+tmp[1]+'&lang='+lang+'">'+tmp[0]+'</a>';
			}
			$('.popover-data').html(str);
		}
		$('.popover-thumb img').on('click', function() { mapbuilder.image(id); } );
		$('.popover-thumb img').attr('title',  maptranslate['title_thumb']);
	},
	
	//---------------------------------------------
	// Image
	//---------------------------------------------
	image : function(id) {
		clone = document.createElement('img'); 
		clone.src = 'jlu.standort.api.php?action=image&file='+id+'.jpg';
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

/* SVGBUILDER */
var svgbuilder = {

	layerAttrib : 'vd_layer',
	polyLayer   : 'Poly-Raum',
	infoLayer   : 'IMS_ATTRIBUTE',
	idLayer     : 'RaumObjID',
	labelLayer  : 'RaumNR',

	//---------------------------------------------
	// Print
	//---------------------------------------------
	print : function () {
	
		layers = [];
		infos  = [];

		objs = document.getElementById('SVGimg').getElementsByTagName('title');
		for(i in objs) {
			parent = objs[i].parentNode;
			if(typeof parent !== 'undefined') {
				layer = parent.getAttribute(this.layerAttrib);
				if(layer === this.polyLayer) {
					// count ploylines
					lines = parent.getElementsByTagName('polyline');
					if(lines.length > 1) {
						count = 0;
						first = 0;
						for(l in lines) {
							if(typeof lines[l].points !== 'undefined') {
								lines[l].setAttribute('stroke', 'none');
								lines[l].setAttribute('fill', 'none');
								if(count === 0) {
									first = l;
									count = 1;
								} else {
									for(c=0;c<lines[l].points.length;c++) {
										lines[first].points.appendItem(lines[l].points[c]);
									}
								}
							}
						}
					} else {
						lines[0].setAttribute('stroke', 'none');
					}
					layers[objs[i].innerHTML] = parent;
				}
				else if(layer === this.infoLayer) {
					infos[objs[i].innerHTML] = {};
					nodes = parent.getElementsByTagName('g');
					for(x in nodes) {
						if(
							typeof nodes[x] === 'object' && 
							nodes[x].getAttribute(this.layerAttrib) === this.idLayer
						) {
							infos[objs[i].innerHTML].id = nodes[x].getElementsByTagName('text')[0].innerHTML;
						}
						else if(
							typeof nodes[x] === 'object' && 
							nodes[x].getAttribute(this.layerAttrib) === this.labelLayer
						) {
							transform = nodes[x].getElementsByTagName('text')[0].getAttribute('transform');
							div = document.createElement('div');
							div.innerHTML = nodes[x].getElementsByTagName('text')[0].innerHTML;
							box = document.createElementNS("http://www.w3.org/2000/svg","foreignObject");
							box.setAttribute('transform', transform);
							box.setAttribute('class', 'label');
							box.setAttribute('width', '1');
							box.setAttribute('height', '1');
							box.appendChild(div);
							infos[objs[i].innerHTML].box = box;
						}
						parent.style.display = 'none'; 
					}
				}
			}
		}

		for(i in infos) {
			//check tree;
			if(typeof tree[infos[i].id] !== 'undefined') {
				// check layer
				if(typeof layers[i] !== 'undefined') {
					parent = layers[i];
					parent.firstElementChild.setAttribute('fill', 'transparent');
					if(infos[i].id === id) {
						parent.firstElementChild.setAttribute('class', 'room active');
					} else {
						parent.firstElementChild.setAttribute('class', 'room');
					}
					parent.firstElementChild.setAttribute('id', infos[i].id);
					parent.getElementsByTagName('title')[0].innerHTML = identifiers['raum']+' '+tree[infos[i].id].l;
					parent.firstElementChild.setAttribute('style', 'cursor:pointer;');

					// make layer clickable
					(function(id, lang) { parent.firstElementChild.onclick = function() {
							location.href = '?id='+id+'&lang='+lang;
						}
					})(infos[i].id, lang);

					str = tree[infos[i].id].l.replace('(','');
					str = str.replace(')','');
					
					// handle label box
					box = infos[i].box;
					box.getElementsByTagName('div')[0].innerHTML = str;

					
					(function(id, lang) { box.onclick = function() {
							location.href = '?id='+id+'&lang='+lang;
						}
					})(infos[i].id, lang);
					(function(id) { box.onmouseover = function() {
							document.getElementById(id).classList.add('hover');
						}
					})(infos[i].id);
					(function(id) { box.onmouseout = function() {
							document.getElementById(id).classList.remove('hover');
						}
					})(infos[i].id);
					parent.appendChild(box);
				}
			}
		}

		plus = document.createElement('button');
		plus.setAttribute('class', 'btn btn-sm btn-default plus');
		plus.setAttribute('id', 'SVGimgPlus');
		plus.innerHTML = '+';
		plus.addEventListener("click", function(event) {
			elem = document.getElementById('SVGimg').getElementsByTagName('svg')[0];
			width  = parseInt(elem.getAttribute('width'));
			width = width + 100;
			elem.setAttribute('width', width );
			/* Position */
			elem = document.getElementById('SVGbox');
			left = parseInt(elem.style.left) -50 +'px';
			elem.style.left = left;
		})
		minus = document.createElement('button');
		minus.setAttribute('class', 'btn btn-sm btn-default minus');
		minus.innerHTML = '-';
		minus.addEventListener("click", function(event) {
			elem = document.getElementById('SVGimg').getElementsByTagName('svg')[0];
			width  = parseInt(elem.getAttribute('width'));
			width = width - 100;
			elem.setAttribute('width', width );
			/* Position */
			elem = document.getElementById('SVGbox');
			left = parseInt(elem.style.left) +50 +'px';
			elem.style.left = left;
		})
		fit = document.createElement('button');
		fit.setAttribute('class', 'btn btn-sm btn-default fit');
		fit.innerHTML = '1:1';
		fit.addEventListener("click", function(event) {
			svgbuilder.fit();
		})
		grab = document.createElement('button');
		grab.setAttribute('id', 'SVGgrab');
		grab.setAttribute('class', 'btn btn-sm btn-default grab');
		grab.innerHTML = '';
		grab.addEventListener("click", function(event) {
			svgbuilder.grab();
		})
		
		div = document.createElement('div');
		div.setAttribute('class', 'btn-group menu');
		div.appendChild(plus);
		div.appendChild(minus);
		div.appendChild(fit);
		div.appendChild(grab);

		document.getElementById('SVGimg').appendChild(div);
		//document.getElementById('SVGimgPlus').focus();
		this.fit();
	},

	//---------------------------------------------
	// Fit
	//---------------------------------------------
	fit : function() {
		/* Initial Size */
		svg = document.getElementById('SVGimg').getElementsByTagName('svg')[0];
		width  = parseInt(svg.getAttribute('width'));
		height = parseInt(svg.getAttribute('height'));
		SVGimg = window.getComputedStyle(document.getElementById('SVGimg'), null);
		x = parseInt(SVGimg.getPropertyValue("width"));
		y = parseInt(SVGimg.getPropertyValue("height"));
		if(Number.isNaN(height)) {
			height = parseInt(window.getComputedStyle(svg, null).getPropertyValue("height"));
		}
		if(height < y) {
			factor = height / y;
			height = y;
			width = Math.round(width / factor);
		}
		if(width > x) {
			factor = width / x;
			width = x;
			height = Math.round(height / factor);
		}
		if(height > y) {
			factor = height / y;
			height = y;
			width = Math.round(width / factor);
		}
		left = (x / 2) - (width / 2)+'px';
		svg.setAttribute('width', width);
		svg.removeAttribute('height')
		// center
		box = document.getElementById("SVGbox");
		box.setAttribute('style', 'top:0;left:'+left+';');
	},

	//---------------------------------------------
	// Grab
	//---------------------------------------------
	grab : function() {
		drag  = document.getElementById("SVGbox");
		rooms = document.getElementById('SVGimg').getElementsByTagName('svg')[0].getElementsByClassName('room');
		if (drag.onmousedown === null) {
			dragElement(drag);
			drag.style.cursor = 'grab';
			document.getElementById('SVGgrab').classList.add('active');
			// handle layer click
			for(i in rooms) {
				if(typeof rooms[i].id !== 'undefined') {
					if(rooms[i].onclick !== null) {
						rooms[i].onclick = null;
						rooms[i].setAttribute('style', '');
					}
				}
			}
		} else {
			drag.onmousedown = null;
			document.onmouseup = null;
			document.onmousemove = null;
			drag.style.cursor = '';
			document.getElementById('SVGgrab').classList.remove('active');
			// handle layer click
			for(i in rooms) {
				if(typeof rooms[i].id !== 'undefined') {
					if(rooms[i].onclick === null) {
						rooms[i].onclick = null;
						(function(id, lang) { rooms[i].onclick = function() {
								location.href = '?id='+id+'&lang='+lang;
							}
						})(rooms[i].id, lang);
						rooms[i].setAttribute('style', 'cursor:pointer;');
					}
				}
			}
		}
	}

}

/* QRCODEBUILDER */
var qrcodebuilder = {

	//---------------------------------------------
	// Print
	//---------------------------------------------
	print : function() {
		clone = document.getElementById('QRCODE').cloneNode(true);
		clone.style.display = 'block';
		$('#QrcodeModal').modal({
			backdrop: true,
			keyboard: true,
			focus: true,
		})
		$('#QrcodeCanvas').html(clone);
		$('#QrcodeCanvas a').trigger('focus');
	},
	close : function() {
		$('#QrcodeModal').modal('hide');
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

//---------------------------------------------
// Sort Alpha Numeric
//---------------------------------------------
function sortAlphaNum(a, b) {

	a = 'XX'+a.split('[[*]]')[0];
	b = 'XX'+b.split('[[*]]')[0];

	aa = a.replace(/(.*?)([0-9].*)/, '$1');
	bb = b.replace(/(.*?)([0-9].*)/, '$1');

	var reA = /[^a-zA-Z]/g;
	var reN = /[^0-9]/g;
	var aA = aa.replace(reA, "");
	var bA = bb.replace(reA, "");

	if (aA === bA) {

		var aN = a.replace(/(.*?)([0-9])/, "$2");
		var bN = b.replace(/(.*?)([0-9])/, "$2");

		aN == a ? aN = 0 : null;
		bN == b ? bN = 0 : null;

		aO = parseInt(aN);
		bO = parseInt(bN);

		// if aO == bO revert parseInt
		if(aO == bO) {
			aO = aN;
			bO = bN;
		}
		return aO === bO ? 0 : aO > bO ? 1 : -1;

	} else {
		return aA > bA ? 1 : -1;
	}
}

//---------------------------------------------
// Sort by Position
//---------------------------------------------
function sortPos(a,b) {
	a = parseInt(a.split('[[*]]')[3]);
	b = parseInt(b.split('[[*]]')[3]);
	return a > b ? 1 : -1;
}

//---------------------------------------------
// Drag Element
//---------------------------------------------
function dragElement(elmnt) {
	var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
	elmnt.onmousedown = dragMouseDown;
	function dragMouseDown(e) {
		e = e || window.event;
		e.preventDefault();
		// get the mouse cursor position at startup:
		pos3 = e.clientX;
		pos4 = e.clientY;
		document.onmouseup = closeDragElement;
		// call a function whenever the cursor moves:
		document.onmousemove = elementDrag;
		elmnt.style.cursor = 'grab';
	}
	function elementDrag(e) {
		e = e || window.event;
		e.preventDefault();
		// calculate the new cursor position:
		pos1 = pos3 - e.clientX;
		pos2 = pos4 - e.clientY;
		pos3 = e.clientX;
		pos4 = e.clientY;
		// set the element's new position:
		elmnt.setAttribute('style','top:'+ (elmnt.offsetTop - pos2) +'px; left:'+ (elmnt.offsetLeft - pos1) +'px');
		elmnt.style.cursor = 'grabbing';
	}
	function closeDragElement() {
		// stop moving when mouse button is released:
		document.onmouseup = null;
		document.onmousemove = null;
		elmnt.style.cursor = 'grab';
	}
}
