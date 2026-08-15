<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="tw-max-w-2xl tw-mx-auto">

            <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                <i class="fa fa-plus-circle tw-mr-2 tw-text-primary"></i><?php echo _l('domain_manager_add'); ?>
            </h4>

            <?php echo form_open(admin_url('domain_manager_hostinger/save_domain_manager'), ['id' => 'save_form', 'onsubmit' => 'return handleFormSubmit(event)']); ?>

            <div class="h-panel">
                <div class="panel-body tw-p-8">

                    <!-- Domain Name -->
                    <div class="form-group">
                        <label class="control-label" for="name">
                            <?php echo _l('domain_manager_domain_name'); ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" id="name" class="form-control" required
                               placeholder="e.g. clientsite.com" autocomplete="off">
                    </div>

                    <div class="row">
                        <!-- Domain Type -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_domain_type'); ?></label>
                                <select name="domain_type" id="domain_type" class="selectpicker form-control" data-width="100%">
                                    <option value="internal"><?php echo _l('domain_manager_internal'); ?></option>
                                    <option value="external" selected><?php echo _l('domain_manager_external'); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Client -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_client'); ?></label>
                                <select name="client_id" id="client_id" class="selectpicker form-control" data-width="100%"
                                        data-live-search="true">
                                    <option value=""><?php echo _l('domain_manager_no_client'); ?></option>
                                    <?php foreach ($clients as $client) { ?>
                                    <option value="<?= $client['userid']; ?>"><?= $client['company']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <!-- Assigned Staff -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Assigned Staff Member</label>
                                <select name="assigned_staff_id" id="assigned_staff_id" class="selectpicker form-control" data-width="100%" data-live-search="true">
                                    <option value="">No Staff Assigned</option>
                                    <?php foreach ($staff as $member) { ?>
                                    <option value="<?= $member['staffid']; ?>"><?= $member['firstname'] . ' ' . $member['lastname']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <!-- Client Email -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label" for="client_email">Client Email</label>
                                <input type="email" name="client_email" id="client_email" class="form-control">
                            </div>
                        </div>

                        <!-- Available Mailbox Count -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label" for="available_mailbox_count">Available Mailbox Count</label>
                                <input type="number" name="available_mailbox_count" id="available_mailbox_count" class="form-control" value="0" readonly>
                                <small class="text-muted">Will sync automatically as mailboxes are added.</small>
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div class="col-md-6">
                            <?php echo render_date_input('start_date', 'Start Date', '', ['id' => 'start_date', 'autocomplete' => 'off']); ?>
                        </div>

                        <!-- Purchase Date -->
                        <div class="col-md-6">
                            <?php echo render_date_input('domain_manager_purchase_date', _l('domain_manager_purchase_date'), '', ['id' => 'domain_manager_purchase_date', 'autocomplete' => 'off']); ?>
                        </div>

                        <!-- Expiry Date -->
                        <div class="col-md-6">
                            <?php echo render_date_input('domain_manager_expiry_date', _l('domain_manager_expiry_date'), '', ['id' => 'domain_manager_expiry_date', 'autocomplete' => 'off']); ?>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_status'); ?></label>
                                <select name="status" id="status" class="selectpicker form-control" data-width="100%">
                                    <option value="active" selected>Active</option>
                                    <option value="expired">Expired</option>
                                    <option value="pending">Pending</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <?php echo render_textarea('description', _l('domain_manager_notes'), '', ['placeholder' => 'Any additional notes about this domain...']); ?>

                    <div class="tw-mt-8 tw-flex tw-justify-end tw-gap-3">
                        <a href="<?= admin_url('domain_manager_hostinger'); ?>" class="btn-h-action-white">Cancel</a>
                        <button class="btn-h-primary" type="submit">
                            <i class="fa fa-save tw-mr-1"></i> Save Domain
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
    $(".sub-menu-item-domain_manager_list_item").addClass('active');

    /**
     * Handle form submission - redirect to index with hash to trigger refresh
     */
    function handleFormSubmit(event) {
        // Allow normal form submission
        return true;
    }

    // Auto-populate primary email address when client is changed
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
                        $('#client_email').val(primary.email);
                    } else {
                        $('#client_email').val('');
                    }
                }
            });
        } else {
            $('#client_email').val('');
        }
    });
</script>
</body>
</html>