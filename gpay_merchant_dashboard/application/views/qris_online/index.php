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
						<h1 class="curr-page">QRIS Online</h1>	
					</div>
				</div>
				<div class="col-md-2">	
					<div class="username text-right">
						Hello, <?= $this->session->userdata('username'); ?>
					</div>
				</div>
			</div>

			<hr>
			<input type="hidden" id="csrf_token" value="<?php echo $this->security->get_csrf_hash();?>">
			<!-- SEARCH PANEL -->
			<div class="row header">
				<div class="col-md-8 d-flex align-items-end" style="padding-left:unset;">
					<p>Total Amount : Rp. <?= html_escape(number_format($total_amounts, 2, ',', '.')); ?></p>	
				</div>
				<div class="col-md-4">		
					<a href="<?= site_url('qris_online/export'); ?>" class="btn btn-light float-right btn-filter" style="
    margin-left: 5px;"><b>Export</b></a>

					<button type="button" data-toggle="modal" data-target="#modal_filter" class="btn btn-light float-right btn-filter">
						<img src="<?= base_url('file/img/filter-ico.png'); ?>">
						<b>Filter</b>
					</button>
				</div>
			</div>
			<?= $this->session->flashdata('message'); ?>
				<?= form_open('transaction/submit_search'); ?>
					<?php $search_param = $this->session->userdata('search_transaction'); ?>
						<input type="hidden" name="simple_transaction_report_search_form" value="simple_transaction_report_search_form">
						
				<?= form_close(); ?>
							
						
						<!-- RESULT DATA -->
						
								<form id="result_form" name="result_form" method="post" action="/qris_online/submit_search" enctype="application/x-www-form-urlencoded">
									<input type="hidden" name="result_form" value="result_form">


									<!-- SIMPLE-TRANSACTIONS-DATATABLE -->
									<table id="table-transaction" class="display" style="width:100%">
											<thead>
												<tr>
													<th>No</th>
													<th>Trx Type</th>
													<th>Trx Role</th>
													<th>Source of Fund</th>
													<th>Timestamp</th>
													<th>Tran No</th>
													<th>Ref Num</th>
													<th>Store</th>
													<th>Terminal ID</th>
													<th>Amount</th>
													<th>MDR Fee</th>
													<th>Fee</th>
													<th>Amount Settle</th>
													<th>Status</th>
												</tr>
											</thead>
											<tbody>
											<?php $no = 1;?>
												<?php foreach($trans_hist as $hist): ?>
												<tr>
													<!--No-->
													<td>
													<?= html_escape($no++); ?>
													</td>
													<!-- TRX TYPE -->
													<td>
														<?= html_escape($hist['TRX_TYPE']); ?>
													</td>
													<!-- Product -->
													<td>
														<?= html_escape($hist['TRX_ROLE']); ?>
													</td>
													<!-- Product -->
													<td>
														<?= html_escape($hist['SOURCE_OF_FUND']); ?>
													</td>
													<!-- Trx. Time -->
													<td>
													<?= html_escape($hist['TIME_REQ']); ?>
													</td>
													<!-- Tran No -->
													<td>
													<?= html_escape($hist['TRAN_NO']); ?>
													</td>
													<!-- Terminal ID -->
													<td>
													<?= html_escape($hist['REFF_NO']); ?>
													</td>
													<!-- Store -->
													<td>
													<?= html_escape($hist['STORE_NAME']); ?>
													</td>
													<!-- Terminal ID -->
													<td>
													<?= html_escape($hist['TERMINAL_ID']); ?>
													</td>
													<!--Ammount -->
													<td class="text-right">
													<?= html_escape($hist['AMOUNT']); ?>
													</td>
													<!-- Ext Reff Num -->
													<td>
														<?= html_escape($hist['MDR_FEE']); ?>
													</td>
													<!-- Ext Reff Num -->
													<td class="text-right">
														<?= html_escape($hist['FEE']); ?>
													</td>
													<!-- Ext Reff Num -->
													<td class="text-right">
														<?= html_escape($hist['AMOUNT_SETTLE']); ?>
													</td>
													<!-- Status -->
													<td>
													<?php if($hist['status'] == "paid"):?>
															<span style='color:green'>
																Paid
															</span>
													<?php elseif($hist['status'] == "refund"): ?>
																<span style='color:green'>
																	Refunded
																</span>
													<?php elseif($hist['status'] == "unpaid"): ?>
																<span style='color:red'>
																	Unpaid
																</span>
													<?php elseif($hist['status'] == "failed"): ?>
																<span style='color:red'>
																	Failed
																</span>
													<?php endif; ?>
													</td>
												</tr>
												<?php endforeach; ?>
											</tbody>
											<tfoot>
												<tr>
													<th>No</th>
													<th>Trx Type</th>
													<th>Trx Role</th>
													<th>Source of Fund</th>
													<th>Timestamp</th>
													<th>Tran No</th>
													<th>Ref Num</th>
													<th>Store</th>
													<th>Terminal ID</th>
													<th>Amount</th>
													<th>MDR Fee</th>
													<th>Fee</th>
													<th>Amount Settle</th>
													<th>Status</th>
												</tr>
											</tfoot>
									</table>
								</form>
							
						<!-- Modal Filter -->
		<?php $this->load->view('qris_online/modal/filter'); ?>
		</div>
	</td>
<script>
$(document).ready(function() {
	$('#table-transaction').DataTable( {
        "dom": '<"top"il>rt<"bottom"fp><"clear">',
    	searching: false,
		"pageLength": 25
	} );
} );
</script>
		

