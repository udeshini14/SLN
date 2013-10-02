<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <script type="text/javascript" src="<?php echo base_url(); ?>libs/jquery-1.4.2.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>libs/json2.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>libs/jquery-ui-1.8.2.custom.min.js"></script>
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>cbeads/css/cbeads_style.css" />   
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>libs/css/jquery-ui.css" />   
    <style>
        #group_list, #item_list { list-style-type: none; margin: 0; padding: 0; width: 300px; height: auto;}
        #item_list {width: 400px;}
        #group_list li, #item_list li { margin: 0 5px 5px 5px; padding: 5px; font-size: 1em; height: 1.2em; line-height: 1em; font-weight: bold;}
        <!--.ui-state-highlight { height: 1.2em; line-height: 1em; background-color: blue; }-->
    </style>
</head> 

<body>


<h1>Manage Menu Structure</h1>

This function allows you to manage the structure of the menu. The menu structure can be set on a global basis, team basis or team-role basis. When the menu is drawn, the system looks for a matching team-role menu structure. If it cannot find one it looks for a matchin team menu structure. Again, if it cannot find one it looks for a global menu structure to use.<br>
If all that fails the system will order menu groups based on the order the application and functions exist in their respective tables.
<br><br><br>
Select the level for which to order the menu:<br>
<input type="radio" name="level" id="level_global" value="global"/><label for="level_global">Global</label><br>
<input type="radio" name="level" id="level_team" value="team"/><label for="level_team">Team</label><br>
<input type="radio" name="level" id="level_teamrole" value="teamrole"/><label for="level_teamrole">Team-Role</label><br><br>
<div id="select_item_div"></div>
<br>
<table>
    <tr>
        <td><span>Groups:</span>
            <span style='float: right; margin-right: 50px;'>
                <input id="group_add" class="btn_add" type="button" value="Add"/>
                <input id="group_del" class="btn_del" type="button" value="Del" />
            </span>
        </td>
        <td valign="center">Group Items:
            <span style='float: right; margin-right: 50px;'>
                <input id="item_add" class="btn_add" type="button" value="Add"/>
                <input id="item_del" class="btn_del" type="button" value="Del" />
            </span>
        </td>
        <td>Custom Values</td>
    </tr>    
    <tr>
        <td valign="top"><div id='group_list_div' style='width: 350px;'></div></td>
        <td valign="top"><div id='item_list_div'  style='width: 450px;'></div></td>
        <td valign="top">
            <div id='custom_values_div' style="width: 300px;">
                <table>
                    <tr>
                        <td>Group Name:</td>
                        <td><input id="custom_group_name" /></td>
                    </tr>
                    <tr>
                        <td>Group Header:</td>
                        <td><input id="custom_group_header" type="checkbox"/></td>
                    </tr>
                    <tr><td colspan="2"><hr></td></tr>
                    <tr>
                        <td>Item Name:</td>
                        <td><input id="custom_item_name" /></td>
                    </tr>
                    <tr>
                        <td>Item's URL:</td>
                        <td><input id="custom_item_url" /></td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<div>
    <input type="button" value="Save" onclick="save();" />
</div>

<br>DEBUG:
<div id = "output" style='min-height: 200px; border: 1px solid green;'></div>

</body>


<script>


var teams = <?php echo $teams; ?>;                      // List of teams available
var teamroles = <?php echo $teamroles; ?>;              // List of team-roles available
var app_functions = <?php echo $app_functions; ?>;      // List of all applications and their functions. Just names and ids.

var cur_team_id = null;             // The team and role that the menu is being edited for.
var cur_role_id = null;
var current_level = "";             // The level being worked at: global, team, team-role
var func_app_map = {};              // Used for mapping function ids to application ids.
var group_items = null;             // Stores the groups and items orders. This is updated every time a change is made by the user and then returned on saving.
var active_group = null;            // The group currently active (meaning it was clicked on).
var active_item = null;             // The item currently active (meaning it was clicked on).

var new_item_counter = 1;           // For generating new group ids. Is incremented every time a new item/group is created.
var new_group_counter = 1;          

