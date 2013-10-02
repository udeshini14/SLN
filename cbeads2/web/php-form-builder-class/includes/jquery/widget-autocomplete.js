/* ****************************************  widget-autocomplete.js  *****************************************

	Based on the multiple value autocomplete code found at: http://jqueryui.com/demos/autocomplete/#multiple
	
	This version allows one to type text anywhere in the textbox and if matches exist they are listed. It can
	be used with textareas but the matches will be displayed underneath the textarea.
	
	As the user enters a value, the list of available items is filtered by that string to show those items 
	that match. Very usefull when dealing with a large list, especially when it is unsorted.
	
	Usage is:  $( "#id of textbox element" ).multi-autocomplete(options);
	Options: 
	word: Specify a regex string which is used to determine what parts of the text can be used for 
	      autocompleting. Default value is /\w/ which means any letter or number can be used for autocomplete.
	match_mode: How string are to be matched. By default string matching will attempt to match anywhere in
	            the word. To match the start of a word set the value to 'start'. To match the end use 'end'.
	data_source: Can be an array of values or lable=>value pairs, a callback which is passed the current term
				 (determined by the 'word' regex) or a string which is treated as the url for a remote
				 source request. Remote source requests are made by POST, with the term to match named 'term'.
	minLength: the minimum number of character required to request available options. This applies to the 
	           number of characters in the current term and not the total number of charactes overall.
	delay: the amount of time to wait from the last user input to when the available options are requested.
	
Changlog:

	2011/06/03
	- First version which works pretty well.

*************************************************************************************************************/

