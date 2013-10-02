<?php echo doctype('html4-strict'); ?>
<html>

<head>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Controller Access Overview</title>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>cbeads/css/cbeads_style.css" />
	<script src="<?php echo base_url(); ?>libs/jquery-1.6.1.min.js" type="text/javascript"></script>

	<style>
	
		.section {
			margin-bottom: 10px;
		}
		
		.weekend {
			color: green;
		}
		.center-text {
			text-align: center;
		}
		.column {
			background-color: #DDFFDD;
			border-top: 1px solid #007700;
			border-left: 1px solid #007700;
			border-right: 1px solid #007700;
			lborder-bottom: 1px solid #000000;
		}
		.graph-line {
			border-top: 1px solid #dddddd;
		}
		
		.vText {
		   -moz-transform: rotate(-90deg) translate(0, 100%);
		   -moz-transform-origin: 0% 100%;
		   -o-transform: rotate(-90deg) translate(0, 100%);
		   -o-transform-origin: 0% 100%;
		   -webkit-transform: rotate(-90deg) translate(0, 100%);
		   -webkit-transform-origin: 0% 100%;
		   transform: rotate(-90deg) translate(0, 100%);
		   transform-origin: 0% 100%;
		   writing-mode: bt-rl;
		   filter: flipV flipH;
		}
	
		#ctrl_usage_tbl td {
			padding: 0px;
		}
		#ctrl_usage_tbl th{
			text-align: left;
		}
		#ctrl_usage_tbl {
			border-collapse: collapse;
		}
		#ctrl_usage_tbl tr.data_row:hover {
			background-color: #ccddee;
		}
		.app_row {
			cursor: pointer;
		}
		.ctrl_row {
		}
		.func_row {
			color: #444444;
			font-style: italic;
		}
		
		#ctrl_unused_tbl td {
			padding: 0px;
		}
		#ctrl_unused_tbl th {
			text-align: left;
		}
		#ctrl_unused_tbl {
			border-collapse: collapse;
		}
		#ctrl_unused_tbl tr.data_row:hover {
			background-color: #ccddee;
		}
	
	</style>
	
</head>

<body>

<h2>Overview</h2>
<p>Select the time period for which to view usage information.</p>
<div class='section'>
	Time Period: Year <select id='year_selector'></select> Month <select id='month_selector'></select> Day <select id='day_selector'></select>
</div>
<div class='section'>
	View Mode:
	<select id='view_mode'>
		<option value='used'>Controllers used</option>
		<option value='not_used'>Controllers not used</option>
		<option value='usage_by_user'>Controller usage by user</option>
	</select>
</div>
<div class='section'>

	<div class='section' id='options_area_by_user'>
		
	</div>

	<div class='section' id='display_area'>
	
	</div>

</div>

</body>


<script type="text/javascript">

// General date functions.

myDate = function() {
    this._dateObj = new Date();
    this._months = new Array('January','February','March','April','May','June','July','August','September','October','November','December');
    this._daysLong = new Array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
    this._daysShort = new Array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
}

// Returns the current date in d/m/yyyy format
myDate.prototype.getCurrentDateString = function() {
    return this._dateObj.getDate() + "/" + (this._dateObj.getMonth() + 1) + "/" + this._dateObj.getFullYear();
}

myDate.prototype.getCurrentYear = function() {
    return this._dateObj.getFullYear();
}

// Returns the month as a number where 0=January and 11=December
myDate.prototype.getCurrentMonth = function() {
    return this._dateObj.getMonth();
}

// Return the month name for a given month number.
myDate.prototype.getMonthName = function(monthNumber) {
    return this._months[monthNumber];
}

// Returns the current day of the month (1 to 31).
myDate.prototype.getCurrentDayOfMonth = function() {
    return this._dateObj.getDate();
}

// Returns the week day as a number where 0=Monday and 6=Sunday
myDate.prototype.getCurrentWeekDay = function() {
    var num = this.adjustDayNumber(this._dateObj.getDay());
    return num;
}

// Return the day name for a given week day number.
myDate.prototype.getDayName = function(dayNumber) {
    return this._daysLong[dayNumber];
}

