<?php echo doctype();?>
<html>

<head>
	<meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>cbeads/css/cbeads_style_.css" />
    <script src="<?php echo base_url(); ?>libs/jquery-1.6.1.min.js" type="text/javascript"></script>
	
	<style>
		.namespace
		{
			color: blue;
		}
		.primary
		{
			color: red;
		}
		.unique
		{
			color: orange;
		}
		.relationship
		{
			color: green;
		}
		.m-m
		{
			color: purple;
		}
		.required
		{
			font-style: italic;
		}
		.more_info
		{
			cursor: pointer;
		}
		
		.fk_m_to_m
		{
			color: gray;
		}

		.error_text
		{
			border-bottom: 2px solid red;
			cursor: pointer;
		}
		.sentence
		{
			background-color: #F0F999;
			padding: 5px;
			font-family: courier, sans-serif;
		}
		.sql_run
		{
			font-family: courier, sans-serif;
			padding: 5px;
			background-color: #F0F999;
		}
		.sql_error
		{
			font-family: courier, sans-serif;
			padding: 5px;
		}
		.legend
		{
			padding-left: 10px;
		}
		
		pre
		{
			padding: 10px 10px 10px 10px;
			background-color: #EEEEEE;
			margin-left: 20px;
		}
		
		p
		{
			max-width: 800px;
			margin-left: 10px;
		}
		
		body
		{
			margin: 10px;
			font-family: sans-serif;
		}
		
		h1 {
			color: #222299;
		}
		h2 {
			color: #229922;
		}
		h3 {
			color: #992222;
		}
		
		a:visited
		{
			color: black;
		}
		a:hover
		{
			color: #FF5500;
		}
	
	</style>
</head>

<body>

<div id="help" style="width: 100%; height: 400px; display: none; overflow: auto; margin-bottom: 20px;">
	<h1 id="content">Content</h1>
	<p>
		This help document is split into these parts:
		<ul>
			<li><a href="#section_show">Showing Current Status of Models</a></li>
			<li><a href="#section_autogen">Automatic Generation of Model Files</a></li>
			<li><a href="#section_manipulate_models">Manipulating Models:</a>
				<ul>
					<li><a href="#section_create_models">Creating Models</a></li>
					<li><a href="#section_modify_models">Modifying Models</a></li>
					<li><a href="#section_remove_models">Removing Models</a></li>
				</ul>
			</li>
			<li><a href="#section_rels">Relationships</a>
				<ul>
					<li><a href="#section_one_to_many">One to Many and Many to One</a></li>
					<li><a href="#section_many_to_many">Many to Many</a></li>
					<li><a href="#section_namespaces">Relationships across Namespaces</a></li>
					<li><a href="#section_aliases">Aliases</a></li>
				</ul>
			</li>
			<li><a href="#section_attr_opts">Attribute Options</a>
				<ul>
					<li><a href="#section_data_types">Data Types</a></li>
				</ul>
			</li>
			<li><a href="#section_config">Configuration</a></li>
		</ul>
	</p>
	<p>
		Please read the <a href="#section_config">Configuration</a> section before doing anything else.
	</p>
	
	<h2 id="section_show">Showing Current Status of Models</h2>
	
	<p>
		To see the current status (attributes and relationships) of a model type:
	</p>
<pre>
show &lt;namespace&gt;		// Will display all models in this namespace
show &lt;namespace&gt;::&lt;model&gt;	// Will display a specific model
</pre>
	
	<h2 id="section_autogen">Automatic Generation of Model Files</h2>
	<p>
		It is possible to automatically generate files for models in a given namespace, or just for a specific model.
	</p>
<pre>
autogen &lt;namespace&gt;		// Will autogenerate for every model in that namespace.
autogen &lt;namespace&gt;::&lt;model&gt;	// Will autogenerate for this specific model.
</pre>
	
	<h2 id="section_manipulate_models">Manipulating Models</h2>
	<p>
		Models can be created, modified and removed. Creating and modifying is done using 'in...' sentences. While models are deleted using 'remove...' sentences.
	</p>
	
	<h3 id="section_create_models">Creating Models</h3>
	<p>
		The most basic syntax for creating a new model is:
	</p>
