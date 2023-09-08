<td>
	<div class="container right-content">
		<div class="row header">
			<div class="col-md-10">	
				<div class="back">
					<h1 class="curr-page">Welcome</h1>
				</div>
			</div>
			<div class="col-md-2">	
				<div class="username text-right">
					Hello, <?= $this->session->userdata('username'); ?>
				</div>
			</div>
		</div>
			
		<hr>

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