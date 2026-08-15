<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="col-md-12">

            <!-- Breadcrumbs -->
            <div class="h-breadcrumbs">
                <a href="<?= admin_url() ?>"><i class="fa fa-home"></i></a>
                <i class="fa fa-chevron-right"></i>
                <span>Emails</span>
            </div>

            <!-- Page Title & Main Action -->
            <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
                <h3 class="tw-font-extrabold tw-text-2xl tw-text-neutral-800 tw-m-0">Emails</h3>
                <?php if (has_permission('domain_manager', get_staff_user_id(), 'hosting_create')) { ?>
                    <a class="btn-h-primary" href="<?= admin_url('domain_manager_hostinger/email_create') ?>">
                        <i class="fa fa-plus tw-mr-1"></i> Add mailbox
                    </a>
                <?php } ?>
            </div>

            <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
                <div class="h-search-wrapper tw-mb-0 tw-flex-1">
                    <i class="fa fa-search h-search-icon"></i>
                    <input type="text" id="email-search" class="form-control h-search-input" placeholder="Search by mailbox, domain...">
                </div>
            </div>



            <!-- Email List Card -->
            <div class="h-panel">
                <div class="panel-body tw-p-6">
                    <div class="panel-table-full">
                        <?php render_datatable([
                            _l('domain_manager_mailbox_name'),
                            _l('domain_manager_domain_name'),
                            _l('domain_manager_available_count'),
                            _l('domain_manager_client'),
                            _l('domain_manager_start_date'),
                            _l('domain_manager_expiry_date'),
                            _l('domain_manager_status'),
                            _l('options'),
                        ], 'emails_manager'); ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function () {
    initDataTable('.table-emails_manager', window.location.href, [7], [7]);

    $('#email-search').on('keyup', function () {
        $('.table-emails_manager').DataTable().search(this.value).draw();
    });


});
</script>
</body>
</html>