// Set up event handlers.
$(function() {
    $('input[name="level"]').change(changed_level);
    $('#select_item_div').change(changed_item);
    
    $('#custom_group_name').change(changed_custom_group_name);
    $('#custom_group_header').change(changed_custom_group_header);
    $('#custom_item_name').change(changed_custom_item_name);
    $('#custom_item_url').change(changed_custom_item_url);
    
    $('#group_add').click(add_group);
    $('#group_del').click(del_group);
    $('#item_add').click(add_item);
    $('#item_del').click(del_item);
    
    
    // generate a map of function ids to app ids. Also store functions orders as they currently exist.
    for(var app_id in app_functions)
    {
        var funcs = app_functions[app_id].Functions;
        for(var i = 0; i < funcs.length; i++)
        {
            func_app_map[funcs[i].id] = app_id;
        }
    }
});



// ----------------------------------------------  Event Handlers  ---------------------------------------------------


// Called when the level to work at has been changed (the radio buttons: global, team, team-role)
function changed_level()
{
    html = "";
    if(this.value == "team")
    {
        html = 'Select Team: <select id="team_select"><option value="">-- Select --</option>';
        for(var i = 0; i < teams.length; i++)
        {
            html += '<option value="' + teams[i].id + '">' + teams[i].name + '</option>';
        }
        html += '</select>';
    }
    else if(this.value == "teamrole")
    {
        html = 'Select Team-Role: <select id="teamrole_select"><option value="">-- Select --</option>';
        for(var i = 0; i < teamroles.length; i++)
        {
            html += '<option value="' + teamroles[i].id + '">' + teamroles[i].name + '</option>';
        }
        html += '</select>';
    }
    else
    {
        cur_team_id = cur_role_id = null;
        get_global_menu_structure();
    }
    current_level = this.value;
    $('#select_item_div').html(html);
}


// Called when the team or team-role to use was changed. Active when the level is team or team-role.
function changed_item()
{
    
    
}


// Called when a group element is selected. Generate the list of items for the current group.
function selected_group()
{
    if(active_group == this.id) return;   // Clicked same group.
    active_group = this.id;
    active_item = null;
    
    // Make the selected group highlighted.
    $('#group_list > li').removeClass('ui-state-highlight').addClass('ui-state-default');
    $('#'+this.id).removeClass('ui-state-default').addClass('ui-state-highlight');

    // Generate the list of items for the current group.
    var html = '<ul id="item_list">';
    var index = get_index_position_for_group_id(get_id(active_group));
    if(index == -1) 
    { 
        alert('An error occurred. Unable to obtain the index position for the current group:' + active_group + '.'); 
        return;
    }
    var group = group_items[index];
    //if(group.application_id !== null) 
    //{
        for(var i = 0; i < group.items.length; i++)
        {
            if(group.items[i].function_id !== null)     // This item is for a function.
            {
                var func = get_function_for_application(group.application_id, group.items[i].function_id);
                html += '<li class="ui-state-default" id="i_' + group.items[i].item_id + '">' + func.name + '</li>';
            }
            else    // This is a custom item (not linked to a function).
            {
                html += '<li class="ui-state-default" id="i_' + group.items[i].item_id + '">[' + group.items[i].name + ']</li>';
            }
        }
    //}
    
    // if(group.application_id !== null)    // This group is for an application.
    // {
        // for(var i = 0; i < group.items.length; i++)
        // {
            // var func = get_function_for_application(group.application_id, group.items[i].function_id);
            // html += '<li class="ui-state-default" id="i_' + group.items[i].item_id + '">' + func.name + '</li>';
        // }
    // }
    // else // This is a custom group. Custom groups can be composed of items that represent functions and custom items.
    // {
        // for(var i = 0; i < group.items.length; i++)
        // {
            // html += '<li class="ui-state-default" id="i_' + group.items[i].item_id + '">' + group.items[i].name + '</li>';
        // }
    // }

    html += '</ul>';
    $('#item_list_div').html(html);
    $('#item_list').sortable({
        placeholder: "ui-state-highlight",
        axis: 'y',
        update: updated_items_list
    });
    $('#item_list').disableSelection();
    $('#item_list li').click(selected_item);
    
    // Set the custom name for the group and wether or not the header is visible.
    $('#custom_group_name').val(group_items[index].name);
    $('#custom_group_header').attr('checked', group.show_group_header == 1 ? 'checked' : '');
    // Empty item related fields.
    $('#custom_item_name, #custom_item_url').val('');
}


