<?php
		defined('BASEPATH') or exit('No direct script access allowed');
	?>
<td>
	<div class="container right-content">
		<div class="row header">
			<div class="col-md-10">	
				<div class="back">
					<a class="back-button" href="../home/index"><svg width="2em" height="2em" viewBox="0 0 16 16" class="bi bi-arrow-left" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
					</svg></a>
					<h1 class="curr-page">Account</h1>	
				</div>
			</div>
			<div class="col-md-2">	
				<div class="username text-right">
					Hello, <?= $this->session->userdata('username'); ?>
				</div>
			</div>
		</div>
		
		<hr>
		<div class="main-content">
			<div class="initial">
				<div class="row">
					<p class="initial-word"><?php echo substr($this->session->userdata('username'),0,2);?></p>
					<div class="name-label"><?= $this->session->userdata('username'); ?></div>
					<button type="button" data-toggle="modal" data-target="#modal_chg_pwd" class="btn btn-link btn-chg-pwd text-nowrap"><b>Change Password</b></button>
					</div>
				</div>
				</br>
				<div class="row">
					<div class="col-md-6">
						<label for="displayName">Display Name</label>
						<input type="text" class="form-control form-control-lg" value=" <?= $this->session->userdata('username'); ?>" readonly>
					</div>
					<div class="col-md-6">
						<label for="displayName">Full Name</label>
						<input type="text" class="form-control form-control-lg" value="<?= html_escape($merchant->owner_name); ?> " readonly>
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-md-6">
						<label for="displayName">Email Address</label>
						<input type="text" class="form-control form-control-lg" value=" <?= html_escape($merchant->email); ?>" readonly>
					</div>
					<div class="col-md-6">
						<label for="displayName">Phone Number</label>
						<input type="text" class="form-control form-control-lg" value=" <?= html_escape($merchant->phone); ?> " readonly>
					</div>
				</div>
				<br>
				
				<div class="row" style="float: right; padding-right: 30px;">
					<a type="button" class="btn btn-link" href="/home/index"><b>Cancel</b></a>
					<button type="button" class="btn btn-primary">Save</button>
				</div>			
			</div>
		</div>
		
	<!-- Modal Edit -->
	<?php $this->load->view('account/modal/edit'); ?>
	</div>
</td>
<?= $this->session->flashdata('message'); ?>