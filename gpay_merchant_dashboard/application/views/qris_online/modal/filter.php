<div class="modal fade" id="modal_filter" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterDialogHeader">Filter</h5>
            </div>

        <!-- Modal Body -->
        <?= form_open('qris_online/submit_search'); ?>
        <div class="modal-body">
            <?php $search_param = $this->session->userdata('filter_trans_history'); ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        Date Start :
                        <div class="input-group date">
                            <input id="tgl_awal" name="tgl_awal" type="text" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group">
                        Date End :
                        <div class="input-group date">
                            <input id="tgl_akhir" name="tgl_akhir" type="text" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        Amount From :
                        <div class="input-group date">
                            <input id="amount_fr" class="text-right" name="amount_fr" type="number">
                        </div>
                    </div>
                    <div class="form-group">
                        Amount To :
                        <div class="input-group date">
                            <input id="amount_to" class="text-right"  name="amount_to" type="number">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                <div class="form-group">
                        Tran No :
                        <div class="input-group date">
                            <input id="tranno" name="tranno" type="text">
                        </div>
                    </div>
                    <div class="form-group">
                        RefNum :
                        <div class="input-group date">
                            <input id="refnum" name="refnum" type="text">
                        </div>
                    </div>
                    <div class="form-group">
                        Source Of Fund :
                        <div class="input-group date">
                            <input id="source_of_fund" name="source_of_fund" type="text">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        Payment Status :
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="status_filter[]" id="paymentAll" value="All" checked>
                            <label class="custom-control-label" for="paymentAll">All</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="status_filter[]" id="paymentPaid" value="paid">
                            <label class="custom-control-label" for="paymentPaid">Paid</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="status_filter[]" id="paymentFailed" value="failed">
                            <label class="custom-control-label" for="paymentFailed">Failed</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="status_filter[]" id="paymentRefund" value="refund">
                            <label class="custom-control-label" for="paymentRefund">Refunded</label>
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
var dateNow = new Date();
$('#tgl_awal').datetimepicker({
    // format:'Y-m-d 00:00:00',
    format: 'Y-m-d H:i:s',
    formatTime:'H:i:s',
    defaultTime : '00:00:00',
    timepicker : false
});

$('#tgl_akhir').datetimepicker({
    format: 'Y-m-d H:i:s',
    formatTime:'H:i:s',
    defaultTime : '23:59:59',
    timepicker : false
});

// $("#merchantAll").click(function(){
//     $('.merchant-filter').prop("checked",this.checked);
//     // $('.merchant-filter').click();
// });


document.getElementById("btnApply").onclick = function () { 
    $.each($("input[name='dateFilter']:checked"), function(){
        var dateFilter = $(this).val();
        var request = new XMLHttpRequest();
        var  url = "<?= base_url('transaction_history/submit_search'); ?>";
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