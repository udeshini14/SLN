<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php if(isset($installation_name)) echo $installation_name.' - Home Page'; ?></title>
    <link href="<?php echo base_web_url() . 'public/css/style.css';?>" rel="stylesheet" type="text/css">
</head>

<body class="default_login_page">
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
    <td align="center"><table width="100%"  border="0" align="center" cellpadding="0" cellspacing="0" class="__middle_box">
      <tr>
        <td valign="top" align="center">
		
			<div class="default_login_page-top_box"></div>
			<table class="default_login_page-mid_box" width="100%"  border="0" align="center" cellpadding="5" cellspacing="5">
			  <tr>
				<td colspan="3" class="default_login_page-bold14">
				  <div align="center">
				     Welcome to <?php if(isset($installation_name)) echo $installation_name; ?>
				  </div>
				</td>
			  </tr>
			  <tr>
				<td>
				  <?php echo form_open('public/home'); ?>
				  <?php
					// Display validation error or any other message if set.
					if(!empty($form_data['error_msg']))
					{
						$validation_errors = $form_data['error_msg'];
						echo '<div align="center" class="default_login_page-error_message">'.$validation_errors.'</div>';
					}
					else if(!empty($message))
					{
						echo '<div align="center" class="default_login_page-error_message">' . $message . '</div>';
					}
				  ?>
				  <table>
					<tr>
					  <td width="36%">
						<div align="right">
						  <label for="username"><b>Username:</b></label>
						</div>
					  </td>
					  <td colspan="2" align="left">
					  <?php $data = array('name' => 'username', 'id' => 'username', 'value' => isset($form_data['username']) ? $form_data['username'] : '');
							echo form_input($data); ?>
					  </td>
					</tr>
					<tr>
					  <td>
					    <div align="right">
						  <label for="password"><b>Password:</b></label>
					    </div>
					  </td>
					  <td colspan="2" align="left">
					  <?php $data = array('name' => 'password', 'id' => 'password');
							echo form_password($data); ?>
					  </td>
					</tr>
					<tr>
					  <td>&nbsp;</td>
					  <td width="19%">
					    <input type="image" align="left" src="<?php echo base_web_url() .'public/images/login.png';?>" width="77" height="23" onclick="form.submit();"/>
					  </td>
					  <td width="45%">
					    <input type="image" align="left" src="<?php echo base_web_url() .'public/images/reset.png';?>" width="77" height="23" onclick="form.reset(); return false;"/>
					  </td>
					</tr>
				  </table>
				  <input type="submit" style="display:none" />	<!-- Odd behaviour: FF adds a default submit button if javascript is disabled. Never seen this before.  -->
				  <?php echo form_close(); ?>
				</td>
			  </tr>
			  <?php
				$this->config->load('openid', TRUE);
				$openid = $this->config->item('openid');
				if($openid['enabled']):
			  ?>
			  <tr>
				<td align="center">
					<div class="default_login_page-login_provider_text">Or log in via an OpenID account provider:</div>
						<form id="providers_form" method="post" action="<?php echo site_url('public/home/openid_login_request'); ?>">
						<?php
							foreach($openid['providers'] as $name => $opts)
							{
								echo '<div class="default_login_page-openid_button default_login_page-' . $opts['title'] . '" title="' . $name . '" onclick="do_openid_login(\'' . $opts['title'] . '\')"></div>';
							}
						?>
						<input type="hidden" name="provider" id="provider"/>
						<input type="submit" style="display:none" />	<!-- Odd behaviour: FF adds a default submit button if javascript is disabled. Never seen this before.  -->
					</form>
					<script type="text/javascript">
						function do_openid_login(provider)
						{
							var el = document.getElementById("provider");
							el.value = provider;
							el = document.getElementById("providers_form");
							el.submit();
						}
					</script>
				</td>
			  </tr>
			  <?php 
				endif; 
				if($allow_registration || $allow_password_reset):
			  ?>
			  <tr>
				<td colspan="2">
					<div class="default_login_page-register_reset_text">
					<?php if($allow_registration): ?>
						<a href="<?php echo site_url('public/registration'); ?>" title="Allows you to create an account on this server">Register</a>
					<?php endif; if($allow_password_reset): ?>
					&nbsp;&nbsp;&nbsp;<a href="<?php echo site_url('public/reset_password'); ?>" title="Allows you to reset your password if you have forgotten it">Reset Password</a>
					<?php endif; ?>
					</div>
				</td>
			  </tr>
			  <?php endif; ?>
			</table>
			<div class="default_login_page-btm_box"></div>
        </td>
      </tr>
    </table>
    <div align="center"></div>
	</td>
  </tr>
</table>
</body>


</html>

