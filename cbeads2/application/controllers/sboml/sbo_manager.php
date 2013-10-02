<?php

/************************************  sboml/sbo_manager.php  *************************************
 *
 *  This tool allows creating and modifying of databases and tables via Smart Business Objects 
 *  (SBO) using the SBO modeling language (SBOML).
 *  Each database is a namespace containing models that represent tables. The tool automates the
 *  creation/updating/removal of models.
 *
 ** Changlog:
 *
 *  2012-10-24 - Markus
 *  - First version that can be tested. Still lots of work to do. Cannot yet delete modles.
 *
 *  2013-01-22 - Markus
 *	- Added new abilities such as being able to specify aliases for relationships, rename aliases
 *    and renaming attributes. Still on the todo list is being able to delete and rename a model
 *    as well as checking model names are not in a reserved words list.
 *
 *************************************************************************************************/

class Sbo_manager extends CI_Controller 
{
	//private $all_relationships = array();		// Stores all relationships found in the database(s).
	//private $all_table_info = array();			// Stores all the table information (except relationships) found in the database(s).

	private $sentences = array();			// Stores the components of every sentence encountered.
	private $cur_sentence = -1;				// Indicates the current sentence being parsed.
	private $text = '';						// String to parse. Accessible by functions. MUST NOT BE MODIFIED.
	
	private $in_sub_sentence = 0;			// Indicates if currently in a sub sentence (sentence enclosed in parentheses). Counter because sentences can be nested!
	
	private $objects = array();
	private $object_changes = array();		// key: ns-mdl, val: changes to make to the model.
	
	private $parse_debug = array();			// To hold debug messages generated when parsing.
	

	CONST ERR_UNKNOWN = 1;
	CONST ERR_UNKNOWN_SENTENCE_ACTION = 100;			// in <namespace>, <model> <action>  action token unknown
	CONST ERR_REQUIRE_SENTENCE_ACTION = 101;			// <action> action token not provided
	CONST ERR_REQUIRE_ATTRIBUTE = 102;					// has <attr>, <attr>...  attr token not provided
	CONST ERR_QUOTED_STRING_NOT_CLOSED = 103;			// "some_string... Quoted string not closed. Detected when the parser runs out of characters to process.
	CONST ERR_INVALID_CHARACTER = 104;					// Invalid character found. Excepting comma or fullstop.
	CONST ERR_REQUIRE_ATTRIBUTE_OPTIONS = 105;			// name(<options>)  options not provided when modifying attribute
	CONST ERR_INVALID_IN_SENTENCE_START = 106;			// in sentence not started using form in <namespace>, <model>
	CONST ERR_INVALID_RENAME_SYNTAX = 107;				// rename syntax was not of the form '<attr> to <new_attr>'
	// Errors with entities and relationships
	CONST ERR_REQUIRE_MODEL_COMPONENT = 200;			// <namespace>::<model> model not provided
	CONST ERR_MAPPING_ENTITY_INVALID = 201;				// <entity> and vice versa via <mapping_entity> ...  mapping entity must end in '_map'
	CONST ERR_EXPECTING_NAMESPACE_OR_MODEL = 202;		// <namespace>::<model> OR <namespace> .... required for delete model/namespace sentence
	
	// Errors with normal attribute options
	CONST ERR_INVALID_OPTION_ARGUMENT = 300;			// attr (<option>)   option is invalid
	CONST ERR_INVALID_DATATYPE_DEFINITION = 301;		// attr(as <datatype>)  datatype token is invalid
	CONST ERR_REQUIRE_OR_BETWEEN_ENUM_TOKENS = 302;		// which could be <token> OR <token>  OR missing between tokens
	
	// Other errors
	CONST ERR_ACCESS_TO_MODEL_DISALLOWED = 400;			// Access to the model which is being changed is disallowed.
	
