<?php
defined('BASEPATH') or exit('No direct script access allowed');
 defined('BASEPATH') or exit('No direct script access allowed'); 
$isGridView = 0;

?>
<?php if (isset($client)) { ?>
<h4 class="customer-profile-group-heading">
    <?= _l('domain_manager'); ?>

</h4>
<div class="row">
    <div class="col-md-12">
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
                        <?php }?>
        <?php init_tail(); ?>
        <script>
        $(function() {
                initDataTable('.table-domain_manager', '<?=admin_url('domain_manager_hostinger?client='.$client->userid)?>');
            });
        </script>