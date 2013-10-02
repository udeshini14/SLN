<?php

/** *********************************** libaries/renderer.php *****************************************
 *  
 * This class allows the creation of forms, tables, menus and other UI types.
 *
 * ***************
 * Changelog:
 *
 * 2010/04/19   - Markus
 * - First version that works reasonably well for generating basic forms. Still need to add many form
 *   elements that can be rendered.
 *
 * 2010/04/30 - Markus
 * - Added _merge_data_options() function to combine the model data with any custom field properties
 *   specified by the user. This gets called from render_as_form().
 *
 * 2010/05/01 - Markus
 * - Added valdiate_form() for form validation. Returns the error string or NULL if there  was no
 *   error.
 * - Added hidden field 'field_submit' when a form is generated so that the validate_form can check if
 *   a form was actually submitted.
 * - Added _add_associative_relationship() and _add_composite_relationship() functions for rendering
 *   relationships.
 * - Modified _add_field() to take into account relationships and added all available render types 
 *   that can be created by the form builder.
 *
 * 2010/05/18 - Markus
 * - Major changes to the render_as_form function. It can now check if  a form was submitted and
 *   perform the appropriate actions such as  creating/editing/deleting objects.
 *   The function can now also handle sub forms. This means if there is  a form with a composite
 *   relationship and the user clicks the create button associated with that relationship, then
 *   a new form is displayed for creating the composite object. This works for edit/create/delete
 *   actions. Once done with a sub form (via submit or cancel) the user is returned to the parent
 *   form. Still more work to be done.
 * - The way a controller is defined has changed. Updated _add_button function but some refinement is
 *   still needed.
 * - Changes to the way the action buttons are generated in _add_composite_relationship. Need to store
 *   some info in the button name so that render_as_form can work out what to do.
 *
 * 2010/05/21 - Markus
 * - Added render_as_table function to display records from a database table. The function also calls
 *   render_as_form depending on what action is  performed. Still needs more work to be fully usable.
 * - Updated render_as_form to test for an 'output' option. When the output option is set to true then
 *   the generated html for the form is returned to the caller function.
 * - Added a function _make_title_text that takes a string input and makes  every word uppercase and
 *   replaces underscores with spaces. This is used render_as_table and render_as_form.
 * - Added function _get_default_table_css which just returns css as a string for printing by
 *   render_as_table.
 *
 * 2010/05/24 - Markus
 * - To avoid possible clashes between table and form create actions, these actions are now prefixed
 *   with '_table_' or '_form_'.
 * 
 * 2010/05/25 - Markus
 * - Added function _render_as_composite_table. Its purpose is to create the the table of composite
 *   objects in a form. This function is used instead of render_as_table because the two functions
 *   create the tables in  different ways and _render_as_composite_table has fewer user actions to
 *   deal with.
 * - _add_composite_relationship is now using _render_as_composite_table instead of creating a table
 *   itself.
 * - changed some name formats for fields/url segments. All composite actions are now prefixed with
 *   _ct- (composite table)
 *
 * 2010/05/26 - Markus
 * - Small change to render_as_form to take into consideration of changed ui level when the user
 *   changes forms using the breadcrumbs.
 * - Form view now displays breadcrumbs when viewing subforms. User can switch between forms 
 *  (forwards and backwards)
 * - When the user has switched to a previous form and performs an action there then all subforms
 *   that exist for that form are removed from the user's session to keep it small. Submitting a form
 *   will also remove that form's data from the session (if submit succeeded). Submitting the top
 *   form does not remove that form's data nor clear the ui_data associated with that user interface.
 * - Added _clear_old_form_instances to remove form instances that are over the cache limit. At this
 *   moment a maximum of 50 forms can be stored. This is needed to avoid the user running out of
 *   space in the database. Forms will end up being unused (unsubmitted) as people are likely to
 *   switch to another function from time to time. Therefore this function is called in
 *   render_as_form every time.
 *   
 * 2010/05/27 - Markus
 * - When dealing with sub form, their relationship to the parent object now appears as text and no
 *   longer as a listbox, because this value should not be possible to change. This meant editing the
 *   model data to set the value for the relationship to the parent object and an update to the
 *   _add_associative_relationship function so it takes into account if a relationship has the static
 *   property set in the options array.
 * - Removed the code block in render_as_form that was responsible for checking if a composite action
 *   was performed and creating a new form. This code has now been put in the code block that checks
 *   if the top_ui_id and ui_level are valid as it fits in there better and avoids multiple saves of
 *   the ui data to the session.
 *
 * 2010/06/02 - Markus
 * - _render_as_composite_table has been updated to test if CRUD options are set or not. It can now
 *   also deal with searches.
 * - render_as_table has been updated to enable searching. The function now  returns an associative
 *   array which can contain the output html if so requested.
 * - render_as_form now stores submitted form data when the user view a sub-form or changes the ui
 *   level. If there is saved form data for the current form then that is used. 
 *  
 * 2010/06/04 - Markus
 * - Added require for cbeads/routines.
 *
 * 2010/06/05 - Markus
 * - Added render_as_menu for rendering a menu where each tab can display a database table from a
 *   namespace, a tab can show content created by a specified function or a combination of the 
 *   two.
 * - Modified _add_associative_relationship function. It wasn't working right in that it couldn't
 *   display a list of objects that reference the current object. This can now do that and
 *   depending on the number of items it can either show them as checkboxes or put them in a
 *   multiple select list.
 *   
 * 2010/06/07 - Markus
 * - Updated _add_associative_relationship() so it works when there are no selected relationships for
 *   the object.
 * - Updated _get_render_options_for_field so that if an attribute definition is found that matches a
 *   field name but it has no render type associated with it, then it will use a default render
 *   definition.
 * - Updated render_as_form. When a form is submitted and the values are saved to the object it now
 *   checks if values for relationships were also provided and updates the relationship as needed.
 *   Previously it only updated relationships where the object had the foreign key field.
 *
 * 2010/06/08 - Markus
 * - Further changes to render_as_form for when a form is submitted. The  relationship update code
 *   now handles many to many relationships and can remove all mappings for a relationship if no
 *   item was selected.
 * - Now using validation callback for for submit if it is provided.
 * - Various fixes to render_as_table to use option values if they are provided. Some of the big ones
 *   are that column and column order are now taken into consideration. Columns can be given custom
 *   labels.
 *   If there are form options defined then they are used for form rendering. Other smaller changes
 *   include setting the title, setting the description, hide search if set to false.
 * - Updated up render_as_menu to not use the items list to generate the  order. Either order is
 *   defined or all tables will be used.
 *
 * 2010/06/09 - Markus
 * - Now using controller callbacks if specified in render_as_menu and render_as_table.
 *
 * 2010/06/16 - Markus
 * - menu rendering now uses a different menu_item structure. Each menu item is now expected to be
 *   an array that can have a 'label' field to specify a custom label for the item and a 'content'
 *   field which is either an array of table options or a function/method reference.
 * - table rendering now passes the table options to the callback function.
 *   So the function will receive one parameter that contains the object id and table options. The
 *   table options can be altered. For example a table can have a description that depends on a
 *   currently selected object. If there is a 'select' controller that is clicked then the table
 *   description can be altered within the controller callback.
 * - added support for filtering columns in render_as_table. Had to modify  the DQL generation for the
 *   search part to get it to work.
 * - If there are no records to display then a message is displayed instead of generating just the
 *   table headers for the render_as_table and _render_as_composite_table functions.
 *
 * 2010/06/17 - Markus
 * - render as table now has a variable called all_columns which stores all existing column names for
 *   a model which can be filtered. This allows foreign key columns to be filtered.
 *
 * 2010/06/18 - Markus
 * - Moved _make_title_text to controllers/cbeads/routines.php as that function is needed in other
 *   files.
 *
 * 2010/06/22 - Markus
 * - Can now pass custom iteams to be used in associative relationships. At the moment cannot specify
 *   selected items for custom item lists.
 *
 * 2010/06/23 - Markus
 * - Can now use callbacks for menu items even when the item is an actual model. Callbacks for models
 *   need to return an array of table options!
 *
 * 2010/06/24 - Markus
 * - _remove_null now ignores arrays as sometimes empty arrays may be passed as field attributes.
 * - _add_associative_relationship now checks if there are related objects available, and if not
 *   then it prints a message.
 *
 * 2010/06/25 - Markus
 * - In render_as_table changed to looping through model_columns instead of column_order in generating
 *   the table as it makes no sense to use column_order.
 * - render_as_table now returns an array indicating success if displaying a form.
 * - modified _add_field to check for comments in the field attributes. To use this feature the form
 *   css was modified to include some extra css.
 *
 * 2010/06/27 - Markus
 * - Added $item->refreshRelated(); into render_as_table where the objects are added into the table.
 *   This was done because it appears that the relationships are not updated on edit/save for some
 *   reason. 
 *   (render as form should be doing that!)
 *
 * 2010/07/01 - Markus
 * - Modified render_as_table so that it uses $column_order again in the table construction. 
 *   $model_columns is not in the right order! Also fixed up the data stored in $model_columns as
 *   foreign key column names should not be replaced with their relation alias because then the table
 *   construction loop cannot match those columns in $model_columns.
 *
 * 2010/07/02 - Markus
 * - Modified _add_field(). Fixed associative relationships not using items list for one to many
 *   relationships if provided (was working for many to many).
 * - Modified render_as_form. When creating a formbuilder instance javascript options are now also
 *   provided.
 * 
 * 2010/07/19 - Markus
 * - Removed functions that returned CSS for menu/table/form rendering. That CSS is now expected to be
 *   provided via a css file. Renamed the various html elements and classes to reduce the chance of a
 *   conflict of names in the future. All names are now in the format renderer_[ui_type] where ui_type
 *   is menu, table or form.
 *
 * 2010/08/04 - Markus
 * - File uploads are now saved to the uploads folder under the web folder.
 *   This is still work in progress.
 * - Added function _get_merged_field_render_options which is responsible for merging the system
 *   defined render options for a fieldname with an array of render properties supplied for this
 *   field. The code used to be in  _add_field but was moved out because the render options for a
 *   fieldname are needed in render_as_form function as well.
 * - Added function _find_attribute_definition_for_field to help get the attribute definition
 *   associated with a field name.
 * - Added function _process_alias_attribute_definition to deal with attribute definitions that are
 *   aliases of other attribute definitions.
 *
 * 2010/08/05 - Markus
 * - Updated _add_associative_relationship so that if the relationship is not required an extra
 *   element is added to the list which has no value and its text is just '-- select --'. This way it
 *   is possible to unset a relation. When checkboxes are used then there is no such element added as
 *   it isn't necessary.
 * - Files uploaded, that go into the web folder have a md5 appended to their filename to avoid
 *   collisions. Private files are now stored in the database as long as the field data type is PFILE.
 *
 * 2010/08/09 - Markus
 * - _get_merged_field_render_options() now uses the function _find_attribute_definition_for_field
 *   in finding the field definition for a fieldname.
 * - Added function _is_privatefile_column for testing if a column/field is a data storage column in
 *   the database. Don't want to show these in forms or tables.
 * - render_as_table and _render_as_composite_table now ignore columns that store file data.
 *
 * 2010/08/12 - Markus
 * - Added function _format_filename to remove the md5 part of a filename.
 * - Added functions: _display_audio, _display_email, _display_file_download, _display_filename,
 *   _display_image, _display_video. These functions are for formatting data for display.
 *   Ie, a filename is formatted into a link to download the file. A email is formatted to be a link
 *   that opens a email client, and so on.
 * - _add_field() will now uses these new formatting functions where possible.
 * - Added function _format_table_data which will format data displayed in tables. For now it just
 *   cleans up public file names. 
 * - When uploading a new public file, the old one is deleted.
 * - Added code to render_as_menu that checks for form actions. Similar to what the table rendering
 *   function does. This is needed to handle download requests.
 * - _add_field() now uses width/height values specified in the render options for text/textarea
 *   fields.
 *
 * 2010/08/13 - Markus
 * - Removed validate_form() as it is no longer needed.
 * - Created function _process_file_download() to handle forcing files to the browser instead of
 *   having most of the code in render_as_form().
 * - Created function _process_file_uploads() to do the file upload checking that used to be done in
 *   render_as_form().
 *
 * 2010/08/16 - Markus
 * - File uploading/deleting/overwriting now works.
 * - Added function _display_file_delete to create the html + JS that allows a file to be 'deleted'
 *   from a form.
 * - Modified _process_file_uploads to check if a file was deleted from the form, which means it needs
 *   to be deleted from the server as well.
 * - Modified render_as_form (in the form submit delete action block) to remove any files that are
 *   associated with an object.
 * - Added function _delete_file_for_field to handle the actual removal of a file from a model
 *   instance.
 *
 * 2010/08/17 - Markus
 * - Added function _inherit_default_form_options which allows default form options to be inherit by
 *   form options for a specific action type.
 * - Added function _inherit_table_options which allows a default form to inherit some table options
 *   (such as order).
 *
 * 2010/08/20 - Markus
 * - Added check for a callback function on form cancel.
 *
 * 2010/08/23 - Markus
 * - In _add_associative_relationship(), added check to make sure the selected item exists before
 *   using it as an index element.
 *
 * 2010/08/24 - Markus
 * - Modified _add_button, so that for cancel buttons existing values for onclick are kept when
 *   setting 'skip_onsubmit=true;' for onclick.
 *
 * 2010/08/30 - Markus
 * - Modified render_as_form:
 *   - On submit, if the hide_form option is set and the submit succeeded, then
 *     the form is not displayed.
 *   - When the hide_form option is set, then the form is deleted from the user session to help save
 *     space. Created function _remove_form() to do that.
 *   - On successfull object update/create, the object's relations are refreshed. Needed so that
 *     callbacks get the object passed with the right related objects.
 *   - _clear_old_form_instances() now starts to remove forms after 30 have already been stored.
 * 
 * 2010/09/08 - Markus
 * - Modified render_as_menu to test if the output value was set in the options. The menu tabs are
 *   now rendered after the table is processed. The table output is now stored in a variable so the
 *   tabs can be printed and then the table output. This fixed the problem with file downloads
 *   containing html of the menu tabs.
 * - Modified render_as_table so that when a form has to be displayed, the html is returned if the
 *   output value was set to true in the options array.
 *
 * 2010/09/09 - Markus
 * - Added 'colour_display' to switch statement in _add_field() and added function
 *   _display_colour_and_value to generate the html when viewing colour fields.
 * - Added case two switch block in _form_table_data to check for columns with 'colour_display' output
 *   type.
 * - Modified the loop that applies value to an object on form create/edit submit. Now a function gets
 *   called to process the value in case the given field needs values to meet a certain requirement.
 *   For example if a password field is submitted and has no value then the password field in the
 *   object is not given a empty value.
 *
 * 2010/09/13 - Markus
 * - For rendering a custom menu tab, the function called is now contained within an output buffer.
 *   The buffered contents are then printed out after the menu html.
 * - The function called for rendering custom menu contents is now passed a parameter containing the
 *   current menu name.
 * 
 * 2010/09/16 - Markus
 * - Updated _get_merged_field_render_options() to check if a field has options defined, indicating
 *   this is a enum field. For enums a select control is needed as the input control.
 *
 * 2010/09/20 - Markus
 * - The form_renderer now displays form descriptions.
 * - When the validation of a doctrine record fails (in form submit) then the form is displayed again
 *   with the errors. In addition, previously entered values are displayed in the fields (however does
 *   not work with many to many relations).
 * - Moved cbeads/routines include from here to the user_validation library because this function gets
 *   run every time before any controller is loaded and thus the routine functions can be accessed
 *   anywhere.
 *
 * 2010/09/26 - Markus
 * - Can now specify a input_type for associative relationships that are many to many.
 * - Added variable attr_render_defs to this module to store render types from the database. This is
 *   used by _get_render_options_for_field() instead of searching for render type for every column
 *   created in a table.
 * - When an object is created/edited/delete the success message now uses remove_namespace_component()
 *   to seperate the class component from the name with a namespace component.
 *
 * 2010/09/27 - Markus
 * - Commented out the refreshRelated() function call on objects that have relationships in
 *   render_as_table (where the table is being constructed) as this causes a very large number of
 *   database queries. Need to think about this.
 *
 * 2010/10/05 - Markus
 * - Updated _inherit_default_form_options() to recursively compare array values.
 *
 * 2010/10/21 - Markus
 * - Updated the render_as_table function so that search terms are base64 encoded and decoded to avoid
 *   issues with disallowed url characters. Also using the htmlentities to encode the search string
 *   when displaying it in the search text box.
 * 
 * 2010/10/22 - Markus
 * - Updated the render_as_table function to check for a options called 'dynamic_setup' in the form
 *   option and to call the given function to to get dynamically generated form options.
 *
 * 2010/10/25 - Markus
 * - Updated _find_attribute_definition_for_field to ignore 'id' fields because they should not be
 *   matched to attribute definitions that may contain the letters 'id' (like learning_guide).
 * - Table titles now use remove_namespace_component to give nicer titles.
 *
 * 2010/11/01 - Markus
 * - Can now pass an array of options to system controllers (view, create, edit, delete) for table
 *   rendering. Allows setting custom labels for system controllers.
 * - Replaced the 'view', 'edit' and 'delete' action links in a rendered table with images. The create
 *   action now also has an image.
 * - Can now specify a image url for custom controllers. This image is displayed instead of the
 *   controller text.
 * - Table pagination now indicates how many pages there are when hovering the cursor over the last
 *   page button.
 *
 * 2010/11/04 - Markus
 * - When uploading public files the renderer can be told not to append the md5 hash to the filename.
 *   This is done by setting the global variable $renderer_hash_file_uploads to FALSE. By default it
 *   is TRUE. The _process_file_uploads() and _format_filename functions have been updated to take 
 *   this into consideration.
 *
 * 2010/11/08 - Markus
 * - The menu rendering function now defaults to the first item in the item order if the active_item
 *   url value is set to something that is not in the item array. This prevents people accessing
 *   tables they are not supposed to.
 *
 * 2010/11/10 - Markus
 * - MAJOR CHANGE: Converted module to a class to get rid of all globals and to be able to restrict
 *   access to certain functions. To render, one must now create an instance of the renderer object.
 *   The constructor accepts an array of options to set some default values, but at the moment this
 *   is not used for anything.
 * - Made CI a class wide variable as well as the uri segments array to reduce the number of
 *   function calls.
 *
 * 2010/11/11 - Markus
 * - Removed the success, warning and error messages (moved to cbeads helper module). Updated calls to
 *   these functions to use their new names.
 *
 * 2010/11/17 - Markus
 *  - Added the ability to set a desired date format for what users will see and a date format for
 *    what the database expects dates to be in. This involved:
 *    Adding functions user_date_format() and database_date_format() to set those format values.
 *    Adding functions format_value_for_input and format_value_for_output to transform date values by
 *    the given formats.
 *    Updating the _add_field(), _process_form_input_value() and render_as_-table() functions to use
 *    those format converter functions so that dates are properly formatted when shown in tables,
 *    forms and when submitting forms, dates are converted to the format the database wants.
 *    Added function convert_php_date_format_to_jquery_date_format(). This is needed for setting up
 *    jquery date pickers to use the required date format.
 *    
 * 2010/11/24 - Markus
 *  - Can now sort foreign key columns.
 *  - Moved sections of code from render_as_table into various functions. Both render_as_table and
 *    render_as_composite_table now share the same function to generate the pagination controls.
 *
 * 2010/11/30 - Markus
 *  - process_table_action() now has the table_options parameter passed by reference. Need to do this 
 *    because a callback function may change the table options.
 *
 * 2010/12/02 - Markus
 *  - Updated render_as_composite_table so that the controllers use images.
 *  - Split up render_as_form(). The part concerned with building up the form is now in its own 
 *    function called build_form().
 *
 * 2010/12/07 - Markus
 *  - Updated _inherit_default_form_options() to not overwrite form options: order & controller_order
 *  - Updated _inherit_table_options() to use the table column order for the form order only if the 
 *    form order key is not set. This allows forms with no fields (needed in some occasions).
 *
 * 2010/12/14 - Markus
 *  - Updated render_as_form. The isValid() check now generates a message if a unique validation fails
 *    and unhandled error codes are now displayed as well (need to add nice message strings as new 
 *    error codes are encountered!)
 *
 * 2010/12/15 - Markus
 *  - Added new code to render_as_form which checks to see if a composite form is in use, and if so 
 *    it tries to find if form options have been defined for this level. In addition some code has
 *    been moved and improved to get any table options specified for composite tables in forms. This 
 *    is then added to the form options array so it can be passed to _add_composite_relationship.
 *  - _add_composite_relationship has been updated to pass all options it has received to the 
 *    _render_as_composite_table function.
 *  - _render_as_composite_table can now process column orders and column definitions.
 * 
 * 2010/01/20 - Markus
 *  - Can now specify callbacks to generate the options to use for default forms. Works for normal &
 *    complex tables. The callback is now also passed the parent id.
 * 
 * 2011/01/21 - Markus
 *  - The pager object used with generating tables is now provided a query string to use for counting.
 *    Done to avoid the same query object being used twice, which can cause problems for models that
 *    hook into DQL queries. Generating queries were modified to remove the use of ? in sub queries.
 *    All sub queries must now list the values used to allow the use of $query->getDql().
 *  - Search boxes can now be hidden when setting the search option to false. They are also hidden when
 *    there are no items to display. Can now specify what CRUD actions composite tables will allow.
 *  - _remove_null() now does a strict comparison (===) to test if values are NULL. Else it chucks out
 *    options that are set to FALSE, and that isn't good.
 *  - When composite forms are submitted with a create action, the foreign key pointing to the parent 
 *    object is now automatically set. So now there is no need for a hidden field storing that id.
 *    The parent id value is now supplied to the model data when creating a new child object, so that 
 *    if the form options say the parent relationship should be shown, the correct object can be 
 *    automatically stringified. Useful for showing the parent object when creating a child object.
 * 
 * 2011/01/24 - Markus
 *  - Renamed _remove_null to _remove_null_or_empty_string. The function now checks if the value is 
 *    NULL or an empty string.
 *
 * 2011/01/27 - Markus
 *  - Can now search and sort for each composite table in a form. Each table maintains its own 
 *    information on search and sort information.
 *
 * 2011/02/03 - Markus
 *  - Modified _clear_old_form_instances() to remove ui_data elements for which there are no more 
 *    form instances found.
 *  - Modified render_as_form() to delete the form instance associated with a submitted form.
 *    When no submit errors occurred AND when render_as_table() has called render_as_form() or
 *    the hide_form option is set, then the function returns (avoiding rendering the form again).
 *  - Added class variable _rendering_table which is set to true when render_as_table is run. (See
 *    above comment).
 *  - Added function _display_text which replaces new line\carriage return characters with html <br>
 *    elements. Used _add_field() and _format_table_data().
 *  - Added function _remove_composite_forms() which is called to remove all composite forms instances 
 *    for a top level ui.
 *
 * 2011/02/16 - Markus
 *  - Fixed problem where, when using composite forms and a submit action occurs, the top most form 
 *    options where used. This was because the options array was only being updated after the form 
 *    submit section. Fixed it by creating a new function called get_correct_form_options that returns
 *    the form options from the options array based on the current model. It is used twice, once to 
 *    get the form options of the submitted form, and again if the ui level has changed (due to a 
 *    successful form submit).
 * 
 * 2011/02/28 - Markus
 *  - Fixed issue with forms and php complaining output headers have already been produced. The whole
 *    render_as_form() is now being buffered. This way, at the end when calling _clear_old_form_instances
 *    php still hasn't outputted the headers and can therefore add the cookie header which happens
 *    when setting session data.
 *
 * 2011/03/02 - Markus
 *  - Updated the error messages in render_as_menu for when a callback function/method to render a tab 
 *    cannot be found. The requested tab is now contained in quotes which should show up any spaces
 *    padding the name.
 *
 * 2011/03/04 - Markus
 *  - Updated generate_query_for_table_render() to fix the problem when generating the SQL for the 
 *    filter component. Now adding a space between the 'AND' and 'OR' and the preceeding value.
 *    "eg c.id = '2'AND" is now "c.id = '2' AND"
 *  - Also added the ability to have table columns sorted by default (not yet implemented for composite
 *    tables.
 *
 * 2011/03/08 - Markus
 *  - The display_image function now outputs a <img> tag pointing to the image file. This only works
 *    for public files. Could technically embed an image directly in the html.
 *  - Updated the file upload element to display the previously uploaded image if the output type for
 *    that field is 'image'. When the delete text is clicked to indicate that the previously uploaded
 *    file should be deleted, then when selecting a new file the warning message indicating that the
 *    previously uploaded file will be overwritten is not displayed. This is because the user has
 *    already indicated that they wanted to get rid of the previous image.
 *  - Modified _get_merged_field_render_options() so that when an enumeration list is generated, a 
 *    item that acts as NULL is added when the field is not required.
 *
 * 2011/03/15 - Markus
 *  - Updated render_as_form so that when forms are submitted, a test is performed to see if the 
 *    options contain custom success message for create/update/delete form action. If so, then the 
 *    custom messages are used.
 *
 * 2011/04/07 - Markus
 *  - Updated render_as_form. When saving a form the foreign key field is tested to see if it is 
 *    rendered as a combobox. If is rendered as a combobox, and form supplied value indicates that the
 *    the user wanted to create a new object, then the supplied callback is called to create a new 
 *    object. The id of the new object is then used as the field value.
 *  - Modified _add_associative_relationship() so that for foreign key fields, if the input_type is 
 *    set to 'combobox', then a combobox is generated instead of a normal select box.
 *
 * 2011/04/10 - Markus
 *  - Added function get_form_submit_action().
 *  - Updated build_form() to add a cancel button if so defined in the controller order. It now also 
 *    sets the controller button names to be of the form '_form_action_[controller_name]' to allow 
 *    custom controller callbacks to be used.
 *  - Updated render_as_form() to use get_form_submit_action(). When a form submit occurs, the 
 *    'cancel' action is looked for first. Then the form is processed by its type and any requested 
 *    controller is run.
 *
 * 2011/04/11 - Markus
 *  - Updated render_as_form() to pass the 'using_js' attribute on form construction. Always want to 
 *    to have javascript active to allow form controllers to abort form submits if needed.
 *
 * 2011/06/10 - Markus
 *  - Added function construct_controllers_for_form(). It ensures the controllers array contains 
 *    default definitions of controllers. Any controller definitions provided via the options array 
 *    merged into the default definitions.
 *  - In render_as_table, the model_columns and non_rel_columns arrays now store all fields even when
 *    they are not in the column order. Allows filtering by columns that aren't in the order array.
 *  - generate_query_for_table_render() now receives the column order to restrict searches to fields
 *    that are  displayed in the table.
 *
 *
 * 2011/06/28 - Markus
 *  - In _add_field(), when the render type is file and the field is required, then the (Delete) part
 *    is not included. No value testing is performed for required file fields in edit forms since no
 *    file change may have occurred. This way the user won't be told to specify a file to upload.
 *
 * 2011/06/29 - Markus
 *  - In _add_button(), fixed a typo with the onclick value (had ;' instead of '; resulting url issues).
 *
 * 2011/06/30 - Markus
 *  - In construct_controllers_for_form(), removed the 'action' attribute of controllers generated by 
 *    default because that caused a useless page request to be generated on form submit.
 *  - In render_as_table(), added check to only call stringify_self() on a related object when it
 *    actually exists (by testing that the id is not NULL).
 *
 * 2011/08/09 - Markus
 *  - The render_as_menu function was updated so that it can be used without specifying a namespace. 
 *    This means that it can be used for showing any content via tabs without needing a database.
 *  - In render_as_table added data type casting on URI input to ensure the value is the right type.
 *    Also added checks to ensure that actions (edit, custom actions, etc...) can only be performed 
 *    on objects that exist. If the object does not exist then a error message is now displayed.
 *  - Updated the render_composite_table function to also type cast expected integers. The function 
 *    now calls generate_query_for_table_render() instead of creating the DQL itself.
 *  - Updated render_as_form() to type cast some values to integers. Also removed a portion of code 
 *    and put it into its own function to reduce the size of the render_as_form function. 
 *    The new function is called get_form_id.
 *
 * 2011/08/11 - Markus
 *  - Updated function names throughout. Cannot refer to functions in the old cbeads/routines anymore.
 *
 * 2011/08/18 - Markus
 *  - Added function generate_columns_for_item() which now generates the cells for an item in the 
 *    table view (excludes the controller cells). This function is now used by render_as_table and by
 *    render_as_composite_table.
 *
 * 2011/08/21 - Markus
 *  - For render_as_form options, can now set the '[create/edit/delete]_success_message' options to 
 *    FALSE. When set to FALSE, no message is displayed.
 *
 * 2011/08/25 - Markus
 *  - Tables can now display custom columns (columns that don't match attributes or relations in the 
 *    model). For this to work, a callback function must be provided for the column which has to 
 *    a value that will be put into the table.
 * 
 * 2011/08/26 - Markus
 *  - Added new option 'filter_by_dql' for table rendering. A DQL string can be provided which is used
 *    for filtering records to display in the table.
 *
 * 2011/09/01 - Markus
 *  - Added a flag that indicates if a form was rendered. Also added function 'was_form_rendered' to
 *    retrieve this value. It may be necessary to know if a form or table was rendered when using 
 *    render_as_table.
 *
 * 2011/09/09 - Markus
 *  - Fixed typo in _merge_data_options(). Had $options->order instead of $options['order'].
 *
 * 2011/09/16 - Markus
 *  - Added function _init_control_lists() that populate the _input_controls and _output_controls
 *    arrays. These arrays define what input controls are available and the various output data 
 *    formats that are available. The arrays are publicly accessible. This allows one to know 
 *    what is supported by the Renderer and to modify the output formats available.
 *  - Both render_as_form and render_as_table now use the output controls list to output data. 
 *    This allows for consistent output displays.
 *  - Added new formatting functions to replace the current _display_[X] functions to take 
 *    advantage of the unified data output formatting and displaying. These functions are 
 *    public so that they can be used in custom UIs as needed.
 *  - Updated _add_field(). It now have two switch statements to select the form-builder function to
 *    use for input and output fields. For output fields, if a formatting function is defined, it 
 *    is called to format the data. The data is then added to the form instance.
 *  - Updated _format_table_data() to now check the column definition for a explicitly defined
 *    output_type (or implicitly defined via a render_type). The output_type is then used to find out 
 *    if a formatting function has to be called to format the data to output.
 *
 * 2011/09/20 - Markus
 *  - Added hour_of_day_picker input control.
 *
 * 2011/09/22 - Markus
 *  - Added support to define how time values are formatted for display to the user and how the 
 *    database wants the format to be. Functions: user_time_format() and database_time_format().
 *  - Added variables _db_time_format and _user_time_format.
 *  - Now passing _user_time_format value when setting the attributes for the form-builder instance.
 *  - Now passing any defined field options to _process_form_input_value. This is used to find out if 
 *    a custom input type was defined for the field to allow the input value to be formatted in a way
 *    that the database can accept it.
 *  - Added functions _format_from_db_hour_of_day and _format_to_db_hour_of_day to convert a db time 
 *    string to something that a hour of day field can use and vice versa. The hour_of_day_picker now
 *    is functional.
 * 
 * 2011/09/26 - Markus
 *  - Removed old display/formatting functions that have been replaced by the new public formatting 
 *    functions (_display_[text/file_download/colour_and_value/email/filename/image]etc..)
 *
 * 2011/09/27 - Markus
 *  - Fixed usage of wrong variable name in format_as_image().
 * 
 * 2011/09/29 - Markus
 *  - Updated format_as_hour_of_day. It can now accept time formats in database format or user format.
 *
 * 2011/10/17 - Markus
 *  - Updated _display_file_delete() to use an image instead of just text saying 'Delete'.
 *
 * 2011/11/03 - Markus
 *  - Can now set a 'process_uploads' flag in form controllers which determine if uploaded files are 
 *    processed or not.
 *
 * ***************************************************************************************************/

