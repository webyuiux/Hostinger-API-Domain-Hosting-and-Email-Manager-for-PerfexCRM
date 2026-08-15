<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <?php
        /**
         * Helper function to render domain table rows in PHP
         */
        if (!function_exists('render_domain_portfolio_card_php')) {
            function render_domain_portfolio_card_php($domain) {
                $status = strtolower($domain['status'] ?? 'pending');
                $status_text = strtoupper($status);
                $badge_class = 'label-default';
                $style = '';

                if ($status === 'active') {
                    $badge_class = 'label-success';
                } elseif ($status === 'expired' || $status === 'suspended') {
                    $badge_class = 'label-danger';
                } elseif ($status === 'pending') {
                    $badge_class = 'label-warning';
                }

                // Check for expiry warning on active domains
                if ($status === 'active' && !empty($domain['expiry_date']) && $domain['expiry_date'] !== '0000-00-00') {
                    $today = strtotime(date('Y-m-d'));
                    $expiry = strtotime($domain['expiry_date']);
                    $diff = round(($expiry - $today) / (60 * 60 * 24));

                    if ($diff < 0) {
                        $style = 'background-color: #7d0101; color: #fff;';
                        $status_text = 'EXPIRED';
                    } elseif ($diff <= 1) {
                        $badge_class = 'label-danger';
                        $status_text = 'EXPIRING SOON (1 day)';
                    } elseif ($diff <= 3) {
                        $style = 'background-color: #ff8c00; color: #fff;';
                        $status_text = 'EXPIRING SOON (' . $diff . ' days)';
                    } elseif ($diff <= 30) {
                        $style = $diff <= 5 ? 'background-color: #ffd700; color: #000;' : '';
                        $badge_class = $diff <= 5 ? '' : 'label-warning';
                        $status_text = 'EXPIRING SOON (' . $diff . ' days)';
                    }
                }

                $CI = &get_instance();

                // Client Email logic
                $client_email = !empty($domain['client_email']) ? $domain['client_email'] : '';
                if (empty($client_email) && !empty($domain['client_id'])) {
                    $contact = $CI->db->select('email')->where('userid', $domain['client_id'])->where('is_primary', 1)->get(db_prefix() . 'contacts')->row();
                    if ($contact) {
                        $client_email = $contact->email;
                    }
                }
                if (empty($client_email)) {
                    $client_email = '—';
                }

                // Created Date logic
                $created_date = '—';
                if (!empty($domain['start_date']) && $domain['start_date'] !== '0000-00-00') {
                    $created_date = _d($domain['start_date']);
                } elseif (!empty($domain['purchase_date']) && $domain['purchase_date'] !== '0000-00-00') {
                    $created_date = _d($domain['purchase_date']);
                } elseif (!empty($domain['created_at']) && $domain['created_at'] !== '0000-00-00 00:00:00') {
                    $created_date = _d(date('Y-m-d', strtotime($domain['created_at'])));
                }
                ?>
                <tr>
                    <td><strong><?php echo e($domain['domain_name']); ?></strong></td>
                    <td>
                        <?php if(!empty($domain['client_id'])): ?>
                            <a href="<?php echo admin_url('clients/client/' . $domain['client_id']); ?>"><?php echo e($domain['client_name']); ?></a>
                        <?php else: ?>
                            <span class="text-muted">No Client Linked</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($client_email); ?></td>
                    <td><?php echo $created_date; ?></td>
                    <td><?php echo !empty($domain['expiry_date']) && $domain['expiry_date'] !== '0000-00-00' ? _d($domain['expiry_date']) : '—'; ?></td>
                    <td>
                        <?php
                        $mailbox_count = $CI->db->where('domain', $domain['domain_name'])->where('deleted', 0)->count_all_results(db_prefix() . 'emails_manager');
                        ?>
                        <span class="label label-info" style="font-weight: 600; font-size: 12px; padding: 4px 8px; border-radius: 12px;"><?= $mailbox_count ?></span>
                    </td>
                    <td><span class="label <?php echo $badge_class; ?>" style="<?php echo $style; ?>"><?php echo $status_text; ?></span></td>
                    <td class="text-right">
                        <div class="tw-flex tw-justify-end tw-items-center" style="gap: 4px; flex-wrap: nowrap !important; white-space: nowrap !important; width: max-content; margin-left: auto;">
                            <a href="<?php echo admin_url('domain_manager_hostinger/view/' . $domain['id']); ?>" class="btn btn-default btn-icon btn-sm" title="View"><i class="fa fa-eye"></i></a>
                            <a href="<?php echo admin_url('domain_manager_hostinger/edit/' . $domain['id']); ?>" class="btn btn-default btn-icon btn-sm" title="Edit"><i class="fa fa-pencil"></i></a>
                            <a href="<?php echo admin_url('domain_manager_hostinger/delete/' . $domain['id']); ?>" class="btn btn-danger btn-icon btn-sm" onclick="return confirm('Are you sure you want to delete this domain and all associated mailboxes?');" title="Delete"><i class="fa fa-remove"></i></a>
                        </div>
                    </td>
                </tr>
                <?php
            }
        }
        ?>

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
                    <!-- Notification Bell -->
                    <div class="dropdown">
                        <button class="btn btn-default dropdown-toggle btn-h-filter" type="button" data-toggle="dropdown">
                            <i class="fa fa-bell <?php echo ($expiring_soon_count > 0) ? 'text-danger' : ''; ?>"></i>
                            <span class="label label-danger tw-ml-1"><?php echo $expiring_soon_count; ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right tw-w-80">
                            <li class="dropdown-header tw-font-bold">Critical Expirations (Next 5 Days)</li>
                            <?php if ($expiring_soon_count > 0): ?>
                                <?php foreach ($expiring_soon_list as $exp): ?>
                                    <li>
                                        <a href="<?php echo admin_url('domain_manager_hostinger/view/' . $exp['id']); ?>" class="tw-py-3">
                                            <div class="tw-flex tw-justify-between">
                                                <span class="tw-font-medium"><?php echo e($exp['domain_name']); ?></span>
                                                <span class="text-danger tw-text-xs">
                                                    <?php echo _d($exp['expiry_date']); ?>
                                                </span>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="tw-p-4 tw-text-center tw-text-neutral-500">No domains expiring soon.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <?php if (!empty(get_option('domain_manager_hostinger_api_token'))) { ?>
                    <button type="button" id="sync-domains-btn" class="btn-h-filter">
                        <i class="fa fa-refresh"></i>
                        Sync
                    </button>
                    <?php } ?>
                    <button type="button" id="send-alerts-btn" class="btn-h-filter">
                        <i class="fa fa-envelope-o"></i>
                        Send Alerts
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
                    <div class="h-stat-val" id="external-stat-val"><?php echo sprintf('%02d', $external_domains); ?></div>
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
                    <div class="h-stat-val" id="internal-stat-val"><?php echo sprintf('%02d', $internal_domains); ?></div>
                    <div class="h-stat-sub">
                        Active company domains
                    </div>
                    <div style="position: absolute; top: 20px; right: 22px; color: #6C52E3; font-size: 18px;">
                        <i class="fa fa-globe"></i>
                    </div>
                </div>
                <div class="h-stat-card <?php echo ($expiring_soon > 0) ? 'tw-border-l-4 tw-border-red-500' : ''; ?>" id="expiring-stat-card">
                    <div class="h-stat-label">
                        <i class="fa fa-clock-o"></i> EXPIRING SOON
                    </div>
                    <div class="h-stat-val <?php echo ($expiring_soon > 0) ? 'text-danger' : ''; ?>" id="expiring-stat-val"><?php echo sprintf('%02d', $expiring_soon); ?></div>
                    <div class="h-stat-sub">
                        Domains expiring in next 5 days
                    </div>
                    <div style="position: absolute; top: 20px; right: 22px; color: #ff2d42; font-size: 18px;">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>



            <!-- External Domains Container -->
            <div class="h-table-section tw-mb-10" style="padding: 24px;">
                <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
                    <h3 class="h-portfolio-title" style="margin:0; font-size: 18px; font-weight: 700; color: #333;">
                        <i class="fa fa-external-link tw-mr-2"></i> EXTERNAL DOMAINS
                    </h3>
                    <span class="badge badge-info" id="external-count-badge"><?php echo count($external_domains_list); ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle tw-mb-0" id="table-external-domains">
                        <thead>
                            <tr>
                                <th>Domain Name</th>
                                <th>Assigned Client</th>
                                <th>Client Email</th>
                                <th>Created Date</th>
                                <th>Expiry Date</th>
                                <th>Mailboxes</th>
                                <th>Status</th>
                                <th class="text-right" style="width: 130px; min-width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="external-domains-container">
                            <?php if(!empty($external_domains_list)): ?>
                                <?php foreach($external_domains_list as $domain): render_domain_portfolio_card_php($domain); endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center tw-py-8 tw-text-neutral-500">No external domains found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Internal Domains Container -->
            <div class="h-table-section" style="padding: 24px;">
                <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
                    <h3 class="h-portfolio-title" style="margin:0; font-size: 18px; font-weight: 700; color: #333;">
                        <i class="fa fa-globe tw-mr-2"></i> INTERNAL DOMAINS
                    </h3>
                    <span class="badge badge-info" id="internal-count-badge"><?php echo count($internal_domains_list); ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle tw-mb-0" id="table-internal-domains">
                        <thead>
                            <tr>
                                <th>Domain Name</th>
                                <th>Assigned Client</th>
                                <th>Client Email</th>
                                <th>Created Date</th>
                                <th>Expiry Date</th>
                                <th>Mailboxes</th>
                                <th>Status</th>
                                <th class="text-right" style="width: 130px; min-width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="internal-domains-container">
                            <?php if(!empty($internal_domains_list)): ?>
                                <?php foreach($internal_domains_list as $domain): render_domain_portfolio_card_php($domain); endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center tw-py-8 tw-text-neutral-500">No internal domains found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function () {
    // Load portfolio view
    loadDomainPortfolio();

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
                        loadDomainPortfolio(); // Reload portfolio
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

    // Send alerts logic
    $('#send-alerts-btn').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-refresh fa-spin"></i> Sending...');
        var $result = $('#domain-sync-result').show()
            .removeClass('alert-success alert-danger alert-info')
            .addClass('alert alert-info')
            .html('<i class="fa fa-spinner fa-spin"></i> Processing and sending expiry alerts...');

        $.ajax({
            url: '<?php echo admin_url('domain_manager_hostinger/send_expiry_alerts_ajax'); ?>',
            type: 'GET',
            dataType: 'json',
            success: function (resp) {
                $result.removeClass('alert-info').addClass(resp.success ? 'alert-success' : 'alert-danger');
                $result.html('<i class="fa ' + (resp.success ? 'fa-check-circle' : 'fa-times-circle') + '"></i> ' + resp.message);
            },
            error: function () {
                $result.removeClass('alert-info').addClass('alert-danger').html('<i class="fa fa-times-circle"></i> Send alerts request failed.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fa fa-envelope-o"></i> Send Alerts');
            }
        });
    });

    // Check for redirect from save and refresh portfolio
    if (window.location.hash === '#domain-saved') {
        window.location.hash = '';
        loadDomainPortfolio();
    }


    
