<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel-body">
    <?php render_datatable([
        '#',
        _l('domain_manager_website_name'),
        _l('domain_manager_client'),
        'Start Date',
        'Expiry Date',
        _l('domain_manager_status'),
    ], 'hosting_details'); ?>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-hosting_details', '<?= admin_url('domain_manager_hostinger/hosting_list?client=' . $client->userid) ?>');
    });
</script>