// Called when a group item element is selected. Populates the custom values elements as needed.
function selected_item()
{
    if(active_item == this.id) return;
    active_item = this.id;
    
    // Make the selected item highlighted.
    $('#item_list > li').removeClass('ui-state-highlight').addClass('ui-state-default');
    $('#'+this.id).removeClass('ui-state-default').addClass('ui-state-highlight');
    
    var func = get_item_by_id(get_id(active_item));
    if(func == null)
    {
        alert('Could not find matching item for item id: ' + active_item);
        return;
    }
    $('#custom_item_name').val(func.name);
    $('#custom_item_url').val(func.custom_url);
}


// Called when the user has moved a group to a new location. Need to update the order of groups in the group_items array.
function updated_groups_list(event, ui)
{
    var new_order = [];
    var old_list = group_items;
    var new_list = [];
    // Get the new order of groups and then update group_items array.
    $('#group_list li').each(function(){
        new_order.push(get_id(this.id));
    });
    for(var i = 0; i < new_order.length; i++)
    {
        for(var j = 0; j < old_list.length; j++)
        {
            if(old_list[j].group_id == new_order[i])
                new_list.push(old_list[j]);
        }
    }
    group_items = new_list;
}


// Called when the user has moved an item to a new location. Need to update the order of items for the current group.
function updated_items_list(event, ui)
{
    var index = get_index_position_for_group_id(get_id(active_group));
    var new_order = [];
    var old_list = group_items[index].items;
    var new_list = [];
    // Get the new order of items and then update the items for the current group.
    $('#item_list li').each(function(){
        new_order.push(get_id(this.id));
    });
    for(var i = 0; i < new_order.length; i++)
    {
        for(var j = 0; j < old_list.length; j++)
        {
            if(old_list[j].item_id == new_order[i])
                new_list.push(old_list[j]);
        }
    }
    group_items[index].items = new_list;
    $("#output").html(JSON.stringify(group_items));
}


// Called when the save button is cicked. Constructs a new menu order list based on the current state of
// the groups list and stored function order lists.
function save()
{
    $.post('<?php echo site_url('cbeads/manage_menu/save_menu'); ?>', 
            {'group_items': group_items, 'team_id': cur_team_id, 'role_id': cur_role_id}, got_save_response);
}


// Called when the value is changed in the custom group name textbox. Update the name component 
// for the corresponding group.
function changed_custom_group_name()
{
    if(active_group === null) return;
    var index = get_index_position_for_group_id(get_id(active_group));

    // Check if this is a custom group or not. Custom groups must have a name.
    if(group_items[index].application_id === null)
    {
        if(this.value == "")
        {
            alert('Must provide a name for this group. Reverting to previous name');
            $('#custom_group_name').val(group_items[group].name);
            return;
        }
        else
        {
            group_items[index].name = this.value;
            $('#g_' + group_items[index].group_id).text('['+this.value+']');
        }
    
    }
    else
    {   if(this.value == "")
            group_items[index].name = null;
        else
            group_items[index].name = this.value;
    }
    $("#output").html(JSON.stringify(group_items));
}

// Called when the the custom group header checkbox status is changed.
function changed_custom_group_header()
{
    if(active_group === null) return;
    var index = get_index_position_for_group_id(get_id(active_group));

    group_items[index].show_group_header = $(this).attr('checked') == '' ? 0 : 1;
    $("#output").html(JSON.stringify(group_items));
}

// Called when the value is changed in the custom item name textbox. Update the name component
// for the corresponding item.
function changed_custom_item_name()
{
    if(active_item === null) return;
    var indexes = get_index_positions_for_group_and_item_by_id(get_id(active_group), get_id(active_item));
    var group = indexes['group'];
    var item = indexes['item'];
    if(group !== null && item !== null)
    {
        if(group_items[group].items[item].function_id !== null)
        {
            if(this.value == "")
                group_items[group].items[item].name = null;
            else
                group_items[group].items[item].name = this.value;
        }
        else
        {
            if(this.value == "")
            {
                alert('Must provide a name for this item. Reverting to previous name');
                $('#custom_item_name').val(group_items[group].items[item].name);
                return;
            }
            else
            {
                group_items[group].items[item].name = this.value;
                $('#i_' + group_items[group].items[item].item_id).text('['+this.value+']');
            }
        }
    }
}