/**
 * Load and display domains in portfolio view
 */
function loadDomainPortfolio() {
    let externalCount = 0;
    let internalCount = 0;
    let externalExpiring = 0;
    let internalExpiring = 0;

    function countExpiringSoon(domains) {
        let count = 0;
        const today = new Date();
        today.setHours(0,0,0,0);
        const limitDate = new Date();
        limitDate.setDate(today.getDate() + 5);
        limitDate.setHours(23,59,59,999);

        domains.forEach(function (domain) {
            if (domain.status === 'active' && domain.expiry_date && domain.expiry_date !== '0000-00-00') {
                const expiry = new Date(domain.expiry_date + 'T00:00:00');
                if (expiry <= limitDate) {
                    count++;
                }
            }
        });
        return count;
    }

    function updateStats() {
        let totalExpiring = externalExpiring + internalExpiring;
        $('#external-stat-val').text(String(externalCount).padStart(2, '0'));
        $('#internal-stat-val').text(String(internalCount).padStart(2, '0'));
        
        let $expiringVal = $('#expiring-stat-val');
        $expiringVal.text(String(totalExpiring).padStart(2, '0'));
        if (totalExpiring > 0) {
            $expiringVal.addClass('text-danger');
            $expiringVal.closest('.h-stat-card').addClass('tw-border-l-4 tw-border-red-500');
        } else {
            $expiringVal.removeClass('text-danger');
            $expiringVal.closest('.h-stat-card').removeClass('tw-border-l-4 tw-border-red-500');
        }
    }

    // Load external domains
    $.ajax({
        url: '<?php echo admin_url('domain_manager_hostinger/get_domains_json'); ?>',
        type: 'GET',
        dataType: 'json',
        cache: false,
        data: { type: 'external' },
        success: function (resp) {
            if (resp.success && resp.data.length > 0) {
                $('#external-domains-container').html(renderDomainCards(resp.data));
                $('#external-count-badge').text(resp.data.length);
                externalCount = resp.data.length;
                externalExpiring = countExpiringSoon(resp.data);
            } else {
                $('#external-domains-container').html('<tr><td colspan="8" class="text-center tw-py-8 tw-text-neutral-500">No external domains found</td></tr>');
                $('#external-count-badge').text('0');
                externalCount = 0;
                externalExpiring = 0;
            }
            updateStats();
        }
    });

    // Load internal domains
    $.ajax({
        url: '<?php echo admin_url('domain_manager_hostinger/get_domains_json'); ?>',
        type: 'GET',
        dataType: 'json',
        cache: false,
        data: { type: 'internal' },
        success: function (resp) {
            if (resp.success && resp.data.length > 0) {
                $('#internal-domains-container').html(renderDomainCards(resp.data));
                $('#internal-count-badge').text(resp.data.length);
                internalCount = resp.data.length;
                internalExpiring = countExpiringSoon(resp.data);
            } else {
                $('#internal-domains-container').html('<tr><td colspan="8" class="text-center tw-py-8 tw-text-neutral-500">No internal domains found</td></tr>');
                $('#internal-count-badge').text('0');
                internalCount = 0;
                internalExpiring = 0;
            }
            updateStats();
        }
    });
}

