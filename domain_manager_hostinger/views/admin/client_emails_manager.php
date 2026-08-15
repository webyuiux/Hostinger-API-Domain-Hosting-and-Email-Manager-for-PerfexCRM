<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel-body">
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
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-emails_manager', '<?= admin_url('domain_manager_hostinger/email_list?client=' . $client->userid) ?>');
    });
</script>
