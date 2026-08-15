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
                            <?php echo render_input('mailbox_name', 'domain_manager_mailbox_name', '', 'text', ['required' => true, 'placeholder' => _l('domain_manager_mailbox_name_ph')], [], 'form-group'); ?>
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
                            <?php echo render_date_input('start_date', 'domain_manager_start_date', '', ['autocomplete' => 'off']); ?>
                        </div>
                        <div class="col-md-6">
                            <?php echo render_date_input('expiry_date', 'domain_manager_expire_date', '', ['autocomplete' => 'off']); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label"><?php echo _l('domain_manager_status'); ?></label>
                        <select name="status" id="status" class="selectpicker form-control" data-width="100%">
                            <option value="active"><?php echo _l('domain_manager_active'); ?></option>
                            <option value="expired"><?php echo _l('domain_manager_expired'); ?></option>
                            <option value="suspended"><?php echo _l('domain_manager_suspended'); ?></option>
                            <option value="pending"><?php echo _l('domain_manager_pending'); ?></option>
                        </select>
                    </div>

                    <?php echo render_textarea('description', 'domain_manager_notes', '', ['placeholder' => 'Any additional notes...']); ?>

                </div>
                    <div class="tw-mt-8 tw-flex tw-justify-end tw-gap-3">
                        <a href="<?= admin_url('domain_manager_hostinger/email_list'); ?>" class="btn-h-action-white">Cancel</a>
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
</script>
</body>
</html>
