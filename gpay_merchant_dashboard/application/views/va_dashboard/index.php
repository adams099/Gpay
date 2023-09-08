<?php
		defined('BASEPATH') or exit('No direct script access allowed');
		
	?>
	<td>
		<?php $search_param = $this->session->userdata('search_transaction'); ?>
		<div class="container right-content">
			<div class="row header">
				<div class="col-md-10">	
					<div class="back">
						<a class="back-button" href="../home/index"><svg width="2em" height="2em" viewBox="0 0 16 16" class="bi bi-arrow-left" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
						</svg></a>
						<h1 class="curr-page">Dashboard</h1>	
					</div>
				</div>
				<div class="col-md-2">	
					<div class="username text-right">
						Hello, <?= $this->session->userdata('username'); ?>
					</div>
				</div>
			</div>
			<hr>
			<div class="body" width="100%" style="padding: 0 0 0 0;">
				<div class="form-row float-right" style="margin-right: 20px;">
					<div class="form-group mx-sm-3 mb-2">			
						<!-- <button type="button" data-toggle="modal" data-target="#modal_filter" class="btn btn-light float-right btn-filter">
							<img src="<?= base_url('file/img/filter-ico.png'); ?>">
							<b>Filter</b>
						</button> -->
					</div>
				</div>
				<br>
				<div class="form-group row" style="margin-top: 60px;">
					<div class="col-md-6">
						<div class="row">
							<div class="box-sales-va bg-box-va">
								<div class="box-va-summary">
								Sum of transaction this month<br/>
								<?php foreach($sum_trx as $sum):?>
									IDR  <?php echo $sum['sum'];?><br/>
								<?php endforeach; ?>
								<?php foreach($count_trx as $count):?>
									Success <?php echo $count['count_trx'];?> transactions
								<?php endforeach; ?>
								</div> 
								<div class="row">
								<div class="box-va-bystatus-new">
								<?php foreach($status_new as $new):?>
									IDR <?php echo $new['sum'];?><br/>
									<?php echo $new['count_trx'];?> Transactions
								<?php endforeach; ?>
								</div>
								<div class="box-va-bystatus-fail">
								<?php foreach($status_fail as $fail):?>
									IDR <?php echo $fail['sum'];?><br/>
									<?php echo $fail['count_trx'];?> Transactions
								<?php endforeach; ?>
								</div>
								<div class="box-va-bystatus-void">
								<?php foreach($status_void as $void):?>
									IDR <?php echo $void['sum'];?><br/>
									<?php echo $void['count_trx'];?> Transactions
								<?php endforeach; ?>
								</div>
								</div>
								
							</div>
						</div>
						
					</div>
					
					
					<div class="col-md-6">
						<div class="box-chart-pie">
							<?php
							foreach($count_trx as $count):
								 
								 $dataPoints = array( 
									array("label"=>"BCA VA", "y"=>$count['count_trx']),
									array("label"=>"BNI VA", "y"=>0),
									array("label"=>"CIMB VA", "y"=>0),
									array("label"=>"Mandiri VA", "y"=>0),
								 );
							 endforeach; 
								
							?>
							<div id="chartContainer" style="height: 250px; width: 100%;"></div>
						</div>	
					</div>
			</div>
			<form id="result_form" name="result_form" method="post" action="/transaction_history/submit_search" enctype="application/x-www-form-urlencoded">
				<input type="hidden" name="result_form" value="result_form">
				<h4>Top 10 Latest Transaction</h3>
					<table class="table " id="result_form:search-native-result-table" style="margin: 10px 0px 5px 0px;" cellspacing="0" cellpadding="0">
						<colgroup span="13"></colgroup>
							<thead class="thead-light">
								<tr >
									<th  scope="col" id="result_form:search-native-result-table:j_id369header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id369','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )"
										style="cursor: pointer;">
										<div id="result_form:search-native-result-table:j_id369header:sortDiv">
											<span >No</span>
										</div>
									</th>	
									<th  scope="col" id="result_form:search-native-result-table:j_id369header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id369','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )" style="cursor: pointer;">
										<div id="result_form:search-native-result-table:j_id369header:sortDiv">
											<span >Status</span>
										</div>
									</th>
									<th  scope="col" id="result_form:search-native-result-table:j_id369header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id369','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )" 									style="cursor: pointer;">
										<div id="result_form:search-native-result-table:j_id369header:sortDiv">
											<span >Timestamp</span>
										</div>
									</th>
									<th  scope="col" id="result_form:search-native-result-table:j_id378header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id378','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )" style="cursor: pointer;">
										<div id="result_form:search-native-result-table:j_id378header:sortDiv">
											<span >VA Number</span>
										</div>
									</th>
									<th  scope="col" id="result_form:search-native-result-table:j_id381header" onclick="A4J.AJAX.Submit('result_form',event,{'similarityGroupingId':'result_form:search\x2Dnative\x2Dresult\x2Dtable','parameters':{'fsp':'result_form:search\x2Dnative\x2Dresult\x2Dtable:j_id381','result_form:search\x2Dnative\x2Dresult\x2Dtable':'fsp'} } )" style="cursor: pointer;">
										<div id="result_form:search-native-result-table:j_id381header:sortDiv">
											<span >Amount</span>
										</div>
									</th>
								</tr>
							</thead>
							<!-- <tfoot>
								<tr class="rich-table-footer">
									<td class="rich-table-footercell" colspan="14" scope="colgroup">
										<div class="rich-datascr" style="" align="center">
											<table border="0" cellpadding="0" cellspacing="1" class="rich-dtascroller-table" style="text-align:center">
												<tbody>
													<?= $this->pagination->create_links(); ?>
												</tbody>
											</table>
										</div>
									</td>
								</tr>
							</tfoot> -->
							<tbody id="result_form:search-native-result-table:tb">
							<?php $no = 1;?>
								<?php foreach($trans_hist as $hist):?>
										<tr>
											<!--No-->
											<td >
											<?= html_escape($no++); ?>
											</td>
											<!-- Status -->
											<td >
											<?php if($hist['response_message'] == "Success"):?>
													<span style='color:green'>
														Success
													</span>
											<?php elseif($hist['response_message'] == "Failed"): ?>
														<span style='color:red'>
															Fail
														</span>
											<?php elseif($hist['response_message'] == "Void"): ?>
														<span style='color:red'>
															Void
														</span>
											<?php endif; ?>
											</td>
											<!-- Timestamp -->
											<td >
											<?= html_escape(date_format(date_create($hist['time_trx']), 'd/m/Y H:i:s')); ?>
											</td>
											<!-- VA Number -->
											<td >
												<?= html_escape($hist['va_number']); ?>
											</td>
											<!--Ammount -->
											<td >
											IDR. <?= html_escape($hist['amount']); ?>
											</td>
										</tr>
										<?php endforeach; ?>
							</tbody>
						</table>
					<input type="hidden" name="javax.faces.ViewState" id="javax.faces.ViewState" value="j_id7" autocomplete="off">
				</form>
		</div>
	</td>
	<!-- Modal Edit -->
	<?php $this->load->view('dashboard/modal/filter'); ?>

<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
<script>
window.onload = function () {
 
	var chart = new CanvasJS.Chart("chartContainer", {
	theme: "light2",
	animationEnabled: true,
	data: [{
		type: "pie",
		indexLabel: "{y}",
		yValueFormatString: "#,##\"Trx\"",
		indexLabelPlacement: "inside",
		indexLabelFontColor: "#36454F",
		indexLabelFontSize: 18,
		indexLabelFontWeight: "bolder",
		showInLegend: true,
		legendText: "{label}",
		dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
	}]
});
chart.render();
 
}
</script>