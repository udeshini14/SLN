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
					<div style="padding: 10px 20px 10px 20px;">
						<p style="font-size: 1.4em; color: #22BB22; text-align: center">Your account has been created!</p>
						<p>An email containing your password has been sent to <?php echo $email; ?></p>
						<p>If you do not receive it, please contact the site administrator.</p>
						<p>Please proceed to the <a href="<?php echo $login_page_url; ?>">login page</a></p>
					</div>
				</div>
				<div class="default_rego_page-btm_box"></div>
			</div>
		</td>
	</tr>
	</table>
	

</body>

</html>