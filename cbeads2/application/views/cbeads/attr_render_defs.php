<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Manage Attribute Render Definitions</title>
</head>

<body>

<?php 
    $tmpl = array ('table_open' => '<table border="1px" cellpadding="4" cellspacing="0">');
    $this->table->set_template($tmpl);
    $this->table->set_heading(array("Id", "Name", "Label", "Validation","Input Type", "Output Type", "&nbsp", "&nbsp"));
    foreach($defs as $def)
    {
        $label = ($def->label != NULL) ? $def->label : '&nbsp';
        $validation = ($def->validation != NULL) ? $def->validation : '&nbsp';
        $input = ($def->input_type != NULL) ? $def->input_type : '&nbsp';
        $output = ($def->output_type != NULL) ? $def->output_type : '&nbsp';
        $this->table->add_row($def->id, $def->name, $label, $validation, $input, $output, 
                              anchor("cbeads/manage_attributes/edit_render_type/$def->id", 'Edit'),
                              anchor("cbeads/manage_attributes/delete_render_type/$def->id", 'Delete'));
    }


echo $this->table->generate();
echo '<br>';
$js = 'onclick="window.location.href=\''.site_url('cbeads/manage_attributes/create_render_type').'\'"';
echo form_button(array('name' => 'create'), "Create Render Type", $js);
?>


</body>

</html>