// Return the short day name for a given week day number.
myDate.prototype.getShortDayName = function(dayNumber) {
    return this._daysShort[dayNumber];
}

// Returns the current year.
myDate.prototype.getYear = function() {
    return this._dateObj.getFullYear();
}

// Returns if the year provided is a leap year or not.
myDate.prototype.isLeapYear = function(year) {
    if (year % 4 == 0 && year % 100 != 0 || year % 400 == 0 )
       return true;
    return false;
}

// Returns the number of days for a given month and year.
myDate.prototype.getDaysInMonth = function(month, year) {
    monthDays = new Array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    if(month < 0) 
    {
        year = year - Math.ceil((Math.abs(month) / 12));
        month = 12 - (Math.abs(month) % 12);
    }
    else if(month > 11)
    {
        year = year + Math.floor(month / 12);
        month = 12 - (month % 12);
    }
    if(month == 1 && this.isLeapYear(year)) return 29;  // February on leap years.
    return monthDays[month];
}

// Returns the weekday that the month for a given year starts on.
// month: the month to get the start weekday for (0 to 11)
// year: the year that the month is located in.
myDate.prototype.getMonthWeekdayStart = function(month, year) {
    var date = new Date(year, month, 1);
    return adjustDayNumber(date.getDay());
}

// Returns the weekday that the month for the given year ends on.
myDate.prototype.getMonthWeekdayEnd = function(month, year) {
    var date = new Date(year, month, this.getDaysInMonth(month, year));
    return adjustDayNumber(date.getDay());
}

myDate.prototype.getMonthStartWeek = function(month, year) {

}

myDate.prototype.getMonthEndWeek = function(month, year) {

}

// Sets the date for this myDate object.
// date: the date object to use as the current date.
myDate.prototype.setDate = function(date) {
    this._dateObj = date;
}

// Adjusts the day number so that Monday is day 0 and Sunday is day 6.
function adjustDayNumber(day) {
    var adjust = new Array(6,0,1,2,3,4,5);
    return adjust[day];
}

</script>


<script type="text/javascript">

var _date = new myDate();

var _cur_year = -1;
var _cur_month = -1;
var _cur_day = -1;

var available_dates = <?php echo $available_dates; ?>;
var _app_data = <?php echo $apps; ?>;
var _users_data = <?php echo $users; ?>;
var _total_users = 0;

var _view_mode = undefined;

var _data_cache = {};		// Cache of data retrieved from the server.
var _data_cache_jqxhr_data = [];	// To associate values with a given post request.

$(document).ready(function(){

	setup();

});


function setup()
{
	for(var i in _users_data)
	{
		_total_users++;
	}

	// Populate the date listboxes
	_cur_year = _date.getCurrentYear();
	_cur_month = _date.getCurrentMonth() + 1;
	_cur_day = _date.getCurrentDayOfMonth();
	populate_years_available();
	populate_users_available();
	
	$('#view_mode').change(changed_view_mode);
	
	$('#year_selector').change(changed_year);
	$('#month_selector').change(changed_month);
	$('#day_selector').change(changed_day);
	
	$('#view_mode').change();	// Get data for the current date.
}

function populate_years_available()
{
	$('#year_selector').append('<option value="-1">All</option>');
	for(var year in available_dates)
	{
		$('#year_selector').append('<option value="' + year + '">' + year + '</option>');
	}
	$('#year_selector').val(_cur_year);
	populate_months_available();
}

function populate_months_available()
{
	if(_cur_year == -1)
	{
		$('#month_selector').attr('disabled', 'disabled');
		$('#day_selector').attr('disabled', 'disabled');
		return;
	}
	$('#month_selector').removeAttr('disabled');
	$('#month_selector').empty();
	var months = [];
	for(var i in available_dates[_cur_year])
	{
		months.push(i);
	}
	//months = months.sort(_sort_numeric_months);
	$('#month_selector').append('<option value="-1">All</option>');
	for(var i = 0; i < months.length; i++)
	{
		$('#month_selector').append('<option value="' + months[i] + '">' + _date.getMonthName(months[i]-1) + '</option>');
	}
	$('#month_selector').val(_cur_month);
	populate_days_available();
}

