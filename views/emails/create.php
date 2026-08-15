<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="tw-max-w-2xl tw-mx-auto">

            <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                <i class="fa fa-plus-circle tw-mr-2 tw-text-primary"></i><?php echo _l('domain_manager_email_add'); ?>
            </h4>

            <?php echo form_open(admin_url('domain_manager_hostinger/save_email'), ['id' => 'email_form']); ?>

            <div class="h-panel">
                <div class="panel-body tw-p-8">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_mailbox_name'); ?></label>
                                <div id="mailboxes_container">
                                    <div class="mailbox-row form-group tw-mb-3">
                                        <div class="input-group">
                                            <input type="text" name="mailbox_names[]" class="form-control" placeholder="e.g. admin" required>
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-danger remove-mailbox-btn" disabled><i class="fa fa-times"></i></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-default btn-xs tw-mt-2" id="add_mailbox_btn">
                                    <i class="fa fa-plus"></i> Add Another Mailbox
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php echo render_input('domain', 'domain_manager_domain_name', '', 'text', ['placeholder' => 'e.g. example.com'], [], 'form-group'); ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?php echo render_input('available_count', 'domain_manager_available_count', '0', 'number', [], [], 'form-group'); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_client'); ?></label>
                                <select name="client_id" id="client_id" class="selectpicker form-control" data-width="100%" data-live-search="true">
                                    <option value=""><?php echo _l('domain_manager_no_client'); ?></option>
                                    <?php foreach ($clients as $client) { ?>
                                        <option value="<?php echo $client['userid']; ?>"><?php echo e($client['company']); ?></option>
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
                                    <option value="">No Email Selected</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php echo render_date_input('start_date', 'domain_manager_start_date', '', ['autocomplete' => 'off']); ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?php echo render_date_input('expiry_date', 'domain_manager_expire_date', '', ['autocomplete' => 'off']); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_status'); ?></label>
                                <select name="status" id="status" class="selectpicker form-control" data-width="100%">
                                    <option value="active"><?php echo _l('domain_manager_active'); ?></option>
                                    <option value="expired"><?php echo _l('domain_manager_expired'); ?></option>
                                    <option value="suspended"><?php echo _l('domain_manager_suspended'); ?></option>
                                    <option value="pending"><?php echo _l('domain_manager_pending'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>


                    <?php echo render_textarea('description', 'domain_manager_notes', '', ['placeholder' => 'Any additional notes...']); ?>

                    <div class="tw-mt-8 tw-flex tw-justify-end tw-gap-3">
                        <a href="<?= admin_url('domain_manager_hostinger/emails'); ?>" class="btn-h-action-white">Cancel</a>
                        <button class="btn-h-primary" type="submit">
                            <i class="fa fa-save tw-mr-1"></i> Save Email
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
        $('#add_mailbox_btn').on('click', function() {
            var rowHtml = '<div class="mailbox-row form-group tw-mb-3">';
            rowHtml += '<div class="input-group">';
            rowHtml += '<input type="text" name="mailbox_names[]" class="form-control" placeholder="e.g. admin" required>';
            rowHtml += '<span class="input-group-btn">';
            rowHtml += '<button type="button" class="btn btn-danger remove-mailbox-btn"><i class="fa fa-times"></i></button>';
            rowHtml += '</span>';
            rowHtml += '</div>';
            rowHtml += '</div>';
            $('#mailboxes_container').append(rowHtml);
            update_remove_mailbox_buttons();
        });

        $('body').on('click', '.remove-mailbox-btn', function() {
            $(this).closest('.mailbox-row').remove();
            update_remove_mailbox_buttons();
        });

        function update_remove_mailbox_buttons() {
            var rows = $('.mailbox-row');
            if (rows.length <= 1) {
                rows.find('.remove-mailbox-btn').attr('disabled', true);
            } else {
                rows.find('.remove-mailbox-btn').removeAttr('disabled');
            }
        }

        // Auto-fetch client email when client is selected
        $('#client_id').on('change', function() {
            var clientId = $(this).val();
            var $emailSelect = $('#client_email');
            
            $emailSelect.empty();
            $emailSelect.append('<option value="">Fetching...</option>');
            $emailSelect.selectpicker('refresh');
            
            if (!clientId) {
                $emailSelect.empty();
                $emailSelect.append('<option value="">No Client Selected</option>');
                $emailSelect.selectpicker('refresh');
                return;
            }
            
            $.ajax({
                url: '<?php echo admin_url("domain_manager_hostinger/get_client_email_ajax"); ?>',
                type: 'GET',
                data: { client_id: clientId },
                dataType: 'json',
                success: function(resp) {
                    $emailSelect.empty();
                    if (resp.success && resp.contacts && resp.contacts.length > 0) {
                        $.each(resp.contacts, function(index, contact) {
                            var optionText = contact.name + ' (' + contact.email + ')';
                            if (contact.is_primary) {
                                optionText += ' [Primary]';
                            }
                            $emailSelect.append('<option value="' + contact.email + '"' + (contact.is_primary ? ' selected' : '') + '>' + optionText + '</option>');
                        });
                    } else {
                        $emailSelect.append('<option value="">No contacts found</option>');
                    }
                    $emailSelect.selectpicker('refresh');
                },
                error: function() {
                    $emailSelect.empty();
                    $emailSelect.append('<option value="">Error fetching emails</option>');
                    $emailSelect.selectpicker('refresh');
                }
            });
        });

        // Auto-fetch domain details when domain name is typed
        $('#domain').on('blur', function() {
            var domainName = $(this).val().trim();
            if (!domainName) return;

            // Show a visual hint
            var $domainInput = $(this);
            var originalColor = $domainInput.css('background-color');
            $domainInput.css('background-color', '#f0fdf4');

            $.ajax({
                url: '<?php echo admin_url("domain_manager_hostinger/get_domain_details_by_name_ajax"); ?>',
                type: 'GET',
                data: { domain: domainName },
                dataType: 'json',
                success: function(resp) {
                    if (resp.success && resp.data) {
                        var d = resp.data;
                        
                        // Set Client
                        if (d.client_id && d.client_id != '0') {
                            $('#client_id').val(d.client_id).selectpicker('refresh');
                        }
                        
                        // Set Client Email
                        if (d.custom_email) {
                            $('#client_email').val(d.custom_email);
                        } else if (d.client_id && d.client_id != '0') {
                            // trigger the existing client change event to fetch client's email
                            $('#client_id').trigger('change');
                        }

                        // Set Dates
                        if (d.purchase_date && d.purchase_date !== '0000-00-00') {
                            $('#start_date').val(d.purchase_date);
                        }
                        if (d.expiry_date && d.expiry_date !== '0000-00-00') {
                            $('#expiry_date').val(d.expiry_date);
                        }
                        
                        // Set Status
                        if (d.status) {
                            $('#status').val(d.status).selectpicker('refresh');
                        }
                    }
                },
                complete: function() {
                    setTimeout(function() {
                        $domainInput.css('background-color', originalColor);
                    }, 500);
                }
            });
        });
    });
</script>
</body>
</html>
