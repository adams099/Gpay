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
						<h1 class="curr-page">Transaction History</h1>	
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
					<a href="<?= site_url('transaction_history/export'); ?>" class="btn btn-light float-right btn-filter" style="
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
						
								<form id="result_form" name="result_form" method="post" action="/transaction_history/submit_search" enctype="application/x-www-form-urlencoded">
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
													<th>Ref Num</th>
													<th>Store Code</th>
													<th>Store</th>
													<th>Terminal ID</th>
													<th>Amount</th>
													<th>MDR Fee</th>
													<th>Fee</th>
													<th>Amount Settle</th>
													<th>Status</th>
												</tr>
											</thead>
											
											<tfoot>
												<tr>
													<th>No</th>
													<th>Trx Type</th>
													<th>Trx Role</th>
													<th>Source of Fund</th>
													<th>Timestamp</th>
													<th>Ref Num</th>
													<th>Store Code</th>
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
		<?php $this->load->view('transaction/modal/filter'); ?>
		</div>
	</td>
	<!-- Script -->
	<script type="text/javascript">
    $(document).ready(function(){
		var t = $('#table-transaction').DataTable({
			"pageLength": 25,
			columnDefs: [
				{
					searchable: false,
					orderable: false,
					targets: 0,
				},
        	],		
			'processing': true,
			'serverSide': true,
			'serverMethod': 'post',
			'searching': false,
			'ajax': {
					'url':'<?=base_url()?>index.php/transaction_history/transList'
					},
          	'columns': [
				{ "defaultContent": "" },
				{ data: 'trans_type' },
				{ data: 'trx_role' },
				{ data: 'source_of_fund' },
				{ data: 'trans_time' },
				{ data: 'rrn' },
				{ data: 'store_code' },
				{ data: 'store_name' },
				{ data: 'terminal_qris_code' },
				{ data: 'amount',
					className: 'dt-body-right',
					render: DataTable.render.number( '.', ',', 2, 'Rp.' ) ,
					orderable: true
				},
				{ data: 'fee' },
				{ data: 'mdr',
					render: function ( data, type, row ) {
						return data + ' %' ;
					} 
				},
				{ data: 'amount_settle',
					className: 'dt-body-right',
					render: DataTable.render.number( '.', ',', 2, 'Rp.' )
				},
				{ data: 'status',
					render: function ( data, type, row ) {
						if (data=='Paid'){
							return '<span style="color:green">Paid</span>';
						} else {
							return '<span style="color:red">Refunded</span>';
						}
					} 	 
				},
          	]
        });

		t.on( 'draw.dt', function () {
    		var PageInfo = $('#table-transaction').DataTable().page.info();
         	t.column(0, { page: 'current' }).nodes().each( function (cell, i) {
            cell.innerHTML = i + 1 + PageInfo.start;
        	});
    	});
     });
     </script>