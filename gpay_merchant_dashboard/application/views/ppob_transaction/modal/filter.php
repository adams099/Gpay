<div class="modal fade" id="modal_filter" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="filterDialogHeader">Filter</h5>
			</div>

			<!-- Modal Body -->
			<?= form_open('ppob_transaction_history/submit_search'); ?>
			<div class="modal-body">
				<?php $search_param = $this->session->userdata('filter_trans_history'); ?>
				<div class="row">
					<div class="col-md-3">
						<div class="form-group">
							Date :
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" id="today" value="today"
									name="dateFilter">
								<label class="custom-control-label" for="today">Today</label>
							</div>
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" id="weekly" value="weekly"
									name="dateFilter">
								<label class="custom-control-label" for="weekly">Weekly</label>
							</div>
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" id="monthly" value="monthly"
									name="dateFilter">
								<label class="custom-control-label" for="monthly">Monthly</label>
							</div>
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							Store :
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" id="storeAll">
								<label class="custom-control-label" for="storeAll">All</label>
							</div>
							<? foreach($store as $st): ?>
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" id="storeFHIstana">
								<label class="custom-control-label"
									for="storeFHIstana"><?= html_escape($st['name']); ?></label>
							</div>
							<? endforeach; ?>
						</div>
					</div>
					<div class="col-md-3">
						<div class="form-group">
							Payment Status :
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" id="paymentAll">
								<label class="custom-control-label" for="paymentAll">All</label>
							</div>
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" id="paymentPaid">
								<label class="custom-control-label" for="paymentPaid">Paid</label>
							</div>
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" id="paymentFailed">
								<label class="custom-control-label" for="paymentFailed">Failed</label>
							</div>
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" id="paymentRefunded">
								<label class="custom-control-label" for="paymentRefunded">Refunded</label>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="modal-footer">
				<button type="button" id="btnReset" name="btnReset" class="btn btn-link"
					data-dismiss="modal"><b>Reset</b></button>
				<button type="submit" id="btnApply" name="btnApply" class="btn btn-primary">Apply Filter</button>
			</div>
			<?= form_close(); ?>
		</div>
	</div>
	<script>
		document.getElementById("btnApply").onclick = function () {
			$.each($("input[name='dateFilter']:checked"), function () {
				var dateFilter = $(this).val();
				var request = new XMLHttpRequest();
				var url = "<?= base_url('ppob_transaction_history/submit_search'); ?>";
				request.open('POST', url, true);
				request.send();
			});
		};

		document.getElementById("btnReset").onclick = function () {
			console.log("reset");
			$('input[type=checkbox]').prop('checked', false);
			window.location.href = "index";
		};

	</script>