class Renderer
{
 
    private $_attr_defs = array();          // Holds all attribute definitions that exist.
    private $_attr_render_defs = array();   // Holds all attribute render defintions that exist.    
    private $_form_type = 'create';         // Holds the form type in use. Default is create.
    private $_validation_types = array();
    private $_object_id = NULL;             // The id of the object being edited
    private $_top_ui_id = NULL;
    private $_ui_level = NULL;
    
    private $_uploads_directory = NULL;     // The directory to upload files to (relative to the web folder). Defaults to /web/uploads/[application]
    public $_hash_file_uploads = TRUE;  // Indicates if uploaded files (public files) should have their md5 value appended to their filename to avoid name conflicts.

    private $_CI = NULL;            // Holds a reference to the CodeIgniter instance.
    private $_uri_segments = NULL;  // Holds the url segments array.
    
    private $_db_date_format = "Y-m-d";     // Format that the database requires dates to be in. Default is YYYY-MM-DD.
    private $_date_format = "Y-m-d";        // Format that dates will be displayed as to the user. Date inputs are also expected to be in this format.
	private $_db_time_format = "H:i:s";		// Format that the database requires times to be in. 24 hour time - HH:MM:SS.
	private $_user_time_format = "g:i:sa";		// Format that times will be displayed as to the user. Time inputs are also expected to be in this format.
    
    private $_rendering_table = FALSE;      // Indicates if render_as_table() has been called. (see 2011/02/03) 
    
	private $_rendered_form = FALSE; 		// Indicate if a form was actually rendered or not.
	
    public $_table_create_label_format = "Add new %model%";
	
	public $_input_controls = array();		// Stores a list of input controls the Renderer supports.
	public $_output_controls = array();		// Stores a list of output controls the Renderer supports.
    
    // Class constructor.
    // options: an associative array of option values that the Renderer should use.
    public function Renderer($options = array())
    {
        // Get all attribute definitions now so that _getRenderOptions doesn't have to load them
        // for every field.
        $this->_attr_defs = Doctrine::getTable('cbeads\Attribute_definition')->findAll();
        
        // Get all render defintions now so that _get_render_options_for_field doesn't have to load 
        // them for every field.
        $this->_attr_render_defs = Doctrine::getTable('cbeads\attribute_render_def')->findAll();
        
        // Get all validation types now so that _make_regex doesn't have to load it every time.
        $this->_validation_types= Doctrine::getTable('cbeads\Validation_type')->findAll();
        
        $this->_CI =& get_instance();      // Reference to the CodeIgniter instance
        $this->_uri_segments = $this->_CI->uri->segment_array();
        
        // Set the default upload directory to the application's namespace.
        $this->_uploads_directory = 'uploads/' . $this->_uri_segments[1];
        
		// Initialise the input and output control lists.
		$this->_init_control_lists();
		
    }
	
