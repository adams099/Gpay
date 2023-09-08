<div class="modal" id="modal_transaction_card_details" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="">
                <div class="rich-mpnl-header-cell">
                    <div class="rich-mpnl-text rich-mpnl-header" id="editUserDialogHeader" style="white-space: nowrap">
                        <span style="padding-right:15px;">Card data</span>
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
                                            <td>Short card number:</td>
                                            <td id="transaction_card_number"></td>
                                        </tr>
                                        <tr>
                                            <td>Status:</td>
                                            <td id="transaction_card_status"></td>
                                        </tr>
                                        <tr>
                                            <td>Expiration date:</td>
                                            <td id="transaction_card_expiration_date"></td>
                                        </tr>
                                        <tr>
                                            <td>Card type:</td>
                                            <td id="transaction_card_type"></td>
                                        </tr>
                                        <tr>
                                            <td>Partner company:</td>
                                            <td id="transaction_card_partner_company"></td>
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