/**
 * Render domain cards HTML
 */
function renderDomainCards(domains) {
    if (!domains || domains.length === 0) {
        return '<tr><td colspan="8" class="text-center tw-py-8 tw-text-neutral-500">No domains found</td></tr>';
    }

    let html = '';
    domains.forEach(function (domain) {
        let statusClass = 'label-success';
        let statusLabel = 'ACTIVE';
        let style = '';

        if (domain.status === 'expired') {
            statusClass = 'label-danger';
            statusLabel = 'EXPIRED';
        } else if (domain.status === 'pending') {
            statusClass = 'label-warning';
            statusLabel = 'PENDING';
        } else if (domain.status === 'suspended') {
            statusClass = 'label-danger';
            statusLabel = 'SUSPENDED';
        } else if (domain.expiry_date && domain.expiry_date !== '0000-00-00') {
            const expiryDate = new Date(domain.expiry_date);
            const today = new Date();
            const daysLeft = Math.floor((expiryDate - today) / (1000 * 60 * 60 * 24));
            
            if (daysLeft < 0) {
                style = 'background-color: #7d0101; color: #fff;';
                statusLabel = 'EXPIRED';
            } else if (daysLeft <= 1) {
                statusClass = 'label-danger';
                statusLabel = 'EXPIRING SOON (1 day)';
            } else if (daysLeft <= 3) {
                style = 'background-color: #ff8c00; color: #fff;';
                statusLabel = 'EXPIRING SOON (' + daysLeft + ' days)';
            } else if (daysLeft <= 30) {
                style = daysLeft <= 5 ? 'background-color: #ffd700; color: #000;' : '';
                statusClass = daysLeft <= 5 ? '' : 'label-warning';
                statusLabel = 'EXPIRING SOON (' + daysLeft + ' days)';
            }
        }

        const purchaseDate = domain.purchase_date && domain.purchase_date !== '0000-00-00' ? domain.purchase_date : 'N/A';
        const expiryDate = domain.expiry_date && domain.expiry_date !== '0000-00-00' ? domain.expiry_date : 'N/A';
        const clientName = domain.client_name || 'No Client Linked';
        const clientLink = domain.client_id ? '<a href="' + admin_url + 'clients/client/' + domain.client_id + '">' + escapeHtml(clientName) + '</a>' : '<span class="text-muted">No Client Linked</span>';

        html += '<tr>';
        html += '<td><strong>' + escapeHtml(domain.domain_name) + '</strong></td>';
        html += '<td>' + clientLink + '</td>';
        html += '<td>' + escapeHtml(domain.formatted_client_email) + '</td>';
        html += '<td>' + escapeHtml(domain.formatted_created_date) + '</td>';
        html += '<td>' + escapeHtml(expiryDate) + '</td>';
        html += '<td><span class="label label-info" style="font-weight: 600; font-size: 12px; padding: 4px 8px; border-radius: 12px;">' + (domain.total_mailboxes || 0) + '</span></td>';
        html += '<td><span class="label ' + statusClass + '" style="' + style + '">' + statusLabel + '</span></td>';
        html += '<td class="text-right">';
        html += '<div class="tw-flex tw-justify-end tw-items-center" style="gap: 4px; flex-wrap: nowrap !important; white-space: nowrap !important; width: max-content; margin-left: auto;">';
        html += '<a href="' + admin_url + 'domain_manager_hostinger/view/' + domain.id + '" class="btn btn-default btn-icon btn-sm" title="View"><i class="fa fa-eye"></i></a>';
        html += '<a href="' + admin_url + 'domain_manager_hostinger/edit/' + domain.id + '" class="btn btn-default btn-icon btn-sm" title="Edit"><i class="fa fa-pencil"></i></a>';
        html += '<a href="' + admin_url + 'domain_manager_hostinger/delete/' + domain.id + '" class="btn btn-danger btn-icon btn-sm" onclick="return confirm(\'Are you sure you want to delete this domain and all associated mailboxes?\');" title="Delete"><i class="fa fa-remove"></i></a>';
        html += '</div></td></tr>';
    });

    return html;
}

/**
 * Escape HTML special characters
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
});
</script>
</body>
</html>
