<div class="modal" id="modal_add_user" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="">
                <div class="rich-mpnl-header-cell">
                    <div class="rich-mpnl-text rich-mpnl-header" id="editUserDialogHeader" style="white-space: nowrap">
                        <span style="padding-right:15px;">Add user</span>
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
                            <?= form_open('admin/users/save_add'); ?>
                                <div class="rich-panel">
                                    <div class="rich-panel-body">
                                        <dl class="rich-messages" style="color:red;">
                                            <dt></dt>
                                        </dl>
                                        <table>
                                            <tbody>
                                                <tr>
                                                    <td>Username<span style="color:red;">*</span></td>
                                                    <td><input type="text" name="username" value="<?= set_value('username'); ?>" required /></td>
                                                    <td>
                                                        <span class="rich-message">
                                                            <span class="rich-message-label"><?= form_error('username'); ?></span>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>POS user</td>
                                                    <td><input type="checkbox" name="is_pos_user" value="Y" onclick="switchPOSUserMode(this.checked);" /></td>
                                                    <td>
                                                        <span class="rich-message">
                                                            <span class="rich-message-label"><?= form_error('is_pos_user'); ?></span>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>POS username</td>
                                                    <td><input id="pos_username" type="text" name="pos_username" style="display: none;" value="<?= set_value('pos_username'); ?>" /></td>
                                                    <td>
                                                        <span class="rich-message">
                                                            <span class="rich-message-label"><?= form_error('pos_username'); ?></span>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <td>Email<span style="color:red;">*</span></td>
                                                    <td><input type="email" name="email" value="<?= set_value('email'); ?>" required /></td>
                                                    <td>
                                                        <span class="rich-message">
                                                            <span class="rich-message-label"><?= form_error('email'); ?></span>
                                                        </span>
                                                    </td>
                                                <tr>
                                                    <td>Change password</td>
                                                    <td><input type="checkbox" name="change_password" value="Y" checked="checked" disabled /></td>
                                                    <td>
                                                        <span class="rich-message">
                                                            <span class="rich-message-label"><?= form_error('change_password'); ?></span>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Unique password</td>
                                                    <td><input id="password" type="password" name="password" /></td>
                                                    <td>
                                                        <span class="rich-message">
                                                            <span class="rich-message-label"><?= form_error('password'); ?></span>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Name of person<span style="color:red;">*</span></td>
                                                    <td>
                                                        <span id="select_person">
                                                            <table>
                                                                <tbody>
                                                                    <tr>
                                                                        <td>
                                                                            <input id="fullname" type="text" name="fullname" value="<?= set_value('fullname'); ?>" required />
                                                                        </td>
                                                                        <!-- <td>
                                                                            <a href="#">Select</a>
                                                                        </td>
                                                                        <td>
                                                                            <a href="#">Add</a>
                                                                        </td> -->
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="rich-message">
                                                            <span class="rich-message-label"><?= form_error('fullname'); ?></span>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Terminal search:</td>
                                                    <td><input id="terminal_search" type="text" name="terminal_search" value="<?= set_value('pos_username'); ?>" /></td>
                                                    <td>
                                                        <span class="rich-message">
                                                            <span class="rich-message-label"><?= form_error('terminal_search'); ?></span>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Time Zone</td>
                                                    <td><input type="text" name="time_zone" value="<?= set_value('time_zone'); ?>" /></td>
                                                    <td>
                                                        <span class="rich-message">
                                                            <span class="rich-message-label"><?= form_error('time_zone'); ?></span>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Agent</td>
                                                    <td><input id="is_agent_check" type="checkbox" name="is_agent_check" value="y" onclick="switchAgentCompanyMode(this.checked);"></td>
                                                    <td>
                                                        <span class="rich-message">
                                                            <span class="rich-message-label"><?= form_error('is_agent_check'); ?></span>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Select agent company</td>
                                                    <td>
                                                        <select id="agent_company" name="agent_company" size="1" style="display: none;">
                                                            <? foreach($merchants as $merchant): ?>
                                                                <option value="<?= $merchant->id; ?>"><?= $merchant->merchant_name; ?></option>
                                                            <? endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <span class="rich-message">
                                                            <span class="rich-message-label"><?= form_error('agent_company'); ?></span>
                                                        </span>
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