<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="tw-max-w-2xl tw-mx-auto">

            <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                <i class="fa fa-pencil tw-mr-2 tw-text-primary"></i><?php echo _l('domain_manager_host_edit'); ?>:
                <span class="tw-text-primary"><?= e($hosting->website_name) ?></span>
            </h4>

            <?php echo form_open(admin_url('domain_manager_hostinger/update_hosting'), ['id' => 'save_form']); ?>
            <input type="hidden" name="id" value="<?= $hosting->id ?>">

            <div class="panel_s">
                <div class="panel-body">

                    <!-- Website / Domain -->
                    <div class="form-group">
                        <label class="control-label" for="website_name">
                            <?php echo _l('domain_manager_website_name'); ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="website_name" id="website_name" class="form-control" required
                               value="<?= e($hosting->website_name) ?>"
                               placeholder="<?php echo _l('domain_manager_website_name_ph'); ?>" autocomplete="off">
                    </div>

                    <div class="row">
                        <!-- Client -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_client'); ?></label>
                                <select name="client_id" id="client_id" class="selectpicker form-control" data-width="100%"
                                        data-live-search="true">
                                    <option value=""><?php echo _l('domain_manager_no_client'); ?></option>
                                    <?php foreach ($clients as $client) { ?>
                                    <option value="<?= $client['userid']; ?>" <?= ($hosting->client_id == $client['userid']) ? 'selected' : '' ?>>
                                        <?= e($client['company']); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <!-- Assigned Staff -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Assigned Staff</label>
                                <select name="assigned_staff_id" id="assigned_staff_id" class="selectpicker form-control" data-width="100%"
                                        data-live-search="true">
                                    <option value="">No Staff Assigned</option>
                                    <?php foreach ($staff as $member) { ?>
                                    <option value="<?= $member['staffid']; ?>" <?= ($hosting->assigned_staff_id == $member['staffid']) ? 'selected' : '' ?>>
                                        <?= e($member['firstname'] . ' ' . $member['lastname']); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Linked Domain -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Linked Domain <small class="text-muted">(optional)</small></label>
                                <select name="domain_id" id="domain_id" class="selectpicker form-control" data-width="100%"
                                        data-live-search="true">
                                    <option value="">No Linked Domain</option>
                                    <?php foreach ($domains as $domain) { ?>
                                    <option value="<?= $domain['id']; ?>" <?= ($hosting->domain_id == $domain['id']) ? 'selected' : '' ?>>
                                        <?= e($domain['domain_name']); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_status'); ?></label>
                                <select name="status" id="status" class="selectpicker form-control" data-width="100%">
                                    <?php foreach (['active', 'suspended', 'expired', 'pending'] as $s) { ?>
                                    <option value="<?= $s ?>" <?= ($hosting->status == $s) ? 'selected' : '' ?>>
                                        <?php echo _l('domain_manager_' . $s); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Start Date -->
                        <div class="col-md-6">
                            <?php echo render_date_input('domain_manager_start_date', _l('domain_manager_start_date'),
                                ($hosting->start_date && $hosting->start_date != '0000-00-00') ? _d($hosting->start_date) : '',
                                ['id' => 'domain_manager_start_date', 'autocomplete' => 'off']); ?>
                        </div>

                        <!-- Expiry Date -->
                        <div class="col-md-6">
                            <?php echo render_date_input('domain_manager_expiry_date', _l('domain_manager_expiry_date'),
                                ($hosting->expiration_date && $hosting->expiration_date != '0000-00-00') ? _d($hosting->expiration_date) : '',
                                ['id' => 'domain_manager_expiry_date', 'autocomplete' => 'off']); ?>
                        </div>

                        <!-- Hosting Provider (optional) -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_provider'); ?> <small class="text-muted">(optional)</small></label>
                                <input type="text" name="domain_manager_provider" id="domain_manager_provider"
                                       class="form-control" value="<?= e($hosting->provider) ?>"
                                       placeholder="<?php echo _l('domain_manager_provider_ph'); ?>" autocomplete="off">
                            </div>
                        </div>

                        <!-- Website URL (for quick open) -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Website URL <small class="text-muted">(optional - for quick open)</small></label>
                                <div class="input-group">
                                    <input type="text" name="access_url" id="access_url"
                                           class="form-control" value="<?= e($hosting->access_url) ?>"
                                           placeholder="e.g. https://clientsite.com" autocomplete="off">
                                    <span class="input-group-addon"><?php if (!empty($hosting->access_url)): ?>
                                        <a href="<?= e($hosting->access_url) ?>" target="_blank"><i class="fa fa-external-link"></i></a>
                                    <?php else: ?><i class="fa fa-external-link"></i><?php endif; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <?php echo render_textarea('description', _l('domain_manager_notes'), $hosting->description,
                        ['placeholder' => 'Any additional notes about this website...']); ?>

                </div>
                <div class="panel-footer text-right">
                    <a href="<?= admin_url('domain_manager_hostinger/hosting_list'); ?>" class="btn btn-default tw-mr-2">Cancel</a>
                    <button class="btn btn-primary" type="submit">
                        <i class="fa fa-save tw-mr-1"></i> Update Website
                    </button>
                </div>
            </div>

            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(".menu-item-domain_manager").addClass('active');
    $(".sub-menu-item-hosting_view_list").addClass('active');
</script>
</body>
</html>