	// Constructor
    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url', 'html'));
		// Check in which namespaces models can be modified
		$records = Doctrine_Core::getTable('sboml\Editable_Model')->findAll();
		foreach($records as $record)
			ModelManager::$allowable_dbs[] = $record->model_name;
		// Set how many file backups to keep for a model
		FileManager::$max_backups = 3;
    }

    function index()
	{
		$this->load->view('sboml/sbo_manager_view', array());
		return;
	}
	
	public function submit()
	{
		$text = $this->input->post('text');
		// if(preg_match('/^\s*show\s+(.*)/', $text, $matches))
		if(preg_match('/^\s*show/', $text, $matches))
		{
			$token = trim(substr($text, strlen($matches[0])));
			if(preg_match('/^(\w+)::(\w+)$/', $token, $matches))	// specified a model
			{
				if(DBManager::does_db_exist($matches[1]) == FALSE)
				{
					echo json_encode(array('success'=>FALSE, 'error' => "Could not display sboml for '" . $matches[1] . '::' . $matches[2] . "' as there is no database called '" . $matches[1] . "'", 'performed' => 'sboml_gen'));
					return;
				}
				if(DBManager::does_table_exist($matches[2], $matches[1]) == FALSE)
				{
					echo json_encode(array('success'=>FALSE, 'error' => "Could not display sboml for '" . $matches[1] . '::' . $matches[2] . "' as there is no table called '" . $matches[2] . "'", 'performed' => 'sboml_gen'));
					return;
				}
				$sentences = array();
				$model = ModelManager::get_model($matches[1], $matches[2]);
				$sentences[] = $model->generate_sboml_sentence();
				echo json_encode(array('success' => TRUE, 'sentences' => $sentences, 'performed' => 'sboml_gen'));
			}
			elseif(preg_match('/^(\w+)/', $token))	// specified just a namespace
			{
				if(DBManager::does_db_exist($token) == FALSE)
				{
					echo json_encode(array('success'=>FALSE, 'error' => "Could not display sboml as there is no database called '" . $token . "'", 'performed' => 'sboml_gen'));
					return;
				}
				$sentences = array();
				$tables = DBManager::get_database_tables($token);
				foreach($tables as $table)
				{
					$model = ModelManager::get_model($token, $table);
					$sentences[] = $model->generate_sboml_sentence();
				}
				echo json_encode(array('success' => TRUE, 'sentences' => $sentences, 'performed' => 'sboml_gen'));
			}
			else
			{
				echo json_encode(array('success'=>FALSE, 'error' => "Invalid namespace or model specified. Usage: 'show <namespace>' or 'show <namespace>::<model>' ", 'performed' => 'sboml_gen'));
			}
			
		}
		elseif(preg_match('/^\s*autogen\s+/', $text, $matches))
		{
			$token = substr($text, strlen($matches[0]));
			if(preg_match('/^(\w+)::(\w+)$/', $token, $matches))	// specified a model
			{
				if(DBManager::does_db_exist($matches[1]) == FALSE)
				{
					echo json_encode(array('success'=>FALSE, 'error' => "Could not generate model files for '" . $matches[1] . '::' . $matches[2] . "' as there is no database called '" . $matches[1] . "'", 'performed' => 'autogen_files'));
					return;
				}
				if(!in_array($token, ModelManager::$allowable_dbs))
				{
					echo json_encode(array('success'=>FALSE, 'error' => "Could not generate models files for database '" . $token . "'. Please add this to the allowable database list.", 'performed' => 'autogen_files'));
					return;
				}
				if(DBManager::does_table_exist($matches[2], $matches[1]) == FALSE)
				{
					echo json_encode(array('success'=>FALSE, 'error' => "Could not generate model files for '" . $matches[1] . '::' . $matches[2] . "' as there is no table called '" . $matches[2] . "'", 'performed' => 'autogen_files'));
					return;
				}
				$result = $this->auto_generate_for_model($matches[1], $matches[2]);
				$result['performed'] = 'autogen_files';
				echo json_encode($result);
			}
			elseif(preg_match('/^(\w+)$/', $token))	// specified just a namespace
			{
				if(DBManager::does_db_exist($token) == FALSE)
				{
					echo json_encode(array('success'=>FALSE, 'error' => "Could not generate model files as there is no database called '" . $token . "'", 'performed' => 'autogen_files'));
					return;
				}
				if(!in_array($token, ModelManager::$allowable_dbs))
				{
					echo json_encode(array('success'=>FALSE, 'error' => "Could not generate models files for database '" . $token . "'. Please add this to the allowable database list.", 'performed' => 'autogen_files'));
					return;
				}
				$result = $this->auto_generate_for_namespace($token);
				$result['performed'] = 'autogen_files';
				echo json_encode($result);
			}
			else
			{
				echo json_encode(array('success'=>FALSE, 'error' => "Invalid namespace or model specified. Usage: 'autogen <namespace>' or 'autogen <namespace>::<model>' ", 'performed' => 'autogen_files'));
			}
		}
		else
		{
			$result = $this->process_sboml($text);
			$result['performed'] = 'db_mod';
			$result['text'] = $text;
			echo json_encode($result);
		}
	}
	
	function process_sboml($text)
	{
		$this->text = $text;
		$result = $this->parse_sentence($this->text);
		
		if($result['success'] == FALSE)
		{
			
			$result['error_message'] = $this::get_error_detail_message($result['error']);
			$result['error_resolutions'] = $this::get_error_resolutions($result['error']);
			$result['phase'] = 'parsing';
		//cbeads_nice_vardump($result);
		//cbeads_nice_vardump($this->parse_debug);
			return $result;
		}
		
		
		return $this->process_parsed_sentences();
	}

	// Will generate a model files for all tables in a given database.
	// Returns an associative array containing:
	//	success: set to TRUE on success, otherwise FALSE
	//	failed: array of associative arrays detailing which models could not be generated.
	private function auto_generate_for_namespace($namespace)
	{
		$failed = array();
		$models = DBManager::get_database_tables($namespace);
		// foreach(array_keys($this->all_table_info[$namespace]) as $model)
		foreach($models as $model)
		{
			$result = $this->auto_generate_for_model($namespace, $model);
			if($result['success'] == FALSE)
			{
				$failed[] = array('namespace' => $namespace, 'model' => $model, 'error' => $result['error']);
			}
		}
		if(count($failed) > 0)
			return array('success' => FALSE, 'error' => 'Encountered errors generating one or more model files.', 'failed' => $failed);
		else
			return array('success' => TRUE);
	}
	
	// Will generate a model file for a table in a given database.
	// Returns an associative array containing;
	// 	success: TRUE if all went well, otherwise false.
	//	error: An error constant representing the error encountered.
	private function auto_generate_for_model($namespace, $model)
	{
		$mdl = ModelManager::get_model($namespace, $model);
		$result = FileManager::update_model_file($mdl);
		return $result;
	}
	
	// Parses a string containing SBOML sentences. Returns arrays for each sentence containing parsed data.
	private function parse_sentence($text)
	{
		// in <namespace>, <model> (<create_sentence> | <add_sentence> | <remove_sentence> | <modify_sentence> | <rename_sentence>).
		
		$text = $this->text;
		
		$parse_pos = 0;		// Position in the string currently at.
		$data = array();
		$safety = 0;	// Will prevent the system from getting stuck in a loop due to issues with parsing. Limits to X sentences!
		while($parse_pos < strlen($this->text) && $safety < 20)
		{
			$this->cur_sentence = $sentence_index = count($this->sentences);
			$this->sentences[] = array();
			$this->parse_debug[] = "PROCESSING: '$text'";
			$data['parse_start'] = $parse_pos;
			// if( preg_match('/^\s*in\s+(\w+)\s*,\s*(\w+)\s+/', $text, $matches))
			if( preg_match('/^\s*in\s+(\w+)\s*,\s*(\w+)/', $text, $matches))
			{
				$data['ns'] = $matches[1];
				$data['mdl'] = $matches[2];
				$this->sentences[$sentence_index] = $data;
				// Work out the type of action.
				$parse_pos += strlen($matches[0]);
				$remainder = substr($text, strlen($matches[0]));
				if(preg_match('/^\s+(\w+)/', $remainder, $matches))
				{
					$action = $data['action'] = $matches[1];
					if($action == 'has')
					{
						//$this->cur_pd_segment = count($this->parse_data);
						//$this->sentences[$this->cur_pd_segment] = array('namespace' => $data['ns'], 'model' => $data['mdl']);
						$parse_pos += strlen($matches[0]);
						$result = $this->parse_create_sentence($parse_pos);
						if($result['success'] == FALSE)
						{
							$result['context'] = substr($text, 0, $result['error_pos'] + 20);
							$result['sentence_start_pos'] = $data['parse_start'];
							return $result;
						}
						$data['attributes'] = $result['data'];
						$data['action'] = 'create';
					}
					elseif($action == 'add')
					{
						//$this->cur_pd_segment = count($this->parse_data);
						//$this->parse_data[$this->cur_pd_segment] = array('namespace' => $data['ns'], 'model' => $data['mdl']);
						$parse_pos += strlen($matches[0]);
						$result = $this->parse_create_sentence($parse_pos);
						if($result['success'] == FALSE)
						{
							$result['context'] = substr($text, 0, $result['error_pos'] + 20);
							$result['sentence_start_pos'] = $data['parse_start'];
							return $result;
						}
						$data['attributes'] = $result['data'];
					}
					elseif($action == 'remove')
					{
						$parse_pos += strlen($matches[0]);
						$result = $this->parse_remove_sentence($parse_pos);
						if($result['success'] == FALSE)
						{
							$result['context'] = substr($text, 0, $result['error_pos'] + 20);
							$result['sentence_start_pos'] = $data['parse_start'];
							return $result;
						}
						$data['action'] = 'remove';
						$data['attributes'] = $result['data'];
						//cbeads_nice_vardump($data);
					}
					elseif($action == 'modify')
					{
						$parse_pos += strlen($matches[0]);
						$result = $this->parse_modify_sentence($parse_pos);
						if($result['success'] == FALSE)
						{
							$result['context'] = substr($text, 0, $result['error_pos'] + 20);
							$result['sentence_start_pos'] = $data['parse_start'];
							return $result;
						}
						$data['action'] = 'modify';
						$data['attributes'] = $result['data'];
						//cbeads_nice_vardump($data);
					}
					elseif($action == 'rename')
					{
						$parse_pos += strlen($matches[0]);
						$result = $this->parse_rename_sentence($parse_pos);
						if($result['success'] == FALSE)
						{
							$result['context'] = substr($text, 0, $result['error_pos'] + 20);
							$result['sentence_start_pos'] = $data['parse_start'];
							return $result;
						}
						$data['action'] = 'rename';
						$data['attributes'] = $result['data'];
					}
					else	// Unknown action.
					{
						$this->parse_debug[] = "'$action' is not a valid 'action' keyword. Expecting: 'has', 'add', 'remove', 'modify' or 'rename'";
						$err = $this::ERR_UNKNOWN_SENTENCE_ACTION;
						return array('success' => FALSE, 'error' => $err, 'error_token' => $action, 'error_pos' => $parse_pos, 'sentence_start_pos' => $data['parse_start'], 'context' => substr($text, 0, $parse_pos + 20));
					}
				}
				else	// Require action.
				{
					$this->parse_debug[] = "Expecting action keyword: 'has', 'add', 'remove', 'modify' or 'rename'";
					$err = $this::ERR_REQUIRE_SENTENCE_ACTION;
					return array('success' => FALSE, 'error' => $err, 'error_token' => '', 'error_pos' => $parse_pos, 'sentence_start_pos' => $data['parse_start'], 'context' => substr($text, 0, $parse_pos + 20));
				}
			}
			// Model/namespace delete sentence
			elseif(preg_match('/^\s*remove/', $text, $matches))
			{
				$parse_pos += strlen($matches[0]);
				$remainder = substr($text, strlen($matches[0]));
				if(preg_match('/^\s+(\w+)::(\w+)/', $remainder, $matches))	// Delete a entity
				{
					$data['ns'] = $matches[1];
					$data['mdl'] = $matches[2];
					$data['action'] = 'delete_model';
					$data['applies_to'] = 'model';
					$data['attributes'] = array();
					$parse_pos += strlen($matches[0]);
				}
				// elseif(preg_match('/^\s+(\w+)/', $remainder, $matches))	// Delete a complete namespace
				// {
					// $data['ns'] = $matches[1];
					// $data['action'] = 'delete_namespace';
					// $data['applies_to'] = 'namespace';
					// $parse_pos += strlen($matches[0]);
				// }
				else
				{
					$this->parse_debug[] = "Expecting namespace or model for remove sentence: remove &lt;namespace&gt;<br/>remove &lt;namespace&gt;::&lt;model&gt;";
					$err = $this::ERR_EXPECTING_NAMESPACE_OR_MODEL;
					return array('success' => FALSE, 'error' => $err, 'error_pos' => $parse_pos, 'sentence_start_pos' => $data['parse_start'], 'context' => substr($text, 0, $parse_pos + 20));
				}
			}
			// Must start sentence as in <namespace>, <model>
			else
			{
				$this->parse_debug[] = "Expecting sentence of form: in &lt;namespace&gt;, &lt;model&gt;";
				$err = $this::ERR_INVALID_IN_SENTENCE_START;
				return array('success' => FALSE, 'error' => $err, 'error_pos' => $parse_pos, 'sentence_start_pos' => $data['parse_start'], 'context' => substr($text, 0, $parse_pos + 20));
			}
			
			$data['parse_end'] = $parse_pos;
			$this->sentences[$sentence_index] = $data;
			$safety++;
			//echo "position is: $parse_pos out of " . strlen($this->text) . "<br/>";
			$text = substr($this->text, $parse_pos);
			if(trim($text) == '')	// Quit loop if there are empty spaces after the sentence
				break;
		}
		//cbeads_nice_vardump($this->sentences);
		return array('success' => TRUE);
	}
	
	private function parse_create_sentence(&$parse_pos)
	{
		// Sentence is made up of:
		// attr_sentence 	= (<norm_attr> | <rel_attr>) {, <norm_attr> | <rel_attr>}
		// rel_attr 		= many_to_one | one_to_many | many_to_many ;
		// many_to_one 		= <entity> ['('<create_sentence>')'] [as <alias>] ;
		// one_to_many 		= many <entity> ['('<create_sentence>')'] [as <alias>] ;
		// many_to_many 	= many <entity> ['('<create_sentence>')'] and vice versa [via <table>] [as <alias>] ;
		// norm_attr 		= <attr_name> ['('<attr_options>')'] ;
	
		// An attribute may be one word for a normal attribute or many_to_one relation, or it may
		// be 'many <entity>'. Optionally it may be followed by attribute options or an entity
		// creation statement inside of (). For relationships additional tokens may exist.
		// First find everything before a '(' or a ',' or a '.'. Then work out the type of
		// attribute and process it. Continue until no more attributes left in the sentence.
		$text = substr($this->text, $parse_pos);

		$sentence_finished = false;
		$data = array();
		$safety = 100;  // Remove when well tested.
		while(!$sentence_finished && $safety > 0)
		{
			//echo "Looking at: $text<br/>";
			if(preg_match('/^\s*many\s+([\w:]+)/', $text, $matches))	// entity (*_to_many relation)
			{
				$location = $parse_pos;
				$result = $this->parse_tomany_relation($parse_pos);
				//cbeads_nice_vardump($result);
				if($result['success'])
				{
					$result['attr_type'] = $result['type'];
					unset($result['type'], $result['success']);
					$result['location'] = $location;
					$data[] = $result;
				}
				else
				{
					$this->parse_debug[] = "Error parsing related entity.";
					return $result;
				}
			}
			elseif(preg_match('/^\s*([\w:]+)/', $text, $matches))		// normal attr or entity (many_to_one relation)
			{
				// cbeads_nice_vardump($matches);
				$attr_name = trim($matches[1]);
				$offset = strlen($matches[0]);
				$remainder = substr($text, $offset);
				//echo "remainder: $remainder<br/>";
				//$this->parse_data[$this->cur_pd_segment]['attributes'][] = $attribute;
				// If the attribute name contains '::' then treat as a entity for a relationship.
				// Else If the following characters are '(has ...'), then treat as an entity for a relationship.
				// Else If the following characters are 'as <alias>', then treat as an entity for a relationship.
				// Else If the following character is '(' (ie without the 'has') then treat as normal attribute.
				// Finally, if none of the above apply, then cannot be sure yet. Have to parse the rest
				// of the sentence(s) first.
				if(strpos($attr_name, '::') !== FALSE || preg_match('/^\s*\(has/', $remainder) ||
					preg_match('/^\s*as\s*\w+/', $remainder) )
				{
					//echo "relation<br/>";
					$location = $parse_pos;
					$result = $this->parse_toone_relation($parse_pos);
					if($result['success'])
					{
						unset($result['success']);
						$result['attr_type'] = 'many-to-one';
						$result['location'] = $location;
						$data[] = $result;
					}
					else
					{
						//echo "Error parsing relationship. Msg: " . $result['msg'] . '<br/>';
						//exit();
						$this->parse_debug[] = "Error parsing related entity.";
						return $result;
					}
				}
				elseif(preg_match('/^\s*\(/', $remainder))
				{
				//echo "normal<br/>";
					$result = $this->parse_normal_attribute($parse_pos);
					//cbeads_nice_vardump($attr_opts);
					if($result['success'])
					{
						$data[] = array('attr_type' => 'normal', 'attr_name' => $attr_name, 'options' => $result['data'], 'location' => $parse_pos);
					}
					else
					{
						//echo "Error parsing attribute<br/>";
						//exit();
						$this->parse_debug[] = 'Error parsing attribute';
						$result['attribute'] = $attr_name;
						return $result;
					}
				}
				else	// Unknown attribute type.
				{
					// Expecting only a single word token representing a normal attribute or
					// an entity.
					$data[] = array('attr_type' => 'unknown', 'attr_name' => $attr_name, 'location' => $parse_pos);
					//cbeads_nice_vardump($matches);
					$parse_pos += $offset;
				}
			}
			else
			{
				$this->parse_debug[] = "Expecting attribute after " . substr($this->text, 0, $parse_pos) . " <-- (position: $parse_pos)";
				return array('success' => FALSE, 'error' => $this::ERR_REQUIRE_ATTRIBUTE, 'error_pos' => $parse_pos);
			}
			
			// End of sentence check. Check what the next non space character is.
			// If its a comma, continue. If its a fullstop then this is the end of the sentence.
			// If the in_sub_sentence flag is set and a closing parenthesis is found then this
			// is the end of the sub sentence.
			// If the string ends, then this is the end of the sentence.
			// Anything else is a syntax error.
			$text = substr($this->text, $parse_pos);
			if(preg_match('/\s*(.)/', $text, $matches))
			{
				if($matches[1] == ',')
				{
					$parse_pos += strlen($matches[0]);
					$text = substr($this->text, $parse_pos);
					//echo "continue<br/>";
				}
				elseif($matches[1] == '.' || ($this->in_sub_sentence != 0 && $matches[1] == ')'))
				{
					$parse_pos += strlen($matches[0]);
					//echo "End of sentence or sub sentence<br/>";
					break;
				}
				else
				{
					$this->parse_debug[] = "Invalid character '" . $matches[1] . "', expecting comma or fullstop.";
					return array('success' => FALSE, 'error' => $this::ERR_INVALID_CHARACTER, 'error_pos' => $parse_pos + 1, 'error_token' => $matches[1]);
				}
			}
			else	// End of string.
			{
				//echo "End of string.<br/>";
				break;
			}
			
			$safety--;
		}
		
		//echo "<br/>Processed Successfully all parts: <br/>";
		//cbeads_nice_vardump($data);
		return array('success' => TRUE, 'data' => $data);
	}
	
	private function parse_remove_sentence(&$parse_pos)
	{
		// Sentence must be constructed this way:
		// remove (<attr_name> | <entity>) {, (<attr_name> | <entity>)}
		// TODO: will have to extend this to allow entity references by aliases. This is needed 
		// especially for cases where a model has multiple relationships to the same model.
		// Possibly like so: Person has name, age, Person as Father, Person as Mother ...
		$text = substr($this->text, $parse_pos);

		$sentence_finished = false;
		$data = array();
		$safety = 100;  // Remove when well tested.
		while(!$sentence_finished && $safety > 0)
		{
			$attribute = array('type' => 'unknown', 'attr' => NULL, 'namespace' => NULL, 'model' => NULL);
			// if(preg_match('/^\s*(\w+)::(\w+)/', $text, $matches)) // entity in namespace::model format.
			// {
				// $attribute['type'] = 'entity';
				// $attribute['namespace'] = $matches[1];
				// $attribute['model'] = $matches[2];
				// $attribute['name'] = trim($matches[0]);
				// $offset = strlen($matches[0]);
			// }
			//else
			if(preg_match('/^\s*(\w+)/', $text, $matches))	// normal attribute or alias
			{
				$attribute['name'] = $matches[1];
				$offset = strlen($matches[0]);
			}
			else
			{
				// echo "Syntax error: expecting attribute after " . substr($this->text, 0, $parse_pos) . " <-- (position: $parse_pos)";
				// exit();
				$this->parse_debug[] = "Expecting attribute after " . substr($this->text, 0, $parse_pos) . " <-- (position: $parse_pos)";
				return array('success' => FALSE, 'error' => $this::ERR_REQUIRE_ATTRIBUTE, 'error_pos' => $parse_pos);
			}
			$attribute['location'] = $parse_pos;
			$data[] = $attribute;
			$parse_pos += $offset;
			
			// End of sentence check. Check what the next non space character is.
			// If its a comma, continue. If its a fullstop then this is the end of the sentence.
			// If the string ends, then this is the end of the sentence.
			// Anything else is a syntax error.
			$text = substr($this->text, $parse_pos);
			if(preg_match('/\s*(.)/', $text, $matches))
			{
				if($matches[1] == ',')
				{
					$parse_pos += strlen($matches[0]);
					$text = substr($this->text, $parse_pos);
					//echo "continue<br/>";
				}
				elseif($matches[1] == '.')
				{
					$parse_pos += strlen($matches[0]);
					//echo "End of sentence<br/>";
					break;
				}
				else
				{
					//echo "Invalid token '" . $matches[1] . "'. Expecting a comma or fullstop.";
					//exit();
					$this->parse_debug[] = "Invalid character '" . $matches[1] . "', expecting comma or fullstop.";
					return array('success' => FALSE, 'error' => $this::ERR_INVALID_CHARACTER, 'error_pos' => $parse_pos + 1, 'error_token' => $matches[1]);
				}
			}
			else	// End of string.
			{
				//echo "End of string.<br/>";
				break;
			}
			
			$safety--;
		}
		
		return array('success' => TRUE, 'data' => $data);
	}
	
	private function parse_modify_sentence(&$parse_pos)
	{
		// Sentence must be constructed this way:
		// modify <attr_name> '(' <attr_options> ')' {, <attr_name> '(' <attr_options> ')'};
		$text = substr($this->text, $parse_pos);

		$sentence_finished = false;
		$data = array();
		$safety = 100;  // Remove when well tested.
		while(!$sentence_finished && $safety > 0)
		{
			$attribute = array('type' => 'unknown', 'attr' => NULL, 'namespace' => NULL, 'model' => NULL);
			if(preg_match('/^\s*([\w:]+)/', $text, $matches))		// First check if there is an attribute name present.
			{
				$attr_name = $matches[1];
				//echo "modify $attr_name<br>";
				// expecting an attribute name followed by options inside parentheses.
				if(preg_match('/^\s*(\w+)\s*\(/', $text, $matches))	
				{
					$attribute['name'] = $matches[1];
					$offset = strlen($matches[0]);
					//echo "start parsepos = $parse_pos";
					$location = $parse_pos;
					$result = $this->parse_normal_attribute($parse_pos);
					//echo " end parsepos = $parse_pos";
					//cbeads_nice_vardump($result);
					if($result['success'])
					{
						$data[] = array('attr_type' => 'normal', 'attr_name' => $attr_name, 'options' => $result['data'], 'location' => $location);
					}
					else
					{
						// echo "Error parsing attribute<br/>";
						// exit();
						$this->parse_debug[] = "Parsing normal attribute failed";
						return $result;
					}
				}
				else
				{
					// echo "Syntax error: expecting open parenthesis followed by list of options for this attribute " . substr($this->text, 0, $parse_pos) . " <-- (position: $parse_pos)";
					// exit();
					$this->parse_debug[] = "Expecting open parenthesis followed by list of options for this attribute " . substr($this->text, 0, $parse_pos) . " <-- (position: $parse_pos)";
					return array('success' => FALSE, 'error' => $this::ERR_REQUIRE_ATTRIBUTE_OPTIONS, 'error_pos' => $parse_pos);
				}
			}
			else
			{
				// echo "Syntax error: expecting attribute name after " . substr($this->text, 0, $parse_pos) . " <-- (position: $parse_pos)";
				// exit();
				$this->parse_debug[] = "Expecting attribute after " . substr($this->text, 0, $parse_pos) . " <-- (position: $parse_pos)";
				return array('success' => FALSE, 'error' => $this::ERR_REQUIRE_ATTRIBUTE, 'error_pos' => $parse_pos);
			}
			//$parse_pos += $offset;
			//$data[] = $attribute;
			
			// End of sentence check. Check what the next non space character is.
			// If its a comma, continue. If its a fullstop then this is the end of the sentence.
			// If the string ends, then this is the end of the sentence.
			// Anything else is a syntax error.
			$text = substr($this->text, $parse_pos);
			//echo "parsing $text<br/>";
			if(preg_match('/\s*(.)/', $text, $matches))
			{
				if($matches[1] == ',')
				{
					$parse_pos += strlen($matches[0]);
					$text = substr($this->text, $parse_pos);
					//echo "continue<br/>";
				}
				elseif($matches[1] == '.')
				{
					$parse_pos += strlen($matches[0]);
					//echo "End of sentence<br/>";
					break;
				}
				else
				{
					// echo "Invalid token '" . $matches[1] . "'. Expecting a comma or fullstop.";
					// exit();
					$this->parse_debug[] = "Invalid character '" . $matches[1] . "', expecting comma or fullstop.";
					return array('success' => FALSE, 'error' => $this::ERR_INVALID_CHARACTER, 'error_pos' => $parse_pos + 1, 'error_token' => $matches[1]);
				}
			}
			else	// End of string.
			{
				//echo "End of string.<br/>";
				break;
			}
			
			$safety--;
		}
		
		return array('success' => TRUE, 'data' => $data);
	}
	
	private function parse_rename_sentence(&$parse_pos)
	{
		// rename attr to <attr_name> to <attr_name> (, <attr_name> to <attr_name>)
	
		$text = substr($this->text, $parse_pos);
		
		$sentence_finished = false;
		$data = array();
		$safety = 100;  // Remove when well tested.
		while(!$sentence_finished && $safety > 0)
		{
			if(preg_match('/^\s*(\w+)\s+to\s+(\w+)/', $text, $matches))
			{
				$data[] = array('attr_name' => $matches[1], 'rename_to' => $matches[2], 'location' => $parse_pos);
				$parse_pos += strlen($matches[0]);
			}
			else
			{
				$this->parse_debug[] = "Expecting rename of attribute/relationship-alias to be of the form <attr_name> to <new_attr_name> " . substr($this->text, 0, $parse_pos) . " <-- (position: $parse_pos)";
				return array('success' => FALSE, 'error' => $this::ERR_INVALID_RENAME_SYNTAX, 'error_pos' => $parse_pos);
			}
			
			// End of sentence check. Check what the next non space character is.
			// If its a comma, continue. If its a fullstop then this is the end of the sentence.
			// If the string ends, then this is the end of the sentence.
			// Anything else is a syntax error.
			$text = substr($this->text, $parse_pos);
			if(preg_match('/\s*(.)/', $text, $matches))
			{
				if($matches[1] == ',')
				{
					$parse_pos += strlen($matches[0]);
					$text = substr($this->text, $parse_pos);
				}
				elseif($matches[1] == '.')
				{
					$parse_pos += strlen($matches[0]);
					break;
				}
				else
				{
					$this->parse_debug[] = "Invalid character '" . $matches[1] . "', expecting comma or fullstop.";
					return array('success' => FALSE, 'error' => $this::ERR_INVALID_CHARACTER, 'error_pos' => $parse_pos + 1, 'error_token' => $matches[1]);
				}
			}
			else	// End of string.
			{
				break;
			}
			
			$safety--;
		}
		return array('success' => TRUE, 'data' => $data);
	}
	
	// Parses a to-many relation.
	private function parse_tomany_relation(&$parse_pos)
	{
		// one_to_many 	= many <entity> ['('<create_sentence>')'] [as <alias> [and <alias>]] ;
		// many_to_many = many <entity> ['('<create_sentence>')'] and vice versa [via <entity>] [as <alias> [and <alias>]] ;
		
		$text = substr($this->text, $parse_pos);
		$ns = $this->sentences[$this->cur_sentence]['ns'];
		$mdl = '';
		$type = 'one-to-many';
		$cust_map_model = '';
		$cust_map_namespace = '';
		$result = array();
		
		// Must get namespace and model.
		if(preg_match('/^\s*many\s+(\w+)::(\w+)/', $text, $matches))
		{
			$ns = $matches[1];
			$mdl = $matches[2];
		}
		elseif(preg_match('/^\s*many\s+(\w+)/', $text, $matches))
		{
			$mdl = $matches[1];
			// Check for this case:  'namespace:: '
			if(substr($text, strlen($matches[0]), 2) == '::')
			{
				return array('success' => FALSE, 'error' => $this::ERR_REQUIRE_MODEL_COMPONENT, 'error_pos' => $parse_pos + strlen($matches[0]) + 2);
			}
		}
		// else
		// {
			// echo "missing model name token<br />";
			// return array('parse_failed' => TRUE, 'msg' => 'missing model name token');
		// }
		$parse_pos += strlen($matches[0]);
		$remainder = substr($this->text, $parse_pos);
		//echo "ns: $ns, model: $mdl, remainder: $remainder<br/>";
		// if followed by a parenthesis then this is a create sentence if it starts
		// with 'has'.
		if(preg_match('/^\s*\(/', $remainder, $matches))
		{
			$offset = strlen($matches[0]);
			if(preg_match('/^\s*has\s*/', substr($remainder, $offset), $matches))
			{
				$parse_pos += ($offset + strlen($matches[0]));
				
				$parent_sentence_index = $this->cur_sentence;

				$this->sentences[] = array();
				$index = count($this->sentences) - 1;
				$sentence = array();
				$sentence['ns'] = $ns;
				$sentence['mdl'] = $mdl;
				$sentence['action'] = 'create';
				$sentence['parse_start'] = $parse_pos - strlen($matches[0]);
				$this->sentences[$index] = $sentence;
				
				$this->cur_sentence = $index;
				$this->in_sub_sentence++;
				$result = $this->parse_create_sentence($parse_pos);
				if($result['success'] == FALSE)
				{
					$result['sub_sentence_start_post'] = $sentence['parse_start'];
					return $result;
				}
				$data = $result['data'];
				unset($result['data']);
				$this->in_sub_sentence--;
				$this->cur_sentence = $parent_sentence_index;
				
				$this->sentences[$index]['parse_end'] = $parse_pos - 1;
				$this->sentences[$index]['attributes'] = $data;
				//cbeads_nice_vardump($data);
			}
		}
		
		// This relation is a many-to-many relation if 'and vice versa' was specified.
		if(preg_match('/^\s*and\s+vice\s+versa/', substr($this->text, $parse_pos), $matches))
		{
			$type = 'many-to-many';
			$parse_pos += strlen($matches[0]);
			$pos = $parse_pos;
			$using_custom = FALSE;
			// Check if mapping entity was explicitly defined. Note, the model name must end in 
			// '_map' for this table to be picked up as a mapping table!
			if(preg_match('/^\s+via\s+(\w+)::(\w+)/', substr($this->text, $parse_pos), $matches))
			{
				$cust_map_namespace = $matches[1];
				$cust_map_model = $matches[2];
				$parse_pos += strlen($matches[0]);
				$using_custom = TRUE;
			}
			elseif(preg_match('/^\s+via\s+(\w+)/', substr($this->text, $parse_pos), $matches))
			{
				$cust_map_namespace = $ns;
				$cust_map_model = $matches[1];
				$parse_pos += strlen($matches[0]);
				$using_custom = TRUE;
			}
			if($using_custom && substr($cust_map_model, strlen($cust_map_model) - 4) != '_map')
			{
				return array('success' => FALSE, 'error' => $this::ERR_MAPPING_ENTITY_INVALID, 'error_pos' => $pos);
			}
		}
		
		// Check if an alias is specified for this relation.
		$res = $this->parse_alias($parse_pos);
		if($res['alias1'] !== FALSE)
			$result['alias1'] = $res['alias1'];
		if($res['alias2'] !== FALSE)
			$result['alias2'] = $res['alias2'];
		// $alias = '';
		// if(preg_match('/^\s*as\s*(\w+)/', substr($this->text, $parse_pos), $matches))
		// {
			// $parse_pos += strlen($matches[0]);
			// $alias = $matches[1];
		// }
		
		$result['success'] = TRUE;
		$result['namespace'] = $ns;
		$result['model'] = $mdl;
		$result['type'] = $type;
		$result['mapping_table'] = $cust_map_model;
		$result['mapping_namespace'] = $cust_map_namespace;

		return $result;
	}
	
	// Parses a to-one relation.
	private function parse_toone_relation(&$parse_pos)
	{
		// many_to_one 	= <entity> ['('<create_sentence>')'] [as <alias>] ;
		
		$text = substr($this->text, $parse_pos);
		$ns = $this->sentences[$this->cur_sentence]['ns'];
		$mdl = '';
		$result = array();
		// Must get namespace and model.
		if(preg_match('/^\s*(\w+)::(\w+)/', $text, $matches))
		{
			$ns = $matches[1];
			$mdl = $matches[2];
		}
		elseif(preg_match('/^\s*(\w+)/', $text, $matches))
		{
			$mdl = $matches[1];
			// Check for this case:  'namespace:: '
			if(substr($text, strlen($matches[0]), 2) == '::')
			{
				return array('success' => FALSE, 'error' => $this::ERR_REQUIRE_MODEL_COMPONENT, 'error_pos' => $parse_pos + strlen($matches[0]) + 2);
			}
		}
		// else
		// {
			// echo "missing model name token<br />";
			// return array('parse_failed' => TRUE, 'msg' => 'missing model name token');
		// }
		$parse_pos += strlen($matches[0]);
		$remainder = substr($this->text, $parse_pos);
		// if followed by a parenthesis then this is a create sentence if it starts
		// with 'has'.
		if(preg_match('/^\s*\(/', $remainder, $matches))
		{
			$offset = strlen($matches[0]);
			if(preg_match('/^\s*has\s*/', substr($remainder, $offset), $matches))
			{
				$parse_pos += ($offset + strlen($matches[0]));
				
				$parent_sentence_index = $this->cur_sentence;

				$this->sentences[] = array();
				$index = count($this->sentences) - 1;
				$sentence = array();
				$sentence['ns'] = $ns;
				$sentence['mdl'] = $mdl;
				$sentence['action'] = 'create';
				$sentence['parse_start'] = $parse_pos - strlen($matches[0]);
				$this->sentences[$index] = $sentence;
				
				$this->cur_sentence = $index;
				$this->in_sub_sentence++;
				$result = $this->parse_create_sentence($parse_pos);
				if($result['success'] == FALSE)
				{
					$result['sub_sentence_start_post'] = $sentence['parse_start'];
					return $result;
				}
				$data = $result['data'];
				unset($result['data']);
				$this->in_sub_sentence--;
				$this->cur_sentence = $parent_sentence_index;
				// for($i = $sentence['parse_start']; $i < $parse_pos - 1; $i++)
					// echo $this->text[$i];
				$this->sentences[$index]['parse_end'] = $parse_pos - 1;
				$this->sentences[$index]['attributes'] = $data;
				//cbeads_nice_vardump($data);
			}
		}
		
		// Check if an alias is specified for this relation.
		$res = $this->parse_alias($parse_pos);
		if($res['alias1'] !== FALSE)
			$result['alias1'] = $res['alias1'];
		if($res['alias2'] !== FALSE)
			$result['alias2'] = $res['alias2'];
		
		$result['success'] = TRUE;
		$result['namespace'] = $ns;
		$result['model'] = $mdl;
		return $result;
	}
	
	// Works out if an alias was specified. Use for relationship parsing.
	// Returns an associative array where:
	// 	'alias1' is the alias to use for this relationship on the current model. If set to FALSE
	//  then there was no alias specified.
	//  'alias2' is the alias to use for this relationship on the related model. If set to FALSE
	//  then there was no alias specified.
	private function parse_alias(&$parse_pos)
	{
		$alias1 = FALSE;
		$alias2 = FALSE;
		if(preg_match('/^\s*as\s+(\w+)/', substr($this->text, $parse_pos), $matches))
		{
			$parse_pos += strlen($matches[0]);
			$alias1 = $matches[1];
			// Is a 2nd alias specified which will be used in the related model's definition.
			if(preg_match('/^\s+and\s+(\w+)/', substr($this->text, $parse_pos), $matches))
			{
				$parse_pos += strlen($matches[0]);
				$alias2 = $matches[1];
			}
		}
		return array('alias1' => $alias1, 'alias2' => $alias2);
	}
	
	// Parses options for a normal attribute. The parser has detected that there is an opening
	// parenthesis after the attribute name so this function will work out what options have been
	// set.
	private function parse_normal_attribute(&$parse_pos)
	{
		// attr_options 	= as <data_type> | (is required | is optional) | (values must be unique | values can be duplicated) {, (as <data_type> | (is required | is optional) | (values must be unique | values can be duplicated) } ;
		// data_type 		= <dt_string> | <dt_integer> | <dt_float> | <dt_decimal> | <dt_date> | <dt_timestamp> | <dt_blob> | <dt_enum> |  etc...
		$text = substr($this->text, $parse_pos);
		$data = array();
		
		// The characters from the opening parenthesis to the closing parenthesis are processed one
		// by one. There may be multiple option segments which are delimited by a comma.
		$start_at = strpos($text, '(') + 1;
		$parenthesis_cnt = 1;
		$in_quotes = FALSE;			// Need to know if currently inside quoted text.
		$options_string = "";		// stores whole options string
		$opts = array(array('text' => '', 'start' => $start_at));			// stores the options separated into segments
		$o = 0;
		for($i = $start_at; $i < strlen($text); $i++)
		{
			if($text[$i] == '(') $parenthesis_cnt++;
			elseif($text[$i] == ')') $parenthesis_cnt--;
			if($parenthesis_cnt == 0) break;		// Out of options block.
			$options_string .= $text[$i];
			if($in_quotes === FALSE)
			{
				if($text[$i] == "'" || $text[$i] == '"')
					$in_quotes = $text[$i];
				// Check if this options segment is completed. However for cases such as 
				// "(as float(4,4), is required)", if the parenthesis_cnt is more than 1 then the
				// comma is not interpreted as a delimiter. IE, float(4,4) must be taken as a whole.
				if($text[$i] == ',' && $parenthesis_cnt == 1)	
				{
					$o++;
					$opts[$o] = array('text' => '', 'start' => $i);
				}
				else
					$opts[$o]['text'] .= $text[$i];
			}
			else
			{
				if($text[$i] == $in_quotes) $in_quotes = FALSE;
				
				$opts[$o]['text'] .= $text[$i];
			}
		}
		
		// Process option segments.
		for($o = 0; $o < count($opts); $o++)
		{
			$result = $this->parse_normal_attribute_option($opts[$o]['text']);
			if($result['success'] == FALSE)
			{
				$result['error_token'] = $opts[$o]['text'];
				$result['error_pos'] = $opts[$o]['start'] + $parse_pos;
				return $result;
			}
			unset($result['success']);
			$data[] = $result;
		}
		
		//cbeads_nice_vardump($opts);
		//cbeads_nice_vardump($data);
		$i++;

		$parse_pos += $i;
	
		return array('success' => TRUE, 'data' => $data);
	}
	
	// Parses text representing an option for a normal attribute. Returns array containing info
	// on the option.
	private function parse_normal_attribute_option($option)
	{
		// attr_options 	= as <data_type> | (is required | is optional) | (is unique | is not unique)) {, (as <data_type> | (is required | is optional) | (is unique | is not unique)) } ;
		// data_type 		= <dt_string> | <dt_integer> | <dt_float> | <dt_decimal> | <dt_date> | <dt_timestamp> | <dt_blob> | <dt_enum> |  etc...
		$ret = array('success' => TRUE);
		if(preg_match('/^\s*is\s+required$/', $option, $matches))
		{
			$ret = array('success' => TRUE, 'option_type' => 'required', 'value' => TRUE);
		}
		elseif(preg_match('/^\s*is\s+optional$/', $option, $matches))
		{
			$ret = array('success' => TRUE, 'option_type' => 'required', 'value' => FALSE);
		}
		elseif(preg_match('/^\s*is\s+unique$/', $option, $matches))
		{
			$ret = array('success' => TRUE, 'option_type' => 'unique', 'value' => TRUE);
		}
		elseif(preg_match('/^\s*is\s+not\s+unique$/', $option, $matches))
		{
			$ret = array('success' => TRUE, 'option_type' => 'unique', 'value' => FALSE);
		}
		elseif(preg_match('/^\s*which\s+could\s+be\s+(.+)/', $option, $matches))
		{
			$enum_part = $matches[1];
			$result = $this->process_enumeration($enum_part);
			if($result['success'] == FALSE)
			{
				return $result;
			} 
			$ret['option_type'] = 'data_type';
			$ret['data_type'] = 'enum';
			$ret['values'] = $result['values'];
			$ret['length'] = 0;
			$ret['decimal'] = 0;
		}
		elseif(preg_match('/^\s*as(.+)/', $option, $matches))	// Possible data type definition
		{
			$result = $this->parse_datatype_option(trim($matches[1]));
			if($result['success'] == FALSE)
			{
				return $result; //'Invalid data type definition: "' . trim($matches[1]) . '"';
			}
			$ret['option_type'] = 'data_type';
			$ret['data_type'] = $result['type'];
			$ret['length'] = $result['length'];
			$ret['decimal'] = $result['decimal'];
		}
		else
		{
			$this->parse_debug[] = 'Invalid option argument: ' . $option;
			return array('success' => FALSE, 'error' => $this::ERR_INVALID_OPTION_ARGUMENT);
		}
		return $ret;
	}
	
	private function process_enumeration($text)
	{
		//dt_enum 		= which could be <quoted_string> { or <quoted_string> } ;
		$in_quotes = FALSE;
		$enums = array();
		$e = -1;
		$between = '';
		$text = trim($text);
		for($i = 0; $i < strlen($text); $i++)
		{
			if($text[$i] == "'" || $text[$i] == '"')	// inside a quoted string
			{
				$between = trim($between);
				if($e != -1 && strtolower($between) != 'or')		// Must have ' or ' between each quoted string.
				{
					return array('success' => FALSE, 'error' => $this::ERR_REQUIRE_OR_BETWEEN_ENUM_TOKENS);
				}
				$in_quotes = $text[$i];
				$e++;
				$enums[$e] = '';
				$i++;
				while($text[$i] != $in_quotes)
				{
					$enums[$e] .= $text[$i];
					$i++;
					if($i >= strlen($text))	// exceeded available characters. 
					{
						return array('success' => FALSE, 'error' => $this::ERR_QUOTED_STRING_NOT_CLOSED);
					}
				}
				// echo $enums[$e];
				$between = '';
			}
			else	// outside quoted string.
			{
				$between .= $text[$i];
			}
		}
		return array('success' => TRUE, 'values' => $enums);
	}
	
	private function parse_datatype_option($text)
	{
		// Check if there is a value inclosed in parenthesis after the data type:
		// eg varchar(255), float(10,2)
		// Enums and sets not allowed to be defined this way, so report error if encountered.
		// Will first check if a simple(alias) data type was specified, then checks for valid
		// data types as exist for this database language.
		$type = '';
		$length = 0;
		$decimal = 0;
		if(preg_match('/^(\w+)$/', $text, $matches))
		{
			$type = $matches[0];
		}
		elseif(preg_match('/^(\w+)\s*\(\s*([0-9]+|[0-9]+\s*,\s*[0-9]+)\s*\)/', $text, $matches))
		{
			//if($matches[1] == 'enum' || $matches[1] == 'set')
			//	//return array('success' => FALSE, 'parse_failed' => 'Invalid data type definition: "' . $matches[1] . '" is not allowed to be defined this way');
			$type = $matches[1];
			$parts = preg_split('/,/', $matches[2]);
			$length = (int)$parts[0];
			if(count($parts) == 2)
				$decimal = (int)$parts[1];
		}
		else
		{
			//return array('success' => FALSE, 'parse_failed' => 'Expecting ' . $text . ' to be a datatype definition');
			return array('success' => FALSE, 'error' => $this::ERR_INVALID_DATATYPE_DEFINITION);
		}
		$simple_types = DBManager::get_simple_types();
		if(isset($simple_types[$type]))
		{
			//echo "is simple: $type";
			$length = isset($simple_types[$type]['length']) ? $simple_types[$type]['length'] : 0;
			$decimal = isset($simple_types[$type]['decimal']) ? $simple_types[$type]['decimal'] : 0;
			$type = $simple_types[$type]['type'];
			//echo " -> type: $type, length: $length<br/>";
		}

		$valid_types = DBManager::get_valid_types();
		if(!isset($valid_types[$type]))
		{
			return array('success' => FALSE, 'error' => $this::ERR_INVALID_DATATYPE_DEFINITION);
		}
		
		return array('success' => TRUE, 'type' => $type, 'length' => $length, 'decimal' => $decimal);
	}
	
	private function is_quoted_string($text)
	{
	
	}
	
	
	
	// Called once parsing is completed. Parsed data is then processed to generate/update models.
	private function process_parsed_sentences()
	{
		//cbeads_nice_vardump($this->sentences);

		$models = array();
		$i = 0;
		foreach($this->sentences as $sentence)
		{
			if(ModelManager::is_access_allowed($sentence['ns']))
			{
				$models[] = ModelManager::get_model($sentence['ns'], $sentence['mdl']);
			}
			else
			{
				$result['success'] = FALSE;
				$result['error'] = $this::ERR_ACCESS_TO_MODEL_DISALLOWED;
				$result['location'] = $sentence['parse_start'];
				$result['error_message'] = $this->get_error_detail_message($result['error']);
				$result['error_resolutions'] = $this->get_error_resolutions($result['error']);
				$result['sentence_start_pos'] = $sentence['parse_start'];
				$result['sentence_end_pos'] = $sentence['parse_end'];
				return $result;
			}
		}
		// Can now apply actions to models.
		foreach($this->sentences as $sentence)
		{
			$result = $models[$i]->apply_action($sentence['action'], $sentence['attributes']);
			//cbeads_nice_vardump($result);
			//TODO: If failed need to stop further processing. User will have to fix the error.
			//cbeads_nice_vardump($models[$i]);
			if($result['success'] == FALSE)
			{
				$result['error_message'] = $models[$i]->get_error_detail_message($result['error']);
				$result['error_resolutions'] = $models[$i]->get_error_resolutions($result['error']);
				$result['sentence_start_pos'] = $sentence['parse_start'];
				$result['sentence_end_pos'] = $sentence['parse_end'];
				if(!isset($result['location'])) $result['location'] = $sentence['parse_start'];
				//cbeads_nice_vardump($result);
				return $result;
			}
			$i++;
		}
		//return;
		//cbeads_nice_vardump($models);
		//exit();
		// Now validate models to find any potential problems before committing.
		$errors = array();
		$sql_statements = array();
		$models = ModelManager::get_all_models();
		//cbeads_nice_vardump($models);
		foreach($models as $model)
		{
			$result = $model->validate();
			if($result['success'] == FALSE)
			{
			//echo "Validation failure in $model->namespace $model->model<br/>";
				$errors[] = $result['errors'];
			}
		}
		if(count($errors))
		{
			echo "Found validation errors in model<br/>";
			cbeads_nice_vardump($errors);
			return;
		}
		//echo "There are " . count($models) . " models to commit";
		//return;
		foreach($models as $model)
		{
			$result = $model->commit_stage1();
			if(isset($result['sql_run']) && $result['sql_run'] != "")
				$sql_statements[] = $result['sql_run'];
			if($result['success'] == FALSE)
			{
				// // What to do when there is an error?
				// echo "Error occurred during commit stage 1 for model: " . $model->namespace . '::' . $model->model;
				// echo "<br/><br/><code>" .$result['error'].'</code><br/>';
				// echo "<br/>Unable to continue!<br/>";
				// //return;
				$result['error_message'] = $model->get_error_detail_message($result['error']);
				$result['error_resolutions'] = $model->get_error_resolutions($result['error']);
				$result['sentence_start_pos'] = $sentence['parse_start'];
				$result['sentence_end_pos'] = $sentence['parse_end'];
				$result['namespace'] = $model->namespace;
				$result['model'] = $model->model;
				$result['sql_run'] = $sql_statements;
				if(!isset($result['location'])) $result['location'] = $sentence['parse_start'];
				//cbeads_nice_vardump($result);
				return $result;
			}
		}
		foreach($models as $model)
		{
			$result = $model->commit_stage2();
			if(isset($result['sql_run']) && $result['sql_run'] != "")
				$sql_statements[] = $result['sql_run'];
			if($result['success'] == FALSE)
			{
				// // What to do when there is an error?
				// echo "Error occurred during commit stage 2 for model: " . $model->namespace . '::' . $model->model;
				// echo "<br/><code>" .$result['error'].'</code>';
				// echo "<br/>Unable to continue!<br/>";
				//return;
				$result['error_message'] = $model->get_error_detail_message($result['error']);
				$result['error_resolutions'] = $model->get_error_resolutions($result['error']);
				$result['sentence_start_pos'] = $sentence['parse_start'];
				$result['sentence_end_pos'] = $sentence['parse_end'];
				$result['namespace'] = $model->namespace;
				$result['model'] = $model->model;
				$result['sql_run'] = $sql_statements;
				if(!isset($result['location'])) $result['location'] = $sentence['parse_start'];
				//cbeads_nice_vardump($result);
				return $result;
			}
		}
		//echo "ALL DONE";
		//cbeads_nice_vardump($models);
		return array('success' => TRUE, 'sql_run' => $sql_statements);
	}
	
	private function check_reserved_words()
	{
		// cannot use reserved words for attribute names/properties.
		
		// for each sentence
		// for each attribute
		// if relation attribute, check name isn't reserved
		// if normal attribute, check name isn't reserved
		
		// return data success or failure (with appropriate error message).
	}
	
	// Get detailed message for this error.
	private function get_error_detail_message($error)
	{
		$msg = '';
		if($error == $this::ERR_UNKNOWN_SENTENCE_ACTION)
		{
			$msg = 'The action keyword was not recognised. Please check what actions are allowed.';
		}
		elseif($error == $this::ERR_REQUIRE_SENTENCE_ACTION)
		{
			$msg = 'The action keyword was not provided. Please provide an action for this sentence.';
		}
		elseif($error == $this::ERR_REQUIRE_MODEL_COMPONENT)
		{
			$msg = 'The model component was not provided when using a &lt;namespace&gt;::&lt;model&gt; entity reference. Please provide the model for this entity reference.';
		}
		elseif($error == $this::ERR_REQUIRE_ATTRIBUTE)
		{
			$msg = 'An attribute/entity was expected but not provided. Please provide an attribute/entity.';
		}
		elseif($error == $this::ERR_INVALID_OPTION_ARGUMENT)
		{
			$msg = 'The option argument is invalid. Please check what options are allowed.';
		}
		elseif($error == $this::ERR_INVALID_DATATYPE_DEFINITION)
		{
			$msg = 'The datatype definition is invalid. Please check what datatypes (and options) are allowed.';
		}
		elseif($error == $this::ERR_REQUIRE_OR_BETWEEN_ENUM_TOKENS)
		{
			$msg = 'Expecting and "OR" between tokens in an enumeration list';
		}
		elseif($error == $this::ERR_QUOTED_STRING_NOT_CLOSED)
		{
			$msg = 'Detected a unclosed string.';
		}
		elseif($error == $this::ERR_INVALID_CHARACTER)
		{
			$msg = 'Invalid character. Expecting comma or fullstop.';
		}
		elseif($error == $this::ERR_INVALID_IN_SENTENCE_START)
		{
			$msg = 'Must start the sentence using the form \'in &lt;namespace&gt;,&lt;model&gt; ...\'';
		}
		elseif($error == $this::ERR_REQUIRE_ATTRIBUTE_OPTIONS)
		{
			$msg = 'Attribute requires options to be set.';
		}
		elseif($error == $this::ERR_ACCESS_TO_MODEL_DISALLOWED)
		{
			$msg = 'Access to the model is disallowed.';
		}
		elseif($error == $this::ERR_INVALID_RENAME_SYNTAX)
		{
			$msg = 'To rename an attribute/relationship-alias use the form: <attr_name> to <new_attr_name>';
		}
		elseif($error == $this::ERR_MAPPING_ENTITY_INVALID)
		{
			$msg = 'The model name of the custom entity to use for mapping must end in "_map".';
		}
		elseif($error == $this::ERR_UNKNOWN)
		{
			$msg = 'An error has occurred which has not been documented (either unexpected or not yet properly defined).';
		}
		elseif($error == $this::ERR_EXPECTING_NAMESPACE_OR_MODEL)
		{
			$msg = 'Expecting a namespace or model for the sentence.';
		}
		return $msg;
	}
	
	// For certain errors, can suggest possible resolutions (ways to fix it).
	private function get_error_resolutions($error)
	{
		$resolutions = array();
		if($error == $this::ERR_UNKNOWN_SENTENCE_ACTION || $error == $this::ERR_REQUIRE_SENTENCE_ACTION)
		{
			$resolutions = array(
				'has','add','modify','remove'
			);
		}
		elseif($error == $this::ERR_INVALID_OPTION_ARGUMENT)
		{
			$resolutions = array(
				'is required', 'is optional', 'is unique', 'is not unique', 'as &lt;datatype&gt;'
			);
		}
		return $resolutions;
	}
}


