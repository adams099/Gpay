<div class="modal fade" id="modal_export" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exportDialogHeader">Export to Excel</h5>
			</div>

			<!-- Modal Body -->
			<?= form_open('mandiri_va_transaction_history/export'); ?>
			<div class="modal-body">
				<?php $search_param = $this->session->userdata('filter_trans_history'); ?>
				<div class="col-md-12">
						<div class="form-group">
							Date Start :
							<div class="input-group date">
								<input id="start_date" name="start_date" type="text" >
							</div>
						</div>
						<div class="form-group">
							Date End :
							<div class="input-group date">
								<input id="end_date" name="end_date" type="text" >
							</div>
						</div>
					</div>
			</div>

			<div class="modal-footer">
				<button type="button" id="btnReset" name="btnReset" class="btn btn-link"
					data-dismiss="modal"><b>Reset</b></button>
				<button type="submit" id="btnExport" name="btnExport" class="btn btn-primary">Export Data</button>
			</div>
			<?= form_close(); ?>
		</div>
	</div>
</div>	
	<script>
	
		$('#start_date').datetimepicker({
 			format:'Y-m-d H:i:00',
			 defaultDate: new Date()
 		});

		 $('#end_date').datetimepicker({
 			format:'Y-m-d H:i:00',
			 defaultDate: new Date()
 		});

		document.getElementById("btnExport").onclick = function () {
				var dateStart = $("#start_date").val();
				var dateEnd = $("#end_date").val();
				var request = new XMLHttpRequest();
				console.log(dateStart);
				console.log(dateEnd);
				var url = "<?= base_url('va_transaction_history/export'); ?>";
				request.open('POST', url, true);
				request.send();
			
		};

		document.getElementById("btnReset").onclick = function () {
			console.log("reset");
			$('input[type=checkbox]').prop('checked', false);
			window.location.href = "index";
		};

	</script>
