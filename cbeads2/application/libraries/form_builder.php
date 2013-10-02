<?php
/*
Google Code Project Hosting - http://code.google.com/p/php-form-builder-class/
Google Groups - http://groups.google.com/group/php-form-builder-class/
*/

/* CodeIgniter Integration Changelog:
 * 
 * 2010/04/13 - Markus
 * - Added a variable that stores the absolute URL to the includes folder.
 *   The relative includes path is now set to php-form-builder-class/includes
 *   and should not need to be changed unless the folder is moved.
 * - Changed all $_SESSION references to use the CI session instead. (This affected
 *   the validate and render functions.)
 *
 * 2010/04/14 - Markus
 * - Added code to the jsCycleElements function that performs client side JS validation 
 *   on text and textarea fields.
 * - Added a variable ($validateErrorMsgFormat)for specifying the validate error message 
 *   format.
 * 
 * 2010/04/27 - Markus
 * - Factored out some common code from the elementsToString and render functions into
 *   a function called addAttributes(). This means that buttons can now be aligned as
 *   desired (the old code is still there for reference).
 *
 * 2010/05/01 - Markus
 * - Added addText() function to allow just text fields to be added. This would mainly
 *   be used mainly for view forms.
 * - Added code to test for 'static' control type in elementsToString function to
 *   display text.
 * - Modified phpCycleElements() so that when testing if a required value has been provided
 *   that 0 and "0" are accepted as actual inputs.
 *
 * 2010/05/03 - Markus
 * - Some major modifications to the elementsToString function:
 * - The map array now accepts values specifying the width of each cell in a row as well assign
 *   the label position. An example:
 *   "map" => array(
 *               array(2, 'inline', 'left', '10%', '200', '100', '0'), 
 *               array(2, 'inline', 'center', '10%', '20%', '10%', 'auto'),
 *               array(2, 'inline', 'left', '10%', '20%', '10%', ''),
 *   )
 *   This will result in the first row containing labels in line with the fields. The labels
 *   will be to the left of the field. So for two fields, there will be 4 table cells in total.
 *   The first cell with have a width of 10%, the second will be 200 pixels, the third 100 
 *   pixels and the last cell has the remaining width (can use '0', 'auto' or '' for that purpose).
 *   If an actual value is given for the last field, and the total width of all fields does not
 *   match the full width of the table if specified, then all the widths are adjusted by the browser
 *   to fit the full width. Keep that in mind when setting the widths.
 *   When no widths are specified, as in this example:
 *   "map" => array(
 *                array(2, 'above', 'left'),
 *                array(3, 'above', 'center')
 *   )
 *   then the cell widths are calculated as normal.
 *   The allowable label positions are 'above', 'inline', 'below' and 'none' (prints no label and
 *   no cell!).
 *   The allowable label alignment values are 'left', 'center', 'right' for when labels are above
 *   or below fields, and 'top', 'bottom', 'center' for when the labels are inline.
 * - The part that created the labels has been moved into a function that is called before
 *   or after the fields are created depending on the label position set! 
 *
 * 2010/05/04 - Markus
 * - Added support for setting label alignment for inline labels.
 *
 * 2010/05/05 - Markus
 * - Modified the phpCycleElements functions so that it can report multiple errors. Ie if two fields
 *   are required and no values provided then it will provide two error messages. 
 * - Errors are now stored in an array as field_name => (error_string | array(error_strings)) pairs.
 * - The validate function was changed to allow a function callback to be passed to it. This callback
 *   allows custom validation to be performed on the received data. If the function returns an error
 *   array, the existing values are preserved for use when generating the form again.
 * - Added function error_string to return all errors as a string ready for printing.
 *
 * 2010/05/06 - Markus
 * - Adjusted the validate function to allow callbacks to class methods (either a static method or 
 *   method of an object instance).
 *
 * 2010/05/14 - Markus
 * - NOTE: ALWAYS serialize objects when saving them using CI Session class, as the Session class 
 *   cannot handle objects!
 *
 * 2010/05/16 - Markus
 * - Modified the render function by adding some additional javascript code that will run the form
 *   submission function only when skip_onsubmit is set to false. Needed for having cancel buttons 
 *   on forms.
 *
 * 2010/06/06 - Markus
 * - Modified the 'select' creation part in elementsToString so that it add '[]' to the name of the
 *   element if there is more than one item to choose from and the multiple flag has been set.
 *   PHP doesn't like to receive fields with the same name via POST. It ends up only seeing the 
 *   last item which is no good.
 *   
 * 2010/06/07 - Markus
 * - Added function addPreTableHTML to allow html to be set outside of the form table to act as 
 *   This was needed for displaying breadcrumbs because it isn't suitable to have in a table.
 *   Edited render() to check for elements of this type.
 * - Added function get_all_elements_for_rendered_form which will return all elements stored for
 *   already serialized forms. This can be used to find out what elements a form had. 
 * 
 * 2010/06/25 - Markus
 * - Tooltip icon path now uses 'includesAbsoluteWebPath'.
 * - Changed the date format to yy-mm-dd because that is what the database wants it to be in!
 *
 * 2010/07/02 - Markus
 * - added onchange as allowable html attribute for text, textarea and radio input types.
 * - added attributes: form_js, pre_init_js and post_init_js which can be set when creating a 
 *   form. form_js contains javascript code will be put into a script tag. This is where callback
 *   functions should go into. pre_init_js contains code to be run before any other form controls
 *   are initialised. post_init_js is run after all form controls are initialised.
 *   As such the elementsToString() was modified to deal with these new attributes.
 *
 *
 * 2010/08/11 - Markus
 * - Modified addFile to accept a value parameter. This value can then store the filename of 
 *   a file that has previously been uploaded (for use in edit/view/delete forms). The value 
 *   can also contain html, such as a link to download the file.
 * - In elementsToString() modified the file creation code to add some text below the file 
 *   input control to display if there is already a file associated with this field.
 *
 * 2010/08/12 - Markus
 * - Modified render() to not make the table span across the whole screen.
 * - Modified elementsToString() by removing most width and height styling occurances when creating controls.
 *   That will now allow the widths and heights to be specified externally.
 *
 * 2010/08/14 - Markus
 * - Modified file input generation in elementsToString() so that the value attribute is not printed, but still
 *   kept in the element attributes definition for when a form doesn't validate so that everything is regenerated
 *   properly.
 * 
 * 2010/08/17 - Markus
 * - Added variable use_js in function elementsToString which is set to true if a field needs jquery loaded. In
 *   this case it was added for File fields, because they may need jquery loaded.
 * 
 * 2010/09/08 - Markus
 * - Edited elementsToString() so that for colour pickers, a element is displayed next to the textbox to show
 *   the colour of the current value. Had to edit the colourpicker setup javascript so that the colour of that
 *   element is updated when a new colour is selected.
 * 
 * 2010/09/16 - Markus
 * - Edited phpCycleElements() so that only fields of type 'text' are validated.
 *
 * 2010/12/06 - Markus
 * - Edited elementsToString() where the checkboxes are created. When checking for values in attributes['values']
 *   the in_array() was told to do a strict check which can cause issues if one is not carefull with the data types.
 *   (values in the POST array are all treated as strings it seems) Changed to non strict checking.
 * - The checkboxes section now is able to put checkboxes into multiple columns. This is useful for when there are 
 *   a lot of checkboxes. The additionalParams argument for addCheckbox can be passed a key value pair: 
 *      list_as_columns => #
 *   to specify how many columns to use. Default is 1 column.
 * - The form buttons are now told to span 2 columns and are left aligned. This should avoid them being squashed into
 *   a column when the form fields are very wide.
 *
 * 2011/01/21 - Markus
 * - The checkbox generation in elementsToString() now puts checkboxes into rows instead of having just one row. This
 *   way the result looks nicer. Although now items are displayed row-wise instead of column-wise as before.
 *
 * 2011/02/03 - Markus
 * - Update the form instance saving/loading so that now forms are serialized, then compressed (to save space)
 *   and then base64 encoded (because trying to save the compressed string isn't working properly).
 *
 * 2011/04/07 - Markus
 * - The form-builder class was updated to include a function for adding comboboxes (addCombobox). It expects all the 
 *   parameters that addSelect receives. One of the variables it looks for is 'select_only'. If set to false, the combobox 
 *   is set up to allow custom value entry. By default this value is true.
 * - elementsToString() was updated to add the html and javascript necessary for comboboxes.
 *
 * 2011/04/11 - Markus
 * - Added variable using_js to the formbuilder class. This variable can be used to force the on form onclick attribute and
 *   the on submit handler function to be generated. This may be necessary when a form button has a onclick event that sets
 *   a variable which must be checked to allow/disallow form submission.
 * - Added javscript variable abort_submit, which is used in the form's onclick function. If set to true, the form submit is
 *   disabled.
 * 
 * 2011/06/03 - Markus
 * - The elementsToString() function now checks if the element attributes specifies the 'autocomplete' capability. When
 *   specifies, elements (which should be either textboxes or textareas) will be able to autocomplete user input.
 *   In addition a id is automatically generated for each form element. This is needed to be able to assign the autocomplete
 *   capability.
 *
 * 2011/06/29 - Markus
 * - Replaced all instances of document.forms[""].elements with document.getElementById("").elements because newer versions of 
 *   IE don't allow using the form name as the index to the forms collection.
 *
 *
 * 2011/08/21 - Markus
 * - Had do upgrade to a newer of qtip because version 1 rc3, doesn't work with jquery 1.4.2. New qtip version requires a 
 *   css be included. The instantiation code differs to the previous version and has been updated. Rounded corners are no
 *   longer included (requires explicitly adding css to set rounding). As a result of the update the $tooltipBorderColor
 *   property is no longer used.
 *
 * 2011/09/20 - Markus
 * - Added hour of day picker, to let people select an hour in the day. Can be am/pm or 24 hour time depending on the
 *   the timeFormat defined via setAttributes(). Also possible to specify a range of hours that can be selected from.
 *
 */

class base {
    /*This class provides two methods - setAttributes and debug - that can be used for all classes that extend this class 
    which are form, option, element, and button.*/
    function setAttributes($params) {
        if(!empty($params) && is_array($params))
        {
            /*Loop through and get accessible class variables.*/
            $objArr = array();
            foreach($this as $key => $value)
                $objArr[$key] = $value;

            foreach($params as $key => $value)
            {
                if(array_key_exists($key, $objArr))
                {
                    if(is_array($this->$key) && !empty($this->$key))
                    {
                        /*Using array_merge prevents any default values from being overwritten.*/
                        $this->$key = array_merge($this->$key, $value);
                    }   
                    else
                        $this->$key = $value;
                }
                elseif(array_key_exists("attributes", $objArr))
                    $this->attributes[$key] = $value;
            }
            unset($objArr);
        }
    }

    /*Used for development/testing.*/
    function debug()
    {
        echo "<pre>";
            print_r($this);
        echo "</pre>";
    }
}

class form_builder extends base { 
    /*Variables that can be set through the setAttributes function on the base class.*/
    protected $attributes;              /*HTML attributes attached to <form> tag.*/
    protected $tableAttributes;         /*HTML attributes attached to <table> tag.*/
    protected $tdAttributes;            /*HTML attributes attached to <td> tag.*/
    protected $labelAttributes;         /*HTML attributes attached to <div> tag.*/
    protected $requiredAttributes;      /*HTML attributes attached to <span> tag.*/
    protected $map;                     /*Unrelated to latlng/map field type.  Used to control table structure.*/
    protected $ajax;                    /*Activate ajax form submission.*/
    protected $ajaxType;                /*Specify form submission as get/post.*/
    protected $ajaxUrl;                 /*Where to send ajax submission.*/
    protected $ajaxPreCallback;         /*Optional function to call before ajax form submission.*/
    protected $ajaxCallback;            /*Optional function to call after successful ajax form submission.*/
    protected $ajaxDataType;            /*Defaults to text.  Options include xml, html, script, json, jsonp, and text.  View details at http://docs.jquery.com/Ajax/jQuery.ajax#options*/
    protected $tooltipIcon;             /*Overrides default tooltip icon.*/
    protected $tooltipBorderColor;      /*Overrides default tooltip border color.*/
    protected $preventJQueryLoad;       /*Prevents jQuery js file from being loaded twice.*/
    protected $preventJQueryUILoad;     /*Prevents jQuery UI js file from being loaded twice.*/
    protected $preventQTipLoad;         /*Prevents qTip js file from being loaded twice.*/
    protected $preventGoogleMapsLoad;   /*Prevents Google Maps js file from being loaded twice.*/
    protected $preventTinyMCELoad;      /*Prevents TinyMCE js file from being loaded twice.*/
    protected $preventTinyMCEInitLoad;  /*Prevents TinyMCE init functions from being loaded twice.*/
    protected $noLabels;                /*Prevents labels from being rendered on checkboxes and radio buttons.*/
    protected $noAutoFocus;             /*Prevents auto-focus feature..*/
    protected $captchaTheme;            /*Allows reCAPTCHA theme to be customized.*/
    protected $captchaLang;             /*Allows reCAPTCHA language to be customized.*/
    protected $captchaPublicKey;        /*Contains reCAPTCHA public key.*/
    protected $captchaPrivateKey;       /*Contains reCAPTCHA private key.*/
    protected $preventCaptchaLoad;      /*Prevents reCAPTCHA js file from being loaded twice.*/
    protected $jqueryDateFormat;        /*Allows date field to be formatted. See http://docs.jquery.com/UI/Datepicker/$.datepicker.formatDate for formatting options.*/
	protected $timeFormat;				/*Specifies the general format of time values. */
    protected $ckeditorLang;            /*Allows CKEditor language to be customized.*/
    protected $ckeditorCustomConfig;    /*Allows CKEditor settings to be loaded through a supplied js file.*/
    protected $preventCKEditorLoad;     /*Prevents CKEditor js file from being loaded twice.*/
    protected $enableSessionAutoFill;   /*If enabled, this parameter will allow a form's elements to be populated from the session.*/
    protected $errorMsgFormat;          /*Allow you to customize was is alerted/returned during js/php validation.*/
    protected $validateErrorMsgFormat;  /*Allows the validate error message to be customised for js validation.*/
    protected $emailErrorMsgFormat;     /*Allow you to customize was is alerted/returned during js/php email validation.*/
    protected $latlngDefaultLocation;   /*Allow you to customize the default location of latlng form elements.*/
    protected $parentFormOverride;      /*When using the latlng form element with the elementsToString() function, this attribute will need to be set to the parent form name.*/
    protected $includesRelativePath;    /*Specifies where the includes directory is located using a file system path. (see changelog 2010/04/13)*/
    protected $includesAbsoluteWebPath; /*Specifies where the includes directory is located using a URL path. (see changelog 2010/04/13)*/
    protected $onsubmitFunctionOverride;/*Allows onsubmit function for handling js error checking and ajax submission to be renamed.*/
    protected $post_init_js;            // Contains code to call after the form javascript initialisation (see 2010/07/02)
    protected $pre_init_js;             // Contains code to call before the form javascript initialisation (see 2010/07/02)
    protected $form_js;                 // Contains javascript code to include with the form
    protected $using_js;                // Indicates if some javascript is being used. The form should set up a on submit handler in this case.
    
    /*Variables that can only be set inside this class.*/
    private $elements;                  /*Contains all element objects for a form.*/
    private $bindRules;                 /*Contains information about nested forms.*/
    private $buttons;                   /*Contains all button objects for a form.*/
    private $checkform;                 /*If a field has the required attribute or validate attribute (see 2010/04/14) set, this field will be set causing javascript error checking.*/
    private $allowedFields;             /*Controls what attributes can be attached to various html elements.*/
    private $stateArr;                  /*Associative array holding states.  Prevents generating array each time state form field is used.*/
    private $countryArr;                /*Associative array holding countries.  Prevents generating array each time country form field is used.*/   
    private $referenceValues;           /*Associative array of values to pre-fill form fields.*/
    private $captchaExists;             /*If there is a captcha element attached to the form, this flag will be set and force the formhandler js function to be called when the form is submitted.*/
    private $focusElement;              /*Sets focus of first form element.*/
    private $tinymceIDArr;              /*Uniquely identifies each tinyMCE web editor.*/
    private $ckeditorIDArr;             /*Uniquely identifies each CKEditor web editor.*/
    private $hintExists;                /*If one or more form elements have hints, this flag will be set and force the formhandler js function to be called when the form is submitted.*/
    private $emailExists;               /*If one or more form elements of type email exist, this flag will be set and force the formhandler js function to be called when the form is submitted.*/
    
    /*Variables that can be accessed outside this class directly.*/
    public $errorMessages;              /*Contains array of human readable error message set in validate() method. (see 2010/05/05) */
    //private $errorMsg;                    /*Contains a human readable error message set in validate() method. */

    public function __construct($id = "myform") 
    {
        $id = preg_replace("/[^a-zA-Z0-9]/", "_", $id);
        /*Provide default values for class variables.*/
        $this->attributes = array(
            "id" => $id,
            "name" => $id,
            "method" => "post",
            "action" => basename($_SERVER["SCRIPT_NAME"]),
            "style" => "padding: 0; margin: 0;"
        );
        $this->tableAttributes = array(
            "cellpadding" => "4",
            "cellspacing" => "0",
            "border" => "0"
        );
        $this->tdAttributes = array(
            "valign" => "top",
            "align" => "left"
        );
        $this->requiredAttributes = array(
            "style" => "color: #990000;"
        );
        $this->captchaTheme = "white";
        $this->captchaLang = "en";
        $this->captchaPublicKey = "6LcazwoAAAAAADamFkwqj5KN1Gla7l4fpMMbdZfi";
        $this->captchaPrivateKey = "6LcazwoAAAAAAD-auqUl-4txAK3Ky5jc5N3OXN0_";
        $this->jqueryDateFormat = "yy-mm-dd";
		$this->timeFormat = "H:i:s";

        /*This array prevents junk from being inserted into the form's HTML.  If you find that an attributes you need to use is not included
        in this list, feel free to customize to fit your needs.*/
        $this->allowedFields = array(
            "form" => array("method", "action", "enctype", "onsubmit", "id", "class", "name"),
            "table" => array("cellpadding", "cellspacing", "border", "style", "id", "class", "name", "align", "width"),
            "td" => array("id", "name", "valign", "align", "style", "id", "class", "width"),
            "div" => array("id", "name", "valign", "align", "style", "id", "class"),
            "hidden" => array("id", "name", "value", "type"),
            "text" => array("id", "name", "value", "type", "class", "style", "onclick", "onkeyup", "onchange", "onfocus", "onblur", "maxlength", "size"),
            "textarea" => array("id", "name", "class", "style", "onchange", "onclick", "onkeyup", "maxlength", "onfocus", "onblur", "size", "rows", "cols"),
            "select" => array("id", "name", "class", "style", "onclick", "onchange", "onfocus", "onblur", "size"),
            "radio" => array("name", "style", "class", "onclick", "onchange", "type"),
            "checksort" => array("style", "class"),
            "button" => array("name", "value", "type", "id", "onclick", "class", "style"),
            "a" => array("id", "name", "href", "class", "style", "target"),
            "latlng" => array("id", "name", "type", "class", "style", "onclick", "onkeyup", "maxlength", "size")
        );

        $this->ajaxType = "post";
        $this->ajaxUrl = basename($_SERVER["SCRIPT_NAME"]);
        $this->ajaxDataType = "text";
        $this->errorMsgFormat = "[LABEL] is a required field.";
        $this->validateErrorMsgFormat = "[LABEL] does not validate.";
        $this->emailErrorMsgFormat = "[LABEL] contains an invalid email address.";
        $this->includesRelativePath = "php-form-builder-class/includes";
        $this->onsubmitFunctionOverride = "formhandler_" . $this->attributes["name"];
        $this->post_init_js = "";
        $this->pre_init_js = "";
        $this->form_js = "";
        $this->using_js = false;

        $CI =& get_instance();
        $this->includesAbsoluteWebPath = $CI->config->slash_item('base_url').'php-form-builder-class/includes';
    }