function populate_days_available()
{
	if(_cur_month == -1)
	{
		$('#day_selector').attr('disabled', 'disabled');
		return;
	}
	$('#day_selector').removeAttr('disabled');
	$('#day_selector').empty();
	var days = [];
	for(var i = 0; i < available_dates[_cur_year][_cur_month].length; i++)
	{
		days.push(available_dates[_cur_year][_cur_month][i]);
	}
	//days = days.sort(_sort_numeric_months);
	$('#day_selector').append('<option value="-1">All</option>');
	for(var i = 0; i < days.length; i++)
	{
		$('#day_selector').append('<option value="' + days[i] + '">' + days[i] + '</option>');
	}
	$('#day_selector').val(_cur_day);
}

function populate_users_available()
{
	var html = '<select id="user_selector" >';
	for(var u in _users_data)
	{
		html += '<option value="' + u + '">' + _users_data[u].uname + '</option>';
	}
	html += '</select>';
	$('#options_area_by_user').hide();
	$('#options_area_by_user').html(html);
	$('#user_selector').change(changed_user);
}

function changed_year()
{
	_cur_year = this.value;
	if(_cur_year != -1)
	{
		if(available_dates[_cur_year][_cur_month] === undefined)
		{
			_cur_month = -1;
			_cur_day = -1;
		}
	}
	populate_months_available(_cur_year);
	$('#view_mode').change();
}
function changed_month()
{
	_cur_month = this.value;
	if(_cur_month != -1)
	{
		var found = false;
		for(var i = 0; i < available_dates[_cur_year][_cur_month]; i++)
		{
			if(available_dates[_cur_year][_cur_month][_cur_day] == _cur_day)
				found = true;
		}
		if(!found) _cur_day = -1;
	}
	populate_days_available(_cur_month);
	$('#view_mode').change();
}
function changed_day()
{
	_cur_day = this.value;
	$('#view_mode').change();
}

function changed_user()
{
	$('#view_mode').change();
}

function changed_view_mode()
{
	_view_mode = this.value;
	$('#options_area_by_user').hide();
	if(_view_mode == 'used')
	{
		view_controllers_used();
	}
	else if(_view_mode == 'not_used')
	{
		view_controllers_not_used();
	}
	else if(_view_mode == 'usage_by_user')
	{
		$('#options_area_by_user').show();
		view_usage_by_user();
	}
}


// Based on the time period selected, constructs a key and checks if usage data exists.
// If not, it initiates a data request to the server. If the data exists, the key to use is 
// returned. Otherwise returns undefined.
// success_callback: a function that will be called when data was successfully fetched from the server.
function get_key_for_usage_data(success_callback)
{
	var year = Number($('#year_selector').val());
	var month = Number($('#month_selector').val());
	var day = Number($('#day_selector').val());

	if(year == -1)	// All years
	{
		key = '-1';
		if(!does_cache_data_exist(key))
		{
			fetch_value_for_cache({
				url: '<?php echo site_url(); ?>/cbeads/controller_access_and_profiles/get_allyears_usage_data', 
				post_data: {}, data_key: cache_allyears_usage_data, custom_cache_parameters: {key: key}, success_callback: got_allyears_usage_data, success_parameters: {func: success_callback},
				failure_callback: cannot_get_allyears_usage_data
			});
			return;
		}
	}
	else
	{
		if(month == -1)	// Usage for a year.
		{
			key = year;
			if(!does_cache_data_exist(key))
			{
				fetch_value_for_cache({
					url: '<?php echo site_url(); ?>/cbeads/controller_access_and_profiles/get_year_usage_data', 
					post_data: {year: year}, data_key: cache_year_usage_data, custom_cache_parameters: {key: key}, success_callback: got_year_usage_data, success_parameters: {func: success_callback},
					failure_callback: cannot_get_year_usage_data
				});
				return;
			}
		}
		else
		{
			if(day == -1)	// Usage for a month.
			{
				key = year + '' + ((month < 10) ? '0' + month : month);
				if(!does_cache_data_exist(key))
				{
					fetch_value_for_cache({
						url: '<?php echo site_url(); ?>/cbeads/controller_access_and_profiles/get_month_usage_data', 
						post_data: {year: year, month: month}, data_key: cache_month_usage_data, custom_cache_parameters: {key: key}, success_callback: got_month_usage_data, success_parameters: {func: success_callback},
						failure_callback: cannot_get_month_usage_data
					});
					return;
				}
			}
			else			// Usage for a day.
			{
				key = year + '' + ((month < 10) ? '0' + month : month) + '' + ((day < 10) ? '0' + day : day);
				if(!does_cache_data_exist(key))
				{
					fetch_value_for_cache({
						url: '<?php echo site_url(); ?>/cbeads/controller_access_and_profiles/get_day_usage_data', 
						post_data: {year: year, month: month, day: day}, data_key: cache_day_usage_data, custom_cache_parameters: {key: key}, success_callback: got_month_usage_data, success_parameters: {func: success_callback},
						failure_callback: cannot_get_month_usage_data
					});
					return;
				}
			}
		
		}
	}
	return key;
}