/* 
	In memory model manager.
*/
class ModelManager
{
	public static $models = array();
	//public static $all_table_info = array();
	//public static $all_relationships = array();
	public static $declared_classes = array();
	public static $allowable_dbs = array();
	
	// Retrieves the requested in-memory model. If it doesn't exist, creates it.
	public static function get_model($namespace, $model)
	{
		$model = strtolower($model);
		$namespace = strtolower($namespace);
		if(isset(ModelManager::$models[$namespace][$model]))
			return ModelManager::$models[$namespace][$model];
		else
		{ 
			$obj = ModelManager::create_model($namespace, $model);
			ModelManager::$models[$namespace][$model] = $obj;
			return $obj;
		}
	}
	
	// Is there a model with the given namespace and model name. Check for actual database and table
	// since there is no guarantee that a model file exists.
	public static function is_a_model($namespace, $model)
	{
		//return isset(DBManager::$all_table_info[strtolower($namespace)][strtolower($model)]);
		return DBManager::does_table_exist($model, $namespace);
	}
	
	public static function create_model($namespace, $model)
	{
		$obj = new InMemoryModel($namespace, $model);
		return $obj;
	}
	
	public static function is_access_allowed($namespace)
	{
		foreach(ModelManager::$allowable_dbs as $db)
		{
			if(strtolower($db) == strtolower($namespace))
				return TRUE;
		}
		return FALSE;
	}
	