    /*Creates new element object instances and attaches them to the form object.  This function is private and can only be called inside this class.*/
    private function attachElement($params)
    {
        $ele = new element();
        $ele->setAttributes($params);
        $eleType = &$ele->attributes["type"];

        if($eleType == "state")
        {
            /*This section prevents the stateArr from being generated for each form and/or multiple state field types per form.*/
            $eleType = "select";
            if(empty($this->stateArr))
            {
                $this->stateArr = array(
                    array("value" => "", "text" => "--Select a State/Province--"),
                    array("value" => "AL", "text" => "Alabama"),
                    array("value" => "AK", "text" => "Alaska"),
                    array("value" => "AZ", "text" => "Arizona"),
                    array("value" => "AR", "text" => "Arkansas"),
                    array("value" => "CA", "text" => "California"),
                    array("value" => "CO", "text" => "Colorado"),
                    array("value" => "CT", "text" => "Connecticut"),
                    array("value" => "DE", "text" => "Delaware"),
                    array("value" => "DC", "text" => "District of Columbia"),
                    array("value" => "FL", "text" => "Florida"),
                    array("value" => "GA", "text" => "Georgia"),
                    array("value" => "HI", "text" => "Hawaii"),
                    array("value" => "ID", "text" => "Idaho"),
                    array("value" => "IL", "text" => "Illinois"),
                    array("value" => "IN", "text" => "Indiana"),
                    array("value" => "IA", "text" => "Iowa"),
                    array("value" => "KS", "text" => "Kansas"),
                    array("value" => "KY", "text" => "Kentucky"),
                    array("value" => "LA", "text" => "Louisiana"),
                    array("value" => "ME", "text" => "Maine"),
                    array("value" => "MD", "text" => "Maryland"),
                    array("value" => "MA", "text" => "Massachusetts"),
                    array("value" => "MI", "text" => "Michigan"),
                    array("value" => "MN", "text" => "Minnesota"),
                    array("value" => "MS", "text" => "Mississippi"),
                    array("value" => "MO", "text" => "Missouri"),
                    array("value" => "MT", "text" => "Montana"),
                    array("value" => "NE", "text" => "Nebraska"),
                    array("value" => "NV", "text" => "Nevada"),
                    array("value" => "NH", "text" => "New Hampshire"),
                    array("value" => "NJ", "text" => "New Jersey"),
                    array("value" => "NM", "text" => "New Mexico"),
                    array("value" => "NY", "text" => "New York"),
                    array("value" => "NC", "text" => "North Carolina"),
                    array("value" => "ND", "text" => "North Dakota"),
                    array("value" => "OH", "text" => "Ohio"),
                    array("value" => "OK", "text" => "Oklahoma"),
                    array("value" => "OR", "text" => "Oregon"),
                    array("value" => "PA", "text" => "Pennsylvania"),
                    array("value" => "RI", "text" => "Rhode Island"),
                    array("value" => "SC", "text" => "South Carolina"),
                    array("value" => "SD", "text" => "South Dakota"),
                    array("value" => "TN", "text" => "Tennessee"),
                    array("value" => "TX", "text" => "Texas"),
                    array("value" => "UT", "text" => "Utah"),
                    array("value" => "VT", "text" => "Vermont"),
                    array("value" => "VA", "text" => "Virginia"),
                    array("value" => "WA", "text" => "Washington"),
                    array("value" => "WV", "text" => "West Virginia"),
                    array("value" => "WI", "text" => "Wisconsin"),
                    array("value" => "WY", "text" => "Wyoming"),
                    array("value" => "", "text" => ""),
                    array("value" => "", "text" => "-- Canadian Province--"),
                    array("value" => "AB", "text" => "Alberta"),
                    array("value" => "BC", "text" => "British Columbia"),
                    array("value" => "MB", "text" => "Manitoba"),
                    array("value" => "NB", "text" => "New Brunswick"),
                    array("value" => "NL", "text" => "Newfoundland and Labrador"),
                    array("value" => "NS", "text" => "Nova Scotia"),
                    array("value" => "NT", "text" => "Northwest Territories"),
                    array("value" => "NU", "text" => "Nunavut"),
                    array("value" => "ON", "text" => "Ontario"),
                    array("value" => "PE", "text" => "Prince Edward Island"),
                    array("value" => "QC", "text" => "Qu&#233;bec"),
                    array("value" => "SK", "text" => "Saskatchewan"),
                    array("value" => "YT", "text" => "Yukon"),
                    array("value" => "", "text" => ""),
                    array("value" => "", "text" => "-- US Territories--"),
                    array("value" => "AS", "text" => "American Samoa"),
                    array("value" => "FM", "text" => "Federated States of Micronesia"),
                    array("value" => "GU", "text" => "Guam"),
                    array("value" => "MH", "text" => "Marshall Islands"),
                    array("value" => "PW", "text" => "Palau"),
                    array("value" => "PR", "text" => "Puerto Rico"),
                    array("value" => "VI", "text" => "Virgin Islands")
                );
            }
            $ele->options = array();
            $stateSize = sizeof($this->stateArr);
            for($s = 0; $s < $stateSize; ++$s)
            {
                $opt = new option();
                $opt->setAttributes($this->stateArr[$s]);
                $ele->options[] = $opt;
            }
        }   
        elseif($eleType == "country")
        {
            /*This section prevents the countryArr from being generated for each form and/or multiple country field types per form.*/
            $eleType = "select";
            if(empty($this->countryArr))
            {
                $this->countryArr = array(
                    array("value" => "", "text" => "--Select a Country--"),
                    array("value" => "US", "text" => "United States"),
                    array("value" => "AF", "text" => "Afghanistan"),
                    array("value" => "AL", "text" => "Albania"),
                    array("value" => "DZ", "text" => "Algeria"),
                    array("value" => "AS", "text" => "American Samoa"),
                    array("value" => "AD", "text" => "Andorra"),
                    array("value" => "AO", "text" => "Angola"),
                    array("value" => "AI", "text" => "Anguilla"),
                    array("value" => "AG", "text" => "Antigua and Barbuda"),
                    array("value" => "AR", "text" => "Argentina"),
                    array("value" => "AM", "text" => "Armenia"),
                    array("value" => "AW", "text" => "Aruba"),
                    array("value" => "AU", "text" => "Australia"),
                    array("value" => "AT", "text" => "Austria"),
                    array("value" => "AZ", "text" => "Azerbaijan"),
                    array("value" => "BS", "text" => "Bahamas"),
                    array("value" => "BH", "text" => "Bahrain"),
                    array("value" => "BD", "text" => "Bangladesh"),
                    array("value" => "BB", "text" => "Barbados"),
                    array("value" => "BY", "text" => "Belarus"),
                    array("value" => "BE", "text" => "Belgium"),
                    array("value" => "BZ", "text" => "Belize"),
                    array("value" => "BJ", "text" => "Benin"),
                    array("value" => "BM", "text" => "Bermuda"),
                    array("value" => "BT", "text" => "Bhutan"),
                    array("value" => "BO", "text" => "Bolivia"),
                    array("value" => "BA", "text" => "Bosnia and Herzegowina"),
                    array("value" => "BW", "text" => "Botswana"),
                    array("value" => "BR", "text" => "Brazil"),
                    array("value" => "IO", "text" => "British Indian Ocean Territory"),
                    array("value" => "BN", "text" => "Brunei Darussalam"),
                    array("value" => "BG", "text" => "Bulgaria"),
                    array("value" => "BF", "text" => "Burkina Faso"),
                    array("value" => "BI", "text" => "Burundi"),
                    array("value" => "KH", "text" => "Cambodia"),
                    array("value" => "CM", "text" => "Cameroon"),
                    array("value" => "CA", "text" => "Canada"),
                    array("value" => "CV", "text" => "Cape Verde"),
                    array("value" => "KY", "text" => "Cayman Islands"),
                    array("value" => "CF", "text" => "Central African Republic"),
                    array("value" => "TD", "text" => "Chad"),
                    array("value" => "CL", "text" => "Chile"),
                    array("value" => "CN", "text" => "China"),
                    array("value" => "CO", "text" => "Colombia"),
                    array("value" => "CG", "text" => "Congo"),
                    array("value" => "CK", "text" => "Cook Islands"),
                    array("value" => "CR", "text" => "Costa Rica"),
                    array("value" => "CI", "text" => "Cote d'Ivoire"),
                    array("value" => "HR", "text" => "Croatia"),
                    array("value" => "CY", "text" => "Cyprus"),
                    array("value" => "CZ", "text" => "Czech Republic"),
                    array("value" => "DK", "text" => "Denmark"),
                    array("value" => "DJ", "text" => "Djibouti"),
                    array("value" => "DM", "text" => "Dominica"),
                    array("value" => "DO", "text" => "Dominican Republic"),
                    array("value" => "EC", "text" => "Ecuador"),
                    array("value" => "EG", "text" => "Egypt"),
                    array("value" => "SV", "text" => "El Salvador"),
                    array("value" => "GQ", "text" => "Equatorial Guinea"),
                    array("value" => "ER", "text" => "Eritrea"),
                    array("value" => "EE", "text" => "Estonia"),
                    array("value" => "ET", "text" => "Ethiopia"),
                    array("value" => "FO", "text" => "Faroe Islands"),
                    array("value" => "FJ", "text" => "Fiji"),
                    array("value" => "FI", "text" => "Finland"),
                    array("value" => "FR", "text" => "France"),
                    array("value" => "GF", "text" => "French Guiana"),
                    array("value" => "PF", "text" => "French Polynesia"),
                    array("value" => "GA", "text" => "Gabon"),
                    array("value" => "GM", "text" => "Gambia"),
                    array("value" => "GE", "text" => "Georgia"),
                    array("value" => "DE", "text" => "Germany"),
                    array("value" => "GH", "text" => "Ghana"),
                    array("value" => "GI", "text" => "Gibraltar"),
                    array("value" => "GR", "text" => "Greece"),
                    array("value" => "GL", "text" => "Greenland"),
                    array("value" => "GD", "text" => "Grenada"),
                    array("value" => "GP", "text" => "Guadeloupe"),
                    array("value" => "GU", "text" => "Guam"),
                    array("value" => "GT", "text" => "Guatemala"),
                    array("value" => "GN", "text" => "Guinea"),
                    array("value" => "GW", "text" => "Guinea-Bissau"),
                    array("value" => "GY", "text" => "Guyana"),
                    array("value" => "HT", "text" => "Haiti"),
                    array("value" => "HM", "text" => "Heard Island And Mcdonald Islands"),
                    array("value" => "HK", "text" => "Hong Kong"),
                    array("value" => "HU", "text" => "Hungary"),
                    array("value" => "IS", "text" => "Iceland"),
                    array("value" => "IN", "text" => "India"),
                    array("value" => "ID", "text" => "Indonesia"),
                    array("value" => "IR", "text" => "Iran, Islamic Republic Of"),
                    array("value" => "IL", "text" => "Israel"),
                    array("value" => "IT", "text" => "Italy"),
                    array("value" => "JM", "text" => "Jamaica"),
                    array("value" => "JP", "text" => "Japan"),
                    array("value" => "JO", "text" => "Jordan"),
                    array("value" => "KZ", "text" => "Kazakhstan"),
                    array("value" => "KE", "text" => "Kenya"),
                    array("value" => "KI", "text" => "Kiribati"),
                    array("value" => "KP", "text" => "Korea, Democratic People's Republic Of"),
                    array("value" => "KW", "text" => "Kuwait"),
                    array("value" => "KG", "text" => "Kyrgyzstan"),
                    array("value" => "LA", "text" => "Lao People's Democratic Republic"),
                    array("value" => "LV", "text" => "Latvia"),
                    array("value" => "LB", "text" => "Lebanon"),
                    array("value" => "LS", "text" => "Lesotho"),
                    array("value" => "LR", "text" => "Liberia"),
                    array("value" => "LI", "text" => "Liechtenstein"),
                    array("value" => "LT", "text" => "Lithuania"),
                    array("value" => "LU", "text" => "Luxembourg"),
                    array("value" => "MO", "text" => "Macau"),
                    array("value" => "MK", "text" => "Macedonia, The Former Yugoslav Republic Of"),
                    array("value" => "MG", "text" => "Madagascar"),
                    array("value" => "MW", "text" => "Malawi"),
                    array("value" => "MY", "text" => "Malaysia"),
                    array("value" => "MV", "text" => "Maldives"),
                    array("value" => "ML", "text" => "Mali"),
                    array("value" => "MT", "text" => "Malta"),
                    array("value" => "MH", "text" => "Marshall Islands"),
                    array("value" => "MQ", "text" => "Martinique"),
                    array("value" => "MR", "text" => "Mauritania"),
                    array("value" => "MU", "text" => "Mauritius"),
                    array("value" => "MX", "text" => "Mexico"),
                    array("value" => "FM", "text" => "Micronesia, Federated States Of"),
                    array("value" => "MD", "text" => "Moldova, Republic Of"),
                    array("value" => "MC", "text" => "Monaco"),
                    array("value" => "MN", "text" => "Mongolia"),
                    array("value" => "MS", "text" => "Montserrat"),
                    array("value" => "MA", "text" => "Morocco"),
                    array("value" => "MZ", "text" => "Mozambique"),
                    array("value" => "NA", "text" => "Namibia"),
                    array("value" => "NP", "text" => "Nepal"),
                    array("value" => "NL", "text" => "Netherlands"),
                    array("value" => "AN", "text" => "Netherlands Antilles"),
                    array("value" => "NC", "text" => "New Caledonia"),
                    array("value" => "NZ", "text" => "New Zealand"),
                    array("value" => "NI", "text" => "Nicaragua"),
                    array("value" => "NE", "text" => "Niger"),
                    array("value" => "NG", "text" => "Nigeria"),
                    array("value" => "NF", "text" => "Norfolk Island"),
                    array("value" => "MP", "text" => "Northern Mariana Islands"),
                    array("value" => "NO", "text" => "Norway"),
                    array("value" => "OM", "text" => "Oman"),
                    array("value" => "PK", "text" => "Pakistan"),
                    array("value" => "PW", "text" => "Palau"),
                    array("value" => "PA", "text" => "Panama"),
                    array("value" => "PG", "text" => "Papua New Guinea"),
                    array("value" => "PY", "text" => "Paraguay"),
                    array("value" => "PE", "text" => "Peru"),
                    array("value" => "PH", "text" => "Philippines"),
                    array("value" => "PL", "text" => "Poland"),
                    array("value" => "PT", "text" => "Portugal"),
                    array("value" => "PR", "text" => "Puerto Rico"),
                    array("value" => "QA", "text" => "Qatar"),
                    array("value" => "RE", "text" => "Reunion"),
                    array("value" => "RO", "text" => "Romania"),
                    array("value" => "RU", "text" => "Russian Federation"),
                    array("value" => "RW", "text" => "Rwanda"),
                    array("value" => "KN", "text" => "Saint Kitts and Nevis"),
                    array("value" => "LC", "text" => "Saint Lucia"),
                    array("value" => "VC", "text" => "Saint Vincent and the Grenadines"),
                    array("value" => "WS", "text" => "Samoa"),
                    array("value" => "SM", "text" => "San Marino"),
                    array("value" => "SA", "text" => "Saudi Arabia"),
                    array("value" => "SN", "text" => "Senegal"),
                    array("value" => "SC", "text" => "Seychelles"),
                    array("value" => "SL", "text" => "Sierra Leone"),
                    array("value" => "SG", "text" => "Singapore"),
                    array("value" => "SK", "text" => "Slovakia"),
                    array("value" => "SI", "text" => "Slovenia"),
                    array("value" => "SB", "text" => "Solomon Islands"),
                    array("value" => "SO", "text" => "Somalia"),
                    array("value" => "ZA", "text" => "South Africa"),
                    array("value" => "ES", "text" => "Spain"),
                    array("value" => "LK", "text" => "Sri Lanka"),
                    array("value" => "SD", "text" => "Sudan"),
                    array("value" => "SR", "text" => "Suriname"),
                    array("value" => "SZ", "text" => "Swaziland"),
                    array("value" => "SE", "text" => "Sweden"),
                    array("value" => "CH", "text" => "Switzerland"),
                    array("value" => "SY", "text" => "Syrian Arab Republic"),
                    array("value" => "TW", "text" => "Taiwan, Province Of China"),
                    array("value" => "TJ", "text" => "Tajikistan"),
                    array("value" => "TZ", "text" => "Tanzania, United Republic Of"),
                    array("value" => "TH", "text" => "Thailand"),
                    array("value" => "TG", "text" => "Togo"),
                    array("value" => "TO", "text" => "Tonga"),
                    array("value" => "TT", "text" => "Trinidad and Tobago"),
                    array("value" => "TN", "text" => "Tunisia"),
                    array("value" => "TR", "text" => "Turkey"),
                    array("value" => "TM", "text" => "Turkmenistan"),
                    array("value" => "TC", "text" => "Turks and Caicos Islands"),
                    array("value" => "TV", "text" => "Tuvalu"),
                    array("value" => "UG", "text" => "Uganda"),
                    array("value" => "UA", "text" => "Ukraine"),
                    array("value" => "AE", "text" => "United Arab Emirates"),
                    array("value" => "GB", "text" => "United Kingdom"),
                    array("value" => "UY", "text" => "Uruguay"),
                    array("value" => "UZ", "text" => "Uzbekistan"),
                    array("value" => "VU", "text" => "Vanuatu"),
                    array("value" => "VE", "text" => "Venezuela"),
                    array("value" => "VN", "text" => "Vietnam"),
                    array("value" => "VG", "text" => "Virgin Islands (British)"),
                    array("value" => "VI", "text" => "Virgin Islands (U.S.)"),
                    array("value" => "WF", "text" => "Wallis and Futuna Islands"),
                    array("value" => "EH", "text" => "Western Sahara"),
                    array("value" => "YE", "text" => "Yemen"),
                    array("value" => "YU", "text" => "Yugoslavia"),
                    array("value" => "ZM", "text" => "Zambia"),
                    array("value" => "ZR", "text" => "Zaire"),
                    array("value" => "ZW", "text" => "Zimbabwe")
                );
            }
            $ele->options = array();
            $countrySize = sizeof($this->countryArr);
            for($s = 0; $s < $countrySize; ++$s)
            {
                $opt = new option();
                $opt->setAttributes($this->countryArr[$s]);
                $ele->options[] = $opt;
            }
        }
        elseif($eleType == "yesno")
        {
            /*The yesno field is shortcut creating a radio button with two options: yes and no.*/
            $eleType = "radio";
            $ele->options = array();
            $opt = new option();
            $opt->setAttributes(array("value" => "1", "text" => "Yes"));
            $ele->options[] = $opt;
            $opt = new option();
            $opt->setAttributes(array("value" => "0", "text" => "No"));
            $ele->options[] = $opt;
        }
        elseif($eleType == "truefalse")
        {
            /*Similar to yesno, the truefalse field is shortcut creating a radio button with two options: true and false.*/
            $eleType = "radio";
            $ele->options = array();
            $opt = new option();
            $opt->setAttributes(array("value" => "1", "text" => "True"));
            $ele->options[] = $opt;
            $opt = new option();
            $opt->setAttributes(array("value" => "0", "text" => "False"));
            $ele->options[] = $opt;
        }
        /*If there is a captcha elements in the form, make sure javascript onsubmit function is enabled.*/
        elseif($eleType == "captcha")
        {
            if(empty($this->captchaExists))
                $this->captchaExists = 1;
            else
                return;
        }   
        elseif($eleType == "email")
            $this->emailExists = 1;
        else
        {
            /*Various form types (select, radio, ect.) use the options parameter to handle multiple choice elements.*/
            if(array_key_exists("options", $params) && is_array($params["options"]))
            {
                $ele->options = array();
                /*If the options array is numeric, assign the key and text to each value.*/
                if(array_values($params["options"]) === $params["options"])
                {
                    foreach($params["options"] as $key => $value)
                    {
                        $opt = new option();
                        $opt->setAttributes(array("value" => $value, "text" => $value));
                        $ele->options[] = $opt;
                    }
                }
                /*If the options array is associative, assign the key and text to each key/value pair.*/
                else
                {
                    foreach($params["options"] as $key => $value)
                    {
                        $opt = new option();
                        $opt->setAttributes(array("value" => $key, "text" => $value));
                        $ele->options[] = $opt;
                    }
                }
            }

            /*If there is a file field type in the form, make sure that the encytype is set accordingly.*/
            if($eleType == "file")
                $this->attributes["enctype"] = "multipart/form-data";
        }

        /*If there is a required/validate field type in the form, make sure javascript error checking is enabled.*/
        if((!empty($ele->required) || !empty($ele->attributes['validate'])) && empty($this->checkform))
            $this->checkform = 1;

        /*Set default hints for various element types.*/
        if($eleType == "date" && empty($ele->hint))
            $ele->hint = "Click to Select Date...";
        elseif($eleType == "daterange" && empty($ele->hint))
            $ele->hint = "Click to Select Date Range...";
        elseif($eleType == "colorpicker" && empty($ele->hint))
            $ele->hint = "Click to Select Color...";
        elseif($eleType == "latlng" && empty($ele->hint))
            $ele->hint = "Drag Map Marker to Select Location...";

        /*Triggers the formhandler onsubmit function.*/
        if(in_array($eleType, array("text", "textarea", "date", "daterange", "colorpicker", "latlng", "email")) && !empty($ele->hint) && empty($ele->attributes["value"]))
            $this->hintExists = 1;
        
        $this->elements[] = $ele;
    }
    
    /*-------------------------------------------START: HOW USERS CAN ADD FORM FIELDS--------------------------------------------*/

    /*addElements allows users to add multiple form elements by passing a multi-dimensional array.*/
    public function addElements($params)
    {
        $paramSize = sizeof($params);
        for($i = 0; $i < $paramSize; ++$i)
            $this->attachElement($params[$i]);
    }

    /*addElement allows users to add a single form element by passing an array.*/
    public function addElement($label, $name, $type="", $value="", $additionalParams="")
    {
        $params = array("label" => $label, "name" => $name);
        if(!empty($type))
            $params["type"] = $type;
        $params["value"] = $value;
            
        /*Commonly used attributes such as name, type, and value exist as parameters in the function.  All other attributes
        that need to be included should be passed in the additionalParams field.  This field should exist as an associative
        array with the key being the attribute's name.  Examples of attributes passed in the additionalParams field include
        style, class, and onkeyup.*/    
        if(!empty($additionalParams) && is_array($additionalParams))
        {
            foreach($additionalParams as $key => $value)
                $params[$key] = $value;
        }
        $this->attachElement($params);
    }

