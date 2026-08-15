<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<style>
    .h-table-section table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.05em;
        color: #64748B;
        border-bottom: 2px solid #F1F5F9 !important;
        padding: 14px 16px !important;
    }
    .h-table-section table td {
        padding: 16px !important;
        vertical-align: middle !important;
        border-bottom: 1px solid #F1F5F9 !important;
        font-size: 13.5px;
        color: #334155;
    }
    .h-table-section table tr:hover {
        background-color: #F8FAFC !important;
    }
    .btn-dropdown-mailbox {
        border-radius: 20px !important;
        border-color: #E2E8F0 !important;
        padding: 6px 14px !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        color: #4F46E5 !important;
        background-color: #EEF2F6 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        transition: all 0.2s ease !important;
    }
    .btn-dropdown-mailbox:hover, .btn-dropdown-mailbox:focus {
        background-color: #E0E7FF !important;
        border-color: #C7D2FE !important;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.08) !important;
    }
    .btn-dropdown-client {
        border-radius: 20px !important;
        border-color: #E2E8F0 !important;
        padding: 6px 14px !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        color: #475569 !important;
        background-color: #F8FAFC !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        transition: all 0.2s ease !important;
    }
    .btn-dropdown-client:hover, .btn-dropdown-client:focus {
        background-color: #F1F5F9 !important;
        border-color: #CBD5E1 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04) !important;
    }
    .premium-dropdown-menu {
        min-width: 300px !important;
        padding: 8px 0 !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1) !important;
        border: 1px solid #F1F5F9 !important;
        margin-top: 6px !important;
    }
    .client-link-premium:hover {
        color: #4F46E5 !important;
    }
    .client-link-premium:hover span {
        border-bottom-color: #4F46E5 !important;
    }
</style>

