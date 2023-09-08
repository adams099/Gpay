<div class="modal" id="modal_add_usergroup" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="">
                <div class="rich-mpnl-header-cell">
                    <div class="rich-mpnl-text rich-mpnl-header" id="editUserDialogHeader" style="white-space: nowrap">
                        <span style="padding-right:15px;">Add usergroup</span>
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
                            <?= form_open('admin/users/save_add_usergroup/'.$user_detail->id, array('id' => 'form_add_usergroup')); ?>
                                <div class="rich-panel">
                                    <div class="rich-panel-body">
                                        <dl class="rich-messages" style="color:red;">
                                            <dt></dt>
                                        </dl>
                                        <table>
                                            <tbody>
                                                <tr>
                                                    <td>User group</td>
                                                    <td>
                                                        <select id="user_group" name="user_group" size="1" required>
                                                            <option value="" selected="selected">-- No selection --</option>
                                                            <? foreach($unassigned_user_groups as $unassigned_user_group): ?>
                                                                <option value="<?= $unassigned_user_group->code; ?>">
                                                                    <?= $unassigned_user_group->dsc; ?>
                                                                </option>
                                                            <? endforeach; ?>
                                                        </select>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <input type="submit" value="Save" />
                                <input type="button" value="Cancel" data-dismiss="modal" />
                            <?= form_close(); ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>