<div class="modal fade" id="modal_chg_pwd" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title" id="changePwdDialogHeader">Change Your Password</h5>
        </div>

        <!-- Modal Body -->
        <?= form_open('account/submit_change'); ?>
        <div class="modal-body">
        <?php $param = $this->session->userdata('change_pass_param'); ?>
                <div class="form-group">
                    <label for="old-pwd" class="col-form-label">Old Password:</label>
                    <input type="password" class="form-control" id="old_password" name="old_password"> 
                </div>
                <div class="form-group">
                    <label for="new-pwd" class="col-form-label">New Password:</label>
                    <input type="password" class="form-control" id="new_password" name="new_password">
                    <small id="passwordHelpBlock" class="form-text text-muted">Maximum 6 Characters</small>

                </div>
                <div class="form-group">
                    <label for="confirm-pwd" class="col-form-label">Confirm Password:</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    <small id="passwordHelpBlock" class="form-text text-muted">Maximum 6 Characters</small>
                </div>
        </div>
   
        <div class="modal-footer">
            <button type="button" class="btn btn-link" data-dismiss="modal"><b>Cancel</b></button>
            <button type="submit" id="btnChange" name="btnChange" class="btn btn-primary">Change</button>
        </div>
    </div>
    <?= form_close(); ?>
</div>

<script>
		
		document.getElementById("btnChange").onclick = function () {
				
				var request = new XMLHttpRequest();
				var url = "<?= base_url('account/submit_change'); ?>";
				request.open('POST', url, true);
				request.send();
			
		};


	</script>