	// Intialises the input and output control lists.
	private function _init_control_lists()
	{
		$this->_input_controls = array(
			'textbox' => array('description' => 'A textbox for entering a value'),
			'textarea' => array('description' => 'A textarea for entering multiple lines of text'),
			'map' => array('description' => 'A map for selecting a location'),
			'date' => array('description' => 'A date select control'),
			'file' => array('description' => 'A file select control. If a previously uploaded file exists, displays a link to the file or displays the file in the page if that is supported'),
			'ckeditor' => array('description' => 'A WYSIWYG html editor'),
			'html_editor' => array('description' => 'A WYSIWYG html editor'),
			'tinymce' => array('description' => 'A WYSIWYG html editor'),
			'select' => array('description' => 'A select control for selecting one or multiple values.'),
			'select_sort' => array('description' => 'Two lists of items that can be sorted and allow items to be swap between them.'),
			'sort_list' => array('description' => 'A list of items that can be sorted.'),
			'password' => array('description' => 'A password entry textbox. Typed text is masked.'),
			'yesno' => array('description' => 'Allows a Yes(1) or No(0) selection.'),
			'daterange' => array('description' => 'A date range (start - end) select control.'),
			'country_list' => array('description' => 'For selecting a country from a list of countries.'),
			'truefalse' => array('description' => 'Allows a TRUE(1) or FALSE(0) selection.'),
			'radio_buttons' => array('description' => 'A list of radio buttons for selecting one value from a given list'),
			'check_boxes' => array('description' => 'A list of check_boxes for selecting multiple values from a given list'),
			'checksort' => array('description' => 'A list of checkboxes for slecting multiple items, plus a sort list to allow sorting of the items that have been selected'),
			'captcha' => array('description' => 'A captcha.'),
			'slider' => array('description' => 'A slider for selecting a value.'),
			'rating' => array('description' => 'A control for selecting a star rating.'),
			'colourpicker' => array('description' => 'A colour select control.'),
			'hour_of_day_picker' => array('description' => 'For selecting an hour of the day.', 'format_to_db' => array($this, 'format_to_db_hour_of_day'), 'format_from_db' => array($this, 'format_from_db_hour_of_day'))
		);
	
		$this->_output_controls = array(
			'text' => array('description' => 'Displays a html value. If text needs to be displayed that contains special html characters, then entity encoding is required.', 'format_function' => array($this, 'format_as_text'), 'control' => 'text'),
			'text_without_label' => array('description' => 'Displays html without an associated label.', 'control' => 'html'),
			'none' => array('description' => 'Displays nothing.', 'control' => 'none'),
			'file_download' => array('description' => 'Displays a file download link.', 'control' => 'text', 'format_function' => array($this, 'format_as_file_download')),
			'email' => array('description' => 'Displays an email link.', 'format_function' => array($this, 'format_as_email_link'), 'control' => 'text'),
			'image' => array('description' => 'Displays an image.', 'format_function' => array($this, 'format_as_image'), 'control' => 'text'),
			'colour_display' => array('description' => 'Displays a hex colour value followed by a box filled with that colour.', 'format_function' => array($this, 'format_as_colour_and_value'), 'control' => 'text'),
			'map' => array('description' => 'Display a location on a map', 'control' => 'map'),
			'yesno' => array('description' => 'Displays "Yes" for a value of 1, and "No" for a value of 0.', 'format_function' => array($this, 'format_as_yesno'), 'control' => 'text'),
			'truefalse' => array('description' => 'Displays "True" for a value of 1 and "False" for a value of 0.', 'format_function' => array($this, 'format_as_truefalse'), 'control' => 'text'),
			'hour_of_day' => array('description' => 'Displays the hour component of a time.', 'format_function' => array($this, 'format_as_hour_of_day'), 'control' => 'text')
		);
	
	
	}
    
    
    // Expects an array of data to populate the form with (assuming
    // the options array has the 'type' option set to 'edit') and an
    // array of options that define how to render the form.
    public function render_as_form($options, $data = NULL)
    {
        ob_start();
    
        $org_options = $options;        // Keep a copy of the original options array.
        $this->_CI->load->helper('download');  // Helper that generates file download headers.
        // nice_vardump($data);

        $this->_top_ui_id = NULL;          // The unique id required for all form submissions to get the correct data from the ui_data array in the user's session.
        $this->_ui_level = NULL;           // The ui level (will be 0 unless working with composite forms)
        $form_id = NULL;            // The unique form id to use when creating a form instance.
        $cur_ui_data = NULL;        // Stores the ui_data for the specified top_ui_id
        $form_submit = FALSE;       // Flag indicating if this is a submitted form (usually the case when the form_submit field exists)
        $form_action = NULL;        // Stores the name of a form action if supplied via the url
        $form_action_data = NULL;   // The data associated with the form action (just a string)
        $composite_action = NULL;   // Stores a composite table action type if provided (create/edit/delete) 
        $composite_model = NULL;    // The composite model on which to perform a composite action
        $composite_id = NULL;       // The id of the composite object on which to perform a composite action
        $submit_url = $this->_CI->uri->uri_string();   // The url the form will submit to.
        $prv_level = false;         // Flag that indicates if the previous form should be shown.
        $change_ui_level = NULL;    // If user clicks the breadcrumb then this stores the ui level the user wants to go to.
        $submit_error = FALSE;      // Flag indicating if a form submit didn't succeed (validation errors). This is returned by the function for use by render_as_table().
        $hide_form = FALSE;         // Flag indicating if the form should be hidden after submit (applies to create, edit and delet).

        //print '<pre>'; print_r($data); print '</pre>';
        if(empty($options) || !is_array($options)) return $this->_result(FALSE, 'Options array needs to be supplied to render_as_from()');

        // Check for various values that are expected. If they are not defined then use default values.
        if(!isset($options['type'])) $options['type'] = 'create';

        $this->_form_type = $options['type'];

        // Get id of object being edited, viewed, deleted
        if($this->_form_type != 'create') $this->_object_id = $data['object_id'];
        
        // Can have a custom upload directory specified.
        if(!empty($options['upload_directory'])) $this->_uploads_directory = $options['upload_directory'];
        
        
        
        // Check if there is a segment indicating a form action
        // Eg: /app/ctrl/index/_form_[action]/data  where [action] indicates the action and data is a string associated
        // with the action. Always expecting the first 3 segments to be /app/controller/function
        for($i = 4; $i < count($this->_uri_segments) + 1; $i++)  // indices start from 1!
        {
            // See if this segment indicates a form download request.
            if($this->_uri_segments[$i] == '_form_download')
            {
                $form_action = 'download';
                $form_action_data = $this->_uri_segments[$i+1];        // The field representing the file to download.
                break;
            }
        }
        
        // Check the POST or url segments for _top_ui_id and ui_level values
        if(!empty($_POST['_top_ui_id'])) $this->_top_ui_id = (int)$_POST['_top_ui_id'];
        if(isset($_POST['_ui_level']) && $_POST['_ui_level'] !== NULL ) $this->_ui_level = (int)$_POST['_ui_level'];
        
        // The form_submit field (when set to 1) indicates a form submission. Ie some action was performed with this form.
        $form_submit = (isset($_POST['_form_submit']) && (int)$_POST['_form_submit'] == 1) ? TRUE : FALSE;

        // If there is a field name that starts with '_ct-' then a composite table action was performed.
        // Also check if a change level action was taken (user clicked on breadcrumb).
        foreach($_POST as $name => $val)
        {
            if(preg_match('/^_ct-/', $name)) // Will be in format '_ct-[action]-[model]-[id]'
            {
                $parts = explode('-', $name);
                $composite_action = $parts[1];
                $composite_model = str_replace(':', "\\", $parts[2]);
                if(isset($parts[3])) $composite_id = $parts[3]; // not supplied for create action
                //echo "<br> composite action values: <pre>"; print_r($parts); echo '</pre><br>';
            }
            elseif(preg_match('/^_change_ui_level-/', $name))
            {
                $parts = explode('-', $name);
                if(isset($parts[1]))  $change_ui_level = $parts[1];
            } 
        }

        $ui_data = $this->_CI->session->userdata('ui_data');

        if($change_ui_level !== NULL)
        {
            $this->_ui_level = $change_ui_level;
        }
		
		$result = $this->get_form_id($ui_data, $cur_ui_data, $form_id, $data, $composite_model, $composite_action, $composite_id, $change_ui_level, $form_submit);
        if($result !== TRUE) return $this->_result(FALSE, $result);

        // Check for form actions.
        if($form_action !== NULL)
        {
            if($form_action == 'download')  // File download requested. Get the file contents associated with the field and force it to the user.
            {
                $this->_process_file_download($this->_object_id, $form_action_data, $cur_ui_data['model'], $data);
                return; // Nothing else to do.
            }
        }
        
        // At this point, the form options of the current form need to be retrieved.
        $options = $this->get_correct_form_options($options, $cur_ui_data, $ui_data, $data);
        // Ensure the controllers array is provided with default values.
		$this->construct_controllers_for_form($options);
		
        // Need an array called fields
        if(!isset($options['fields']) || !is_array($options['fields']))
            $options['fields'] = array();
        
        // Start the form generation.
        include_once(APPPATH.'libraries/form_builder.php');

        
        // Create a new form or get data from an existing form.
        $form = new form_builder($form_id);
        $form->setAttributes(array(
            "tableAttributes" => array("width" => (!empty($options['width']) ? $options['width'] : "100%")),
            "action" => site_url($submit_url),
            "enableSessionAutoFill" => 1,
            "map" => (!empty($options['layout'])) ? $options['layout'] : NULL,
            'form_js' => (!empty($options['javascript'])) ? $options['javascript'] : NULL,
            'pre_init_js' => (!empty($options['pre_init_javascript'])) ? $options['pre_init_javascript'] : NULL,
            'post_init_js' => (!empty($options['post_init_javascript'])) ? $options['post_init_javascript'] : NULL,
            'jqueryDateFormat' => $this->convert_php_date_format_to_jquery_date_format($this->_date_format),
			'timeFormat' => $this->_user_time_format,
            'using_js' => TRUE
        ));


        // If a form has been submitted and no composite action occurred (including changing levels when 
        // using composite forms), check what action was performed.
        if($change_ui_level === NULL && $form_submit && $composite_action === NULL)
        {
            $submit_action = $this->get_form_submit_action();
            if($submit_action == 'cancel')  // Cancel action means the form must be hidden.
            {
                if($this->_ui_level > 0) $prv_level = true;
                // Check if some callback needs to be called on the cancel action.
                $controller = NULL;
                if(isset($options['controllers']['cancel']))
                    $controller = $options['controllers']['cancel'];
                if($controller !== NULL && isset($controller['callback']))
                {
                    if(is_callable($controller['callback']))
                        call_user_func($controller['callback'], NULL);
                    else
                        print cbeads_error_message("Controller callback provided cannot be called!");
                }
            }
            // Process the form if not cancelled.
            elseif($this->_form_type == 'create' || $this->_form_type == 'edit')
            {
                $custom_validation = (isset($options['validation']) ? $options['validation'] : NULL);
                if($form->validate($custom_validation) == FALSE)
                {
                    $form->addPreTableHTML(cbeads_error_message('Errors:<br>'. $this->form_errors_to_string($form->errorMessages)));
                    $submit_error = true;
                }
                else
                {
                     // Create/update object as long as the controller does not have create or update set to false.
                    $do_create_update = FALSE;
                    $controller = NULL;
                    if(isset($options['controllers']))
                    {
                        if(isset($options['controllers'][$this->_form_type])) $controller = $options['controllers'][$this->_form_type];
                    }
                    if($controller == NULL || !empty($controller[$this->_form_type])) $do_create_update = TRUE;
                    $str = $cur_ui_data['model'];
                    $obj = NULL;
                    if(!empty($str) && $do_create_update)
                    {
                        $obj = ($this->_form_type == 'create') ? new $str() : Doctrine::getTable($str)->find($this->_object_id);
                        // If this is a new composite object, need to assign the parent id that it is associated with.
                        if($this->_ui_level > 0 && $this->_form_type == 'create')
                        {
                            foreach($data['columns'] as $name => $opts)  // Find the foreign key field to use
                            {
                                if(isset($opts['foreign_class']) && $opts['foreign_class'] == $ui_data[$this->_top_ui_id][$this->_ui_level - 1]['model'])
                                {
                                    $tmp_name = $opts['column_name'];
                                    $obj->$tmp_name = $ui_data[$this->_top_ui_id][$this->_ui_level - 1]['obj_id'];
                                    if(isset($_POST[$tmp_name])) unset($_POST[$tmp_name]);  // Remove field storing related object to prevent the client changing the parent id. 
                                }
                            }
                        }
                        // Process only fields that are actual columns in this table
                        foreach($_POST as $field => $val)
                        {
                            if(isset($data['columns'][$field]))
                            {
                                $col_opts = $data['columns'][$field];
                                if(isset($col_opts['related']))  // Foreign key
                                {
                                    if($val == '')   // No value for this foreign key. Need to set it to NULL
                                        $obj->$field = NULL;
                                    else
                                    {
                                        // Check if a combobox was used. A combobox that allows custom values means that
                                        // a new related object may have to be created by using the provided callback. If 
                                        // the value starts with '_[[new]]_' this indicates a user supplied value.
                                        if(isset($options['fields'][$field]))
                                        {
                                            $tmp = $options['fields'][$field];
                                            if(isset($tmp['input_type']) && $tmp['input_type'] == 'combobox' &&
                                                isset($tmp['object_creation_callback']))
                                            {
                                                if(!is_callable($tmp['object_creation_callback']))
                                                    echo cbeads_error_message("Unable to call object creation callback for field: $field");
                                                else
                                                {
                                                    if(substr($val, 0, 9) == "_[[new]]_")
                                                    {
                                                        $result = call_user_func($tmp['object_creation_callback'], substr($val, 9));
                                                        if($result['success'] == FALSE)
                                                            $form->addPreTableHTML(cbeads_error_message($result['msg']));
                                                        else
                                                            $val = $result['id'];
                                                    }
                                                }
                                            }
                                        }
                                        $obj->$field = $val;
                                    }
                                }
                                else
                                {
                                    //if($val == '')
                                    //{
                                    //  if(isset($_FILES[$field]) && !empty($_FILES[$field]['name'])) $val = $_FILES[$field]['name'];
                                    //
                                    //}
									$tmp_opts = array();
									if(isset($options['fields'][$field]) && is_array($options['fields'][$field]))
										$tmp_opts = $options['fields'][$field];
                                    $tmp_opts['required'] = FALSE;
                                    if(isset($data['columns'][$field]['required']))
                                        $tmp_opts['required'] = $data['columns'][$field]['required'];
										//echo cbeads_nice_vardump(array_keys($tmp_opts));
                                    $def = $this->_find_attribute_definition_for_field($field);
                                    $tmp_opts['attribute_type'] = ($def !== NULL) ? $def['name'] : NULL;
                                    $this->_process_form_input_value($obj, $field, $val, $tmp_opts);
                                }
                            }
                        }
                        
                        // Do a validation check on the applied values. If Doctrine has issues with 
                        // any values then report those issues.
                        if(!$obj->isValid())
                        {
                            $errorMessages = "";
                            $errors_ = $obj->getErrorStack();
                            foreach($errors_ as $fieldName => $errorCodes)
                            {
                                $errorMessages .= '<br>Field \'' . cbeads_make_title_text($fieldName) . '\': ';
                                for($i = 0; $i < count($errorCodes); $i++)
                                {
                                    if($errorCodes[$i] == "type") $errorMessages .= "type does not match";
                                    elseif($errorCodes[$i] == "length") $errorMessages .= "length does not match";
                                    elseif($errorCodes[$i] == "constraint") $errorMessages .= "constraint does not match";
                                    elseif($errorCodes[$i] == "unique") $errorMessages .= "the value is not unique";
                                    elseif($errorCodes[$i] == "notnull") $errorMessages .= "must provide a value";
                                    else $errorMessages .= $errorCodes[$i];
                                    if($i+1 < count($errorCodes)) $errorMessages .= ', ';
                                }
                            }
                            $form->addPreTableHTML(cbeads_error_message('Errors:'. $errorMessages));
                            $submit_error = true;
                            $form->setReferenceValues($_POST);      // Reapply values entered into the form.
                        }
                        else
                        {
                            // Process file uploads, unless requested otherwise.
							$process_uploads = TRUE;
							if(isset($controller['process_uploads']) && $controller['process_uploads'] != FALSE)
								$process_uploads = FALSE;
							if($process_uploads)
								$submit_error = !$this->_process_file_uploads($form, $obj, $data);
                        }
                        
                        
                        if($submit_error == FALSE)
                        {
                            $obj->save();
                        
                            // Check for any relationship of type MANY-MANY or ONE-MANY that are
                            // not composite. Need to link/unlink foreign objects to this object
                            // as needed. Note: the POST array may not contain any values for a 
                            // relationship even though that relationship field was provided in the
                            // form. That happens when no elements have been selected. As a result
                            // all existing related objects need to be removed for that relationship.
                            $do_save = FALSE;
                            $saved_form_elements = $form->get_all_elements_for_rendered_form($form_id);
                            //nice_vardump($saved_form_elements);
                            foreach($data['relationships'] as $relationship => $reldata)
                            {
                                $new_relation_ids = isset($_POST[$relationship]) ? $_POST[$relationship] : NULL;
                                if($new_relation_ids !== NULL && !is_array($new_relation_ids)) $new_relation_ids = array($new_relation_ids);
                                //nice_vardump($new_relation_ids);
                                //nice_vardump($reldata);
                                if(empty($reldata['composite']) && !empty($new_relation_ids))
                                {
                                    // Make an array of object ids to add the relationship to and
                                    // an array of ids to remove the relationship from.
                                    $add = array_diff($new_relation_ids, $reldata['selected']);
                                    $remove = array_diff($reldata['selected'], $new_relation_ids);
                                    if($reldata['many_to_many'])
                                    {
                                        $mapping_table = $reldata['mapping_table'];
                                        foreach($add as $id)
                                        {
                                            $newmaps = new $mapping_table();
                                            $newmaps[$reldata['local_key']] = $obj->id;
                                            $newmaps[$reldata['foreign_key']] = $id;
                                            $newmaps->save();
                                        }
//                                             if(!empty($add)) 
//                                             {
//                                                 $obj->refreshRelated(); // Need to refresh relations list because object does not know about the new mappings.
//                                             }
                                        if(!empty($remove))
                                        {
                                            $obj->unlink($relationship, $remove);
                                            $do_save = TRUE;
                                        }
                                        //echo 'many to many relationship updated.';
                                    }
                                    else
                                    {
                                        $obj->link($relationship, $add);
                                        if(!empty($remove)) $obj->unlink($relationship, $remove); // If array were empty all relations would be removed!
                                        $obj->$relationship->takeSnapshot();
                                        if(!empty($add) || !empty($remove)) $do_save = TRUE;
                                    }
                                    
                                    //echo "Object now has " . $obj->$relationship->count(). " object(s) related to it.";
                                }
                                elseif(empty($reldata['composite'])) // Ignore composite relationships
                                {
                                    foreach($saved_form_elements as $element)
                                    {
                                        if($element->attributes['name'] == $relationship ||
                                            $element->attributes['name'] == $relationship . '[]')
                                        {
                                            if(isset($reldata['foreign_class']) && $relationship == $reldata['foreign_class']) break; 
                                            //echo "there are no more objects for the relationship: ".$relationship.'<br>';
                                            $obj->unlink($relationship);
                                            $obj->$relationship->takeSnapshot();
                                            $do_save = TRUE;
                                            break;
                                        }   
                                    }
                                }
                            }
                            if($do_save) $obj->save();
                            $obj->refreshRelated();         // Make sure all related objects are up-to-date.

                            if($this->_form_type == 'create')
                            {
                                if(!isset($options['create_success_message']))
								{
									echo cbeads_success_message(cbeads_make_title_text(cbeads_remove_namespace_component($str)) .' was created!');
								}
                                else
								{
									if($options['create_success_message'] !== FALSE)
										echo cbeads_success_message($options['create_success_message']);
								}
                            }
                            else
                            {
                                if(!isset($options['update_success_message']))
								{
									echo cbeads_success_message(cbeads_make_title_text(cbeads_remove_namespace_component($str)) .' was updated');
								}
                                else
								{
									if($options['update_success_message'] !== FALSE)
										echo cbeads_success_message($options['update_success_message']);
								}
                            }
                        }
                    }
                    
                    // If a callback was specified for create or edit then call it.
                    if($submit_error === FALSE && $controller !== NULL && isset($controller['callback']))
                    {
                        if(is_callable($controller['callback']))
                        {
                            call_user_func($controller['callback'], ($do_create_update ? $obj : NULL));
                        }
                        else
                        {
                            print cbeads_error_message("The controller callback provided cannot be called!");
                        }
                    }
                    if(!empty($controller['hide_form'])) $hide_form = TRUE;
                    
                    // If this form is a sub form, then go back to the parent form by default
                    if($submit_error === FALSE && $this->_ui_level > 0)
                    {
                        $prv_level = true;
                    }
                    
                    unset($obj, $do_create_update, $controller);
                }
            }
            elseif($this->_form_type == 'view')
            {
                // DO ANYTHING?
            }
            elseif($this->_form_type == 'delete')
            {
                $controller = NULL;

                if(isset($options['controllers']))
                {
                    if(isset($options['controllers'][$submit_action])) $controller = $options['controllers'][$submit_action];
                }       
                
                // Delete the object unless the controller says not to.
                $do_delete = ($controller == NULL || !empty($controller['delete'])) ? TRUE : FALSE;
                $str = $cur_ui_data['model'];
                //if(!empty($str))
                //{
                //print '<pre>'; print_r($cur_ui_data); print_r($_POST); print '</pre>';
                    $obj = Doctrine::getTable($str)->find($this->_object_id);
                    if($obj === false)
                        echo cbeads_error_message('Could not find object to delete');
                    elseif($do_delete)
                    {
                        foreach(array_keys($data['columns']) as $field)
                        {
                            if($this->_is_publicfile_column(array_keys($data['columns']), $field))
                            {
                                $this->_delete_file_for_field($field, $obj, $data);
                            }
                        }
                        $obj->delete();
						if(!isset($options['delete_success_message']))
						{
							echo cbeads_success_message(cbeads_make_title_text(cbeads_remove_namespace_component($str)) . ' was deleted');
						}
						else
						{
							if($options['delete_success_message'] !== FALSE)
								echo cbeads_success_message($options['delete_success_message']);
						}
                    }
                //}
                // If a callback was specified then call it.
                if($controller !== NULL && isset($controller['callback']))
                {
                    if(is_callable($controller['callback']))
                    {
                        call_user_func($controller['callback'], ($do_delete ? NULL : $obj));
                    }
                    else
                    {
                        print cbeads_error_message("Controller callback provided cannot be called!");
                    }
                }
                if(!empty($controller['hide_form'])) $hide_form = TRUE;
                
                if($this->_ui_level > 0) $prv_level = true;
            }

            // When a form is successfully submitted then remove it from the session.
            if(!$submit_error)
            {
                $this->_remove_form($form_id);
                // For forms that are on the top level and not called via render_as_table or when
                // the hide_form option is set, return to the calling function here, else the form
                // will be generated again (and saved to the session).
                if($this->_ui_level == 0 && ($this->_rendering_table || $hide_form))
                {
                    // Must remove all composite forms (instances and ui data) if there are any.
                    $this->_remove_composite_forms($ui_data);
                    return array('success' => TRUE, 'submit_error' => FALSE, 'submitted' => TRUE);
                }
            }
            
        }
        
        
        
        if($prv_level)
        {
            // echo "go back to this parent form<br>";
                                
            // Get the parent form info.
            $this->_ui_level--;
            $ui_data = $this->_CI->session->userdata('ui_data');
            $cur_ui_data = $ui_data[$this->_top_ui_id][$this->_ui_level];
            $this->_object_id = $cur_ui_data['obj_id'];
            $this->_form_type = $cur_ui_data['action'];
            $str = $cur_ui_data['model'];
            $obj = new $str();
            $data = NULL;
            $data = $obj->select($this->_object_id);
            //print '<pre>'; print_r($data); print '</pre>';
            $form_id = $cur_ui_data['form_id'];
            
            // Remove all ui levels (and associated data) higher than the current one.
            $formclass_instances = $this->_CI->session->userdata('formclass_instances');
            for($i = count($ui_data[$this->_top_ui_id]) - 1; $i > -1; $i--)
            {
                if($i > $this->_ui_level) 
                {
                    unset($formclass_instances[$ui_data[$this->_top_ui_id][$i]['form_id']]);
                    unset($ui_data[$this->_top_ui_id][$i]);
                }
            }
            $this->_CI->session->set_userdata('formclass_instances', $formclass_instances);
            $this->_CI->session->set_userdata('ui_data', $ui_data);
            // print 'New UI data: <pre>'; print_r($ui_data[$top_ui_id]); print '</pre>';
            
            // Since the form in use has changed, need to again retrieve the options for the current form.
            $options = $this->get_correct_form_options($org_options, $cur_ui_data, $ui_data, $data);
			$this->construct_controllers_for_form($options);
            
            // Create a new form instance using the form id of the parent form.
            $form = new form_builder($form_id);
            $form->setAttributes(array(
                "tableAttributes" => array("width" => (!empty($options['width']) ? $options['width'] : "100%")),
                "action" => site_url($submit_url),
                "enableSessionAutoFill" => 1,
                "map" => (!empty($options['layout'])) ? $options['layout'] : NULL,
                'form_js' => (!empty($options['javascript'])) ? $options['javascript'] : NULL,
                'pre_init_js' => (!empty($options['pre_init_javascript'])) ? $options['pre_init_javascript'] : NULL,
                'post_init_js' => (!empty($options['post_init_javascript'])) ? $options['post_init_javascript'] : NULL,
                'jqueryDateFormat' => $this->convert_php_date_format_to_jquery_date_format($this->_date_format),
				'timeFormat' => $this->_user_time_format,
                'using_js' => TRUE
            ));
        }
        
        // Merge model data with the form options specified. Form options 
        // override what is defined in the model data.
        $this->_merge_data_options($options, $data);
        
        // Need to get the proper table options for any composite tables that the form may be displaying.
        $comp_tables = NULL;
        if(isset($org_options['composite_tables'])) $comp_tables = $org_options['composite_tables'];
        if($comp_tables)
        {
            foreach($options['fields'] as $field => $opts)
            {
                if(!empty($opts['composite']))  // Is a composite object field
                {
                    $tmpname = $opts['model_data']['classname'];
                    if(isset($comp_tables[$tmpname]))   // Any definition provided?
                    {
                        foreach($comp_tables[$tmpname] as $key => $val)  // Add definition elements to the field options.
                        {
                            $options['fields'][$field][$key] = $val;
                        }
                    }
                }
            }
        }
       
        $this->build_form($form, $options, $data, $ui_data, $cur_ui_data);
       

        // If output in the options was set to true, then capture the produced html and
        // return it. If output is not set then render will just print the form here.
        if(!empty($options['output']) && ($options['output']))
        {
            $result = array('success' => TRUE, 'output_html' => $form->render(TRUE), 'submitted' => $form_submit, 'submit_error' => $submit_error);
        }
        else
        {
            $form->render();
            $result = array('success' => TRUE, 'submitted' => $form_submit, 'submit_error' => $submit_error);
        }
        // To avoid problems with the session size getting to big, remove forms no longer needed.
        $this->_clear_old_form_instances($ui_data);
        // Can now display anything that has been outputted.
        ob_end_flush();
		
		$this->_rendered_form = TRUE;	// 2011/09/01
		
        return $result;
    }
	
	// Gets a id to use with the form_builder class. The id can either be a new one or an existing one depending on
	// wether a a top_ui_id exists or not. The function also looks at any composite form action to see if the form 
	// to use has to be changed. Various variables have to be passed by reference as they may be changed.
	// ui_data: reference which contains all the UI data that is stored in the user's session.
	// cur_ui_data: reference storing the UI data for the current UI group.
	// form_id: reference to a variable to hold the form id to use when instantiating the form-builder class.
	// data: reference to the model data (supplied to render_as_form). This is updated when composite objects are worked with.
	// composite_model: the composite model currently active.
	// composite_action: the composite action peformed for the current form.
	// composite_id: for composite forms, this is the id of the child object being edited.
	// change_ui_level: the ui level to switch too.
	// form_submit: bool, indicates if the form was submitted.
	private function get_form_id(&$ui_data, &$cur_ui_data, &$form_id, &$data, $composite_model, $composite_action, $composite_id, $change_ui_level, $form_submit)
	{
		$accept_actions = array('create'=>1, 'edit'=>1, 'delete'=>1, 'copy'=>1, 'view'=>1); // Array of actions render as form will accept
		if($this->_top_ui_id !== NULL && $this->_ui_level !== NULL)
        {
            if(isset($ui_data[$this->_top_ui_id])) // Does this top_ui_id exist?
            {
                //print 'Original UI data: <pre>'; print_r($ui_data[$top_ui_id]); print '</pre>';
                if(isset($ui_data[$this->_top_ui_id][$this->_ui_level]))  // Does this ui_level exist?
                {
                    // When a composite action is performed or the ui level was changed then save all field-value pairs 
                    // to the ui data. This will then be used to set the field values if the user returns to this form.
                    if($composite_action || $change_ui_level !== NULL)
                    {
                        $ui_data[$this->_top_ui_id][(int)$_POST['_ui_level']]['saved_values'] = array();
                        foreach($_POST as $key => $val)
                            $ui_data[$this->_top_ui_id][(int)$_POST['_ui_level']]['saved_values'][$key] = $val;
                        //echo '<pre>'; print_r($ui_data[$top_ui_id][$_POST['_ui_level']]['saved_values']); echo '</pre>';
                        $this->_CI->session->set_userdata('ui_data', $ui_data);
                    }
                
                    // If a composite action was performed and is accepted then a new child form is generated. That
                    // means updating variables and saving the new form data to the user's session.
                    if($form_submit && isset($accept_actions[$composite_action]))
                    {
                        //$parent_id = $object_id;
                        $this->_object_id = $composite_id;
                        //print "Object id is $object_id and composite action is: $composite_action";
                        $obj = ($composite_action == 'create') ? new $composite_model() : Doctrine::getTable($composite_model)->find($this->_object_id);
                        $data = $obj->select($this->_object_id);
                        $this->_form_type = $composite_action;
                        // If the user has used the breadcrumb to move to a previous form and in that form a composite 
                        // action occurred then need to remove ui data of all subforms from that point on. Otherwise
                        // form data is just added to the list.
                        $formclass_instances = $this->_CI->session->userdata('formclass_instances');
                        for($i = count($ui_data[$this->_top_ui_id]) - 1; $i > -1; $i--)
                        {
                            if($i > $this->_ui_level) 
                            {
                                unset($formclass_instances[$ui_data[$this->_top_ui_id][$i]['form_id']]);
                                unset($ui_data[$this->_top_ui_id][$i]);
                            }
                        }
                        $this->_CI->session->set_userdata('formclass_instances', $formclass_instances);
                        
                         // Add to ui_data array in the user's session.
                        $form_id = time();
                        $cur_ui_data = array('ui_type' => 'form', 'action' => $this->_form_type, 'model' => $composite_model, 'obj_id' => $this->_object_id, 'form_id' => $form_id);
                        $ui_data[$this->_top_ui_id][$this->_ui_level + 1] = $cur_ui_data;
                        $this->_CI->session->set_userdata('ui_data', $ui_data);
                        
                        $this->_ui_level++;
                    }
                    else    // Get the saved data for this current ui level
                    {
                        $form_id = $ui_data[$this->_top_ui_id][$this->_ui_level]['form_id'];
                        $cur_ui_data = $ui_data[$this->_top_ui_id][$this->_ui_level];
                        $this->_object_id = $cur_ui_data['obj_id'];
                        $modelname = $cur_ui_data['model'];
                        $this->_form_type = $cur_ui_data['action'];
                        // Gets model data for this form if not supplied or if the form is a child form.
                        if(!isset($data) || $data['classname'] != $modelname)
                        {
                            $obj = ($cur_ui_data['action'] == 'create') ? new $modelname() : Doctrine::getTable($modelname)->find($this->_object_id);
                            $data = $obj->select($this->_object_id);
                        }
						//echo "Existing form with: '" . $this->_top_ui_id . "' and '$form_id'";
                    }
                }
                else
                    return 'Invalid ui data level ('.$this->_ui_level.') specified!';
            }
            else
                return 'No form data exists for this user interface ID!';
        }
        else    // A new top level form is being created.
        {
            // Generate unique top_ui_id value
            $now = time();
            $this->_top_ui_id = $now;
            $form_id = $now;
            $this->_ui_level = 0;
            // echo "New form with: '" . $this->_top_ui_id . "' and '$form_id'<br>";
            // Add to ui_data array in the user's session.
            if(empty($ui_data)) $ui_data = array();
            $ui_data[$this->_top_ui_id] = array();
            $cur_ui_data = array('ui_type' => 'form', 'action' => $this->_form_type, 'model' => (!empty($data)) ? $data['classname'] :  '', 'obj_id' => $this->_object_id, 'form_id' => $form_id);
            $ui_data[$this->_top_ui_id][] = $cur_ui_data;
            $this->_CI->session->set_userdata('ui_data', $ui_data);
        }
		return TRUE;
	}
	
	
	// Merges the controllers provided in the options with the default controllers for the 
	// current form type.
	// options: reference to the options array. The controllers element will be updated.
	private function construct_controllers_for_form(&$options)
	{
		// Generate the default controllers based on form type.
		if($this->_form_type == 'create') 
			$controllers = array(
				'create' => array('label' => 'Save', 'create' => TRUE),
				'cancel' => array('label' => 'Cancel')
			);
		elseif($this->_form_type == 'edit') 
			$controllers = array(
				'edit' => array('label' => 'Update', 'edit' => TRUE),
				'cancel' => array('label' => 'Cancel')
			);
		elseif($this->_form_type == 'view') 
			$controllers = array(
				'cancel' => array('label' => 'Back')
			);
		elseif($this->_form_type == 'delete')
			$controllers = array(
				'delete' => array('label' => 'Delete', 'delete' => TRUE),
				'cancel' => array('label' => 'Cancel')
			);
        //}
		// Merge the default controller options with the ones provided (if any).
		if(isset($options['controllers']) && is_array($options['controllers']))
		{
			foreach($options['controllers'] as $controller => $opts)
			{
				if(!isset($opts[$this->_form_type])) $opts[$this->_form_type] = TRUE;
				$controllers[$controller] = $opts;
			}
		}
        
		$options['controllers'] = $controllers;
		//nice_vardump($options['controllers']);
	}
    
