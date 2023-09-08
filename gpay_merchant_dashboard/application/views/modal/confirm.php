<div class="modal" id="modal_confirm" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="">
                <div class="rich-mpnl-header-cell">
                    <div class="rich-mpnl-text rich-mpnl-header" id="editUserDialogHeader" style="white-space: nowrap">
                        <span id="title_confirm" style="padding-right:15px;"></span>
                        <div class="rich-mpnl-text rich-mpnl-controls">
                            <img src="<?= base_url('file/img/close.png'); ?>" data-dismiss="modal" class="modalConfirmCancel"/>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="">
                <table width="100%">
                    <tr style="height: 99%">
                        <td class="rich-mpnl-body" valign="top">
                            <span id="label_confirm" style="padding-right:15px;"></span>
                            <?= form_open('', array('id' => 'form_confirm')); ?>
                                <input value="Yes" type="submit" onClick="this.disabled=true; this.value='Sending…'; this.form.submit();"/>
                                <input value="Cancel" type="button" class="modalConfirmCancel" data-dismiss="modal" />
                            <?= form_close(); ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>