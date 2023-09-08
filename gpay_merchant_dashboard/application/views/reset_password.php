<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Cache-Control" content="no-cache, no-store, max-age=0, must-revalidate">
	<meta http-equiv="Expires" content="0">
	<title>Merchant Aggregator Dashboard</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?= base_url('file/css/bootstrap.min.css') ?>" type="text/css">

	<!-- Custom CSS -->
	<link href="<?= base_url('file/css/theme.css'); ?>" rel="stylesheet" type="text/css">
	<?php echo $script_captcha; // javascript recaptcha ?>
</head>

<body cz-shortcut-listen="true" style="
    background-image: url(../file/img/frame.png);
    background-repeat: round;">

	<div id="help_button"></div>

	<div class="body">
		<!-- Message -->
		<ul id="messages" class="message"><?= $this->session->flashdata('message'); ?></ul>

		<div class="row">
  			<div class="col-md-6" style="padding-left: 150px; padding-top: 50px;">
			  <img src="../file/img/illustraion.png" class="Illustraion">
			</div>
  			<div class="col-md-6"style="
    padding-top: 50px;">
				<h3 class="roboto" style="text-align: center;">Reset Password</h3>	
				<p class="small-text">Please choose new password</p>	
			  	<?= form_open('login/googleCaptachStore','style="padding-left: 190px;"'); ?>
				<input type="hidden" name="login" value="login">
					<div class="login-form">
						<div class="form-group">
						<input type="password" class="textbox" name="password" placeholder="New Password">
  						</div>
  						<div class="form-group">
							<input type="password" class="textbox" name="password" placeholder="Confirm Password">
						  </div>
						  <div class="form-group">
            </div>
						  <button type="submit" class="btn btn-primary btn-block">Save New Password</button>
						<div class="row">
							<div class="col-md-3">
								</br>
								<a href="home"><b>Help Me!</b></a>
							</div>
							<div class="col-md-3">
							</div>
							<div class="col-md-6">
								</br>
								<a href="/login/index" style="padding-left: 70px;"><b>Back to Login</b></a>
							</div>
						</div>
					</div>
					
				<?= form_close(); ?>
			</div>
		</div>
		
	</div>


	<!-- Bootstrap JS -->
    <script src="<?= base_url('file/js/jquery-3.4.1.min.js'); ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="<?= base_url('file/js/bootstrap.min.js'); ?>" ></script>

    <!-- Custom JS -->
    <script src="<?= base_url('file/js/config.js'); ?>" ></script>
</body>
</html>