// Want to see what apps, controllers and functions where used.
function view_controllers_used()
{
	var key = get_key_for_usage_data(view_controllers_used);
	if(key === undefined) return;		// Must wait for the data to be fetched.

	var html = '';
	
	html += '<br /><br /><table class="ctrl_usage_tbl" id="ctrl_usage_tbl">';
	html += '<tr><th>Application</th><th>Unique Users</th><th>Hits</th></tr>';
	var data = _data_cache[key].overall;
	var ctrls, funcs, app, ctrl, html_a, html_u, html_h;
	
	for(var app_id in data)
	{
		app = data[app_id];
		html_a = _app_data[app_id].name;
		html_u = app.uniq + ' (' + app.perc + '%)';
		html_h = app.hits;
		html += '<tr class="data_row app_row" id="app_' + app_id + '" ><td class="app_col">' + html_a + '</td><td class="usage_col">' + html_u + '</td><td class="hit_col">' + html_h + '</td></tr>';
		for(var ctrl_id in app.ctrls)
		{
			ctrl = app.ctrls[ctrl_id];
			html_a = _app_data[app_id]['ctrls'][ctrl_id].name;
			html_u = ctrl.uniq + ' (' + ctrl.perc + '%)';
			html_h = ctrl.hits;
			html += '<tr class="data_row ctrl_row" id="app_' + app_id + '_' + ctrl_id + '"  style="display: none;"><td class="app_col">' + html_a + '</td><td class="usage_col">' + html_u + '</td><td class="hit_col">' + html_h + '</td></tr>';
			for(var func_name in ctrl.funcs)
			{
				html_a = func_name;
				html_u = '';
				html_h = ctrl.funcs[func_name];
				html += '<tr class="data_row func_row" id="app_' + app_id + '_' + ctrl_id + '_' + func_name + '"  style="display: none;"><td class="app_col">' + html_a + '</td><td class="usage_col">' + html_u + '</td><td class="hit_col">' + html_h + '</td></tr>';
			}
		}
	}
	
	html += '</table>';
	
	$('#display_area').html(html);
	$('.ctrl_row .app_col').css('padding-left', '2em').css('padding-right', '1em');
	$('.func_row .app_col').css('padding-left', '4em').css('padding-right', '1em');
	$('#ctrl_usage_tbl th').css('padding', '2px 10px 2px 0px');
	
	$('.app_row').toggle(
		function(){
			$(this).nextUntil('.app_row').show();
		},
		function(){
			$(this).nextUntil('.app_row').hide();
		}
	);
	
}

