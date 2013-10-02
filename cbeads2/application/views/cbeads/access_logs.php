<?php echo doctype();?>
<html>

<head>
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>cbeads/css/cbeads_style.css" />
    <script src="<?php echo base_url(); ?>libs/jquery-1.6.1.min.js" type="text/javascript"></script>
	
	<style>
	
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
	
	</style>
	
</head>

<body>

	<div id="view_types">
	</div>
	<div id="drawing_container" style="float:left; width: 650px; height: 450px; background-color: #DBFFB7; border: 1px solid gray;">
		<div class="graph_title" style="height: 30px;"></div>
		<div class="y_axis_container" style="width: 50px; height: 350px; float: left" ></div>
		<div class="graphing_area" style="width: 549px; height: 350px; background-color: #FFF; float:left; border-left: 1px solid black; border-bottom: 1px solid black;"></div>
	</div>
	<div id="selector" style="float: left;">
		<table>
			<tr>
				<td valign="top">View Mode:<br />
					<input id="data_view_months" type="radio" name="view_mode" value="months" /><label for="data_view_months">By Months</label><br/>
					<input id="data_view_days" type="radio" name="view_mode" value="days" checked="checked"/><label for="data_view_days">By Days</label><br/>
				</td>
			</tr>
			<tr>
			<td colspan="2">
				Year:<select id="year_selector"></select> Month:<select id="month_selector"></select>
			</td>
			</tr>
			<tr>
				<td>
					<input id="data_type_login_unique" type="radio" name="data_view" value="login_unique" checked="checked" /><label for="data_type_login_unique">Logins (Unique)</label><br/>
					<input id="data_type_login" type="radio" name="data_view" value="login" /><label for="data_type_login">Logins</label><br/>
					<input id="data_type_failure" type="radio" name="data_view" value="login_fail" /><label for="data_type_failure">Failed Logins</label><br/>
					<input id="data_type_logout" type="radio" name="data_view" value="logout" /><label for="data_type_logout">Logouts</label><br/>
					<input id="data_type_expired" type="radio" name="data_view" value="expired" /><label for="data_type_expired">Expired Sessions</label>
				</td>
			</tr>
		</table>
	</div>
	
	<div id="hidden_x_lables"></div>
	<div id="hidden_y_labels"></div>
	<div id="hidden_elements"></div>
	<div id="hidden_nav_elements"></div>
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
var _cur_year = undefined;
var _cur_month = undefined;
var available_dates = <?php echo $logs; ?>;

var _view_mode = "days";
var _data_view = "login_unique";

var _data_cache = {};		// Cache of data retrieved from the server.
var _data_cache_jqxhr_data = [];	// To associate values with a given post request.

$(document).ready(function(){

	setup();

	view();
});


function setup()
{
	_cur_year = _date.getCurrentYear();
	_cur_month = _date.getCurrentMonth() + 1;
	var days_in_month = _date.getDaysInMonth(_cur_month, _cur_year);
	
	$('input[name="view_mode"]').change(changed_view_mode);
	//alert($('input[name="view_mode"]:checked').val());
	populate_years_available();

	$('input[name="data_view"]').change(changed_data_to_view);
	//alert(_cur_year + ' ' + (_cur_month + 1) + ' has ' + days_in_month + ' days');
}

function changed_view_mode()
{
	//console.log('here');
	_view_mode = $(this).val();
	if(_view_mode == "months") 
		$('#month_selector').attr('disabled', 'disabled');
	else
		$('#month_selector').removeAttr('disabled');
	view();
}

function populate_years_available()
{
	for(var year in available_dates)
	{
		$('#year_selector').append('<option value="' + year + '">' + year + '</option>');
	}
	$('#year_selector').val(_cur_year).change(changed_year);
	populate_months_available(_cur_year);
	$('#month_selector').val(_cur_month).change(changed_month);
}

function populate_months_available(year)
{
	$('#month_selector').empty();
	var months = available_dates[year];
	months = months.sort(_sort_numeric_months);
	for(var i = 0; i < months.length; i++)
	{
		$('#month_selector').append('<option value="' + months[i] + '">' + _date.getMonthName(months[i]-1) + '</option>');
	}
}

function changed_year()
{
	populate_months_available(this.value);
}

function changed_month()
{
	view();
}

function changed_data_to_view()
{
	_data_view = $('input[name="data_view"]:checked').val();
	// Redraw graph with the requested data view.
	view();
}


