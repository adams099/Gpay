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
						<h1 class="curr-page">Transaction History Virtual Account</h1>	
					</div>
				</div>
				<div class="col-md-2">	
					<div class="username text-right">
						Hello, <?= $this->session->userdata('username'); ?>
					</div>
				</div>
			</div>

			<hr>
			<span class="font-weight-bold"><?= html_escape($offset);?></span>
			<input type="hidden" id="csrf_token" value="<?php echo $this->security->get_csrf_hash();?>">
			<!-- SEARCH PANEL -->
			<a href="<?= site_url('va_transaction_history/export'); ?>" class="btn btn-light float-right btn-filter" style="
    margin-left: 5px;"><b>Export</b></a>

			<button type="button" data-toggle="modal" data-target="#modal_filter" class="btn btn-light float-right btn-filter">
				<img src="<?= base_url('file/img/filter-ico.png'); ?>">
				<b>Filter</b>
			</button>
				
			

			<?= $this->session->flashdata('message'); ?>
				<?= form_open('va_transaction_history/submit_search'); ?>
						<input type="hidden" name="simple_transaction_report_search_form" value="simple_transaction_report_search_form">
						
				<?= form_close(); ?>
							
						
						<!-- RESULT DATA -->
						
								<form id="result_form" name="result_form" method="post" action="/transaction_history/submit_search" enctype="application/x-www-form-urlencoded">
									<input type="hidden" name="result_form" value="result_form">


									<!-- SIMPLE-TRANSACTIONS-DATATABLE -->
									
											<table class="table table-bordered" id="va-trans-hist" style="margin: 10px 0px 5px 0px;" cellspacing="0"
											cellpadding="0" border="0">
												<colgroup span="10"></colgroup>
												<thead>
													<tr>
													<th scope="col" id="result_form:search-native-result-table:j_id369header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id369','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id369header:sortDiv">
																<span>Invoice</span>
															</div>
														</th>	
													<th scope="col" id="result_form:search-native-result-table:j_id369header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id369','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id369header:sortDiv">
																<span>Mobile Phone No</span>
															</div>
														</th>
														<th scope="col" id="result_form:search-native-result-table:j_id369header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id369','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id369header:sortDiv">
																<span>VA Number</span>
															</div>
														</th>
														<th scope="col" id="result_form:search-native-result-table:j_id378header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id378','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id378header:sortDiv">
																<span>Customer Name</span>
															</div>
														</th>
														<th scope="col" id="result_form:search-native-result-table:j_id378header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id378','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id378header:sortDiv">
																<span>Customer Email</span>
															</div>
														</th>
														<th scope="col" id="result_form:search-native-result-table:j_id381header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id381','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id381header:sortDiv">
																<span>Timestamp</span>
															</div>
														</th>
														<th scope="col" id="result_form:search-native-result-table:j_id393header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id393','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id393header:sortDiv">
																<span>Amount</span>
															</div>
														</th>
														<th scope="col" id="result_form:search-native-result-table:j_id404header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id404','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id404header:sortDiv">
																<span>Payment Channel</span>
															</div>
														</th>
														<th scope="col" id="result_form:search-native-result-table:j_id404header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id404','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id404header:sortDiv">
																<span>Bank Issuer</span>
															</div>
														</th>
														<th scope="col" id="result_form:search-native-result-table:j_id416header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id416','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id416header:sortDiv">
																<span class="rich-table-sortable-header">Status</span>
															</div>
														</th>
													</tr>
												</thead>
												<tfoot>
													<tr class="rich-table-footer">
														<td class="rich-table-footercell" colspan="10" scope="colgroup">
															<div class="rich-datascr" style="" align="center">
																<table border="0" cellpadding="0" cellspacing="1" class="rich-dtascroller-table" style="text-align:center">
																	<tbody>
																		<?= $this->pagination->create_links(); ?>
																	</tbody>
																</table>
															</div>
														</td>
													</tr>
												</tfoot>
												<tbody id="result_form:search-native-result-table:tb">												
													<?php foreach($trans_hist as $hist):?>
															<tr class="rich-table-row rich-table-firstrow">
																<!--No-->
																<td >
																<?= html_escape($hist['order_number']); ?>
																</td>
																<!-- Trx. Time -->
																<td >
																<?= html_escape($hist['cust_phone']); ?>
																</td>
																<!-- Product -->
																<td >
																	<!-- <a href="#modal_detail" data-whatever="@mdo" data-toggle="modal" data-target="#modal_detail"><?= html_escape($hist['va_number']); ?></a> -->
																	<?= html_escape($hist['va_number']); ?>
																</td>
																<!-- Terminal ID -->
																<td >
																<?= html_escape($hist['customer_name']); ?>
																</td>
																<!-- Terminal ID -->
																<td >
																<?= html_escape($hist['cust_email']); ?>
																</td>
																<!-- Store -->
																<td >
																<?php if(!empty($hist['time_trx'])){ echo html_escape(date_format(date_create($hist['time_trx']), 'd/m/Y H:i:s')); }?>
																</td>
																<!--Ammount -->
																<td style="white-space:nowrap;">
																IDR. <?= html_escape($hist['amount']); ?>
																</td>
																<!-- Reff Num -->
																<td >
																<?= html_escape($hist['bca_payment_channel']); ?>
																</td>
																<!-- Reff Num -->
																<td >
																<?= html_escape($hist['bank_issuer']); ?>
																</td>
																<!-- Status -->
																<td >
																<?php if($hist['response_message'] == "Paid"):?>
																		<span style='color:green'>
																			Paid
																		</span>
																<?php elseif($hist['response_message'] == "Failed"): ?>
																			<span style='color:red'>
																			Failed
																			</span>
																<?php elseif($hist['response_message'] == "Unpaid"): ?>
																			<span style='color:red'>
																			Unpaid
																			</span>
																<?php elseif($hist['response_message'] == "Canceled"): ?>
																			<span style='color:red'>
																			Canceled
																			</span>
																<?php endif; ?>
																</td>
															</tr>
															<?php endforeach; ?>
														
												</tbody>
											</table>
											
									<input type="hidden" name="javax.faces.ViewState" id="javax.faces.ViewState" value="j_id7" autocomplete="off">
								</form>
							<!-- Modal Filter -->
							<?php $this->load->view('va_transaction/modal/filter'); ?>							

		</div>
		</td>
		<script>
//     $(document).ready( function () {
//       $('#va-trans-hist').DataTable();
//   } );
</script>
		
		
		

