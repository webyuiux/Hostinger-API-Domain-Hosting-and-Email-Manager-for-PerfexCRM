<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<style>
    /* Custom CRM Enterprise Styles */
    .crm-bg { background-color: #F8FAFC; min-height: 100vh; padding-bottom: 50px; }
    .crm-card { background: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); border: 1px solid #E2E8F0; transition: transform 0.2s, box-shadow 0.2s; position:relative; }
    .crm-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04); }
    .crm-stat-card { background: #FFFFFF; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #E2E8F0; position: relative; overflow: hidden; }
    .crm-stat-icon { position: absolute; top: 20px; right: 20px; font-size: 24px; opacity: 0.8; }
    .crm-stat-title { font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; padding-right: 28px; line-height: 1.3; }
    .crm-stat-value { font-size: 28px; font-weight: 700; color: #1F2937; margin: 8px 0; }
    
    .crm-badge { padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .crm-badge-success { background: rgba(34, 197, 94, 0.1); color: #22C55E; }
    .crm-badge-danger { background: rgba(239, 68, 68, 0.1); color: #EF4444; }
    .crm-badge-warning { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
    .crm-badge-gray { background: rgba(100, 116, 139, 0.1); color: #64748B; }

    .crm-btn { border-radius: 6px; font-weight: 600; padding: 6px 14px; font-size: 13px; transition: all 0.2s; border: none; }
    .crm-btn-primary { background: #673DE6; color: #fff; }
    .crm-btn-primary:hover { background: #522bce; color: #fff; }
    .crm-btn-view { background: #3B82F6; color: #fff; }
    .crm-btn-edit { background: #F59E0B; color: #fff; }
    .crm-btn-renew { background: #22C55E; color: #fff; }
    .crm-btn-delete { background: #EF4444; color: #fff; }
    
    .website-grid { display: grid; grid-template-columns: 3fr 1.5fr 1fr 1fr 1fr; gap: 16px; align-items: center; width: 100%; }
    .website-grid > div { min-width: 0; } /* Prevent grid blowout from long text */
    
    .crm-toolbar { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: #1F2937; color: white; padding: 12px 24px; border-radius: 99px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); z-index: 50; display: none; align-items: center; gap: 16px; }

    /* Custom Input Styles */
    .crm-input { border-radius: 8px; border: 1px solid #E2E8F0; padding: 10px 14px; width: 100%; transition: border-color 0.2s; background: #fff; }
    .crm-input:focus { border-color: #673DE6; outline: none; box-shadow: 0 0 0 3px rgba(103, 61, 230, 0.1); }
</style>

<div id="wrapper" class="crm-bg">
    <div class="content">
        <div class="wrap">
            
            <!-- Breadcrumb -->
            <div class="h-breadcrumb-premium">
                INFRASTRUCTURE / <span>WEBSITES</span>
            </div>

            <!-- Page Header -->
            <div class="h-page-header">
                <div>
                    <h1 class="h-page-title">Website Management</h1>
                    <p class="h-page-subtitle">Monitor and manage your active hosting plans and deployments.</p>
                </div>
                <div class="tw-flex tw-gap-3 tw-items-center">

                    <!-- Notification Bell -->
                    <div class="dropdown">
                        <button class="btn btn-default dropdown-toggle btn-h-filter" type="button" data-toggle="dropdown">
                            <i class="fa fa-bell <?php echo ($websites_expiring_soon_count > 0) ? 'text-danger' : ''; ?>"></i>
                            <span class="label label-danger tw-ml-1"><?php echo $websites_expiring_soon_count; ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right tw-w-80">
                            <li class="dropdown-header tw-font-bold">Critical Expirations (Next 5 Days)</li>
                            <?php if ($websites_expiring_soon_count > 0): ?>
                                <?php foreach ($websites_expiring_soon_list as $exp): ?>
                                    <li>
                                        <a href="<?php echo admin_url('domain_manager_hostinger/view_hosting/' . $exp['id']); ?>" class="tw-py-3">
                                            <div class="tw-flex tw-justify-between">
                                                <span class="tw-font-medium"><?php echo e($exp['website_name']); ?></span>
                                                <span class="text-danger tw-text-xs">
                                                    <?php echo $exp['expiration_date']; ?>
                                                </span>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="tw-p-4 tw-text-center tw-text-neutral-500">No websites expiring soon.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Bulk Delete (shown when rows are selected) -->
                    <button type="button" id="bulk-delete-btn" class="btn-h-filter" style="display:none; background:#ef4444; color:#fff; border-color:#ef4444;">
                        <i class="fa fa-trash"></i> Delete Selected
                    </button>

                    <?php if (!empty(get_option('domain_manager_hostinger_api_token'))) { ?>
                    <button type="button" id="sync-websites-btn" class="btn-h-filter">
                        <i class="fa fa-refresh"></i>
                        Sync
                    </button>
                    <?php } ?>

                    <button type="button" id="send-alerts-btn" class="btn-h-filter">
                        <i class="fa fa-envelope-o"></i>
                        Send Alerts
                    </button>

                    <?php if (has_permission('domain_manager', '', 'hosting_create')) { ?>
                    <button type="button" class="btn-h-add" data-toggle="modal" data-target="#addWebsiteModal">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Website
                    </button>
                    <?php } ?>
                </div>
            </div>

            <div id="website-sync-result" style="display:none;margin-bottom:12px;"></div>

            <!-- Statistics Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 32px;">
                <div class="crm-stat-card">
                    <i class="fa fa-globe crm-stat-icon" style="color: #673DE6;"></i>
                    <div class="crm-stat-title">Total Websites</div>
                    <div class="crm-stat-value" id="stat-total">0</div>
                    <div class="tw-text-xs tw-text-neutral-500"><span class="tw-text-green-500"><i class="fa fa-arrow-up"></i> All</span> registered assets</div>
                </div>
                <div class="crm-stat-card">
                    <i class="fa fa-check-circle crm-stat-icon" style="color: #22C55E;"></i>
                    <div class="crm-stat-title">Active Websites</div>
                    <div class="crm-stat-value" id="stat-active">0</div>
                    <div class="tw-text-xs tw-text-neutral-500"><span class="tw-text-green-500"><i class="fa fa-check"></i> Healthy</span> deployments</div>
                </div>
                <div class="crm-stat-card">
                    <i class="fa fa-exclamation-triangle crm-stat-icon" style="color: #F59E0B;"></i>
                    <div class="crm-stat-title">Expiring Soon</div>
                    <div class="crm-stat-value" id="stat-expiring">0</div>
                    <div class="tw-text-xs tw-text-neutral-500"><span class="tw-text-orange-500"><i class="fa fa-clock-o"></i> Within 30 days</span></div>
                </div>
                <div class="crm-stat-card">
                    <i class="fa fa-times-circle crm-stat-icon" style="color: #EF4444;"></i>
                    <div class="crm-stat-title">Expired Domains</div>
                    <div class="crm-stat-value" id="stat-expired">0</div>
                    <div class="tw-text-xs tw-text-neutral-500"><span class="tw-text-red-500"><i class="fa fa-warning"></i> Require attention</span></div>
                </div>
                <div class="crm-stat-card">
                    <i class="fa fa-users crm-stat-icon" style="color: #8B5CF6;"></i>
                    <div class="crm-stat-title">Total Clients</div>
                    <div class="crm-stat-value" id="stat-clients">0</div>
                    <div class="tw-text-xs tw-text-neutral-500">Linked to websites</div>
                </div>
            </div>


            <!-- Websites Table -->
            <div class="tw-bg-white tw-shadow-sm tw-border tw-border-neutral-200 tw-mb-10" style="border-radius: 12px; padding: 24px;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle tw-mb-0" style="color: #333; font-size: 14px;">
                        <thead>
                            <tr style="border-bottom: 1px solid #E2E8F0;">
                                <th style="padding: 12px 8px; border-bottom: none; width: 40px;"><input type="checkbox" id="selectAllWebsites"></th>
                                <th style="padding: 12px 8px; border-bottom: none; font-weight: 600; color: #1F2937;">Website</th>
                                <th style="padding: 12px 8px; border-bottom: none; font-weight: 600; color: #1F2937;">Status</th>
                                <th style="padding: 12px 8px; border-bottom: none; font-weight: 600; color: #1F2937;">Provider</th>
                                <th style="padding: 12px 8px; border-bottom: none; font-weight: 600; color: #1F2937;">Client</th>
                                <th style="padding: 12px 8px; border-bottom: none; font-weight: 600; color: #1F2937;">Mailboxes</th>
                                <th style="padding: 12px 8px; border-bottom: none; font-weight: 600; color: #1F2937;">Expiry Date</th>
                                <th style="padding: 12px 8px; border-bottom: none; font-weight: 600; color: #1F2937;">Created Date</th>
                                <th class="text-right" style="padding: 12px 8px; border-bottom: none; font-weight: 600; color: #1F2937;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="websites-container">
                            <!-- Loaded via AJAX -->
                             <tr>
                                 <td colspan="9" class="text-center tw-py-12">
                                     <i class="fa fa-spinner fa-spin tw-text-3xl tw-text-neutral-300"></i>
                                 </td>
                             </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>



<!-- Add Website Modal -->
<div class="modal fade" id="addWebsiteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <form id="addWebsiteForm">
            <div class="modal-content" style="border-radius:12px;border:none;">
                <div class="modal-header tw-bg-[#F8FAFC]" style="border-radius:12px 12px 0 0;border-bottom:1px solid #E2E8F0;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title tw-font-bold tw-text-[#1F2937]">Add New Website</h4>
                </div>
                <div class="modal-body tw-p-6">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="tw-font-bold tw-text-sm">Website Name / URL <span class="tw-text-red-500">*</span></label>
                            <input type="text" name="website_name" class="crm-input" required placeholder="e.g. example.com">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="tw-font-bold tw-text-sm">Linked Client</label>
                            <select name="client_id" class="crm-input">
                                <option value="">Select Client...</option>
                                <?php foreach($clients as $c) { echo "<option value='{$c['userid']}'>".e($c['company'])."</option>"; } ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="tw-font-bold tw-text-sm">Assigned Staff</label>
                            <select name="assigned_staff_id" class="crm-input">
                                <option value="">Select Staff...</option>
                                <?php 
                                $CI =& get_instance();
                                $CI->load->model('staff_model');
                                $staff_members = $CI->staff_model->get('', ['active' => 1]);
                                foreach($staff_members as $m) { 
                                    echo "<option value='{$m['staffid']}'>".e($m['firstname'] . ' ' . $m['lastname'])."</option>"; 
                                } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="tw-font-bold tw-text-sm">Linked Domain</label>
                            <select name="domain_id" class="crm-input">
                                <option value="">Select Domain...</option>
                                <?php foreach($domains as $d) { echo "<option value='{$d['id']}'>".e($d['domain_name'])."</option>"; } ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="tw-font-bold tw-text-sm">Hosting Provider</label>
                            <input type="text" name="domain_manager_provider" class="crm-input" placeholder="e.g. Hostinger, AWS">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="tw-font-bold tw-text-sm">Server Type</label>
                            <select name="server_type" class="crm-input">
                                <option value="Shared">Shared Hosting</option>
                                <option value="VPS">VPS</option>
                                <option value="Dedicated">Dedicated</option>
                                <option value="Cloud">Cloud</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="tw-font-bold tw-text-sm">Website Status</label>
                            <select name="status" class="crm-input">
                                <option value="active">Active</option>
                                <option value="pending">Draft / Pending</option>
                                <option value="suspended">Suspended</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="tw-font-bold tw-text-sm">Domain Status</label>
                            <select name="domain_status" class="crm-input">
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="tw-font-bold tw-text-sm">SSL Status</label>
                            <select name="ssl_status" class="crm-input">
                                <option value="active">Active (Secured)</option>
                                <option value="expired">Expired</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="tw-font-bold tw-text-sm">Start Date</label>
                            <input type="date" name="domain_manager_start_date" class="crm-input">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="tw-font-bold tw-text-sm">Expiry Date</label>
                            <input type="date" name="domain_manager_expiry_date" class="crm-input">
                        </div>
                        <div class="col-md-12 form-group tw-mb-0">
                            <label class="tw-font-bold tw-text-sm">Notes</label>
                            <textarea name="description" class="crm-input" rows="3" placeholder="Additional details..."></textarea>
                        </div>
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #E2E8F0;">
                    <button type="button" class="crm-btn" data-dismiss="modal" style="background:#F1F5F9;color:#475569;">Cancel</button>
                    <button type="submit" class="crm-btn crm-btn-primary">Save Website</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php init_tail(); ?>

<script>
let allWebsites = [];

$(function () {
    loadWebsitePortfolio();

    // Removed filter events since inputs are gone
    
    // Add Website AJAX
    $('#addWebsiteForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        $.ajax({
            url: '<?php echo admin_url('domain_manager_hostinger/save_hosting'); ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp) {
                if(resp.success) {
                    alert_float('success', resp.message);
                    $('#addWebsiteModal').modal('hide');
                    $('#addWebsiteForm')[0].reset();
                    loadWebsitePortfolio();
                } else {
                    alert_float('danger', resp.message);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('Save Website');
            }
        });
    });

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
                if (resp.success) loadWebsitePortfolio();
            },
            error: function () {
                $result.removeClass('alert-info').addClass('alert-danger').html('<i class="fa fa-times-circle"></i> Sync request failed.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Sync');
            }
        });
    });

    // Send Alerts logic — reuses the unified domain+website expiry alerts endpoint
    $('#send-alerts-btn').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-refresh fa-spin"></i> Sending...');
        var $result = $('#website-sync-result').show()
            .removeClass('alert-success alert-danger alert-info')
            .addClass('alert alert-info')
            .html('<i class="fa fa-spinner fa-spin"></i> Processing and sending website expiry alerts...');

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
});

function loadWebsitePortfolio() {
    $.ajax({
        url: '<?php echo admin_url('domain_manager_hostinger/get_websites_json'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function (resp) {
            if (resp.success) {
                allWebsites = resp.data || [];
                updateStats();
                renderWebsites();
            }
        }
    });
}

function updateStats() {
    let active = 0, expiring = 0, expired = 0;
    let clientsSet = new Set();
    const today = new Date();
    today.setHours(0,0,0,0);

    allWebsites.forEach(w => {
        if(w.status === 'active') active++;
        if(w.status === 'expired') expired++;
        if(w.client_id) clientsSet.add(w.client_id);

        if(w.expiration_date) {
            const exp = new Date(w.expiration_date);
            const diffDays = Math.ceil((exp - today) / (1000 * 60 * 60 * 24));
            if(diffDays >= 0 && diffDays <= 30 && w.status !== 'expired') expiring++;
        }
    });

    $('#stat-total').text(allWebsites.length);
    $('#stat-active').text(active);
    $('#stat-expiring').text(expiring);
    $('#stat-expired').text(expired);
    $('#stat-clients').text(clientsSet.size);
}

function renderWebsites() {
    let filtered = allWebsites;

    if (filtered.length === 0) {
        $('#websites-container').html(`
            <tr>
                <td colspan="9" class="text-center tw-py-8 tw-text-neutral-500">No websites found</td>
            </tr>
        `);
        return;
    }

    let html = '';
    filtered.forEach(w => {
        // Badges matching screenshot precisely
        let statusBadge = `<span style="border: 1px solid #86EFAC; color: #16A34A; background: #DCFCE7; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; letter-spacing: 0.5px;">ACTIVE</span>`;
        if(w.status === 'expired' || w.status === 'suspended') statusBadge = `<span style="background: #991B1B; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; letter-spacing: 0.5px;">${(w.status).toUpperCase()}</span>`;
        if(w.status === 'pending') statusBadge = `<span style="border: 1px solid #CBD5E1; color: #64748B; background: #F1F5F9; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; letter-spacing: 0.5px;">DRAFT</span>`;

        const expStr = w.expiration_date ? w.expiration_date : 'N/A';
        const createdStr = w.created_at ? w.created_at.substring(0, 10) : 'N/A';
        let clientName = escapeHtml(w.client_name || 'No Client Linked');
        let provider = escapeHtml(w.provider || 'N/A');
 
        html += `
            <tr style="border-bottom: 1px solid #F1F5F9;">
                <td style="padding: 16px 8px; vertical-align: middle;">
                    <input type="checkbox" class="bulk-website-cb" value="${w.id}">
                </td>
                <td style="padding: 16px 8px; vertical-align: middle; color: #4B5563;">
                    ${w.access_url ? `<a href="${escapeHtml(w.access_url)}" target="_blank" style="color: #4B5563; text-decoration: none;">${escapeHtml(w.access_url)}</a>` : escapeHtml(w.website_name)}
                </td>
                <td style="padding: 16px 8px; vertical-align: middle;">${statusBadge}</td>
                <td style="padding: 16px 8px; vertical-align: middle; color: #4B5563;">${provider}</td>
                <td style="padding: 16px 8px; vertical-align: middle; color: #4B5563;">${clientName}</td>
                <td style="padding: 16px 8px; vertical-align: middle;"><span class="label label-info" style="font-weight: 600; font-size: 12px; padding: 4px 8px; border-radius: 12px;">${w.total_mailboxes || 0}</span></td>
                <td style="padding: 16px 8px; vertical-align: middle; color: #4B5563;">${expStr}</td>
                <td style="padding: 16px 8px; vertical-align: middle; color: #4B5563;">${createdStr}</td>
                <td class="text-right" style="padding: 16px 8px; vertical-align: middle;">
                    <a href="${admin_url}domain_manager_hostinger/view_hosting/${w.id}" class="btn btn-default btn-icon" style="padding: 4px 8px; border-color: #E2E8F0; color: #4B5563;"><i class="fa fa-eye"></i></a>
                    <a href="${admin_url}domain_manager_hostinger/edit_hosting/${w.id}" class="btn btn-default btn-icon" style="padding: 4px 8px; border-color: #E2E8F0; color: #4B5563;"><i class="fa fa-pencil"></i></a>
                    <a href="#" onclick="singleAction('delete', ${w.id}); return false;" class="btn btn-danger btn-icon" style="padding: 4px 8px; background: #DC2626; border-color: #DC2626; color: white;"><i class="fa fa-times"></i></a>
                </td>
            </tr>
        `;
    });

    $('#websites-container').html(html);
}

function singleAction(action, id) {
    if(action === 'delete') {
        if(confirm('Are you sure you want to delete this website?')) {
            window.location.href = '<?php echo admin_url('domain_manager_hostinger/delete_hosting/'); ?>' + id;
        }
    } else if(action === 'renew') {
        if(confirm('Are you sure you want to renew this website for 1 year?')) {
            $.post('<?php echo admin_url('domain_manager_hostinger/bulk_action_hosting'); ?>', {
                action: 'renew',
                ids: [id],
                <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
            }, function(resp) {
                alert_float(resp.success ? 'success' : 'danger', resp.message);
                if(resp.success) loadWebsitePortfolio();
            }, 'json');
        }
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

$(document).on('change', '#selectAllWebsites', function() {
    $('.bulk-website-cb').prop('checked', $(this).prop('checked'));
    toggleBulkDeleteBtn();
});

$(document).on('change', '.bulk-website-cb', function() {
    toggleBulkDeleteBtn();
    if ($('.bulk-website-cb:checked').length === $('.bulk-website-cb').length) {
        $('#selectAllWebsites').prop('checked', true);
    } else {
        $('#selectAllWebsites').prop('checked', false);
    }
});

function toggleBulkDeleteBtn() {
    if ($('.bulk-website-cb:checked').length > 0) {
        $('#bulk-delete-btn').show();
    } else {
        $('#bulk-delete-btn').hide();
    }
}

$('#bulk-delete-btn').on('click', function() {
    var ids = [];
    $('.bulk-website-cb:checked').each(function() {
        ids.push($(this).val());
    });
    
    if (ids.length === 0) return;

    if (confirm('Are you sure you want to delete ' + ids.length + ' selected websites?')) {
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin tw-mr-1"></i> Deleting...');
        $.post('<?php echo admin_url('domain_manager_hostinger/bulk_action_hosting'); ?>', {
            action: 'delete',
            ids: ids,
            <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
        }, function(resp) {
            alert_float(resp.success ? 'success' : 'danger', resp.message);
            if(resp.success) {
                $('#selectAllWebsites').prop('checked', false);
                $('#bulk-delete-btn').hide();
                loadWebsitePortfolio();
            }
            $btn.prop('disabled', false).html('<i class="fa fa-trash tw-mr-1"></i> Delete Selected');
        }, 'json');
    }
});
</script>
</body>
</html>
