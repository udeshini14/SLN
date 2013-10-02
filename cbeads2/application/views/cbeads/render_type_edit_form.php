<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php $title; ?></title>
</head>
<body>

<p class="heading"><?php $heading; ?></p>
<?php echo form_open('cbeads/manage_attributes/edit_render_type/'.$obj_id); ?>
<?php echo validation_errors('<p>','</p>'); ?>

<table>
<tr>
    <td><label for="name">Name: </label></td>
    <td><?php echo form_input('name', set_value('name', $_name)); ?></td>
</tr>
<tr>
    <td><label for="label">Label: </label></td>
    <td><?php echo form_input('label', set_value('label', $_label)); ?></td>
</tr>
<tr>
    <td><label for="validation">Validation: </label></td>
    <td><?php echo form_input('validation', set_value('validation', $_validation)); ?></td>
</tr>
<tr>
    <td><label for="input_type">Input Control: </label></td>
    <td><?php echo form_dropdown('input_type', $input_types, set_value('input_type', array($_input_type))); ?></td>
</tr>
<tr>
    <td><label for="output_type">Output Control: </label></td>
    <td><?php echo form_dropdown('output_type', $output_types, set_value('output_type', array($_output_type))); ?></td>
</tr>
<tr>
    <td><label for="width">Width: </label></td>
    <td><?php echo form_input(array('name' => 'width', 'size' => '3', 'maxlength' => '3'), set_value('width', $_width));?></td>
</tr>
<tr>
    <td><label for="height">Height: </label></td>
    <td><?php echo form_input(array('name' => 'height', 'size' => '3', 'maxlength' => '3'), set_value('height', $_height));?></td>
</tr>
<tr>
    <td><?php 
        echo form_button(array('name' => 'Cancel'), 'Cancel', 'onclick="window.location.href=\''.site_url('cbeads/manage_attributes/render_definitions').'\'"');
        echo form_submit('update', 'Update');
        ?>
    </td>
</tr>

<?php echo form_close(); ?>
</table>