// Want to see the controllers that have not been used for the current time period.
function view_controllers_not_used()
{
	var key = get_key_for_usage_data(view_controllers_not_used);
	if(key === undefined) return;		// Must wait for the data to be fetched.
	
	var html = '';

	html += '<br /><br /><table class="ctrl_unused_tbl" id="ctrl_usage_tbl">';
	html += '<tr><th>Application</th><th>Unused Controllers</th></tr>';
	var data = _data_cache[key].overall;
	var ctrls, app, ctrl, html_a, html_u, unused = [], used_everything = true;
	
	
	for(var app_id in _app_data)
	{
		html_a = _app_data[app_id].name;
		if(data[app_id] === undefined)		// No controller in this app was used!
		{
			html += '<tr class="data_row" id="app_' + app_id + '"><td class="app_col">' + html_a + '</td><td class="unused_col">No controller has been used!</td></tr>';
		}
		else
		{
			app = data[app_id];
			for(var ctrl_id in _app_data[app_id].ctrls)
			{
				if(app.ctrls[ctrl_id] === undefined)
				{
					unused.push(_app_data[app_id].ctrls[ctrl_id].name);
				}
			}
			if(unused.length > 0)
			{
				html_u = unused.join(', ');
				html += '<tr class="data_row" id="app_' + app_id + '"><td class="app_col">' + html_a + '</td><td class="unused_col">' + html_u + '</td></tr>';
			}
		}
	}
	
	html += '</table>';
	
	$('#display_area').html(html);
	$('#ctrl_usage_tbl th').css('padding', '2px 10px 2px 0px');
	$('.app_col').css('padding-right', '1em');

}



function view_usage_by_user()
{
	var key = get_key_for_usage_data(view_usage_by_user);
	if(key === undefined) return;		// Must wait for the data to be fetched.
	var data = _data_cache[key];

	var user_id = $('#user_selector').val();
	var hits = 0;
	var access_data = data.data[user_id];	// Get the individual user data
	var app, ctrl, func;
	overall = {};
	for(var acf in access_data)
	{
		hits = parseInt(access_data[acf], 10);
		
		acf = acf.split(" ");
		app = acf[0];
		ctrl = acf[1];
		func = acf[2];
		
		if(overall[app] === undefined) overall[app] = {ctrls: {}, hits: 0};
		overall[app].hits += hits;
		if(overall[app].ctrls[ctrl] === undefined) overall[app].ctrls[ctrl] = {funcs: {}, hits: 0};
		var tmp = overall[app]['ctrls'][ctrl];
		tmp['hits'] += hits;
		tmp['funcs'][func] = hits;
		overall[app]['ctrls'][ctrl] = tmp;
	}
	
	var html = '';
	
	html += '<br /><br /><table class="ctrl_usage_tbl" id="ctrl_usage_tbl">';
	html += '<tr><th>Application</th><th>Hits</th></tr>';
	data = overall;
	var ctrls, funcs, app, ctrl, html_a, html_u, html_h;
	
	for(var app_id in data)
	{
		app = data[app_id];
		html_a = _app_data[app_id].name;
		html_h = app.hits;
		html += '<tr class="data_row app_row" id="app_' + app_id + '" ><td class="app_col">' + html_a + '</td><td class="hit_col">' + html_h + '</td></tr>';
		for(var ctrl_id in app.ctrls)
		{
			ctrl = app.ctrls[ctrl_id];
			html_a = _app_data[app_id]['ctrls'][ctrl_id].name;
			html_h = ctrl.hits;
			html += '<tr class="data_row ctrl_row" id="app_' + app_id + '_' + ctrl_id + '"  style="display: none;"><td class="app_col">' + html_a + '</td><td class="hit_col">' + html_h + '</td></tr>';
			for(var func_name in ctrl.funcs)
			{
				html_a = func_name;
				html_h = ctrl.funcs[func_name];
				html += '<tr class="data_row func_row" id="app_' + app_id + '_' + ctrl_id + '_' + func_name + '"  style="display: none;"><td class="app_col">' + html_a + '</td><td class="hit_col">' + html_h + '</td></tr>';
			}
		}
	}
	
	html += '</table>';
	
	$('#display_area').html(html);
	$('.ctrl_row .app_col').css('padding-left', '2em').css('padding-right', '1em');
	$('.func_row .app_col').css('padding-left', '4em').css('padding-right', '1em');
	$('#ctrl_usage_tbl th').css('padding', '2px 10px 2px 0px');
	
	$('.app_row').toggle(
		function(){
			$(this).nextUntil('.app_row').show();
		},
		function(){
			$(this).nextUntil('.app_row').hide();
		}
	);
	
}