	public function get_all_models()
	{
		$models = array();
		foreach(ModelManager::$models as $ns_models)
		{
			foreach($ns_models as $model)
			{
				$models[] = $model;
			}
		}
		return $models;
	}

}


/*
	Represents a model that is being worked on. Changes to attributes and relationships are queued
	to be processed later on.
	If any changes have occurred and passed validation then the model will write itself to file.
*/
class InMemoryModel
{
	public $namespace;
	public $model;
	
	private $exists;		// Indicates if this model already exists or not.
	public  $status;		// Status of the model once actions have been applied: 'created', 'updated', 'removed'
	private $update_file;	// Indicates if the model file will need to be updated.
	
	private $attr_to_add = array();
	private $attr_to_delete = array();
	private $attr_to_modify = array();
	
	private $rel_to_add = array();
	private $rel_to_delete = array();
	
	private $cur_attrs = array();		// Current attributes of this model
	private $cur_rels = array();		// Current relations of this model	
	
	private $action_recorded = FALSE;	// Was an action already recorded? Will only allow one.
	

	CONST ERR_UNKNOWN = 1;						// A generic error.
	
	CONST ERR_ALREADY_APPLIED_ACTION = 100;		// Attempted to apply a second action to a model.
	CONST ERR_UNKNOWN_ACTION = 101;				// 'in <namespace>,<model> <action>'  action is unknown.
	
	CONST ERR_MODEL_ALREADY_EXISTS = 200;		// 'in <namespace>,<model> has '  used 'has' on model that already exists.
	CONST ERR_MODEL_DOES_NOT_EXIST = 201;		// 'in <namespace>,<model> add '  used 'add' on model that does not exist.
	// Foreign key /relationship related errors
	CONST ERR_FK_ALREADY_EXISTS_WITH_INVALID_TYPE = 300;	// A field already exists and has a type that cannot be used as a foreign key. Ie, not an integer.
	CONST ERR_FK_ALREADY_ADDED_WITH_INVALID_TYPE = 301;		// A field with the same name as the fk was already put on the to add list but has a data type that cannot be used as a foreign key. Ie, not an integer.
	CONST ERR_REL_FOR_REMOVAL_DOES_NOT_EXIST = 302;			// 'remove <entity>'  the specified entity does not exist in any relationships that the model has.
	CONST ERR_REL_ALREADY_EXISTS = 303;						// Relation to add already exists.
	CONST ERR_REL_ACCESS_DISALLOWED = 304;					// Access to the referenced model was disallowed.
	// Attribute related errors
	CONST ERR_ATTRIBUTE_ALREADY_EXISTS = 400;				// 'add <attr>'   attr already exists in the model
	CONST ERR_ATTRIBUTE_DUPLICATE_DECLARATION = 401;		// 'has|add <attr> <attr> .. '  an attribute appears more than once in the list.
	CONST ERR_ATTRIBUTE_DOES_NOT_EXIST = 402;				// 'remove <attr>'  the attribute does not exist.
	CONST ERR_ATTRIBUTE_OR_ALIAS_DOES_NOT_EXIST = 403;		// 'rename <attr> to <attr>'  the attribute or relationship alias does not exist.
	// SQL related errors
	CONST ERR_SQL_ERROR = 500;					// Error executing an sql statement.
	
	// Creates an in memory model instance for a given namespace and model.
	// If the model exists, will get current attributes and relationships.
	public function InMemoryModel($namespace, $model)
	{
		$this->namespace = $namespace;
		$this->model = $model;
		
		if(!$this->does_model_exist())
		{
			$this->exists = FALSE;
			$this->status = "created";
		}
		else
		{
			$this->exists = TRUE;
			if(!$this->get_existing_fields())
			{
				// TODO: throw error?
			}
			if(!$this->get_existing_relationships())
			{
				// TODO: throw error?
			}
			//cbeads_nice_vardump($this);
		}
	}
	
	// Apply an action to the model.
	// action_type: create | update | delete | etc..
	// properties: depends on the action type. Contains data necessary to apply the action.
	public function apply_action($action_type, $properties)
	{
		if($this->action_recorded)
		{
			//return array('result' => FALSE, 'msg' => 'Can only apply one action to a model at a time.', 'model' => $this->model, 'namespace' => $this->namespace);
			return array('success' => FALSE, 'error' => $this::ERR_ALREADY_APPLIED_ACTION);
		}
		
		$result = array();
		if($action_type == 'create')
		{
			$result = $this->apply_create_action($properties);
		}
		elseif($action_type == 'add')
		{
			$result = $this->apply_add_action($properties);
		}
		elseif($action_type == 'remove')
		{
			$result = $this->apply_remove_action($properties);
		}
		elseif($action_type == 'modify')
		{
			$result = $this->apply_modify_action($properties);
		}
		elseif($action_type == 'rename')
		{
			$result = $this->apply_rename_action($properties);
		}
		elseif($action_type == 'delete_model')
		{
			$result = $this->apply_remove_model_action();
		}
		// Need model remove action check and maybe a model modify (renaming of the model) check.
		else
		{
			// return array('result' => FALSE, 'msg' => "Action '$action_type' is unknown.");
			return array('success' => FALSE, 'error' => $this::ERR_UNKNOWN_ACTION);
		}
		//cbeads_nice_vardump($this);
		$this->action_recorded = TRUE;
		return $result;
	}
	
	private function apply_create_action($properties)
	{
		// Can only create if model does not exist.
		if(!$this->exists)
		{
			//cbeads_nice_vardump($properties);
			foreach($properties as $attr)
			{
				$location = $attr['location'];
				// Is the attribute name a model in this namespace?
				$is_relation = FALSE;
				if($attr['attr_type'] == 'unknown' && ModelManager::is_a_model($this->namespace, $attr['attr_name']))
				{
					//echo $attr['attr_name'] . " is a model";
					//ModelManager::get_model($this->namespace, $attr['attr_name']);
					$is_relation = TRUE;
					$attr['namespace'] = $this->namespace;
					$attr['table'] = $attr['attr_name'];
					$attr['attr_type'] = 'many-to-one';
				}
				elseif($attr['attr_type'] == 'unknown' || $attr['attr_type'] == 'normal')
				{
					// Need to work out what data type to use for this attribute.
					if($attr['attr_name'] == 'id')		// Special case for ID fields.
					{
						$attr['type'] = 'integer';
						$attr['ntype'] = 'integer';
						$attr['length'] = 4;
						$attr['decimal'] = 0;
						$attr['primary'] = TRUE;
						$attr['autoincrement'] = TRUE;
						$attr['notnull'] = TRUE;
					}
					else
					{
						$specified_datatype = FALSE;
						if(isset($attr['options']))
						{
							foreach($attr['options'] as $option)
							{
								if($option['option_type'] == 'data_type')
								{
									$specified_datatype = TRUE;
									break;
								}
							}
						}
						if(!$specified_datatype)
						{
							$def = $this->get_type_from_attribute_definition($attr['attr_name']);
							$def['attr_name'] = $attr['attr_name'];
							$attr = $def;
						}
					}
				}
				
				if($attr['attr_type'] == 'many-to-many' || $attr['attr_type'] == 'many-to-one' || $attr['attr_type'] == 'one-to-many')
				{
					if(ModelManager::is_access_allowed($attr['namespace']))
						$result = $this->add_relationship($attr);
					else
						$result = array('success' => FALSE, 'error' => $this::ERR_REL_ACCESS_DISALLOWED, 'error_token' => $attr['namespace'] .'::'. $attr['model'], 'location' => $location);
				}
				else
				{
					$to_add = $attr;
					unset($to_add['options'], $to_add['attr_name'], $to_add['attr_type']);
					$to_add['name'] = $attr['attr_name'];

					if(isset($attr['options']))
					{
						foreach($attr['options'] as $option)
						{
							if($option['option_type'] == 'required')
								$to_add['notnull']  = $option['value'];
							elseif($option['option_type'] == 'unique')
								$to_add['unique'] = $option['value'];
							elseif($option['option_type'] == 'data_type')
							{
								$to_add['ntype'] = $option['data_type'];
								$to_add['length'] = $option['length'];
								$to_add['decimal'] = $option['decimal'];
								if(isset($option['values']))	// For enumerations.
									$to_add['values'] = $option['values'];
							}
						}
					}
					$this->attr_to_add[$attr['attr_name']] = $to_add;
					$result['success'] = true;
				}
				if($result['success'] == FALSE)
				{
					//echo "failed to apply attribute ".$attr['attr_name']; 
					$result['location'] = $location;
					return $result;
				}
			}
			$result = array('success' => TRUE);
			$this->status = "created";
		}
		else
		{
			// $result = array('success' => FALSE, 'msg' => 'Model already exists. Please first delete it or use "add", "remove", "modify" to alter the existing model.');
			$result = array('success' => FALSE, 'error' => $this::ERR_MODEL_ALREADY_EXISTS);
		}
		
		return $result;
	}
	
	private function apply_add_action($properties)
	{
		// Can only update if model already exists.
		if($this->exists)
		{
			//cbeads_nice_vardump($properties);
			foreach($properties as $attr)
			{
				$location = $attr['location'];
				// Is the attribute name a model in this namespace?
				$is_relation = FALSE;
				if($attr['attr_type'] == 'unknown' && ModelManager::is_a_model($this->namespace, $attr['attr_name']))
				{
					//echo $attr['attr_name'] . " is a model";
					//ModelManager::get_model($this->namespace, $attr['attr_name']);
					$is_relation = TRUE;
					$attr['namespace'] = $this->namespace;
					$attr['model'] = $attr['attr_name'];
					$attr['attr_type'] = 'many-to-one';
				}
				elseif($attr['attr_type'] == 'unknown' || $attr['attr_type'] == 'normal')
				{
					// Need to work out what data type to use for this attribute.
					if($attr['attr_name'] == 'id')		// Special case for ID fields.
					{
						$attr['type'] = 'integer';
						$attr['ntype'] = 'integer';
						$attr['length'] = 4;
						$attr['decimal'] = 0;
						$attr['primary'] = TRUE;
						$attr['autoincrement'] = TRUE;
						$attr['notnull'] = TRUE;
					}
					else
					{
						$specified_datatype = FALSE;
						if(isset($attr['options']))
						{
							foreach($attr['options'] as $option)
							{
								if($option['option_type'] == 'data_type')
								{
									$specified_datatype = TRUE;
									break;
								}
							}
						}
						if(!$specified_datatype)
						{
							$def = $this->get_type_from_attribute_definition($attr['attr_name']);
							$def['attr_name'] = $attr['attr_name'];
							$attr = $def;
						}
					}
				}
				
				if($attr['attr_type'] == 'many-to-many' || $attr['attr_type'] == 'many-to-one' || $attr['attr_type'] == 'one-to-many')
				{
					if(ModelManager::is_access_allowed($attr['namespace']))
						$result = $this->add_relationship($attr);
					else
						$result = array('success' => FALSE, 'error' => $this::ERR_REL_ACCESS_DISALLOWED, 'error_token' => $attr['namespace'] .'::'. $attr['model'], 'location' => $location);
				}
				else
				{
					// Check if attribute already exists or was already put into the to add list.
					if(isset($this->cur_attrs[$attr['attr_name']]))
						return array('success' => FALSE, 'error' => $this::ERR_ATTRIBUTE_ALREADY_EXISTS, 'error_token' => $attr['attr_name'], 'location' => $location);
					elseif(isset($this->attr_to_add[$attr['attr_name']]))
						return array('success' => FALSE, 'error' => $this::ERR_ATTRIBUTE_DUPLICATE_DECLARATION, 'error_token' => $attr['attr_name'], 'location' => $location);
					
					$to_add = $attr;
					unset($to_add['options'], $to_add['attr_name'], $to_add['attr_type']);
					$to_add['name'] = $attr['attr_name'];

					if(isset($attr['options']))
					{
						foreach($attr['options'] as $option)
						{
							if($option['option_type'] == 'required')
								$to_add['notnull']  = $option['value'];
							elseif($option['option_type'] == 'unique')
								$to_add['unique'] = $option['value'];
							elseif($option['option_type'] == 'data_type')
							{
								$to_add['ntype'] = $option['data_type'];
								$to_add['length'] = $option['length'];
								$to_add['decimal'] = $option['decimal'];
								if(isset($option['values']))	// For enumerations.
									$to_add['values'] = $option['values'];
							}
						}
					}
					$this->attr_to_add[$attr['attr_name']] = $to_add;
					$result['success'] = true;
				}
				if($result['success'] == FALSE)
					return $result;
			}
			$result = array('success' => TRUE);
			$this->status = "updated";
		}
		else
		{
			$result = array('success' => FALSE, 'error' => $this::ERR_MODEL_DOES_NOT_EXIST);
		}
		
		return $result;
	}
	
	private function apply_remove_action($properties)
	{
		if($this->exists)
		{
			foreach($properties as $attr)
			{
				// Is the attribute name a model in this namespace or an alias for a relationship?
				$is_relation = FALSE;
				if($attr['type'] == 'unknown')
				{
					//$res = $this->is_model_or_relationship_alias($attr['name']);
					$res = $this->get_relationship_by_alias($attr['name']);
					//cbeads_nice_vardump($res);
					if($res['found'])
					{
						$attr['type'] = 'entity';
						//$attr['model'] = $res['model'];
						//$attr['namespace'] = $res['namespace'];
						//$attr['alias'] = $res['alias'];
						$attr['rel'] = $res['rel'];
					}
				}
				if($attr['type'] == 'entity')
				{
					// $found = FALSE;
					// foreach($this->cur_rels as $rel)
					// {
						// $rel['model'] = $rel['table'];
						//cbeads_nice_vardump($rel);
						// if($rel['namespace'] == $attr['namespace'] && $rel['model'] == $attr['model'] && $rel['alias'] == $attr['alias'])
						// {
							// $found = TRUE;
							// unset($rel['table']);
							// //cbeads_nice_vardump($rel);
							// $result = $this->remove_relationship($rel);
							// if($result['success'] == FALSE) 
							// {
								// $result['location'] = $attr['location'];
								// return $result;
							// }
						// }
					// }
					// if(!$found)
					// {
						// return array('success' => FALSE, 'error' => $this::ERR_REL_FOR_REMOVAL_DOES_NOT_EXIST, 'error_token' => $attr['namespace'] . '::' . $attr['model'], 'location' => $attr['location']);
					// }
					$rel = $attr['rel'];
					$rel['model'] = $rel['table'];
					unset($rel['table']);
					$result = $this->remove_relationship($rel);
					if($result['success'] == FALSE) 
					{
						$result['location'] = $attr['location'];
						return $result;
					}
					//$this->rel_to_delete[] = $attr;
				}
				else
				{
					// Check if attribute exists and can be deleted. For example should not be able
					// to remove foreign key fields if they are in use (TODO).
					if(!isset($this->cur_attrs[$attr['name']]))
						return array('success' => FALSE, 'error' => $this::ERR_ATTRIBUTE_DOES_NOT_EXIST, 'error_token' => $attr['name'], 'location' => $attr['location']);
					$this->attr_to_delete[] = $attr['name'];
					//$this->attr_to_delete[$attr['name']] = $attr;
				}
			}
			$result = array('success' => TRUE);
			$this->status = "updated";
		}
		else
		{
			$result = array('success' => FALSE, 'error' => $this::ERR_MODEL_DOES_NOT_EXIST);
		}

		return $result;
	}
	
	private function apply_remove_model_action()
	{
		if($this->exists)
		{
			$result = array('success' => TRUE);
			$this->status = "removed";
			// For any relationships, notify the related model that the relationship is being
			// removed.
			foreach($this->cur_rels as $rel)
			{
				$relmodel = ModelManager::get_model($rel['namespace'], $rel['table']);
				$opts = array();
				if($rel['type'] == 'one-to-many')
				{
					$opts['namespace'] = $this->namespace;
					$opts['model'] = $this->model;
					$opts['local'] = $rel['foreign'];
					$opts['foreign'] = $rel['local'];
					$opts['fk_constraint_name'] = $rel['fk_constraint_name'];
					$opts['type'] = 'many_to_one';
					$result = $relmodel->notify_of_relationship('remove', 'many-to-one', $opts);
					if($result['success'] == FALSE) return $result;
					if($relmodel->status != "created") $relmodel->status = "updated";
				}
				elseif($rel['type'] == 'many-to-one')
				{
					$opts['namespace'] = $this->namespace;
					$opts['model'] = $this->model;
					$opts['local'] = $rel['foreign'];
					$opts['foreign'] = $rel['local'];
					$opts['fk_constraint_name'] = $rel['fk_constraint_name'];
					$opts['type'] = 'one-to-many';
					$result = $relmodel->notify_of_relationship('remove', 'one-to-many', $opts);
					if($result['success'] == FALSE) return $result;
					if($relmodel->status != "created") $relmodel->status = "updated";
				}
				elseif($rel['type'] == 'many-to-many')
				{
					$opts['namespace'] = $this->namespace;
					$opts['model'] = $this->model;
					$opts['local'] = $rel['foreign'];
					$opts['foreign'] = $rel['local'];
					$opts['type'] = 'many-to-many';
					$result = $relmodel->notify_of_relationship('remove', 'many-to-many', $opts);
					if($result['success'] == FALSE) return $result;
					if($relmodel->status != "created") $relmodel->status = "updated";
					$mapper = ModelManager::get_model($rel['mapping_namespace'], $rel['mapping_table']);
					$mapper->notify_of_relationship('remove', 'mapping', $opts);
				}
			}
			//cbeads_nice_vardump(ModelManager::get_all_models());
			//exit();
		}
		else
		{
			$result = array('success' => FALSE, 'error' => $this::ERR_MODEL_DOES_NOT_EXIST);
		}
		return $result;
	}
	
