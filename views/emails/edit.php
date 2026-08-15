<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="tw-max-w-2xl tw-mx-auto">

            <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                <i class="fa fa-pencil tw-mr-2 tw-text-primary"></i><?php echo _l('domain_manager_email_edit'); ?>:
                <span class="tw-text-primary"><?= e($email->mailbox_name) ?></span>
            </h4>

            <!-- Main Primary Mailbox Form -->
            <?php echo form_open(admin_url('domain_manager_hostinger/update_email'), ['id' => 'email_form']); ?>
            <input type="hidden" name="id" value="<?php echo $email->id; ?>">

            <div class="h-panel tw-mb-6">
                <div class="panel-body tw-p-8">
                    <div class="row">
                        <div class="col-md-6">
                            <?php echo render_input('mailbox_name', 'Primary Mailbox', $email->mailbox_name, 'text', ['required' => true], [], 'form-group'); ?>
                        </div>
                        <div class="col-md-6">
                            <?php echo render_input('domain', 'domain_manager_domain_name', $email->domain, 'text', ['readonly' => true], [], 'form-group'); ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?php echo render_input('available_count', 'domain_manager_available_count', $email->available_count, 'number', [], [], 'form-group'); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_client'); ?></label>
                                <select name="client_id" id="client_id" class="selectpicker form-control" data-width="100%" data-live-search="true">
                                    <option value=""><?php echo _l('domain_manager_no_client'); ?></option>
                                    <?php foreach ($clients as $client) { ?>
                                        <option value="<?php echo $client['userid']; ?>" <?php echo ($client['userid'] == $email->client_id) ? 'selected' : ''; ?>>
                                            <?php echo e($client['company']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Client Email</label>
                                <select name="client_email" id="client_email" class="selectpicker form-control" data-width="100%" data-live-search="true" data-none-selected-text="No Client Selected">
                                    <?php if (!empty($email->client_email)) { ?>
                                        <option value="<?php echo html_escape($email->client_email); ?>" selected><?php echo html_escape($email->client_email); ?></option>
                                    <?php } else { ?>
                                        <option value="">No Email Selected</option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php echo render_date_input('start_date', 'domain_manager_start_date', ($email->start_date && $email->start_date != '0000-00-00') ? _d($email->start_date) : '', ['autocomplete' => 'off']); ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?php echo render_date_input('expiry_date', 'domain_manager_expire_date', ($email->expiry_date && $email->expiry_date != '0000-00-00') ? _d($email->expiry_date) : '', ['autocomplete' => 'off']); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_status'); ?></label>
                                <select name="status" id="status" class="selectpicker form-control" data-width="100%">
                                    <?php foreach(['active', 'expired', 'suspended', 'pending'] as $s) { ?>
                                        <option value="<?= $s ?>" <?= ($email->status == $s) ? 'selected' : '' ?>>
                                            <?= _l('domain_manager_'.$s) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <?php echo render_textarea('description', 'domain_manager_notes', $email->description, ['placeholder' => 'Any additional notes...']); ?>

                    <hr style="margin: 20px 0; border-top: 1px dashed #cbd5e1;" />
                    
                    <h4 class="tw-font-bold tw-text-neutral-700 tw-mb-4" style="font-size: 15px; font-weight: 700;">Mailboxes inside <?= html_escape($email->domain) ?></h4>
                    
                    <!-- Existing Mailboxes Tree List -->
                    <div class="mailbox-tree-list tw-mb-6" id="additional-mailboxes-list" style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 20px;">
                        <?php if (!empty($additional_mailboxes)) { ?>
                            <?php 
                            $total = count($additional_mailboxes);
                            $index = 0;
                            foreach ($additional_mailboxes as $mb) { 
                                $index++;
                                $is_last = ($index === $total);
                                $tree_char = $is_last ? '└── ' : '├── ';
                                $status_class = ($mb['status'] == 'active') ? 'success' : (($mb['status'] == 'expired' || $mb['status'] == 'suspended') ? 'danger' : 'warning');
                            ?>
                                <div class="mailbox-tree-item tw-flex tw-justify-between tw-items-center tw-py-2 hover:tw-bg-neutral-50 tw-px-2 tw-rounded" style="font-family: monospace; font-size: 14px; display: flex; justify-content: space-between; align-items: center; padding-top: 4px; padding-bottom: 4px;">
                                    <div class="tw-flex tw-items-center" style="display: flex; align-items: center;">
                                        <span class="text-muted tw-mr-1" style="color: #64748b; margin-right: 4px;"><?= $tree_char ?></span>
                                        <span style="color: #334155; font-weight: 600;"><?= html_escape($mb['mailbox_name']) ?></span>
                                        <span class="label label-<?= $status_class ?> tw-ml-3" style="font-family: sans-serif; font-size: 10px; padding: 2px 6px; margin-left: 12px;"><?= ucfirst(html_escape($mb['status'])) ?></span>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-danger btn-icon btn-xs delete-mailbox-btn" data-id="<?= $mb['id'] ?>" title="Delete"><i class="fa fa-remove"></i></button>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="text-muted tw-py-2" style="color: #64748b; padding-left: 10px;">No additional mailboxes found.</div>
                        <?php } ?>
                    </div>

                    <!-- Repeatable Input Fields for Adding New Mailboxes -->
                    <div class="form-group tw-mt-4" style="margin-top: 20px;">
                        <label class="control-label" style="font-weight: 600; margin-bottom: 8px; display: block;">Add Other Mailbox(es)</label>
                        <div id="new_mailboxes_container" style="display: flex; flex-direction: column; gap: 10px;">
                            <!-- Dynamically added input rows go here -->
                        </div>
                        <button type="button" class="btn btn-default btn-xs tw-mt-2" id="btn-add-mailbox-row" style="margin-top: 8px;">
                            <i class="fa fa-plus"></i> Add Mailbox
                        </button>
                    </div>

                    <div class="tw-mt-8 tw-flex tw-justify-end tw-gap-3" style="margin-top: 30px; display: flex; justify-content: flex-end; gap: 12px;">
                        <a href="<?= admin_url('domain_manager_hostinger/emails'); ?>" class="btn-h-action-white">Cancel</a>
                        <button class="btn-h-primary" type="submit">
                            <i class="fa fa-save tw-mr-1"></i> Update Email
                        </button>
                    </div>
                </div>
            </div>
            <?php echo form_close(); ?>

        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
    $(".menu-item-domain_manager").addClass('active');
    $(".sub-menu-item-emails_view_list").addClass('active');

    $(function() {
        const domain = '<?= html_escape($email->domain) ?>';

        // Add repeatable mailbox input row
        $('#btn-add-mailbox-row').on('click', function() {
            var rowHtml = '<div class="mailbox-row form-group tw-mb-3" style="margin-top: 10px; margin-bottom: 10px;">' +
                          '  <div class="input-group">' +
                          '    <input type="text" name="mailbox_names[]" class="form-control" placeholder="e.g. support" required>' +
                          '    <span class="input-group-addon">@' + domain + '</span>' +
                          '    <span class="input-group-btn">' +
                          '      <button type="button" class="btn btn-danger remove-mailbox-btn"><i class="fa fa-times"></i></button>' +
                          '    </span>' +
                          '  </div>' +
                          '</div>';
            $('#new_mailboxes_container').append(rowHtml);
        });

        // Remove repeatable mailbox input row
        $('body').on('click', '.remove-mailbox-btn', function() {
            $(this).closest('.mailbox-row').remove();
        });

        // AJAX Delete Mailbox
        $('body').on('click', '.delete-mailbox-btn', function() {
            const id = $(this).data('id');
            if (confirm('Are you sure you want to delete this mailbox?')) {
                $.ajax({
                    url: admin_url + 'domain_manager_hostinger/delete_mailbox_ajax/' + id,
                    type: 'POST',
                    data: {
                        "<?= $this->security->get_csrf_token_name(); ?>": "<?= $this->security->get_csrf_hash(); ?>"
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.success) {
                            alert_float('success', resp.message);
                            setTimeout(function() {
                                window.location.reload();
                            }, 800);
                        } else {
                            alert_float('danger', resp.message);
                        }
                    }
                });
            }
        });

        // Client change handler to update client email list
        $('#client_id').on('change', function() {
            var client_id = $(this).val();
            if (client_id) {
                $.ajax({
                    url: '<?= admin_url('domain_manager_hostinger/get_client_email_ajax') ?>',
                    type: 'GET',
                    dataType: 'json',
                    data: { client_id: client_id },
                    success: function(res) {
                        if (res.success && res.contacts && res.contacts.length > 0) {
                            var primary = res.contacts.find(c => c.is_primary == 1) || res.contacts[0];
                            $('#client_email').empty().append('<option value="' + primary.email + '" selected>' + primary.email + '</option>').selectpicker('refresh');
                        } else {
                            $('#client_email').empty().append('<option value="">No Email Selected</option>').selectpicker('refresh');
                        }
                    }
                });
            } else {
                $('#client_email').empty().append('<option value="">No Email Selected</option>').selectpicker('refresh');
            }
        });
    });
</script>
</body>
</html>