/*
data: an object containing the following information on populating the graph:
	- values (an array of values, one for each column)
	- x_labels (an array of column labels, same length as the number of elements in 'values')
	- x_axis_label (what to call the x-axis)
	- y_axis_label (what to call the y-axis)
styling: an object containing the following information on styling the graph:
	- col_spacing (pixels between columns)
	- x_labels (an array of classes to apply to x-axis labels, same length as the number of elements in 'x_labels') eg: ["week", "weekend", "holiday"]
*/
function draw_graph(data, styling)
{
	//console.log(data, styling);

	var max_value = 0;		// Heighest column value.
	var min_value = 0;		// Lowest column value.
	var y_range_max = 0;	// Highest value on the y-axis.
	var y_range_min = 0;	// Lowest value on the y-axis.
	
	var values = data.values;
	y_range_max = max_value = _get_highest_value(values);
	if(max_value == 0) y_range_max = 1;
	
	var ga = $(".graphing_area");	// element to use for the graphing area.
	var ga_height = ga.height();		// height of the graphing area.
	var ga_width = ga.width();			// width of the graphing area.
	var ga_pos = ga.offset();			// top-left position of the graphing area.
	var x_axis_top = ga_pos.top + ga_height;	// top edge of the x-axis labeling area.
	var y_axis_right = ga_pos.left;		// right edge of the y-axis labeling area.
	
	var col_spacing = styling.col_spacing || 5;
	var col_width = (ga_width - (values.length + 1) * 5) / values.length;
	var col_label_width = (ga_width - 5) / values.length;
	//console.log(col_label_width);
	var col_max_height = ga_height - 30;		// Leave some space at the top.
	
	// Clear the calendar by emptying any existing dom elements.
	var hidden_elements = $('#hidden_elements').empty();
	
	// Add y axis labels and lines.
	hidden_elements.append('<span id="y-label-' + y_range_max + '">' + y_range_max + '</span>');
	var label = $('#y-label-' + y_range_max);
	$('#y-label-' + y_range_max).css({position: 'absolute', top: ga_pos.top + ga_height - col_max_height - label.height() / 2, left: y_axis_right - label.width() - 5});
	hidden_elements.append('<div id="y-line-' + y_range_max + '" class="graph-line"></div>');
	$('#y-line-' + y_range_max).css({position: 'absolute', top: ga_pos.top + ga_height - col_max_height - 1, left: y_axis_right + 1, width: ga_width});
	hidden_elements.append('<span id="y-axis-label" class="vText" style="background-color: #DBFFB7;">'+ (data.y_axis_label || '') +'</span>');
	$('#y-axis-label').css({position: 'absolute', top: ga_pos.top + ga_height / 2 + $('#y-axis-label').width() / 2, left: y_axis_right - 40});
	
	// Add the columns and x axis labels.
	var label_styling = styling.x_labels || {};
	for(var i = 0; i < values.length; i++)
	{
		var label = data.x_labels[i];
		hidden_elements.append('<div id="x-label-' + label + '" class="center-text" style="width: ' + col_label_width + 'px; font-size: 0.8em;">' + label + '</div>');
		$("#x-label-" + label).css({position: 'absolute', top: x_axis_top, left: y_axis_right + col_spacing / 2 + col_label_width * i});

		if(label_styling[i] !== undefined)
			$('#x-label-' + label).addClass(label_styling[i]);
		
		col_height = 0;
		//console.log(col_max_height, values[i], max_value);
		if(values[i] > 0)
		{
			col_height = col_max_height * (values[i] / max_value);
			hidden_elements.append('<div id="col-' + i + '" class="column" style="width: ' + col_width + 'px; height: ' + col_height + 'px;"></div>');
			$("#col-" + i).css({position: 'absolute', top: x_axis_top - col_height - 1, left: y_axis_right + col_spacing * (i+1) + col_width * i});
		}
	}
	hidden_elements.append('<span id="x-axis-label">'+ (data.x_axis_label || '') +'</span>');
	$('#x-axis-label').css({position: 'absolute', top: x_axis_top + 20, left: y_axis_right + ga_width / 2 - $('#x-axis-label').width() / 2});
}



