<style>

	body
	{
		font-family: verdana, sans-serif;
		font-size: 0.9em;
	}

	
	table 
	{
		border-collapse: collapse;
		border-spacing: 0px;
	}
	
	#data_table td
	{
		padding: 2px 5px 2px 5px;
		font-family: courier new, sans-serif;
		font-size: 0.8em;
	}
	
	#data_table th
	{
		padding-right: 10px;
		font-size: 0.8em;
	}
	
	.has_error
	{
		background-color: #F99;
		cursor: pointer;
	}
	
	.has_no_error
	{
		background-color: #CFC;
	}
	
	#results_table
	{
		border: 1px solid #555;
		margin-bottom: 40px;
	}
	
	#results_table tr
	{
		border-bottom: 1px solid #555;
	}
	
	#results_table td, #results_table th
	{
		border-right: 1px solid #555;
		padding: 3px 5px;
	}
	

</style>

<h2>Members Uploader</h2>
<div style="font-size: 0.9em;">
	<b>Note:</b><br>
	Click on the 'Do Validation' button to have the system test that the CSV structure and records are valid. Errors found will be displayed and should be fixed before committing.<br>
	Click on the 'Commit Records' button to have the system save records that can be saved.
	<br><br>
	[PUT CSV REQUIREMENTS HERE]
</div>
<br><br>
<form id="file_upload_form" method="post" enctype="multipart/form-data" actiond="'.site_url('testbed/test/do_check').'" target="upload_target">
	File: <input name="csv" id="file" size="27" type="file" /><br><br>
	<input type="submit" id="validate" name="validate" value="Do Validation" /> <input type="submit" id="commit" name="commit" value="Commit Records" /><br>
</form>

<div id="results"></div>
<iframe id="upload_target" name="upload_target" src="" style="width:1000px;height:600px;display:block;border:1px solid #ccc;"></iframe>




<script src="<?php echo $jquery_url; ?>" type="text/javascript"></script>

<script>
var frame_was_loaded = false;
var submit_type = "";
$(function()
{
    var form = $("#file_upload_form")[0];
    form.onsubmit = submiting;
    $("#validate").click(function(){
        $("#file_upload_form").attr("action","<?php echo $validate_url; ?>");
        submit_type = "validate";
    });
    $("#commit").click(function(){
        $("#file_upload_form").attr("action","<?php echo $commit_url; ?>");
        submit_type = "commit";
    });
    var iframe = $("#upload_target");
    iframe.load(frame_loaded);
	frame_was_loaded = true;
});

function submiting()
{
    
}

function frame_loaded()
{
    if(frame_was_loaded == false)	// Don't do anything on first load of the frame (which happens on page load)
        return;
	
    var u = $("#upload_target")[0];
    var d = u.contentDocument;          // Get the frame document
    var f = $(d).find("body");          // Get the document body element
    var data = jQuery.parseJSON(f.html());  // convert the body html into an object
    
    if(data.success)		// No error.
    {
		//console.log('success');
        $("#results").html(data.msg);
    }
    else
    {
        if(data.failures != undefined && (data.failures != 0 || data.failed_saves != 0))    // Not all records validated/committed.
        {
            var html = '';
            // if(submit_type == "validate")
            // {
                html = "<p>Not all the records in the file passed the validation check. The results are as follow:<br /><br/>";
                html += "Number of records that can be added: " + data.successes + "<br/>";
                html += "Number of records that can not be added: " + data.failures + "</p>";
            // }
            // else
            // {
                // html = "<p>Not all the records in the file could be saved. The results are as follows:<br /><br />";
                // html += "<p>Number of records that were saved: " + data.successes + "<br/>";
                // html += "Number of records that could not be added: " + (data.failures + data.failed_saves) + "</p>";
            // }
            html += "<p>The following table lists the problems found. Note that record numbers indicate the records processed in the order they appear in the file and not the actual line number.</p>"
            html += "<table id='results_table'><tr><th>Record&nbsp;#</th><th>Problems</th></tr>";
			var ids = [];
			for(var i = 0; i < data.successes + data.failures; i++) { ids[i] = false; }
            for(var i in data.record_errors)
            {
				var record = data.record_errors[i];
				var field_errors = _get_all_field_errors(record);
				var validation_errors = _get_all_validation_errors(record);
				
				var all = field_errors.concat(validation_errors);
				//console.log(all);
				
				html += "<tr><td>" + i + "</td><td> - " + all.join("<br /> - ") + "</td></tr>";
				ids[i] = all.join("\n - ").replace(/<br \/>/gi, "\n");
			}
			console.log(ids);
            html += "</table>";
			html += 'Data contained within the CSV file:<br />';
			html += '<div style="max-width: 1000px; max-height: 900px; overflow: auto;">' + _draw_data_table(data.csv, ids) + '</div>';
            $("#results").html(html);
        }
		else if(data.save_success != undefined)		// An error occurred while trying to save.
		{
			var html = "<p>There was an error when saving records as shown below:</p>";
			html += "<table id='results_table'><tr><th>Record&nbsp;#</th><th>Error</th></tr>";
			var ids = [];
			for(var id in data.save_success)
			{
				html += "<tr><td>" + id + "</td><td>" + data.save_success[id].replace(/\n/, '<br />') + "</td></tr>";
				ids[id] = data.save_success[id];
			}
			html += "</table>";
			html += '<p>Data contained within the CSV file:</p>';
			html += '<div style="max-width: 1000px; max-height: 900px; overflow: auto;">' + _draw_data_table(data.csv, ids) + '</div>';
            $("#results").html(html);
		}
        else    // Some other issue to do with the file or options used.
        {
            $("#results").html("<p>There was an error:<br>" + data.msg + '</p>');
        }
    }

}