<pre>
in &lt;namespace&gt;, &lt;model&gt; has &lt;attribute&gt; {, &lt;attribute&gt;} 
</pre>
	<p>
		Example, creating a model called 'Person' with attributes name, age and gender:
	</p>
<pre>
in temp, person has name, age (as int), gender (which could be 'male' or 'female')
</pre>
	<p>
		Running this will result in a table called 'person' being created in the database 'temp'. A model file will have been created under 'application/models/temp/'. In addition you will see the sql that was run to create the table. Or if there was an error, an error message is displayed.
	</p>
	<p>
		In the above example, data types were specified for 'age' and 'gender'. See the section on <a href="#section_attr_opts">attribute options</a> for what options are available. By default all attributes are strings (varchar(255)).
	</p>
	<p>
		It is possible to run multiple sentences at once by ending a sentence with a fullstop and starting another one.
	</p>
<pre>
in temp, person has name, age (as int), gender (which could be 'male' or 'female'). in temp, job has name, description, salary
</pre>
	<p>
		This would create two models. You can put a sentence on a new line, however the previous sentence <b>must</b> be ended with a fullstop. Otherwise the system will report an error.
	</p>
	
	<h3 id="section_modify_models">Modifying Models</h3>
	<p>
		It is possible to alter the attributes in a model once it has been created using the following sentences:
	</p>
<pre>
// To add attributes to this model
in &lt;namespace&gt;, &lt;model&gt; add &lt;attribute&gt; {, &lt;attribute&gt;}

// To remove attributes from this model
in &lt;namespace&gt;, &lt;model&gt; remove &lt;attribute&gt; {, &lt;attribute&gt;}

// To rename attributes in this model
in &lt;namespace&gt;, &lt;model&gt; rename &lt;attribute&gt; to &lt;new_attribute_name&gt; {, &lt;attribute&gt; to &lt;new_attribute_name&gt;}

// To modify the properties of attributes in this model
in &lt;namespace&gt;, &lt;model&gt; modify &lt;attribute&gt; (&lt;attribute options&gt;) {, &lt;attribute&gt; (&lt;attribute options&gt;)}
</pre>
	<p>
		Note: attributes (including aliases) are treated case insensitively. The same goes for model and namespaces.
	</p>
	
	<h3 id="section_remove_models">Removing Models</h3>
	<p>
		To completely remove a model use this sentence:
	</p>
<pre>
remove &lt;namespace&gt;::&lt;model&gt;
</pre>
	<p>
		You can only remove one model at a time. The table in the database will be removed. The model file will be deleted. Use this with caution as there is no confirmation when removing a model.
	</p>
	
	<h2 id="section_rels">Relationships</h2>
	<p>
		Relationships between models can be set up and altered (to some extent). It is possible to create relationships  while creating models or after they have been created.
	</p>
	
	<h3 id="section_one_to_many">One to Many and Many to One</h3>
	<p>
		The following two examples will show how relationships can be set up. In the first the relationship is defined while both models are created. In the second it is assumed both models already exist:
	</p>
<pre>
in temp, person has name, age, gender, job (has name, description, salary)
// Assuming the models already exist
in temp, person add job
</pre>
	<p>
		The SBOML Manager will first look if an attribute to add is the same name as a model in that namespace. If there was no model called 'job' in namespace person then job would be treated as a non relationship attribute.
	</p>
	<p>
		The above examples show a many to one relationship being declared (from the perspective of the person model). To declare a one to many relationship would be done so:
	</p>