// Called when the value is changed in the custom item url textbox. Update the url component 
// for the corresponding custom item. Only works for custom items.
function changed_custom_item_url()
{
    if(active_item === null) return;
    var indexes = get_index_positions_for_group_and_item_by_id(get_id(active_group), get_id(active_item));
    var group = indexes['group'];
    var item = indexes['item'];
    if(group !== null && item !== null)
    {
        if(group_items[group].items[item].function_id !== null) return;     // Not a custom item.
        group_items[group].items[item].custom_url = this.value;
    }
}

// Called when the user wants to add a custom group to the groups list.
function add_group()
{
    var new_group = '<li class="ui-state-default" id="g_new' + new_group_counter + '">[New Group ' + new_group_counter + ']</li>';
    $('#group_list').append(new_group);
    $('#group_list').sortable('refresh');
    // Also need to attach a click event handler so the group can be 'selected'
    $("#g_new" + new_group_counter).click(selected_group);
    // Add the group to the group_items array.
    group_items.push({'application_id': null, 'name': 'New Group ' + new_group_counter, 'show_group_header': 1, 'group_id': 'new' + new_group_counter, 'items':[]});
    new_group_counter++;
    $("#output").html(JSON.stringify(group_items));
}

// Called when the user wants to delete a custom group from the groups list.
function del_group()
{
    if(active_group == null)
    {
        alert('You must first select a custom group to delete');
        return;
    }
    
    var index = get_index_position_for_group_id(get_id(active_group));
    var grp = group_items[index];
    if(grp.application_id != null)
    {
        alert('You must select a custom group to delete. The current group is linked to an application.');
        return;
    }
    
    // Delete the group from the DOM and the group_items array.
    $('#g_' + grp.group_id).remove();
    group_items.splice(index, 1);
    
    active_group = null;
    // Empty item and group related fields.
    $('#custom_item_name, #custom_item_url, #custom_group_name').val('');
    $('#custom_group_header').attr('checked', '');
}

// Called when the user wants to add a custom item to the current group.
function add_item()
{
    if(active_group == null)
    {
        alert('You need to select a group to be able to add a custom item');
        return;
    }
    // Add the item to the ul element.
    var new_item = '<li class="ui-state-default" id="i_new' + new_item_counter + '">[New Item ' + new_item_counter + ']</li>';
    $('#item_list').append(new_item);
    $('#item_list').sortable('refresh');
    // Also need to attach a click event handler so the item can be 'selected'
    $("#i_new" + new_item_counter).click(selected_item);
    // Add the item to the group in the group_items array.
    var index = get_index_position_for_group_id(get_id(active_group));
    group_items[index].items.push({'function_id': null, 'name': 'New Item ' + new_item_counter, 'custom_url': null, 'item_id': 'new' + new_item_counter});
    new_item_counter++;
    $("#output").html(JSON.stringify(group_items));
}

// Called when the user wants to delete a custom item from the current group.
function del_item()
{
    if(active_item == null)
    {
        alert('You must first select a custom item to delete');
        return;
    }
    
    // Are the active item and group custom ones? When the group is linked to an app,
    // can only delete custom items. When the group is a custom one can delete any item.
    var indexes = get_index_positions_for_group_and_item_by_id(get_id(active_group), get_id(active_item));
    var grp = group_items[indexes['group']];
    var item = grp.items[indexes['item']];
    if(!(grp.application_id == null || item.function_id == null))
    {
        alert('You must select a custom item to delete. The current group is linked to a application and the item to a function. Therefore it cannot be deleted.');
        return;
    }
    
    // Delete the item from the DOM and the group_items array.
    $('#i_'+item.item_id).remove();
    group_items[indexes['group']].items.splice(indexes['item'], 1);
    
    active_item = null;
    // Empty item related fields.
    $('#custom_item_name, #custom_item_url').val('');
}



// ----------------------------------  AJAX request functions  ----------------------------------

function get_global_menu_structure()
{
    $.post('<?php echo site_url('cbeads/manage_menu/get_global_menu'); ?>', got_global_menu_structure);
}

function get_team_menu_structure()
{

}

function get_team_role_menu_structure()
{

}


// ----------------------------------  AJAX response handlers  --------------------------------------

