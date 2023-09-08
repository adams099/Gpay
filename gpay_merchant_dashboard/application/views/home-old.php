<td>
	<div class="container right-content">
		<div class="row header">
			<div class="col-md-10">	
				<div class="back">
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
	<div width="100%">
		<div class="row justify-content-md-center">
			<div class="col-md-12">
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
		</div>
	
		<div class="row content-md-left mt-3">
			<div class="col-md-6">
				<div class="box-va">
					<table class="table">
						<thead>
							<tr>
								<th scope="col" class="bg-va-bca">
									VA BCA
								</th>
								<th scope="col">
									<small>Total Volume</small> </br>
									Rp.10.000.000
								</th>
								<th scope="col">
									<small>Total Count </small></br>
									0
								</th>
							</tr>
							<tr>
								<th scope="col" class="bg-va-mandiri">
									VA Mandiri
								</th>
								<th scope="col">
									<small>Total Volume</small> </br>
									Rp.10.000.000
								</th>
								<th scope="col">
									<small>Total Count </small></br>
									0
								</th>
							</tr>
							<tr>
								<th scope="col" class="bg-va-cimb">
									VA CIMB NIAGA
								</th>
								<th scope="col">
									<small>Total Volume</small> </br>
									Rp.10.000.000
								</th>
								<th scope="col">
									<small>Total Count </small></br>
									0
								</th>
							</tr>
							<tr>
								<th scope="col" class="bg-va-bni">
									VA BNI
								</th>
								<th scope="col">
									<small>Total Volume</small> </br>
									Rp.10.000.000
								</th>
								<th scope="col">
									<small>Total Count </small></br>
									0
								</th>
							</tr>
							<tr></tr>
							
						</thead>
					</table>
				</div>	
			</div>
			<div class="col-md-6">
				<div class="box-chart-pie">
				<?php
					$dataPoints = array( 
						array("label"=>"Pulsa", "y"=>51.7),
						array("label"=>"Paket Data", "y"=>26.6),
						array("label"=>"BPJS Kesehatan", "y"=>13.9),
						array("label"=>"Token Listrik", "y"=>7.8)
					)
	 
				 ?>
 					<div id="chartContainer" style="height: 250px; width: 100%;"></div>
				</div>	
			</div>
		</div>
        <?= $this->session->flashdata('message'); ?>
	</div>

	</div>
</td>
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
<script>
window.onload = function () {
 
	var chart = new CanvasJS.Chart("chartContainer", {
	theme: "light2",
	animationEnabled: true,
	data: [{
		type: "pie",
		indexLabel: "{y}",
		yValueFormatString: "#,##0.00\"%\"",
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