    /*The remaining function are shortcuts for adding each supported form field.*/
    public function addHidden($name, $value="", $additionalParams="") {
        $this->addElement("", $name, "hidden", $value, $additionalParams);
    }
    public function addTextbox($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "text", $value, $additionalParams);
    }
    public function addTextarea($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "textarea", $value, $additionalParams);
    }
    public function addWebEditor($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "webeditor", $value, $additionalParams);
    }
    public function addCKEditor($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "ckeditor", $value, $additionalParams);
    }
    public function addPassword($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "password", $value, $additionalParams);
    }
    public function addFile($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "file", $value, $additionalParams);
    }
    public function addDate($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "date", $value, $additionalParams);
    }
    public function addDateRange($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "daterange", $value, $additionalParams);
    }
    public function addState($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "state", $value, $additionalParams);
    }
    public function addCountry($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "country", $value, $additionalParams);
    }
    public function addYesNo($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "yesno", $value, $additionalParams);
    }
    public function addTrueFalse($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "truefalse", $value, $additionalParams);
    }
    /*This function is included for backwards compatability.*/
    public function addSelectbox($label, $name, $value="", $options="", $additionalParams="") {
        $this->addSelect($label, $name, $value, $options, $additionalParams);
    }
    public function addSelect($label, $name, $value="", $options="", $additionalParams="") {
        if(!is_array($additionalParams))
            $additionalParams = array();
        $additionalParams["options"] = $options;    
        $this->addElement($label, $name, "select", $value, $additionalParams);
    }
    public function addRadio($label, $name, $value="", $options="", $additionalParams="") {
        if(!is_array($additionalParams))
            $additionalParams = array();
        $additionalParams["options"] = $options;    
        $this->addElement($label, $name, "radio", $value, $additionalParams);
    }
    public function addCheckbox($label, $name, $value="", $options="", $additionalParams="") {
        if(!is_array($additionalParams))
            $additionalParams = array();
        $additionalParams["options"] = $options;    
        $this->addElement($label, $name, "checkbox", $value, $additionalParams);
    }
    public function addSort($label, $name, $options="", $additionalParams="") {
        if(!is_array($additionalParams))
            $additionalParams = array();
        $additionalParams["options"] = $options;    
        $this->addElement($label, $name, "sort", "", $additionalParams);
    }
    public function addLatLng($label, $name, $value="", $additionalParams="") {
        $this->addMap($label, $name, $value, $additionalParams);
    }
    /*This function is included for backwards compatability.*/
    public function addMap($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "latlng", $value, $additionalParams);
    }
    public function addCheckSort($label, $name, $value="", $options="", $additionalParams="") {
        if(!is_array($additionalParams))
            $additionalParams = array();
        $additionalParams["options"] = $options;    
        $this->addElement($label, $name, "checksort", $value, $additionalParams);
    }
    public function addCaptcha($label="", $additionalParams="") {
        $this->addElement($label, "recaptcha_response_field", "captcha", "", $additionalParams);
    }   
    public function addSlider($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "slider", $value, $additionalParams);
    }
    public function addRating($label, $name, $value="", $options="", $additionalParams="") {
        if(!is_array($additionalParams))
            $additionalParams = array();
        $additionalParams["options"] = $options;    
        $this->addElement($label, $name, "rating", $value, $additionalParams);
    }
    public function addHTML($value) {
        $this->addElement("", "", "html", $value);
    }
    public function addColorPicker($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "colorpicker", $value, $additionalParams);
    }
    public function addEmail($label, $name, $value="", $additionalParams="") {
        $this->addElement($label, $name, "email", $value, $additionalParams);
    }
	public function addHourOfDayPicker($label, $name, $value="0", $additionalParams="") {
		if(!is_array($additionalParams)) $additionalParams = array();
		$this->addElement($label, $name, "hour_of_day_picker", $value, $additionalParams);
	}
    // Allows just text to be added as a field. (added 2010/05/01)
    public function addText($label, $name, $value)
    {
        $this->addElement($label, $name, "static", $value);
    }
    
    // Allows setting of html before the form table is created (added 2010/06/07)
    public function addPreTableHTML($value)
    {
        $this->addElement("", "", "pretablehtml", $value);
    }
    
    // A combobox which allows the user to select from a list of values or enter a value. Also allows filtering the list
    // of values based on text entered by the user. Good for long lists. (added 2011/04/06)
    public function addCombobox($label, $name, $value="", $options="", $additionalParams=array())
    {
        if(!is_array($additionalParams)) $additionalParams = array();
        $additionalParams["options"] = $options;
        $this->addElement($label, $name, "combobox", $value, $additionalParams);
    }
    
    /*-------------------------------------------END: HOW USERS CAN ADD FORM FIELDS--------------------------------------------*/

    /*This function can be called to clear all attached element object instances from the form - beneficial when using the elementsToString function.*/
    public function clearElements() {
        $this->elements = array();
    }

    /*This function can be called to clear all attached button object instances from the form.*/
    public function clearButtons() {
        $this->buttons = array();
    }

    /*This function creates new button object instances and attaches them to the form.  It is private and can only be used inside this class.*/
    private function attachButton($params)
    {
        $button = new button();
        $button->setAttributes($params);
        $this->buttons[] = $button;
    }

    /*This function allows users to add multiple button object instances to the form by passing a multi-dimensional array.*/
    public function addButtons($params)
    {
        $paramSize = sizeof($params);
        for($i = 0; $i < $paramSize; ++$i)
            $this->attachButton($params[$i]);
    }

    /*This function allows users to add a single button object instance to the form by passing an array.*/
    public function addButton($value="Submit", $type="submit", $additionalParams="")
    {
        $params = array("value" => $value, "type" => $type);

        /*The additionalParams performs a similar role as in the addElement function.  For more information, please read to description
        of this field in the addElement function.  Commonly used attributes included for additionalParams in this function include
        onclick.*/
        if(!empty($additionalParams) && is_array($additionalParams))
        {
            foreach($additionalParams as $key => $value)
                $params[$key] = $value;
        }
        $this->attachButton($params);
    }

    /*This function renders the form's HTML.*/
    public function render($returnString=false)
    {
        ob_start();

        /*Render the form tag with all appropriate attributes.*/
        echo "\n<form";
        if(!empty($this->attributes) && is_array($this->attributes))
        {
            /*This syntax will be used throughout the render() and elementsToString() functions ensuring that attributes added to various HTML tags
            are allowed and valid.  If you find that an attribute is not being included in your HTML tag definition, please reference $this->allowedFields.
            This variable can be modified to fit your specific needs.*/
            $tmpAllowFieldArr = $this->allowedFields["form"];
            foreach($this->attributes as $key => $value)
            {
                /*If an onsubmit function is defined and the form is setup for javascript error checking (checkform) or ajax submission (ajax), the user
                defined onsubmit function will be overwritten and discarded.*/
                if($key == "onsubmit" && (!empty($this->checkform) || !empty($this->ajax) || !empty($this->captchaExists)))
                    continue;
                if(in_array($key, $tmpAllowFieldArr))
                    echo ' ', $key, '="', str_replace('"', '&quot;', $value), '"';
            }   
        }
            
        if(!empty($this->checkform) || !empty($this->ajax) || !empty($this->captchaExists) || !empty($this->hintExists) || !empty($this->emailExists)
            || !empty($this->using_js))  
            echo ' onsubmit="return (abort_submit == false && (skip_onsubmit || ', $this->onsubmitFunctionOverride, '(this)));"';  // Changed 2010/05/16 & 2011/04/11
        echo ">\n";

        $CI =& get_instance();
        $formclass_values = $CI->session->userdata('formclass_values');
        //echo "When rendering the form <br>";
        //echo "session auto fill is: " . $this->enableSessionAutoFill;
        //echo "<br>reference values are: <pre>"; print_r($this->referenceValues);print'</pre>';
        //echo "<br>form class values is: " . ((!empty($formclass_values)) ? " not empty" : " empty");
        if(!empty($this->enableSessionAutoFill) && empty($this->referenceValues) && !empty($formclass_values) && array_key_exists($this->attributes["name"], $formclass_values))
        {
            $this->setReferenceValues($formclass_values[$this->attributes["name"]]);
        //  echo "<br>set the reference values! <pre>"; print_r($this->referenceValues);print'</pre>';
        }

        /*This section renders all the hidden form fields outside the <table> tag.
          It also is used to check if a pre-table html was specified */
        $elementSize = sizeof($this->elements);
        for($i = 0; $i < $elementSize; ++$i)
        {
            $ele = $this->elements[$i];
            if($ele->attributes["type"] == "hidden")
            {
                /*If the referenceValues array is filled, check for this specific element's name in the associative array key and populate the field's value if applicable.*/
                if(!empty($this->referenceValues) && is_array($this->referenceValues))
                {
                    if(array_key_exists($ele->attributes["name"], $this->referenceValues))
                        $ele->attributes["value"] = $this->referenceValues[$ele->attributes["name"]];
                    elseif(substr($ele->attributes["name"], -2) == "[]" && array_key_exists(substr($ele->attributes["name"], 0, -2), $this->referenceValues))
                        $ele->attributes["value"] = $this->referenceValues[substr($ele->attributes["name"], 0, -2)];
                }   

                echo "<input";
        echo $this->addAttributes($ele->attributes, "hidden");
                echo "/>\n";
            }
            elseif($ele->attributes['type'] == 'pretablehtml')
            {
                echo $ele->attributes['value'];
            }
        }   

        /*The form fields are rendered in a basic table structure.*/
        echo "<table ";
        //echo $this->addAttributes($this->tableAttributes, "table");
        echo 'cellpadding="4" cellspacing="0" border="0" style="width: auto;" ';
        echo ">\n";

        /*Render the elements by calling elementsToString function with the includeTable tags field set to false.  There is no need
        to render the table tag b/c we have just done that above.*/
        echo $this->elementsToString(false);

        /*If there are buttons included, render those to the screen now.*/
        if(!empty($this->buttons))
        {
        // echo "\t", '<tr><td align="right"';
    echo "\t", '<tr><td ';
    // if(!empty($this->tdAttributes) && is_array($this->tdAttributes))
    // {
        // $tmpAllowFieldArr = $this->allowedFields["td"];
        // foreach($this->tdAttributes as $key => $value)
        // {
            // /*This if section overwrites the align attribute of the table cell tag (<td>) forcing all buttons to be aligned right.*/
            // if($key != "align" && in_array($key, $tmpAllowFieldArr))
                // echo ' ', $key, '="', str_replace('"', '&quot;', $value), '"';
        // }        
    // }
        //echo $this->addAttributes($this->tdAttributes, "td");
            echo 'colspan="2" style="text-align: left;"';
            echo ">\n";
            $buttonSize = sizeof($this->buttons);
            for($i = 0; $i < $buttonSize; ++$i)
            {
                /*The wraplink parameter will simply wrap an anchor tag (<a>) around the button treating it as a link.*/
                if(!empty($this->buttons[$i]->wrapLink))
                {
                    echo "\t\t<a";
                    echo $this->addAttributes($this->buttons[$i]->linkAttributes, "a");
                    echo ">";
                }
                else
                    echo "\t";

                /*The phpFunction parameter was included to give the developer the flexibility to use any custom button generation function 
                they might currently use in their development environment.*/
                if(!empty($this->buttons[$i]->phpFunction))
                {
                    $execStr = $this->buttons[$i]->phpFunction . "(";
                    if(!empty($this->buttons[$i]->phpParams))
                    {
                        if(is_array($this->buttons[$i]->phpParams))
                        {
                            $paramSize = sizeof($this->buttons[$i]->phpParams);
                            for($p = 0; $p < $paramSize; ++$p)
                            {
                                if($p != 0)
                                    $execStr .= ",";

                                if(is_string($this->buttons[$i]->phpParams[$p]))    
                                    $execStr .= '"' . $this->buttons[$i]->phpParams[$p] . '"';
                                else    
                                    $execStr .= $this->buttons[$i]->phpParams[$p];  
                            }
                        }
                        else
                            $execStr .= $this->buttons[$i]->phpParams;
                    }
                    $execStr .= ");";
                    echo eval("return " . $execStr);
                }
                else
                {
                    if(empty($this->buttons[$i]->wrapLink))
                        echo "\t";
                    echo "<input";
                    echo $this->addAttributes($this->buttons[$i]->attributes, "button");
                    echo "/>";
                }

                if(!empty($this->buttons[$i]->wrapLink))
                    echo "</a>";
                
                echo "\n";
            }
            echo "\t</td></tr>\n";
        }
        echo "</table>\n";

        echo "</form>\n\n";

        /*
        If there are any required fields in the form or if this form is setup to utilize ajax, build a javascript 
        function for performing form validation before submission and/or for building and submitting a data string through ajax.
        */
        if(!empty($this->checkform) || !empty($this->ajax) || !empty($this->captchaExists) || !empty($this->hintExists) || 
           !empty($this->emailExists) || !empty($this->using_js))
        {
            echo '<script type="text/javascript">';
            if(!empty($this->emailExists))
                echo "\n\tvar validemail_", $this->attributes["name"], ";";
            echo "\n\tvar skip_onsubmit = false;";  // Added 2010/05/16
            echo "\n\tvar abort_submit = false;";   // Added 2011/04/11
            echo "\n\tfunction ", $this->onsubmitFunctionOverride, "(formObj) {";
            /*If this form is setup for ajax submission, a javascript variable (form_data) is defined and built.  This variable holds each
            key/value pair and acts as the GET or POST string.*/
            if(!empty($this->ajax))
                echo "\n\t\t", 'var form_data = ""';
                
            $this->jsCycleElements($this->elements);
            if(!empty($this->bindRules))
            {
                $bindRuleKeys = array_keys($this->bindRules);
                $bindRuleSize = sizeof($bindRuleKeys);
                for($b = 0; $b < $bindRuleSize; $b++)
                {
                    if(!empty($this->bindRules[$bindRuleKeys[$b]][0]->elements))
                    {
                        if(!empty($this->bindRules[$bindRuleKeys[$b]][1]))
                            echo "\n\t\tif(", $this->bindRules[$bindRuleKeys[$b]][1] , ") {";
                        $this->jsCycleElements($this->bindRules[$bindRuleKeys[$b]][0]->elements);
                        if(!empty($this->bindRules[$bindRuleKeys[$b]][1]))
                            echo "\n\t\t}";
                    }
                }
            }
                
            if(!empty($this->ajax))
            {
                echo "\n\t\tform_data = form_data.substring(1, form_data.length);";
                echo "\n\t\t$.ajax({";
                    echo "\n\t\t\t", 'type: "', $this->ajaxType, '",';
                    echo "\n\t\t\t", 'url: "', $this->ajaxUrl, '",';
                    echo "\n\t\t\t", 'dataType: "', $this->ajaxDataType, '",';
                    echo "\n\t\t\tdata: form_data,";
                    if(!empty($this->ajaxPreCallback))
                    {
                        echo "\n\t\t\tbeforeSend: function() {";
                            echo "\n\t\t\t\t", $this->ajaxPreCallback, "();";
                        echo "\n\t\t\t},";
                    }
                    echo "\n\t\t\tsuccess: function(responseMsg) {";
                    if(!empty($this->ajaxCallback))
                        echo "\n\t\t\t\t", $this->ajaxCallback, "(responseMsg);";
                    else
                    {
                        echo "\n\t\t\t\t", 'if(responseMsg != "")';
                            echo "\n\t\t\t\t\talert(responseMsg);";
                    }       
                    echo "\n\t\t\t},";
                    echo "\n\t\t\terror: function(XMLHttpRequest, textStatus, errorThrown) { alert(XMLHttpRequest.responseText); }";
                echo "\n\t\t});";
                echo "\n\t\treturn false;";
            }   
            else    
                echo "\n\t\treturn true;";
            echo "\n\t}";
            echo "\n</script>\n\n";
        }

        /*This javascript section sets the focus of the first field in the form.  This default behavior can be overwritten by setting the
        noAutoFocus parameter.*/
        if(empty($this->noAutoFocus) && !empty($this->focusElement))
        {
            echo '<script type="text/javascript">';
            /*The webeditor and ckeditor fields are a special case.*/
            if(!empty($this->tinymceIDArr) && is_array($this->tinymceIDArr) && in_array($this->focusElement, $this->tinymceIDArr))
                echo "\n\t\t", 'setTimeout("if(tinyMCE.get(\"', $this->focusElement, '\")) tinyMCE.get(\"', $this->focusElement, '\").focus();", 1000);';
            elseif(!empty($this->ckeditorIDArr) && is_array($this->ckeditorIDArr) && array_key_exists($this->focusElement, $this->ckeditorIDArr))
                echo "\n\t\t", 'setTimeout("CKEDITOR.instances.' . $this->focusElement . '.focus();", 1000);';
            else
            {
                /*Any fields with multiple options such as radio button, checkboxes, etc. are handled accordingly.*/
                echo "\n\t", 'if(document.getElementById("'. $this->attributes["id"]. '").elements["', $this->focusElement, '"].type != "select-one" && document.getElementById("'. $this->attributes["id"]. '").elements["', $this->focusElement, '"].type != "select-multiple" && document.getElementById("'. $this->attributes["id"]. '").elements["', $this->focusElement, '"].length)';
                    echo "\n\t\t", 'document.getElementById("'. $this->attributes["id"]. '").elements["', $this->focusElement, '"][0].focus();';
                echo "\n\telse";
                    echo "\n\t\t", 'document.getElementById("'. $this->attributes["id"]. '").elements["', $this->focusElement, '"].focus();';
            }       
            echo "\n</script>\n\n";
        }

        $content = ob_get_contents();
        ob_end_clean();

        /* Serialize the form and compress the resultant string to it in a session array. This
           variable will be uncompressed and unserialized for use within the validate() method. */
        $formclass_instances = $CI->session->userdata('formclass_instances');
        //echo "serialized length is: " . strlen(serialize($this)) . '<br>';
        //echo "compressed length is: " . strlen(gzcompress(serialize($this))) . '<br>';
        //echo "base64ed length is: " . strlen(base64_encode(gzcompress(serialize($this)))) . '<br>';
        //nice_vardump(base64_encode(gzcompress(serialize($this))));
        $formclass_instances[$this->attributes["name"]] = base64_encode(gzcompress(serialize($this)));     //2010/05/14
        $CI->session->set_userdata('formclass_instances', $formclass_instances);
        if(!$returnString)
            echo($content);
        else
            return $content;
    }

    /*This function builds and returns a string containing the HTML for the form fields.  Typically, this will be called from within the render() function; however, it can also be called by the user during unique situations.*/
    public function elementsToString($includeTableTags = true)
    {
        $str = "";
        $tooltipIDArr = array();        // An array for storing tooltip ids in use.
        $cell_widths = NULL;            // If mapping is used, this stores any custom cell widths specified.
        $default_label_pos = 'inline';  // The default label position and alignment to use.
        $default_label_align = 'center';
        

        if(empty($this->includesRelativePath) || !file_exists($this->includesRelativePath) || !is_dir($this->includesRelativePath))
            $str .= "\n\t" . '<script type="text/javascript">alert("php-form-builder-class Configuration Error: Invalid includes Directory Path\n\nUse the includesRelativePath form attribute to identify the location of the includes directory included within the php-form-builder-class folder.");</script>';

        if(empty($this->noAutoFocus))
            $focus = true;
        else
            $focus = false;

        /*If this map array is set, an additional table will be inserted in each row - this way colspans can be omitted.*/
        if(!empty($this->map))
        {
            $mapIndex = 0;
            $mapCount = 0;
            if($includeTableTags)
                $str .= "\n<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">\n";
            if(!empty($this->tdAttributes["width"]))
                $mapOriginalWidth = $this->tdAttributes["width"];
        }   
        else
        {
            if($includeTableTags)
            {
                $str .= "\n<table";
                $str .= $this->addAttributes($this->tableAttributes, "table");
                $str .= ">\n";
            }
        }

		// Arrays used to store ids of elements that will be used in javascript for setting up different component.
		$jqueryDateIDArr = $jqueryDateRangeIDArr = $jquerySortIDArr = $tooltipIDArr = $jquerySliderIDArr =
			$jqueryStarRatingIDArr = $jqueryColorIDArr = $jqueryComboboxSetupArr = $jqueryAutocompleteSetupArr = array();
        $element_ids = array();
		
        $elementSize = sizeof($this->elements);
        for($i = 0; $i < $elementSize; ++$i)
        {
            $ele = $this->elements[$i];

            /*If the referenceValues array is filled, check for this specific element's name in the associative array key and populate the field's value if applicable.*/
            if(!empty($this->referenceValues) && is_array($this->referenceValues))
            {
                if(array_key_exists($ele->attributes["name"], $this->referenceValues))
                    $ele->attributes["value"] = $this->referenceValues[$ele->attributes["name"]];
                elseif(substr($ele->attributes["name"], -2) == "[]" && array_key_exists(substr($ele->attributes["name"], 0, -2), $this->referenceValues))
                    $ele->attributes["value"] = $this->referenceValues[substr($ele->attributes["name"], 0, -2)];
            }   

            /*Hidden values do not need to be inside any table cell container; therefore, they are handled differently than the other fields.*/
            if($ele->attributes["type"] == "hidden")
            {
                if($includeTableTags)
                {
                    $str .= "\t<input";
                    $str .= $this->addAttributes($ele->attributes, "hidden");
                    $str .= "/>\n";
                }
            }
            else
            {
                if(!empty($this->map))
                {
                    // Valid mapping items are arrays with as least 3 elements: (number of fields, label position, label alignment, [width, ...]).
                    // The width of each table cell is optional. (2010/05/03)
                    if(array_key_exists($mapIndex, $this->map) && is_array($this->map[$mapIndex]) && 
                       (count($this->map[$mapIndex]) > 2) && $this->map[$mapIndex][0] > 1)
                    {
                        if($mapCount == 0)
                        {
                            // Get the label position and alignment to use for this row of fields.
                            $label_align = (!empty($this->map[$mapIndex][2])) ? $this->map[$mapIndex][2] : $default_label_align;
                            $label_pos = (!empty($this->map[$mapIndex][1])) ? $this->map[$mapIndex][1] : $default_label_pos;
                            // Get the widths to use for each cell.
                            $cell_widths = array();
                            $cur_cell = 0;
                            for($c = 3; $c < count($this->map[$mapIndex]); $c++)
                            $cell_widths[] = $this->map[$mapIndex][$c];
                            //echo '<pre>'; print_r($cell_widths); echo '</pre>';
                            $str .= "\t" . '<tr><td style="padding: 0;">' . "\n";
                            $str .= "\t\t<table";
                            $str .= $this->addAttributes($this->tableAttributes, "table");
                            $str .= ">\n";
                            $str .= "\t\t\t<tr>\n\t\t\t\t";

                            // For when no custom widths are specified:
                            /*Widths are percentage based and are calculated by dividing 100 by the number of form fields in the given row.*/
                            if(($elementSize - $i) < $this->map[$mapIndex][0])
                                $this->tdAttributes["width"] = number_format(100 / ($elementSize - $i), 2, ".", "") . "%";
                            else
                                $this->tdAttributes["width"] = number_format(100 / $this->map[$mapIndex][0], 2, ".", "") . "%";
                            // For inline rows, the number of fields is effectively doubled to accomodate the labels.
                            if($label_pos == 'inline')
                                $this->tdAttributes["width"] = number_format(50 / $this->map[$mapIndex][0], 2, ".", "") . "%";
                    }
                    else
                            $str .= "\t\t\t\t";
                    }
                    else
                    {
                        // Get the label position and alignment to use for this one field row.
                        $label_align = (!empty($this->map[$mapIndex][2])) ? $this->map[$mapIndex][2] : "left";
                        $label_pos = (!empty($this->map[$mapIndex][1])) ? $this->map[$mapIndex][1] : "above";
                        // Get the widths to use for each cell.
                        $cell_widths = array();
                        $cur_cell = 0;
                        if(array_key_exists($mapIndex, $this->map) && is_array($this->map[$mapIndex]))
                        {
                            for($c = 3; $c < count($this->map[$mapIndex]); $c++)
                            {
                                $cell_widths[] = $this->map[$mapIndex][$c];
                            }
                        }
                    
                        $str .= "\t" . '<tr><td style="padding: 0;">' . "\n";
                        $str .= "\t\t<table";
                        $str .= $this->addAttributes($this->tableAttributes, "table");
                        $str .= ">\n";
                        $str .= "\t\t\t<tr>\n\t\t\t\t";
                        if(!empty($mapOriginalWidth))
                            $this->tdAttributes["width"] = $mapOriginalWidth;
                        else
                            unset($this->tdAttributes["width"]);
                        
                        // For when no custom widths are specified:
                        // For inline rows, the number of fields is effectively doubled to accomodate the labels.
                        if($label_pos == 'inline')
                                $this->tdAttributes["width"] = number_format(50 / $this->map[$mapIndex][0], 2, ".", "") . "%";
                    }   
                }
                else
                {
                    // When no layout settings are specified, labels will use the default position.
                    $str .= "\t<tr>";
                    $label_pos = $default_label_pos;
                    $label_align = $default_label_align;
                    $cell_widths = NULL;
                }

                // If a custom defined width is available, then use it. (2010/05/03)
                if(!empty($cell_widths) && $cur_cell < count($cell_widths) )
                {
                    $tmpAttr = $this->tdAttributes;
                    // If inline labelling is used, set the cell alignment as defined.
                    if($label_pos == 'inline') $tmpAttr['valign'] = $label_align;
                    $tmpAttr['width'] = $cell_widths[$cur_cell];
                    $cur_cell++;
                    $str .= "<td";
                    $str .= $this->addAttributes($tmpAttr, "td");
                    $str .= ">\n";
                }
                else
                {
                $str .= "<td";
                $str .= $this->addAttributes($this->tdAttributes, "td");
                $str .= ">\n";
                }

                /*preHTML and postHTML allow for any special case scenarios.  One specific situation where these may be used would
                be if you need to toggle the visibility of an item or items based on the state of another field such as a radio button.*/
                if(!empty($ele->preHTML))
                {
                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= $ele->preHTML;
                    $str .= "\n";   
                }       

                // Print a label if needed.
                if(!empty($ele->label) && $label_pos != 'below')
                    $this->_print_label($str, $ele, $label_pos, $label_align, $tooltipIDArr, $cell_widths, $cur_cell);

				// Ensure that the element has an id. If not, create a unique one. (2011/06/03)
				if(empty($ele->attributes["id"]))
				{
					$ele->attributes["id"] = "_element_" . rand(0, 999);
					while(in_array($ele->attributes["id"], $element_ids))
						$ele->attributes["id"] = "_element_" . rand(0, 999);
				}
					
					
                /*Check the element's type and render the field accordinly.*/
                $eleType = &$ele->attributes["type"];

                /*Add appropriate javascript event functions if hint is present.*/
                if(in_array($eleType, array("text", "textarea", "date", "daterange", "colorpicker", "latlng", "email")) && !empty($ele->hint))
                {
                    if(empty($ele->attributes["value"]))
                    {
                        /*The latlng element is a special case that is handled when building the field.*/
                        if($eleType != "latlng")
                        {
                            $ele->attributes["value"] = $ele->hint;
                            $hintFocusFunction = "hintfocus_" . $this->attributes["name"] . "(this);";
                            if(empty($ele->attributes["onfocus"]))
                                $ele->attributes["onfocus"] = $hintFocusFunction;
                            else
                                $ele->attributes["onfocus"] .= " " . $hintFocusFunction;

                            $hintBlurFunction = "hintblur_" . $this->attributes["name"] . "(this);";
                            if(empty($ele->attributes["onblur"]))
                                $ele->attributes["onblur"] = $hintBlurFunction;
                            else
                                $ele->attributes["onblur"] .= " " . $hintBlurFunction;
                        }   
                    }
                    else
                        $this->elements[$i]->hint = "";
                }
                elseif(!empty($ele->hint))
                    unset($this->elements[$i]->hint);
                if($eleType == "static")    // Just a text display field
                {
                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<span id=\"".$ele->attributes['name']."\">";
                    $str .= $ele->attributes['value'];
                    $str .= "</span>\n";
                }
                elseif($eleType == "text" || $eleType == "password" || $eleType == "email")
                {
                    if($eleType == "email")
                    {
                        $resetTypeToEmail = true;
                        $eleType = "text";
                    }   
                        
                    //if(empty($ele->attributes["style"]))
                    //    $ele->attributes["style"] = "width: 100%;";

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<input";
                    $str .= $this->addAttributes($ele->attributes, "text");
                    if(!empty($ele->disabled))
                        $str .= ' disabled="disabled"';
                    if(!empty($ele->readonly))
                        $str .= ' readonly="readonly"';
                    $str .= "/>\n";
                    if($focus)
                        $this->focusElement = $ele->attributes["name"];
                    
                    if(isset($resetTypeToEmail))
                    {
                        unset($resetTypeToEmail);
                        $eleType = "email";
                    }
                }
                elseif($eleType == "file")
                {
                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<input";
                    if(!empty($ele->attributes['value']))
                    {
                        $temp = $ele->attributes['value'];
                        unset($ele->attributes['value']);
                        $use_js = TRUE;
                    }
                    $str .= $this->addAttributes($ele->attributes, "text");
                    if(!empty($temp)) $ele->attributes['value'] = $temp;
                    unset($temp);
                    if(!empty($ele->disabled))
                        $str .= ' disabled="disabled"';
                    if(!empty($ele->readonly))
                        $str .= ' readonly="readonly"';
                    $str .= "/>\n";
                    if(!empty($ele->attributes['value']))
                    {
                        $str .= '<br>';
                        $str .= 'Previously uploaded file: ' . $ele->attributes['value'];
                    }
                    if($focus)
                        $this->focusElement = $ele->attributes["name"];
                }
                elseif($eleType == "textarea")
                {
                    // if(empty($ele->attributes["style"]))
                        //$ele->attributes["style"] = "width: 100%; height: 100px;";
                    if(empty($ele->attributes["rows"]))
                        $ele->attributes["rows"] = "6";
                    if(empty($ele->attributes["cols"]))
                        $ele->attributes["cols"] = "30";

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<textarea";
                    $str .= $this->addAttributes($ele->attributes, "textarea");
                    if(!empty($ele->disabled))
                        $str .= ' disabled="disabled"';
                    if(!empty($ele->readonly))
                        $str .= ' readonly="readonly"';
                    $str .= ">" . $ele->attributes["value"] . "</textarea>\n";
                    if($focus)
                        $this->focusElement = $ele->attributes["name"];
                }
                elseif($eleType == "webeditor")
                {
                    // if(empty($ele->attributes["style"]))
                        //$ele->attributes["style"] = "width: 100%; height: 100px;";
                    
                    if(empty($ele->attributes["class"]))
                        $ele->attributes["class"] = "";
                    else    
                        $ele->attributes["class"] .= " ";

                    if(!empty($ele->webeditorSimple))
                        $ele->attributes["class"] .= "tiny_mce_simple";
                    else
                        $ele->attributes["class"] .= "tiny_mce";

                    if(empty($ele->attributes["id"]))
                        $ele->attributes["id"] = "webeditor_" . rand(0, 999);

                    if(empty($ele->attributes["rows"]))
                        $ele->attributes["rows"] = "6";
                    if(empty($ele->attributes["cols"]))
                        $ele->attributes["cols"] = "30";

                    /*This section ensures that each webeditor field has a unique identifier.*/
                    if(empty($this->tinymceIDArr))
                        $this->tinymceIDArr = array();
                    while(in_array($ele->attributes["id"], $this->tinymceIDArr))
                        $ele->attributes["id"] = "webeditor_" . rand(0, 999);
                    $this->tinymceIDArr[] = $ele->attributes["id"]; 
                        
                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<textarea";
                    $str .= $this->addAttributes($ele->attributes, "textarea");
                    if(!empty($ele->disabled))
                        $str .= ' disabled="disabled"';
                    if(!empty($ele->readonly))
                        $str .= ' readonly="readonly"';
                    $str .= ">" . $ele->attributes["value"] . "</textarea>\n";
                    if($focus)
                        $this->focusElement = $ele->attributes["id"];
                }
                elseif($eleType == "ckeditor")
                {
                    if(empty($ele->attributes["id"]))
                        $ele->attributes["id"] = "ckeditor_" . rand(0, 999);

                    /*This section ensures that each ckeditor field has a unique identifier.*/
                    if(empty($this->ckeditorIDArr))
                        $this->ckeditorIDArr = array();
                    while(array_key_exists($ele->attributes["id"], $this->ckeditorIDArr))
                        $ele->attributes["id"] = "ckeditor_" . rand(0, 999);
                    $this->ckeditorIDArr[$ele->attributes["id"]] = $ele; 

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<textarea"; 
                    $str .= $this->addAttributes($ele->attributes, "textarea");
                    if(!empty($ele->disabled))
                        $str .= ' disabled="disabled"';
                    if(!empty($ele->readonly))
                        $str .= ' readonly="readonly"';
                    $str .= ">" . $ele->attributes["value"] . "</textarea>\n";
                    if($focus)
                        $this->focusElement = $ele->attributes["id"];
                }
                elseif($eleType == "select")
                {
                    //if(empty($ele->attributes["style"]))
                    //    $ele->attributes["style"] = "width: 100%;";
                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<select";
                    $optionSize = 0;
                    if(is_array($ele->options)) $optionSize = sizeof($ele->options);
                    if($optionSize > 1 && substr($ele->attributes["name"], -2) != "[]" && !empty($ele->multiple))
                            $ele->attributes["name"] .= "[]";
                    $str .= $this->addAttributes($ele->attributes, "select");
                    if(!empty($ele->disabled))
                        $str .= ' disabled="disabled"';
                    if(!empty($ele->readonly))
                        $str .= ' readonly="readonly"';
                    if(!empty($ele->multiple))
                        $str .= ' multiple="multiple"';
                    $str .= ">\n";

                    $selected = false;
                    if(is_array($ele->options))
                    {
                        for($o = 0; $o < $optionSize; ++$o)
                        {
                            $str .= "\t\t\t";
                            if(!empty($this->map))
                                $str .= "\t\t\t";
                            $str .= '<option value="' . str_replace('"', '&quot;', $ele->options[$o]->value) . '"';
                            if((!is_array($ele->attributes["value"]) && !$selected && $ele->attributes["value"] == $ele->options[$o]->value) || 
                               (is_array($ele->attributes["value"]) && in_array($ele->options[$o]->value, $ele->attributes["value"], true)))
                            {
                                $str .= ' selected="selected"';
                                $selected = true;
                            }   
                            $str .= '>' . $ele->options[$o]->text . "</option>\n"; 
                        }   
                    }

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "</select>\n";
                    if($focus)
                        $this->focusElement = $ele->attributes["name"];
                }
                elseif($eleType == "combobox")
                {
                    // Ensure that comboboxes have unique ids.
                    if(empty($ele->attributes["id"]))
                        $ele->attributes["id"] = "combobox_" . rand(0, 999);
                    while(in_array($ele->attributes["id"], $jqueryComboboxSetupArr))
                        $ele->attributes["id"] = "combobox_" . rand(0, 999);
                    $jqueryComboboxSetupArr[$ele->attributes["id"]] = array('select_only' => isset($ele->attributes['select_only']) ? $ele->attributes['select_only'] : TRUE);   

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<select id='" . $ele->attributes["id"] . "' ";
                    $optionSize = 0;
                    if(is_array($ele->options)) $optionSize = sizeof($ele->options);
                    if($optionSize > 1 && substr($ele->attributes["name"], -2) != "[]" && !empty($ele->multiple))
                            $ele->attributes["name"] .= "[]";
                    $str .= $this->addAttributes($ele->attributes, "select");
                    if(!empty($ele->disabled))
                        $str .= ' disabled="disabled"';
                    if(!empty($ele->readonly))
                        $str .= ' readonly="readonly"';
                    if(!empty($ele->multiple))
                        $str .= ' multiple="multiple"';
                    $str .= ">\n";

                    $selected = false;
                    
                    if(is_array($ele->options))
                    {
                        for($o = 0; $o < $optionSize; ++$o)
                        {
                            $str .= "\t\t\t";
                            if(!empty($this->map))
                                $str .= "\t\t\t";
                            $str .= '<option value="' . str_replace('"', '&quot;', $ele->options[$o]->value) . '"';
                            if((!is_array($ele->attributes["value"]) && !$selected && $ele->attributes["value"] == $ele->options[$o]->value) || 
                               (is_array($ele->attributes["value"]) && in_array($ele->options[$o]->value, $ele->attributes["value"], true)))
                            {
                                $str .= ' selected="selected"';
                                $selected = true;
                            }   
                            $str .= '>' . $ele->options[$o]->text . "</option>\n"; 
                        }   
                    }
                    $str .= "</select>\n";

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                }
                elseif($eleType == "radio")
                {
                    if(is_array($ele->options))
                    {
                        $optionSize = sizeof($ele->options);
                        for($o = 0; $o < $optionSize; ++$o)
                        {
                            $str .= "\t\t";
                            if(!empty($this->map))
                                $str .= "\t\t\t";

                            if($o != 0)
                            {
                                if(!empty($ele->nobreak))
                                    $str .= "&nbsp;&nbsp;";
                                else
                                    $str .= "<br/>";
                            }   

                            $str .= "<input";
                            $str .= $this->addAttributes($ele->attributes, "radio");
                            $str .= ' id="' . str_replace('"', '&quot;', $ele->attributes["name"]) . $o . '" value="' . str_replace('"', '&quot;', $ele->options[$o]->value) . '"';     
                            if($ele->attributes["value"] == $ele->options[$o]->value)
                                $str .= ' checked="checked"';
                            if(!empty($ele->disabled))
                                $str .= ' disabled="disabled"';
                            $str .= '/>';
                            if(empty($this->noLabels))
                                $str .= '<label for="' . str_replace('"', '&quot;', $ele->attributes["name"]) . $o . '" style="cursor: pointer;">';
                            $str .= $ele->options[$o]->text;
                            if(empty($this->noLabels))
                                 $str .= "</label>\n"; 
                        }   
                        if($focus)
                            $this->focusElement = $ele->attributes["name"];
                    }
                }
                elseif($eleType == "checkbox")
                {
                    if(is_array($ele->options))
                    {
                        $optionSize = sizeof($ele->options);

                        if($optionSize > 1 && substr($ele->attributes["name"], -2) != "[]")
                            $ele->attributes["name"] .= "[]";
                        $list_as_columns = isset($ele->attributes['list_as_columns']) ? $ele->attributes['list_as_columns'] : 1;
                        if($list_as_columns < 1) $list_as_columns = 1;
                        $col_length = ceil($optionSize / $list_as_columns);
                        $str .= '<table><tr>';
                        $col = 0;
                        for($o = 0; $o < $optionSize; ++$o)
                        {
                            if($col == $list_as_columns)        // Jump to next row if all columns are filled.
                            {
                                $str .= '</tr><tr>';
                                $col = 0;
                            }
                            //$str .= '<td valign="top">';
                            //for($c = 0; $c < $col_length && $o < $optionSize; $c++)
                            //{
                                $str .= "\t\t";
                                if(!empty($this->map))
                                    $str .= "\t\t\t";

                                //if($o != 0)
                                //{
                                //    if(!empty($ele->nobreak))
                                //        $str .= "&nbsp;&nbsp;";
                                //    elseif($c != 0) // Don't want a break on the first item in a column
                                //        $str .= "<br/>";
                                //}   
                            $str .= '<td>';
                                $str .= "<input";
                                $str .= $this->addAttributes($ele->attributes, "radio");
                                $tmpID = str_replace(array('"', '[]'), array('&quot;', '-'), $ele->attributes["name"]) . $o;
                                $str .= ' id="' . $tmpID . '" value="' . str_replace('"', '&quot;', $ele->options[$o]->value) . '"';        

                                /*For checkboxes, the value parameter can be an array - which allows for multiple boxes to be checked by default.*/
                                if((!is_array($ele->attributes["value"]) && $ele->attributes["value"] == $ele->options[$o]->value) || (is_array($ele->attributes["value"]) && in_array($ele->options[$o]->value, $ele->attributes["value"])))
                                    $str .= ' checked="checked"';
                                if(!empty($ele->disabled))
                                    $str .= ' disabled="disabled"';
                                $str .= '/>';
                            $str .= '</td><td>';
                                if(empty($this->noLabels))
                                    $str .= '<label for="' . $tmpID . '" style="cursor: pointer;">';
                                $str .= $ele->options[$o]->text;
                                if(empty($this->noLabels))
                                    $str .= "</label>\n";
                                $o++;
                            //}
                            $o--;
                            $str .= '</td>';
                            $col++;
                        }   
                        $str .= '</tr></table>';
                        if($focus)
                            $this->focusElement = $ele->attributes["name"];
                    }
                }
                elseif($eleType == "date")
                {
                    if(empty($ele->attributes["style"]))
                        // $ele->attributes["style"] = "width: 100%; cursor: pointer;";
                        $ele->attributes["style"] = "cursor: pointer;";

                    if(empty($ele->attributes["id"]))
                        $ele->attributes["id"] = "dateinput_" . rand(0, 999);

                    /*Temporarily set the type attribute to "text" for <input> tag.*/
                    $eleType = "text";
                    
                    /*This section ensures that each date field has a unique identifier.*/
                    if(!isset($jqueryDateIDArr))
                        $jqueryDateIDArr = array();
                    while(in_array($ele->attributes["id"], $jqueryDateIDArr))
                        $ele->attributes["id"] = "dateinput_" . rand(0, 999);
                    $jqueryDateIDArr[] = $ele->attributes["id"];    

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<input";
                    $str .= $this->addAttributes($ele->attributes, "text");
                    $str .= ' readonly="readonly"';
                    $str .= "/>\n";

                    /*Now that <input> tag his been rendered, change type attribute back to "date".*/
                    $eleType = "date";
                }
                elseif($eleType == "daterange")
                {
                    if(empty($ele->attributes["style"]))
                        // $ele->attributes["style"] = "width: 100%; cursor: pointer;";
                        $ele->attributes["style"] = "cursor: pointer;";

                    if(empty($ele->attributes["id"]))
                        $ele->attributes["id"] = "daterangeinput_" . rand(0, 999);

                    /*Temporarily set the type attribute to "text" for <input> tag.*/
                    $eleType = "text";

                    /*This section ensure that each daterange field has a unique identifier.*/
                    if(!isset($jqueryDateRangeIDArr))
                        $jqueryDateRangeIDArr = array();
                    while(in_array($ele->attributes["id"], $jqueryDateRangeIDArr))
                        $ele->attributes["id"] = "daterangeinput_" . rand(0, 999);
                    $jqueryDateRangeIDArr[] = $ele->attributes["id"];   

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<input";
                    $str .= $this->addAttributes($ele->attributes, "text");
                    $str .= ' readonly="readonly"';
                    $str .= "/>\n";

                    /*Now that <input> tag his been rendered, change type attribute back to "date".*/
                    $eleType = "daterange";
                }
                elseif($eleType == "sort")
                {
                    if(is_array($ele->options))
                    {
                        $str .= "\t\t";
                        if(!empty($this->map))
                            $str .= "\t\t\t";

                        if(empty($ele->attributes["id"]))
                            $ele->attributes["id"] = "sort_" . rand(0, 999);
                        if(substr($ele->attributes["name"], -2) != "[]")
                            $ele->attributes["name"] .= "[]";

                        /*This section ensures that each sort field has a unique identifier.*/
                        if(!isset($jquerySortIDArr))
                            $jquerySortIDArr = array();
                        while(in_array($ele->attributes["id"], $jquerySortIDArr))
                            $ele->attributes["id"] = "sort_" . rand(0, 999);
                        $jquerySortIDArr[] = $ele->attributes["id"]; 

                        $str .= '<ul id="' . str_replace('"', '&quot;', $ele->attributes["id"]) . '" style="list-style-type: none; margin: 0; padding: 0; cursor: pointer;">' . "\n";
                        $optionSize = sizeof($ele->options);
                        for($o = 0; $o < $optionSize; ++$o)
                        {
                            $str .= "\t\t\t";
                            if(!empty($this->map))
                                $str .= "\t\t\t";
                            $str .= '<li class="ui-state-default" style="margin: 3px 0; padding-left: 0.5em; font-size: 1em; height: 2em; line-height: 2em;"><input type="hidden" name="' . str_replace('"', '&quot;', $ele->attributes["name"]) . '" value="' . str_replace('"', '&quot;', $ele->options[$o]->value) . '"/>' . $ele->options[$o]->text . '</li>' . "\n";
                        }   
                        $str .= "\t\t";
                        if(!empty($this->map))
                            $str .= "\t\t\t";
                        $str .= "</ul>\n";
                    }
                }
                elseif($eleType == "latlng")
                {
                    if(empty($ele->attributes["class"]))
                        $ele->attributes["class"] = "";
                    if(empty($ele->attributes["style"]))
                        $ele->attributes["style"] = "width: 100%;";
                    if(empty($ele->attributes["id"]))
                        $ele->attributes["id"] = "latlnginput_" . rand(0, 999);
                    

                    /*If there is a hint included, handle accordingly.*/
                    if(!empty($ele->hint) && empty($ele->attributes["value"]))
                    {
                        $hintFocusFunction = "hintfocus_" . $this->attributes["name"] . "(this);";
                        if(empty($ele->attributes["onfocus"]))
                            $ele->attributes["onfocus"] = $hintFocusFunction;
                        else
                            $ele->attributes["onfocus"] .= " " . $hintFocusFunction;

                        $hintBlurFunction = "hintblur_" . $this->attributes["name"] . "(this);";
                        if(empty($ele->attributes["onblur"]))
                            $ele->attributes["onblur"] = $hintBlurFunction;
                        else
                            $ele->attributes["onblur"] .= " " . $hintBlurFunction;
                    }   
                    
                    /*This section ensures that each latlng (Google Map) field has a unique identifier.*/
                    if(!isset($latlngIDArr))
                        $latlngIDArr = array();
                    while(array_key_exists($ele->attributes["id"], $latlngIDArr))
                        $ele->attributes["id"] = "latlnginput_" . rand(0, 999);
                    $latlngIDArr[$ele->attributes["id"]] = $ele; 

                    /*Temporarily set the type attribute to "text" for <input> tag.*/
                    $eleType = "text";

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<input";
                    $str .= $this->addAttributes($ele->attributes, "latlng");
                    $str .= ' value="';
                    if(!empty($ele->hint) && empty($ele->attributes["value"]))
                        $str .= $ele->hint;
                    elseif(!empty($ele->attributes["value"]) && is_array($ele->attributes["value"]))    
                        $str .=  "Latitude: " . $ele->attributes["value"][0] . ", Longitude: " . $ele->attributes["value"][1];
                    $str .= '"';

                    $str .= ' readonly="readonly"';
                    $str .= "/>\n";

                    /*Now that <input> tag his been rendered, change type attribute back to "latlng".*/
                    $eleType = "latlng";

                    if(empty($ele->latlngHeight))
                        $ele->latlngHeight = 200;

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= '<div id="' . str_replace('"', '&quot;', $ele->attributes["id"]) . '_canvas" style="margin: 2px 0; height: ' . $ele->latlngHeight . 'px;';
                    if(!empty($ele->latlngWidth))
                        $str .= ' width: ' . $ele->latlngWidth . 'px;';
                    $str .= '"></div>' . "\n";
                    if(empty($ele->latlngHideJump))
                    {
                        $str .= "\t\t";
                        if(!empty($this->map))
                            $str .= "\t\t\t";
                        $str .= '<input id="' . str_replace('"', '&quot;', $ele->attributes["id"]) . '_locationJump" type="text" value="Location Jump: Enter Keyword, City/State, Address, or Zip Code" style="' . str_replace('"', '&quot;', $ele->attributes["style"]) . '" class="' . str_replace('"', '&quot;', $ele->attributes["class"]) . '" onfocus="focusJumpToLatLng_' . $this->attributes["name"] . '(this);" onblur="blurJumpToLatLng_' . $this->attributes["name"] . '(this);" onkeyup="jumpToLatLng_' . $this->attributes["name"] . '(this, \'' . htmlentities($ele->attributes["id"], ENT_QUOTES) . '\', \'' . htmlentities($ele->attributes["name"]) . '\');"/>' . "\n";
                    }
                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= '<div id="' . str_replace('"', '&quot;', $ele->attributes["id"]) . '_clearDiv" style="margin-top: 2px;';
                    if(empty($ele->attributes["value"]) || !is_array($ele->attributes["value"]))
                        $str .= 'display: none;';
                    $str .= '"><small><a href="javascript: clearLatLng_' . $this->attributes["name"] . '(\'' . htmlentities($ele->attributes["id"], ENT_QUOTES) . '\', \'' . htmlentities($ele->attributes["name"]) . '\');">Clear Latitude/Longitude</a></small></div>';   
                }
                elseif($eleType == "checksort")
                {
                    if(is_array($ele->options))
                    {
                        if(empty($ele->attributes["id"]))
                            $ele->attributes["id"] = "checksort_" . rand(0, 999);
                        if(substr($ele->attributes["name"], -2) != "[]")
                            $ele->attributes["name"] .= "[]";

                        /*This section ensure that each checksort field has a unique identifier.  You will notice that sort and checksort are stores in the same
                        array (jquerySortIDArr).  This is done because they both use the same jquery ui sortable functionality.*/
                        if(!isset($jquerySortIDArr))
                            $jquerySortIDArr = array();
                        while(in_array($ele->attributes["id"], $jquerySortIDArr))
                            $ele->attributes["id"] = "checksort_" . rand(0, 999);
                        $jquerySortIDArr[] = $ele->attributes["id"]; 

                        /*This variable triggers a javascript section for handling the dynamic adding/removing of sortable option when a user clicks the checkbox.*/
                        $jqueryCheckSort = 1;

                        /*Temporary variable for building <ul> sorting structure for checked options.*/
                        $sortLIArr = array();

                        $optionSize = sizeof($ele->options);
                        for($o = 0; $o < $optionSize; ++$o)
                        {
                            $str .= "\t\t";
                            if(!empty($this->map))
                                $str .= "\t\t\t";

                            if($o != 0)
                            {
                                if(!empty($ele->nobreak))
                                    $str .= "&nbsp;&nbsp;";
                                else
                                    $str .= "<br/>";
                            }   

                            $str .= "<input";
                            $str .= $this->addAttributes($ele->attributes, "checksort");

                            $tmpID = str_replace(array('"', '[]'), array('&quot;', '-'), $ele->attributes["name"]) . $o;
                            $str .= ' id="' . $tmpID . '" type="checkbox" value="' . str_replace('"', '&quot;', $ele->options[$o]->value) . '" onclick="addOrRemoveCheckSortItem_' . $this->attributes["name"] . '(this, \'' . str_replace(array('"', "'"), array('&quot;', "\'"), $ele->attributes["id"]) . '\', \'' . str_replace(array('"', "'"), array('&quot;', "\'"), $ele->attributes["name"]) . '\', ' . $o . ', \'' . str_replace(array('"', "'"), array('&quot;', "\'"), $ele->options[$o]->value) . '\', \'' . str_replace(array('"', "'"), array('&quot;', "\'"), $ele->options[$o]->text) . '\');"';

                            /*For checkboxes, the value parameter can be an array - which allows for multiple boxes to be checked by default.*/
                            if((!is_array($ele->attributes["value"]) && $ele->attributes["value"] == $ele->options[$o]->value) || (is_array($ele->attributes["value"]) && in_array($ele->options[$o]->value, $ele->attributes["value"], true)))
                            {
                                $str .= ' checked="checked"';
                                $sortLIArr[$ele->options[$o]->value] = '<li id="' . str_replace('"', '&quot;', $ele->attributes["id"]) . $o . '" class="ui-state-default" style="margin: 3px 0; padding-left: 0.5em; font-size: 1em; height: 2em; line-height: 2em;"><input type="hidden" name="' . str_replace('"', '&quot;', $ele->attributes["name"]) . '" value="' . str_replace('"', '&quot;', $ele->options[$o]->value) . '"/></span>' . $ele->options[$o]->text . '</li>' . "\n";
                            }   
                            if(!empty($ele->disabled))
                                $str .= ' disabled="disabled"';
                            $str .= '/>';
                            if(empty($this->noLabels))
                                $str .= '<label for="' . $tmpID . '" style="cursor: pointer;">';
                            $str .= $ele->options[$o]->text;
                            if(empty($this->noLabels))
                                 $str .= "</label>\n"; 
                        }   

                        /*If there are any check options by default, render the <ul> sorting structure.*/
                        $str .= "\t\t";
                        if(!empty($this->map))
                            $str .= "\t\t\t";
                        $str .= '<ul id="' . str_replace('"', '&quot;', $ele->attributes["id"]) . '" style="list-style-type: none; margin: 0; padding: 0; cursor: pointer;">' . "\n";
                        if(!empty($sortLIArr))
                        {
                            if(is_array($ele->attributes["value"]))
                            {
                                $eleValueSize = sizeof($ele->attributes["value"]);
                                for($li = 0; $li < $eleValueSize; $li++)
                                {
                                    if(isset($sortLIArr[$ele->attributes["value"][$li]]))
                                    {
                                        $str .= "\t\t\t";
                                        if(!empty($this->map))
                                            $str .= "\t\t\t\t";
                                        $str .= $sortLIArr[$ele->attributes["value"][$li]]; 
                                    }
                                }
                            }
                            else
                            {
                                if(isset($sortLIArr[$ele->attributes["value"][$li]]))
                                {
                                    $str .= "\t\t\t";
                                    if(!empty($this->map))
                                        $str .= "\t\t\t\t";
                                    $str .= $sortLIArr[$ele->attributes["value"]];
                                }
                            }       
                        }
                        $str .= "\t\t";
                        if(!empty($this->map))
                            $str .= "\t\t\t";
                        $str .= "</ul>\n";
                    }
                }
                elseif($eleType == "captcha")
                {
                    if(empty($ele->attributes["id"]))
                        $ele->attributes["id"] = "captchainput_" . rand(0, 999);
                    
                    $captchaID = array();
                    $captchaID = $ele->attributes["id"]; 

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= '<div id="' . $ele->attributes["id"] . '"></div>' . "\n";
                        $str .= "\t\t\t";
                }
                elseif($eleType == "slider")
                {
                    if(empty($ele->attributes["id"]))
                        $ele->attributes["id"] = "sliderinput_" . rand(0, 999);

                    /*This section ensures that each slider field has a unique identifier.*/
                    if(!isset($jquerySliderIDArr))
                        $jquerySliderIDArr = array();
                    while(array_key_exists($ele->attributes["id"], $jquerySliderIDArr))
                        $ele->attributes["id"] = "sliderinput_" . rand(0, 999);
                    /*The bottom line of this section sets this specific variable to $ele.*/    
                    $jquerySliderIDArr[$ele->attributes["id"]] = "";

                    if(empty($ele->attributes["value"]))
                        $ele->attributes["value"] = "0";

                    if(empty($ele->sliderMin))
                        $ele->sliderMin = "0";

                    if(empty($ele->sliderMax))
                        $ele->sliderMax = "100";

                    if(empty($ele->sliderOrientation) || !in_array($ele->sliderOrientation, array("horizontal", "vertical")))
                        $ele->sliderOrientation = "horizontal";

                    if(empty($ele->sliderPrefix))
                        $ele->sliderPrefix = "";

                    if(empty($ele->sliderSuffix))
                        $ele->sliderSuffix = "";
                    
                    if(is_array($ele->attributes["value"]) && sizeof($ele->attributes["value"]) == 1)
                        $ele->attributes["value"] = $ele->attributes["value"][0];
                    
                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= '<div id="' . $ele->attributes["id"] . '" style="font-size: 12px !important; margin: 2px 0;';
                    if($ele->sliderOrientation == "vertical" && !empty($ele->sliderHeight))
                    {
                        if(substr($ele->sliderHeight, -2) != "px")
                            $ele->sliderHeight .= "px";
                        $str .= ' height: ' . $ele->sliderHeight;
                    }   
                    $str .= '"></div>' . "\n";

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    
                    if(empty($ele->sliderHideDisplay))
                    {
                        $str .= '<div id="' . $ele->attributes["id"] . '_display">';
                        if(is_array($ele->attributes["value"]))
                        {
                            sort($ele->attributes["value"]);
                            $str .= $ele->sliderPrefix . $ele->attributes["value"][0] . $ele->sliderSuffix . " - " . $ele->sliderPrefix . $ele->attributes["value"][1] . $ele->sliderSuffix;
                        }   
                        else
                            $str .= $ele->sliderPrefix . $ele->attributes["value"] . $ele->sliderSuffix;
                        $str .= '</div>' . "\n";    
                    }

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    if(is_array($ele->attributes["value"]))
                    {
                        if(substr($ele->attributes["name"], -2) != "[]")
                            $ele->attributes["name"] .= "[]";
                        $str .= '<input type="hidden" name="' . str_replace('"', '&quot;', $ele->attributes["name"]) . '" value="' . str_replace('"', '&quot;', $ele->attributes["value"][0]) . '"/>' . "\n";
                        $str .= "\t\t";
                        if(!empty($this->map))
                            $str .= "\t\t\t";
                        $str .= '<input type="hidden" name="' . str_replace('"', '&quot;', $ele->attributes["name"]) . '" value="' . str_replace('"', '&quot;', $ele->attributes["value"][1]) . '"/>' . "\n";
                    }
                    else
                        $str .= '<input type="hidden" name="' . str_replace('"', '&quot;', $ele->attributes["name"]) . '" value="' . str_replace('"', '&quot;', $ele->attributes["value"]) . '"/>' . "\n";

                    $jquerySliderIDArr[$ele->attributes["id"]] = $ele;
                }
                elseif($eleType == "rating")
                {
                    //if(empty($ele->attributes["style"]))
                    //    $ele->attributes["style"] = "width: 100%;";

                    /*This section ensures each rating field has a unique identifier.*/
                    $starratingID = "starrating_" . rand(0, 999);
                    if(!isset($jqueryStarRatingIDArr))
                        $jqueryStarRatingIDArr = array();
                    while(array_key_exists($starratingID, $jqueryStarRatingIDArr))
                        $starratingID = "starrating_" . rand(0, 999);
                    $jqueryStarRatingIDArr[$starratingID] = $ele;

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= '<table cellpadding="0" cellspacing="0" border="0"><tr><td valign="middle"><div id="' . $starratingID . '">' . "\n";

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<select";
                    $str .= $this->addAttributes($ele->attributes, "select");
                    if(!empty($ele->disabled))
                        $str .= ' disabled="disabled"';
                    if(!empty($ele->readonly))
                        $str .= ' readonly="readonly"';
                    if(!empty($ele->multiple))
                        $str .= ' multiple="multiple"';
                    $str .= ">\n";

                    $selected = false;
                    if(is_array($ele->options))
                    {
                        $optionSize = sizeof($ele->options);
                        for($o = 0; $o < $optionSize; ++$o)
                        {
                            $str .= "\t\t\t";
                            if(!empty($this->map))
                                $str .= "\t\t\t";
                            $str .= '<option value="' . str_replace('"', '&quot;', $ele->options[$o]->value) . '"';
                            if((!is_array($ele->attributes["value"]) && !$selected && $ele->attributes["value"] == $ele->options[$o]->value) || (is_array($ele->attributes["value"]) && in_array($ele->options[$o]->value, $ele->attributes["value"], true)))
                            {
                                $str .= ' selected="selected"';
                                $selected = true;
                            }   
                            $str .= '>' . $ele->options[$o]->text . "</option>\n"; 
                        }   
                    }

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "</select>\n";

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= '</div></td>';

                    if(empty($ele->ratingHideCaption))
                        $str .= '<td valign="middle"><div id="' . $starratingID . '_caption" style="padding-left: 5px;"></div></td>';
                        
                    $str .= '</tr></table>' . "\n";

                    if($focus)
                        $this->focusElement = $ele->attributes["name"];
                }
                elseif($eleType == "colorpicker")
                {
                    if(empty($ele->attributes["style"]))
                        // $ele->attributes["style"] = "width: 100%; cursor: pointer;";
                        $ele->attributes["style"] = "cursor: pointer;";

                    if(empty($ele->attributes["id"]))
                        $ele->attributes["id"] = "colorinput_" . rand(0, 999);

                    /*Temporarily set the type attribute to "text" for <input> tag.*/
                    $eleType = "text";
                    
                    /*This section ensures that each colorpicker field has a unique identifier.*/
                    if(!isset($jqueryColorIDArr))
                        $jqueryColorIDArr = array();
                    while(in_array($ele->attributes["id"], $jqueryColorIDArr))
                        $ele->attributes["id"] = "colorinput_" . rand(0, 999);
                    $jqueryColorIDArr[] = $ele->attributes["id"];   

                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= "<input";
                    $str .= $this->addAttributes($ele->attributes, "text");
                    $str .= "/>\n";
                    $str .= '<span id="' . $ele->attributes['id'] . '_display" style="width: 70px; border: 1px solid black; background-color: '.$ele->attributes['value'] . ';">&nbsp&nbsp&nbsp</span>';

                    /*Now that <input> tag his been rendered, change type attribute back to "colorpicker".*/
                    $eleType = "colorpicker";
                }
				// Adds a hour of day picker (drop down). Lists hours from 0 to 23 (12am to 12pm) or for a specified range of hours.
				elseif($eleType == "hour_of_day_picker")
				{
					$attributes = $ele->attributes;

					// For each hour generate the time values formatted as needed. What the user
					// sees will only be the hour component plus am/pm depending on if this was
					// specified in the timeFormat.
					$tmp_new_format = "";	// Holds the new format string.
					$tmp_allow = array('a','A','g','G','h','H', ' ');
					for($c = 0; $c < strlen($this->timeFormat); $c++)
					{
						$tmp_char = $this->timeFormat[$c];
						if(in_array($tmp_char, $tmp_allow))
							$tmp_new_format .= $tmp_char;
					}
					//echo "new format: $tmp_new_format<br/>";
					$tmp_time = DateTime::createFromFormat('H:i:s', '23:00:00');
					$times = array();
					for($h = 0; $h < 24; $h++)
					{
						$tmp_time->add(new DateInterval('PT1H'));
						$times[] = array($tmp_time->format($this->timeFormat),$tmp_time->format($tmp_new_format));
					}
					//print_r($times);
					$min = 0;
					$max = 0;
					if(isset($attributes['hour_range']) && is_array($attributes['hour_range']) && count($attributes['hour_range']) == 2)
					{
						$min = (int)$attributes['hour_range'][0];
						$min = $min < 0 ? 0 : $min;
						$max = (int)$attributes['hour_range'][1];
						$max = $max > 23 ? 23 : $max;
					}
					$options_list = '';
					$tmp_selected = '';
					for($h = $min; $h <= $max; $h++)
					{
						$tmp_selected = $attributes['value'] == $times[$h][0] ? 'selected="selected"' : '';
						$options_list .= '<option value="'.$times[$h][0]."\" $tmp_selected >" . $times[$h][1] . "</option>";
					}
					
					// Generate the select element.
					$str .= "\t\t" . (!empty($this->map) ? "\t\t\t" : '');
					$str .= '<select' . $this->addAttributes($attributes, "select") . ">\n";
					$str .= $options_list;
					$str .= '</select>';
				}
                elseif($eleType == "html")
                {
                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= $ele->attributes["value"] . "\n";
                }   

                if(!empty($ele->postHTML))
                {
                    $str .= "\t\t";
                    if(!empty($this->map))
                        $str .= "\t\t\t";
                    $str .= $ele->postHTML;
                    $str .= "\n";   
                }       

                // See if a label needs to be added below the field.
                if(!empty($ele->label) && $label_pos == 'below')
                    $this->_print_label($str, $ele, $label_pos, $label_align, $tooltipIDArr, $cell_widths, $cur_cell);
                    
                $str .= "\t";
                if(!empty($this->map))
                    $str .= "\t\t\t";
                $str .= "</td>";

                if(!empty($this->map))
                {
                    if(($i + 1) == $elementSize)
                        $str .= "\n\t\t\t</tr>\n\t\t</table>\n\t</td></tr>\n";
                    // elseif(array_key_exists($mapIndex, $this->map) && $this->map[$mapIndex] > 1)
                    elseif(array_key_exists($mapIndex, $this->map) && is_array($this->map[$mapIndex]) && 
                       count($this->map[$mapIndex]) > 2 && $this->map[$mapIndex][0] > 1)
                    {
                        if(($mapCount + 1) == $this->map[$mapIndex][0])
                        {
                            $mapCount = 0;
                            ++$mapIndex;
                            $str .= "\n\t\t\t</tr>\n\t\t</table>\n\t</td></tr>\n";
                        }
                        else
                        {
                            ++$mapCount;
                            $str .= "\n";
                        }   
                    }
                    else
                    {
                        ++$mapIndex;
                        $mapCount = 0;
                        $str .= "\n\t\t\t</tr>\n\t\t</table>\n\t</td></tr>\n";
                    }   
                }
                else
                    $str .= "</tr>\n";
                $focus = false;
				
				// Test if the element has been the given autocomplete capability. (2011/06/03)
				if(isset($ele->attributes['autocomplete']) && is_array($ele->attributes['autocomplete']))
				{
					$jqueryAutocompleteSetupArr[$ele->attributes["id"]] = $ele->attributes['autocomplete'];   
					//nice_vardump($jqueryAutocompleteSetupArr);
				}
				// Keep track of all elements that have been given ids.
				if(!empty($ele->attributes['id'])) array_push($element_ids, $ele->attributes['id']);
            }   
        }

        if(!empty($this->map) && !empty($mapOriginalWidth))
            $this->tdAttributes["width"] = $mapOriginalWidth;
        else
            unset($this->tdAttributes["width"]);

        if($includeTableTags)
            $str .= "</table>\n";

        if(!empty($jqueryDateIDArr) || !empty($jqueryDateRangeIDArr) || !empty($jquerySortIDArr) || !empty($tooltipIDArr) ||
            !empty($jquerySliderIDArr) || !empty($jqueryStarRatingIDArr) || !empty($jqueryColorIDArr) || 
            !empty($jqueryComboboxSetupArr) || !empty($jqueryAutocompleteSetupArr) ||
            !empty($this->post_init_js) || !empty($this->pre_init_js) || !empty($this->form_js) || !empty($use_js))
        {
            if(empty($this->preventJQueryLoad))
                $str .= "\n\t" . '<script type="text/javascript" src="' . $this->includesAbsoluteWebPath . '/jquery/jquery.js"></script>';

            if(!empty($jqueryDateIDArr) || !empty($jqueryDateRangeIDArr) || !empty($jquerySortIDArr) || !empty($jquerySliderIDArr) || 
               !empty($jqueryStarRatingIDArr) || !empty($jqueryComboboxSetupArr) || !empty($jqueryAutocompleteSetupArr))
            {
                $str .= "\n\t" . '<link href="' . $this->includesAbsoluteWebPath . '/jquery/jquery-ui.css" rel="stylesheet" type="text/css"/>';
                if(empty($this->preventJQueryUILoad))
                    $str .= "\n\t" . '<script type="text/javascript" src="' . $this->includesAbsoluteWebPath . '/jquery/jquery-ui.js"></script>';
            }

            if(!empty($tooltipIDArr) && empty($this->preventQTipLoad))
			{
                 $str .= "\n\t" . '<script type="text/javascript" src="' . $this->includesAbsoluteWebPath . '/jquery/qtip/jquery.qtip.min.js"></script>';
				 $str .= "\n\t" . '<link href="' . $this->includesAbsoluteWebPath . '/jquery/qtip/jquery.qtip.min.css" rel="stylesheet" type="text/css"/>';
			}

            if(!empty($jqueryDateIDArr))
                $str .= "\n\t" . '<style type="text/css">.ui-datepicker-div, .ui-datepicker-inline, #ui-datepicker-div { font-size: 0.8em !important; }</style>';

            if(!empty($jquerySliderIDArr))
                $str .= "\n\t" . '<style type="text/css">.ui-slider-handle { cursor: pointer !important; }</style>';

            if(!empty($jqueryDateRangeIDArr))
            {
                $str .= "\n\t" . '<link href="' . $this->includesAbsoluteWebPath . '/jquery/ui.daterangepicker.css" rel="stylesheet" type="text/css"/>';
                $str .= "\n\t" . '<script type="text/javascript" src="' . $this->includesAbsoluteWebPath . '/jquery/daterangepicker.jquery.js"></script>';
            }   

            if(!empty($jqueryStarRatingIDArr))
            {
                $str .= "\n\t" . '<style type="text/css">';
                $str .= '
        .ui-stars-star,
        .ui-stars-cancel {
            float: left;
            display: block;
            overflow: hidden;
            text-indent: -999em;
            cursor: pointer;
        }
        .ui-stars-star a,
        .ui-stars-cancel a {
            width: 28px;
            height: 26px;
            display: block;
            position: relative;
            background: transparent url("' . $this->includesAbsoluteWebPath . '/jquery/starrating/remove_inactive.png") 0 0 no-repeat;
            _background: none;
            filter: progid:DXImageTransform.Microsoft.AlphaImageLoader
                (src="' . $this->includesAbsoluteWebPath . '/jquery/starrating/remove_inactive.png", sizingMethod="scale");
        }
        .ui-stars-star a {
            background: transparent url("' . $this->includesAbsoluteWebPath . '/jquery/starrating/star_inactive.png") 0 0 no-repeat;
            _background: none;
            filter: progid:DXImageTransform.Microsoft.AlphaImageLoader
                (src="' . $this->includesAbsoluteWebPath . '/jquery/starrating/star_inactive.png", sizingMethod="scale");
        }
        .ui-stars-star-on a {
            background: transparent url("' . $this->includesAbsoluteWebPath . '/jquery/starrating/star_active.png") 0 0 no-repeat;
            _background: none;
            filter: progid:DXImageTransform.Microsoft.AlphaImageLoader
                (src="' . $this->includesAbsoluteWebPath . '/jquery/starrating/star_active.png", sizingMethod="scale");
        }
        .ui-stars-star-hover a {
            background: transparent url("' . $this->includesAbsoluteWebPath . '/jquery/starrating/star_hot.png") 0 0 no-repeat;
            _background: none;
            filter: progid:DXImageTransform.Microsoft.AlphaImageLoader
                (src="' . $this->includesAbsoluteWebPath . '/jquery/starrating/star_hot.png", sizingMethod="scale");
        }
        .ui-stars-cancel-hover a {
            background: transparent url("' . $this->includesAbsoluteWebPath . '/jquery/starrating/remove_active.png") 0 0 no-repeat;
            _background: none;
            filter: progid:DXImageTransform.Microsoft.AlphaImageLoader
                (src="' . $this->includesAbsoluteWebPath . '/jquery/starrating/remove_active.png", sizingMethod="scale");
        }
        .ui-stars-star-disabled,
        .ui-stars-star-disabled a,
        .ui-stars-cancel-disabled a {
            cursor: default !important;
        }';
                $str .= "\n\t" . '</style>';
                $str .= "\n\t" . '<script type="text/javascript" src="' . $this->includesAbsoluteWebPath . '/jquery/starrating/ui.stars.min.js"></script>';
            }

            if(!empty($jqueryColorIDArr))
            {
                $str .= "\n\t" . '<link href="' . $this->includesAbsoluteWebPath . '/jquery/colorpicker/colorpicker.css" rel="stylesheet" type="text/css"/>';
                $str .= "\n\t" . '<script type="text/javascript" src="' . $this->includesAbsoluteWebPath . '/jquery/colorpicker/colorpicker.js"></script>';
            }
            
            if(!empty($jqueryComboboxSetupArr))
            {   
                $str .= "\n\t" . '<link href="' . $this->includesAbsoluteWebPath . '/jquery/widget-combobox.css" rel="stylesheet" type="text/css"/>';
                $str .= "\n\t" . '<script type="text/javascript" src="' . $this->includesAbsoluteWebPath . '/jquery/widget-combobox.js"></script>';
            }
			// (2011/06/03)
			if(!empty($jqueryAutocompleteSetupArr))
			{
                $str .= "\n\t" . '<script type="text/javascript" src="' . $this->includesAbsoluteWebPath . '/jquery/widget-autocomplete.js"></script>';
			}

            $str .= "\n\t" . '<script type="text/javascript" defer="defer">';
            $str .= "\n\t\t" . "$(function() {";
            
            // Some code to run before any javascript initialisation occurs.
            if(!empty($this->pre_init_js))
            {
                $str .= "\n\t\t\t" . $this->pre_init_js;
            }
            
            if(!empty($jqueryDateIDArr))
            {
                $dateSize = sizeof($jqueryDateIDArr);
                for($d = 0; $d < $dateSize; ++$d)
                    $str .= "\n\t\t\t" . '$("#' . $jqueryDateIDArr[$d] . '").datepicker({ dateFormat: "' . $this->jqueryDateFormat . '", showButtonPanel: true });';
            }

            if(!empty($jqueryDateRangeIDArr))
            {
                $dateRangeSize = sizeof($jqueryDateRangeIDArr);
                for($d = 0; $d < $dateRangeSize; ++$d)
                    $str .= "\n\t\t\t" . '$("#' . $jqueryDateRangeIDArr[$d] . '").daterangepicker({ dateFormat: "' . $this->jqueryDateFormat . '" });';
            }

            if(!empty($jquerySortIDArr))
            {
                $sortSize = sizeof($jquerySortIDArr);
                for($s = 0; $s < $sortSize; ++$s)
                {
                    $str .= "\n\t\t\t" . '$("#' . $jquerySortIDArr[$s] . '").sortable({ axis: "y" });';
                    $str .= "\n\t\t\t" . '$("#' . $jquerySortIDArr[$s] . '").disableSelection();';
                }   
            }

            /*For more information on qtip, visit http://craigsworks.com/projects/qtip/.*/
            if(!empty($tooltipIDArr))
            {
                $tooltipKeys = array_keys($tooltipIDArr);
                $tooltipSize = sizeof($tooltipKeys);
                for($t = 0; $t < $tooltipSize; ++$t)
                {
                    $str .= "\n\t\t\t" . '$("#' . $tooltipKeys[$t] . '").qtip({ content: "' . str_replace('"', '\"', $tooltipIDArr[$tooltipKeys[$t]]) . '", style: { classes: "ui-tooltip-light", tip: { corner: "bottomLeft", width: 10, height: 8 } }, position: { at: "topRight", my: "bottomLeft" } });';
                }   
            }

            /*For more information on the jQuery UI slider, visit http://jqueryui.com/demos/slider/.*/
            if(!empty($jquerySliderIDArr))
            {
                $sliderKeys = array_keys($jquerySliderIDArr);
                $sliderSize = sizeof($jquerySliderIDArr);
                for($s = 0; $s < $sliderSize; ++$s)
                {
                    $slider = $jquerySliderIDArr[$sliderKeys[$s]];
                    $str .= "\n\t\t\t" . '$("#' . $sliderKeys[$s] . '").slider({';
                    if(is_array($slider->attributes["value"]))
                        $str .= 'range: true, values: [' . $slider->attributes["value"][0] . ', ' . $slider->attributes["value"][1] . ']';
                    else
                        $str .= 'range: "min", value: ' . $slider->attributes["value"];
                    $str .= ', min: ' . $slider->sliderMin . ', max: ' . $slider->sliderMax . ', orientation: "' . $slider->sliderOrientation . '"';
                    if(!empty($slider->sliderSnapIncrement))
                        $str .= ', step: ' . $slider->sliderSnapIncrement;
                    if(is_array($slider->attributes["value"]))
                    {
                        $str .= ', slide: function(event, ui) { ';
                        if(empty($slider->sliderHideDisplay))
                            $str .= '$("#' . $sliderKeys[$s] . '_display").text("' . $slider->sliderPrefix . '" + ui.values[0] + "' . $slider->sliderSuffix . ' - ' . $slider->sliderPrefix . '" + ui.values[1] + "' . $slider->sliderSuffix . '"); ';
                        $str .= 'document.getElementById("'. $this->attributes["id"]. '").elements["' . str_replace('"', '&quot;', $slider->attributes["name"]) . '"][0].value = ui.values[0]; document.getElementById("'. $this->attributes["id"]. '").elements["' . str_replace('"', '&quot;', $slider->attributes["name"]) . '"][1].value = ui.values[1];}';
                    }   
                    else
                    {
                        $str .= ', slide: function(event, ui) { ';
                        if(empty($slider->sliderHideDisplay))
                            $str .= '$("#' . $slider->attributes["id"] . '_display").text("' . $slider->sliderPrefix . '" + ui.value + "' . $slider->sliderSuffix . '");';
                        $str .= ' document.getElementById("'. $this->attributes["id"]. '").elements["' . str_replace('"', '&quot;', $slider->attributes["name"]) . '"].value = ui.value;}';
                    }   
                    $str .= '});';
                }
            }

            /*For more information on the jQuery rating plugin, visit http://plugins.jquery.com/project/Star_Rating_widget.*/
            if(!empty($jqueryStarRatingIDArr))
            {
                $ratingKeys = array_keys($jqueryStarRatingIDArr);
                $ratingSize = sizeof($jqueryStarRatingIDArr);
                for($r = 0; $r < $ratingSize; ++$r)
                {
                    $rating = $jqueryStarRatingIDArr[$ratingKeys[$r]];
                    $str .= "\n\t\t\t" . '$("#' . $ratingKeys[$r] . '").stars({';
                    if(empty($rating->ratingHideCaption))
                        $str .= "\n\t\t\t\t" . 'captionEl: $("#' . $ratingKeys[$r] . '_caption"),'; 
                    if(!empty($rating->ratingHideCancel))
                        $str .= "\n\t\t\t\t" . 'cancelShow: false,'; 
                    $str .= "\n\t\t\t\t" . 'inputType: "select", cancelValue: ""';
                    $str .= '});';
                }   
            }

            /*For more information on the jQuery colorpicker plugin, visit http://plugins.jquery.com/project/color_picker.*/
            if(!empty($jqueryColorIDArr))
            {
                $colorSize = sizeof($jqueryColorIDArr);
                for($c = 0; $c < $colorSize; ++$c)
                    $str .= "\n\t\t\t" . '$("#' . $jqueryColorIDArr[$c] . '").ColorPicker({ onSubmit: function(hsb, hex, rgb, el) { $(el).val(hex); $(el).ColorPickerHide(); $("#" + el.id + "_display").css("background-color", hex);}, onBeforeShow: function() { if(this.value != "Click to Select Color..." && this.value != "") $(this).ColorPickerSetColor(this.value); } }).bind("keyup", function(){ $(this).ColorPickerSetColor(this.value); });';
            }
            
            /* Added 2011/04/06. The combobox widget is based on the code found here: http://jqueryui.com/demos/autocomplete/combobox.html */
            if(!empty($jqueryComboboxSetupArr))
            {
                foreach($jqueryComboboxSetupArr as $id => $setup)
                {
                    if($setup['select_only'])
                    {
                        $str .= "\n\t\t\t" . '$( "#'.$id.'" ).combobox({select_only: true})';
                    }
                    else
                    {
                        $str .= "\n\t\t\t" . '$( "#'.$id.'" ).combobox({select_only: false})';
                    }
                }
            }
			
			/* Added 2011/06/03. Generate javascript for elements that have been given the autocomplete capability. */
			foreach($jqueryAutocompleteSetupArr as $id => $options)
			{
				if(isset($options['source_type']) && isset($options['source']))	// Must have these defined.
				{
					$source_type = $options['source_type'];
					$source = $options['source'];
					unset($options['source_type'], $options['source']);
					if($source_type == 'javascript')
					{
						$str .= "\n\t\t\t" . '$("#' . $id . '" ).multi_autocomplete({';
						foreach($options as $key => $val)
						{
							$str .= "'$key': '$val', ";
						}
						$str .= "'data_source': $source";
						$str .= "});";
					}
					elseif($source_type == 'server')
					{
						$options['data_source'] = $source;
						$str .= "\n\t\t\t" . '$("#' . $id . '" ).multi_autocomplete({';
						$parts = array();
						foreach($options as $key => $val)
						{
							$parts[] = "'$key': '$val'";
						}
						$str .= join(', ', $parts) . "});";
					}
				}
			}
			
			
            // Some code to run after any javascript initialisation.
            if(!empty($this->post_init_js))
            {
                $str .= "\n\t\t\t" . $this->post_init_js;
            }
            
            $str .= "\n\t\t});";

            $str .= "\n\t</script>\n\n";
            
            // Some user supplied javascript code to include with the form. Must not contain script tags.
            if(!empty($this->form_js))
            {
                $str .= "\n\t<script>\n" . $this->form_js . "\n\t</script>\n\n";
            }
        }   
        elseif((!empty($this->ajax) || !empty($this->emailExists)) && empty($this->preventJQueryLoad))
            $str .= "\n\t" . '<script type="text/javascript" src="' . $this->includesAbsoluteWebPath . '/jquery/jquery.js"></script>';

        if(!empty($latlngIDArr))
        {
            if(!empty($this->parentFormOverride))
                $latlngForm = $this->parentFormOverride;
            else
                $latlngForm = $this->attributes["name"];

            if(empty($this->latlngDefaultLocation))
                $this->latlngDefaultLocation = array(41.847, -87.661);
            if(empty($this->preventGoogleMapsLoad))
                $str .= "\n\t" . '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>';
            $str .= "\n\t" . '<script type="text/javascript">';
            $latlngSize = sizeof($latlngIDArr);
            $latlngKeys = array_keys($latlngIDArr);

            for($l = 0; $l < $latlngSize; ++$l)
            {
                $latlng = $latlngIDArr[$latlngKeys[$l]];
                $latlngID = str_replace('"', '&quot;', $latlng->attributes["id"]);
                $str .= "\n\t\t" . 'var map_' . $latlngID . ';';
                $str .= "\n\t\t" . 'var marker_' . $latlngID . ';';
                $str .= "\n\t\t" . 'var geocoder_' . $latlngID . ';';
            }
            $str .= "\n\t\tfunction initializeLatLng_" . $this->attributes["name"] . "() {";
            for($l = 0; $l < $latlngSize; ++$l)
            {
                $latlng = $latlngIDArr[$latlngKeys[$l]];
                $latlngID = str_replace('"', '&quot;', $latlng->attributes["id"]);
                $latlngHint = str_replace('"', '&quot;', $latlng->hint);
                if(!empty($latlng->attributes["value"]))
                {
                    $latlngCenter = $latlng->attributes["value"];
                    if(empty($latlng->latlngZoom))
                        $latlngZoom = 9;
                    else
                        $latlngZoom = $latlng->latlngZoom;
                }       
                else    
                {
                    $latlngCenter = $this->latlngDefaultLocation;
                    if(empty($latlng->latlngZoom))
                        $latlngZoom = 5;
                    else
                        $latlngZoom = $latlng->latlngZoom;
                }   
                $str .= "\n\t\t\t" . 'geocoder_' . $latlngID . ' = new google.maps.Geocoder();';
                $str .= "\n\t\t\t" . 'var latlng_' . $latlngID . ' = new google.maps.LatLng(' . $latlngCenter[0] . ', ' . $latlngCenter[1] . ');';
                $str .= "\n\t\t\t" . 'var mapoptions_' . $latlngID . ' = { zoom: ' . $latlngZoom . ', center: latlng_' . $latlngID . ', mapTypeId: google.maps.MapTypeId.ROADMAP, mapTypeControl: false }';
                $str .= "\n\t\t\t" . 'map_' . $latlngID . ' = new google.maps.Map(document.getElementById("' . $latlngID . '_canvas"), mapoptions_' . $latlngID . ');';
                $str .= "\n\t\t\t" . 'var markeroptions_' . $latlngID . ' = { position: latlng_' . $latlngID . ', map: map_' . $latlngID . ', draggable: true }';
                $str .= "\n\t\t\t" . 'marker_' . $latlngID . ' = new google.maps.Marker(markeroptions_' . $latlngID . ');';
                $str .= "\n\t\t\t" . 'google.maps.event.addListener(marker_' . $latlngID . ', "dragend", function() {';
                    $str .= "\n\t\t\t\tvar latlng = marker_" . $latlngID . ".getPosition();";
                    $str .= "\n\t\t\t\tvar lat = latlng.lat();";
                    $str .= "\n\t\t\t\tvar lng = latlng.lng();";
                    $str .= "\n\t\t\t\t" . 'document.getElementById("' . $latlngForm . '").elements["' . str_replace('"', '&quot;', $latlng->attributes["name"]) . '"].value = "Latitude: " + lat.toFixed(3) + ", Longitude: " + lng.toFixed(3);';
                    $str .= "\n\t\t\t\t" . 'document.getElementById("' . $latlngID . '_clearDiv").style.display = "block";';
                $str .= "\n\t\t\t});";
            }
            $str .= "\n\t\t}";
            $str .= "\n\t\tfunction jumpToLatLng_" . $this->attributes["name"] . "(fieldObj, latlngID, fieldName) {";
                $str .= "\n\t\t\teval('var geocoderObj = geocoder_' + latlngID);";
                $str .= "\n\t\t\teval('var mapObj = map_' + latlngID);";
                $str .= "\n\t\t\teval('var markerObj = marker_' + latlngID);";
                $str .= "\n\t\t\tif(geocoderObj) {";
                    $str .= "\n\t\t\t\tgeocoderObj.geocode({'address': fieldObj.value}, function(results, status) {";
                        $str .= "\n\t\t\t\t\tif(status == google.maps.GeocoderStatus.OK) {";
                            $str .= "\n\t\t\t\t\t\tmapObj.setCenter(results[0].geometry.location);";
                            $str .= "\n\t\t\t\t\t\tmarkerObj.setPosition(results[0].geometry.location);";
                            $str .= "\n\t\t\t\t\t\tvar lat = results[0].geometry.location.lat();";
                            $str .= "\n\t\t\t\t\t\tvar lng = results[0].geometry.location.lng();";
                            $str .= "\n\t\t\t\t\t\t" . 'document.getElementById("'. $latlngForm . '").elements[fieldName].value = "Latitude: " + lat.toFixed(3) + ", Longitude: " + lng.toFixed(3);';
                            $str .= "\n\t\t\t\t\t\t" . 'document.getElementById(latlngID + "_clearDiv").style.display = "block";';
                        $str .= "\n\t\t\t\t\t}";
                    $str .= "\n\t\t\t\t});";
                $str .= "\n\t\t\t}";
            $str .= "\n\t\t}";
            $str .= "\n\t\tfunction focusJumpToLatLng_" . $this->attributes["name"] . "(fieldObj) {";
                $str .= "\n\t\t\tif(fieldObj.value == 'Location Jump: Enter Keyword, City/State, Address, or Zip Code')";
                    $str .= "\n\t\t\t\tfieldObj.value = '';";
            $str .= "\n\t\t}";
            $str .= "\n\t\tfunction blurJumpToLatLng_" . $this->attributes["name"] . "(fieldObj) {";
                $str .= "\n\t\t\tif(fieldObj.value == '')";
                    $str .= "\n\t\t\t\tfieldObj.value = 'Location Jump: Enter Keyword, City/State, Address, or Zip Code';";
            $str .= "\n\t\t}";
            $str .= "\n\t\tfunction clearLatLng_" . $this->attributes["name"] . "(latlngID, latlngFieldName) {";
                    $str .= "\n\t\t\t" . 'if(document.getElementById("'. $latlngForm . '").elements[latlngID + "_locationJump"])';
                        $str .= "\n\t\t\t\t" . 'document.getElementById("'. $latlngForm . '").elements[latlngID + "_locationJump"].value = "Location Jump: Enter Keyword, City/State, Address, or Zip Code";';
                    $str .= "\n\t\t\t" . 'document.getElementById("'.  $latlngForm . '").elements[latlngFieldName].value = "' . $latlngHint . '";';
                    $str .= "\n\t\t\t" . 'document.getElementById(latlngID + "_clearDiv").style.display = "none";';
            $str .= "\n\t\t}";
            $str .= "\n\t\t" . 'if(window.addEventListener) { window.addEventListener("load", initializeLatLng_' . $this->attributes["name"] . ', false); }'; 
            $str .= "\n\t\t" . 'else if(window.attachEvent) { window.attachEvent("onload", initializeLatLng_' . $this->attributes["name"] . '); }'; 
            $str .= "\n\t</script>\n\n";
        }

        if(!empty($jqueryCheckSort))
        {
            $str .= "\n\t" . '<script type="text/javascript" defer="defer">';
                $str .= "\n\t\tfunction addOrRemoveCheckSortItem_" . $this->attributes["name"] . "(cs_fieldObj, cs_id, cs_name, cs_index, cs_value, cs_text) {";
                    $str .= "\n\t\t\tif(cs_fieldObj.checked != true)";
                        $str .= "\n\t\t\t\t" . 'document.getElementById(cs_id).removeChild(document.getElementById(cs_id + cs_index));';
                    $str .= "\n\t\t\telse {";
                        $str .= "\n\t\t\t\tvar li = document.createElement('li');";
                        $str .= "\n\t\t\t\tli.id = cs_id + cs_index;";
                        $str .= "\n\t\t\t\tli.className = 'ui-state-default';";
                        $str .= "\n\t\t\t\tli.style.cssText = 'margin: 3px 0; padding-left: 0.5em; font-size: 1em; height: 2em; line-height: 2em;'";
                        $str .= "\n\t\t\t\tli.innerHTML = '<input type=\"hidden\" name=\"' + cs_name + '\" value=\"' + cs_value + '\"/>' + cs_text;";
                        $str .= "\n\t\t\t\tdocument.getElementById(cs_id).appendChild(li);";
                    $str .= "\n\t\t\t}";
                $str .= "\n\t\t}";
            $str .= "\n\t</script>\n\n";
        }

        if(!empty($this->tinymceIDArr))
        {
            if(empty($this->preventTinyMCELoad))
                $str .= "\n\t" . '<script type="text/javascript" src="' . $this->includesAbsoluteWebPath . '/tinymce/tiny_mce.js"></script>';

            if(empty($this->preventTinyMCEInitLoad))
            {
                $str .= "\n\t" . '<script type="text/javascript">';
                    $str .= "\n\t\ttinyMCE.init({";
                        $str .= "\n\t\t\t" . 'mode: "textareas",';
                        $str .= "\n\t\t\t" . 'theme: "advanced",';
                        $str .= "\n\t\t\t" . 'plugins: "safari,table,paste,inlinepopups",';
                        $str .= "\n\t\t\t" . 'dialog_type: "modal",';
                        $str .= "\n\t\t\t" . 'theme_advanced_buttons1: "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,outdent,indent,|,forecolor,backcolor",';
                        $str .= "\n\t\t\t" . 'theme_advanced_buttons2: "formatselect,fontselect,fontsizeselect,|,pastetext,pasteword,|,link,image",';
                        $str .= "\n\t\t\t" . 'theme_advanced_buttons3: "tablecontrols,|,code,cleanup,|,undo,redo",';
                        $str .= "\n\t\t\t" . 'theme_advanced_toolbar_location: "top",';
                        $str .= "\n\t\t\t" . 'editor_selector: "tiny_mce",';
                        $str .= "\n\t\t\t" . 'forced_root_block: false,';
                        $str .= "\n\t\t\t" . 'force_br_newlines: true,';
                        $str .= "\n\t\t\t" . 'force_p_newlines: false';
                    $str .= "\n\t\t});";
                    $str .= "\n\t\ttinyMCE.init({";
                        $str .= "\n\t\t\t" . 'mode: "textareas",';
                        $str .= "\n\t\t\t" . 'theme: "simple",';
                        $str .= "\n\t\t\t" . 'editor_selector: "tiny_mce_simple",';
                        $str .= "\n\t\t\t" . 'forced_root_block: false,';
                        $str .= "\n\t\t\t" . 'force_br_newlines: true,';
                        $str .= "\n\t\t\t" . 'force_p_newlines: false';
                    $str .= "\n\t\t});";
                $str .= "\n\t</script>\n\n";
            }
        }

        if(!empty($this->ckeditorIDArr))
        {
            if(empty($this->preventCKEditorLoad))
                $str .= "\n\t" . '<script type="text/javascript" src="' . $this->includesAbsoluteWebPath . '/ckeditor/ckeditor.js"></script>';

            $str .= "\n\t" . '<script type="text/javascript">';
            $ckeditorSize = sizeof($this->ckeditorIDArr);
            $ckeditorKeys = array_keys($this->ckeditorIDArr);

            for($c = 0; $c < $ckeditorSize; ++$c)
            {
                $ckeditor = $this->ckeditorIDArr[$ckeditorKeys[$c]];
                $ckeditorID = str_replace('"', '&quot;', $ckeditor->attributes["id"]);
                $ckeditorParamArr = array();
                if(!empty($ckeditor->ckeditorBasic))
                    $ckeditorParamArr[] = 'toolbar: "Basic"';
                if(!empty($this->ckeditorCustomConfig)) 
                    $ckeditorParamArr[] = 'customConfig: "' . $this->ckeditorCustomConfig . '"';
                if(!empty($this->ckeditorLang))
                    $ckeditorParamArr[] = 'language: "' . $this->ckeditorLang . '"';
                $str .= "\n\t\t" . 'CKEDITOR.replace("' . $ckeditorID . '"';
                if(!empty($ckeditorParamArr))
                    $str .=  ", { " . implode(", ", $ckeditorParamArr) . " }";
                $str .= ");";
            }
            $str .= "\n\t</script>\n\n";
        }   

        if(!empty($captchaID))
        {
            if(empty($this->preventCaptchaLoad))
                $str .= "\n\t" . '<script type="text/javascript" src="http://api.recaptcha.net/js/recaptcha_ajax.js"></script>';
            
            $str .= "\n\t" . '<script type="text/javascript">';
                $str .= "\n\t\t" . 'Recaptcha.create("' . $this->captchaPublicKey . '", "' . $captchaID . '", { theme: "' . $this->captchaTheme . '", lang: "' . $this->captchaLang . '" });';
            $str .= "\n\t</script>\n\n";
        }

        if(!empty($this->hintExists))
        {
            $str .= "\n\t" . '<script type="text/javascript">';
                $str .= "\n\t\t" . 'function hintfocus_' . $this->attributes["name"] . '(eleObj) {';
                    $str .= "\n\t\t\tif(eleObj.value == eleObj.defaultValue)";
                        $str .= "\n\t\t\t\teleObj.value = '';";
                $str .= "\n\t\t}";
                $str .= "\n\t\t" . 'function hintblur_' . $this->attributes["name"] . '(eleObj) {';
                    $str .= "\n\t\t\tif(eleObj.value == '')";
                        $str .= "\n\t\t\t\teleObj.value = eleObj.defaultValue;";
                $str .= "\n\t\t}";
            $str .= "\n\t</script>\n\n";
        }

        return $str;
    }

    
    private function jsCycleElements($elements)
    {
        $elementSize = sizeof($elements);
        for($i = 0; $i < $elementSize; ++$i)
        {
            $ele = $elements[$i];
            $eleType = $ele->attributes["type"];
            $eleName = str_replace('"', '&quot;', $ele->attributes["name"]);
            $eleLabel = str_replace('"', '&quot;', strip_tags($ele->label));
            $alertMsg = 'alert("' . str_replace(array("[LABEL]", '"'), array($eleLabel, '&quot;'), $this->errorMsgFormat) . '");';
            $validateMsg = 'alert("' . str_replace(array("[LABEL]", '"'), array($eleLabel, '&quot;'), $this->validateErrorMsgFormat) . '");';       // Added (2010/04/14) for validation.
            
            if($eleType == "html")
                continue;

            if($eleType == "checkbox")
            {
                echo "\n\t\t" , 'if(formObj.elements["', $eleName, '"].length) {';
                    if(!empty($ele->required))
                        echo "\n\t\t\tvar is_checked = false;";
                    echo "\n\t\t\t", 'for(i = 0; i < formObj.elements["', $eleName, '"].length; i++) {';
                        echo "\n\t\t\t\t", 'if(formObj.elements["', $eleName, '"][i].checked) {';
                        if(!empty($this->ajax))
                            echo "\n\t\t\t\t\t", 'form_data += "&', $eleName, '=" + escape(formObj.elements["', $eleName, '"][i].value);';
                        if(!empty($ele->required))
                            echo "\n\t\t\t\t\tis_checked = true;";
                        echo "\n\t\t\t\t}";
                    echo "\n\t\t\t}";       
                    if(!empty($ele->required))
                    {
                        echo "\n\t\t\tif(!is_checked) {";
                            echo "\n\t\t\t\t", $alertMsg;
                            echo "\n\t\t\t\treturn false;";
                        echo "\n\t\t\t}";
                    }
                echo "\n\t\t}";     
                echo "\n\t\telse {";
                if(!empty($this->ajax))
                {
                    echo "\n\t\t\t", 'if(formObj.elements["', $eleName, '"].checked)';
                        echo "\n\t\t\t", 'form_data += "&', $eleName, '=" + escape(formObj.elements["', $eleName, '"].value);';
                }   
                if(!empty($ele->required))
                {
                    echo "\n\t\t\t", 'if(!formObj.elements["', $eleName, '"].checked) {';
                        echo "\n\t\t\t\t", $alertMsg;
                        echo "\n\t\t\t\treturn false;";
                    echo "\n\t\t\t}";
                }
                echo "\n\t\t}";
            }
            elseif($eleType == "radio")
            {
                echo "\n\t\t" , 'if(formObj.elements["', $eleName, '"].length) {';
                    if(!empty($ele->required))
                        echo "\n\t\t\tvar is_checked = false;";
                    echo "\n\t\t\t", 'for(i = 0; i < formObj.elements["', $eleName, '"].length; i++) {';
                        echo "\n\t\t\t\t", 'if(formObj.elements["', $eleName, '"][i].checked) {';
                        if(!empty($this->ajax))
                            echo "\n\t\t\t\t\t", 'form_data += "&', $eleName, '=" + escape(formObj.elements["', $eleName, '"][i].value);';
                        if(!empty($ele->required))
                            echo "\n\t\t\t\t\tis_checked = true;";
                        echo "\n\t\t\t\t}";
                    echo "\n\t\t\t}";       
                    if(!empty($ele->required))
                    {
                        echo "\n\t\t\tif(!is_checked) {";
                            echo "\n\t\t\t\t", $alertMsg;
                            echo "\n\t\t\t\treturn false;";
                        echo "\n\t\t\t}";
                    }
                echo "\n\t\t}";     
                echo "\n\t\telse {";
                if(!empty($this->ajax))
                {
                    echo "\n\t\t\t", 'if(formObj.elements["', $eleName, '"].checked)';
                        echo "\n\t\t\t", 'form_data += "&', $eleName, '=" + escape(formObj.elements["', $eleName, '"].value);';
                }   
                if(!empty($ele->required))
                {
                    echo "\n\t\t\t", 'if(!formObj.elements["', $eleName, '"].checked) {';
                        echo "\n\t\t\t\t", $alertMsg;
                        echo "\n\t\t\t\treturn false;";
                    echo "\n\t\t\t}";
                }
                echo "\n\t\t}";
            }
            elseif($eleType == "text" || $eleType == "textarea" || $eleType == "date" || $eleType == "daterange" || $eleType == "latlng" || $eleType == "colorpicker" || $eleType == "email")
            {
                $eleHint = str_replace('"', '&quot;', $ele->hint);
                if(!empty($this->ajax))
                {
                    echo "\n\t\t", 'form_data += "&', $eleName, '="';
                    echo "\n\t\t" , 'if(formObj.elements["', $eleName, '"].value != "', $eleHint, '")';
                        echo "\n\t\t\t", 'form_data += formObj.elements["', $eleName, '"].value;';
                }   
                if(!empty($ele->required))
                {
                    echo "\n\t\t" , 'if(formObj.elements["', $eleName, '"].value == "', $eleHint, '") {';
                        echo "\n\t\t\t", $alertMsg;
                        echo "\n\t\t\t", 'formObj.elements["', $eleName, '"].focus();';
                        echo "\n\t\t\treturn false;";
                    echo "\n\t\t}";
                }
                // Added javascript validation code. (2010/04/14)
                if(!empty($ele->attributes['validate']) && ($eleType == "text" || $eleType == "textarea"))
                {
                    echo "\n\t\t", 'var value = formObj.elements["', $eleName, '"].value;';
                    echo "\n\t\t" , 'if(value != "" && !value.match(', $ele->attributes['validate'], ') ) {';
                    echo "\n\t\t\t", $validateMsg;
                    echo "\n\t\t\t", 'formObj.elements["', $eleName, '"].focus();';
                    echo "\n\t\t\treturn false;";
                    echo "\n\t\t}";
                }
            }
            elseif($eleType == "select" || $eleType == "hidden" || $eleType == "file" || $eleType == "password")
            {
                if(!empty($this->ajax))
                    echo "\n\t\t", 'form_data += "&', $eleName, '=" + escape(formObj.elements["', $eleName, '"].value);';
                if(!empty($ele->required))
                {
                    echo "\n\t\t" , 'if(formObj.elements["', $eleName, '"].value == "") {';
                        echo "\n\t\t\t", $alertMsg;
                        echo "\n\t\t\t", 'formObj.elements["', $eleName, '"].focus();';
                        echo "\n\t\t\treturn false;";
                    echo "\n\t\t}";
                }
            }
            elseif($eleType == "rating")
            {
                if(!empty($this->ajax))
                {
                    echo "\n\t\t" , 'if(formObj.elements["', $eleName, '"].value != "") {';
                        echo "\n\t\t\t", 'form_data += "&', $eleName, '=" + escape(formObj.elements["', $eleName, '"].value);';
                }   
                if(!empty($ele->required))
                {
                    echo "\n\t\t" , 'if(formObj.elements["', $eleName, '"].value == "") {';
                        echo "\n\t\t\t", $alertMsg;
                        echo "\n\t\t\t", 'formObj.elements["', $eleName, '"].focus();';
                        echo "\n\t\t\treturn false;";
                    echo "\n\t\t}";
                }
            }
            elseif($eleType == "slider")
            {
                if(!empty($this->ajax))
                {
                    echo "\n\t\t" , 'if(formObj.elements["', $eleName, '"].length) {';
                        echo "\n\t\t\t\t\t", 'form_data += "&', $eleName, '=" + escape(formObj.elements["', $eleName, '"][0].value);';
                        echo "\n\t\t\t\t\t", 'form_data += "&', $eleName, '=" + escape(formObj.elements["', $eleName, '"][1].value);';
                    echo "\n\t\t}";     
                    echo "\n\t\telse {";
                        echo "\n\t\t\t\t\t", 'form_data += "&', $eleName, '=" + escape(formObj.elements["', $eleName, '"].value);';
                    echo "\n\t\t}";     
                }   
            }
            elseif($eleType == "captcha")
            {
                if(!empty($ele->required))
                {
                    echo "\n\t\t" , 'if(formObj.elements["recaptcha_response_field"].value == "") {';
                        echo "\n\t\t\t", $alertMsg;
                        echo "\n\t\t\t", 'formObj.elements["recaptcha_response_field"].focus();';
                        echo "\n\t\t\treturn false;";
                    echo "\n\t\t}";
                }
                if(!empty($this->ajax))
                {
                    echo "\n\t\t", 'form_data += "&recaptcha_challenge_field=" + escape(Recaptcha.get_challenge());';
                    echo "\n\t\t", 'form_data += "&recaptcha_response_field=" + escape(Recaptcha.get_response());';
                }   
            }
            elseif($eleType == "webeditor")
            {
                if(!empty($this->ajax))
                    echo "\n\t\t", 'form_data += "&', $eleName, '=" + escape(tinyMCE.get("', $ele->attributes["id"], '").getContent());';
                if(!empty($ele->required))
                {
                    echo "\n\t\t", 'if(tinyMCE.get("', $ele->attributes["id"], '").getContent() == "") {';
                        echo "\n\t\t\t", $alertMsg;
                        echo "\n\t\t\t" , 'tinyMCE.get("', $ele->attributes["id"], '").focus();';
                        echo "\n\t\t\treturn false;";
                    echo "\n\t\t}";
                }
            }
            elseif($eleType == "ckeditor")
            {
                if(!empty($this->ajax))
                    echo "\n\t\t", 'form_data += "&', $eleName, '=" + escape(CKEDITOR.instances.' . $ele->attributes["id"] . '.getData());';
                if(!empty($ele->required))
                {
                    echo "\n\t\t" , 'if( CKEDITOR.instances.' . $ele->attributes["id"] . '.getData() == "") {';
                        echo "\n\t\t\t", $alertMsg;
                        echo "\n\t\t\t" , 'CKEDITOR.instances.' . $ele->attributes["id"] . '.focus();';
                        echo "\n\t\t\treturn false;";
                    echo "\n\t\t}";
                }
            }
            elseif($eleType == "checksort")
            {
                if(!empty($this->ajax))
                {
                    echo "\n\t\t", 'if(formObj.elements["', $eleName, '"]) {';
                        echo "\n\t\t\t" , 'if(formObj.elements["', $eleName, '"].length) {';
                            echo "\n\t\t\t\t" , 'var ulObj = document.getElementById("', str_replace('"', '&quot;', $ele->attributes["id"]), '");';
                            echo "\n\t\t\t\tvar childLen = ulObj.childNodes.length;";
                            echo "\n\t\t\t\tfor(i = 0; i < childLen; i++) {";
                                echo "\n\t\t\t\t\t", 'childObj = document.getElementById("', str_replace('"', '&quot;', $ele->attributes["id"]), '").childNodes[i];';
                                    echo "\n\t\t\t\t\t", 'if(childObj.tagName && childObj.tagName.toLowerCase() == "li")';
                                        echo "\n\t\t\t\t\t\t", 'form_data += "&', $eleName, '=" + escape(childObj.childNodes[0].value);';
                            echo "\n\t\t\t\t}";
                        echo "\n\t\t\t}";
                        echo "\n\t\t\telse";
                            echo "\n\t\t\t\t", 'form_data += "&', $eleName, '=" + escape(formObj.elements["', $eleName, '"].value);';
                    echo "\n\t\t}";
                }
                if(!empty($ele->required))
                {
                    echo "\n\t\t", 'if(!formObj.elements["', $eleName, '"]) {';
                        echo "\n\t\t\t", $alertMsg;
                        echo "\n\t\t\treturn false;";
                    echo "\n\t\t}"; 
                }   
            }
            elseif(!empty($this->ajax) && $eleType == "sort")
            {
                echo "\n\t\t", 'if(formObj.elements["', $eleName, '"]) {';
                    echo "\n\t\t\t" , 'if(formObj.elements["', $eleName, '"].length) {';
                        echo "\n\t\t\t\t" , 'var ulObj = document.getElementById("', str_replace('"', '&quot;', $ele->attributes["id"]), '");';
                        echo "\n\t\t\t\tvar childLen = ulObj.childNodes.length;";
                        echo "\n\t\t\t\tfor(i = 0; i < childLen; i++) {";
                            echo "\n\t\t\t\t\t", 'childObj = document.getElementById("', str_replace('"', '&quot;', $ele->attributes["id"]), '").childNodes[i];';
                                echo "\n\t\t\t\t\t", 'if(childObj.tagName && childObj.tagName.toLowerCase() == "li")';
                                    echo "\n\t\t\t\t\t\t", 'form_data += "&', $eleName, '=" + escape(childObj.childNodes[0].value);';
                        echo "\n\t\t\t\t}";
                    echo "\n\t\t\t}";
                    echo "\n\t\t\telse";
                        echo "\n\t\t\t\t", 'form_data += "&', $eleName, '=" + escape(formObj.elements["', $eleName, '"].value);';
                echo "\n\t\t}";
            }
            
            if($eleType == "email")
            {
                echo "\n\t\t" , 'if(formObj.elements["', $eleName, '"].value != "', $eleHint, '") {';
                    echo "\n\t\t\t$.ajax({";
                        echo "\n\t\t\t\t", 'async: false,';
                        echo "\n\t\t\t\t", 'type: "post",';
                        echo "\n\t\t\t\t", 'url: "', $this->includesRelativePath, '/php-email-address-validation/ajax-handler.php",';
                        echo "\n\t\t\t\t", 'dataType: "text",';
                        echo "\n\t\t\t\t", 'data: "email=" + escape(formObj.elements["', $eleName, '"].value) + "&label=" + escape("', $eleLabel, '") + "&format=" + escape("', $this->emailErrorMsgFormat, '"),';
                        echo "\n\t\t\t\tsuccess: function(responseMsg, textStatus) {";
                            echo "\n\t\t\t\t\t", 'if(responseMsg != "") {';
                                echo "\n\t\t\t\t\t\tvalidemail_", $this->attributes["name"], " = false;";
                                echo "\n\t\t\t\t\t\talert(responseMsg);";
                            echo "\n\t\t\t\t\t}";
                            echo "\n\t\t\t\t\telse";
                                echo "\n\t\t\t\t\t\tvalidemail_", $this->attributes["name"], " = true;";
                        echo "\n\t\t\t\t},";
                        echo "\n\t\t\t\terror: function(XMLHttpRequest, textStatus, errorThrown) { alert(XMLHttpRequest.responseText); }";
                    echo "\n\t\t\t});";

                    echo "\n\t\t\tif(!validemail_", $this->attributes["name"], ") {";
                        echo "\n\t\t\t\t", 'formObj.elements["', $eleName, '"].focus();';
                        echo "\n\t\t\t\treturn false;";
                    echo "\n\t\t\t}";
                echo "\n\t\t}";
            }
        }   

        /*Remove hints if they remain as form element values.*/
        for($i = 0; $i < $elementSize; ++$i)
        {
            $ele = $elements[$i];
            if(!empty($ele->hint))
            {
                $eleName = str_replace('"', '&quot;', $ele->attributes["name"]);
                echo "\n\t\t", 'if(formObj.elements["', $eleName, '"].value == formObj.elements["', $eleName, '"].defaultValue)';
                    echo "\n\t\t\t", 'formObj.elements["', $eleName, '"].value = "";';
            }
        }   
    }

    /*
    This function validates all required fields.  If a captcha field is found, it is validated as well.  This function returns 
    true if the form successfully passes validation or false if errors were found.  If the form does return false, the errorMsg 
    variable will be populated with a human readable error message that can be displayed to the user upon redirect if desired.
    */
    // Modified: 2010/05/05 
    public function validate($custom_validation = NULL)
    {
        /*Determine if the form's submit method was get or post.*/
        if(!empty($_POST))
            $referenceValues = $_POST;
        elseif(!empty($_GET))
            $referenceValues = $_GET;
        else
        {
             //$this->errorMsg = 'The $_GET/$_POST array containing the form\'s submitted values does not exists.';
             $this->errorMessages['_ERROR_'] = 'The $_GET/$_POST array containing the form\'s submitted values does not exists.';
             return false;
        }

        $CI =& get_instance();
        $formclass_instances = $CI->session->userdata('formclass_instances');
        if(!empty($formclass_instances) && array_key_exists($this->attributes["name"], $formclass_instances))
        {
            /*Automatically unserialize the appropriate form instance stored in the session array.*/
            $form = unserialize(gzuncompress(base64_decode($formclass_instances[$this->attributes["name"]])));   // 2010/05/14

            /*If session autofill is enabled, store the submitted values in the session.*/
            if(!empty($form->enableSessionAutoFill))
            {
                $formclass_values = $CI->session->userdata('formclass_values');
                $formclass_values[$this->attributes["name"]] = $referenceValues;
                /*Unset reCAPTCHA field if applicable.*/
                if(array_key_exists("recaptcha_challenge_field", $formclass_values[$this->attributes["name"]]))
                    unset($formclass_values[$this->attributes["name"]]["recaptcha_challenge_field"]);
                if(array_key_exists("recaptcha_response_field", $formclass_values[$this->attributes["name"]]))
                    unset($formclass_values[$this->attributes["name"]]["recaptcha_response_field"]);
                $CI->session->set_userdata('formclass_values', $formclass_values);
            }   

            //if(!$this->phpCycleElements($form->elements, $referenceValues, $form))
            //  return false;
            $this->phpCycleElements($form->elements, $referenceValues, $form);
            if(!empty($form->bindRules))
            {
                $bindRuleKeys = array_keys($form->bindRules);
                $bindRuleSize = sizeof($bindRuleKeys);
                for($b = 0; $b < $bindRuleSize; $b++)
                {
                    if(!empty($form->bindRules[$bindRuleKeys[$b]][0]->elements))
                    {
                        if(empty($form->bindRules[$bindRuleKeys[$b]][2]) || (eval("if(" . $form->bindRules[$bindRuleKeys[$b]][2] . ") return true; else return false;")))
                        {
                            // if(!$this->phpCycleElements($form->bindRules[$bindRuleKeys[$b]][0]->elements, $referenceValues, $form))
                                // return false;
                            $this->phpCycleElements($form->bindRules[$bindRuleKeys[$b]][0]->elements, $referenceValues, $form);
                         }
                     }
                }
            }


            if(!empty($this->errorMessages)) return FALSE;

            // See if a custom validation function was provided. Call it and see if there
            // were any errrors (need to deal with class methods and globally accessable
            // functions).
            //echo 'custom_validation = ' .$custom_validation;
            if(!empty($custom_validation) && is_array($custom_validation))  // Expects class/object-instance and method 
            {
                $this->errorMessages = call_user_func(array($custom_validation[0], 
                                                        $custom_validation[1]), $referenceValues);
                if(!empty($this->errorMessages) && $this->errorMessages !== FALSE) return FALSE;
            }
            else    // just a function
            {
                if(function_exists($custom_validation))
                {
                    $this->errorMessages = $custom_validation($referenceValues);
                    if(!empty($this->errorMessages)) return FALSE;
                }
            }

            // As there are no errors and session filling was enabled, clear the saved field
            // values because form submission has succeeded and we don't need them anymore.
            if(!empty($form->enableSessionAutoFill))
            {
                $formclass_values = $CI->session->userdata('formclass_values');
                unset($formclass_values[$this->attributes["name"]]);
                $CI->session->set_userdata('formclass_values', $formclass_values);
            }

            return true;
        }
        else
        {
            $this->errorMessages['_ERROR_'] = 'The session variable containing this form\'s serialized instance does not exists.';
            return false;
        }
    }

    // Modified: 2010/05/05
    private function phpCycleElements($elements, $referenceValues, $form)
    {
        $elementSize = sizeof($elements);
        for($i = 0; $i < $elementSize; ++$i)
        {
            $ele = $elements[$i];
            $name = $ele->attributes['name'];
            /*The html, sort, and element types are ignored.*/
            if($ele->attributes["type"] == "html" || $ele->attributes["type"] == "sort" || $ele->attributes["type"] == "hidden")
                continue;
            elseif($ele->attributes["type"] == "captcha")
            {
                require_once($form->includesRelativePath . "/recaptchalib.php");
                $recaptchaResp = recaptcha_check_answer($form->captchaPrivateKey, $_SERVER["REMOTE_ADDR"], $referenceValues["recaptcha_challenge_field"], $referenceValues["recaptcha_response_field"]);
                if(!$recaptchaResp->is_valid)
                {
                    if($recaptchaResp->error == "invalid-site-public-key")
                        $this->errorMessages[$name] = "The reCAPTCHA public key could not be verified.";
                    elseif($recaptchaResp->error == "invalid-site-private-key")
                        $this->errorMessages[$name] = "The reCAPTCHA private key could not be verified.";
                    elseif($recaptchaResp->error == "invalid-request-cookie")
                        $this->errorMessages[$name] = "The reCAPTCHA challenge parameter of the verify script was incorrect.";
                    elseif($recaptchaResp->error == "incorrect-captcha-sol")
                        $this->errorMessages[$name] = "The reCATPCHA solution entered was incorrect.";
                    elseif($recaptchaResp->error == "verify-params-incorrect")
                        $this->errorMessages[$name] = "The reCAPTCHA parameters passed to the verification script were incorrect, make sure you are passing all the required parameters.";
                    elseif($recaptchaResp->error == "invalid-referrer")
                        $this->errorMessages[$name] = "The reCAPTCHA API public/private keys are tied to a specific domain name for security reasons.";
                    else
                        $this->errorMessages[$name] = "An unknown reCAPTCHA error has occurred.";
                    // return false;
                }
            }
            elseif(!empty($ele->required))
            {
                if(($ele->attributes["type"] == "checkbox" || $ele->attributes["type"] == "radio" || $ele->attributes["type"] == "checksort" || $ele->attributes["type"] == "rating") && !isset($referenceValues[$ele->attributes["name"]]))
                {
                    $this->errorMessages[$name] = str_replace("[LABEL]", $ele->label, $form->errorMsgFormat);
                    // return false;
                }
                elseif($ele->attributes['type'] == 'file')
                {
                    if(!isset($_FILES[$name]))
                        $this->errorMessages[$name] = str_replace("[LABEL]", $ele->label, $form->errorMsgFormat);
                }
                elseif(empty($referenceValues[$ele->attributes["name"]]) && 
                        ($referenceValues[$ele->attributes['name']] != 0 ||
                        $referenceValues[$ele->attributes['name']] != "0"))
                {
                    $this->errorMessages[$name] = str_replace("[LABEL]", $ele->label, $form->errorMsgFormat);
                    // return false;
                }   
            }
            
            // Do a validation check if needed (added 2010/04/14). Only required for text input fields (2010/09/16)
            if(!empty($ele->attributes['validate']) && !empty($referenceValues[$ele->attributes["name"]]) &&
                $ele->attributes['type'] == 'text')
            {
                $regex = $ele->attributes['validate'];
                if(!preg_match("$regex", $referenceValues[$ele->attributes["name"]]))
                {
                    $this->errorMessages[$name] = str_replace("[LABEL]", $ele->label, $form->validateErrorMsgFormat);
                    return false;
                }
            }

            if($ele->attributes["type"] == "email" && !empty($referenceValues[$ele->attributes["name"]]))
            {
                require_once($form->includesRelativePath . "/php-email-address-validation/EmailAddressValidator.php");
                $emailObj = new EmailAddressValidator;
                if(!$emailObj->check_email_address($referenceValues[$ele->attributes["name"]]))
                {
                    $this->errorMessages[$name] = str_replace("[LABEL]", $ele->label, $form->emailErrorMsgFormat);
                    // return false;
                }   
            }
        }
        // return true;
    }   

    /*This function sets the referenceValues variables which can be used to pre-fill form fields.  This function needs to be called before the render function.*/
    public function setReferenceValues($ref)
    {
        $this->referenceValues = $ref;
    }

    /*This function can be used to bind nested form elements rendered through elementsToString to the parent form object.*/
    public function bind($ref, $jsIfCondition = "", $phpIfCondition = "")
    {
        $this->bindRules[$ref->attributes["name"]] = array($ref, $jsIfCondition, $phpIfCondition);
        if(!empty($ref->emailExists))
            $this->emailExists = 1;
    }

    // Function for returning any error(s) as a string.
    public function error_string()
    {
        if(empty($this->errorMessages)) return "";
        foreach($this->errorMessages as $field => $val)
        {
            if(is_array($val) && !empty($val))  // For multiple errors on a field.
            {
                echo "Field $field has these error(s):<br>";
                echo implode(', ', $val);
            }
            else    // For single field/form error.
            {
                echo $val .'<br>';
            }
        }
    }
    
    /* Utility functions */

    // This function creates a string of form key="value" key2="value2" etc... to use in HTML tags.
    // attributes: an array of attributes to add.
    // fieldtype: the field type so the function can check which attributes are allowed to be included.
    // Returns a string of allowed attribute key value pairs, or an empty if nothing was done or no 
    // attributes where allowed.
    private function addAttributes($attributes, $fieldType)
    {
        $str = "";
        if(!empty($attributes) && is_array($attributes))
        {
            $tmpAllowFieldArr = $this->allowedFields[$fieldType];
            foreach($attributes as $key => $value)
            {
                if(in_array($key, $tmpAllowFieldArr))
                    $str .= ' ' . $key . '="' . str_replace('"', '&quot;', $value) . '"';
            }       
        }
        return $str;
    }

    // Adds a label into the form html string. (2010/05/03)
    // str: reference to the string object containing the form html. This string
    //      will be added to.
    // ele: the element being added to the form
    // pos: the position of the label. Can be 'above', 'below', 'inline', 'none'
    // alignment: the alignment of the label. For above and below positions that
    //            can be 'left', 'center' or 'right'. For the inline position it
    //            can be 'top', 'center' or 'bottom'.
    // tooltipIDArr: reference to the array containing tooltip ids in use.
    // cell_widths: the array of cell width specified when the map option is used.
    //              Can be NULL.
    // cur_cell: reference to the cell counter to index the cell_widths array.
    // Returns nothing. The html is added to the str reference passed.
    private function _print_label(&$str, $ele, $pos, $alignment, &$tooltipIDArr, $cell_widths, &$cur_cell)
    {
        $html = "";     
        
        /*If this field is set as required, render an "*" inside a <span> tag.*/
        if(!empty($ele->required))
        {
            $html .= " <span";
            $html .= $this->addAttributes($this->requiredAttributes, "div");
            $html .= ">*</span> ";
        }
        /*jQuery Tooltip Functionality*/
        if(!empty($ele->tooltip))
        {
            if(empty($this->tooltipIcon))
                $this->tooltipIcon = $this->includesAbsoluteWebPath . "/jquery/qtip/tooltip-icon.gif";

            /*This section ensures that each tooltip has a unique identifier.*/
            $tooltipID = "tooltip_" . rand(0, 999);
            while(array_key_exists($tooltipID, $tooltipIDArr))
                $tooltipID = "tooltip_" . rand(0, 999);
            $tooltipIDArr[$tooltipID] = $ele->tooltip;  

            $html .= ' <img id="' . $tooltipID . '" src="' . $this->tooltipIcon . '"/>';
        }
    
        if($pos == 'above' || $pos == 'below')
        {
            /*Render the label inside a <div> tag.*/    
            $str .= "<div";
            $this->labelAttributes['align'] = $alignment;
            $str .= $this->addAttributes($this->labelAttributes, "div");
            $str .= ">";
            $str .= $ele->label;
            $str .= $html;
            $str .= "</div>\n";
        }
        elseif($pos == 'inline')
        {
            // In this case the label is in line with the field an to the left.
            // The label is rendered within a td tag. This means it is closed off
            // at the end, and another td element is started for the field element.
            $str .= $ele->label;
            $str .= $html;
            $str .= "</td>\n";  //  <-- closing off and starting a new cell.
            
            // If a custom defined width is available, then use it for the field cell.
            if(!empty($cell_widths) && $cur_cell < count($cell_widths))
            {
                $tmpAttr = $this->tdAttributes;
                $tmpAttr['width'] = $cell_widths[$cur_cell];
                $cur_cell++;
                $str .= "<td";
                $str .= $this->addAttributes($tmpAttr, "td");
                $str .= ">\n";
            }
            else
            {
                $str .= "<td";
                $str .= $this->addAttributes($this->tdAttributes, "td");
                $str .= ">\n";
            }
        }
        elseif($pos == 'nolabel')
        {
            // Do nothing
        }
    }
    
    // Returns the array of all elements which exist for a form that has been rendered already.
    // form_name: the name of the form to get the elements for.
    // Returns an array of elements that belong to the form. If the form was not found then
    // NULL is returned.
    public function get_all_elements_for_rendered_form($form_name)
    {
        $CI =& get_instance();
        $formclass_instances = $CI->session->userdata('formclass_instances');
        if(!empty($formclass_instances) && array_key_exists($form_name, $formclass_instances))
        {
            $form = unserialize(gzuncompress(base64_decode($formclass_instances[$form_name])));
            return $form->elements;
        }
        else
        {
            return NULL;
        }
    }
}

