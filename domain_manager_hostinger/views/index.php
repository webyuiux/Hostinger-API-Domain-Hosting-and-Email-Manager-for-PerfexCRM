<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="wrap">
            
            <!-- Breadcrumb -->
            <div class="h-breadcrumb-premium">
                INFRASTRUCTURE / <span>DOMAINS</span>
            </div>

            <!-- Page Header -->
            <div class="h-page-header">
                <div>
                    <h1 class="h-page-title">Domain Portfolio</h1>
                    <p class="h-page-subtitle">Manage and monitor your digital assets across multiple registrars.</p>
                </div>
                <div class="tw-flex tw-gap-3">
                    <?php if (!empty(get_option('domain_manager_hostinger_api_token'))) { ?>
                    <button type="button" id="sync-domains-btn" class="btn-h-filter">
                        <i class="fa fa-refresh"></i>
                        Sync
                    </button>
                    <?php } ?>
                    <button class="btn-h-filter">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
                        Filter
                    </button>
                    <?php if (has_permission('domain_manager', '', 'create')) { ?>
                    <a href="<?= admin_url('domain_manager_hostinger/create') ?>" class="btn-h-add" style="text-decoration: none;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Domain
                    </a>
                    <?php } ?>
                </div>
            </div>

            <div id="domain-sync-result" style="display:none;margin-bottom:12px;"></div>

            <!-- Stats Row -->
            <div class="h-stats-row">
                <div class="h-stat-card">
                    <div class="h-stat-label">
                        <i class="fa fa-external-link"></i> EXTERNAL DOMAINS
                    </div>
                    <div class="h-stat-val"><?php echo sprintf('%02d', $external_domains); ?></div>
                    <div class="h-stat-sub">
                        Active external client domains
                    </div>
                    <div style="position: absolute; top: 20px; right: 22px; color: #6C52E3; font-size: 18px;">
                        <i class="fa fa-external-link"></i>
                    </div>
                </div>
                <div class="h-stat-card">
                    <div class="h-stat-label">
                        <i class="fa fa-globe"></i> INTERNAL DOMAINS
                    </div>
                    <div class="h-stat-val"><?php echo sprintf('%02d', $internal_domains); ?></div>
                    <div class="h-stat-sub">
                        Active company domains
                    </div>
                    <div style="position: absolute; top: 20px; right: 22px; color: #6C52E3; font-size: 18px;">
                        <i class="fa fa-globe"></i>
                    </div>
                </div>
            </div>

            <!-- External Domains Table -->
            <div class="h-table-section">
                <div class="panel-table-full">
                    <?php render_datatable([
                        'EXTERNAL DOMAINS (' . $external_domains . ')',
                        'CLIENT',
                        'REGISTRAR',
                        'EXPIRY DATE',
                        'TYPE',
                        'STATUS',
                        'CREATED DATE',
                        ['name' => 'ACTIONS', 'th_attrs' => ['class' => 'text-right']],
                    ], 'domains-external', ['data-type' => 'external'], [
                        'data-last-order-identifier' => 'domains-external',
                        'data-default-order'         => get_table_last_order('domains-external'),
                        'data-page-length'           => '10',
                    ]); ?>
                </div>
            </div>

            <!-- Internal Domains Table -->
            <div class="h-table-section">
                <div class="panel-table-full">
                    <?php render_datatable([
                        'INTERNAL DOMAINS (' . $internal_domains . ')',
                        'CLIENT',
                        'REGISTRAR',
                        'EXPIRY DATE',
                        'TYPE',
                        'STATUS',
                        'CREATED DATE',
                        ['name' => 'ACTIONS', 'th_attrs' => ['class' => 'text-right']],
                    ], 'domains-internal', ['data-type' => 'internal'], [
                        'data-last-order-identifier' => 'domains-internal',
                        'data-default-order'         => get_table_last_order('domains-internal'),
                        'data-page-length'           => '10',
                    ]); ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function () {
    // Internal domains table
    initDataTable('.table-domains-internal', admin_url + 'domain_manager_hostinger/domain_manager_table?type=internal', undefined, undefined, 'undefined', [3, 'asc']);
    
    // External domains table
    initDataTable('.table-domains-external', admin_url + 'domain_manager_hostinger/domain_manager_table?type=external', undefined, undefined, 'undefined', [3, 'asc']);

    // Sync logic
    $('#sync-domains-btn').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-refresh fa-spin"></i> Syncing...');
        var $result = $('#domain-sync-result').show()
            .removeClass('alert-success alert-danger alert-info')
            .addClass('alert alert-info')
            .html('<i class="fa fa-spinner fa-spin"></i> Syncing domains from Hostinger...');

        $.ajax({
            url: '<?php echo admin_url('domain_manager_hostinger/sync_hostinger_domains'); ?>',
            type: 'POST',
            dataType: 'json',
            data: { <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>' },
            success: function (resp) {
                $result.removeClass('alert-info').addClass(resp.success ? 'alert-success' : 'alert-danger');
                $result.html('<i class="fa ' + (resp.success ? 'fa-check-circle' : 'fa-times-circle') + '"></i> ' + resp.message);
                if (resp.success) {
                    setTimeout(function () { 
                        window.location.reload();
                    }, 800);
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
