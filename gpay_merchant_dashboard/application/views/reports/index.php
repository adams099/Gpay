<td>
	<div class="body" width="100%">
	<div class="row">
		<div class="col-md-4">
		<a href="<?= site_url('dashboard/index'); ?>">
			<div class="menu-dash">
				<img src="../file/img/icon-dashboard.png" alt="Dashboard" style="width: 200px;">
				<p class="menu-title">Dashboard</p>
			</div>
		</div>
		<div class="col-md-4">
		<a href="<?= site_url('transaction_history/index'); ?>">
			<div class="menu-trans">
				<img src="../file/img/icon-transaction-history.png" alt="Transaction History" style="width: 200px;">
				<p class="menu-title">Transaction History</p>
			</div>
		</div>
		<div class="col-md-4">
		<a href="<?= site_url('reports/index'); ?>">
			<div class="menu-report">
				<img src="../file/img/icon-report.png" alt="Reports" style="width: 200px;">
				<p class="menu-title">Reports</p>
			</div>
		</div>
	
	
	
	</div>
	
        <?= $this->session->flashdata('message'); ?>
	</div>
</td>