class element extends base {
    /*Public variables to be read/written in both the base and form classes. These variables can be assigned in the last parameter
    of each function for adding form fields.*/
    public $attributes;                 /*HTML attibutes that are applied to form input type.*/
    public $label;                      /*Text/HTML that is placed in <div> about form input type.*/
    public $options;                    /*Contains multiple options such as select, radio, checkbox, etc.  Can exist as associative or one-dimensional array.*/
    public $required;                   /*Will trigger javascript error checking.*/
    public $disabled;                   /*Adds "disabled" keyword to input element.*/
    public $multiple;                   /*Adds "multiple" keyword to input element.*/
    public $readonly;                   /*Adds "readonly" keyword to input element.*/
    public $nobreak;                    /*Applicable for radio, yesno, truefalse, and checkbox elements.  If this parameter is set, there will not be <br> tags separating each option.*/
    public $preHTML;                    /*HTML content that is rendered before <div> containing the element's label.*/
    public $postHTML;                   /*HTML content that is rendered just before the closing </td> of the element.*/
    public $tooltip;                    /*If provided, this content (text or HTML) will generate a tooltip activated onkeyup.*/
    public $hint;                       /*If provided, this content will be displayed as the field's value until focus event.*/

    /*webeditor specific fields*/
    public $webeditorSimple;            /*Overrides default webeditor settings and renders a simplified version.*/

