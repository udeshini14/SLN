<html>

<head>

    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>public/css/style.css"/>

</head>

<body class="default_pass_reset_page">

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
			<div class="default_pass_reset_page-center_box">
				<div class="default_pass_reset_page-top_box"></div>
				<div class="default_pass_reset_page-mid_box">
					<div style="padding: 10px 20px 10px 20px;">
					<?php if($stage == 'email'): ?>
						<?php echo form_open('public/reset_password'); ?>
						<div style="padding: 10px;">
							<div class="default_rego_page-server_title"><?php if(isset($installation_name)) echo $installation_name . ' - '; ?>Password Reset</div>
							<div class="default_rego_page-instructions">Your email address must be verified before proceeding with the password reset. Please enter the email address you associated with your account.</div>
						</div>
						<?php if(isset($error)): ?>
						<div style="color: red"><?php echo $error; ?></div>
						<?php endif; ?>
						<table>
							<tr>
								<td>Email</td><td><input name="email" size="50" value="<?php echo $value; ?>"/></td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="Continue" />&nbsp;&nbsp;<input type="button" value="Cancel" onclick="window.location.href='<?php echo $login_page_url;?>'" /></td>
							</tr>
						</table>
						<?php echo form_close(); ?>
					<?php elseif($stage == "email-sent"): ?>
						<p>An email has been sent to your email address containing a link. Follow the link to continue with the password reset.</p>
					<?php elseif($stage == 'password'): ?>
						<?php echo form_open("public/reset_password/reset/$user_id/$nonce"); ?>
						<div style="padding: 10px;">
							<div class="default_rego_page-server_title"><?php if(isset($installation_name)) echo $installation_name . ' - '; ?>Password Reset Form</div>
							<div class="default_rego_page-instructions">Enter your new password and confirm it.</div>
						</div>
						<?php if(isset($error)): ?>
						<div style="color: red"><?php echo $error; ?></div>
						<?php endif; ?>
						<table>
							<tr>
								<td>Password</td><td><input type='password' name="password" size="50" /></td>
							</tr>
							<tr>
								<td>Confirm Password</td><td><input type='password' name="confirm_password" size="50" /></td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="Reset" />&nbsp;&nbsp;<input type="button" value="Cancel" onclick="window.location.href='<?php echo $login_page_url;?>'" /></td>
							</tr>
						</table>
						<?php echo form_close(); ?>
					<?php else: ?>
						<p style="font-size: 1.4em; color: #22BB22; text-align: center">Your password has been changed.</p>
						<p>Click <a href="<?php echo $login_page_url; ?>">here</a> to return to the login page.</p>
					<?php endif; ?>
					</div>
				</div>
				<div class="default_pass_reset_page-btm_box"></div>
			</div>
		</td>
	</tr>
	</table>
	

</body>

</html>