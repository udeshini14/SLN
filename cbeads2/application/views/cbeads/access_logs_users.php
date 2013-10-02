<?php echo doctype();?>
<html>

<head>
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>cbeads/css/cbeads_style.css" />
    <script src="<?php echo base_url(); ?>libs/jquery-1.6.1.min.js" type="text/javascript"></script>
	
	<style>
		table {
			border: 1px solid gray;
			border-collapse: collapse;
		}
		td, th{
			border: 1px solid gray;
		}
		
	
	</style>
	
</head>

<body>


<p><?php echo $login_count . ' users (' . $login_percent . '%)'; ?> on the system have logged in.<br/><?php echo $no_login_count . ' users (' . $no_login_percent . '%)'; ?> on the system have never logged in.</p>


<p>The following users have logged into the system:</p>
<div id="logged_in_users">
	<table>
		<tr><th>User</th><th>Log in count</th><th>Last log in</th></tr>
		<?php foreach($users as $id => $user)
			echo '<tr><td>' . $user['name'] . '</td><td>' . $user['login'] . '</td><td>' . $user['last_login'] . '</td></tr>';
		?>
	</table>
</div>

<p>The following users have never logged into the system:</p>
<div id="never_logged_in_users">
	<table>
		<tr><th>User</th></tr>
		<?php foreach($never_logged_in_users as $id => $user)
			echo '<tr><td>' . $user['name'] . '</td></tr>';
		?>
	</table>
</div>

</body>


</html>