    /*ckeditor specific fields*/
    public $ckeditorBasic;              /*Overrides default ckeditor settings and renders a simplified toolbar.*/

    /*latlng specific fields*/
    public $latlngHeight;               /*Controls height of Google Map.*/
    public $latlngWidth;                /*Controls width of Google Map.*/
    public $latlngZoom;                 /*Controls zoom level when Google Map is initially loaded.*/
    public $latlngHideJump;             /*Will hide the textbox for location jump functionality.*/

    /*slider specific fields*/
    public $sliderMin;                  /*Controls lowest value of slider.*/ 
    public $sliderMax;                  /*Controls highest value of slider.*/
    public $sliderSnapIncrement;        /*Controls incremental step of slider.*/
    public $sliderOrientation;          /*Defaults to horizontal but can be set to vertical.*/
    public $sliderPrefix;               /*Will prepend dynamic slider label with specified string.*/
    public $sliderSuffix;               /*Will append end of dynamic slider label with specified string.*/
    public $sliderHeight;               /*If the sliderOrientation is set to vertical, this parameter controls the slider height.*/
    public $sliderHideDisplay;          /*Hides dynamic slider label.*/

    /*rating specific fields*/
    public $ratingHideCaption;          /*Hides dynamic rating label.*/
    public $ratingHideCancel;           /*Hides rating cancel image.*/

    public function __construct() {
        /*Set default values where appropriate.*/
        $this->attributes = array(
            "type" => "text"
        );
    }
}
class option extends base {
    /*Public variables to be read/written in both the base and form classes.*/
    public $value;                      /*Contains input value.*/
    public $text;                       /*Contains displayed text.*/
}
class button extends base {
    /*Public variables to be read/written in both the base and form classes.*/
    public $attributes;                 /*HTML attibutes that are applied to button input type.*/
    public $phpFunction;                /*Specified php function for generating button images.*/
    public $phpParams;                  /*Array containing paramters passed to phpFunction.*/
    public $wrapLink;                   /*Wraps anchor tag around button.*/
    public $linkAttributes;             /*HTML attibutes that are applied to the anchor tag is the wrapLink parameter is specified.*/

    /*Set default values where appropriate.*/
    public function __construct() {
        $this->linkAttributes = array(
            "style" => "text-decoration: none;"
        );
    }
}
?>