<div id="wrapper">
    <div class="content">
        <div class="wrap">
            
            <!-- Breadcrumb -->
            <div class="h-breadcrumb-premium">
                INFRASTRUCTURE / <span>EMAILS</span>
            </div>

            <!-- Page Header -->
            <div class="h-page-header">
                <div>
                    <h1 class="h-page-title">Email Accounts</h1>
                    <p class="h-page-subtitle">Manage email mailboxes associated with your domains.</p>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2" style="display: flex; gap: 8px; align-items: center;">
                    <button type="button" class="btn btn-danger btn-sm" id="btn-delete-selected-emails" style="display: none;">
                        <i class="fa fa-trash"></i> Delete Selected (<span id="selected-emails-count">0</span>)
                    </button>
                    <?php if (has_permission('domain_manager', '', 'hosting_create')) { ?>
                        <a href="<?= admin_url('domain_manager_hostinger/email_create') ?>" class="btn-h-add" style="text-decoration: none;">
                            <i class="fa fa-plus tw-mr-1"></i> Add Email Account
                        </a>
                    <?php } ?>
                </div>
            </div>


            <!-- Email Accounts Container -->
            <div class="h-table-section">
                <div class="tw-bg-white tw-shadow-sm tw-border tw-border-neutral-200" style="border-radius: 12px; padding: 24px;">
                    <div class="table-responsive">
                        <table class="table align-middle tw-mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 30px;"><input type="checkbox" id="chk-select-all-emails" style="cursor: pointer; transform: scale(1.1);"></th>
                                    <th>Domain Name</th>
                                    <th>Total Mailboxes</th>
                                    <th>Assigned Client</th>
                                    <th>Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (!empty($domains)): 
                                    foreach ($domains as $domain): 
                                        $mailbox_count = $this->db->where('domain', $domain['domain_name'])->where('deleted', 0)->count_all_results(db_prefix() . 'emails_manager');
                                ?>
                                        <tr>
                                            <td><input type="checkbox" class="chk-mailbox-item" value="<?= $domain['id'] ?>" style="cursor: pointer; transform: scale(1.1);"></td>
                                            <td>
                                                <span class="tw-font-medium tw-text-neutral-700" style="font-weight: 600; font-size: 14px;">
                                                    <?php echo e($domain['domain_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="label label-info" style="font-weight: 600; font-size: 12px; padding: 4px 10px; border-radius: 12px;">
                                                    <?= $mailbox_count ?> <?= $mailbox_count == 1 ? 'Mailbox' : 'Mailboxes' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($domain['client_id'])): ?>
                                                    <a href="<?php echo admin_url('clients/client/' . $domain['client_id']); ?>" class="client-link-premium" style="font-weight: 600; color: #1E293B; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: color 0.15s ease;">
                                                        <i class="fa fa-building-o" style="color: #4F46E5; font-size: 14px;"></i> 
                                                        <span style="border-bottom: 1px dashed #CBD5E1;"><?php echo e($domain['client_name']); ?></span>
                                                    </a>
                                                <?php else: ?>
                                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 500; color: #64748B; background-color: #F1F5F9; padding: 4px 8px; border-radius: 12px; border: 1px solid #E2E8F0;">
                                                        <i class="fa fa-unlink" style="font-size: 10px;"></i> Unassigned
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="label label-<?= ($domain['status'] == 'active') ? 'success' : (($domain['status'] == 'expired' || $domain['status'] == 'suspended') ? 'danger' : 'warning') ?>">
                                                    <?= ucfirst(e($domain['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <div class="tw-flex tw-justify-end tw-gap-1">
                                                    <a href="<?= admin_url('domain_manager_hostinger/domain_email_edit/' . $domain['id']) ?>" class="btn btn-default btn-icon btn-xs" title="Edit"><i class="fa fa-pencil"></i></a>
                                                    <a href="<?= admin_url('domain_manager_hostinger/domain_email_delete/' . $domain['id']) ?>" class="btn btn-danger btn-icon btn-xs" onclick="return confirm('Are you sure you want to delete all mailboxes for this domain?');" title="Delete"><i class="fa fa-remove"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center tw-py-8 tw-text-neutral-500">No domains found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
    $(function() {
        $(".menu-item-domain_manager").addClass('active');
        $(".sub-menu-item-emails_view_list").addClass('active');

        // Handle "Select All" checkbox
        $('#chk-select-all-emails').on('change', function() {
            let isChecked = $(this).prop('checked');
            $('.chk-mailbox-item').prop('checked', isChecked);
            updateDeleteSelectedButton();
        });

        // Handle individual checkbox changes
        $('body').on('change', '.chk-mailbox-item', function() {
            let total = $('.chk-mailbox-item').length;
            let checked = $('.chk-mailbox-item:checked').length;
            $('#chk-select-all-emails').prop('checked', total === checked);
            updateDeleteSelectedButton();
        });

        function updateDeleteSelectedButton() {
            let checkedCount = $('.chk-mailbox-item:checked').length;
            if (checkedCount > 0) {
                $('#selected-emails-count').text(checkedCount);
                $('#btn-delete-selected-emails').show();
            } else {
                $('#btn-delete-selected-emails').hide();
            }
        }

        // Handle Delete Selected Button Click
        $('#btn-delete-selected-emails').on('click', function() {
            let selectedIds = [];
            $('.chk-mailbox-item:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) return;

            if (confirm('Are you sure you want to delete the ' + selectedIds.length + ' selected email account(s)?')) {
                $.ajax({
                    url: '<?= admin_url("domain_manager_hostinger/delete_multiple_mailboxes_ajax") ?>',
                    type: 'POST',
                    data: {
                        ids: selectedIds
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.success) {
                            alert_float('success', resp.message);
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            alert_float('danger', resp.message);
                        }
                    },
                    error: function() {
                        alert_float('danger', 'Failed to delete selected email accounts.');
                    }
                });
            }
        });
    });
</script>
</body>
</html>