    // Returns the form options of the currently used form.
    // options: the options received by render_as_form().
    // cur_ui_data: the current ui data
    // ui_data: all the ui data
    // data: the model data for the current model that the form is being generated.
    // Returns the form options for the current form.
    private function get_correct_form_options($options, $cur_ui_data, $ui_data, $data)
    {        
        if($this->_ui_level == 0) return $options;  // No composite form in use. Can just use options array as is.
        
        $comp_tables = NULL;
        if(isset($options['composite_tables'])) $comp_tables = $options['composite_tables'];

        //nice_vardump($options);
        //echo "modelname is: ".$cur_ui_data['model']."<br>";
        $form_options = array();
        if(isset($comp_tables[$cur_ui_data['model']]['form_options'])) 
        {
            $table_options = $comp_tables[$cur_ui_data['model']];
            $action = $this->_form_type;
            $default_form_options = NULL;
            // See if the form wants to dynamically set up the default form options.
            if(isset($table_options['form_options']['default']) && is_array($table_options['form_options']['default']))
            {
                $default_form_options = $table_options['form_options']['default'];
                if(isset($table_options['form_options']['default']['dynamic_setup']) &&
                   is_callable($table_options['form_options']['default']['dynamic_setup']))
                {
                    $default_form_options = call_user_func($table_options['form_options']['default']['dynamic_setup'], 
                                            array('object_id' => $this->_object_id, 
                                                  'parent_id' => $ui_data[$this->_top_ui_id][$this->_ui_level - 1]['obj_id'])
                    );
                }
            }
            // Check if dynamic form setup is requested for the current form action type.
            if(isset($table_options['form_options'][$action]))
            {
                if(isset($table_options['form_options'][$action]['dynamic_setup']))
                {
                    if(is_callable($table_options['form_options'][$action]['dynamic_setup']))
                    {
                        $form_options = call_user_func($table_options['form_options'][$action]['dynamic_setup'], 
                                            array('object_id' => $this->_object_id,
                                            'parent_id' => $ui_data[$this->_top_ui_id][$this->_ui_level - 1]['obj_id'])
                        );
                    }
                    else
                    {
                        print cbeads_error_message("No valid callback function specified for dynamic form rendering");
                    }
                }
                else
                {
                    $form_options = $table_options['form_options'][$action];
                }
                if($default_form_options)
                    $form_options = $this->_inherit_default_form_options($default_form_options, $form_options);
            }
            else
            {
                // Use default form options if provided.
                if($default_form_options)
                    $form_options = $default_form_options;
            }
            $form_options = $this->_inherit_table_options($table_options, $form_options);
        }
        
        $form_options['output'] = TRUE;

        // Supply the parent object for the composite form by adding it to the model data. This way,
        // if the form options indicate the parent relationship should be shown, then the form can
        // show the parent object (stringified).
        foreach($data['columns'] as $name => $opts)
        {
            if(isset($opts['foreign_class']) && $opts['foreign_class'] == $ui_data[$this->_top_ui_id][$this->_ui_level - 1]['model'])
            {
                $data['columns'][$name]['value'] = $ui_data[$this->_top_ui_id][$this->_ui_level - 1]['obj_id'];
                if(!isset($form_options['fields'])) $form_options['fields'] = array();
                if(!isset($form_options['fields'][$name])) $form_options['fields'][$name] = array();
                if(!isset($form_options['fields'][$name]['static'])) $form_options['fields'][$name]['static'] = TRUE;
            }
        }
    
        return $form_options;
    }
    
    
    // Constructs a form, by adding elements to a form object.
    // form: reference to the form object to use for building the form.
    // options: the form options supplied to render_as_form.
    // data: the model data provided when working with an existing object.
    // ui_data: the user interface data associated with the user.
    // cur_ui_data: the current user interface data that is relevent to this form.
    private function build_form(&$form, $options, $data, $ui_data, $cur_ui_data)
    {
        //$form->addHTML(_get_default_form_css());
        
        // Generate the breadcrumb html if viewing a sub form.
        if(count($ui_data[$this->_top_ui_id]) > 1)
        {
            $bc_html = '<div class="renderer_form_breadcrumb">';
            //print'<pre>'; print_r($ui_data[$top_ui_id]); print '</pre>';
            for($i = 0; $i < count($ui_data[$this->_top_ui_id]); $i++)
            {
                $tmp_ui_data = $ui_data[$this->_top_ui_id][$i];
                $parts = explode("\\", $tmp_ui_data['model']);
                $model_name = (count($parts) == 2) ? $parts[1] : $parts[0];
                $class = ($i == $this->_ui_level) ? "renderer_form_active_breadcrumb" : "";
                $bc_html .= '<input type="submit" class="'.$class.'" name="_change_ui_level-'.$i.'" value="'.ucfirst($tmp_ui_data['action']). ': '. cbeads_make_title_text($model_name).'" onclick="skip_onsubmit=true;" />';
                //echo "level $i ";
            }
            $bc_html .= '</div>';
            $form->addPreTableHTML($bc_html);
        }
        
        // Add form decription if there is any.
        if(isset($options['description']))
            $form->addPreTableHTML('<h5>'.$options['description'].'</h5>' );

        
        // Add various hidden fields to handle returned forms.
        $form->addHidden('_form_submit', 1);     // This will be used to test if a form was actually submitted.
        $form->addHidden('_top_ui_id', $this->_top_ui_id); // Unique identifier to get the correct form data from the ui_data session variable
        $form->addHidden('_ui_level', $this->_ui_level);   // Identifies the UI level (to access the correct element in the array associated with top_ui_id)

        // Check the model for any primary columns. These columns will have their
        // value put into a hidden field, except for create forms.
        // In addition, for create forms the primary columns are not displayed,
        // nor are any columns that are autoincrement. Also, if the ui data has
        // field values saved then apply them.
        if(!empty($data))
        {
            foreach($data['columns'] as $name => $attr)
            {
                $field = $options['fields'][$name];
                
                if($this->_form_type == 'create')
                {
                    if(!empty($attr['primary']) || !empty($attr['autoincrement'])) 
                    {
                        $key = array_keys($options['order'], $name);
                        if(!empty($key)) unset($options['order'][$key[0]]);
                        unset($options['fields'][$name]);
                    }
                }
                else
                {
                    // Make primary fields static unless the static attribute has already been defined in the field's options.
                    if(!empty($attr['primary']) && !isset($field['static']))
                    {
                        $field['static'] = 1;
                        $options['fields'][$name] = $field;
                    }
                    if(!empty($field['primary']))
                        $form->addHidden('_'.$name, $attr['value']);
                }
                
                if(isset($cur_ui_data['saved_values']) && isset($cur_ui_data['saved_values'][$name]))       // Apply saved form values.
                {
                    $options['fields'][$name]['value'] = $cur_ui_data['saved_values'][$name];
                }
            }
        }

        // If the field order is defined then loop through it and see if there is 
        // a corresponding definition for that field in the fields array.
        if(isset($options['order']))
        {
            $fields = $options['fields'];
            foreach($options['order'] as $fieldname)
            {
                if($this->_is_privatefile_column($options['order'], $fieldname)) continue;
                if(isset($fields) && isset($fields[$fieldname]))    // If there is a field definition, pass it too.
                    $this->_add_field($form, $fieldname, $fields[$fieldname]);
                else
                    $this->_add_field($form, $fieldname);
            }
            
        }
        // With no order, just use the order found in the fields array.
        elseif(isset($options['fields']))
        {
            foreach($options['fields'] as $name => $properties)
            {
                if($this->_is_privatefile_column($options['order'], $fieldname)) continue;
                $this->_add_field($form, $name, $properties);
            }
        }

        $form->addHTML('<br><br>');
        
        // Construct the order array if it hasn't been defined and then add the controller buttons.
        if(!isset($options['controller_order']) || count($options['controller_order']) == 0)
            $options['controller_order'] = array_keys($options['controllers']);
        foreach($options['controller_order'] as $item)
        {
            if(isset($options['controllers'][$item]))
            {
                $opts = $options['controllers'][$item];
                $action = NULL;
                $buttonType = (strtolower($item) == 'reset') ? 'reset' : 'submit';
                $label = (isset($opts['label']) && !empty($opts['label'])) ? $opts['label'] : ucfirst($item);
                $opts['name'] = '_form_action_' . strtolower($item);
                if(isset($opts['action']) && !empty($opts['action']))
                    $action = $opts['action'];
                $this->_add_button($form, $label, $action, $buttonType, $opts);
            }
        }

    }



    // Private function. This function adds a field to the form. 
    // form: the form object to add the field to (as a reference).
    // name: the field name. This will be used to get the render definition.
    // propeties: the properties to use for this field. Will override values specified in the render definition.
    // Returns nothing.
    private function _add_field(&$form, $name, $properties = array())
    {
        $render_options = array();          // The options to pass to the various addControl functions.
        $is_relationship = FALSE;           // Indicates if this field is for a relationship.
        $output_type = 'text';              // Stores the output render type associated (or specified) for this field.
        
        $render_options = $this->_get_merged_field_render_options($name, $properties);
		$is_input = TRUE;					// Indicates if the field is designed for input or output.
        
        // Relationships have special 'control' types.
        if(!empty($properties['is_relationship']))
        {
            // Composite relationships show a table for managing the composite objects (in 
            $type = (isset($render_options['composite']) && $render_options['composite']) ? 'composite_relationship' : 'associative_relationship';
        }
        else
        {
            // $type = ($this->_form_type == 'view' || $this->_form_type == 'delete' || (!empty($render_options['static'])) ) ? 
                        // $render_options['output_type'] : $render_options['input_type'];
			if($this->_form_type == 'view' || $this->_form_type == 'delete' || (!empty($render_options['static'])) )
			{
				$type = $render_options['output_type'];
				$is_input = FALSE;
			}
			else
			{
				$type = $render_options['input_type'];
			}
            $output_type = $render_options['output_type'];
        }

        $label = (isset($render_options['label'])) ? cbeads_make_title_text($render_options['label']) : cbeads_make_title_text($name);
        $value = (isset($render_options['value'])) ? $render_options['value'] : "";
        $value = $this->format_value_for_output($value, isset($render_options['input_type']) ? $render_options['input_type'] : '');
        
        // Need to make sure the 'validate' value is a regex string.
        if(isset($render_options['validate']) && $render_options['validate'] !== NULL)
        {
            //echo "Validate type(".$render_options['validate'].") converted to: ";
            $render_options['validate'] = $this->_make_regex($render_options['validate']);
            //echo $render_options['validate'] . "\n";
        }
        
        // When a comment is provided, change the key to 'tooltip' so the formbuilder can use it.
        if(isset($render_options['comment']))
        {
            $render_options['tooltip'] = $render_options['comment'];
            unset($render_options['comment']);
        }
        
        // TODO: Clean up the remaining options by removing elements that are not needed.
        //nice_vardump($render_options);
        $opts = $render_options;
        unset($opts['input_type']);
        unset($opts['output_type']);
        unset($opts['value']);
        
    //echo "Control Type is: $type";
		if($is_input)
		{
			switch(strtolower($type))
			{
				case "textbox":
					if(!empty($opts['width'])) $opts['size'] = $opts['width'];
					$form->addTextbox($label, $name, $value, $opts);
					break;
				case "textarea":
					if(!empty($opts['width'])) $opts['cols'] = $opts['width'];
					if(!empty($opts['height'])) $opts['rows'] = $opts['height'];
					$form->addTextarea($label, $name, $value, $opts);
					break;
				case "map":
					$form->addMap($label, $name, $value, $opts);
					break;
				case "date":
					if(!empty($opts['width'])) $opts['cols'] = $opts['width'];
					$form->addDate($label, $name, $value, $opts);
					break;
				case "file":
					$html = "";
					if($this->_form_type == 'edit')
					{
						if($value != "") 
						{
							$html = '<span id="'.$name.'_actions_container">';
							$html .= $this->format_as_file_download(array('name' => $name, 'value' => $value));
							$opts['onclick'] = 'return confirm("A file for this field has already been uploaded. If you upload another file the current one will be discarded. Continue?");';
							$opts['id'] = $name."_file_browser";
							if(empty($opts['required']))
							{
								$html .= ' '.$this->_display_file_delete($name, $value);
							}
							else
							{
								// No delete available, but field should not be required. This way if the value is not changed the
								// client side javascript and server won't complain.
								unset($opts['required']);
							}
							$html .= '</span>';
							if($output_type == 'image')
								$html .= "<br><span id='".$name."_image_container'>" . $this->format_as_image(array(
								'name' => $name, 'value' => $value)) . '</span>';
						}
					}
					if(!empty($opts['width'])) $opts['cols'] = $opts['width'];
					$form->addFile($label, $name, $html, $opts);
					break;
				case "ckeditor":
				case "html_editor":     # default editor
					if(!empty($opts['width'])) $opts['cols'] = $opts['width'];
					if(!empty($opts['height'])) $opts['rows'] = $opts['height'];
					$form->addCKEditor($label, $name, $value, $opts);
					break;
				case "tinymce":
					//echo "here";
					if(!empty($opts['width'])) $opts['cols'] = $opts['width'];
					if(!empty($opts['height'])) $opts['rows'] = $opts['height'];
					$form->addWebEditor($label, $name, $value, $opts);
					break;
				case 'select':
					// Select control for selecting one item (unless multiple attribute is provided).
					if(!empty($opts['width'])) $opts['cols'] = $opts['width'];
					if(isset($opts['items']) && is_array($opts['items']) && !empty($opts['items']))
					{
						$items = $opts['items'];
						//print_r($items);
						unset($opts['items']);
						$form->addSelect($label, $name, $value, $items, $opts);
					}
					else
					{
						$form->addHTML('Error: select items were not defined!');
					}
					break;
				case "select_sort":
					// To add: Two lists of items where items can be moved between them and sorted.
					$form->addHTML('to add: Two lists of items where items can be moved between them and sorted.');
					break;
				case "sort_list":
					if(isset($opts['order']) && is_array($opts['order']) && !empty($opts['order']))
					{
						$order = $opts['order'];
						$form->addSort($label, $name, $order, $opts);
					}
					else
					{
						$form->addHTML('Error: list was not defined!');
					}
					break;
				case 'password':
					if(!empty($opts['width'])) $opts['cols'] = $opts['width'];
					$form->addPassword($label, $name, "", $opts);
					break;
				case 'yesno':
					if($is_input)
						$form->addYesNo($label, $name, $value, $opts);
					else
						$form->addText($label, $name, $value ? 'Yes' : 'No');
					break;
				
				// Text fields are just text. There is nothing for the user to input.
				case 'text':
					$value = $this->format_value_for_output($value, $render_options['input_type']);
					$form->addText($label, $name, $this->format_as_text(array('value' => $value)));
					break;

				case 'composite_relationship':
					$this->_add_composite_relationship($form, $label, $name, $render_options);
					break;
				case 'associative_relationship':
					if($this->_form_type == 'view' || $this->_form_type == 'delete') $render_options['static'] = TRUE;
					// Use array of items or value if provided in the field options.
					if(isset($opts['items']) && is_array($opts['items']))
					{
						if(isset($render_options['objects'])) $render_options['objects'] = $opts['items'];
						if(isset($render_options['related'])) $render_options['related'] = $opts['items'];
					}
					$this->_add_associative_relationship($form, $label, $name, $render_options);
					break;        
				case 'hidden':
					$form->addHidden($name, $value);
					break;
				case 'date_range':
					if(!empty($opts['width'])) $opts['cols'] = $opts['width'];
					$form->addDateRange($label, $name, $value, $opts);
					break;
				case 'country_list':
					if(!empty($opts['width'])) $opts['cols'] = $opts['width'];
					$form->addCountry($label, $name, $value, $opts);
					break;
				case 'truefalse':
					$form->addTrueFalse($label, $name, $value, $opts);
					break;
				case 'radio_buttons':
					if(isset($opts['items']) && is_array($opts['items']) && !empty($opts['items']))
					{
						$items = $opts['items'];
						unset($opts['items']);
						$form->addRadio($label, $name, $value, $items, $opts);
					}
					else
					{
						$form->addHTML('Error: radio items were not defined!');
					}
					break;
				case 'check_boxes':
					if(isset($opts['items']) && is_array($opts['items']) && !empty($opts['items']))
					{
						$items = $opts['items'];
						unset($opts['items']);
						$form->addCheckbox($label, $name, $value, $items, $opts);
					}
					else
					{
						$form->addHTML('Error: checkbox items were not defined!');
					}
					break;
				case 'checksort':
					if(isset($opts['items']) && is_array($opts['items']) && !empty($opts['items']))
					{
						$items = $opts['items'];
						unset($opts['items']);
						$form->addCheckSort($label, $name, $value, $items, $opts);
					}
					else
					{
						$form->addHTML('Error: checksort items were not defined!');
					}
					break;
				case 'captcha':
					$form->addCaptcha($label, $opts);
					break;
				case 'slider':
					$form->addSlider($label, $name, $value, $opts);
					break;
				case 'rating':
					//$form->addRating($label, $name, $value, range(1, 10));
					$form->addHTML('To add: rating');
					break;
				case 'html':
					$form->addHTML($value);
					break;
				case 'colourpicker':
					if(!empty($opts['width'])) $opts['cols'] = $opts['width'];
					$form->addColorPicker($label, $name, $value, $opts);
					break;
				case 'hour_of_day_picker':
					$form->addHourOfDayPicker($label, $name, $value, $opts);
					break;
				case 'button':
					$form->addButton($label, "button", $opts);
					break;
				case 'none';
					$form->addHTML('');
					break;
				// case 'file_download':
					// $form->addText($label, $name, $this->format_as_file_download(array('name' => $name, 'value' => $value)));
					// break;
				// case 'email':
					// $form->addText($label, $name, $this->format_as_email_link(array('label' => $value, 'value' => $value)));
					// break;
				// case 'image':
					// $form->addText($label, $name, $this->format_as_image(array('name' => $name, 'value' => $value)));
					// break;
				// case 'colour_display':
					// $form->addText($label, $name, $this->format_as_colour_and_value(array('value' => $value)));
					// break;
				default:
					$form->addText($label, $name, cbeads_error_message("ERROR: Could not match display type for this field ($type)"));
			}
		}
		else
		{
			$tmp = strtolower($type);
			if(isset($this->_output_controls[$tmp]))
			{
				if(isset( $this->_output_controls[$tmp]['control']))
					$type = $this->_output_controls[$tmp]['control'];
				if(isset($this->_output_controls[$tmp]['format_function']) && is_callable($this->_output_controls[$tmp]['format_function']))
					$value = call_user_func($this->_output_controls[$tmp]['format_function'], array('value' => $value, 'name' => $name));
			}
			
			//cbeads_nice_vardump($opts);
			switch(strtolower($type))
			{
				case 'text':
					$value = $this->format_value_for_output($value, $render_options['input_type']);
					$form->addText($label, $name, $this->format_as_text(array('value' => $value)));
					break;
				case 'html':
					$form->addHTML($value);
					break;
				case 'none';
					$form->addHTML('');
					break;
				case "map":
					$form->addMap($label, $name, $value, $opts);
					break;
				case 'hidden':
					$form->addHidden($name, $value);
					break;
				default:
					$form->addText($label, $name, cbeads_error_message("ERROR: Could not match display type for this field ($type)"));
			}
		}

    }


    // Private function. This function adds a button to the form.
    // form: the form object to add the button to (as a reference).
    // label: the value of the button.
    // action: a url to go to (onclick='window.location="someurl"'). This is optional.
    // type: the button type. Either 'button', 'submit' or 'reset'
    // options: additional parameters to pass to the addButton function.
    private function _add_button(&$form, $label, $action = NULL, $type = 'button', $options = array())
    {
        // Clean up options
        unset($options['action']);
        unset($options['label']);
        unset($options['callback']);
        
        if($type == 'button')
        {
            if($action != NULL) $options['onclick'] = "window.location.href='". site_url($action) ."';";
            $form->addButton($label, $type, $options);
        }
        else
        {
            if($action != NULL) $options['onclick'] = "window.location.href='". site_url($action) ."';";
            if(strtolower($options['name']) == '_form_action_cancel')
            {
                if(!isset($options['onclick'])) $options['onclick'] = "";
                $options['onclick'] = $options['onclick'] . "\nskip_onsubmit=true;";
            }
            
            $form->addButton($label, $type, $options);
        }
    }


    // Private function. This function will merge model data with
    // provided form options. The form options override values in the
    // model data.
    // options: reference to the array of form options. This may end up being modified
    //          if model data exists. 
    // data: the model data to use for merging with the form options. Can be NULL
    //       if the form is not based on a model.
    private function _merge_data_options(&$options, $data)
    {
        if($data === NULL) return;
        if(!is_array($data) || empty($data)) return;
        
        $columns = $data['columns'];
        $relationships = $data['relationships'];
        
		// When no order is provided in the options, use the columns and relationships from the model as the order.
        if(!isset($options['order']))
        {
            $options['order'] = array_merge(array_keys($columns), array_keys($relationships));
        }
        
        // Generate temp field array from the data->columns/relationships definitions, obeying the field order defined.
        $model_fields = array();
        foreach($columns as $name => $def)
        {
            if(isset($def['related'])) 
            {
                $def['is_relationship'] = TRUE;
                if(!empty($options['composite_parent_id']))
                    $def['value'] = $options['composite_parent_id'];
            }
            $model_fields[$name] = $def;
        }
        foreach($relationships as $name => $rel)
        {
            // Don't include composite relationships when the form type is create!
            if($this->_form_type == 'create' && !empty($rel['composite']))
            {
                $key = array_keys($options['order'], $name);
                if(!empty($key)) unset($options['order'][$key[0]]);
                continue;
            }
            elseif(!empty($rel['composite']))
            {
                if(!empty($options['composite_controllers']) && !empty($options['composite_controllers'][$name]))
                    $rel['controllers'] = $options['composite_controllers'][$name];
            }
            $rel['is_relationship'] = TRUE;
            $model_fields[$name] = $rel;
        }
        
        // Copy field attributes to existing options field array.
        foreach($model_fields as $name => $attributes)
        {
            if(isset($options['fields'][$name]))    // Field exists. Don't want to overwrite existing attributes.
            {
                foreach($attributes as $key => $value)
                {
                    if(!isset($options['fields'][$name][$key]))
                    {
                        $options['fields'][$name][$key] = $value;
                    }
                }
            }
            else    // Field doesn't exist, so copy everything over.
            {
                $options['fields'][$name] = $attributes;
            }
            
        }
        // echo '<pre>'; print_r($options); echo '</pre>';
    }