function cache_allyears_usage_data(data, params)
{
	return _cache_usage_data(data, params, 'Unable to parse server data for "all years" time period.');
	// try
	// {
		// data = $.parseJSON(data);
	// }
	// catch(e)
	// {
		// alert('Unable to parse server data for the "all years" period.');
		// return false;
	// }

	
	// overall = calculate_overall_usage(data);
	// data = {'data': data, 'overall': overall};
	
	// console.log(data);
	// _data_cache[params.key] = data;
	// return true;
}
function got_allyears_usage_data(params)
{
	params.func();
}
function cannot_get_allyears_usage_data(params)
{
	alert('Failed to get usage data for all years');
}


function cache_year_usage_data(data, params)
{
	return _cache_usage_data(data, params, 'Unable to parse server data for "year" time period.');
}
function got_year_usage_data(params)
{
	params.func();
}
function cannot_get_year_usage_data(params)
{
	alert('Failed to get usage data for year: ' + params.key);
}


function cache_month_usage_data(data, params)
{
	return _cache_usage_data(data, params, 'Unable to parse server data for "month" time period.');
}
function got_month_usage_data(params)
{
	params.func();
}
function cannot_get_month_usage_data(params)
{
	alert('Failed to get usage data for month: ' + params.key);
}


function cache_day_usage_data(data, params)
{
	return _cache_usage_data(data, params, 'Unable to parse server data for "day" time period.');
}
function got_day_usage_data(params)
{
	params.func();
}
function cannot_get_day_usage_data(params)
{
	alert('Failed to get usage data for day: ' + params.key);
}



function _cache_usage_data(data, params, failmsg)
{
	try
	{
		data = $.parseJSON(data);
	}
	catch(e)
	{
		alert(failmsg);
		return false;
	}

	overall = calculate_overall_usage(data);
	data = {'data': data, 'overall': overall};
	
	//console.log(data);
	_data_cache[params.key] = data;
	return true;
}




// Need to calculate the percentage of users that have used an application/controller/function,
// plus total number of hits.
function calculate_overall_usage(data)
{
	/*
	overall_usage: {
		app_id: {
			uniq: unique user counter
			perc: % of users
			hits: #
			controllers: {
				ctrl_id: {
					perc: % of users
					hits: #
					functions: {
						func_name: #hits
					}
				}
			}
		}
	}
	*/

	var mapped = {};		// Maps 'app_id ctrl_id func_name' strings to an array where each component is an element.
	var overall = {};
	var apps_recorded;		// When calculating what percent of users have used an application/controller/function
	var ctrls_recorded; 	// need to record for which ones the user has already been recorded.
	
	var hits = 0;
	var access_data;
	var app, ctrl, func;
	
	for(var user_id in data)
	{
		access_data = data[user_id];
		apps_recorded = {};
		ctrls_recorded = {};
		for(var acf in access_data)
		{
			hits = parseInt(access_data[acf], 10);
			if(mapped[acf] === undefined)
			{
				mapped[acf] = acf.split(" ");
				app = mapped[acf][0];
				ctrl = mapped[acf][1];
				func = mapped[acf][2];
				if(overall[app] === undefined) overall[app] = {perc: 0, uniq: 0, hits: 0, ctrls: {}};
				if(overall[app]['ctrls'][ctrl] === undefined) overall[app]['ctrls'][ctrl] = {perc: 0, uniq: 0, hits: 0, funcs: {}};
				if(overall[app]['ctrls'][ctrl]['funcs'][func] === undefined) overall[app]['ctrls'][ctrl]['funcs'][func] = 0;
			}
			else
			{
				app = mapped[acf][0];
				ctrl = mapped[acf][1];
				func = mapped[acf][2];
			}
			overall[app].hits += hits;
			if(apps_recorded[app] === undefined)
			{
				apps_recorded[app] = true;
				overall[app].uniq++;
				var percent = overall[app].uniq / _total_users * 100;
				overall[app].perc = percent.toFixed(1);
			}
			
			var tmp = overall[app]['ctrls'][ctrl];
			tmp['hits'] += hits;
			if(ctrls_recorded[ctrl] === undefined)
			{
				ctrls_recorded[ctrl] = true;
				tmp.uniq++;
				var percent = tmp.uniq / _total_users * 100;
				tmp.perc = percent.toFixed(1);
			}
			tmp['funcs'][func] += hits;
			overall[app]['ctrls'][ctrl] = tmp;
			// Doing it this way doesn't work for some reason :(
			// ovarall[app]['ctrls'][ctrl]['hits'] += hits;
			// overall[app]['ctrls'][ctrl]['funcs'][func] += hits;
		}
	}
	
	return overall;
}


