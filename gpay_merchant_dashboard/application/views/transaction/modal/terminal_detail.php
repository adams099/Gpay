<div class="modal" id="modal_transaction_terminal_details" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="">
                <div class="rich-mpnl-header-cell">
                    <div class="rich-mpnl-text rich-mpnl-header" id="editUserDialogHeader" style="white-space: nowrap">
                        <span style="padding-right:15px;">Data of endpoints</span>
                        <div class="rich-mpnl-text rich-mpnl-controls">
                            <img src="<?= base_url('file/img/close.png'); ?>" data-dismiss="modal" />
                        </div>
                    </div>
                </div>
            </div>
        
            <!-- Modal Body -->
            <div class="">
                <table width="100%">
                    <tr style="height: 99%">
                        <td class="rich-mpnl-body" valign="top">
                            <div class="rich-panel-body">
                                <table width="100%" frame="box">
                                    <tbody>
                                        <tr>
                                            <td>Terminal data</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td>TID:</td>
                                            <td id="transaction_terminal_id"></td>
                                        </tr>
                                        <tr>
                                            <td>Status:</td>
                                            <td id="transaction_terminal_status"></td>
                                        </tr>
                                        <tr>
                                            <td>Name of participating business:</td>
                                            <td id="transaction_terminal_name_participating"></td>
                                        </tr>
                                        <tr>
                                            <td>Address of participating business:</td>
                                            <td id="transaction_terminal_address_participating"></td>
                                        </tr>
                                        <tr>
                                            <td>Partner company:</td>
                                            <td id="transaction_terminal_partner_company"></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            <input type="button" value="Cancel" data-dismiss="modal" />
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>