    // Private function. This function will add an assocative relationship
    // to the form. This means a select box for selecting the related object(s).
    // form: reference to the form object that creates the form.
    // label: the label for the field.
    // name: the name of the field.
    // options: contains the relationship data as well as any form field
    //          properties to customise the select box.
    // Return nothing.
    private function _add_associative_relationship(&$form, $label, $name, $options)
    {
        $multiple = FALSE;
        $element_type = "select";
        $all_objects = array();
        $selected = array();
        
        // echo $name . " ". $label;
        //nice_vardump($options);
        
        if(!isset($options['related']))
        {
            $multiple = TRUE;
            if(isset($options['objects']))
            {
                if(count($options['objects']) < 10)
                {
                    $element_type = "check";
                }
                $all_objects = $options['objects'];
            }
            $selected = (isset($options['selected'])) ? $options['selected'] : array();
            $options['multiple'] = TRUE;
        }
        else
        {
            $all_objects = $options['related'];
            $selected = (isset($options['value'])) ? $options['value'] : array();
        }

        if(count($all_objects) == 0)
        {
            $form->addText($label, $name, 'No related objects available');
            return;
        }

        if(empty($options['static']))
        {
            // remove unneeded elements from the options array.
            unset($options['related']);
            unset($options['value']);
            unset($options['objects']);
            unset($options['selected']);
            unset($options['is_relationship']);
            unset($options['foreign_class']);
            unset($options['static']);
            unset($options['many_to_many']);
            if($multiple)
            {
                // Check the render options for a 'input_type' value as the user can specify how to render these options.
                if(!empty($options['input_type']))  $element_type = $options['input_type'];
                switch($element_type)
                {
                    case "check":
                        $form->addCheckbox($label, $name, $selected, $all_objects, $options);
                        break;
                    default: 
                        // Can't have a '-- select --' option for M-M relationships because that will result in an error on submit.
                        $form->addSelect($label, $name, $selected, $all_objects, $options);
                }
            }
            else
            {
                if(empty($options['required'])) $all_objects = array('' => '-- select --') + $all_objects;
                if(isset($options['input_type']))
                {
                    if($options['input_type'] == 'combobox') $element_type = 'combobox';
                }
                if($element_type == 'combobox')
                    $form->addCombobox($label, $name, $selected, $all_objects, $options);
                else
                    $form->addSelect($label, $name, $selected, $all_objects, $options);
            }
        }
        else
        {
            $html = '';
            if($multiple)
            {
                // When this is a static field, need to show all related objects
                // that are selected.
                foreach($selected as $id)
                {
                    if(isset($all_objects[$id]))
                    {
                        $html .= $all_objects[$id] . '<br>';
                    }
                }
            }
            else
            {
                if(!empty($selected))   // If one of the related items is selected, then its id is stored in the form in a hidden element.
                {
                    if(isset($all_objects[$selected]))
                    {
                        $html .= $all_objects[$selected] . '<br>';
                    }
                    $form->addHidden($name, $selected);
                }
            }
            $form->addText($label, $name, $html);
        }

    }

    // Private function. This function will add a table for managing
    // a composite relationship.
    // form: reference to the form object
    // label: the label to use for the table
    // name: the name of the relationship
    // options: contains the relationship data as well as any table and form
    //          properties to use for this composite object.
    private function _add_composite_relationship(&$form, $label, $name, $options)
    {
        $html = "";
        $options['model'] = $options['model_data']['classname'];
        $options['parent_id'] = $this->_object_id;
        //$options = array('model' => $options['model_data']['classname'], 'parent_id' => $this->_object_id, 'foreign_key' => $options['foreign_key']);
        if($this->_form_type != 'edit')
            $options = array_merge($options, array('create' => FALSE, 'edit' => FALSE, 'view' => FALSE, 'delete' => FALSE));
        $result = $this->_render_as_composite_table($options);
        if(isset($result['success']) && $result['success'])
            $html = $result['output_html'];
        else
            $html = $result['msg'];
        $form->addText($label, $name.'_container', $html);

    }


/////////////////////////////////////// FUNCTIONS RELATED TO TABLE RENDERING //////////////////////////////////////////
    

    // Renders a table for displaying records from a database table. It also allows
    // actions to be performed such as searching the table, sorting columns, and
    // CRUD actions on records plus custom actions on records.
    // options: contains the options used to create the table.
    // Returns an array containing a field 'success' which is either TRUE if all went well
    // or FALSE if something went wrong. On failure the 'msg' field contains an error
    // message. If output was set to 1 in the options then the table html is returned 
    // in the array using the 'output_html' field.
    public function render_as_table($options)
    {
        $this->_rendering_table = TRUE;     // Set flag to indicate that this function has been called. (see 2011/02/03)
    
        // Model name is required!
        if(empty($options) || !is_array($options) || empty($options['model'])) 
            return array('success' => FALSE, 'msg' => "Need to specify the model name in the options array!");
        
        // Variables
        $html = "";                     // Stores the html generated which can be returned to the caller if requested.
        $model = $options['model'];
        
        $model_columns = array();       // Stores columns to display for the model. (format: column_name => array(is_relationship => 0))
        $controllers = array();         // Stores the controllers available (for display in the table).
        $data = array();                // Stores the data for the records that need to be displayed.
        $show_form = TRUE;              // Either a form or a table is rendered depending on the value. TRUE means rendering the form, FALSE renders the table.
        $column_order = array();        // Stores the column order if defined.
        $columns = array();             // Stores column attributes if defined (will be merged with model_columns)
        $allow_search = TRUE;           // Flag indicating if searching is allowed.
        
        $function_url = "";             // Will store the url of the website and function of the current page (eg www.domain.com/index.php/namespace/controller/function)
        
        
        // Construct the function url component (domain and all url segments up to the function segment)
        for($i = 1; $i < 4 && $i < count($this->_uri_segments) + 1; $i++)  // indices start from 1 in the array returned by segment_array
        {
            if($i > 1) $function_url .= '/';
            $function_url .= $this->_uri_segments[$i];
        }
        $function_url = site_url($function_url);
        //print '<pre>';print_r($uri_segments);print'</pre>';
        
        // Construct array of controllers that the table needs to display.
        $this->check_for_table_controllers($controllers, $options);
        
        if(isset($options['search'])) $allow_search = $options['search'];
        if(isset($options['column_order'])) $column_order = $options['column_order'];
        if(isset($options['columns'])) $columns = $options['columns'];
        
        // Check if the url contains any requests for the table, such as sorting, searching, page to use.
        $requests = $this->check_for_table_requests($controllers, $allow_search);
        
        // Need to know if the top most form or a child form (for a composite object) was submitted.
        // When a form was submitted and there was a composite action or a change in ui level, or the
        // form id does not match the top_ui_id then a form needs to be shown.
        $this->check_for_table_form_submit($show_form);
        
        // If an action on a specific record was requested then do it.
        $form_result = array();
        $action = !empty($requests['action']) ? $requests['action'][0] : NULL;
        $obj_id = !empty($requests['action']) ? $requests['action'][1] : NULL;
		$this->process_table_action($show_form, $form_result, $action, $obj_id, $model, $options);

        // If a form needs to be displayed, print or return the output html as required.
        if($show_form)
        {
            $result = array();
            $result['success'] = TRUE;
            if(!empty($options['output']))
                $result['output_html'] = $form_result['output_html'];
            else
                print $form_result['output_html'];
            return $result;
        }
        
        // Get all columns and relationships for the model. Columns that do not appear
        // in the column order array are discarded.
        $table = \Doctrine::getTable($model);
        $relations = $table->getRelations();
        $db_columns = $table->getColumns();
        $non_rel_columns = array();
        $all_columns = array();
        // If the order array is empty then use the columns and relations from the
        // model as the order.
        if(empty($column_order)) $column_order = array_merge(array_keys($db_columns), array_keys($relations));
        
        // Remove all columns that store file data.
        $tmp_keys = array_keys($db_columns);
        for($i = count($db_columns) - 1; $i > -1; $i--)
        {
            if($this->_is_privatefile_column($tmp_keys, $tmp_keys[$i]))
                unset($db_columns[$tmp_keys[$i]]);
        }
        
        foreach($db_columns as $col_name => $opts)
        {
            $all_columns[] = $col_name;
            //if(!in_array($col_name, $column_order)) continue;
            $is_relationship = FALSE;
            $rel_alias = NULL;
            $rel_class = NULL;
            foreach($relations as $name => $relation)   // See if this column is a foreign key column
            {
                if($relation->getType() == Doctrine_Relation::ONE && $relation->getLocal() == $col_name) // Only interested in One-to-One and Many-to-One relations
                {
                    $is_relationship = TRUE;
                    $rel_alias = $relation->getAlias();
                    $rel_class = $relation->getClass();
                    break;
                }
            }
            $model_columns[$col_name] = array('is_relationship' => $is_relationship, 'is_associative' => $is_relationship, 'is_many_to_many' => FALSE, 'rel_alias' => $rel_alias, 'rel_class' => $rel_class);
            if(isset($columns[$col_name]))
                $model_columns[$col_name] = array_merge($columns[$col_name], $model_columns[$col_name]);
            if(!$is_relationship) $non_rel_columns[] = $col_name;
        }

        //nice_vardump($model_columns);
        // Check for relationships where the foreign key is in the foreign table.
        // These will be One to Many or Many to Many relationships. For a relationship 
        // that is already identified by a foreign key column in this model there is no 
        // need to add it.
        foreach($relations as $name => $relation)
        {
            //if(!in_array($name, $column_order)) continue;
            if($relation->getType() == Doctrine_Relation::ONE) continue;  
            $model_columns[$name] = array('is_relationship' => TRUE, 'is_associative' => TRUE, 'is_many_to_many' => TRUE, 'rel_alias' => $relation->getAlias(), 'rel_class' => $relation->getClass());
            if(isset($columns[$name])) $model_columns[$name] = array_merge($columns[$name], $model_columns[$name]);
        }
        //nice_vardump($model_columns);

        // Get the data to display.
        $cur_page = (!empty($requests['page'])) ? $requests['page'] : 1;
        $records_per_page = (!empty($options['records_per_page'])) ? $options['records_per_page'] : 20;
        $searchtext = (isset($requests['search']) && $requests['search'] != "") ? $requests['search'] : NULL;
        $sort_info = NULL;
        if(isset($requests['sort']) && isset($model_columns[$requests['sort'][0]])) // User sort provided (overrides any default sorting specified)
            $sort_info = array($requests['sort'][0] => $requests['sort'][1]);
        else
        {
            if(isset($options['default_sorting']) && is_array($options['default_sorting']))     // See if default sorting is provided.
                $sort_info = $options['default_sorting'];
            else
                unset($requests['sort']);           // No sorting 
        }
        
        // Get a query object that uses any search text, filters and sorting provided.
        $args = array(
            'model' => $model,
            'model_columns' => $model_columns,
            'all_columns' => $all_columns,
            'non_rel_columns' => $non_rel_columns,
            'searchtext' => $searchtext,
            'filters' => isset($options['filters']) && is_array($options['filters']) ? $options['filters'] : NULL,
            'filter_by_dql' => !empty($options['filter_by_dql']) ? $options['filter_by_dql'] : NULL,
            'sort_info' => $sort_info,
			'column_order' => $column_order
        );
        $query = $this->generate_query_for_table_render($args);
        //echo $query->getSqlQuery();
        //echo '<pre>'; print_r($query->getParams()); echo '</pre>';
		
        // The pager object is created from the query object and it will then fetch all items as specified.
        $pager = new Doctrine_Pager($query, $cur_page, $records_per_page);
        $pager->setCountQuery($query->getDql());
        $items = $pager->execute(array(), Doctrine_Core::HYDRATE_ON_DEMAND);
        
        // Vars for constructing urls.
        $sortcolumn = NULL;
        $sortdirection = "";
        $paging = "";
        $searching = "";
        $sorting = "";
        if(isset($requests['sort']))
        {
            $sortcolumn = $requests['sort'][0];
            $sortdirection = $requests['sort'][1];
            $sorting = '/_table_sort/'.$sortcolumn.'/'.$sortdirection;
        }
        if(isset($requests['page'])) $paging = '/_table_page/'.$requests['page'];
        if($searchtext !== NULL) $searching = '/_table_search/'. base64_encode($searchtext);
        $extra_url = $sorting.$paging.$searching;
        $uri_params = (isset($options['uri_params'])) ? $options['uri_params'] : array();   // Contains parameters to include in the urls.
		if(!is_array($uri_params)) $uri_params = array($uri_params => '');	// Expected to be in array form.
        $param_url = $this->_construct_query_url($uri_params);
        $function_url .= $param_url;

        // Add form for creating and searching.
        //$html .= _get_default_table_css();
        $html = '<div class="table_ui">';
        $html .= '<div><h2>'.(isset($options['title']) ? $options['title'] : 'Manage '.cbeads_make_title_text(cbeads_remove_namespace_component($model))).'</h2>';
        $html .= (isset($options['description']) ? '<h5>'.$options['description'].'</h5></div>' : '</div>');
        

        $html .= '<form method="post" action="'.$function_url.$extra_url.'" >';
        $html .= '<input type="hidden" name="_table_submit" value="1" />';
        $html .= '<div>';
        if($allow_search && (iterator_count($items) > 0 || $searchtext !== NULL))   // Show search box when a search is active
            $html .= '<div style="float: left;"><input name="_table_searchtext" value="'.htmlentities($searchtext).'"/><input type="submit" name="_table_search" value="Search" /></div>';

        if(isset($controllers['create']))
        {
            if(isset($controllers['create']['label']))              // Options supplied create label
            {
                $create_label = $controllers['create']['label'];
            }
            elseif($this->_table_create_label_format != "")         // Generate generic label.
            {
                $tmp_name = cbeads_make_title_text(cbeads_remove_namespace_component($model));
                $create_label = preg_replace(array('/%model%/'), $tmp_name, $this->_table_create_label_format); 
            }
            else    // Cannot be empty.
            {
                $create_label = "Add New " . cbeads_make_title_text(cbeads_remove_namespace_component($model));
            }
            if(isset($controllers['create']['label'])) $create_label = $controllers['create']['label'];
            $html .= '<div class="no_decoration_link" style="text-align: right;"><a href="' .$function_url. '/_table_create' . $extra_url.'" title="' . cbeads_make_title_text($create_label) . '">';
            $html .= '<img class="img_link" src="' . base_url() . 'cbeads/images/create.png" /><span style="position: relative; top: -5px;">';
            $html .= cbeads_make_title_text($create_label) . '</span></a></div>';
        
        }
        $html .= '</div>
        </form>';
        
        // Create the table if there are items to display.
        if(iterator_count($items) > 0)
        {
            // Construct table.
            $html .= '<br><table class="renderer_table">';

            // Add column headings and unset create controller because we don't want that to appear in the table.
            unset($controllers['create']);
            $html .= '<tr>';

            foreach($column_order as $name)
            {
                if(!isset($model_columns[$name]))	// The column is neither an attribute of the item or a relationship
				{
					if(isset($columns[$name]['custom_render']) && is_callable($columns[$name]['custom_render']))
					{
						$label = (isset($columns[$name]['label']) ? $columns[$name]['label'] : $name);
						$html .= '<th>'.cbeads_make_title_text($label).'</th>';
					}
					continue;
				}
                $opts = $model_columns[$name];
                $label = (isset($opts['label']) ? $opts['label'] : $name);
                if($name == $sortcolumn)    // If this column is already sorted then make the url so that sorting will occur in the other direction if clicked again.
                {
                    $newsortdir = ($sortdirection == "ASC") ? "DESC" : "ASC";
                    $html .= '<th><a class="renderer_table_sort_header" href="'.$function_url.'/_table_sort/'.$name.'/'.$newsortdir.$paging.$searching.'" >'.cbeads_make_title_text($label).'</a></th>';
                }
                else
                {
                    // Can not order fields that are many to many or one to many.
                    if($opts['is_relationship'] == FALSE || ($opts['is_relationship'] && !$opts['is_many_to_many']))
                        $html .= '<th><a class="renderer_table_sort_header" href="'.$function_url.'/_table_sort/'.$name.'/ASC'.$paging.$searching.'" >'.cbeads_make_title_text($label).'</a></th>';
                    else
                        $html .= '<th>'.cbeads_make_title_text($label).'</th>';
                }
            }
            foreach($controllers as $name => $opts)
            {
                if(isset($opts['label'])) $name = $opts['label'];
                $html .= '<th width="1%" >'.cbeads_make_title_text($name).'</th>';
            }
                
            $html .= '</tr>';
            error_reporting(E_ALL);
            // Add data to the table.
            foreach($items as $item)
            {
                $html .= '<tr class="datarow">';
                //foreach($model_columns as $name => $opts)
				$html .= $this->generate_columns_for_item($item, $columns, $column_order, $model_columns);
                foreach($controllers as $name => $opts)
                {
                    $usename = $name;
                    if(isset($opts['label'])) $usename = $opts['label'];
                    
                    $html .= '<td style="text-align: center;"><a href="'.$function_url.'/_table_'.$name.'/'.$item->id . $extra_url.'" >';
                    if($name == "view" || $name == "edit" || $name == "delete")     // System controllers use images.
                        $html .= '<img class="img_link" src="' . base_url() . '/cbeads/images/'.$name.'.png" alt="' . ucfirst($usename) . '" title="' . ucfirst($usename) . '" />';
                    elseif(isset($opts['image_url']))   // Custom controller has an image to use.
                        $html .= '<img class="img_link" src="' . $opts['image_url'] . '" alt="' . ucfirst($usename) . '" title="' . ucfirst($usename) . '" />';
                    else
                        $html .= cbeads_make_title_text($usename);
                    $html .= '</a></td>';
      
                }
                $html .= '</tr>';
            }
            
            $html .= '</table>';
            $html .= $this->construct_table_pagination_controls($pager, array('url' => $function_url.$sorting.$searching), NULL);
        }
        else
        {
            $html .= cbeads_warning_message("There are no records to display!");
        }
        $result = array();
        $result['success'] = TRUE;

        if(!empty($options['output'])) 
            $result['output_html'] = $html;
        else
            print $html;
        
        return $result;
    }

    // Checks if there are controllers specified for the table. System controllers may be disabled and 
    // custom controllers may be provided in the table options.
    // controllers: reference to an array that will populated with controller information gathered in this function.
    // options: the table options.
    private function check_for_table_controllers(&$controllers, $options)
    {
        // Construct list of controllers. By default 'create', 'edit', 'view', 'delete' controllers are enabled.
        $system_controllers = array('view' => array(), 'edit' => array(), 'create' => array(), 'delete' => array());
        // Get any custom controllers defined.
        if(!empty($options['controllers']) && is_array($options['controllers']))
        {
            foreach($options['controllers'] as $controller => $opts)
            {
                if(!isset($system_controllers[$controller]))  //cannot have the same names as the system controllers
                    $controllers[$controller] = $opts;
            }
        }
        $controllers = array_merge($controllers, $system_controllers);
        // Check if any default controllers where disabled and getting any options that may have been provided.
        foreach($system_controllers as $ctrl => $val)
        {
            if(isset($options[$ctrl]) && is_array($options[$ctrl]))          // Option provided.
                $controllers[$ctrl] = $options[$ctrl];
            elseif(isset($options[$ctrl]) && $options[$ctrl] == FALSE)    // Disabled
                unset($controllers[$ctrl]);
        }
    }
    
    // Checks for table requests recorded in the url. This is for use by render_as_table.
    // controllers: array of controller that the table renderer should use.
    // allow_search: boolean, indicating if searching is allowed or not.
    // Returns an array of all requests.
    private function check_for_table_requests($controllers, $allow_search)
    {
        $requests = array();
        // Check if there are additional url segments indicating any actions and/or table modifications
        // Expect url segments: [/[action]/[obj_id]][/page/number][/sort/column/ASC|DESC][/search/searchtext] - all are optional and can be in any order
        for($i = 4; $i < count($this->_uri_segments) + 1; $i++)  // indices start from 1!
        {
            // See if this segment indicates a page request.
            if($this->_uri_segments[$i] == '_table_page' && !isset($requests['page']) && !isset($this->_uri_segments['page']) && isset($this->_uri_segments[$i+1]))
            {
                $requests['page'] = (int)$this->_uri_segments[$i+1];        // The page number
                $i++;
            }
            elseif($this->_uri_segments[$i] == '_table_sort' && !isset($requests['sort']) && isset($this->_uri_segments[$i+1]) && isset($this->_uri_segments[$i+2]))
            {
                $requests['sort'] = array($this->_uri_segments[$i+1], $this->_uri_segments[$i+2]);    // The column and direction to sort in
                $i++;
            }
            elseif($this->_uri_segments[$i] == '_table_search' && !isset($requests['search']) && isset($this->_uri_segments[$i+1]) && $allow_search)
            {
                $requests['search'] = base64_decode($this->_uri_segments[$i+1]);      // The search text
            }
            else
            {
                // See if this segment indicates an action. Needs to match an element in the controllers array.
                if(!isset($requests['action']))
                {
                    foreach(array_keys($controllers) as $name)
                    {
                        if($this->_uri_segments[$i] == '_table_'.$name)
                        {
                            $action = substr($this->_uri_segments[$i], 7);
                            if($action != 'create' && isset($this->_uri_segments[$i+1]))
                            {
								$requests['action'] = array($action, (int)$this->_uri_segments[$i+1]);  // The action name and object id for the action
								$i++;
                            }
                            else
                                $requests['action'] = array('create', NULL);
                            break;
                        }
                    }
                }
            }
        }
        // Check POST for actions
        if($this->_CI->input->post('_table_submit', FALSE) == 1)
        {
            if($this->_CI->input->post('_table_search') && $allow_search) $requests['search'] = trim($this->_CI->input->post('_table_searchtext'));
        }
        return $requests;
    }

    // Checks for a form submit when rendering a table. Usually when a form is submitted
    // the table is shown again, unless the form is a composite form, then the parent form 
    // may be shown. The table sits at the top of the hierarchy.
    // show_form: variable is passed by reference and is set to TRUE or FALSE to indicate if a form or 
    //            the table should be shown.
    private function check_for_table_form_submit(&$show_form)
    {
        if(!empty($_POST['_form_submit']))
        {
            $show_form = FALSE;     // Assume form will not be shown anymore.
            foreach(array_keys($_POST) as $fieldname)   // Look for a composite action or change_ui_level action
            {
                if(preg_match('/^_ct-/', $fieldname))
                {
                    $show_form = TRUE;
                    break;
                }
                elseif(preg_match('/^_change_ui_level-/', $fieldname))
                {
                    $show_form = TRUE;
                    break;
                }
            }
            if(!$show_form)     
            {
                // Make sure the top_ui_id and ui_level values exist and are valid.
                if(!empty($_POST['_top_ui_id'])) $this->_top_ui_id = (int)$_POST['_top_ui_id'];
                if(isset($_POST['_ui_level'])) $this->_ui_level = (int)$_POST['_ui_level'];
                $ui_data = $this->_CI->session->userdata('ui_data');
                if(isset($ui_data[$this->_top_ui_id])&& isset($ui_data[$this->_top_ui_id][$this->_ui_level]))
                {
                    $form_id = $ui_data[$this->_top_ui_id][$this->_ui_level]['form_id'];
                    // echo 'top_ui_id = ' . $top_ui_id . ' and form_id = ' . $form_id;
                    if($form_id != $this->_top_ui_id) $show_form = TRUE;
                }
            }
        }
    }
    