<pre>
in temp, job has name, description, salary, many person (has name, age, gender)
// Assuming the models already exist
in temp, job add many person
</pre>
	
	<h3 id="section_many_to_many">Many to Many</h3>
	<p>
		The following two examples will show how to set up a many to many relationship. This is similar to creating one to many relationships, except that the key words 'and vice versa' need to be supplied.
	</p>
<pre>
in temp, person has name, age, gender, many job (has name, description, salary) and vice versa
// Assuming the models already exist
in temp, person add many job and vice versa
</pre>
	<p>
		Setting up a many to many relationship will create a model used for mapping. By default it is named as &lt;model1&gt;_&lt;model2&gt;_map. In the above example this would be: 'person_job_map'.
	</p>
	<p>
		It is possible to provide a custom name to use for the mapping model. However this is unlikely to be required very often (if at all):
	</p>
<pre>
in temp, person add many job and vice versa via employment_map
</pre>
	<p>
		There is a requirement that mapping names end in '_map'. If this is not done the relationship will not be recognised as a many to many mapping.
	</p>
	
	<h3 id="section_namespaces">Relationships across Namespaces</h3>
	<p>
		It is possible to define relationships across namespaces using the &lt;namespace&gt;&lt;model&gt; format to define a model. The following example shows a relationship being created between two models in different namespaces:
	</p>
<pre>
in namespace_a, person add many namespace_b::job
</pre>
	
	<h3 id="section_aliases">Aliases (Relationship Names)</h3>
	<p>
		When working with model instances, relationships are accessed using aliases as defined in the model files. In most cases the alias name is the name of the related model (pluralised if necessary). For example with the following relationship:
	</p>
<pre>
in temp, person add job
</pre>
	<p>
		if you have a person instance you access the related job as: $person->Job. If you have a job instance you access the related persons collection as $job->Persons. Note that the relationship names are capitalised.
	</p>
	<p>
		A custom alias for a relationship can be specified either when creating the relationship, or later on by renaming the relationship:
	</p>
<pre>
in temp, person add job as MyJob
// Or if the relationship already exists, it can be renamed:
in temp, person rename Job to MyJob
</pre>
	<p>
		Now accessing the related job from a person instance is done as: $person->MyJob.
		It is also possible to specify a custom alias on the reverse side of a relationship when creating a new relationship. For example:
	</p>
<pre>
in temp, person add job as MyJob and Workers
// The above is equivalent to doing the following:
in temp, person add job
in temp, person rename Job to MyJob
in temp, job rename Persons to Workers
</pre>
	<p>
		Aliases can also be customised in many to many relationship using the same syntax. For example:
	</p>
<pre>
in temp, person add many job and vice versa as MyJobs and Workers
</pre>
	<p>
		When not given custom aliases, the two models involved are pluralised.
	</p>
	
	<h2 id="section_attr_opts">Attribute Options</h2>
	<p>
		There are a number of options that can be set for model attributes (excludes aliases). These are:
		<ul>
			<li>the datatype OR specfying an enumeration</li>
			<li>is required or optional</li>
			<li>is unique or not unique</li>
		</ul>
		The following examples show how to use these options:
	</p>
<pre>
// Sets name as being required and unique
in temp, person modify name (is required, is unique)
// Sets age as being required and given a data type as integer
in temp, person modify age (is required, is not unique, as integer)
// Sets gender to use an enumeration and to be required
in temp, person modify age gender (which could be 'male' or 'female', is required)
</pre>
	<h3 id="section_data_types">Data Types</h3>
	<p>
		The data types that can be specified are the same as those used by mysql. In addition some simple data types have been created:
		<ul>
			<li>'string' = varchar(100)</li>
			<li>'number' = number(20,10)</li>
			<li>'boolean' = bit</li>
		</ul>
		Enumerations are declared using the 'which could be &lt;opt&gt; { or &lt;opt&gt; }' format.
	</p>
	<p>
		For some data types, there are options that can be specified. For example a varchar can be told how many characters it can contain. For example:
	</p>
