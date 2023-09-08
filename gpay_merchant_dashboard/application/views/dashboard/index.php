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
						<button type="button" data-toggle="modal" data-target="#modal_filter" class="btn btn-light float-right btn-filter">
							<img src="<?= base_url('file/img/filter-ico.png'); ?>">
							<b>Filter</b>
						</button>
					</div>
				</div>
				<br>
				<div class="form-group row" style="margin-top: 60px;">
					<div class="col-md-6">
						<div class="box-summary">
							<div class="row"><b class="headline-summary">Merchant Summary</b></div> 
							<table class="table">
								<thead class="thead-light">
									<tr>
										<th scope="col">Merchant</th>
										<th scope="col">Transaction Count</th>
										<th scope="col">Transaction Amount</th>
									</tr>
									
									<?php foreach($merch_summ as $summary):?>
									<tr>
										<td scope="row"><?= html_escape($summary['merchant_name']); ?></td>
										<td scope="row"><?= html_escape($summary['transCount']); ?></td>
										<td scope="row"><?= html_escape($summary['amount']); ?></td>
									</tr>
									<?php endforeach; ?>
								</thead>
							</table>
						</div>
					</div>
					<div class="col-md-6">
						<div class="box-transaction">
							<table class="table">
								<thead>
								<?php foreach($issuer_summ as $issuer): ?>
									<tr>
										<th scope="col" class="bg-issuer-gpay">
											<?= html_escape($issuer['source_of_fund']); ?>
										</th>
										<th scope="col">
										<small>Total Volume</small> </br>
										<?= html_escape($issuer['amount']); ?>
										</th>
										<th scope="col">
											<small>Total Count </small></br>
											<?= html_escape($issuer['transCount']); ?>
										</th>
									</tr>
									<?php endforeach; ?>
									
								</thead>
							</table>
						</div>	
					</div>
				</div>
				<div class="row">
					<div class="box-chart">
					<?php 
					$dataPoints = array();
					foreach($sales_summ as $sales){
						
						$phpTimestamp = strtotime(date_format(date_create($sales['trx_time']), 'Y-m-d h:i:sa'));
						$javaScriptTimestamp = $phpTimestamp * 1000;
						array_push($dataPoints, array("x"=> $javaScriptTimestamp, "y"=> $sales['amount']));
					}
					?>

					<?php 
					$dataPoints1 = array();
					foreach($sales_summ_ystrdy as $sales_ystrdy){
						$phpTimestamp = strtotime(date_format(date_create($sales_ystrdy['trx_time']), 'Y-m-d h:i:sa'));
						$javaScriptTimestamp = $phpTimestamp * 1000;
						array_push($dataPoints1, array("x"=> $javaScriptTimestamp, "y"=> $sales_ystrdy['amount']));
					}
					
					?>
					
						
						
					<div id="chartContainer" style="height: 300px; width: 100%;"></div>
				<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
					</div>
					<?= $this->session->flashdata('message'); ?>
				</div>
			</div>
		</div>
	</td>
	<!-- Modal Edit -->
	<?php $this->load->view('dashboard/modal/filter'); ?>

	<script>
window.onload = function () {

var formatString = <?php echo "'".$format."'"; ?>;
var chart = new CanvasJS.Chart("chartContainer", {
	animationEnabled: true,  
	title:{
		text: "Sales Summary"
	},
	width: 1280
	,
	axisY: {
		title: "Sales Volume (Rp)",
		prefix: "Rp"
	},
    axisX:{
		title: "Time",
		valueFormatString: formatString
    },
	data: [{
		type: "splineArea",
		color: "rgba(255, 90, 90, 0.35)",
		markerSize: 5,
		xValueType: "dateTime",
		xValueFormatString: formatString,
        showInLegend: true,
        name:<?php echo "'".$time."'"; ?>,
		dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
	},
    {
		type: "splineArea",
		color: "rgba(54,158,173,.7)",
		markerSize: 5,
		xValueType: "dateTime",
		xValueFormatString: formatString,
        name:<?php echo "'".$last_time."'"; ?>,
        showInLegend: true,
		dataPoints: <?php echo json_encode($dataPoints1, JSON_NUMERIC_CHECK); ?>
	}]
	});
chart.render();

}
</script>