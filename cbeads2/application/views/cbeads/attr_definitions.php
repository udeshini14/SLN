<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Manage Attribute Definitions</title>
</head>

<body>

<?php 
    $tmpl = array ('table_open' => '<table border="1px" cellpadding="4" cellspacing="0">');
    $this->table->set_template($tmpl);
    $this->table->set_heading(array("Id", "Name", "Data type", "Rendered as","Additional", "Comment", "&nbsp", "&nbsp"));
    foreach($defs as $def)
    {
        $additional = ($def->additional != NULL) ? $def->additional : $additional = '&nbsp';
        $comment = ($def->comment != NULL) ? $def->comment : $comment = '&nbsp';
        $render =($def->render_type != NULL) ? $def->render_type->name : '<i>NONE</i>';
        $this->table->add_row($def->id, $def->name, $def->db_type, $render, $additional, $comment, 
                              anchor("cbeads/manage_attributes/edit_attr/$def->id", 'Edit'),
                              anchor("cbeads/manage_attributes/delete_attr/$def->id", 'Delete'));
    }


echo $this->table->generate();
echo '<br>';
$js = 'onclick="window.location.href=\''.site_url('cbeads/manage_attributes/create_attr').'\'"';
echo form_button(array('name' => 'create'), "Create Attribute", $js);
?>


</body>

</html>