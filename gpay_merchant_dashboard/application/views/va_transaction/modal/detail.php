<div class="modal fade" id="modal_detail" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
			</div>

			<!-- Modal Body -->
			<?= form_open('ppob_transaction_history/submit_search'); ?>
			<div class="modal-body">
				<?php $search_param = $this->session->userdata('filter_trans_history'); ?>
				<div class="row box-detail-va-payment">
					<div class="col-md-3">
						<p id="total">TOTAL PAYMENT<br></p>
						<b>IDR 123.123</b>
					</div>
					<div class="col-md-3">
						INVOICE<br>
						<b>123123123123</b>
					</div>
					<div class="col-md-3">
						PAYMENT METHOD<br>
						<b>BCA VA</b>
					</div>
					<div class="col-md-3">
						SUCCESS
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
					<div class="box-title-detail">Order Items</div>
						<table class="table">
							<thead>
								<tr>
									<th scope="col">No</th>
									<th scope="col">Item Name</th>
									<th scope="col">QTY</th>
									<th scope="col">Price</th>
									<th scope="col">Subtotal</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<th scope="row">1</th>
									<td>212312321483242340</td>
									<td>1</td>
									<td>@IDR 389.000</td>
									<td>@IDR 389.000</td>
								</tr>
								<tr>
								<td colspan="4" class="text-right"><b>Total</b></td>
								<td><b>@IDR 389.000</b></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
					<ul class="nav nav-tabs" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" href="#cust_info" role="tab" data-toggle="tab">Customer Info</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#add_info" role="tab" data-toggle="tab">Additional Info</a>
						</li>
					</ul>

					<!-- Tab panes -->
					<div class="tab-content">
					<div role="tabpanel" class="tab-pane in active" id="cust_info">
						<div class="row">
							<div class="col-md-4">Buyer Info</div>
							<div class="col-md-8">Billing Address</div>
						</div>
						<div class="row">
							<div class="col-md-4">
								Ren Delan<br>
								rendelan@gmail.com<br>
								628123123
							</div>
							<div class="col-md-8">Billing Address</div>
						</div>
					</div>
					<div role="tabpanel" class="tab-pane fade" id="add_info">Additional Info</div>
					</div>
					</div>
				</div>
			</div>

			<div class="modal-footer">
			</div>
			<?= form_close(); ?>
		</div>
	</div>
	<script>
$.ajax({
  method: "POST",
  url: "detail"
})
  .done(function( response ) {
	
  console.log(response);

  });
</script>
	
	
	