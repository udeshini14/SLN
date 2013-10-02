<html>

<head>

    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>public/css/style.css"/>
	
</head>

<body class="default_rego_page">

	<table width="800"  border="0" align="center" cellpadding="5" cellspacing="5">
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td><div align="center"><img src="<?php echo base_web_url() . 'public/images/quobs.png';?>" alt="Version Logo" width="287" height="117"></div></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td><div align="center"><img src="<?php echo base_web_url() . 'public/images/logo.png';?>" alt="Logo" width="531" height="61"></div></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>
			<div class="default_rego_page-center_box">
				<div class="default_rego_page-top_box"></div>
				<div class="default_rego_page-mid_box">
					<div style="padding: 10px;">
					<div class="default_rego_page-server_title"><?php if(isset($installation_name)) echo $installation_name . ' - '; ?>Registration Form</div>
					<div class="default_rego_page-instructions">Please fill in all the fields. Once submitted, a password will be sent to your email address.</div>
					
					<?php echo form_open('public/registration') ?>
					
						<table>
							<tr>
								<td colspan="2"><?php echo validation_errors('<div class="default_rego_page-form_error_msg">', '</div>'); ?></td>
							</tr>
							<tr>
								<td>Username</td>
								<td><input id="username" name="username" value="<?php echo set_value('username'); ?>" size="30" /></td>
							</tr>
							<tr>
								<td>Email</td>
								<td><input id="email" name="email" value="<?php echo set_value('email', $email); ?>" size="30" /></td>
							</tr>
							<tr>
								<td>Firstname</td>
								<td><input id="firstname" name="firstname" value="<?php echo set_value('firstname'); ?>" size="30" /></td>
							</tr>
							<tr>
								<td>Lastname</td>
								<td><input id="lastname" name="lastname" value="<?php echo set_value('lastname'); ?>" size="30" /></td>
							</tr>
						</table>
						<input type="submit" value="Create Account" />&nbsp
						<input type="button" value="Cancel" onclick="window.location.href='<?php echo $login_page_url; ?>'" />

					<?php echo form_close(); ?>
					</div>
				</div>
				<div class="default_rego_page-btm_box"></div>
			</div>
		</td>
	</tr>
	</table>
	

</body>

</html>