<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>cbeads/css/cbeads_style.css" />
    <script src="<?php echo base_url(); ?>libs/jquery-1.4.1.min.js" type="text/javascript"></script>   
    <style type="text/css"> 
        body { background-color: #b1c2d6; margin-left:10px; margin-right:10px; margin-bottom:4px; margin-top:4px; } 
        a { color: black; text-decoration: none; } 
        a:hover { color: #93A7CC; text-decoration: underline; }
        div.right {float: right;}
        div.left {float: left;}
    </style>
</head>

<body style='background-color: #0091BE; margin: 0px; min-width: 1000px;'>

    <div class='right' style='margin: 4px 10px 0px 0px;'>
        Select Team-Role: 
        <select id='teamroles'>
        <?php
		foreach($team_roles as $id => $teamrole)
		{
			echo'<option value="'.$id.'" >'.$teamrole.'</option>';
		}
        ?>
        </select>
        <a href='<?php echo site_url('cbeads/logout'); ?>' target=_parent>Log out</a>
    </div>
    
    <script type='text/javascript'>
        $(function(){
            //onchange='parent.menu.location.reload();
			$('#teamroles').val(<?php echo $cur_team_role; ?>);
            $('#teamroles').change(changedTeamRole);
        });
        
        function changedTeamRole()
        {
            var d = new Date();
            var time = d.getTime();
            selectedTeamRole = this.options[this.selectedIndex].value;
            $.get("<?php echo site_url('cbeads/access_bar/changedTeamRole').'/'; ?>"+selectedTeamRole + "/" 
                  + time + Math.random(), acknowledged);
        }
        
        function acknowledged(reply)
        {
            if(reply == 'success')
            {
                parent.menu.location.reload();
                parent.content.location.href = "<?php echo site_url('cbeads/home'); ?>"
            }
            else
            {
                alert('Could not change team-role:\n' + reply);
            }
        }
        
    </script>

</body>

</html>