	private function apply_modify_action($properties)
	{
		if($this->exists)
		{
			foreach($properties as $attr)
			{
				// Check that this attribute exists.
				$name = strtolower($attr['attr_name']);
				if(!isset($this->cur_attrs[$name]))
				{
					return array('success'=> FALSE, 'error' => $this::ERR_ATTRIBUTE_DOES_NOT_EXIST, 'error_token' =>  $attr['attr_name'], 'location' => $attr['location']);
				}
				
				// Apply current option values for this attribute. Needed to generate a full column
				// definition.
				$matched = $this->cur_attrs[$name];
				$to_modify = array_merge($matched, $attr);
				//echo "merged stuff:";
				//cbeads_nice_vardump($to_modify);
				unset($to_modify['options'], $to_modify['attr_name'], $to_modify['attr_type']);
				$to_modify['name'] = $attr['attr_name'];

				foreach($attr['options'] as $option)
				{
					if($option['option_type'] == 'required')
						$to_modify['notnull']  = $option['value'];
					elseif($option['option_type'] == 'unique')
						$to_modify['unique'] = $option['value'];
					elseif($option['option_type'] == 'data_type')
					{
						$to_modify['ntype'] = $option['data_type'];
						$to_modify['length'] = $option['length'];
						$to_modify['decimal'] = $option['decimal'];
						if(isset($option['values']))	// For enumerations.
							$to_modify['values'] = $option['values'];
					}
				}
				// This step gets the data type name without any options. Necessary when modifying
				// attribute without specifying the data type. In that case the existing data type
				// is used. However the system only wants the type with no options.
				if(preg_match('/^\w+/', $to_modify['ntype'], $matches))
					$to_modify['ntype'] = $matches[0];
				$this->attr_to_modify[$attr['attr_name']] = $to_modify;
			}
			$result = array('success' => TRUE);
			$this->status = "updated";
		}
		else
		{
			$result = array('success' => FALSE, 'error' => $this::ERR_MODEL_DOES_NOT_EXIST);
		}

		return $result;
	}
	
	private function apply_rename_action($properties)
	{
		if($this->exists)
		{
			foreach($properties as $attr)
			{
				// Check if there is such a attribute or relationship-alias.
				$name = strtolower($attr['attr_name']);
				$found = isset($this->cur_attrs[$name]);
				$is_rel = FALSE;
				$relationship = NULL;
				if(!$found)
				{
					foreach($this->cur_rels as $rel)
					{
						if(strtolower($rel['alias']) == $name)
						{
							$found = $is_rel = TRUE;
							$relationship = $rel;
							break;
						}
					}
				}
				if(!$found)
				{
					return array('success'=> FALSE, 'error' => $this::ERR_ATTRIBUTE_OR_ALIAS_DOES_NOT_EXIST, 'error_token' =>  $attr['attr_name'], 'location' => $attr['location']);
				}
				
				if($is_rel)
				{
					// Get the model that contains the fk and update the comment with the new alias.
					$model = $this;
					$fk = $relationship['local'];
					//cbeads_nice_vardump($relationship);
					if($relationship['type'] == 'one-to-many')
					{
						$model = ModelManager::get_model($relationship['namespace'], $relationship['table']);
						$fk = $relationship['foreign'];
					}
					elseif($relationship['type'] == 'many-to-many')
					{
						$model = ModelManager::get_model($relationship['mapping_namespace'], $relationship['mapping_table']);
						$fk = $relationship['foreign'];
						$model->status = 'updated';
					}
					$relationship['alias'] = $attr['rename_to'];
					
					$to_modify = $model->cur_attrs[$fk];
					$comment = json_decode($to_modify['comment'], TRUE);
					if(!$comment) $comment = array();
					if($relationship['type'] == 'many-to-one')
						$comment['alias1'] = $attr['rename_to'];
					elseif($relationship['type'] == 'one-to-many')
						$comment['alias2'] = $attr['rename_to'];
					elseif($relationship['type'] == 'many-to-many')
						$comment['alias'] = $attr['rename_to'];
					$to_modify['comment'] = json_encode($comment);
					// This step gets the data type name without any options. Necessary when modifying
					// attribute without specifying the data type. In that case the existing data type
					// is used. However the system only wants the type with no options.
					if(preg_match('/^\w+/', $to_modify['ntype'], $matches))
						$to_modify['ntype'] = $matches[0];
					$model->attr_to_modify[$fk] = $to_modify;
					// Update the alias in the relationship info for this model.
					for($i = 0; $i < count($this->cur_rels); $i++)
					{
						if(strtolower($this->cur_rels[$i]['alias']) == $name)
						{
							$this->cur_rels[$i] = $relationship;
							break;
						}
					}
					// Must update the model file when an alias was renamed.
					$this->update_file = TRUE;
				}
				else
				{
					// Apply current option values for this attribute. Needed to generate a full column
					// definition.
					$to_modify = array_merge($this->cur_attrs[$name], $attr);
					unset($to_modify['attr_name']);
					$to_modify['name'] = $attr['attr_name'];
					// This step gets the data type name without any options. Necessary when modifying
					// attribute without specifying the data type. In that case the existing data type
					// is used. However the system only wants the type with no options.
					if(preg_match('/^\w+/', $to_modify['ntype'], $matches))
						$to_modify['ntype'] = $matches[0];
					$this->attr_to_modify[$attr['attr_name']] = $to_modify;
				}
			}
			$result = array('success' => TRUE);
			$this->status = "updated";
		}
		else
		{
			$result = array('success' => FALSE, 'error' => $this::ERR_MODEL_DOES_NOT_EXIST);
		}

		return $result;
	}
	
	private function add_relationship($options)
	{
		// Check if the relationship already exists. Report error or maybe just a notice?
		// Because it doesn't really hurt to just ignore this relationship.
		foreach($this->cur_rels as $rels)
		{
			if($rels['type'] == $options['attr_type'] && $rels['namespace'] == $options['namespace'] && $rels['table'] == $options['model'])
			{
				return array('success' => FALSE, 'error' => $this::ERR_REL_ALREADY_EXISTS, 'location' => $options['location'], 'namespace' => $options['namespace'], 'model' => $options['model']);
			}
		}
		
		// Get a reference to the newly related model.
		$obj = ModelManager::get_model($options['namespace'], $options['model']);
		if($options['attr_type'] == 'many-to-one')
		{
			return $this->add_many_to_one_relationship($options, $obj);
		}
		elseif($options['attr_type'] == 'one-to-many')
		{
			return $this->add_one_to_many_relationship($options, $obj);
		}
		elseif($options['attr_type'] == 'many-to-many')
		{
			return $this->add_many_to_many_relationship($options, $obj);
		}
		return array('success' => TRUE);
	}
	
	private function add_many_to_one_relationship($options, $relmodel)
	{
		//Only for many-to-one relationships. Add a foreign key to attr_to_add if it doesn't already exist. If it does exist but the type is incorrect then error. The user has to change the type of the attribute first to integer. Call the related model's notify_of_relationship function so it knows about this new relationship.
		$fk = $this->_format_to_fk($options['model']);
		if(isset($this->attr_to_add[$fk]))   // this should go into a function.
		{
			$existing = $this->attr_to_add[$fk];
			if($this->attr_to_add[$fk]['ntype'] != 'integer')
			{
				//echo "field to use for fk was already added to the 'to add' list but has an invalid type!<Br/>";
				return array('success' => FALSE, 'error' => $this::ERR_FK_ALREADY_ADDED_WITH_INVALID_TYPE, 'error_token' => $fk);
			}
		}
		elseif(isset($this->cur_attrs[$fk]))
		{
			if($this->cur_attrs[$fk]['ntype'] != 'int(11)')
			{
				//echo "fk field exists but has invalid type!<br/>";
				return array('success' => FALSE, 'error' => $this::ERR_FK_ALREADY_EXISTS_WITH_INVALID_TYPE, 'error_token' => $fk);
			}
		}
		
		// set the 'foreign' and 'local' fields needed by Doctrine when setting up relationships.
		$options['foreign'] = 'id';
		$options['local'] = $fk;
		// Work out aliases. Just pluralise the model name if no alias was given.
		$alias1 = isset($options['alias1']) ? $options['alias1'] : $this->pluralise(ucfirst($relmodel->model));
		$alias2 = isset($options['alias2']) ? $options['alias2'] : $this->pluralise(ucfirst($this->model));
		if($relmodel != NULL)
		{
			$opts = $options;
			$opts['namespace'] = $this->namespace;
			$opts['model'] = $this->model;
			$tmp = $opts['local'];
			$opts['local'] = $opts['foreign'];
			$opts['foreign'] = $tmp;
			$opts['alias1'] = $alias2;
			$opts['alias2'] = $alias1;
			$result = $relmodel->notify_of_relationship('add', 'one-to-many', $opts);
			if($result['success'] == FALSE) return $result;
			if($relmodel->status != "created") $relmodel->status = "updated";
		}
		// If custom aliases are used, will need to store them as a comment in the foreign key field
		$aliases = array(); $comment = FALSE;
		if(isset($options['alias1'])) $aliases['alias1'] = $options['alias1'];
		if(isset($options['alias2'])) $aliases['alias2'] = $options['alias2'];
		if(count($aliases) > 0) $comment = json_encode($aliases);
		$this->attr_to_add[$fk] = array('name' => $fk, 'ntype' => 'integer', 'type' => 'integer', 'length' => 4, 'decimal' => 0, 'comment' => $comment);
		
		$this->rel_to_add[] = array('namespace' => $options['namespace'], 'table' => $options['model'], 'update' => NULL, 'delete' => NULL, 'type' => 'many-to-one', 'alias' => $alias1, 'local' => $options['local'], 'foreign' => $options['foreign']);
		return array('success' => TRUE);
	}
	
	private function add_one_to_many_relationship($options, $relmodel)
	{
		//Only for one-to-many relationships. Call the related model's notify_of_relationship function which will add the foreign key to its list as well as record the opposite of the relationship (ie many-to-one).

		// set the 'foreign' and 'local' fields needed by Doctrine when setting up relationships.
		$options['foreign'] = $this->_format_to_fk($this->model);
		$options['local'] = 'id';
		// Work out aliases. Just pluralise the model name if no alias was given.
		$alias1 = isset($options['alias1']) ? $options['alias1'] : $this->pluralise(ucfirst($relmodel->model));
		$alias2 = isset($options['alias2']) ? $options['alias2'] : $this->pluralise(ucfirst($this->model));

		if($relmodel != NULL) 
		{
			$opts = $options;
			$opts['namespace'] = $this->namespace;
			$opts['model'] = $this->model;
			$tmp = $opts['local'];
			$opts['local'] = $opts['foreign'];
			$opts['foreign'] = $tmp;
			$opts['alias1'] = $alias2;
			$opts['alias2'] = $alias1;
			$result = $relmodel->notify_of_relationship('add', 'many-to-one', $opts);
			if($result['success'] == FALSE) return $result;
			if($relmodel->status != "created") $relmodel->status = "updated";
		}
		$this->rel_to_add[] = array('namespace' => $options['namespace'], 'table' => $options['model'], 'update' => NULL, 'delete' => NULL, 'type' => 'one-to-many', 'alias' => $alias1, 'local' => $options['local'], 'foreign' => $options['foreign']);
		
		return array('success' => TRUE);
	}
	
	private function add_many_to_many_relationship($options, $relmodel)
	{
		//If so, call the related model's notify_of_relationship function (ie many-to-many) which will tell it about this relationship. Need to create a new model that will contain the mapping table and call the notify_of_relationship function on it. This is a special case where the relationship type is 'mapping' telling that model it is acting as a mapping table. What the model will do is to add the necessary fields and record the mapping relationship in rel_to_add. This is needed for the model to create the relationships at the database level later on. The model file created will not know about the mapping relationship (although a comment added about it in the setUp() function).
		
		// Generate the name and namespace for the mapping table if it doesnt exist.
		if($options['mapping_table'] == '') $options['mapping_table'] = $this->_format_to_mapping_table($this->model, $options['model']);
		if($options['mapping_namespace'] == '') $options['mapping_namespace'] = $this->namespace;
		
		// Generate the local and foreign field names if they don't exist.
		//if(!isset($options['local'])) $options['local'] = $this->_format_to_fk($this->model);
		//if(!isset($options['foreign'])) $options['foreign'] = $this->_format_to_fk($options['model']);
		//if($options['local'] == $options['foreign'])
		//{
			$options['local'] = strtolower($this->namespace) . '_' . $this->_format_to_fk($this->model);
			$options['foreign'] = strtolower($options['namespace']) . '_' . $this->_format_to_fk($options['model']);
		//}
		// TODO: What if the model names are still the same? Happens with self referential m-m mapping.
		// Eg: person has name, age, many person and vice versa as Friends.

		// Work out aliases. Just pluralise the model name if no alias was given.
		$alias1 = isset($options['alias1']) ? $options['alias1'] : $this->pluralise(ucfirst($relmodel->model));
		$alias2 = isset($options['alias2']) ? $options['alias2'] : $this->pluralise(ucfirst($this->model));
		//echo "Mapping options: "; cbeads_nice_vardump($options);
		
		if($relmodel != NULL)
		{
			// Tell the related model about this relationship.
			$opts = $options;
			$opts['namespace'] = $this->namespace;
			$opts['model'] = $this->model;
			$tmp = $opts['local'];
			$opts['local'] = $opts['foreign'];
			$opts['foreign'] = $tmp;
			$tmp = $alias1;
			$opts['alias1'] = $alias2;
			$opts['alias2'] = $tmp;
			$result = $relmodel->notify_of_relationship('add', 'many-to-many', $opts);
			if($result['success'] == FALSE) return $result;
			// Get/Generate the model for the mapping table and tell it about the relationship.
			$mapper = ModelManager::get_model($options['mapping_namespace'], $options['mapping_table']);
			if($mapper->status != "created") $mapper->status = "updated";
			$map_opts = array('namespace1' => $this->namespace, 'model1' => $this->model, 'fk1' => $options['local'], 'namespace2' => $options['namespace'], 'model2' => $options['model'], 'fk2' => $options['foreign'], 'alias1' => $alias1, 'alias2' => $alias2);
			$mapper->notify_of_relationship('add', 'mapping', $map_opts);
		}
		
		$options['alias'] = $alias1;
		$options['type'] = $options['attr_type'];
		$options['table'] = $options['model'];
		unset($options['attr_type'], $options['model'], $options['alias1'], $options['alias2']);
		$this->rel_to_add[] = $options;
		if($this->status != "created") $this->status = "updated";
		
		return array('success' => TRUE);
	}
	
	private function add_mapping_relationship($options)
	{
		// TODO: Check if fields for the foreign keys already exist or are pending. The data type must be integer. Otherwise an error must be reported.
		// Need to add two foreign keys pointing to the two tables that will be mapped together.
		$this->attr_to_add[$options['fk1']] = array('name' => $options['fk1'], 'ntype' => 'integer', 'length' => 4, 'decimal' => 0, 'index' => TRUE, 'comment' => json_encode(array('alias' => $options['alias2'])));
		$this->attr_to_add[$options['fk2']] = array('name' => $options['fk2'], 'ntype' => 'integer', 'length' => 4, 'decimal' => 0, 'index' => TRUE, 'comment' => json_encode(array('alias' => $options['alias1'])));
		// Record a 'mapping' relationship that will be used later to set up the foreign keys at the database level.
		$this->rel_to_add[] = array_merge(array('type' => 'mapping'),$options);
		return array('success'=> TRUE);
	}
	
	private function remove_relationship($options)
	{
		// Get a reference to the related model.
		$obj = ModelManager::get_model($options['namespace'], $options['model']);
		if($options['type'] == 'many-to-one')
		{
			return $this->remove_many_to_one_relationship($options, $obj);
		}
		elseif($options['type'] == 'one-to-many')
		{
			return $this->remove_one_to_many_relationship($options, $obj);
		}
		elseif($options['type'] == 'many-to-many')
		{
			return $this->remove_many_to_many_relationship($options, $obj);
		}
		return array('success' => TRUE);
	}
	
	private function remove_many_to_one_relationship($options, $relmodel)
	{
		// Add the fk to the attribute delete list and notify the related model so it can delete
		// the relationship on its end.
		$this->attr_to_delete[] = $options['local'];
		if($relmodel != NULL)
		{
			$opts = $options;
			$opts['namespace'] = $this->namespace;
			$opts['model'] = $this->model;
			$tmp = $opts['local'];
			$opts['local'] = $opts['foreign'];
			$opts['foreign'] = $tmp;
			$opts['type'] = 'one-to-many';
			$result = $relmodel->notify_of_relationship('remove', 'one-to-many', $opts);
			if($result['success'] == FALSE) return $result;
			if($relmodel->status != "created") $relmodel->status = "updated";
		}
		$this->rel_to_delete[] = $options;
		return array('success' => TRUE);
	}
	
	private function remove_one_to_many_relationship($options, $relmodel)
	{
		// Just record the relationship for removal so the model file will be updated.
		// Notify the related model so it can remove its foreign key.
		$this->rel_to_delete[] = $options;
		if($relmodel != NULL) 
		{
			$opts = $options;
			$opts['namespace'] = $this->namespace;
			$opts['model'] = $this->model;
			$tmp = $opts['local'];
			$opts['local'] = $opts['foreign'];
			$opts['foreign'] = $tmp;
			$opts['type'] = 'many-to-one';
			$result = $relmodel->notify_of_relationship('remove', 'many-to-one', $opts);
			if($result['success'] == FALSE) return $result;
			if($relmodel->status != "created") $relmodel->status = "updated";
		}
		return array('success' => TRUE);
	}
	
	private function remove_many_to_many_relationship($options, $relmodel)
	{
		// Notify the related object and mapping object of the removal of the relationship.
		if($relmodel != NULL)
		{
			$opts = $options;
			$opts['namespace'] = $this->namespace;
			$opts['model'] = $this->model;
			$tmp = $opts['local'];
			$opts['local'] = $opts['foreign'];
			$opts['foreign'] = $tmp;
			$result = $relmodel->notify_of_relationship('remove', 'many-to-many', $opts);
			if($result['success'] == FALSE) return $result;
			$mapper = ModelManager::get_model($options['mapping_namespace'], $options['mapping_table']);
			$mapper->notify_of_relationship('remove', 'mapping', $opts);
		}
		$this->rel_to_delete[] = $options;
		$this->status = 'updated';
		//echo "REM M-M"; exit();
		return array('success' => TRUE);
	}
	
	private function remove_mapping_relationship($options)
	{
		// The mapping table is to be removed.
		// NOTE: This assumes this is a mapping table between just two models. What if more are 
		// involved?
		$this->status = "removed";
		// Force the removal of the foreign keys which is needed when deleting a model involved
		// in a m-m relationship. The mapping table may be removed after the model is being 
		// deleted, which causes a constraint error. Deleting the foreign keys first from the
		// mapping table solves this problem (although at the cost of more queries).
		$this->attr_to_delete[] = $options['local'];
		$this->attr_to_delete[] = $options['foreign'];
		return array('success' => TRUE);
	}
	
	// Actually, can't this function just be deleted and the corresponding *_relationship 
	// functions called directly?
	// Used to notify this model of a change in relationships to other models.
	// action: 'add' or 'remove'
	// rel_type: 'one-to-many', 'many-to-many', 'many-to-one', 'mapping'
	public function notify_of_relationship($action, $rel_type, $options)
	{
		if($rel_type == 'one-to-many')
		{
			if($action == 'add')
				$result = $this->add_one_to_many_relationship($options, NULL);
			else
				$result = $this->remove_one_to_many_relationship($options, NULL);
		}
		elseif($rel_type == 'many-to-one')
		{
			if($action == 'add')
				$result = $this->add_many_to_one_relationship($options, NULL);
			else
				$result = $this->remove_many_to_one_relationship($options, NULL);
		}
		elseif($rel_type == 'many-to-many')
		{
			if($action == 'add')
				$result = $this->add_many_to_many_relationship($options, NULL);
			else
				$result = $this->remove_many_to_many_relationship($options, NULL);
		}
		else
		{
			// echo 'Inform of mapping relationship.';
			// cbeads_nice_vardump($options);
			if($action == 'add')
				$result = $this->add_mapping_relationship($options);
			else
				$result = $this->remove_mapping_relationship($options);
		}
		return $result;
	}
	