function view()
{
	var year = Number($('#year_selector').val());
	var month = Number($('#month_selector').val());
	if(_view_mode == "years")
	{
		alert("To do years");
	}
	else if(_view_mode == "months")
	{
		// Work out if any months for this year haven't been retrieved from the server.
		var missing = [];
		for(var i = 1; i < 13; i++)
		{
			if(!does_cache_data_exist(year + '' + (i < 10 ? '0' + i : i)))
				missing.push(i);
		}
		if(missing.length > 0)	// Not all months retrieved, so need to get the missing ones.
		{
			fetch_value_for_cache({url: '<?php echo site_url(); ?>/cbeads/access_logs/get_monthly_events_for_year', post_data: {year: year, months: missing}, data_key: cache_months_for_year, custom_cache_parameters: {year: year, months: missing}, success_callback: got_year_data, success_parameters: {year: year}, failure_callback: cannot_get_year_data, failure_parameters: {year: year}});
			return;
		}
		view_months_of_year(year);
	}
	else if(_view_mode == "days")
	{
		var key = year + '' + ((month < 10) ? '0' + month : month);
		if(!does_cache_data_exist(key))
		{
			fetch_value_for_cache({url: '<?php echo site_url(); ?>/cbeads/access_logs/get_daily_events_for_month', post_data: {year: year, month: month}, data_key: key, success_callback: got_month_data, success_parameters: {year: year, month: month}, failure_callback: cannot_get_month_data, failure_parameters: {year: year, month: month}});
			return;
		}
		view_days_of_month(year, month);
	}
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


function view_days_of_month(year, month)
{
	// Generate array of values and labels to use in the graph.
	var days_in_month = _date.getDaysInMonth(month-1, year);
	var day = new Date(year, month-1, 1);
	var x_labels = [];
	var x_label_classes = [];
	var given_values = get_days_of_month(year, month);
	given_values = given_values[_data_view];
	//console.log(given_values);
	var values = [];
	for(var i = 1; i <= days_in_month; i++)
	{
		x_labels.push(i);
		var day_number = adjustDayNumber(day.getDay());
		if(day_number > 4)
			x_label_classes.push('weekend');
		else
			x_label_classes.push('');
		day.setDate(day.getDate() + 1);	// move to next day.
		values.push(given_values[i] !== undefined ? given_values[i] : 0);
	}
	var y_labels = {'login_unique': 'unique log in count', 'login': 'log in count', 'login_fail': 'failed log in count', 'logout': 'log out count', 'expired': 'expired session count'};
	draw_graph({values: values, x_labels: x_labels, y_axis_label: y_labels[_data_view], x_axis_label: 'days'}, {x_labels: x_label_classes});
	
}

function view_months_of_year(year)
{
	// Generate array of values and labels to use in the graph.
	var given_values = get_months_of_year(year);
	//given_values = given_values.login;
	given_values = given_values[_data_view];
	//console.log(given_values);
	var values = [];
	for(var i = 1; i < 13; i++)
	{
		values.push(given_values[i] !== undefined ? given_values[i] : 0);
	}
	var y_labels = {'login_unique': 'unique log in count', 'login': 'log in count', 'login_fail': 'failed log in count', 'logout': 'log out count', 'expired': 'expired session count'};
	var x_labels = ['Jan', 'Feb', 'Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
	draw_graph({values: values, x_labels: x_labels, y_axis_label: y_labels[_data_view], x_axis_label: 'months'}, {x_labels: []});
}

// Get days of month (year, month).
function get_days_of_month(year, month)
{
	var key = year + '' + (((month) < 10) ? '0' + (month) : (month));
	//console.log(_data_cache);
	//console.log('looking for ' + key);
	return _data_cache[key];
}

// Get events accumulated over months for a given year.
function get_months_of_year(year)
{
	var months = {login : {}, login_unique: {}, login_fail: {}, logout: {}, expired: {}};
	var events_list = ['login', 'login_fail', 'logout', 'expired'];
	for(var i = 1; i < 13; i++)
	{
		var key = year + '' + (i < 10 ? '0' + i : i);
		var month = _data_cache[key];
		if(month !== undefined)
		{
			for(var j = 0; j < events_list.length; j++)
			{
				var event_type = events_list[j];
				var count = 0;
				for(var day in month[event_type])
				{
					count += month[event_type][day];
				}
				months[event_type][i] = count;
			}
			months.login_unique[i] = month.login_unique_month_total;
		}
	}
	//console.log(months);
	return months;
}

// Checks if the cache has data for a given key.
function does_cache_data_exist(key)
{
	return (_data_cache[key] !== undefined) ? true : false;
}



// fetch_value_for_cache('url', 'post_data', 'function to call on success', 'function to call on failure', 'success_parameters')
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
				data.data_key($.parseJSON(data_received), data.custom_cache_parameters);
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

function got_month_data(params)
{
	view_days_of_month(params.year, params.month);
}

function cannot_get_month_data(params)
{
	alert('failed to get month data');
}

function got_year_data(params)
{
	view_months_of_year(params.year);
	//alert('');
}

function cannot_get_year_data(params)
{
	alert('failed to get year data');
}

// Caches month values for a given year.
function cache_months_for_year(data, params)
{
	var year = params.year;
	for(var month in data)
	{
		var key = year + '' + (month < 10 ? '0' + month : month);
		_data_cache[key] = data[month];
	}
}




function _get_highest_value(values)
{
	if(values.length == 0) return undefined;
	var max = values[0];
	for(var i = 1; i < values.length; i++)
		if(values[i] > max) max = values[i];
	return max;
}

function _get_lowest_value(values)
{
	if(values.length == 0) return undefined;
	var min = values[0];
	for(var i = 1; i < values.length; i++)
		if(values[i] < min) min = values[i];
	return min;
}

function _sort_numeric_months(a, b)
{
	return a - b;
}


</script>


</html>