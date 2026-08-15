<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="tw-max-w-2xl tw-mx-auto">

            <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                <i class="fa fa-pencil tw-mr-2 tw-text-primary"></i><?php echo _l('domain_manager_edit'); ?>:
                <span class="tw-text-primary"><?= e($domain->domain_name) ?></span>
            </h4>

            <?php echo form_open(admin_url('domain_manager_hostinger/update_domain_manager'), ['id' => 'save_form']); ?>
            <input type="hidden" name="id" value="<?= $domain->id ?>">

            <div class="h-panel">
                <div class="panel-body tw-p-8">

                    <!-- Domain Name -->
                    <div class="form-group">
                        <label class="control-label" for="name">
                            <?php echo _l('domain_manager_domain_name'); ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" id="name" class="form-control" required
                               value="<?= e($domain->domain_name) ?>" autocomplete="off">
                    </div>

                    <div class="row">
                        <!-- Domain Type -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_domain_type'); ?></label>
                                <select name="domain_type" id="domain_type" class="selectpicker form-control" data-width="100%">
                                    <option value="internal" <?= ($domain->domain_type == 'internal') ? 'selected' : '' ?>>
                                        <?php echo _l('domain_manager_internal'); ?>
                                    </option>
                                    <option value="external" <?= ($domain->domain_type != 'internal') ? 'selected' : '' ?>>
                                        <?php echo _l('domain_manager_external'); ?>
                                    </option>
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
                                    <option value="<?= $client['userid'] ?>" <?= ($domain->client_id == $client['userid']) ? 'selected' : '' ?>>
                                        <?= e($client['company']) ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <!-- Purchase Date -->
                        <div class="col-md-6">
                            <?php echo render_date_input('domain_manager_purchase_date', _l('domain_manager_purchase_date'),
                                ($domain->purchase_date && $domain->purchase_date != '0000-00-00') ? _d($domain->purchase_date) : '',
                                ['id' => 'domain_manager_purchase_date', 'autocomplete' => 'off']); ?>
                        </div>

                        <!-- Expiry Date -->
                        <div class="col-md-6">
                            <?php echo render_date_input('domain_manager_expiry_date', _l('domain_manager_expiry_date'),
                                ($domain->expiry_date && $domain->expiry_date != '0000-00-00') ? _d($domain->expiry_date) : '',
                                ['id' => 'domain_manager_expiry_date', 'autocomplete' => 'off']); ?>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label"><?php echo _l('domain_manager_status'); ?></label>
                                <select name="status" id="status" class="selectpicker form-control" data-width="100%">
                                    <?php 
                                    $statuses = ['active', 'expired', 'pending', 'suspended'];
                                    foreach($statuses as $st) {
                                        echo '<option value="'.$st.'" '.($domain->status == $st ? 'selected' : '').'>'.ucfirst($st).'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <?php echo render_textarea('description', _l('domain_manager_notes'), $domain->description,
                        ['placeholder' => 'Any additional notes about this domain...']); ?>

                </div>
                    <div class="tw-mt-8 tw-flex tw-justify-end tw-gap-3">
                        <a href="<?= admin_url('domain_manager_hostinger'); ?>" class="btn-h-action-white">Cancel</a>
                        <button class="btn-h-primary" type="submit">
                            <i class="fa fa-save tw-mr-1"></i> Update Domain
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
</script>
</body>
</html>