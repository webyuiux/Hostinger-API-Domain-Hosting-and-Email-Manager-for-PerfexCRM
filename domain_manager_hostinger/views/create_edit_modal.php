<div class="modal fade " id="domain_manager-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
    <?php echo form_open(admin_url('domain_manager_hostinger/save'),['id'=>'save_form']); ?>
    
    <div class="modal-dialog modal-mg">
            <div class="modal-content data">
                <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                        <h4 class="modal-title" id="myModalLabel">Add new</h4>
                </div>
                <div class="modal-body" id="domain_manager_model_body">
                

                </div>
                <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="saveDomain_manager(); return false;">Save</button>
                </div>
            </div>
        </div>

    <?php echo form_close(); ?>
</div>


<div class="modal fade " id="edit-domain_manager-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
    <?php echo form_open(admin_url('domain_manager_hostinger/update_domain_manager')); ?>

        <div class="modal-dialog modal-mg">
            <div class="modal-content data">
                <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                        <h4 class="modal-title" id="myModalLabel">Edit</h4>
                </div>
                <div class="modal-body" id="edit_domain_manager_model_body">
                

                </div>
                <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="subnit" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    <?php echo form_close(); ?>
</div>