(function( $ ) {
	$.widget( "ui.multi_autocomplete", {
		options: {
			word: /\w/,
			match_mode: 'any',			// How to match words with a string: start (match the beginning), any (match anywhere), end (match the end)
			data_source: undefined, //[{label: 'one', value: '1'}, {label: 'two', value: '2'}, {label: 'three', value: '3'}, {label: 'one-hundred', value: '100'}]
			minLength: 0,			// the minimum number of characters that need to be supplied (for the current part of the text input) to start filtering the data source.
			delay: 0				// the amount of time to wait between the last user input and when the filtered list is requested.
		},
		_init: function() {
			this.current_group = 0;	
		},
		_create: function() {
			var self = this;
			var input = this.element;
			
			var hidden = null;
			this.datasource_type = 0;  	// 0: undefined, 1: array, 2: callback function, 3: url

			this._determine_datasource_type();
			if(this.datasource_type == 0)
			{
				alert('You must provide a datasource to use this control!');
				return;
			}

			input.bind( "keydown", function( event ) {
					if ( event.keyCode === $.ui.keyCode.TAB &&
							$( this ).data( "autocomplete" ).menu.active ) {
						event.preventDefault();
					}
					if((event.keyCode == 39 || event.keyCode == 37) && $( this ).data( "autocomplete" ).menu.active ) {
						event.preventDefault();
					}
				})
				.keyup(function(event){
					if(event.keyCode == 39 || event.keyCode == 37) // left and right arrow keys. Need to update the current group in.
					{
						self._update_current_group(this);
						//$(this).autocomplete("close"); // close the menu since the user may be moving to another word.
					}
				})
				.autocomplete({
					delay: this.options.delay,
					minLength: this.options.minLength,
					focus: function(event, ui) {
						//console.log("called");
						//console.log(ui.item);
						// prevent value inserted on focus
						return false;
					},
					source: function(request, response) {
						//console.log("Getting source");
						self._get_matches(request, response);
					},
					// User selected an item.
					select: function( event, ui ) {
						var terms = self._split( this.value );
						self._insert_selection(this, terms, ui.item);
						return false;
					},
					// Called when the input element has lost focus and its value has changed.
					change: function( event, ui ) {
						//alert("next: " + ui.item.value);
					}
				});
		},

		destroy: function() {
			$.Widget.prototype.destroy.call( this );
		},
		
		// ----------------- Private functions ----------------
		
		_determine_datasource_type: function(){
			if($.isArray(this.options.data_source))
			{
				this.datasource_type = 1;
			}
			else if($.isFunction(this.options.data_source))
			{
				this.datasource_type = 2;
			}
			else if(Object.prototype.toString.call(this.options.data_source).slice(8, -1) == "String")
			{
				this.datasource_type = 3;
			}
			else
			{
				this.datasource_type = 0;
			}
		},
		
		
		_insert_selection: function(ctrl, terms, item){
			var newpos = 0;
			if(terms.length == 0) 
				terms.push(item.value);
			else
			{
				//console.log(terms, this.current_group);
				for(var g = 0; g < this.current_group; g++)
					newpos += terms[g].length;
				//newpos = ctrl.selectionStart - terms[this.current_group].length;
				terms[this.current_group] = item.value;
			}
			var value = String(item.value);
			newpos += value.length;
			ctrl.value = terms.join( "" );
			ctrl.setSelectionRange(newpos, newpos);
		},
		
		// Splits text into groups based on the regular expression used to indicate what is a 'word' and
		// what is not.
		_split: function(val) {
			var matcher = new RegExp(this.options.word);
			var parts = [];
			var cur = 0;	// Current character type (ingore: 0, include: 1)
			var prv = 0;	// Type of previous character
			var segment = "";
			for(var i = 0; i < val.length; i++)
			{
				prv = cur;
				cur = 0;
				if(matcher.test(val[i])) cur = 1;
				if(cur != prv)
				{
					if(segment != "") parts.push(segment);
					segment = "";
				}
				segment += val[i];
			}
			if(segment != "") parts.push(segment);
			return parts;
		},
		
		// Works out which group the caret is currently in.
		_update_current_group: function(ctrl) {
			var parts = this._split(ctrl.value);
			var curpos = ctrl.selectionStart;
			var cnt = 0;
			var done = false;
			if(parts.length == 0) this.current_group = 0;
			for(var p = 0; p < parts.length && !done; p++)
			{
				cnt += parts[p].length;
				if(curpos <= cnt) done = true;
				this.current_group = p;
			}

			//$("#debug2").html("P: " + curpos + " G: " + this.current_group);
			return 0;
		},
		
		_extract_current_group: function( term ) {
			var parts = this._split( term );
			if(parts.length == 0) return "";
			//$("#debug").html("Group count: " + parts.length + " = " + parts.join(" | "));
			return parts[this.current_group];
		},
		
		_get_matches: function( request, response ) 
		{
			this._update_current_group(this.element[0]);
			var text = this._extract_current_group(request.term);
			if(this.options.minLength > text.length) 
			{
				response([]);
				return;
			}
			if(text == "" || text == undefined || !this._is_word(text))
			{
				response([]);
				return;
			}

			var matches = [];
			var matcher;
			if(this.options.match_mode == 'start')
				matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex(text), "i" );
			else if(this.options.match_mode == 'any')
				matcher = new RegExp( $.ui.autocomplete.escapeRegex(text), "i" );
			else
				matcher = new RegExp( $.ui.autocomplete.escapeRegex(text) + "$", "i" );
			
			var options = [{label: 'one', value: '1'}, {label: 'two', value: '2'}, {label: 'three', value: '3'}, {label: 'one-hundred', value: '100'}];
			if(this.datasource_type == 1)	// array
			{
				var options = this.options.data_source;
				for(var i = 0; i < options.length; i++)
				{
					// An options elements could be using label/value pairs or just strings.
					if(options[i].label !== undefined && options[i].value !== undefined)
					{
						if(matcher.test(options[i].label))
							matches.push(options[i]);
					}
					else if(Object.prototype.toString.call(options[i]).slice(8, -1) == "String")
					{
						if(matcher.test(options[i]))
							matches.push({label: options[i], value: options[i]});
					}
					
				}
			}
			else if(this.datasource_type == 2)	// callback
			{
				matches = this.options.data_source(text);
			}
			else if(this.datasource_type == 3)	// url
			{
				$.post(this.options.data_source, {term: text}, 
					function(data) {
						response(data.options);
					}, 'json');
				
				return;
			}
			else
			{
				alert('Need a datasource!');
			}
			
			response(matches);
		},
		
		// Tests if all the characters in a string match the 'word' regex. If not, then the text cannot
		// be considered a word.
		// text: the text to check. If undefined, not a string or an empty string, false is returned!
		// Returns true if the text is a word. False if any one character is not.
		_is_word: function(text)
		{
			if(Object.prototype.toString.call(text).slice(8, -1) != "String") return false;
			if(text == "") return false;
			var matcher = new RegExp(this.options.word);
			for(var i = 0; i < text.length; i++)
			{
				if(!matcher.test(text[i])) return false;
			}
			return true;
		}
		
	});
})( jQuery );