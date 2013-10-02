<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php $title; ?></title>
</head>
<body>

<p class="heading"><?php $heading; ?></p>
<?php echo form_open('cbeads/manage_attributes/edit_attr/'.$obj_id); ?>
<?php echo validation_errors('<p>','</p>'); ?>

<table>
<tr>
    <td><label for="name">Name: </label></td>
    <td><?php echo form_input('name', set_value('name', $_name)); ?></td>
</tr>
<tr>
    <td><label for="db_type">Data Type: </label></td>
    <td><?php echo form_dropdown('db_type', $data_types, set_value('db_type', array($_db_type))); ?></td>
</tr>
<tr>
    <td><label for="additional">Additional: </label></td>
    <td><?php echo form_input('additional', set_value('additional', $_additional)); ?></td>
</tr>
<tr>
    <td><label for="render_type">Render Type: </label></td>
    <td><?php echo form_dropdown('render_type', $render_types, set_value('render_type', array($_render_type))); ?></td>
</tr>
<tr>
    <td><label for="comment">Comment: </label></td>
    <td><?php echo form_textarea(array('name' => 'comment', 'cols' => '50', 'rows' => '5'), set_value('comment', $_comment));?></td>
</tr>
<tr>
    <td><?php 
        echo form_button(array('name' => 'Cancel'), 'Cancel', 'onclick="window.location.href=\''.site_url('cbeads/manage_attributes/attribute_definitions').'\'"');
        echo form_submit('update', 'Update'); 
        ?>
    </td>
</tr>

<?php echo form_close(); ?>
</table>