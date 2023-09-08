<div class="modal fade" id="modal_filter" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title" id="filterDialogHeader">Filter</h5>
        </div>

        <!-- Modal Body -->
		<?= form_open('dashboard/submit_search'); ?>
        <div class="modal-body">
		<?php $search_param = $this->session->userdata('filter_dashboard'); ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                    <b>Date :</b>
                        <div class="custom-control custom-checkbox">
							<input type="checkbox" class="custom-control-input" value="today" id="today" name="dateFilter" <?= $search_param['dateFilter']="today";?>> 
                            <label class="custom-control-label" for="today">Today</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" value="weekly" id="weekly" name="dateFilter">
                            <label class="custom-control-label" for="weekly">Weekly</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" value="monthly" id="monthly" name="dateFilter">
                            <label class="custom-control-label" for="monthly">Monthly</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <b>Issuer :</b>
                        <div class="custom-control custom-checkbox">
							<input type="checkbox" class="custom-control-input" id="issuerAll" value="All" name="all" <?= $search_param['all']="All";?>>
                            <label class="custom-control-label" for="issuerAll">All</label>
                        </div>
                        <div class="custom-control custom-checkbox">
							<input type="checkbox" class="custom-control-input" id="issuerGpay" value="GPay" name="gpay">
                            <label class="custom-control-label" for="issuerGpay">GPay</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="issuerShopeePay" value="ShopeePay" name="shopeepay">
                            <label class="custom-control-label" for="issuerShopeePay">ShopeePay</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="issuerOvo" value="Ovo" name="ovo">
                            <label class="custom-control-label" for="issuerOvo">OVO</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="issuerLinkAja" value="LinkAja" name="linkaja">
                            <label class="custom-control-label" for="issuerLinkAja">LinkAja</label>
                        </div>
                    </div>
                </div>     
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" id="btnReset" name="btnReset" class="btn btn-link" data-dismiss="modal"><b>Reset</b></button>
            <button type="submit" id="btnApply" name="btnApply" class="btn btn-primary">Apply Filter</button>
        </div>
		<?= form_close(); ?>
    </div>
</div>
<script>
document.getElementById("btnApply").onclick = function () { 
    $.each($("input[name='dateFilter']:checked"), function(){
        var dateFilter = $(this).val();
        var request = new XMLHttpRequest();
        var  url = "<?= base_url('dashboard/submit_search'); ?>";
       
        request.open('POST', url, true);
        request.send();
            });
    };

    document.getElementById("btnReset").onclick = function () { 
        console.log("reset");
        $('input[type=checkbox]').prop('checked',false);
        window.location.href="index";
    };
</script>