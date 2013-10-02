/** ******************************************  widget-combobox.js  ******************************************
 *
 *   Based on the combobox widget code found at: http://jqueryui.com/demos/autocomplete/#combobox
 *  
 *  This version allows the user to enter custom values based on the 'select_only' options. If set to false
 *  the user can enter custom values. By default it is true.
 *  
 *  As the user enters a value, the list of available items is filtered by that string to show those items 
 *  that match. Very usefull when dealing with a large list, especially when it is unsorted.
 *  
 *  Usage is:  $( "#id of select element" ).combobox({select_only: true/false});
 *
 ** Changelog:
 *  2011/08/31 - Markus
 *  - Added a key up event handler which checks if the entered value matches a predefined value in the select
 *    list, or is to be treated as a new value. The value of the hidden field is set accordinly. Only applies
 *    when the 'select_only' option is set to false.
 *
*************************************************************************************************************/

(function( $ ) {
    $.widget( "ui.combobox", {
        options: {
            select_only: true
        },
        _create: function() {
            var self = this,
                select = this.element.hide(),
                selected = select.children( ":selected" ),
                //value = selected.val() ? selected.text() : "";
                value = selected.text();
                
            var hidden = null;
            if(this.options.select_only === false)      // User can provide a value. Meaning that we need a hidden field to store that value.
            {
                hidden = this.hidden = $("<input type='hidden'>")
                    .insertAfter(select)
                    .val(selected.val())
                    //.attr("type", "hidden")
                    .attr("name", select.attr("name"));  // The name from the select is assigned to the hidden field.
                select.attr("name", "");
            }
                
            var input = this.input = $( "<input>" )		// Textbox where the user can enter text into.
                .insertAfter( select )
                .val( value );
            var menu_holder = $('<span class="ui_widget_combobox_menu_holder" ></span>');		// Holds the menu items.
			$(input).after(menu_holder);
            $(input).autocomplete({					// Initialises the auto complete capability.
                    delay: 0,
                    minLength: 0,
                    source: function( request, response ) {
                        var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
                        response( select.children( "option" ).map(function() {
                            var text = $( this ).text();
                            //if ( this.value && ( !request.term || matcher.test(text) ) )
                            if ( ( !request.term || matcher.test(text) ) )
                            {
                                if(this.value == "" && request.term != "") return;      // Don't show the '-- select --' item which indicates nothing is selected, when the user has provided a request term.
                                return {
                                    label: text.replace(
                                        new RegExp(
                                            "(?![^&;]+;)(?!<[^<>]*)(" +
                                            $.ui.autocomplete.escapeRegex(request.term) +
                                            ")(?![^<>]*>)(?![^&;]+;)", "gi"
                                        ), "<strong>$1</strong>" ),
                                    value: text,
                                    option: this
                                };
                            }
                        }) );
                    },
                    // User selected an item.
                    select: function( event, ui ) {
                        ui.item.option.selected = true;
                        // When the user is allowed to enter a custom value, then the hidden element is used for storing the selected value.
                        if(self.options.select_only === false) hidden.val($(ui.item.option).val());
                        self._trigger( "selected", event, {
                            item: ui.item.option
                        });
                    },
                    // Called when the input element has lost focus and its value has changed.
                    change: function( event, ui ) {
                        var value_changed = false;
                        if(ui.item != null)
                        {
                            // A item was selected but the user continued to type in text, thus invalidating the currently selected item.
                            if($(ui.item.option).text() != $(this).val())
                                value_changed = true;
                        }
                        if ( !ui.item || value_changed) {
                            // When no item is selected see if any item matches the text contained in the textbox.
                            var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( $(this).val() ) + "$", "i" ),
                                valid = false;
                            select.children( "option" ).each(function() {
                                if ( $( this ).text().match( matcher ) ) {
                                    this.selected = valid = true;
                                    input.val($(this).text());
                                    if(self.options.select_only === false)
                                        hidden.val($(this).val());
                                    return false;
                                }
                            });
                            if ( !valid ) { // No match found.
                            
                                // When it is not required to select an element, the user's text is used as the value.
                                if(self.options.select_only === false) 
                                {
                                    hidden.val($(this).val() == "" ? "" : ("_[[new]]_" + $(this).val()));
                                }
                                else
                                {
                                    // remove invalid value, as it didn't match anything
                                    $( this ).val( "" );
                                    select.val( "" );
                                    input.data( "autocomplete" ).term = "";
                                }
                                return false;
                            }
                        }
                    },
					appendTo: menu_holder
                })
                .addClass( "ui-widget ui-widget-content ui-corner-left" );
			
			// On key up, if custom values can be supplied, see if the entered string matches an
			// existing value or is a new one. Sets the value for the hidden field accordingly. (Added 2011/08/31)
			input.keyup(function() {
				if(self.options.select_only === false)
				{
					var text = $(this).val();
					var found = false;
					select.children( "option" ).each(function() {
						if ( $( this ).text() ==  text) {
							hidden.val($(this).val());
							found = true;
						}
					});
					if(found === false)		// New value.
					{
						hidden.val($(this).val() == "" ? "" : ("_[[new]]_" + $(this).val()));
					}
				}
			});

            input.data( "autocomplete" )._renderItem = function( ul, item ) {
                return $( "<li></li>" )
                    .data( "item.autocomplete", item )
                    .append( "<a>" + item.label + "</a>" )
                    .appendTo( ul );
            };

           var button = this.button = $( "<span style='display: inline-block'>&nbsp;</span>" )
                .attr( "tabIndex", -1 )
                .attr( "title", "Show All Items" )
                .insertAfter( input )
                .button({
                    icons: {
                        primary: "ui-icon-triangle-1-s"
                    },
                    text: false
                })
                .removeClass( "ui-corner-all" )
                .addClass( "ui-corner-right ui-button-icon" )
                .click(function() {
                    // close if already visible
                    if ( input.autocomplete( "widget" ).is( ":visible" ) ) {
                        input.autocomplete( "close" );
                        return;
                    }

                    // pass empty string as value to search for, displaying all results
                    input.autocomplete( "search", "" );
                    input.focus();
                });
            
            // Make sure the button height is the same as the input field's height.
            var height_dif = input.outerHeight(false) - button.outerHeight(false);
            var in_out_dif = button.outerHeight() - button.innerHeight();  // Indicates padding + border height.
            button.height(button.height() + height_dif + in_out_dif - 1);
        },

        destroy: function() {
            this.input.remove();
            this.button.remove();
            this.element.show();
            $.Widget.prototype.destroy.call( this );
        }
    });
})( jQuery );