	// Validates the model. Need to find any issues here before going to the commit stage
	// where errors could result in databases and models being in an inconsistant state.
	// Returns boolean true if all is OK. Otherwise return false and an array of errors found.
	public function validate()
	{
		$errors = array();
		// Must have an ID field once all operations are applied. If not, add it.
		if(!isset($this->attr_to_add['id']) && !isset($this->cur_attrs['id']))
		{
			$this->attr_to_add['id'] = array('name' => 'id', 'type' => 'integer', 'ntype' => 'integer', 'length' => 4, 'primary' => TRUE, 'notnull' => TRUE, 'autoincrement' => TRUE);
		}
		elseif(isset($this->attr_to_add['id']))
		{
			if($this->attr_to_add['id']['ntype'] != 'integer')	// In case use did has id(as not_integer)..
			{
				$success = FALSE;
				$errors[] = "Field 'id' must be an integer";
			}
		}
		
		// TODO: If this model is to be deleted, check that it isn't being referenced by any other model.
		
		
		// What else should be tested for?
		
		
		if(count($errors) > 0)
			return array('success' => FALSE, 'errors' => $errors);
		else
			return array('success' => TRUE);
	}
	
	// Applies changes to the model's table. Excludes changes to relationships which come in stage2,
	// except when dropping tables! That can be done all at once. Another exception is when dropping
	// relationships. Need to remove the relationship before dropping the foreign key.
	public function commit_stage1()
	{
		$to_delete = array();
		$to_modify = array();
		$to_add = array();
		$result = array('success' => TRUE);
		
		// If the model is new, will need to create the table. Otherwise it can be altered.
		if($this->exists)
		{
			// Is an update required or a delete?
			if($this->status == "updated")
			{
				// Get removes
				foreach($this->attr_to_delete as $attr)
				{
					$del = array('_type' => 'attr', 'name' => $attr);
					// Check if the attribute is actually a foreign key in a relationship that is 
					// being dropped. Must handle such attributes as relationships being removed.
					foreach($this->rel_to_delete as $rel)
					{
						if($rel['local'] == $attr)
						{
							$del = array('_type' => 'rel', 'fk' => $attr, 'db' => $rel['namespace'], 'table' => $rel['model'], 'type' => $rel['type'], 'fk_constraint_name' => $rel['fk_constraint_name']);
							break;
						}
					}
					$to_delete[] = $del;
				}
				// Get modifications
				foreach($this->attr_to_modify as $attr)
				{
				//cbeads_nice_vardump($attr);
					$field['name'] = $attr['name'];
					$field['type'] = $attr['ntype'];
					$field['length'] = $attr['length'];
					$field['decimal'] = isset($attr['decimal']) ? $attr['decimal'] : FALSE;
					$field['values'] = isset($attr['values']) ? $attr['values'] : FALSE;
					$field['rename_to'] = isset($attr['rename_to']) ? $attr['rename_to'] : FALSE;
					$field['index'] = '';
					if(!empty($attr['primary']))
						$field['index'] = 'primary';
					elseif(!empty($attr['unique']))
						$field['index'] = 'unique';
					elseif(!empty($attr['index']))
						$field['index'] = 'index';
					$field['notnull'] = isset($attr['notnull']) ? $attr['notnull'] : FALSE;
					$field['autoincrement'] = isset($attr['autoincrement']) ? $attr['autoincrement'] : FALSE;
					$field['comment'] = isset($attr['comment']) ? $attr['comment'] : FALSE;
					$field['_type'] = 'attr';
					$to_modify[] = $field;
				}
				// Get adds
				foreach($this->attr_to_add as $attr)
				{
					//cbeads_nice_vardump($attr);
					$field['name'] = $attr['name'];
					$field['type'] = $attr['ntype'];
					$field['length'] = $attr['length'];
					$field['decimal'] = isset($attr['decimal']) ? $attr['decimal'] : FALSE;
					$field['values'] = isset($attr['values']) ? $attr['values'] : FALSE;
					$field['rename_to'] = FALSE;
					$field['index'] = '';
					if(isset($attr['primary']))
						$field['index'] = 'primary';
					elseif(isset($attr['unique']))
						$field['index'] = 'unique';
					elseif(isset($attr['index']))
						$field['index'] = 'index';
					$field['notnull'] = isset($attr['notnull']) ? $attr['notnull'] : FALSE;
					$field['autoincrement'] = isset($attr['autoincrement']) ? $attr['autoincrement'] : FALSE;
					$field['comment'] = isset($attr['comment']) ? $attr['comment'] : FALSE;
					$field['_type'] = 'attr';
					$to_add[] = $field;
				}
				$result = DBManager::alter_table($this->namespace, $this->model, $to_add, $to_modify, $to_delete);
				//cbeads_nice_vardump($result);
			}
			elseif($this->status == 'removed') // Delete
			{
				//$result = DBManager::delete_table($this->namespace, $this->model);
				// Get removes. This is done because when deleting a model involved in a m-m 
				// relationship, the foreign keys have to be removed first before any model is
				// deleted. This could be avoided by working out a correct order to delete models.
				foreach($this->attr_to_delete as $attr)
				{
					$del = array('_type' => 'attr', 'name' => $attr);
					// Check if the attribute is actually a foreign key in a relationship that is 
					// being dropped. Must handle such attributes as relationships being removed.
					$rel = array();
					if($this->is_foreign_key($attr, $rel))
					{
						$del = array('_type' => 'rel', 'fk' => $attr, 'db' => $rel['namespace'], 'table' => $rel['model'], 'type' => $rel['type'], 'fk_constraint_name' => $rel['fk_constraint_name']);
					}
					$to_delete[] = $del;
				}
				$result = DBManager::alter_table($this->namespace, $this->model, $to_add, $to_modify, $to_delete);
			}
		}
		else
		{
			$fields = array();
			foreach($this->attr_to_add as $attr)
			{
				//cbeads_nice_vardump($attr);
				$field['name'] = $attr['name'];
				$field['type'] = $attr['ntype'];
				$field['length'] = $attr['length'];//($attr['ntype'] == 'varchar' || $attr['ntype'] == 'varbinary') ? $attr['length'] : 0;
				$field['decimal'] = isset($attr['decimal']) ? $attr['decimal'] : FALSE;
				$field['values'] = isset($attr['values']) ? $attr['values'] : FALSE;
				$field['index'] = '';
				if(isset($attr['primary']))
					$field['index'] = 'primary';
				elseif(isset($attr['unique']))
					$field['index'] = 'unique';
				elseif(isset($attr['index']))
					$field['index'] = 'index';
				$field['notnull'] = isset($attr['notnull']) ? $attr['notnull'] : FALSE;
				$field['autoincrement'] = isset($attr['autoincrement']) ? $attr['autoincrement'] : FALSE;
				$field['comment'] = isset($attr['comment']) ? $attr['comment'] : FALSE;
				
				// Make sure the id field is the first one in the list.
				if($field['name'] == 'id')
					array_unshift($fields, $field);
				else
					array_push($fields, $field);
			}
			$result = DBManager::create_table($this->namespace, $this->model, $fields);
		}
	
		return $result;
	}
	
	// Applies relationship changes to the database level and updates this model's file.
	public function commit_stage2()
	{
		$success = TRUE;
		$error = "";
	
		$to_add = array();
		
		// If the model is new, will need to create the table. Otherwise it can be altered.
		if($this->exists)
		{
			// Is an update required or a delete?
			if($this->status == "updated")
			{
				// Run removes
				
				// Run updates
				
				// Run adds
				foreach($this->rel_to_add as $rel)
				{
					// many-to-one and mapping relationships require foreign keys to be added.
					if($rel['type'] == 'many-to-one')
					{
						$to_add[] = array('_type' => 'rel', 'fk' => $rel['local'], 'id' => $rel['foreign'], 'table' => $rel['table'], 'db' => $rel['namespace']);
					}
					elseif($rel['type'] == 'mapping')
					{
						$to_add[] = array('_type' => 'rel', 'fk' => $rel['fk1'], 'id' => 'id', 'db' => $rel['namespace1'], 'table' => $rel['model1']);
						$to_add[] = array('_type' => 'rel', 'fk' => $rel['fk2'], 'id' => 'id', 'db' => $rel['namespace2'], 'table' => $rel['model2']);
					}
				}
			}
		}
		else
		{
			
			foreach($this->rel_to_add as $rel)
			{
				// many-to-one and mapping relationships require foreign keys to be added.
				if($rel['type'] == 'many-to-one')
				{
					$to_add[] = array('_type' => 'rel', 'fk' => $rel['local'], 'id' => $rel['foreign'], 'table' => $rel['table'], 'db' => $rel['namespace']);
				}
				elseif($rel['type'] == 'mapping')
				{
					$to_add[] = array('_type' => 'rel', 'fk' => $rel['fk1'], 'id' => 'id', 'db' => $rel['namespace1'], 'table' => $rel['model1']);
					$to_add[] = array('_type' => 'rel', 'fk' => $rel['fk2'], 'id' => 'id', 'db' => $rel['namespace2'], 'table' => $rel['model2']);
				}
			}

		}
		
		// If the model was created, removed or a relationship added or removed then the model file has to
		// be updated as well. Note, if an alias was renamed then the update_file flag is already
		// set to true (see the apply_rename_action function).
		if(!$this->exists || $this->status == 'removed' || count($this->rel_to_add) > 0 || count($this->rel_to_delete) > 0)
			$this->update_file = TRUE;
		
		$result = DBManager::alter_table($this->namespace, $this->model, $to_add, array(), array());
		if($result['success'] == FALSE)
			return $result;
		
		$sql_run = $result['sql_run'];
		//$result = array('success' => TRUE, 'sql_run' => $sql_run);
		//cbeads_nice_vardump($this);
		//return array('success'=> TRUE, 'sql_run' => $sql_run);
		// Now update/create the file for this model.
		if(!$this->exists && $this->update_file)
		{
			$result2 = FileManager::create_model_file($this);
			if($result['success'] == FALSE)
			{
				return $result;
			}
		}
		elseif($this->exists && $this->status == "updated" && $this->update_file)		// Only update if there is anything to update.
		{
			$result2 = FileManager::update_model_file($this);
			if($result['success'] == FALSE)
			{
				return $result;
			}
		}
		elseif($this->exists && $this->status == "removed"  && $this->update_file)
		{
			$result2 = FileManager::delete_model_file($this);
			if($result['success'] == FALSE)
			{
				return $result;
			}
			$result = DBManager::delete_table($this->namespace, $this->model);
			if($result['success'] == FALSE)
				return $result;
			$sql_run[] = $result['sql_run'];
		}
		
		return array('success' => TRUE, 'sql_run' => $sql_run);
	}
	
	
	// Works out what existing fields/attributes this model has.
	// Sets the 'cur_attr' variable with an associative array where keys are field names and values
	// hold information on a field.
	private function get_existing_fields()
	{
		$info = DBManager::get_table_info($this->model, $this->namespace);
		//cbeads_nice_vardump($info);
		foreach($info['columns'] as $field => $field_info)
		{
			// Check if the field is required to be unique.
			if(isset($info['indexes'][$field]))
			{
				foreach($info['indexes'][$field] as $index_opt)
				{
					if(isset($index_opt['unique']))
						$field_info['unique'] = $index_opt['unique'];
				}
			}
			$this->cur_attrs[$field] = $field_info;
		}
		
		return TRUE;
	}
	
	// Works out what existing relationships this model has. Relationships are stored in 
	// the $cur_rels variable as an array of arrays, where each array holds information on a relationship.
	private function get_existing_relationships()
	{
		//$all_relationships = ModelManager::$all_relationships;
		$all_relationships = DBManager::get_all_relationships();
		$relationships = array();
		foreach($all_relationships as $relationship)
		{
			$alias = NULL;
			if($relationship['table_schema'] == $this->namespace && $relationship['table_name'] == $this->model && !$this->is_mapping_table($relationship['table_name']))
			{
				$aliases = json_decode($this->cur_attrs[$relationship['column_name']]['comment']);
				if($aliases && isset($aliases->alias1)) 
					$alias = $aliases->alias1;
				if($alias === NULL)
					$alias = ucfirst($relationship['referenced_table_name']);
				$relationships[] = array(
					'namespace' => $relationship['referenced_table_schema'],
					'table' => $relationship['referenced_table_name'],
					'local' => $relationship['column_name'],
					'foreign' => $relationship['referenced_column_name'],
					'update' => $relationship['update_rule'],
					'delete' => $relationship['delete_rule'],
					'fk_constraint_name' => $relationship['constraint_name'],
					'type' => 'many-to-one',
					'alias' => $alias
				);
			}
			if($relationship['referenced_table_schema'] == $this->namespace && $relationship['referenced_table_name'] == $this->model)
			{
			//cbeads_nice_vardump($relationship);
				// Is this a one to many relationship or many to many?
				$rel_table = $relationship['table_name'];
				$rel_namespace = $relationship['table_schema'];
				$rel_local = $relationship['column_name'];
				//if(substr($rel_table, strlen($rel_table) - 4) == '_map')	// Indicates this is a mapping table.
				if($this->is_mapping_table($rel_table))
				{
					$text = '';
					// Get all relationships where the mapping table references other tables.
					// Each related model is part of a many to many mapping with this model.
					foreach($all_relationships as $_rel)
					{
						if($_rel['table_schema'] == $rel_namespace && $_rel['table_name'] == $rel_table &&
							($_rel['referenced_table_name'] != $relationship['referenced_table_name'] || 
							$_rel['referenced_table_schema'] != $relationship['referenced_table_schema']))
						{
							// Get the alias used to refer to the other model.
							$info = DBManager::get_table_info($rel_table, $rel_namespace);
							$comment = json_decode($info['columns'][$_rel['column_name']]['comment']);
							$alias = NULL;
							if($comment !== FALSE)
								$alias = isset($comment->alias) ? $comment->alias : NULL;
							if($alias === NULL)
								$alias = ucfirst($this->pluralise($_rel['referenced_table_name']));
							$relationships[] = array(
								'namespace' => $_rel['referenced_table_schema'],
								'table' => $_rel['referenced_table_name'],
								'update' => $_rel['update_rule'],
								'delete' => $_rel['delete_rule'],
								'type' => 'many-to-many',
								'local' => $rel_local,
								'foreign' => $_rel['column_name'],
								'mapping_namespace' => $rel_namespace,
								'mapping_table' => $rel_table,
								'fk_constraint_name_local' => $relationship['constraint_name'],
								'fk_constraint_name_foreign' => $_rel['constraint_name'],
								'alias' => $alias
							);
						}
					}
				}
				else
				{
					$related_tbl_info = DBManager::get_table_info($relationship['table_name'], $relationship['table_schema']);
					$aliases = json_decode($related_tbl_info['columns'][$relationship['column_name']]['comment']);
					if($aliases && isset($aliases->alias2)) 
						$alias = $aliases->alias2;
					if($alias === NULL)
						$alias = ucfirst($this->pluralise($relationship['table_name']));
					$relationships[] = array(
						'namespace' => $relationship['table_schema'],
						'table' => $relationship['table_name'],
						'local' => $relationship['referenced_column_name'],
						'foreign' => $relationship['column_name'],
						'update' => $relationship['update_rule'],
						'delete' => $relationship['delete_rule'],
						'fk_constraint_name' => $relationship['constraint_name'],
						'type' => 'one-to-many',
						'alias' => $alias
					);
				}
			}
		}
		
		//cbeads_nice_vardump($relationships);
		$this->cur_rels = $relationships;
		
		// NOTE: What if this model represents a mapping table? It will need to know if it is used as mapping table
		// so it can work out what needs to be done if there are changes made to the underlying table.
		// Eg: renaming the mapping table? (Allow this?) Would need to update files of boths models being mapped.
		// 	   	Adding columns to the table.
		//		Removing columns (this is dangerous because a fk could be removed). Allow this? Would have to remove 
		//		the mapping relationship in such a case.
		// Could just deny any operations on a mapping table. But that isn't nice.
	}
	
	// Writes the model data to file.
	public function write_to_file($handle)
	{
		// Need to work out what relationships need to be written to file.
		// Take existing relationships, see if any have to be deleted and if there 
		// are any to add.
		$relationships = array();
		
		foreach($this->cur_rels as $cur_rel)
		{
			$delete = FALSE;
			foreach($this->rel_to_delete as $rel_del)
			{
				if($rel_del['type'] == $cur_rel['type'] && $rel_del['namespace'] == $cur_rel['namespace'] && $rel_del['model'] == $cur_rel['table'])// && $rel_del['alias'] == $cur_rel['alias'])
				{
					$delete = TRUE;
				}
			}
			if(!$delete)
			{
				$relationships[] = $cur_rel;
			}
		}
		$relationships = array_merge($relationships, $this->rel_to_add);
		
		//echo "Add these:";
		//cbeads_nice_vardump($relationships);
		//echo "write_to_file";
		
		$text = '';
		foreach($relationships as $relationship)
		{
			if($relationship['type'] == 'one-to-many')
			{
				$alias = $relationship['alias'];
				$text .= "\t\t\$this->hasMany('" . $relationship['namespace'] . "\\" . $relationship['table'] . " as $alias',\n";
				$text .= "\t\t\tarray(\n";
				$text .= "\t\t\t\t'local' => '" . $relationship['local'] . "',\n";
				$text .= "\t\t\t\t'foreign' => '" . $relationship['foreign'] . "'\n";
				$text .= "\t\t\t)\n";
				$text .= "\t\t);\n";
			}
			elseif($relationship['type'] == 'many-to-one')
			{
				$alias = $relationship['alias'];
				$text .= "\t\t\$this->hasOne('" . $relationship['namespace'] . "\\" . $relationship['table'] . " as $alias',\n";
				$text .= "\t\t\tarray(\n";
				$text .= "\t\t\t\t'local' => '" . $relationship['local'] . "',\n";
				$text .= "\t\t\t\t'foreign' => '" . $relationship['foreign'] . "'\n";
				$text .= "\t\t\t)\n";
				$text .= "\t\t);\n";
			}
			elseif($relationship['type'] == 'many-to-many')
			{
				$alias = $relationship['alias'];
				$text .= "\t\t\$this->hasMany('" . $relationship['namespace'] . "\\" . $relationship['table'] . " as $alias',\n";
				$text .= "\t\t\tarray(\n";
				$text .= "\t\t\t\t'local' => '" . $relationship['local'] . "',\n";
				$text .= "\t\t\t\t'foreign' => '" . $relationship['foreign'] . "',\n";
				$text .= "\t\t\t\t'refClass' => '" . $relationship['mapping_namespace'] . "\\" . $relationship['mapping_table']. "'\n";
				$text .= "\t\t\t)\n";
				$text .= "\t\t);\n";
			}
			
		}
		
		$template = $this->get_file_template();
		$patterns = array(
			'/<backup_generated>/', '/<datetime>/', '/<namespace>/', '/<model>/', '/<auto_generatd_relationships>/'
		);
		$replacements = array(
			'', //$this->exists ? 'A backup was not made!' : '',
			date('Y-m-d \a\t H:i:s'),
			strtolower($this->namespace),
			strtolower($this->model),
			$text
		);
		$content = preg_replace($patterns, $replacements, $template);
		fwrite($handle, $content);
		fclose($handle);
		
		return array('success' => TRUE);
	}
	