    // Processes a table action (controller) has been invoked. There are system controllers
    // (create, edit, delete, view) and custom controllers.
    // show_form: reference to a variable that will be modified to indicate if a form should be shown.
    // form_results: reference to a variable that will store the result of a render_as_form call 
    //               if a system controller was invoked. No change made when calling custom controllers.
    // action: a string indicating the controller action invoked.
    // obj_id: the object id that the action is invoked on (needed for non create actions)
    // model: the name of the model in use.
    // options: a reference to the table options provided to render_as_table. (May end up being changed by a callback function).
    private function process_table_action(&$show_form, &$form_result, $action, $obj_id, $model, &$options)
    {
        if(!empty($action))
        {
			// Must have a valid object id to perform any actions on it (unless it is NULL).
			if($obj_id !== NULL && !Doctrine::getTable($model)->find($obj_id))
			{
				echo cbeads_error_message('Cannot peform action with invalid object id');
				$show_form = FALSE;
				return;
			}
		
            if($action == 'create' || $action == 'edit' || $action == 'view' || $action == 'delete')
            {
                $tmp = new $model();
                $data_for_form = ($action == 'create') ? $tmp->select() : $tmp->select($obj_id);
                $form_options = array();
                $default_form_options = NULL;
                // See if the default form options exist. They may be dynamically generated via a callback.
                if(isset($options['form_options']['default']) && is_array($options['form_options']['default']))
                {
                    $default_form_options = $options['form_options']['default'];
                    if(isset($options['form_options']['default']['dynamic_setup']) &&
                        is_callable($options['form_options']['default']['dynamic_setup']))
                    {
                        $default_form_options = call_user_func($options['form_options']['default']['dynamic_setup'], 
                                                array('object_id' => $obj_id));
                    }
                
                }
                if(isset($options['form_options']) && isset($options['form_options'][$action]))
                {
                    // Check if dynamic form setup is requested.
                    if(isset($options['form_options'][$action]['dynamic_setup']))
                    {
                        if($options['form_options'][$action]['dynamic_setup'])
                        {
                            $form_options = call_user_func($options['form_options'][$action]['dynamic_setup'], 
                                                array('object_id' => $obj_id));
                        }
                        else
                        {
                            print cbeads_error_message("No valid callback function specified for dynamic form rendering");
                        }
                    }
                    else
                    {
                        $form_options = $options['form_options'][$action];
                    }
                    $form_options['type'] = $action;
                    if($default_form_options)
                        $form_options = $this->_inherit_default_form_options($default_form_options, $form_options);
                }
                else
                {
                    // Use default form options if provided.
                    if($default_form_options)
                        $form_options = $default_form_options;
                    $form_options['type'] = $action;
                }
                $form_options['output'] = TRUE;
                $form_options = $this->_inherit_table_options($options, $form_options);
                $form_result = $this->render_as_form($form_options, $data_for_form);
                if($form_result['success'] == FALSE)
                {
                    print "<div style='border: 1px solid red;'>Error rendering as form: <br>" . $form_result['msg']. '</div>';
                }
                if(!empty($form_result['submit_error'])) $show_form = TRUE;
            }
            else    // custom controller invoked.
            {
                $show_form = FALSE;
                if(is_callable($options['controllers'][$action]['callback']))
                {
                    call_user_func($options['controllers'][$action]['callback'], 
                            array('object_id' => $obj_id, 'table_options' => &$options));
                }
                else
                {
                    print cbeads_error_message("No function/method specified to call for custom controller: '$action'");
                }
            }
        }
        else
        {
            $show_form = FALSE;
        }
    }
    
    
    // Generates a query object to use for pagination for tables.
    // args: an associative array of values needed to generate the query (see render_as_table for details)
    //       - model: the name of the model the table is generated for.
    //       - model_columns: array of fields in the model. This includes foreign relationships
    //       - all_columns: array of all the columns in the table associated with the model.
    //       - non_rel_columns: array of field names in the model that are not foreign keys fields.
    //       - searchtext: the search text if any
    //       - filters: array of filters (can contain filter groups) eg: filters => array(filter, filter_grp(filter, filter), filter_grp(filter)) 
    //       - filter_by_dql: a DQL query string to use for filtering records.
	//		 - sort_info: an array indicating the column(s) to sort and sort direction(s) eg: array(column => ASC|DESC, column => ASC|DESC, ...)
    private function generate_query_for_table_render($args)
    {
        $model = $args['model'];
        $model_columns = $args['model_columns'];
        $all_columns = $args['all_columns'];
        $non_rel_columns = $args['non_rel_columns'];
        $searchtext = $args['searchtext'];
        $filters = $args['filters'];
		$filter_by_dql = $args['filter_by_dql'];
        $sort_info = $args['sort_info'];
		//cbeads_nice_vardump($model_columns);
        // Create a query object depending on what options are defined. If filters are specified then
        // they must be included in the query object generation.
        $query = Doctrine_Query::create()->from( $model. ' c' );
        if($searchtext !== NULL)
        {
            //echo "searching for: ".$searchtext."....<br>";
            $first = TRUE;
            $sub_query = "SELECT b.id FROM $model b WHERE (";
            $sub_params = array();
            foreach($non_rel_columns as $col_name)
            {
				if(!in_array($col_name, $args['column_order'])) continue;
                if(!$first) $sub_query .= ' OR ';
                //$sub_query .= 'b.'.$col_name. ' LIKE ? ';
                $sub_query .= 'b.'.$col_name.' LIKE \'%'.$searchtext.'%\' ';
                $first = FALSE;
                //$sub_params[] = '%'.$searchtext.'%';
            }
            $sub_query .= ')';
            if(!$first)
                $query = $query->andWhere('c.id IN (' . $sub_query . ')', $sub_params);
        }
        if(isset($filters) && is_array($filters))
        {
            $has_filter = FALSE;
            $joins = 0; // Used to generate a unique alias for each join made.
            $existing_joins = array();  // Stores the related object and number already used in previous joins.
            $operators = array('<', '>', '=', '!=', 'LIKE');
            $sub_query = Doctrine_Query::create()->select('d.id')->from( $model. ' d' );
            //$sub_query = "SELECT d.id FROM $model d WHERE (";
            //$sub_params = array();
            foreach($filters as $filter)
            {
                if(is_array($filter) && !empty($filter['column']) && isset($filter['value']) // Need all values present and a valid column
                    && !empty($filter['operator']) && !empty($filter['type']))
                {
                    if(empty($filter['join']) && in_array(trim($filter['column']), $all_columns) && in_array(trim($filter['operator']), $operators) )
                    {
                        //if($first)
                            //$sub_query .= 'd.'.$filter['column']. ' '. $filter['operator'].' ';
                        //    $sub_query->addWhere('d.'.$filter['column']. ' '. $filter['operator']." '" . $filter['value'] . "'");
                        //else
                        if($filter['type'] == 'AND')
                            //$sub_query .= ' AND d.'.$filter['column']. ' '.$filter['operator'].' ';
                            $sub_query->addWhere('d.'.$filter['column']. ' '.$filter['operator']." '" . $filter['value'] . "'");
                        elseif($filter['type'] == 'OR')
                            //$sub_query .= ' OR d.'.$filter['column'].' '. $filter['operator'].' ';
                            $sub_query->orWhere('d.'.$filter['column'].' '. $filter['operator']." '" . $filter['value'] . "'");
                        //$sub_params[] = $filter['value'];
                        //$sub_query .= "'" . $filter['value'] . "'";
                        $has_filter = TRUE;
                    }
                    else if(!empty($filter['join']) && in_array(trim($filter['operator']), $operators)
                            && in_array($filter['join'], array_keys($model_columns)))    // Filter applying to relationship
                    {
					//cbeads_nice_vardump($filter);
                        if($model_columns[$filter['join']]['is_relationship'])
                        {
                            if(isset($existing_joins[$filter['join']]))
                            {
                                $alias = ' e' . $existing_joins[$filter['join']];
                            }
                            else
                            {
                                $alias = ' e'.$joins;
                                $existing_joins[$filter['join']] = $joins;
                                $sub_query->leftJoin('d.' . $model_columns[$filter['join']]['rel_alias'] . ' '.$alias);
                            }
                            if($filter['type'] == 'AND')
                                $sub_query->addWhere($alias.'.'.$filter['column']. ' '.$filter['operator']." '" . $filter['value'] . "'");
                            elseif($filter['type'] == 'OR')
                                $sub_query->orWhere($alias.'.'.$filter['column'].' '. $filter['operator']." '" . $filter['value'] . "'");
                            $has_filter = TRUE;
                            $joins++;
                        }
                        
                    }
                    
                }
            }
            //$sub_query .= ')';
            if($has_filter) // Only include sub query if one or more filters where accepted.
            {
                $query = $query->andWhere('c.id IN (' . $sub_query->getDql() . ')');
            }
            unset($sub_query);
        }
		if(!empty($filter_by_dql))
		{
			$query = $query->andWhere('c.id IN (' . $filter_by_dql . ')');
		}
        if(isset($sort_info))
        {
            $order_strings = array();
            foreach($sort_info as $column => $dir)
            {
                //$f = $sort_info[0];
                $f = $column;
                //echo $f;
                //nice_vardump($model_columns);
                if(!isset($model_columns[$f]) || ($dir != "ASC" && $dir != "DESC")) continue;
                $c = $model_columns[$f];
                $orderby_str = 'c.'.$f;             // Assume a field in this model.
                
                // Check if the field to order by is a foreign key field. In that case need to get the 
                // columns to join into a string that can then be sorted.
                if($c['is_associative'] && !$c['is_many_to_many'])
                {
                    $ra = $c['rel_alias'];
                    $rc = $c['rel_class'];
                    $tmp = new $rc();
                    $cols = $tmp->get_columns_for_instance_sorting();
                    $elements = array();
                    foreach($cols as $col)
                        $elements[] = "a.$col";
                    $query->select('c.*, CONCAT(' . join(',', $elements) . ") as $ra\_sort_string");    // Creats a element for the stringified related object.
                    $query->leftJoin("c.$ra a");
                    $orderby_str = "$ra\_sort_string";
                }
                
                $order_strings[] = "$orderby_str $dir";
            }
            //$query = $query->orderby($orderby_str.' '.$sort_info[1]);
            //echo "strings: " .join(',', $order_strings);
            if(count($order_strings) > 0)   // Got something to order by
                $query = $query->orderBy(join(',', $order_strings));
        }
        return $query;
    }
	
    // Generates html table cells containing the attribute values of an item.
	// item: the item whose values should be used.
	// columns: the columns (attributes) for which values need to be returned.
	// column_order: the order of the columns.
	// model_columns: the attributes and relationships that exist in the model that this item is an instance of.
	// Returns a html string of table cells containing values.
	private function generate_columns_for_item($item, $columns, $column_order, $model_columns)
	{
		$html = "";
		foreach($column_order as $name)
		{
		//echo $name.'<br />';
			if(!isset($model_columns[$name]))	// The column is neither an attribute of the item or a relationship
			{			
				if(isset($columns[$name]['custom_render']) && is_callable($columns[$name]['custom_render']))	// If a custom render function is provided for this column, then show it.
				{
					$html .= '<td>' . call_user_func($columns[$name]['custom_render'], 
					array('type' => 'normal', 'item' => $item)) . '</td>';
				}
				continue;
			}
			$opts = $model_columns[$name];
			// Columns can be custom rendered.
			if(isset($columns[$name]['custom_render']))// && is_callable($columns[$name]['custom_render']))     
			{
				if($opts['is_relationship'])
				{
					//$item->refreshRelated();
					if($opts['is_associative'] && !$opts['is_many_to_many'])     // this object is related to a single object (M:O,O:O)
					{
						$rel_alias = $opts['rel_alias'];
						$html .= '<td>' . call_user_func($columns[$name]['custom_render'], 
								array('type' => 'related_one', 'data' => $item->$rel_alias)) . '</td>'; 
					}
					else    // this object is related to many objects (O:M, M:M)
					{
						$html .= '<td>' . call_user_func($columns[$name]['custom_render'], 
								array('type' => 'related_many', 'data' => $item->$name)) . '</td>'; 
					}
				}
				else
				{
					$html .= '<td>' . call_user_func($columns[$name]['custom_render'], 
					array('type' => 'normal', 'data' => $item->$name)) . '</td>';
				}
			}
			else
			{
				if($opts['is_relationship'])
				{
					//$item->refreshRelated();
					if($opts['is_associative'] && !$opts['is_many_to_many'])     // this object is related to a single object (M:O,O:O)
					{
						$rel_alias = $opts['rel_alias'];
						$html .= '<td>';
						if($item->$rel_alias->id !== NULL)
							$html .= $item->$rel_alias->stringify_self();
						$html .= '</td>';
					}
					else    // this object is related to many objects (O:M, M:M)
					{
						$strings = array();
						foreach($item->$name as $obj)
							$strings[] = $obj->stringify_self();
						$html .= '<td>'.implode(', ', $strings).'</td>';
					}
				}
				else
				{
					// Check if there are formatting functions for this column type.
					$td_html = $this->_format_table_data($name, $item->$name, isset($columns[$name]) ? $columns[$name] : array());
					$html .= '<td>' . $td_html . '</td>';
				}
			}
		}
		return $html;
	}
	
    
    // Composite table render function, used by forms for displaying a table of their composite
    // objects. This function is different to render_as_table. (added 2010/05/25)
    // ct_options: stores options for how to render the table as part of a composite form.
    //             This is similar to the data sent to render_as_form but there are some
    //             differences. The options need to provide these three options:
    //             - model: the name of the model
    //             - parent_id: the id of the parent object
    //             - foreign_key: the name of the foreign_key column pointing to the parent model.
    // Returns an array containing a success field. If true all went well and the composite table
    // html is returned as 'output_html' => html. If success is false, then msg contains an error 
    // message.
    private function _render_as_composite_table($ct_options)
    {
        // Model name, parent id and foreign key column are required!
        if(empty($ct_options) || !is_array($ct_options) || empty($ct_options['model'])) 
            return array('success' => FALSE, 'msg' => 'Need to specify the model name in the options array!');
        if(empty($ct_options['parent_id']))
            return array('success' => FALSE, 'msg' => 'Need to specify the the parent id in the options array!');
        if(empty($ct_options['foreign_key']))
            return array('success' => FALSE, 'msg' => 'Need to specify the the foreign key column in the options array!');

        // Variables
        $model = $ct_options['model'];
        $model_name = str_replace("\\", ':', $model);
        
        $column_order = array();        // Stores the column order to use.
        $columns = array();             // Stores the column attributes to use.
        $model_columns = array();       // Stores the model info for every column shown in the table. (format: column_name => array of info)
        $controllers = array();         // Stores the controllers available (for display in the table).
        $data = array();                // Stores the data for the records that need to be displayed.
        $action = NULL;
        $can_search = TRUE;             // Boolean indicating if the search feature is on or off.
        $search_info = NULL;            // Stores search information to use. When NULL it indicates no search.
        $sort_info = NULL;              // Stores sorting information used to sort a column. Is NULL when there is no sorting.
        $page = NULL;                   // Stores the current page on (if pagination is on). Is NULL when no pagincation.
        
        $html = "";                     // Stores the table html.
        
        // Check the UI data from the user's session to see if this composite table has any sorting, page or search set.
        $ui_data = $this->_CI->session->userdata('ui_data');   
        $cur_ui_data = $ui_data[$this->_top_ui_id][$this->_ui_level];
        if(!isset($cur_ui_data['table_states'])) $cur_ui_data['table_states'] = array();
        $table_states = $cur_ui_data['table_states'];
        if(!isset($table_states[$model_name])) $table_states[$model_name] = array();
        $table_state = $table_states[$model_name];
        if(isset($table_state['searching']) && !empty($table_state['searching']))
            $search_info = $table_state['searching'];
        if(isset($table_state['sorting']) && !empty($table_state['sorting']))
            $sort_info = $table_state['sorting'];
        if(isset($table_state['paging']) && !empty($table_state['paging']))
            $page = $table_state['paging'];
        // echo "Current table is: $model_name<br><br>";
        // echo "ALL STATES:<br>";
        // nice_vardump($table_states);
        // nice_vardump($cur_ui_data);
        // echo "CURRENT STATE:<br>";
        // nice_vardump($table_state);
        // echo "SEARCH INFO:<br>";
        // nice_vardump($search_info);
        
        // Check what actions have been disabled (by default the basic actions are enabled).
        $this->check_for_table_controllers($controllers, $ct_options);
        if(isset($ct_options['search'])) $can_search = $ct_options['search'];
            
        if(isset($ct_options['column_order'])) $column_order = $ct_options['column_order'];
        if(isset($ct_options['columns'])) $columns = $ct_options['columns'];
        
         // If there is a field name that starts with '_ct-' then a composite table action was performed.
         // Only interested in sort, search and page actions for generating the table.
        $update_ui_data = FALSE;
        foreach($_POST as $name => $val)
        {
            if(preg_match('/^_ct-/', $name)) // Will be in format '_ct-sort-[model]-[column]-[asc|desc]' or '_ct-search-[model]-[searchtext]' or '_ct-page-[model]-[page_number]'
            {
                $parts = explode('-', $name);
                $composite_action = $parts[1];
                $composite_model = str_replace(':', "\\", $parts[2]);
                if($composite_model == $model)  // Only interested in actions for this table.
                {
                    if($composite_action == 'search')
                    {
                        $search_info = NULL;
                        if(isset($_POST['_searchtext-'.$parts[2]]))
                            $search_info['searchtext'] = trim($_POST['_searchtext-'.$parts[2]]);
                        $table_state['searching'] = $search_info;
                        $update_ui_data = TRUE;
                    }
                    elseif($composite_action == 'sort')
                    {
                        $sort_info[$parts[3]] = $parts[4];   // column name => direction
                        $table_state['sorting'] = $sort_info;
                        $update_ui_data = TRUE;
                    }
                    elseif($composite_action == 'page')
                    {
                        $page = (int)$parts[3];      // page number
                        $table_state['paging'] = $page;
                        $update_ui_data = TRUE;
                    }
                }
            }
        }
        if($update_ui_data)
        {
            $ui_data[$this->_top_ui_id][$this->_ui_level]['table_states'][$model_name] = $table_state;
            $this->_CI->session->set_userdata('ui_data', $ui_data);
        }
        
        // Get all columns and relationships for the model. Columns that do not appear
        // in the column order array are discarded.
        $table = \Doctrine::getTable($model);
        $relations = $table->getRelations();
        $db_columns = $table->getColumns();
        $non_rel_columns = array();
        $all_columns = array();
        // If the order array is empty then use the columns and relations from the
        // model as the order.
        if(empty($column_order)) $column_order = array_merge(array_keys($db_columns), array_keys($relations));
        
        // Remove all columns that store file data.
        $tmp_keys = array_keys($db_columns);
        for($i = count($db_columns) - 1; $i > -1; $i--)
        {
            if($this->_is_privatefile_column($tmp_keys, $tmp_keys[$i]))
                unset($db_columns[$tmp_keys[$i]]);
        }
        
        foreach($db_columns as $col_name => $opts)
        {
            $all_columns[] = $col_name;
            if(!in_array($col_name, $column_order)) continue;
            $is_relationship = FALSE;
            $rel_alias = NULL;
            $rel_class = NULL;
            foreach($relations as $name => $relation)   // See if this column is a foreign key column
            {
                if($relation->getType() == Doctrine_Relation::ONE && $relation->getLocal() == $col_name) // Only interested in One-to-One and Many-to-One relations
                {
                    $is_relationship = TRUE;
                    $rel_alias = $relation->getAlias();
                    $rel_class = $relation->getClass();
                    break;
                }
            }
            $model_columns[$col_name] = array('is_relationship' => $is_relationship, 'is_associative' => $is_relationship, 'is_many_to_many' => FALSE, 'rel_alias' => $rel_alias, 'rel_class' => $rel_class);
            if(isset($columns[$col_name]))
                $model_columns[$col_name] = array_merge($columns[$col_name], $model_columns[$col_name]);
            if(!$is_relationship) $non_rel_columns[] = $col_name;
        }

        // Check for relationships where the foreign table references objects in here.
        // These will be One to Many or Many to Many relationships.
        foreach($relations as $name => $relation)
        {
            if(!in_array($name, $column_order)) continue; 
            if($relation->getType() == Doctrine_Relation::ONE) continue;
            $model_columns[$name] = array('is_relationship' => TRUE, 'is_associative' => FALSE, 'is_many_to_many' => TRUE, 'rel_alias' => $relation->getAlias(), 'rel_class' => $relation->getClass());
            if(isset($columns[$name])) $model_columns[$name] = array_merge($columns[$name], $model_columns[$name]);
        }
        //echo '<pre>'; print_r($model_columns); print '</pre>';
        
        // Get the data to display.
        $cur_page = !empty($page) ? $page : 1;
        $records_per_page = 20;

		
		// Get a query object that uses any search text, filters and sorting provided.
        $args = array(
            'model' => $model,
            'model_columns' => $model_columns,
            'all_columns' => $all_columns,
            'non_rel_columns' => $non_rel_columns,
            'searchtext' => $search_info['searchtext'],
            'filters' => NULL,
			'filter_by_dql' => !empty($options['filter_by_dql']) ? $options['filter_by_dql'] : NULL,
            'sort_info' => $sort_info,
			'column_order' => $column_order
        );
		
		$query = $this->generate_query_for_table_render($args);
		// Only want child elements of the current parent.
		$query->where('c.' . $ct_options['foreign_key'] . ' = ' . $ct_options['parent_id']);
		
        // The pager object is created from the query object and it will then fetch all items as specified.
        $pager = new Doctrine_Pager($query, $cur_page, $records_per_page);
        $pager->setCountQuery($query->getDql());
        $items = $pager->execute(array(), Doctrine_Core::HYDRATE_ON_DEMAND);
        
        $html .= '<div class="table_ui">';

        //$html .= '<div>';
		$search_create_html = '';
        if($can_search && (iterator_count($items) > 0 || $search_info !== NULL))    // When a search is active, show the search box.
            $search_create_html .= '<div style="float: left;"><input name="_searchtext-'.$model_name.'" value="'.$search_info['searchtext'].'"/><input class="ct_button" type="submit" name="_ct-search-'.$model_name.'" value="Search" /></div>';
        if(isset($controllers['create']))
        {
            if(isset($controllers['create']['label']))              // Options supplied create label
                $create_label = $controllers['create']['label'];
            elseif($this->_table_create_label_format != "")         // Generate generic label.
            {
                $tmp_name = cbeads_make_title_text(cbeads_remove_namespace_component($model));
                $create_label = preg_replace(array('/%model%/'), $tmp_name, $this->_table_create_label_format); 
            }
            else    // Cannot be empty.
                $create_label = "Add New " . cbeads_make_title_text(cbeads_remove_namespace_component($model));
            $search_create_html .= '<div style="text-align: right;"><input class="ct_button" type="submit" name="_ct-create-'.$model_name.'" value="" style="background: #FFFFFF url('.base_url() . 'cbeads/images/create.png); border: 0px; cursor: pointer; width: 22px; height: 22px;" />'.$create_label.'</div>';
        }
		if($search_create_html != '') $html .= "<div>$search_create_html<br></div>";
        //$html .= '</div>';
        
        // Create the table if there are items available.
        if(iterator_count($items) > 0)
        {
        
            // Construct table.
            $html .= '<table class="renderer_table">';

            // Add column headings and unset create controller because we don't want that to appear in the table.
            unset($controllers['create']);
            $html .= '<tr>';

            //foreach($model_columns as $name => $opts)
            foreach($column_order as $name)
            {
				if(!isset($model_columns[$name]))	// The column is neither an attribute of the item or a relationship
				{
					if(isset($columns[$name]['custom_render']) && is_callable($columns[$name]['custom_render']))
					{
						$label = (isset($columns[$name]['label']) ? $columns[$name]['label'] : $name);
						$html .= '<th>'.cbeads_make_title_text($label).'</th>';
					}
					continue;
				}
				else
				{
					$opts = $model_columns[$name];
					$label = (isset($opts['label']) ? $opts['label'] : $name);
					if(isset($sort_info[$name]))    // If this column is already sorted then make the url so that sorting will occur in the other direction if clicked again.
					{
						$newsortdir = ($sort_info[$name] == "ASC") ? "DESC" : "ASC";
						$html .= '<th><input class="renderer_table_sort_header" type="submit" name="_ct-sort-'.$model_name.'-'.$name.'-'.$newsortdir.'" value="'.cbeads_make_title_text($label).'" /></th>';
					}
					else
					{
						// Can not order fields that are many to many or one to many.
						if($opts['is_relationship'] == FALSE || ($opts['is_relationship'] && !$opts['is_many_to_many']))
							$html .= '<th><input class="renderer_table_sort_header" type="submit" name="_ct-sort-'.$model_name.'-'.$name.'-ASC" value="'.cbeads_make_title_text($label).'" /></th>';
						else
							$html .= '<th>'.cbeads_make_title_text($label).'</th>';
					}
				}
            }
            foreach($controllers as $name => $opts)
            {
                $html .= '<th width="1%">'.cbeads_make_title_text($name).'</th>';
            }
                
            $html .= '</tr>';
            
            // Add data to the table.
            foreach($items as $item)
            {
                $html .= '<tr class="datarow">';
				$html .= $this->generate_columns_for_item($item, $columns, $column_order, $model_columns);
                foreach($controllers as $name => $opts)
                {
                    $usename = $name;
                    if(isset($opts['label'])) $usename = $opts['label'];
                    
                    $html .= '<td class="ct_controller_td"><input class="ct_controller_button" type="submit" name="_ct-'. 
                             $name . '-' . $model_name .'-'. $item->id . '" value="" ';
                    if($name == "view" || $name == "edit" || $name == "delete")     // System controllers use images.
                       $html .= ' style="background-image: url(' . base_url() . 'cbeads/images/'.$name.'.png); width: 22px; height: 22px;" title="' . ucfirst($usename) . '" />';
                    elseif(isset($opts['image_url']))   // Custom controller has an image to use.
                       $html .= ' style="background-image: url(' . $opts['image_url'] . '); width: 22px; height: 22px;" title="' . ucfirst($usename) . '" />';
                    else
                       $html .= ' />';
                    $html .= '</td>';
                
                }
                $html .= '</tr>';
            }
            
            $html .= '</table>';
            
            $html .= $this->construct_table_pagination_controls($pager, NULL, array('model_name' => $model_name));
        }
        else
        {
            $html .= cbeads_warning_message("There are no records to display!");
        }

        return array('success' => TRUE, 'output_html' => $html);
        
    }

