<div class="modal fade" id="modal_filter" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg m-auto" role="document">
        <div class="modal-content p-4 rounded ">
            <div class="modal-header">
                <h5 class="modal-title" id="filterDialogHeader">Filter</h5>
            </div>

            <!-- Modal Body -->
            <?= form_open('transaction_history/submit_search'); ?>
            <div class="modal-body">
                <?php $search_param = $this->session->userdata('filter_trans_history'); ?>

                <!-- row 1 -->
                <div class="row row-lg-12">
                    <div class="col">
                        <div class="col-md-12">
                            <div class="form-group">
                                Date Start :
                                <div class="input-group date mt-1 mt-1">
                                    <input id="tgl_awal" class="p-2 rounded col col-12" name="tgl_awal" type="text" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                Date End :
                                <div class="input-group date mt-1">
                                    <input id="tgl_akhir" class="p-2 rounded col col-12" name="tgl_akhir" type="text" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                RefNum :
                                <div class="input-group date mt-1">
                                    <input id="refnum" class="p-2 rounded col col-12" name="refnum" type="text">
                                </div>
                            </div>
                            <!-- source of fund -->
                            <div class="form-group">
                                Source Of Fund :
                                <div class="input-group date mt-1">
                                    <input id="refnum" class="p-2 rounded col col-12" name="sof" id="sof" list="sof-list" placeholder="type to search.." type="text" autocomplete="off">
                                    <datalist id="sof-list">
                                        <?php foreach ($sof as $dt) : ?>
                                            <option value="<?= html_escape($dt['product_name']); ?>"></option>
                                        <?php endforeach ?>
                                    </datalist>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="col-md-12">
                            <div class="form-group">
                                Amount From :
                                <div class="input-group date mt-1">
                                    <input id="amount_fr" class="text-right p-2 rounded col col-12" name="amount_fr" type="number">
                                </div>
                            </div>
                            <div class="form-group">
                                Amount To :
                                <div class="input-group date mt-1">
                                    <input id="amount_to" class="text-right p-2 rounded col col-12" name="amount_to" type="number">
                                </div>
                            </div>
                            <div class="form-group">
                                Payment Status :
                                <!-- <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="status_filter[]" id="paymentAll" value="All" checked>
                                    <label class="custom-control-label" for="paymentAll">All</label>
                                </div> -->
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" name="status_filter[]" id="paymentPaid" value="payment" checked>
                                    <label class="custom-control-label" for="paymentPaid">Paid</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="status_filter[]" id="paymentRefund" value="refund" checked>
                                    <label class="custom-control-label" for="paymentRefund">Refund</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="col-md-12">

                            <!-- merchant Group -->
                            <div class="form-group">
                                Merchant Group :
                                <div class="input-group mt-1">
                                    <div class="multiselect">
                                        <div class="selectBox" onclick="showCheckboxes('merchantGroup')">
                                            <select>
                                                <option id="optionText_merchantGroup">Select Group</option>
                                            </select>
                                            <div class="overSelect"></div>
                                        </div>

                                        <div id="checkboxes_merchantGroup">
                                            <div class="custom-control custom-checkbox mt-1 ml-2">
                                                <input type="checkbox" class="custom-control-input" id="merchantGroup" value="all" checked>
                                                <label class="custom-control-label" for="merchantGroup">All</label>
                                            </div>
                                            <?php foreach ($merch_group as $dt) : ?>
                                                <div class="custom-control custom-checkbox mt-1 ml-2">
                                                    <input type="checkbox" class="custom-control-input merchGroup-filter" name="merchGroup_filter[]" id="merchGroup_<?= html_escape($dt['dsc']); ?>" value="<?= html_escape($dt['dsc']); ?>" checked>
                                                    <label class="custom-control-label" name="merchGroup_filter[]" for="merchGroup_<?= html_escape($dt['dsc']); ?>"><?= html_escape($dt['dsc']); ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- sbu -->
                            <div class="form-group">
                                SBU :
                                <div class="input-group mt-1">
                                    <div class="multiselect">
                                        <div class="selectBox" onclick="showCheckboxes('sbu')">
                                            <select>
                                                <option id="optionText_sbu">Select SBU</option>
                                            </select>
                                            <div class="overSelect"></div>
                                        </div>
                                        <div id="checkboxes_sbu">
                                            <div class="custom-control custom-checkbox mt-1 ml-2">
                                                <input type="checkbox" class="custom-control-input" id="sbu-all" value="all" checked onchange="updateSelectOption('sbu', this)">
                                                <label class="custom-control-label" for="sbu-all">All</label>
                                            </div>

                                            <?php foreach ($sbu as $dt) : ?>
                                                <div class="custom-control custom-checkbox mt-1 ml-2">
                                                    <input type="checkbox" class="custom-control-input sbu-filter" name="sbu_filter[]" id="sbu_<?= html_escape($dt['name']); ?>" value="<?= html_escape($dt['name']); ?>" checked>
                                                    <label class="custom-control-label" name="sbu_filter[]" for="sbu_<?= html_escape($dt['name']); ?>"><?= html_escape($dt['name']); ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- row 2 -->
                    <div class="container overflow-hidden mt-4">
                        <div class="row gx-5">
                            <div class="col md-8">
                                <div class="input-group mb-3">
                                    <span class="input-group-text mr-2">Merchant</span>
                                    <input class="form-control rounded" id="merchantList" list="merchant-list" placeholder="type to search.." autocomplete="off">
                                    <datalist id="merchant-list" class="merchant-options">
                                        <?php foreach ($merchant as $dt) : ?>
                                            <option value="<?= html_escape($dt['name']); ?>"></option>
                                        <?php endforeach ?>
                                    </datalist>
                                </div>
                                <div class="form-group">
                                    <div class="container-checkbox p-2 rounded">
                                        <div class="custom-control custom-checkbox mt-1">
                                            <input type="checkbox" class="custom-control-input" id="merchantAll" value="all" checked>
                                            <label class="custom-control-label" for="merchantAll">All</label>
                                        </div>
                                        <?php foreach ($merchant as $dt) : ?>
                                            <div class="custom-control custom-checkbox merchant-item" merchid="<?= html_escape($dt['merchant_id']); ?>" sbu_group="<?= html_escape($dt['sbu']); ?>" merchClass_group="<?= html_escape($dt['merchant_classification']); ?>">
                                                <input type="checkbox" class="custom-control-input merchant-filter" name="merchant_filter[]" id="merchant<?= html_escape($dt['merchant_id']); ?>" value="<?= html_escape($dt['merchant_id']); ?>" checked>
                                                <label class="custom-control-label" name="merchant_filter[]" for="merchant<?= html_escape($dt['merchant_id']); ?>"><?= html_escape($dt['name']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- store -->
                            <div class="col col-md-4">
                                <div class="input-group mb-3">
                                    <span class="input-group-text mr-2">Store</span>
                                </div>
                                <div class="form-group">

                                    <div class="container-checkbox p-2 rounded">
                                        <div class="custom-control custom-checkbox mt-1">
                                            <input type="checkbox" class="custom-control-input" id="storeAll" value="all" checked>
                                            <label class="custom-control-label" for="storeAll">All</label>
                                        </div>
                                        <?php foreach ($store as $dt) : ?>
                                            <div class="custom-control custom-checkbox store-item" merchant_group="<?= html_escape($dt['merchant_id']); ?>" sbu_group="<?= html_escape($dt['sbu']); ?>" merchClass_group="<?= html_escape($dt['merchant_classification']); ?>">
                                                <input type="checkbox" class="custom-control-input store-filter" name="store_filter[]" merchant_chk_box="<?= html_escape($dt['merchant_id']); ?>" id="store<?= html_escape($dt['store_id']); ?>" value="<?= html_escape($dt['store_id']); ?>" checked>
                                                <label class="custom-control-label" name="store_filter[]" for="store<?= html_escape($dt['store_id']); ?>"><?= html_escape($dt['name']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnReset" name="btnReset" class="btn btn-secondary rounded" data-dismiss="modal"><b>Reset</b></button>
                    <button type="submit" id="btnApply" name="btnApply" class="btn btn-primary rounded">Apply Filter</button>
                </div>
                <?= form_close(); ?>
            </div>
        </div>

        <!-- Custom CSS -->
        <link rel="stylesheet" href="<?= base_url('file/css/modal-trans-filter.css'); ?>" type="text/css">

        <!-- Function -->
        <script src="<?= base_url('file/js/filter-transaction.js'); ?>" ></script>