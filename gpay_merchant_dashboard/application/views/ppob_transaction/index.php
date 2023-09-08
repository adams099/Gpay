	<?php
		defined('BASEPATH') or exit('No direct script access allowed');
	?>
		<td>
			<div id="help_button">
			Hello, <?= $this->session->userdata('username'); ?>
			</div>
			<div class="row">
				<a href="../home/index"><svg width="2em" height="2em" viewBox="0 0 16 16" class="bi bi-arrow-left" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
				</svg></a>
				<h1 style="padding-left: 10px;">Transaction History</h1>
			</div>

			<hr>
			<input type="hidden" id="csrf_token" value="<?php echo $this->security->get_csrf_hash();?>">
			<!-- SEARCH PANEL -->
			<button type="button" data-toggle="modal" data-target="#modal_filter" class="btn btn-light float-right btn-filter"><b>Filter</b></button>
			<?= $this->session->flashdata('message'); ?>
				<?= form_open('ppob_transaction_history/submit_search'); ?>
						<input type="hidden" name="simple_transaction_report_search_form" value="simple_transaction_report_search_form">
						<div class="row">
						<? foreach($total_amount as $totalAmt): ?>
							<div class="amount-box">
								<p>Total amount for this period</p>
								<b>Rp.<?= html_escape($totalAmt['amount']); ?></b>
							</div>
						<?endforeach?>
						<? foreach($total_trans as $totalTrans): ?>
							<div class="transaction-box float-right">
								<p>Total Transaction</p>
								<b>Rp.<?= html_escape($totalTrans['amount']); ?></b>
							</div>
						<?endforeach?>
						</div>
				<?= form_close(); ?>
							
						
						<!-- RESULT DATA -->
						
								<form id="result_form" name="result_form" method="post" action="/transaction_history/submit_search" enctype="application/x-www-form-urlencoded">
									<input type="hidden" name="result_form" value="result_form">


									<!-- SIMPLE-TRANSACTIONS-DATATABLE -->
									
											<table class="rich-table " id="result_form:search-native-result-table" style="margin: 10px 0px 5px 0px;" cellspacing="0"
											cellpadding="0" border="0">
												<colgroup span="13"></colgroup>
												<thead class="rich-table-thead">
													<tr class="rich-table-subheader ">
													<th class="rich-table-subheadercell  " scope="col" id="result_form:search-native-result-table:j_id369header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id369','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id369header:sortDiv">
																<span class="rich-table-sortable-header">No</span>
															</div>
														</th>	
													<th class="rich-table-subheadercell  " scope="col" id="result_form:search-native-result-table:j_id369header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id369','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id369header:sortDiv">
																<span class="rich-table-sortable-header">Timestamp</span>
															</div>
														</th>
														<th class="rich-table-subheadercell  " scope="col" id="result_form:search-native-result-table:j_id369header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id369','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id369header:sortDiv">
																<span class="rich-table-sortable-header">Issuer</span>
															</div>
														</th>
														<th class="rich-table-subheadercell  " scope="col" id="result_form:search-native-result-table:j_id378header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id378','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id378header:sortDiv">
																<span class="rich-table-sortable-header">Terminal ID</span>
															</div>
														</th>
														<th class="rich-table-subheadercell  " scope="col" id="result_form:search-native-result-table:j_id381header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id381','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id381header:sortDiv">
																<span class="rich-table-sortable-header">Store</span>
															</div>
														</th>
														<th class="rich-table-subheadercell  " scope="col" id="result_form:search-native-result-table:j_id393header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id393','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id393header:sortDiv">
																<span class="rich-table-sortable-header">Amount</span>
															</div>
														</th>
														<th class="rich-table-subheadercell  " scope="col" id="result_form:search-native-result-table:j_id404header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id404','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id404header:sortDiv">
																<span class="rich-table-sortable-header">Ref Num</span>
															</div>
														</th>
														<th class="rich-table-subheadercell  " scope="col" id="result_form:search-native-result-table:j_id407header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id407','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id407header:sortDiv">
																<span class="rich-table-sortable-header">Ext Ref Num</span>
															</div>
														</th>
														<th class="rich-table-subheadercell  " scope="col" id="result_form:search-native-result-table:j_id416header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id416','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
														style="cursor: pointer;">
															<div id="result_form:search-native-result-table:j_id416header:sortDiv">
																<span class="rich-table-sortable-header">Status</span>
															</div>
														</th>
													</tr>
												</thead>
												<tfoot>
													<tr class="rich-table-footer">
														<td class="rich-table-footercell" colspan="13" scope="colgroup">
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
													<? $no = 1;
													?>
													<? foreach($trans_hist as $hist): ?>
															
															<tr class="rich-table-row rich-table-firstrow">
																<!--No-->
																<td class="rich-table-cell">
																<?= html_escape($no++); ?>
																</td>
																<!-- Trx. Time -->
																<td class="rich-table-cell">
																<?= html_escape(date_format(date_create($hist['trx_time']), 'd/m/Y H:i:s')); ?>
																</td>
																<!-- Product -->
																<td class="rich-table-cell">
																	<?= html_escape($hist['source_of_fund']); ?>
																</td>
																<!-- Terminal ID -->
																<td class="rich-table-cell">
																<?= html_escape($hist['term_id']); ?>
																</td>
																<!-- Store -->
																<td class="rich-table-cell">
																<?= html_escape($hist['store_name']); ?>
																</td>
																<!--Ammount -->
																<td class="rich-table-cell">
																IDR. <?= html_escape($hist['amount']); ?>
																</td>
																<!-- Reff Num -->
																<td class="rich-table-cell">
																<?= html_escape($hist['edc_refnum']); ?>
																</td>
																<!-- Ext Reff Num -->
																<td class="rich-table-cell">
																	<?= html_escape($hist['issuer_refnum']); ?>
																</td>
																<!-- Status -->
																<td class="rich-table-cell">
																<?if($hist['status'] == "paid"):?>
																		<span style='color:green'>
																			Paid
																		</span>
																<? elseif($hist['status'] == "refund"): ?>
																			<span style='color:red'>
																				Refunded
																			</span>
																<? elseif($hist['status'] == "failed"): ?>
																			<span style='color:red'>
																				Failed
																			</span>
																<? endif; ?>
																</td>
															</tr>
															<? endforeach; ?>
														
												</tbody>
											</table>
										
											
											<a href="<?= site_url('ppob_transaction_history/export/'.$this->uri->segment(3)); ?>">Export to XLS file</a>
									

									
									<input type="hidden" name="javax.faces.ViewState" id="javax.faces.ViewState" value="j_id7" autocomplete="off">
								</form>
							
						<!-- Modal Filter -->
		<? $this->load->view('ppob_transaction/modal/filter'); ?>
		</td>
		