    // Constructs the pagination controls for tables.
    // pager: the pager object used in constructing the controls.
    // opts: options passed when calling this function from render_as_table
    // ct_opts: options passed when calling this funtion from _render_as_composite_table.
    // Returns the html of the paginating controls.
    public function construct_table_pagination_controls($pager, $opts, $ct_opts)
    {
        $html = "";
        // Add pagination controlls if necessary.
        if($pager->haveToPaginate())
        {
            $pages = $pager->getRange('Sliding', array('chunk' => 3))->rangeAroundPage();
            if(isset($opts))
            {
                $url = $opts['url'];

                $html .= '<div id="renderer_table_pager_controls"><a title="First Page" class="renderer_table_pager" href="'.$url.'/_table_page/1" >&lt&lt</a>';
                $html .= '<a title="Previous Page" class="renderer_table_pager" href="'.$url.'/_table_page/'.$pager->getPreviousPage().'" >&lt</a>';
                foreach($pages as $page)
                {
                    $class = ($page == $pager->getPage()) ? 'renderer_table_active_page' : 'renderer_table_pager';
                    $html .= '<a class="'.$class.'" href="'.$url.'/_table_page/'. $page . '" >'. $page .'</a>';
                }
                $html .= '<a title="Next Page" class="renderer_table_pager" href="'.$url.'/_table_page/'.$pager->getNextPage().'" >&gt</a>';
                $html .= '<a title="Last Page (' . $pager->getLastPage() . ')" class="renderer_table_pager" href="'.$url.'/_table_page/'.$pager->getLastPage().'" >&gt&gt</a></div>';
            }
            else
            {
                $submit_name = "_ct-page-".$ct_opts['model_name']."-";

                $html .= '<div id="renderer_table_pager_controls"><input type="submit" title="First Page" class="renderer_table_pager" name="'.$submit_name.'1" value="&lt&lt" />';
                $html .= '<input type="submit" title="Previous Page" class="renderer_table_pager" name="'.$submit_name.$pager->getPreviousPage().'" value="&lt" />';
                foreach($pages as $page)
                {
                    $class = ($page == $pager->getPage()) ? 'renderer_table_active_page' : 'renderer_table_pager';
                    $html .= '<input type="submit" class="'.$class.'" name="'.$submit_name.$page.'" value="'.$page.'" />';
                }
                $html .= '<input type="submit" title="Next Page" class="renderer_table_pager" name="'.$submit_name.$pager->getNextPage().'" value="&gt" />';
                $html .= '<input type="submit" title="Last Page (' . $pager->getLastPage() . ')" class="renderer_table_pager" name="'.$submit_name.$pager->getLastPage().'" value="&gt&gt" /></div>';
            }
        }
        return $html;
    }

    
//////////////////////////////////////// FUNCTIONS FOR MENU RENDERING //////////////////////////////////////////////////////
    
    
    // Generates a menu UI. This UI can be used to easily display all tables for a given database.
	// Alternatively it can be used to display custom content on a tab by tab basis without relying
	// on a database. Or it can be a mixture of database tables and custom content.
    // options: the options to use in generating the menu.
    // Returns an array containing a success field. If it is true then all went smoothly. Otherwise
    // it returns false and the array will containg a 'msg' field which contains the error message.
    public function render_as_menu($options)
    {
        $namespace = NULL;      // Stores the namespace in use (if any)
        $model_names = NULL;    // Stores all model names found in the provided namespace.
        $cur_active = NULL;     // The name of the active menu.

        
        $html = "";             // Stores the html generated for the menu.
        
        if(!is_array($options))
            return array('success' => FALSE, 'msg' => 'Expecting an array of options for render_as_menu');
        
        // When a namespace is provided it needs to be valid.
        if(!empty($options['namespace']))
        {
            if(!cbeads_does_namespace_exist($options['namespace']))
            {
                return array('success' => FALSE, 'msg' => 'No models for this namespace could be found');
            }
            $namespace = $options['namespace'];
        }
        
        // When no order is provided it needs to be generated from the items provided or
        // from the models that exist for a selected namespace.
        $model_names = cbeads_get_loaded_model_names_for_db($namespace);
        if(empty($options['item_order']))
        {
                if($namespace !== NULL)
					$options['item_order'] = $model_names;
				else
					$options['item_order'] = array_keys($options['items']);
        }

        if(empty($options['item_order']))
            return array('success' => FALSE, 'msg' => 'No item order or items specified for the menu');
        
        // See if the options specified an active tab or an active tab is specified in the url.
        if(!empty($options['active_item']) && in_array($options['active_item'], $options['item_order']))
        {
            $cur_active = $options['active_item'];
        }
        else
        {
            $this->_uri_segments = $this->_CI->uri->segment_array();  

            // See if there is a url segment called '_menu_active' and use the following segment value 
            // for the active menu as long as it is defined in the item_order array.
            for($i = 1; $i < count($this->_uri_segments) + 1; $i++)  // indices start from 1 in the array returned by segment_array
            {
                if($this->_uri_segments[$i] == '_menu_active')
                {
                    if(isset($this->_uri_segments[$i+1]))
                    {
                        if(in_array($this->_uri_segments[$i+1], $options['item_order']))
                            $cur_active = $this->_uri_segments[$i+1];
                        break;
                    }
                }
            }
            if($cur_active === NULL) $cur_active = $options['item_order'][0];
        }
        
        // Construct the base part of the url to use for the menu tabs
        $base_url = "";
        for($i = 1; $i < 4 && $i < count($this->_uri_segments) + 1; $i++)  // indices start from 1 in the array returned by segment_array
        {
            if($i > 1) $base_url .= '/';
            $base_url .= $this->_uri_segments[$i];
        }

		// Check if custom uri segments need to be added to the base_url (app/controller/function).
		// For example the url in use might be myapp/myctrl/myfunc/some_uri_segment.
		if(isset($options['uri_params']) && $options['uri_params'] != '')
		{
			$base_url .= '/' . $options['uri_params'];
		}
		
        // Generate menu tabs html
        $html = '<div class="renderer_menu"><ul>';
        foreach($options['item_order'] as $item)
        {
            $label = $item;
            if(isset($options['items']) && isset($options['items'][$item]) && isset($options['items'][$item]['label']))
                $label = $options['items'][$item]['label'];
            if($item == $cur_active)
                $html .= '<li><a href="'.site_url($base_url.'/_menu_active/'.$item).'" class="current">'.cbeads_make_title_text($label).'</a></li>';
            else
                $html .= '<li><a href="'.site_url($base_url.'/_menu_active/'.$item).'" >'.cbeads_make_title_text($label).'</a></li>';
        }
        $html .= '</ul></div><br><br>';

        $cust_tab_html = "";
        
        // Need to display a table or call a function depending on what item is active.
        if(in_array($cur_active, $model_names)) // Items that have the same name as models
        {
            $table_options = array();
            if(!empty($options['items'][$cur_active]) && !empty($options['items'][$cur_active]['content'])
               && is_array($options['items'][$cur_active]['content']))
            {
                // We have either been passed a callback function which generates table options
                // or an array containing the table options.
                if(is_callable($options['items'][$cur_active]['content']))
                    $table_options = call_user_func($options['items'][$cur_active]['content']);
                else
                    $table_options = $options['items'][$cur_active]['content'];
            }
            else
            {
                $table_options = array();
                $table_options['create'] = (!isset($options['create'])) ? TRUE : $options['create'];
                $table_options['edit'] = (!isset($options['edit'])) ? TRUE : $options['edit'];
                $table_options['view'] = (!isset($options['view'])) ? TRUE : $options['view'];
                $table_options['delete'] = (!isset($options['delete'])) ? TRUE : $options['delete'];
                $table_options['model'] = $namespace.'\\'.ucfirst($cur_active);
            }
            // Need to provide the model plus specify the active menu item so that the table renderer can use it
            if(!isset($table_options['model'])) $table_options['model'] = $namespace.'\\'.ucfirst($cur_active);
			// Must set additional uri segments needed to know what menu is selected, plus any custom segments 
			// specified by the user.
			if(isset($options['uri_params']) && $options['uri_params'] != '')
			{
				$table_options['uri_params'][$options['uri_params']] = NULL;
			}
            $table_options['uri_params']['_menu_active'] = $cur_active;
            $table_options['output'] = TRUE;
            $result = $this->render_as_table($table_options);
            $html .= $result['output_html'];
        }
        elseif(isset($options['items']) && in_array($cur_active, array_keys($options['items']))  // Items that are not models
               && !empty($options['items'][$cur_active]['content']))
        {
            ob_start();
            if(is_callable($options['items'][$cur_active]['content']))
            {
                call_user_func($options['items'][$cur_active]['content'], array('active_menu' => $cur_active));
            }
            else
            {
                print cbeads_error_message("No function/method specified to call for menu item: '$cur_active'");
            }
            $cust_tab_html = ob_get_contents();
            ob_end_clean();
        }
        else
        {
            ob_start();
            print cbeads_error_message("No function/method specified to call for menu item: '$cur_active'");
            $cust_tab_html = ob_get_contents();
            ob_end_clean();
        }
        
        $html .= $cust_tab_html;

        // Need to return the generated html or just print it.
        $result = array();
        $result['success'] = TRUE;
        
        if(!empty($options['output'])) 
            $result['output_html'] = $html;
        else
            print $html;
        
        return $result;
        
    }

	
////////////////////////////////////////////// Utility functions ///////////////////////////////////////////////////////////


    // Finds the attribute definition that exists for a field.
    // fieldname: the name of the field to look for (will do partial match if full match fails)
    // Returns the found attribute definition record. If no match is found then NULL is returned.
    private function _find_attribute_definition_for_field($fieldname)
    {
        if($fieldname == 'id') return NULL;
        $use_def = NULL;
        // See if a direct match can be found.
        foreach($this->_attr_defs as $def)
        {
            if(strtolower($def['name']) == strtolower($fieldname)) 
            {
                $use_def = $def;
                break;
            }
        }
        
        
        // If there is no direct match, try to perform a substring match
        // and pick the one that has the best matching datatype.
        // Ie, 'name' could match 'first_name', 'last name'
        if($use_def === NULL)
        {
            $lc_name = strtolower($fieldname);
            foreach($this->_attr_defs as $def)
            {
                //if(preg_match("/$lc_name/", strtolower($def['name']))  ||
                if(preg_match('/'.strtolower($def['name']).'/', $lc_name))
                {
                    $use_def = $def;
                    break;
                }
            }
        }
        
        if($use_def === NULL) return NULL;
        if(!empty($use_def->alias_for)) $use_def = $this->_process_alias_attribute_definition($use_def);
        
        return $use_def;
    }

    // Recursively processes a attribute definition which is acting as an alias for another
    // definition to obtain the top most attribute definition and replace its values as set
    // in the alias definition.
    // attribute: the attribute to process (may be an aliasing attribute or a base attribute)
    // Returns the passed attribute modified to take into account values set in the attribute
    // that is being aliased.
    private function _process_alias_attribute_definition($attribute)
    {
        if(!empty($attribute->alias_for))
        {
            $to_alias = $attribute->alias;
            if($to_alias !== FALSE)
            {
                $to_alias = $this->_process_alias_attribute_definition($to_alias);
                if($attribute->db_type === NULL) $attribute->db_type = $to_alias->db_type;
                if($attribute->render_type_id === NULL) $attribute->render_type_id = $to_alias->render_type_id;
                if($attribute->comment === NULL) $attribute->comment = $to_alias->comment;
                if($attribute->additional === NULL) $attribute->additional = $to_alias->additional;
            }
        }
        return $attribute;
    }

    // Private function. This function gets the render options associated with
    // a field name.
    // name: the name of the field to get the render options for.
    // Returns an array of render options.
    private function _get_render_options_for_field($name)
    {
        $use_def = NULL;
        $use_def = $this->_find_attribute_definition_for_field($name);
        
        // If at this point there is still no definition found or the definition
        // has no render type associated with it then create a default one.
        if($use_def == NULL || $use_def->render_type_id == NULL)
        {
            // ? Should check the data type from the table to get a better input/output type ?
            return array('validate' => NULL, 'input_type' => 'textbox',
                         'output_type' => 'text', 'width' => 30);
        }
        
        // Fetch and return the render type for the attribute being used.
        $properties = array();
        //$render = Doctrine::getTable('cbeads\attribute_render_def')->find($use_def->render_type_id);

        foreach($this->_attr_render_defs as $render_def)
        {
            if($render_def->id == $use_def->render_type_id)
            {
                $render = $render_def;
                break;
            }
        }
        
        //echo "searched: $name : "$use_def->render_type_id . "<br>";
        //$properties['name'] = $render->name;
        $properties['label'] = $render->label;
        $properties['validate'] = $render->validation;
        $properties['input_type'] = $render->input_type;
        $properties['output_type'] = $render->output_type;
        $properties['width'] = $render->width;
        $properties['height'] = $render->height;
        
        return $properties;   
    }


    // Private function. This function returns the render options for the requested
    // render type.
    // name: the name of the render type to get the options for.
    // Returns an array of render options if there is a match. Otherwise it returns 
    // just FAlSE.
    private function _get_render_options_by_name($name)
    {
        $properties = array();
        $render = Doctrine::getTable('cbeads\attribute_render_def')->findOneByName($name);;
        if(!$render) return FALSE;
    //echo "found render def for $name";
        $properties['label'] = $render->label;
        $properties['validate'] = $render->validation;
        $properties['input_type'] = $render->input_type;
        $properties['output_type'] = $render->output_type;
        $properties['width'] = $render->width;
        $properties['height'] = $render->height;

        return $properties;
    }


    // Returns the attributes used for rendering a field by merging the system defined render
	// options for a fieldname with the array of render properties supplied for this field (if any).
    // fieldname: the name of the field to get the render options for.
    // properties: any properties supplied for this field. It can be empty.
    // Returns an array of options to use in rendering the field.
    private function _get_merged_field_render_options($fieldname, $properties = array())
    {
        // Obtain the render options for fields that are not relationships (relationships are 
        // handled differently)
        if(empty($properties['is_relationship']))
        {
            // Check if an 'options' field exist. This indicates that this field is an enumeration field.
            if(!empty($properties['options']) && is_array($properties['options']))
            {
                if(empty($properties['required'])) $properties['items'][''] = '-select-';   // Provide an item to act as NULL if this field is not required.
                foreach($properties['options'] as $key => $value)
                {
                    $properties['items'][$value] = $value;
                }
                if(empty($properties['input_type'])) $properties['input_type'] = 'select';
                unset($properties['options']);
            }
        
            // Get the render options for this field. If a render_type is specified in the
            // properties array then use it.
            if(isset($properties['render_type']))
            {
                $render_options = $this->_get_render_options_by_name($properties['render_type']);
                if($render_options == FALSE)
                    $render_options = $this->_get_render_options_for_field($fieldname);
            }
            else
            {
                $render_options = $this->_get_render_options_for_field($fieldname);
            }
        }

        // Merge the values in the properties array with those in the render_options array.
        // Values in the properties array will override values in the render_options.
        // print "\n\nFor '$name'\nRender Options:\n";
        // nice_vardump($render_options);
        // print "Properties passed to renderer:\n";
        // nice_vardump($properties);
        foreach($properties as $key => $value)
        {
            if($value !== NULL )
                $render_options[$key] = $value;
        }

        // print "Final render options:\n";
        // nice_vardump($render_options);
        // Remove elements that are NULLs or empty strings. Avoids having to check for isset
        // AND !NULL, plus less junk to store in session once the form is serialized.
        $render_options = array_filter($render_options, array($this, '_remove_null_or_empty_string'));

        return $render_options;
    }

    // Checks tables options and uses values in it as default values in the form options.
    // table: the table options to inherit from.
    // form: the form options to apply table options to if they haven't already been defined.
    // Returns an array of options that has inherited values from the table options.
    private function _inherit_table_options($table, $form)
    {
        $opts = $form;
        if(!empty($table['column_order']) && is_array($table['column_order']) && !isset($form['order'])) // Can use table column order for form field order
            $opts['order'] = $table['column_order'];
        return $opts;
    }

    // The 'form' variable inherits everything from the 'default' variable, except for fields
    // that exist in both.
    // default: the 'default' form options.
    // form: the options of a form that should inherit values from 'default'.
    // Returns an the 'form' options variable with the values inherited from 'default'.
    private function _inherit_default_form_options($default, $form)
    {
        If(!is_array($default) || !is_array($form)) return $form;   // Only dealing with arrays.

        // Cycle through all all values in default and add them to form if form has no matching key value.
        foreach($default as $key => $val)
        {
            if(isset($form[$key]))
            {
                if(is_array($val) && !in_array($key, array('order', 'controller_order'))) // Must inherit array elements, except for elements that specify orders list.
                    $form[$key] = $this->_inherit_default_form_options($val, $form[$key]);
            }
            else // no matching key
            {
                $form[$key] = $val;
            }
        }
            
        return $form;
    }


    // Private function. Constructs an array containing the success result
    // and any message to be returned to the caller of a function.
    // success: the success of the function operation. Either TRUE or FALSE.
    // msg: a message to be returned. Can be left empty to just return NULL.
    private function _result($success = TRUE, $msg = NULL)
    {
        return array('success' => $success, 'msg' => $msg);
    }

    // Private function. Checks if a column/field is for a private file database
    // column. Such columns are of the format [name]_filedata. If there is a matching
    // column/field called [name] then [name]_filedata is positively a file storage
    // database column. Such columns should not be shown in tables and forms.
    // columns: array of columns/fields used for table/form.
    // name: the name of the column/field to check.
    // Returns true if this column/field is for storing files. Otherwise returns FALSE.
    private function _is_privatefile_column($columns, $name)
    {
        $pos = strrpos($name, '_filedata');
        if($pos !== FALSE && $pos != 0 && in_array(substr($name, 0, $pos), $columns)) 
            return TRUE;
        return FALSE;
    }

    // Checks if a column/field is for public files. Such columns store the filepath of
    // the file stored on the server.
    // columns: array of columns/field names used for table/form.
    // field: the name of the field to test.
    // Returns true if the field is for a public file, otherwise false.
    private function _is_publicfile_column($columns, $field)
    {
        // The field definition will tell us if the file is puplic or private.
        $def = $this->_find_attribute_definition_for_field($field);
        if($def === NULL) return FALSE;
        if($this->_is_privatefile_column($columns, $field)) return FALSE;
        return ($def->db_type == "FILE") ? TRUE : FALSE;
    }

    // Used by PHP array_filter() to remove any element that is NULL or an empty string.
    // This ignores empty arrays as those are needed sometimes. (2010/06/24)
    // value: the value to test
    // Returns true if the value is to be kept (value is not NULL and not an empty string).
    // Otherwise it returns false to remove the value.
    private function _remove_null_or_empty_string( $value ){
        if(!is_array($value))
            return $value !== NULL && $value !== "";
        return TRUE;
    }

    // Takes a validation type value and converts it to a regex string.
    // value: the name of a validation type. 
    // Returns the regex for the matched validation type. If there is 
    // no match then an empty string is returned, unless the passed
    // string is already a regex string, in which case it will be returned.
    private function _make_regex($value)
    {
        // If a regex string (format /..../) is passed then just return it.
        if(preg_match('/^\/.+\/$/', $value))
        {
            return $value;
        }

        foreach($this->_validation_types as $type)
        {
            if(strtoupper($type->name) == strtoupper($value))
                return $type->regex;
        }

        return "";
    }


    // Function that simply converts a form error array into a string that can be
    // printed.
    // errors: array of errors. eg: array(field => 'msg1', field2 => 'msg', field3 => array(msg1, msg2, msg3)) 
    // Returns the errors as a string.
    private function form_errors_to_string($errors)
    {
        if(empty($errors)) return "";
        $str = "";
        foreach($errors as $field => $val)
        {
            if(is_array($val) && !empty($val))  // For multiple errors on a field.
            {
                $str .= "Field $field has these error(s):<br>";
                $str .= implode(', ', $val);
            }
            else    // For single field/form error.
            {
                $str .= $val .'<br>';
            }
        }
        return $str;
    }

    private function _clear_old_form_instances($ui_data)
    {
        $formclass_instances = $this->_CI->session->userdata('formclass_instances');
        if($formclass_instances == NULL) return;
        $form_ids = array_keys($formclass_instances);
        $limit = 10;
        if(count($form_ids) > $limit)
        {
            sort($form_ids);
            $form_ids = array_reverse($form_ids);   // Is sorted from newest to oldest (form ids are timestamps)
            //nice_vardump($form_ids);
            for($i = count($form_ids) - 1; $i > $limit; $i--)
                unset($formclass_instances[$form_ids[$i]]);
            //echo "<br>There are now " . count($formclass_instances) . " in the session";
            //echo ". Total session size is about : " . strlen(serialize($formclass_instances)) . '<br><br>';
            $this->_CI->session->set_userdata('formclass_instances', $formclass_instances);
        }
        //nice_vardump($ui_data);
        // Clean up the ui data array as well. When a top ui id element exists and none of 
        // the form info elements point to an existing form, then the top ui element can be
        // removed.
        $top_ui_ids = array_keys($ui_data);
        foreach($top_ui_ids as $id)
        {
            $form_exists = false;
            foreach($ui_data[$id] as $form_info)
            {
            //nice_vardump($form_info);
            //echo "does form id " .$form_info['form_id'] . ' exist?<br>';
                if(in_array($form_info['form_id'], $form_ids)) 
                {
                    //echo "YES<br>";
                    $form_exists = true;
                }
            }
            if(!$form_exists)
            {
                unset($ui_data[$id]);
            }
        }
        //nice_vardump($ui_data);
        $this->_CI->session->set_userdata('ui_data', $ui_data);
    }