	// Generates a SBOML sentence from the current model status.
	public function generate_sboml_sentence()
	{
		//cbeads_nice_vardump($this);
		$sentence = "in <span class='namespace'>" . ucfirst($this->namespace) . ", " . ucfirst($this->model) . "</span> has ";
		
		// First process all attributes (columns) for this model.
		$attributes = array();
		foreach($this->cur_attrs as $name => $info)
		{
			$classes = array();
			if($info['primary'])
				$classes[] = 'primary';
			if(!empty($info['notnull']))
				$classes[] = 'required';
			if(!empty($info['unique']))
				$classes[] = 'unique';

			$classes[] = 'more_info';
		
			$ref_rel = array();
			if($name == 'id' || !$this->is_foreign_key($name, $ref_rel))
			{
				$tmp = "<span class='" . join(' ', $classes) . "' title='" . $info['ntype'] . " -&gt; " . join(', ', $info['alltypes']) . "'>$name</span>";
			}
			else
			{
				//cbeads_nice_vardump($ref_rel);
				$rel = $this->get_relationship_by_fk($name);
				if(isset($rel['namespace']))	// If no relationship info exists, then this field must be a fk used in a m-m relationship. Best thing to do is just display the this field exists.
				{
					$entity_text = '';
					if($rel['namespace'] != $this->namespace)
						$entity_text .= strtoupper($rel['namespace']) . '::';
					$entity_text .= strtoupper($rel['table']);
					$text = $entity_text . ' as ' . $rel['alias'];
					$classes[] = 'relationship';
					$tmp = "<span class='" . join(' ', $classes) . "' title='Column name: $name. On delete: " . $rel['delete'] . ". On update: " . $rel['update'] . ".'>$text</span>";
				}
				else
				{
					$classes[] = 'fk_m_to_m';
					$tmp = "<span class='" . join(' ', $classes) . "' title='" . $info['ntype'] . " -&gt; " . join(', ', $info['alltypes']) . "'>$name</span>";
				}
			}
			$attributes[] = $tmp;
		}
		
		// Now add one-to-many, many-to-many relationships
		foreach($this->cur_rels as $rel)
		{
			if($rel['type'] == 'one-to-many')
			{
				$entity_text = '';
				if($rel['namespace'] != $this->namespace)
					$entity_text .= strtoupper($rel['namespace']) . '::';
				$entity_text .= strtoupper($rel['table']);
				$text = 'many ' . $entity_text . ' as ' . $rel['alias'];
				$tmp = "<span class='relationship' title=''>$text</span>";
				$attributes[] = $tmp;
			}
			else if($rel['type'] == 'many-to-many')
			{
				//cbeads_nice_vardump($rel);
				$entity_text = '';
				if($rel['namespace'] != $this->namespace)
					$entity_text .= strtoupper($rel['namespace']) . '::';
				$entity_text .= strtoupper($rel['table']);
				$text = 'many ' . $entity_text . ' and vice versa as ' . $rel['alias'];
				$tmp = "<span class='m-m' title=''>$text</span>";
				$attributes[] = $tmp;
			}
		}
		
		$sentence .= join(", ", $attributes);
		return $sentence;
	}
	
	// UTILITY FUNCTIONS
	
	// Checks a table name to see if it is a mapping table. Tables that end in '_map' are
	// treated as mapping tables.
	private function is_mapping_table($table)
	{
		return substr($table, strlen($table) - 4) == '_map' ? TRUE : FALSE;
	}
	
	
	// Converts a fieldname to a foreign key format.
	private function _format_to_fk($name)
	{
		return strtolower($name) . '_id';
	}
	
	// Use two tables to come up with a formatted name for a mapping table.
	private function _format_to_mapping_table($table1, $table2)
	{
		return $table1 . '_' . $table2 . '_map';
	}
	
	private function get_file_template()
	{
		$val = <<<qq
<?php

/*
	This file was auto generated on <datetime>.
	<backup_generated>
*/

namespace <namespace>;

class <model> extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		\$this->setTableName('<namespace>.<model>');
		\$this->auto_generate_definition();
	}

	public function setUp()
	{
<auto_generatd_relationships>
	}

}

?>
qq;
		return preg_replace('/\n/', PHP_EOL, $val);		// Ensure proper line endings on different platforms.
	}
	
	// Pluralise a name. This is just a simple and incorrect implementation. 
	// TODO: Need to see if this can be done properly.
	private function pluralise($name)
	{
		$len = strlen($name);
		if($name[$len - 1] != 's')
		{
			$name .= "s";
		}
		return $name;
	}
	
	// Gets the Doctrine compatible data type for a given field name by looking it up in the CBEADS
	// attribute definition table. If no match found, returns default string data type.
	// name: the field name.
	private function get_type_from_attribute_definition($name)
	{
		$type = "varchar";
		$attr_def = Doctrine_Core::getTable('cbeads\Attribute_definition')->findOneByName($name);
		if($attr_def)
		{
			while($attr_def->db_type == NULL)
			{
				$attr_def = $attr_def->alias;
			}
			$type = strtolower($attr_def->db_type);
		}
		//echo "Declaration for '$type'";
		$declaration = Doctrine_Manager::connection()->dataDict->getPortableDeclaration(array('type' => $type));
		$declaration['ntype'] = $type;
		$declaration['attr_type'] = $declaration['type'][0];
		if($declaration['ntype'] == 'varchar' && $declaration['length'] == 0)
			$declaration['length'] = 255;
		//cbeads_nice_vardump($declaration);
		return $declaration;
	}

	// Checks if the underlying table for this model exists or not. Regardless of wether or not
	// there is a model file, if the table does not exist, then the model does not exist. If a
	// table does exist then the model exists.
	private function does_model_exist()
	{
		$namespace = $this->namespace;
		$model = $this->model;
		
		$got_table = FALSE;
		$tables = DBManager::get_database_tables($namespace);
		// if(isset(ModelManager::$all_table_info[$namespace][$model]))
		if(in_array($model, $tables))
		{
			$got_table = TRUE;
		}
		//echo "Got table '$got_table' for $namespace::$model<br/>";
		return $got_table;
	}
	
	// Finds out if a name represents a model or an alias for the purpose of identifying a
	// relationship.
	// Returns the namespace, model and alias of the matched relationship.
	private function is_model_or_relationship_alias($name)
	{
		$name = strtolower($name);
		foreach($this->cur_rels as $rel)
		{
			if(strtolower($rel['alias']) == $name)
			{
				$result['found'] = TRUE;
				$result['model'] = $rel['table'];
				$result['namespace'] = $rel['namespace'];
				$result['alias'] = $rel['alias'];
				return $result;
			}
			else if(strtolower($rel['table']) == $name)
			{
				$result['found'] = TRUE;
				$result['model'] = $name;
				$result['namespace'] = $rel['namespace'];
				$result['alias'] = $rel['alias'];
				return $result;
			}
		}
	}
	
	// Finds the relationship identified by an alias. Returns an associative array indicating
	// if a relationship was found and if containing the relationship.
	private function get_relationship_by_alias($alias)
	{
		$alias = strtolower($alias);
		$result = array('found' => FALSE);
		foreach($this->cur_rels as $rel)
		{
			if(strtolower($rel['alias']) == $alias)
			{
				$result['found'] = TRUE;
				$result['rel'] = $rel;
				return $result;
			}
		}
		return $result;
	}
	
	// Get detailed message for this error.
	public function get_error_detail_message($error)
	{
		$msg = '';
		if($error == $this::ERR_MODEL_ALREADY_EXISTS)
		{
			$msg = 'Cannot use action "has" since the model already exists. Please check what actions are allowed on models that already exist.';
		}
		elseif($error == $this::ERR_MODEL_DOES_NOT_EXIST)
		{
			$msg = 'Model does not exist. Please first create the model.';
		}
		elseif($error == $this::ERR_ALREADY_APPLIED_ACTION)
		{
			$msg = 'Only one action can be applied to a model per submission. Sentences are parsed in the order they are defined. If one sentence references a model that does not already exist, it is implicitly created at that point. If the model is then later mentioned in a "in <namespace>,<model> <action>..." sentence as the primary model then the system does not allow the action. However, you can for example say "in test,person has name, age" and then in a subsequent sentence say "othermodel has attr1, attr2, many test::person". That will work. This limitation may be removed later on.';
		}
		elseif($error == $this::ERR_UNKNOWN_ACTION)
		{
			$msg = 'The action to apply to the model is unknown.';
		}
		elseif($error == $this::ERR_FK_ALREADY_ADDED_WITH_INVALID_TYPE)
		{
			$msg = 'Field to use for fk was already added to the "to add" list but has an invalid type.';
		}
		elseif($error == $this::ERR_FK_ALREADY_EXISTS_WITH_INVALID_TYPE)
		{
			$msg = 'Foreign key field exists but has invalid type. It may have been created previously for some other purpose.';
		}
		elseif($error == $this::ERR_REL_FOR_REMOVAL_DOES_NOT_EXIST)
		{
			$msg = 'The relationship specified for removal does not exist.';
		}
		elseif($error == $this::ERR_ATTRIBUTE_ALREADY_EXISTS)
		{
			$msg = 'The attribute being added already exists.';
		}
		elseif($error == $this::ERR_ATTRIBUTE_DUPLICATE_DECLARATION)
		{
			$msg = 'The attribute has previously been declared. Remove the duplicate declaration.';
		}
		elseif($error == $this::ERR_ATTRIBUTE_DOES_NOT_EXIST)
		{
			$msg = 'The attribute does not exist.';
		}
		elseif($error == $this::ERR_ATTRIBUTE_OR_ALIAS_DOES_NOT_EXIST)
		{
			$msg = 'The attribute or alias does not exist.';
		}
		elseif($error == $this::ERR_REL_ALREADY_EXISTS)
		{
			$msg = 'A relationship to the specified model already exists.';
		}
		elseif($error == $this::ERR_SQL_ERROR)
		{
			$msg = 'An error occurred running the sql statement. No further actions are being executed.';
		}
		elseif($error == $this::ERR_REL_ACCESS_DISALLOWED)
		{
			$msg = 'Access to the related model is not allowed.';
		}
		elseif($error == $this::ERR_UNKNOWN)
		{
			$msg = 'An error has occurred which has not been documented (either unexpected or not yet properly defined).';
		}
		return $msg;
	}
	
	// Checks if a given attribute is used as a foreign key.
	private function is_foreign_key($attr, &$rel = array())
	{
		// foreach($this->cur_rels as $rel)
		// {
			// if($rel['local'] == $attr)
				// return TRUE;
		// }
		// return FALSE;
		
		$all_relationships = DBManager::get_all_relationships();
		$relationships = array();
		foreach($all_relationships as $relationship)
		{
			$alias = NULL;
			if($relationship['table_schema'] == $this->namespace && $relationship['table_name'] == $this->model)
			{
				if($relationship['column_name'] == $attr)
				{
					$rel['namespace'] = $relationship['referenced_table_schema'];
					$rel['model'] = $relationship['referenced_table_name'];
					$rel['fk_constraint_name'] = $relationship['constraint_name'];
					if($this->is_mapping_table($rel['model']))
						$rel['type'] = 'many-to-many';
					else
						$rel['type'] = 'many-to-one';
					return TRUE;
				}
			}
		}
		return FALSE;
	}
	
	// Returns information on a relationship which uses the specified foreign key.
	// If no match found, returns NULL.
	private function get_relationship_by_fk($fk)
	{
		foreach($this->cur_rels as $rel)
		{
			if($rel['local'] == $fk)
				return $rel;
		}
		return NULL;
	}
	
	// For certain errors, can suggest possible resolutions (ways to fix it).
	public function get_error_resolutions($error)
	{
		$resolutions = array();
		if($error == $this::ERR_MODEL_ALREADY_EXISTS)
		{
			$resolutions = array(
				'in <namespace>,<model> add...','in <namespace>,<model> modify...','in <namespace>,<model> remove...'
			);
		}
		return $resolutions;
	}
	
	
}

// Allows actions to be performed on the database.
// At some point in the future can make this an abstract class and have different implementations for different types of databases. For now this is only usable with MYSQL
class DBManager
{
	CONST ERR_SQL_ERROR = 500;			// Error while executing sql statement.

	static $all_table_info = array();	// Stores table information grouped by database.
	static $all_relationships = array();	// Stores all relationships grouped by database.
	static $db_tables = array();		// Stores table names grouped by database.
	static $fetched_relationships = FALSE;	// Indicates if the relationships were fetched.
	static $all_databases = array();	// Stores list of all databases	
	
	
	/* Creates a new table.
		- db: the database name.
		- tbl: the table name.
		- fields: array of fields to be created. They must contain these even if there are no values
			assigned:
				name - the name of the field (required)
				type - the data type of the field (required)
				length - length value for the field (required when type is VARCHAR or VARBINARY)
				decimal - decimals to use when dealing with data types: 'REAL, DOUBLE, FLOAT, DECIMAL, NUMERIC
				values - values to use for ENUMs and SETs
				notnull - TRUE if NULL values are not allowed, FALSE to allow them
				index - primary | unique | index
				autoincrement - wether to autoincrement this field, TRUE or FALSE
	*/			
	static function create_table($db, $tbl, $fields)
	{
		// Assumptions: The database exists.
		$sql = "CREATE TABLE `$db`.`$tbl` (\n";
		$parts = array();
		foreach($fields as $field)
		{
			$parts[] = DBManager::generate_field_sql($field, TRUE);
		}
		$sql .= join(",\n", $parts);
		$sql .= "\n) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		//cbeads_nice_vardump($sql);
		
		$conn = Doctrine_Manager::connection();
		try
		{
			$res = $conn->exec($sql);
		}
		catch(Exception $ex)
		{
			return array('success' => FALSE, 'error' => DBManager::ERR_SQL_ERROR, 'sql_error' => $ex->getMessage(), 'sql_run' => $sql);
		}
		//cbeads_nice_vardump($res);
		return array('success' => TRUE, 'sql_run' => $sql);
	}
	
	// Drops a table specified by database and table name.
	// Returns associative array where the key 'success' is set to TRUE on success, FALSE on failure.
	// If there was an error, the keys 'error' and 'sql_error' indicate the type of error.
	// NOTE: must have removed all references to this table before it can be deleted!
	static function delete_table($db, $tbl)
	{
		$sql = "DROP TABLE `$db`.`$tbl`;";
		try
		{
			$conn = Doctrine_Manager::connection();
			$res = $conn->exec($sql);
		}
		catch(Exception $ex)
		{
			return array('success' => FALSE, 'error' => DBManager::ERR_SQL_ERROR, 'sql_error' => $ex->getMessage(), 'sql_run' => $sql);
		}
		return array('success' => TRUE, 'sql_run' => $sql);
	}
	
	static function rename_table($db, $tbl, $new_tbl_name)
	{
	
	}
	
	// Generates a sql segment needed for specifying a field. Can be used with ADD or MODIFY
	// statements. When adding, make sure the create parameter is set to TRUE!
	static function generate_field_sql($field, $create = FALSE)
	{
		$name = strtolower($field['name']);
		$type = strtolower($field['type']);
		$valid_types = DBManager::get_valid_types();
		$type_req = $valid_types[$type];
		$length = $field['length'];
		$decimal = $field['decimal'];
		$values = $field['values'];
		$notnull = $field['notnull'];
		$index = $field['index'];
		$comment = $field['comment'];
		
		$key = '';
		if($index == 'primary') $key = "PRIMARY KEY";
		elseif($index == 'unique') $key = "UNIQUE KEY";
		$autoincrement = $field['autoincrement'];
		
		// Check all required values exist for this type. Check that only the optional values
		// specified for this type are used in constructing the sql segment.
		$type_def = $type;
		if(isset($type_req['required']))
		{
			if($type == 'varchar' || $type == 'varbinary')
				$type_def .= "($length)";
			else	// ENUM or SET
			{
				$vals = array();
				foreach($values as $val)
					$vals[] = addslashes($val);
				$type_def .= "('" . join("', '", $vals) . "')";
			}
		}
		// Include optional values with the type.
		if(isset($type_req['optional']))
		{
			$optionals = $type_req['optional'];
			$in_parenthesis = '';
			if(in_array('length', $optionals) && $length > 0)
				$in_parenthesis = $length;
			//if(in_array('decimal', $optionals))
			//	$in_parenthesis .= ', '.$decimal;
			elseif(in_array(array('length', 'decimal'), $optionals) && $length > 0)
				$in_parenthesis .= "$length, $decimal";
			if($in_parenthesis)
				$type_def .= '(' . $in_parenthesis . ')';
			
		}
		
		if(!$create)
		{	
			$new_name = $field['rename_to'] !== FALSE ? strtolower($field['rename_to']) : $name;
			$sql = "`$name` `$new_name` $type_def";
		}
		else
			$sql = "`$name` $type_def";
		if($notnull) $sql .= " NOT NULL";
		if($autoincrement) $sql .= " AUTO_INCREMENT";
		if($key) $sql .= " $key";
		if($comment) $sql .= " COMMENT '$comment'";
		return $sql;
	}
	
	// Generates a sql segment needed for adding a relationship.
	static function generate_add_fk_sql($rel, $table)
	{
		$fk = $rel['fk'];
		$id = $rel['id'];
		$db = $rel['db'];
		$constraint_name = $table . '_' . $fk . '_fk';	// Will ensure unique constraint names within this namespace
		$table = $rel['table'];
		//$sql = "ADD CONSTRAINT `$constraint_name` FOREIGN KEY (`$fk`) REFERENCES `$db`.`$table` (`$id`)";
		$sql = "ADD FOREIGN KEY (`$fk`) REFERENCES `$db`.`$table` (`$id`)";
		return $sql;
	}
	

	/*
		Alters the columns/relationships for this table.
		- to_add, to_update, to_remove are arrays of associative arrays, each of that contain 
		  instructions on how the table should be altered. Eg:
			to_add : [
				{_type: 'attr', name: 'mycol', type: 'integer', etc...},
				{_type: 'rel', fk: 'tbl_a_id', db: 'ns_a', table: 'tbl_a', id: 'id'}
			]
			to_remove: [
				{_type: 'attr', name: 'my_obsoletel_col'},
				{_type: 'rel', fk: 'tbl_a_id', db: 'ns_a', table: 'tbl_a', id: 'id'}
			]
	*/
	static function alter_table($db, $table, $to_add, $to_update, $to_remove)
	{
		$alterations = array();
		
		foreach($to_add as $add)
		{
			if($add['_type'] == 'rel')
			{
				$alterations[] = DBManager::generate_add_fk_sql($add, $table);
			}
			else
			{
				$alterations[] = 'ADD ' . DBManager::generate_field_sql($add, TRUE);
			}
		}
		
		foreach($to_update as $update)
		{
			if($update['_type'] == 'attr')
			{
				$alterations[] = 'CHANGE COLUMN ' . DBManager::generate_field_sql($update);
				// Check if a index needs to be dropped because this field is no longer unique.
				if(isset(DBManager::$all_table_info[$db][$table]['indexes']))
				{
					$indexes = DBManager::$all_table_info[$db][$table]['indexes'];
					foreach($indexes as $index => $fields)
					{
						foreach($fields as $field)
						{
							if($field['field'] == $update['name'] && $field['unique'])
							{
								$alterations[] = 'DROP INDEX `' . $index . '`';
								break;
							}
						}
					}
				}
			}
		}
		
		foreach($to_remove as $remove)
		{
			if($remove['_type'] == 'attr')
			{
				$alterations[] = 'DROP COLUMN `' . $remove['name'] . '`';
			}
			else
			{
				//$constraint_name = $table . '_' . $remove['fk'] . '_fk';
				//$alterations[] = "DROP FOREIGN KEY `$constraint_name`";
				$alterations[] = "DROP FOREIGN KEY `".$remove['fk_constraint_name']."`";
				$alterations[] = "DROP COLUMN `" . $remove['fk'] . "`";
			}
		}
		
		$sql = "";
		if(count($alterations) > 0)
		{
			$sql = "ALTER TABLE `$db`.`$table`\n" . join(",\n", $alterations) . ';';
			$conn = Doctrine_Manager::connection();
			try
			{
				$res = $conn->exec($sql);
			}
			catch(Exception $ex)
			{
				return array('success' => FALSE, 'error' => DBManager::ERR_SQL_ERROR, 'sql_error' => $ex->getMessage(), 'sql_run' => $sql);
			}
		}
		
		// Nothing done or no error recorded. Treat as success.
		return array('success' => TRUE, 'sql_run' => $sql);
		
	}
	