<pre>
in temp, person modify name (as varchar(50))
</pre>
	
	
	<h2 id="section_config">Configuration</h2>
	<p>
		Before starting there is one important configuration to be aware of. The SBOML Manager checks the editable_model table in the sboml database to see for which databases it is allowed to make modifications to models. If you try to use a "in &lt;namespace&gt;..." sentence, a "delete &lt;namespace&gt;..." sentence or a "autogen &lt;namespace&gt;..." sentence and the namespace isn't listed in the editable_model table then an error will be displayed.
	<p>
	<p>
		You can use "show &lt;namespace&gt;..." sentences on any database in the system.
	</p>
	<p>
		When models are updated resulting in changes to their respective files, a backup is made of the previous version (if there is one). The only times the model files are generated are when using 'autogen...', creating a model or changing the relationships in a model.
	</p>
	<p>
		To avoid the directory becoming full of backup files, a limit is set on the maximum number of backup files that can exist for a given model. This can be set in the sbo_manager controller file. Look in the constructor function for <code>FileManager::$max_backups</code> and set to whatever value you want.
	</p>

</div>

<div id="help_show" style="cursor: pointer">Show Help</div>

<div id="work">
	<textarea id="text" style="width: 100%; height: 80px"></textarea></br>
	<button value="Submit" onclick="submit()">Submit</button>
	<div style="font-size: 1em; width: 100%; --max-height: 500px; overflow-y: auto; padding-top: 10px" id="result"></div>
</div>



</body>

