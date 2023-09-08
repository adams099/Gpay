<div class="modal" id="modal_filter" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="filterDialogHeader">Filter</h5>
			</div>

			<!-- Modal Body -->
			<?= form_open('va_transaction_unpaid/submit_search'); ?>
			<div class="modal-body">
				<?php $search_param = $this->session->userdata('filter_trans_unpaid'); ?>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							Date Start :
							<div class="input-group">
								<input id="tgl_awal" name="tgl_awal" type="text" autocomplete="off">
							</div>
						</div>
						<div class="form-group">
							Date End :
							<div class="input-group">
								<input id="tgl_akhir" name="tgl_akhir" type="text" autocomplete="off">
							</div>
						</div>
						<div class="form-group">
							Customer Name :
							<div class="input-group">
								<input id="customer_name" name="customer_name" type="text" autocomplete="off">
							</div>
						</div>
						<div class="form-group">
							Mobile Phone :
							<div class="input-group">
								<input id="mobile_phone_no" name="mobile_phone_no" type="text" autocomplete="off">
							</div>
						</div>
						<div class="form-group">
							Customer Email :
							<div class="input-group">
								<input id="customer_email" name="customer_email" type="text" autocomplete="off">
							</div>
						</div>
						<div class="form-group">
							VA Number :
							<div class="input-group">
								<input id="va_number" name="va_number" type="text" autocomplete="off">
							</div>
						</div>
					</div>
					<!-- <div class="col-md-3">
						<div class="form-group">
							Store :
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" id="storeAll">
								<label class="custom-control-label" for="storeAll">All</label>
							</div>
							
							<?php foreach($store as $st): ?>
								
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" id="storeFHIstana">
								<label class="custom-control-label"
									for="storeFHIstana"><?= html_escape($st['name']); ?></label>
							</div>
							<?php endforeach; ?>
						</div>
					</div> -->
					<div class="col-md-6">
						<div class="form-group">
							Payment Status :
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" name="payment_all" id="paymentAll">
								<label class="custom-control-label" for="paymentAll">All</label>
							</div>
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" name="payment_paid"
									id="paymentPaid">
								<label class="custom-control-label" for="paymentPaid">Paid</label>
							</div>
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" name="payment_failed"
									id="paymentFailed">
								<label class="custom-control-label" for="paymentFailed">Failed</label>
							</div>
							<div class="custom-control custom-checkbox">
								<input type="checkbox" class="custom-control-input" name="payment_unpaid"
									id="paymentUnpaid">
								<label class="custom-control-label" for="paymentUnpaid">Unpaid</label>
							</div>

							Amount :
							<div>
								<div class="custom-control custom-radio">
									<input type="radio" class="custom-control-input" id="amount_desc" name="amount_sort"
										value="desc" >
									<label class="custom-control-label" for="amount_desc">Desc</label>
								</div>
								<div class="custom-control custom-radio">
									<input type="radio" class="custom-control-input" id="amount_asc" name="amount_sort"
										value="asc">
									<label class="custom-control-label" for="amount_asc">Asc</label>
								</div>
							</div>

							Timestamp :
							<div>
								<div class="custom-control custom-radio">
									<input type="radio" class="custom-control-input" id="timestamp_desc"
										name="timestamp_sort" value="desc" ><label
										class="custom-control-label" for="timestamp_desc">Desc</label>
								</div>
								<div class="custom-control custom-radio">
									<input type="radio" class="custom-control-input" id="timestamp_asc"
										name="timestamp_sort" value="asc"><label
										class="custom-control-label" for="timestamp_asc">Asc</label>
								</div>
							</div>

						</div>

					</div>
				</div>
			</div>
			<div class="modal-footer">
				<a href="#" id="btnReset" name="btnReset" class="btn btn-link"><b>Reset</b></a>
				<button type="submit" id="btnApply" name="btnApply" class="btn btn-primary">Apply
					Filter</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
			<?= form_close(); ?>
		</div>
	</div>
	<script type="text/javascript">
		$('#tgl_awal').datetimepicker({
			timepicker: false,
			format: 'Y-m-d 00:00:00.000'
		});

		$('#tgl_akhir').datetimepicker({
			timepicker: false,
			format: 'Y-m-d 23:59:59.000'
		});

		document.getElementById("btnReset").onclick = function () {
			// console.log("reset");
			$('#tgl_awal').val(null);
			$('#tgl_akhir').val(null);
			$('#customer_name').val(null);
			$('#mobile_phone_no').val(null);
			$('#customer_email').val(null);
			$('input[type=checkbox]').prop('checked', false);
			// window.location.href = "index";
		};

	</script>
</div>