    // Removes a given form from the user's session.
    // form_id: id of the form data to remove.
    // Return true on success, otherwise false.
    private function _remove_form($form_id)
    {
        $formclass_instances = $this->_CI->session->userdata('formclass_instances');
        if($formclass_instances !== NULL) 
        {
            $form_ids = array_keys($formclass_instances);
            if(!isset($formclass_instances[$form_id])) return FALSE;
            unset($formclass_instances[$form_id]);
            $this->_CI->session->set_userdata('formclass_instances', $formclass_instances);
            return TRUE;
        }
        return FALSE;
    }

    // Removes all forms from a given top level ui id. This deals with cases where a form 
    // is submitted that has child forms (composite forms).
    private function _remove_composite_forms($ui_data)
    {
        $formclass_instances = $this->_CI->session->userdata('formclass_instances');
        foreach($ui_data[$this->_top_ui_id] as $cur_ui_data)
        {
            unset($formclass_instances[$cur_ui_data['form_id']]);
        }
        unset($ui_data[$this->_top_ui_id]);
        $this->_CI->session->set_userdata('formclass_instances', $formclass_instances);
        $this->_CI->session->set_userdata('ui_data', $ui_data);
    }
    
    // Puts query uri parameters into a / delimited string to be used in urls.
    // params: the array of parameters as key->value pairs. value can itself be an array of values
    // Returns the parameters put into a / delimited string or an empty string if no queries.
    private function _construct_query_url($params)
    {
        $string = "";
        foreach($params as $key => $val)
        {
            if(is_array($val))
            {
                $string .= implode('/', $val);
            }
            else
            {
				if($val !== NULL && $val !== '')
					$string .= '/'.$key.'/'.$val;
				else
					$string .= '/'.$key;
            }
        }
        return $string;
    }

    // Used by the table renderer to format the value of a field for display in the table. It
	// checks if a render type or output type were set for this column. If not, it tries to get render options
	// using the fieldname. By default everything will be just displayed as text.
    // fieldname: the name of the field to look at for formatting the data.
    // value: the value to format
	// column_options: options set for this column.
    // Returns html containing the formatted value.
    private function _format_table_data($fieldname, $value, $column_options = array())
    {
        $html = "";
		$type = NULL;
		$opts = NULL;
		// Find the output type to use. Either it is explictly set in the column options
		if(!empty($column_options['output_type']) && isset($this->_output_controls[$column_options['output_type']]))
		{
			$type = $column_options['output_type'];
		}
		// Or the render type is set in the column options
		else if(!empty($column_options['render_type']))
		{
			$opts = $this->_get_render_options_by_name($column_options['render_type']);
			if($opts !== FALSE) $type = $opts['output_type'];
		}
		// Still now output type. Get the render type associated with the field name.
		if($type === NULL)
		{
			$opts = $this->_get_render_options_for_field($fieldname);
			$type = $opts['output_type'];
		}
		
		$ctrl = 'text';
		// Convert values from a database representation to something the user can read. This applies to certain data types.
		$value = $this->format_value_for_output($value, $opts['input_type']);
		//echo $type;

		// The output type is then used to figure out if a formatting function must be called and what
		// output control should be used for displaying this formatted value.
		if(isset($this->_output_controls[$type]))
		{
			if(isset( $this->_output_controls[$type]['control']))
				$ctrl = $this->_output_controls[$type]['control'];
			if(isset($this->_output_controls[$type]['format_function']) && is_callable($this->_output_controls[$type]['format_function']))
			{
				$value = call_user_func($this->_output_controls[$type]['format_function'], array('value' => $value, 'name' => $fieldname));
			}
		}
		
		switch(strtolower($ctrl))
		{
			case 'text':
				$html = $this->format_as_text(array('value' => $value));
				break;
			case 'html':
				$html = $value;
				break;
			case 'none';
				$html = '';
				break;
			default:
				$html = cbeads_error_message("ERROR: Could not match display type for this field ($type)");
		}
        return $html;
    }

    // Processes a file download request for a form.
    // object_id: the object id that the file is attached to.
    // model: the model to use
    // field: the model field name that the download request was made for
    // data: the model data as provided to render_as_form
    // Returns nothing, but prints out headers that force a file download on the browser side
    // if the object id is valid.
    private function _process_file_download($object_id, $field, $model, $data)
    {
        $obj = Doctrine::getTable($model)->find($object_id);
        if($obj !== FALSE)
        {
            if(isset($data['columns'][$field.'_filedata'])) // private file
            {
                $t_name = $obj->$field;
                $t_fieldname = $field . '_filedata'; 
                force_download($t_name, $obj->$t_fieldname);
            }
            else    // public file
            {
                $filedata = file_get_contents(FCPATH . $this->_uploads_directory . '/' . $obj->$field);
                $t_name = $this->_format_filename($obj->$field);
                force_download($t_name, $filedata);
            }
        }
    }

    // Processes uploaded files for when a form is submitted.
    // form: reference to the form_builder object so that data can be added to it.
    // obj: reference to the object that the form submit is working on.
    // data: the data for the model being used.
    // Returns true if all went well, otherwise false.
    private function _process_file_uploads(&$form, &$obj, $data)
    {
        foreach($_FILES as $field => $info)
        {
            if($info['error'] == UPLOAD_ERR_NO_FILE)    // No file uploaded for this field.
            {
                // See if there is a POST field called _delete_file_for_field_[fieldname], which indicates
                // that a file for this field was deleted.
                if(!empty($_POST["_delete_file_for_field_$field"]))
                {
                    // If this field can be NULL and has an existing value then the previously
                    // uploaded file must be deleted.
                    if(empty($data['columns'][$field]['required']) && $data['columns'][$field]['value'] !== NULL)
                        $this->_delete_file_for_field($field, $obj, $data);
                }
                continue;
            }
            if($info['error'] != UPLOAD_ERR_OK)
            {
                $form->addPreTableHTML(cbeads_error_message('Could not upload file: ' . $info['name']));
                return FALSE;
            }
            if(isset($data['columns'][$field]))
            {
                // The field definition will tell us if the file is puplic or private.
                $def = $this->_find_attribute_definition_for_field($field);
                if($def->db_type == "FILE")
                {
                    if(!is_dir(FCPATH . $this->_uploads_directory))
                    {
                        if(!mkdir(FCPATH . $this->_uploads_directory, 0755, TRUE))
                        {
                            echo cbeads_error_message("Could not create uploads directory!");
                            return FALSE;
                        }
                    }
                    // Get the md5 of the file and append it to the file name to avoid collisions between files of the same name.
                    $md5 = $this->_hash_file_uploads ? md5_file($info['tmp_name']) : "";
                    if(move_uploaded_file($info['tmp_name'], FCPATH . $this->_uploads_directory . '/' . $info['name'] . $md5) === FALSE)
                    {
                        $form->addPreTableHTML(cbeads_error_message('File: ' . $info['name'] . ' was not uploaded.'));
                        return FALSE;
                    }
                    else
                    {
                        if(!empty($obj->$field))    // If a previous file was uploaded then need to remove it.
                        {
                            if(unlink(FCPATH . $this->_uploads_directory .'/' . $obj->$field) === FALSE)
                            {
                                echo cbeads_warning_message('Failed to remove previously uploaded file: <br>'. FCPATH . 'uploads/'.$obj->$field);
                            }
                        }
                        $obj->$field = $info['name'] . $md5;
                        echo cbeads_success_message('File: ' . $info['name'] . ' was uploaded.');
                    }
                }
                elseif($def->db_type == "PFILE")
                {
                    if(!is_uploaded_file($info['tmp_name']))        // Makes sure file was uploaded via POST (i assume it checks the ownership of the file)
                    {
                        $form->addPreTableHTML(cbeads_error_message('Could not upload file: ' . $info['name'] . '<br>Failed is_uploaded_file check!'));
                        return FALSE;
                    }
                    $filename = $info['tmp_name'];
                    $handle = fopen($filename, 'rb');
                    $filedata = fread($handle, filesize($filename));
                    fclose($handle);
                    $obj->$field = $info['name'];           // Stores the filename.
                    $fd = $field.'_filedata';
                    $obj->$fd = $filedata;                  // Stores the actual file contents
                }
            }
        }
        return TRUE;
    }


    // Deletes a file for a form field.
    // field: the name of the field for which the file should be deleted.
    // obj: reference to a record instance.
    // data: the model data as provided to render_as_form.
    private function _delete_file_for_field($field, &$obj, $data)
    {
        if(isset($data['columns'][$field.'_filedata'])) // private file
        {
            $t_fieldname = $field . '_filedata';
            $obj->$t_fieldname = NULL;
            $obj->$field = NULL;
            $obj->save();
        }
        else    // public file
        {
            unlink(FCPATH . $this->_uploads_directory . '/' . $obj->$field);
            $obj->$field = NULL;
            $obj->save();
        }
        return true;
    }

    // Processes input values used for create and edit forms. Based on the field name,
    // the value and the render options associated with the field, the value to apply 
    // may be changed.
    // obj: reference to the database object to apply the value to.
    // field: the name of the field in the object.
    // value: a value to apply to the field in the object. May be formatted.
    // options: array of options for the specified field. Must include the key 'required'.
    private function _process_form_input_value(&$obj, $field, $value, $options)
    {
        switch($options['attribute_type'])
        {
            case "password":
                if($value != '')    // Save password only if value is provided.
                    $obj->$field = $value;
                break;
            default:
                if($value == '' && empty($options['required'])) // If not required and no value then can just set the field value to NULL.
                    $obj->$field = NULL;
                else
                {
					// What formatting has to be done to the input value depends on the input control this field is using.
					$render_options = $this->_get_merged_field_render_options($field, $options);
                    $obj->$field = $this->format_value_for_input($value, isset($render_options['input_type']) ? $render_options['input_type'] : '');
                }
        }
    }

    // Changes the value retrieved from a database to a value useable by a user (ie for output).
    // The formatting change depends on the type of value.
    // value: the value to format.
    // value_type: identifies what input render type to associate the value with. If a value is treated 
    //             as a date for input, it is assumed it is also treated as a date for output and thus
    //             can be formatted.
    // Returns the formatted value if formatting was possible.
    public function format_value_for_output($value, $value_type)
    {
        $val = $value;
		//echo $value . ":" . $value_type . '<br />';
        switch($value_type)
        {
            case 'date':
                if($this->_db_date_format != $this->_date_format)
                {
                    $date = DateTime::createFromFormat($this->_db_date_format, $value);
                    if($date) $val = $date->format($this->_date_format);
                }
                break;
            case 'daterange':       // Will expect a string in format date_start - date_end. The two date components will be formatted.
                echo 'TODO: Daterange formatting.<br>';
                break;
			case 'hour_of_day_picker':
				$val = $this->_format_from_db_hour_of_day($val);
				break;
        }
        return $val;
    }
    
    
    public function format_value_for_input($value, $value_type)
    {
        $val = $value;
		//echo $value_type . ':' . $value . '<br/>';
        switch($value_type)
        {
            case 'date':
                if($this->_db_date_format != $this->_date_format)
                {
                    $date = DateTime::createFromFormat($this->_date_format, $value);
                    if($date) $val = $date->format($this->_db_date_format);
                }
                break;
            case 'daterange':
                echo 'TODO: Daterange formatting.<br>';
                break;
			case 'hour_of_day_picker':
				$val = $this->_format_to_db_hour_of_day($val);
				break;
        }
        return $val;
    }
    
	
	
	
	
    // Removes the md5 hash part from the filename (used for public files) if needed.
    private function _format_filename($filename)
    {
        return ($this->_hash_file_uploads === FALSE) ? $filename : substr($filename, 0, strlen($filename) - 32);
    }
	
    // Generates html that allows a file to be deleted from the server without submitting the form.
    // This should be used for edit forms.
    private function _display_file_delete($fieldname, $filename)
    {
        if($filename == '') return;
        $def = $this->_find_attribute_definition_for_field($fieldname);
        if($def->db_type == 'FILE') $filename = $this->_format_filename($filename);
        $js = <<<JS
        if(!confirm("If you submit the form, this file will be deleted. Continue?")) return;
        var html = "<input type=\"hidden\" value=\"delete\" name=\"_delete_file_for_field_$fieldname\" />";
        $("input[name=\"_form_submit\"]").after(html);
        $("#$fieldname\_actions_container").remove();
        $("#$fieldname\_image_container").remove();
        $("#$fieldname\_file_browser").removeAttr("onclick");
JS;
        //$html = "<span onclick='".$js."'>Delete</span>";
        $html = "<img src='".base_web_url() . "cbeads/images/delete_small.png' onclick='".$js."' title='Click to indicate that you want this existing file deleted' alt='Delete'/>";
        return $html;
    }

	
	// New Formatting/Display Functions. These are used by the table and form renderers to
	// change the formatting of the data and to add html as needed. The output is then 
	// included in the generated html. The functions are public so they can be used to
	// generate the same data formatting and display as the Renderer (ie consistency).
	
	
	// Formats some text for display on a web page. This means replacing newlines with <br>
    // elements. Note, this function is only for simple text that contains new lines.
	public function format_as_text($opts)
	{
		$text = $opts['value'];
		return preg_replace(array('/\n/','/\r\n/'), '<br>', $text);
	}
	
	// Formats a email address to display in a link tag (opens email client if clicked)
	// The options array must include:
	//   value: the email address
	// Optional:
	//   label: the label to use in the email link (the part seen by the user). If not provided
	//          will use the email address as the label.
	// Returns a email link.
	public function format_as_email_link($opts)
	{
		$email = $label = $opts['value'];
		if(!empty($opts['label'])) $label = $opts['label'];
		return "<a href='mailto:$email'>$label</a>";
	}
	
	// Generates a link to download a file. This is used stand alone or by edit forms (allowing the previously uploaded file to be downloaded.
	// The options must include:
    // name: the name of the field that the file belongs to.
    // value: the name of the file to link to.
    // Returns a html link for downloading the file. If no filename provided then returns an empty string.
    public function format_as_file_download($opts)
    {
		$fieldname = $opts['name'];
		$filename = $opts['value'];
        if($filename == '') return '';
        $def = $this->_find_attribute_definition_for_field($fieldname);
        if($def->db_type == 'FILE')
            $html = anchor($this->_CI->uri->uri_string() . '/_form_download/' . $fieldname, $this->_format_filename($filename));
        else
            $html = anchor($this->_CI->uri->uri_string() . '/_form_download/' . $fieldname, $filename);
        return $html;
    }
    
	// Formats a image file to be displayed as an image tag.
	// The opts must include:
	// name: the name of the field containing the filename.
	// value: the name of the file.
	// Returns a img tag displaying the image file. If no filename provided returns an empty string.
	public function format_as_image($opts)
	{
		$fieldname = $opts['name'];
		$filename = $opts['value'];
		if($filename == '') return '';
		$render_def = $this->_get_render_options_for_field($fieldname);
        $html = "<img src='" . base_web_url() . $this->_uploads_directory . "/$filename' alt='" . $this->_format_filename($filename) . "' ";
        if(!empty($render_def['width'])) $html .= "width='" . $render_def['width'] . "' ";
        if(!empty($render_def['height'])) $html .= "height='" . $render_def['height'] . "' ";
        $html .= " />";
    
        return $html;
    }
	
	// Formats a hex colour value to be displayed as the hex colour and a box filled with the specified colour.
	// The opts must include: 
	// value: the hex colour value.
	// Returns the hex value with a box filled with the colour. If no colour then returns 'No Value'
	public function format_as_colour_and_value($opts)
	{
		$colour = $opts['value'];
		if(!empty($colour))
            return "$colour <span style='background-color: $colour; border: 1px solid black;'>&nbsp&nbsp&nbsp</span>";
        return "No Value";
	}
    
	// Formats a binary value (1/0) into a 'No' or 'Yes'.
	// The opts must include:
	// value: the binary value (1 or 0).
	// Returns 'No' when the value is 0, and 'Yes' when it is 1.
	public function format_as_yesno($opts)
	{
		return $opts['value'] == 0 ? 'No' : 'Yes';
	}
	
	// Formats a binary value (1/0) into a 'True' or 'False'.
	// The opts must include:
	// value: the binary value (1 or 0).
	// Returns 'False' when the value is 0, and 'True' when it is 1.
	public function format_as_truefalse($opts)
	{
		return $opts['value'] == 0 ? 'False' : 'True';
	}
	
	// Formats a time string into a string containing just the hour component,
	// as either 24 hour time (0 to 23) or as am/pm, based on the time 
	// format set.
	// The opts must include:
	// value: a time value in the format specified by database_time_format.
	// Returns the hour component of the time value formatted as specified. If 
	// formatting fails, will return the value as is.
	public function format_as_hour_of_day($opts)
	{
		$new_format = "";
		$allow = array('a','A','g','G','h','H', ' ');
		for($c = 0; $c < strlen($this->_user_time_format); $c++)
		{
			$char = $this->_user_time_format[$c];
			if(in_array($char, $allow))
				$new_format .= $char;
		}
		$time = DateTime::createFromFormat($this->_user_time_format, $opts['value']);
		if($time)
			return $time->format($new_format);
		else	// Unable to create a time object from the value.
		{
			// Maybe the value is in the db_time_format.
			$time = DateTime::createFromFormat($this->_db_time_format, $opts['value']);
			if($time) return $time->format($new_format);
		}
		return $opts['value'];
	}
	
	
	
	// Functions for formatting data to and from database representations.
	// Sometimes the way the database stores a value is not how it should be displayed to
	// a user or how a control expects it to be as. Therefore there are two functions
	// for each input control. One to format the entered value to something that the
	// database will accept. The other to format the stored value to something the user or
	// control wants it as.
	// Eg: Dates can be stored in YYYY-MM-DD format in the db but might need to be 
	// displayed to the user in the format DD-MM-YYYY or DD-MM-YY or MM-DD-YY.
	
	// The input value from the hour_of_day time value must be converted to a format the db expects (eg HH:MM:SS).
	private function _format_to_db_hour_of_day($input_value)
	{
		$val = $input_value;
		if($this->_db_time_format != $this->_user_time_format)
		{
			$time = DateTime::createFromFormat($this->_user_time_format, $input_value);
			if($time) 
			{
				$val = $time->format($this->_db_time_format);
			}
		}
		return $val;
	}
	
	// The hour_of_day controller on the form expects a time value to be in the user format.
	private function _format_from_db_hour_of_day($db_value)
	{
		$val = $db_value;
		if($this->_db_time_format != $this->_user_time_format)
		{	//echo $this->_db_time_format . ' to ' . $this->_user_time_format;
			$time = DateTime::createFromFormat($this->_db_time_format, $db_value);
			if($time) 
			{
				$val = $time->format($this->_user_time_format);
			}
		}
		return $val;
	}
	
	
	
    
    // Converts a date format in php to a date format in jquery. Only characters that 
    // have a jquery equivalent will be converted.
    // For PHP these are accepted characters: http://au2.php.net/manual/en/function.date.php
    // For jquery these are accepted characters: http://docs.jquery.com/UI/Datepicker/formatDate
    // The function will convert these characters: d, D, j, l (lowercase L), z, F, m, M, n, Y, y
    public function convert_php_date_format_to_jquery_date_format($format)
    {
        $replace = array(
            'd' => 'dd',  // day of month, with leading 0    (01 - 31)
            'j' => 'd',   // day of month, with no leading 0  (1 - 31)
            'D' => 'D',   // short form day name    (Mon - Sun)     
            'l' => 'DD',  // long form day name     (Sunday - Saturday)  (Not sure if jquery starts from Monday)
            'z' => 'o',   // day of year (0 - 365). (Not sure if jquery needs this to start from 1)
            'n' => 'm',   // month of year, with no leading 0 (1 - 12)
            'm' => 'mm',  // month of year, with leading 0 (01 - 12)
            'M' => 'M',   // short form month name (Jan - Dec)
            'F' => 'MM',  // long form month name (January - December)
            'y' => 'y',   // year using 2 digits
            'Y' => 'yy',  // year using 4 digits
        );
            
        $new = "";
        $chars = str_split($format);
        $in_literal_text = FALSE;
        for($i = 0; $i < count($chars); $i++)
        {
            if($chars[$i] == '\\') // escaped characters need to be put into single quotes and without the backslash.
            {
                $i++;   
                if($i < count($chars)) 
                {
                    if($in_literal_text == FALSE)
                    {
                        $in_literal_text = TRUE;
                        $new .= '\'';
                    }
                    $new .= $chars[$i ];
                }
            }
            else    // None escaped characters.
            {
                if($in_literal_text)    // Put closing single quote if escaped characters were previously added.
                {
                    $in_literal_text = FALSE;
                    $new .= '\'';
                }
                if(isset($replace[$chars[$i]]))     // If it can be replaced, do so.
                {
                    $new .= $replace[$chars[$i]];
                }
                else    // Other characters that cannot be replaced.
                {
                    $new .= $chars[$i];
                }
            }
        }
        
        if($in_literal_text)    // Ensure that a previously opened single quote is closed.
        {
            $in_literal_text = FALSE;
            $new .= '\'';
        }
        
        return $new;
    }

    

    // Property set/get functions
    
    // Set the database date format so that renderer knows how to format date inputs.
    // If no format value is supplied, returns the current formatting set.
    public function database_date_format($format = NULL)
    {
        if(!empty($format))
            $this->_db_date_format = $format;
        else
            return $this->_db_date_format;
    }
    
    // Set the date format to use by the renderer when outputting dates (in forms and tables).
    // If no format value is supplied, returns the current formatting set.
    public function user_date_format($format = NULL)
    {
        if(!empty($format))
            $this->_date_format = $format;
        else
            return $this->_date_format;
    }
    
	// Set/get the time format used by the database.
	// format: when provided with a value, sets the database time format.
	// Returns the current format when no value is passed to the function.
    public function database_time_format($format = NULL)
	{
		if(!empty($format))
			$this->_db_time_format = $format;
		else
			return $this->_db_time_format;;
	}
	
	// Set/get the time format used by the user.
	// format: when provided with a value, sets the user time format.
	// Returns the current format when no value is passed to the function.
    public function user_time_format($format = NULL)
	{
		if(!empty($format))
			$this->_user_time_format = $format;
		else
			return $this->_user_time_format;;
	}
	
	
    // Searches the POST array to find what form action was made.
    private function get_form_submit_action()
    {
        foreach($_POST as $key => $val)
        {
            if(strpos($key,  '_form_action_') !== FALSE)
                return substr($key, 13);
        }
    }

	// Indicates if a form was rendered or not. Used in cases where a function renders a table, and 
	// has to display additional html when the form or table is visible/not-visible.
	// Call this function after the render_as_[] method has been called. (2011/09/01)
	// Returns TRUE if the form was rendered, otherwise FALSE.
	public function was_form_rendered()
	{
		return $this->_rendered_form;
	}
	
} 