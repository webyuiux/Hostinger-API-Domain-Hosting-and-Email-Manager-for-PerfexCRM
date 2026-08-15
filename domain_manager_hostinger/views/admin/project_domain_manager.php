<?php
defined('BASEPATH') or exit('No direct script access allowed');
 defined('BASEPATH') or exit('No direct script access allowed'); 
$isGridView = 0;

?>

<div class="panel-body">

    <div class="row" id="threedstudio-table">
        <div class="col-md-12">
                <?php render_datatable([
                    '#',
                    _l('domain_manager_domain_name'),
                    _l('domain_manager_domain_type'),
                    _l('domain_manager_registrar'),
                    _l('domain_manager_client'),
                    _l('domain_manager_purchase_date'),
                    _l('domain_manager_expiry_date'),
                    _l('domain_manager_status'),
                    ], 'domain_manager'); ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>
 <script>
  $(function() {
        initDataTable('.table-domain_manager', '<?=admin_url('domain_manager_hostinger?project='.$project->id)?>');
    });
</script>