// Checks if the cache has data for a given key.
function does_cache_data_exist(key)
{
	return (_data_cache[key] !== undefined) ? true : false;
}

// Fetches a value from the server to store in the cache. The data parameter must contain:
// post_data: any data to send to the server via POST.
// url: the url to send the request to.
// optionally it can contain other information such as:
// data_key: a key value (string) to store the fetched data under. Alternatively can be a callback
//           function which is called to store the server data manually in the cache.
// custom_cache_parameters: some data to pass to the manual data caching callback.
// success_callback: a callback function which is called when the data fetch has succeeded.
// success_parameters: some data to pass to the success callback. (optional)
// failure_callback: a callback function which is called when the data fetch has failed.
// failure_parameters: some data to pass to the failure callback. (optional)
//
function fetch_value_for_cache(data)
{
	if(data.post_data === undefined) 
		post_data = {};
	else
		post_data = data.post_data;
	//jqxhr = $.post(data.url, post_data, data.success_callback).error(data_fetch_failed);
	// jqxhr = $.post('lala').error(data_fetch_failed);
	// _data_cache_jqxhr_data.push({jqxhr: jqxhr, params: 'hello world'});
	// jqxhr = $.post('lala');
	// _data_cache_jqxhr_data.push({jqxhr: jqxhr, params: 'nooo'});
	var jqxhr = $.post(data.url, post_data, data_fetch_success).error(data_fetch_failed);
	//_data_cache_jqxhr_data.push({jqxhr: jqxhr, success_callback: data.success_callback, success_parameters: data.success_parameters, failure_callback: data.failure_callback, failure_parameters: data.failure_parameters, data_key: data.data_key});
	data['jqxhr'] = jqxhr;
	_data_cache_jqxhr_data.push(data);
}

// Called when the data fetch for the cache succeeded.
function data_fetch_success(data_received, text_status, jqxhr)
{
	//alert(data_received);
	for(var i = 0; i < _data_cache_jqxhr_data.length; i++)
	{
		data = _data_cache_jqxhr_data[i];
		if(data.jqxhr === jqxhr) 
		{
			// The data can be stored automatically using a key value or a callback function can process the data and store it as desired.
			if($.isFunction(data.data_key))
			{
				if(data.data_key(data_received, data.custom_cache_parameters) == false) return;
			}
			else
				_data_cache[data.data_key] = $.parseJSON(data_received);	// Store the received data.
			_data_cache_jqxhr_data.splice(i, 1);
			if($.isFunction(data.success_callback))
				data.success_callback(data.success_parameters !== undefined ? data.success_parameters : {});

			break;
		}
	}
}

function data_fetch_failed(jqxhr, text_status)
{
	for(var i = 0; i < _data_cache_jqxhr_data.length; i++)
	{
		data = _data_cache_jqxhr_data[i];
		if(data.jqxhr === jqxhr) 
		{
			_data_cache_jqxhr_data.splice(i, 1);
			if($.isFunction(data.failure_callback))
				data.failure_callback(data.failure_parameters !== undefined ? data.failure_parameters : {});
			break;
		}
	}
}



function _sort_numeric_months(a, b)
{
	return a - b;
}



</script>


</html>