function got_global_menu_structure(data)
{
    var parsedData = $.parseJSON(data);
    group_items = parsedData;
    
    var html = '<ul id="group_list">';
    for(var i = 0; i < group_items.length; i++)
    {
        var group = group_items[i];
        var name = "";
        if(group.application_id == null)    // custom group
            name = '['+group.name+']';
        else                                // app group. Use name from the application/functions array.
            name = app_functions[group.application_id].name;
        html += '<li class="ui-state-default" id="g_' + group.group_id + '">' + name +"</li>";
    }
    html += '</ul>';
    $('#group_list_div').html(html);

    $('#group_list').sortable({
        placeholder: "ui-state-highlight",
        axis: 'y',
        update: updated_groups_list
    });
    $('#group_list').disableSelection();

    // Attach click handlers to the group list items so that the corresponding menu items can be shown.
    $('#group_list li').click(selected_group);
}


function got_save_response(data)
{
    var response = $.parseJSON(data);
    $('#output').html(data);
    
    // Update any ids for newly created items. Needed to match already saved items with items on the server.
    // Else these 'new' items are saved again. The group_items array must also be updated with the new ids.
    var update_item_ids = response['update_item_ids'];

    for(var old_id in update_item_ids)
    {   
        var done = false;
        $('#i_' + old_id).attr('id', 'i_' + update_item_ids[old_id]);
        for(var g = 0; g < group_items.length && done == false; g++)
        {
            for(var i = 0; i < group_items[g].items.length && done == false; i++)
            {
                if(group_items[g].items[i].item_id == old_id)
                {
                    group_items[g].items[i].item_id = update_item_ids[old_id];
                    done = true;
                }
            }
        }
    }
    
    var update_group_ids = response['update_group_ids'];
    for(var old_id in update_group_ids)
    {   
        var done = false;
        $('#g_' + old_id).attr('id', 'g_' + update_group_ids[old_id]);
        for(var g = 0; g < group_items.length && done == false; g++)
        {
            if(group_items[g].group_id == old_id)
            {
                group_items[g].group_id = update_group_ids[old_id];
                done = true;
            }
        }
    }
    
    
    alert('Saved Menu Structure');
}



// ------------------------------------------  Helper Functions -----------------------------------------


// Returns the function object for the matched application and function, from the app_functions
// list.
function get_function_for_application(application_id, func_id)
{
    var app = app_functions[application_id];
    for(var i = 0; i < app.Functions.length; i++)
    {
        if(app.Functions[i].id == func_id) return app.Functions[i];       
    }
    return null;
}

// Returns the index position of the requested group in the group_items array.
// group_id: the id of the group.
// Returns the index position or -1 if the group couldn't be found.
function get_index_position_for_group_id(group_id)
{
    for(var i = 0; i < group_items.length; i++)
    {
        if(group_items[i].group_id == group_id) return i;
    }
    return -1;
}

// Returns the index position for the requested group and the requested item by their id.
// The item id must be one that belongs to the requested group id, otherwise no item will
// be matched.
// item_id: the id of the item
// group_id: the id of the group
// Returns the index of the group and item as an object: {item_id: #, group_id: #}. The 
// values are null if no matches were made.
function get_index_positions_for_group_and_item_by_id(group_id, item_id)
{
    for(var i = 0; i < group_items.length; i++)
    {
        if(group_items[i].group_id == group_id)
        {
            for(var j = 0; j < group_items[i].items.length; j++)
            {
                if(group_items[i].items[j].item_id == item_id) return {'item': j, 'group': i};  // both matched
            }
            return {'item': null, 'group': j};  // only group matched
        }
    }
    return {'item': null, 'group': null};       // nothing matched
}

// Returns the item matching the item id from the group_items array.
// item_id: the id of the item
// Returns the item object or NULL if it couldn't be found.
function get_item_by_id(item_id)
{
    for(var i = 0; i < group_items.length; i++)
    {
        for(var j = 0; j < group_items[i].items.length; j++)
        {
            if(group_items[i].items[j].item_id == item_id) return group_items[i].items[j];
        }
    }
    return null;
}

// Extracts the identifier from a string of form g_ or i_. To avoid name conflicts, elements 
// ids from the group list are appended with g_ and element ids from the items list with i_.
// The number/string that comes after is used in group_items to identify individual groups/
// items.
function get_id(val)
{
    return val.substr(2);
}

</script>