<script type="text/javascript">
	
	$(document).ready(function(){
		$('#help_show').click(function(){
			$('#help').slideToggle('fast');
		});
	});
	
	
	function submit()
	{
		$('#result').html('');
		$.post("<?php echo site_url('sboml/sbo_manager/submit'); ?>", {text: $('#text').val() }, submit_callback);
	}
	
	function submit_callback(result)
	{
		try 
		{
			result = $.parseJSON(result);
		}
		catch(e)
		{
			$('#result').html('Server response was not valid JSON. Response is shown below:<br/><br/>' + result);
			return;
		}
		if(result.success)
		{
			html = "";
			if(result.performed == 'sboml_gen')
			{
				var str = result.sentences.join('<br/>');
				if(str == "") str = "Call succeeded but no data was returned. Check that the requested database is in the allow list.";
				html = '<div style="font: 1em courier, sans-serif">' + str + '</div>';
				if(str != "")
				{
					html += '<br/><br/><br/><div>Legend:<ul class="legend">';
					html += '<li><span class="namespace">Model</span> - Represents a model</li>';
					html += '<li><span class="primary">Primary</span> - Indicates the attribute is the primary key</li>';
					html += '<li><span class="unique">Unique</span> - Indicates the attribute stores only unique values</li>';
					html += '<li><span class="relationship">Relationship</span> - Indicates the attribute represents a relationship</li>';
					html += '<li><span class="m-m">Many to Many</span> - Indicates the attribute represents a many to many relationship</li>';
					html += '<li><span class="required">Required</span> - Indicates the attribute requires values</li>';
					html += '<li><span class="fk_m_to_m">Many to Many: Foreign Key</span> - Indicates the attribute is used as a foreign key in a many to many relationship.</li>';
					html += '</ul></div>';
				}
			}
			else if(result.performed == 'autogen_files')
			{
				html = 'No errors encountered while auto generating model files.';
			}
			else if(result.performed == 'db_mod')
			{
				html = 'No errors encountered while modifying the DB.';
				var str = result.sql_run.join("<br/>");
				str = str.replace(/\\n|\n/g, '<br/>');
				html += '<p>The following sql was run:</p><div class="sql_run">' + str + '</div>';
			}
			else
			{
				html = 'No errors encounceted while performing unknown (' + result.performed + ') action.';
			}
			$('#result').html(html).scrollTop(0);
		}
		else
		{
			//console.log('got error', result);
			$('#result').html(result).scrollTop(0);
			if(result.performed == 'sboml_gen')
			{
				//$('#result').html('Encountered error(s) while generating SBOML sentences.');
				$('#result').html(encode_html_chars(result.error));
			}
			else if(result.performed == 'autogen_files')
			{
				$('#result').html(encode_html_chars(result.error));
			}
			else if(result.performed == 'db_mod')
			{
				var html = "Encountered an error.";
				var s_start	= result.sentence_start_pos;
				var s_end = (result.sentence_end_pos !== undefined) ? result.sentence_end_pos : undefined;
				var sentence = "";
				if(s_end !== undefined)
					sentence = result.text.substring(s_start, s_end);
				else
					sentence = result.text.substring(s_start);
				if(result.phase == 'parsing')
				{
					var error_pos = result.error_pos - s_start;
					var error_end = 0;
					var patt = /^\s*([\w]*)/;
					var before_error = sentence.substr(0, error_pos);
					var after_error = sentence.substring(error_pos);
					var matches = patt.exec(after_error);
					//console.log(matches);
					after_error = sentence.substring(error_pos + matches[0].length);
					//console.log(before_error, 8, matches[0], 8, after_error);
					html += ' At or after position ' + error_pos + ' in the sentence:<br/><br>';
					html += '<span class="sentence">' + before_error + '<span class="error_text" title="' + result.error_message + '" >';
					if(matches[0] == "")
					{
						html += '<span style="xwidth: 20px; display: inline-block">&nbsp;</span>';
					}
					else
					{
						html += matches[0].replace(' ', '&nbsp;');
					}
					html += '</span>' + after_error + '</span>';
					html += '<br/><br/>Error message: ' + encode_html_chars(result.error_message);
					if(result.error_resolutions.length > 0)
					{
						html += '<br/><br/>Possible ways to fix this error:<br/>';
						html += '"' + encode_html_chars(result.error_resolutions.join('", "')) + '"';
					}
				}
				else
				{
					var error_pos = result.location - s_start;
					var error_end = 0;
					var patt = /^\s*([\w]*)/;
					var before_error = sentence.substr(0, error_pos);
					var after_error = sentence.substring(error_pos);
					var matches = patt.exec(after_error);
					//console.log(matches);
					after_error = sentence.substring(error_pos + matches[0].length);
					//console.log(before_error, 8, matches[0], 8, after_error);
					//console.log(encode_html_chars(result.error_message));
					html += ' At or after position ' + error_pos + ' in the sentence:<br/><br>';
					html += '<span class="sentence">' + before_error + '<span class="error_text" title="' + encode_html_chars(result.error_message) + '" >';
					if(matches[0] == "")
					{
						html += '<span style="xwidth: 20px; display: inline-block">&nbsp;</span>';
					}
					else
					{
						html += matches[0].replace(' ', '&nbsp;');
					}
					html += '</span>' + after_error + '</span>';
					html += '<br/><br/>Error message: ' + encode_html_chars(result.error_message);
					if(result.error_resolutions.length > 0)
					{
						html += '<br/><br/>Possible ways to fix this error:<br/>';
						html += '"' + encode_html_chars(result.error_resolutions.join('", "')) + '"';
					}
					if(result.sql_run !== undefined)
					{
						var str = result.sql_run.join('<br/><br/>');
						str = str.replace(/\\n|\n/g, '<br/>');
						html += '<p>The following sql was run:</p><div class="sql_run">' + str + '</div>';
						html += '<p>The sql error message is:</p><div class="sql_error">' + result.sql_error + '</div>';
					}
				}
				
				$('#result').html(html);
			}
			else
			{
				$('#result').html('No errors encounceted while performing unknown (' + result.performed + ') action.');
			}
			$('#result').scrollTop(0);
		}
	}
	
	function encode_html_chars(string)
	{
		string = string.replace(/</g, '&lt;');
		return string.replace(/>/g, '&gt;');
	}
	
</script>

</html>