	// Returns what data types are available for use.
	static function get_valid_types()
	{
		return array(
			'bit' => array('optional' => array('//length')),
			'tinyint' => array('optional' => array('//length', 'unsigned', 'zerofill')),
			'smallint' => array('optional' => array('//length', 'unsigned', 'zerofill')),
			'mediumint' => array('optional' => array('//length', 'unsigned', 'zerofill')),
			'int' => array('optional' => array('//length', 'unsigned', 'zerofill')),
			'integer' => array('optional' => array('//length', 'unsigned', 'zerofill')),
			'bigint' => array('optional' => array('//length', 'unsigned', 'zerofill')),
			'real' => array('optional' => array('length', 'decimal', 'unsigned', 'zerofill')),
			'double' => array('optional' => array('length', 'decimal', 'unsigned', 'zerofill')),
			'float' => array('optional' => array('length', 'decimal', 'unsigned', 'zerofill')),
			'decimal' => array('optional' => array(array('length', 'decimal'), 'unsigned', 'zerofill')),
			'numeric' => array('optional' => array(array('length', 'decimal'), 'unsigned', 'zerofill')),
			'date' => array(),
			'time' => array(),
			'timestamp' => array(),
			'datetime' => array(),
			'year' => array(),
			'char' => array('optional' => array('length', 'charset', 'collate')),
			'varchar' => array('required' => array('length'), 'optional' => array('charset', 'collate')),
			'binary' => array('optional' => array('length')),
			'varbinary' => array('required' => array('length')),
			'tinyblob' => array(),
			'blob' => array(),
			'mediumblob' => array(),
			'longblob' => array(),
			'tinytext' => array('optional' => array('binary', 'charset', 'collate')),
			'text' => array('optional' => array('binary', 'charset', 'collate')),
			'mediumtext' => array('optional' => array('binary', 'charset', 'collate')),
			'longtext' => array('optional' => array('binary', 'charset', 'collate')),
			'enum' => array('required' => array('values'), 'optional' => array('charset', 'collate')),
			'set' => array('required' => array('values'), 'optional' => array('charset', 'collate'))
		);
	}
	
	// Returns what simple data types are available. These are just aliases with default data type
	// options set. For example 'string' has is a varchar of length 100.
	static function get_simple_types()
	{
		return array(
			'string' => array('type' => 'varchar', 'length' => 100),
			'number' => array('type' => 'numeric', 'length' => 20, 'decimal' => 10),
			'boolean' => array('type' => 'bit')
		);
	}
		
	// Gets list of tables in a given database.
	static function get_database_tables($database)
	{
		if(isset(DBManager::$db_tables[$database])) return DBManager::$db_tables[$database];
		$result = array();
		$conn  = Doctrine_Manager::connection();
		//$tbls = $conn->import->listTables($database);		<--- Passing database has no effect!
		$sql = "SHOW TABLES FROM `$database`";
		$tbls = $conn->fetchAssoc($sql);		// Return format: array( array("Tables_in_[database]" => tablename), array(...), ...)
		foreach($tbls as $arr)
		{
			$result[] = $arr["Tables_in_$database"];
		}
		DBManager::$db_tables[$database] = $result;
		return $result;
	}
	
	// Obtains information on a specific table. Use get_table_info to have the information cached!
	// Returns an associative array containing column and index information.
	static function obtain_tbl_info($table, $database)
	{
		$conn  = Doctrine_Manager::connection();
		$result = array();
		$result['columns'] = $conn->import->listTableColumns("$database.$table");
		$result['indexes'] = DBManager::get_indexes($table, $database);
		$cols = $conn->fetchAssoc("SHOW FULL COLUMNS FROM `$database`.`$table`");
		foreach($cols as $col)
		{
			$result['columns'][$col['Field']]['comment'] = $col['Comment'];
		}
		return $result;
	}	
	
	// Fetches the indexes (and constraints) for a given database table.
	// Returns an array of fields associated with a 
	static function get_indexes($table, $database)
	{
		$conn = Doctrine_Manager::connection();
		$keyName = 'Key_name';
		$nonUnique = 'Non_unique';
		$colName = 'Column_name';
		if ($conn->getAttribute(Doctrine_Core::ATTR_FIELD_CASE) && ($conn->getAttribute(Doctrine_Core::ATTR_PORTABILITY) & Doctrine_Core::PORTABILITY_FIX_CASE)) {
			if ($conn->getAttribute(Doctrine_Core::ATTR_FIELD_CASE) == CASE_LOWER) {
				$keyName = strtolower($keyName);
				$nonUnique = strtolower($nonUnique);
				$colName = strtolower($colName);
			} else {
				$keyName = strtoupper($keyName);
				$nonUnique = strtoupper($nonUnique);
				$colName = strtoupper($colName);
			}
		}
		
		$table = $conn->quoteIdentifier($table, true);
		$query = "SHOW INDEX FROM `$database`.`$table`";
		
		$indexes = $conn->fetchAssoc($query);
	
		$result = array();
		$data = array();
		foreach ($indexes as $indexData) {
			if ($indexData[$keyName] !== 'PRIMARY') {
				$index = $conn->formatter->fixIndexName($indexData[$keyName]);
			} else {
				$index = 'PRIMARY';
			}
			$data['unique'] = !$indexData[$nonUnique];
			$data['field'] = $indexData[$colName];
			if ( ! empty($index)) {
				$result[$index][] = $data;
			}
		}
		
		return $result;
	}

	// Retrieves all relationships that exist for all databases or where a specific database
	// is involved.
	static function get_all_relationships($namespace = NULL)
	{
		if(DBManager::$fetched_relationships) return DBManager::$all_relationships;
		// if($namespace !== NULL)
		// {
			// $query = sprintf("SELECT column_name, table_schema, key_column_usage.table_name, key_column_usage.referenced_table_schema, key_column_usage.referenced_table_name, referenced_column_name, update_rule, delete_rule, key_column_usage.constraint_name FROM information_schema.key_column_usage LEFT JOIN  information_schema.referential_constraints ON key_column_usage.constraint_name = referential_constraints.constraint_name WHERE (key_column_usage.table_schema = '%s' OR key_column_usage.referenced_table_schema = '%s') AND referenced_column_name is not NULL",
				// mysql_real_escape_string($namespace),
				// mysql_real_escape_string($namespace)
			// );
		// }
		// else
		// {
			$query = sprintf("SELECT column_name, table_schema, key_column_usage.table_name, key_column_usage.referenced_table_schema, key_column_usage.referenced_table_name, referenced_column_name, update_rule, delete_rule, key_column_usage.constraint_name FROM information_schema.key_column_usage LEFT JOIN  information_schema.referential_constraints ON key_column_usage.constraint_name = referential_constraints.constraint_name WHERE referenced_column_name is not NULL");
		// }
		$conn  = Doctrine_Manager::connection();
		DBManager::$all_relationships = $conn->fetchAssoc($query);
		//cbeads_nice_vardump($results);
		DBManager::$fetched_relationships = TRUE;
		return DBManager::$all_relationships;
	}
	
	// Gets information on a table. If the info isn't already cached, then caches it.
	static function get_table_info($table, $database)
	{	
		if(!isset(DBManager::$all_table_info[$database]))
		{
			DBManager::$db_tables[$database] = DBManager::get_database_tables($database);
			DBManager::$all_table_info[$database] = array();
		}
		
		if(!isset(DBManager::$all_table_info[$database][$table]))
		{
			DBManager::$all_table_info[$database][$table] = DBManager::obtain_tbl_info($table, $database);
		}
		
		return DBManager::$all_table_info[$database][$table];
	}
	
	// Gets information on relationships for a table. If the info isn't already cached, then caches it.
	static function get_table_relationships($table, $database)
	{
		if(!DBManager::$fetched_relationships)
		{
			DBManager::get_all_relationships($database);
		}
		$relationships = array();
		foreach(DBManager::$all_relationships as $relationship)
		{
			if(($relationship['table_schema'] == $database && $relationship['table_name'] == $table) ||
				($relationship['referenced_table_schema'] == $database && $relationship['referenced_table_name'] == $table))
			{
				$relationships[] = $relationship;
			}
		}
		return $relationships;
	}
	
	// Indicates if a database exists or not.
	static function does_db_exist($database)
	{
		if(count(DBManager::$all_databases) == 0)
		{
			$conn  = Doctrine_Manager::connection();
			DBManager::$all_databases = $conn->import->listDatabases();
		}
		return in_array(strtolower($database), DBManager::$all_databases);
	}
	
	// Indicates if a database table exists or not. Note, will return false if either the database
	// table don't exist.
	static function does_table_exist($table, $database)
	{
		if(DBManager::does_db_exist($database) == FALSE) return FALSE;
		$tables = DBManager::get_database_tables($database);
		foreach($tables as $tbl)
		{
			if(strtolower($table) == strtolower($tbl))
				return TRUE;
		}
		return FALSE;
	}
}


/*
	Handles tasks related to creating, opening and deleting files ad folders.
*/
class FileManager
{
	static $max_backups = 3;	// Maximum number of backups to keep before deleting the oldest file(s).

	// Creates a file for a model.
	static function create_model_file($model)
	{
		//cbeads_nice_vardump( FileManager::get_file_template());
		//echo "Creating file for $model->namespace :: $model->model<br/>";
		$path = APPPATH . 'models/' . strtolower($model->namespace);
		if(!file_exists($path))
		{
			$result = FileManager::create_folder($path);
			if($result['success'] == FALSE)
			{
				$result['error'] = 'Unable to create file for ' .$model->namespace . '::' . $model->model . ' due to the following error: ' . $result['error'];
				return $result;
			}
		}
		
		$path .= '/' . strtolower($model->model) . '.php';
		if(file_exists($path))
		{
			FileManager::create_backup($path);
		}
		$handle = FileManager::create_file($path);
		if(!$handle)
		{
			return array('success' => FALSE, 'error' => 'Unable to create file: "' . $path . '"');
		}
		chmod($path, 0777);
		
		$result = $model->write_to_file($handle);
		return $result;
	}
	
	// Updates the file for a model.
	static function update_model_file($model)
	{
		//echo "Updating file for $model->namespace :: $model->model<br/>";
		$path = APPPATH . 'models/' . strtolower($model->namespace);
		if(!file_exists($path))
		{
			$result = FileManager::create_folder($path);
			if($result['success'] == FALSE)
			{
				$result['error'] = 'Unable to create file for ' .$model->namespace . '::' . $model->model . ' due to the following error: ' . $result['error'];
				return $result;
			}
		}
		
		$path .= '/' . strtolower($model->model) . '.php';
		//FileManager::delete_file($path);	// TODO: make backup instead
		if(file_exists($path))
		{
			FileManager::create_backup($path);
		}
		$handle = FileManager::create_file($path);
		if(!$handle)
		{
			return array('success' => FALSE, 'error' => 'Unable to create file: "' . $path . '"');
		}
		chmod($path, 0777);
		
		$result = $model->write_to_file($handle);
		return $result;
	}
	
	// Deletes the file for this model. Designed to be called when the model itself is being deleted.
	static function delete_model_file($model)
	{
		$path = APPPATH . 'models/' . strtolower($model->namespace) . '/' . strtolower($model->model) . '.php';
		if(FileManager::delete_file($path) == FALSE)
		{
			return array('success' => FALSE, 'error' => 'Unable to delete file: "' . $path . '"');
		}
		return array('success' => TRUE);
	}
	
	// Creates a folder using the given path.
	// Returns an associative array containing a success value and error value that is populated
	// if an error has occurred.
	static function create_folder($path)
	{
		$success = mkdir($path, 0777);
		if($success == FALSE)
		{
			return array('success' => FALSE, 'error' => "Could not create directory $path");
		}
		return array('success' => TRUE);
	}
	
	// Creates a file using the given file location.
	// Returns the handle to the created file. Is set to FALSE when an error occurred.
	static function create_file($filename)
	{
		return fopen($filename, 'c+');
	}
	
	// Deletes a file using the given file location.
	// Returns TRUE if the file was deleted, otherwise FALSE.
	static function delete_file($filename)
	{
		if(file_exists($filename))
			return unlink($filename);
		else
			return FALSE;
	}

	// Creates a file backup
	static function create_backup($filename)
	{
		if(!file_exists($filename)) return FALSE;
		$path_parts = pathinfo($filename);
		$name = $path_parts['basename'];
		$directory = $path_parts['dirname'];
		$files = scandir($directory);
		$date = date("ymd_Hi");
		$newfilename = "$filename.$date.bkp";
		//cbeads_nice_vardump($files);
		$backups = array();
		foreach($files as $file)
		{
			if(!is_dir($file) && stripos($file, $name) === 0 && $name !== $file)
			{
				$backups[] = $file;
			}
		}
		sort($backups); 	// Make sure the file names are sorted from oldest to newest.
		// The file to backup exists. Make a backup. Remove an existing backup(s) if 
		// the limit is reached.
		if(count($backups) >= FileManager::$max_backups - 1)
		{
			$to_remove = count($backups) - (FileManager::$max_backups - 1);
			for($i = 0; $i < $to_remove; $i++)
			{
				FileManager::delete_file($directory . DIRECTORY_SEPARATOR . $backups[$i]);
			}
		}
		
		$result = rename($filename, $newfilename);
		chmod($newfilename, 0777);
		return $result;
	}
}

/*

SBOML SYNTAX:



sentences 		= sentence {sentence} ;

sentence 		= in <namespace>, <model> (<create_sentence> | <add_sentence> | <remove_sentence> | <modify_sentence> | <rename_sentence>). ;

create_sentence = has <attr_sentence> ;
attr_sentence 	= (<norm_attr> | <rel_attr>) {, <norm_attr> | <rel_attr>} ;

rel_attr 		= many_to_one | one_to_many | many_to_many ;
many_to_one 	= <entity> ['('<create_sentence>')'] [as <alias> [and <alias>]] ;
one_to_many 	= many <entity> ['('<create_sentence>')'] [as <alias> [and <alias>]] ;
many_to_many 	= many <entity> ['('<create_sentence>')'] and vice versa [via <table>] [as <alias>  [and <alias>]] ;

norm_attr 		= <attr_name> ['('<attr_options>')'] ;
attr_options 	= as <data_type> | (is required | is optional) | (is unique | is not unique) {, <data_type> | (is required | is optional) | (is unique | is not unique)} ;
data_type 		= <dt_string> | <dt_integer> | <dt_float> | <dt_decimal> | <dt_date> | <dt_timestamp> | <dt_blob> | <dt_enum> |  etc...
dt_string		= string(<string_length>) ;
dt_integer		= number ;
dt_enum 		= which could be <quoted_string> { or <quoted_string> } ;

add_sentence 	= add <attr_sentence> ;

remove_sentence = remove (<attr_name> | <entity>) {, (<attr_name> | <entity>)} ;

modify_sentence = modify <attr_name> '(' <attr_options> ')' {, <attr_name> '(' <attr_options> ')'};

rename_sentence = rename <attr_name> to <attr_name> {, <attr_name> to <attr_name> } ;

entity 			= [<namespace>::]<model> ;
namespace 		= <valid_name> ;
model 			= <valid_name> ;
alias 			= <valid_name> ;
attr_name 		= <valid_name> ;
quoted_string	= '\''<string>'\'' | '"'<string>'"' ;

valid_name		= (<underscore> | <letter>) {<underscore> | <letter> | <digit>} ;
string			= (<underscore> | <letter> | <digit> | <space>) {<underscore> | <letter> | <digit> | <space>} ;
underscore 		= '_' ;
letter 			= 'A' | 'B' | 'C' | 'D' | 'E' | 'F' | 'G' | 'H' | 'I' | 'J' | 'K' | 'L' | 'M' | 'N' | 'O' | 'P' | 'Q' | 'R' | 'S' | 'T' | 'U' | 'V' | 'W' | 'X' | 'Y' | 'Z' | 'a' | 'b' | 'c' | 'd' | 'e' | 'f' | 'g' | 'h' | 'i' | 'j' | 'k' | 'l' | 'm' | 'n' | 'o' | 'p' | 'q' | 'r' | 's' | 't' | 'u' | 'v' | 'w' | 'x' | 'y' | 'z' ;
digit			= '1' | '2' | '3' | '4' | '5' | '6' | '7' | '8' | '9' | '0' ;
space			= ' ' ;



Examples:

('!' denotes not implemented)

in namespace, model has attr, attr, rel_attr
in namespace, model has attr, attr, rel_attr as alias
in namespace, model has attr, attr, rel_attr (has attr, attr)
in namespace, model has attr, attr, rel_attr (has attr, attr) as alias!
in namespace, model has attr, attr, rel_attr (has attr, attr, rel_attr)
in namespace, model has attr, attr, rel_attr (has attr, attr, rel_attr (has attr, attr))
in namespace, model has attr, attr, many rel_attr
in namespace, model has attr, attr, many rel_attr as alias!
in namespace, model has attr, attr, many rel_attr and vice versa
in namespace, model has attr, attr, many rel_attr and vice versa as alias!
in namespace, model has attr, attr, many rel_attr and vice versa, attr, rel_attr
in namespace, model has attr, attr, many rel_attr (has attr, attr)
in namespace, model has attr, attr, many rel_attr (has attr, attr) as alias
in namespace, model has attr, attr, many rel_attr (has attr, attr) as alias and alias_foreign
in namespace, model has attr, attr, many rel_attr (has attr, attr) and vice versa
in namespace, model has attr, attr, many rel_attr and vice versa via mapping_table
in namespace, model has attr, attr, many rel_attr and vice versa via mapping_table as alias
in namespace, model has attr, attr, many rel_attr and vice versa via mapping_table as alias and alias_foreign
in namespace, model has attr, attr, many rel_attr (has attr, attr) and vice versa via mapping_table
in namespace, model has attr, attr, many rel_attr (has attr, attr) and vice versa via mapping_table as alias
in namespace, model has attr, attr, many rel_attr (has attr, attr) and vice versa via mapping_table as alias and alias_foreign
in namespace, model has attr (as data-type)
in namespace, model has attr (as data-type, is required)
in namespace, model has attr (as data-type, is required, is unique)
in namespace, model has attr (as string(length))
in namespace, model has attr (as integer(length))
in namespace, model has attr (which could be opt1 or opt2 or opt3)

in namespace, model add attr
in namespace, model add attr, rel_attr
in namespace, model add attr, rel_attr (has attr, attr)

in namespace, model remove attr, rel_attr, alias

in namespace, model modify attr(as data-type)
in namespace, model modify attr(is required)
in namespace, model modify attr(is optional)
in namespace, model modify attr(is unique)
in namespace, model modify attr(is not unique)

in namespace, model rename attr to new_attr, attr to new_attr
in namespace, model rename alias to new_alias

delete namespace::model
delete namespace !
clear namespace !

?delete model in namespace   OR   delete namespace::model   OR   in namespace, model remove all
?rename model in namespace to new_name    OR   rename namespace::model to new_name


Other actions:

autogen <namespace>[::<model>]

show <namespace>[::<model>]

*/


?>