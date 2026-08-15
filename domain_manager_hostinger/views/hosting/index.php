<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="wrap">
            
            <!-- Breadcrumb -->
            <div class="h-breadcrumb-premium">
                INFRASTRUCTURE / <span>WEBSITES</span>
            </div>

            <!-- Page Header -->
            <div class="h-page-header">
                <div>
                    <h1 class="h-page-title">Hosting & Websites</h1>
                    <p class="h-page-subtitle">Monitor your active hosting plans and website deployments.</p>
                </div>
                <div class="tw-flex tw-gap-3">
                    <?php if (!empty(get_option('domain_manager_hostinger_api_token'))) { ?>
                    <button type="button" id="sync-websites-btn" class="btn-h-filter">
                        <i class="fa fa-refresh"></i>
                        Sync
                    </button>
                    <?php } ?>
                    <?php if (has_permission('domain_manager', '', 'hosting_create')) { ?>
                    <a href="<?= admin_url('domain_manager_hostinger/hosting_create') ?>" class="btn-h-add" style="text-decoration: none;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Website
                    </a>
                    <?php } ?>
                </div>
            </div>

            <div id="website-sync-result" style="display:none;margin-bottom:12px;"></div>

            <!-- Websites Table -->
            <div class="h-table-section">
                <div class="panel-table-full">
                    <?php render_datatable([
                        _l('domain_manager_website_name'),
                        _l('domain_manager_client'),
                        _l('domain_manager_start_date'),
                        _l('domain_manager_expiry_date'),
                        _l('domain_manager_status'),
                        ['name' => 'ACTIONS', 'th_attrs' => ['class' => 'text-right']],
                    ], 'hosting_details'); ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function () {
    initDataTable('.table-hosting_details', window.location.href);

    $('#sync-websites-btn').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-refresh fa-spin"></i> Syncing...');
        var $result = $('#website-sync-result').show()
            .removeClass('alert-success alert-danger alert-info')
            .addClass('alert alert-info')
            .html('<i class="fa fa-spinner fa-spin"></i> Syncing websites from Hostinger...');

        $.ajax({
            url: '<?php echo admin_url('domain_manager_hostinger/sync_hostinger_websites'); ?>',
            type: 'POST',
            dataType: 'json',
            data: { <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>' },
            success: function (resp) {
                $result.removeClass('alert-info').addClass(resp.success ? 'alert-success' : 'alert-danger');
                $result.html('<i class="fa ' + (resp.success ? 'fa-check-circle' : 'fa-times-circle') + '"></i> ' + resp.message);
                if (resp.success) {
                    setTimeout(function () { $('.table-hosting_details').DataTable().ajax.reload(); }, 800);
                }
            },
            error: function () {
                $result.removeClass('alert-info').addClass('alert-danger').html('<i class="fa fa-times-circle"></i> Sync request failed.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Sync');
            }
        });
    });
});
</script>
</body>
</html>