function _get_all_field_errors(record)
{
	var errors = [];
	if(record.FIELD_ERRORS !== undefined)
	{
		for(var model in record.FIELD_ERRORS)
		{
			var model_errors = record.FIELD_ERRORS[model];
			for(var field in model_errors)
			{
				errors.push(_process_field_error(model_errors[field], field));
			}
		}
	}
	return errors;
}

function _get_all_validation_errors(record)
{
	var errors = [];
	if(record.VALIDATION_ERRORS !== undefined)
	{
		for(var model in record.VALIDATION_ERRORS)
		{
			var msg = "Encountered validation errors when creating a " + model + " instance: <br />" + record.VALIDATION_ERRORS[model].join('<br />');
			errors.push(msg);
		}
	}
	
	if(record.CUSTOM_ERRORS !== undefined)
	{
		for(var model in record.CUSTOM_ERRORS)
		{
			console.log(model);
			var msg = "Encountered validation errors:<br />" + record.CUSTOM_ERRORS[model];
			errors.push(msg);
			console.log(msg);
		}
	}
	
	return errors;
}


function _process_field_error(error, field)
{
	var msg = "";
	if(error.type == 'required')
	{
		msg = "A value is required for field: '" + field + "'";
	}
	else if(error.type == 'acceptable_value')
	{
		msg = "The value: '" + error.value + "' for field: '" + field + "' is not acceptable.";
	}
	else if(error.type == 'unique_value')
	{
		msg = "The value: '" + error.value + "' for field: '" + field + "' is not unique. Value is shared with record #" + error.shared_with;
	}
	else if(error.type == 'no_related_object')
	{
		if(error.specific_error !== undefined)
		{
			msg = "Unable to find related object using value in field: '" + field + "' due to this error: " + error.specific_error;
		}
		else
		{
			msg = "Unable to find related object using value in field: '" + field + "'";
		}
	}
	//console.log("msg is", msg, error, field);
	return msg;
}


function _draw_data_table(records, ids)
{
	var html = '<table id="data_table">';
	
	var first = records[0];
	var fields = [];
	for(var field in first)
	{
		fields.push(field);
	}
	// header
	html += '<tr><th>#</th>';
	for(var f = 0; f < fields.length; f++)
	{
		html += '<th>' + fields[f] + '</th>';
	}
	html += '</tr>';
	
	// records
	var row = 0;
	var record = undefined;
	for(var i = 0; i < records.length; i++)
	{
		row++;
		record = records[i];
		var classes = ids[row] ? "has_error" : "has_no_error";
		html += '<tr class="' + classes + '" title="' + (ids[row] ? ids[row] : "") + '"><td>' + row + '</td>';
		for(var f = 0; f < fields.length; f++)
		{
			html += '<td>' + record[fields[f]] + '</td>';
		}
		html += '</tr>';
	}
	
	html += '</table>';
	return html;
}

</script>

</body>
