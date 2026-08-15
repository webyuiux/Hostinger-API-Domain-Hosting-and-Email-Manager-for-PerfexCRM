<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <?= form_hidden('domain_id', $domain->id) ?>
    <div class="content">
        <div class="tw-mb-8">
            <!-- Breadcrumbs -->
            <div class="h-breadcrumbs" style="font-size: 13px; color: #6b7280; margin-bottom: 15px;">
                <a href="<?= admin_url() ?>" style="color: #3b82f6; text-decoration: none;">Dashboard</a>
                <span class="tw-mx-2">></span>
                <a href="<?= admin_url('domain_manager_hostinger') ?>" style="color: #3b82f6; text-decoration: none;">Domain Portfolio</a>
                <span class="tw-mx-2">></span>
                <span style="color: #374151;"><?= e($domain->domain_name); ?></span>
            </div>

            <div class="tw-flex tw-justify-between tw-items-start">
                <div>
                    <h3 style="font-size: 28px; font-weight: 500; color: #111827; margin: 0; line-height: 1.2;">
                        <?= e($domain->domain_name); ?>
                    </h3>
                    <p style="font-size: 16px; color: #6b7280; margin-top: 5px; margin-bottom: 0;">WHOIS Information</p>
                </div>
                <div class="tw-flex tw-gap-3">
                    <a href="<?= admin_url('domain_manager_hostinger/edit/' . $domain->id) ?>" class="btn btn-primary" style="background-color: #6366f1; border-color: #6366f1; padding: 8px 20px; font-weight: 500; border-radius: 6px;">
                        Edit Domain
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Domain Overview (WHOIS Style) -->
            <div class="col-md-8">
                <div class="panel_s" style="border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: none;">
                    <div class="panel-body" style="padding: 30px;">
                        
                        <!-- Domain Information -->
                        <h4 style="font-size: 18px; font-weight: 600; color: #111827; margin-top: 0; margin-bottom: 25px;">Domain Information</h4>
                        
                        <div style="display: flex; margin-bottom: 15px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Created</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;">
                                <?php
                                $created_val = !empty($whois['purchase_date']) ? $whois['purchase_date'] : ($domain->purchase_date ?? '');
                                echo !empty($created_val) ? _d($created_val) : '—';
                                ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; margin-bottom: 15px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Updated</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;">
                                <?php
                                $updated_val = !empty($whois['updated_date']) ? $whois['updated_date'] : ($domain->updated_at ?? '');
                                echo !empty($updated_val) ? _d(date('Y-m-d', strtotime($updated_val))) : '—';
                                ?>
                            </div>
                        </div>

                        <div style="display: flex; margin-bottom: 15px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Expires</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;">
                                <?php
                                $expiry_val = !empty($whois['expiry_date']) ? $whois['expiry_date'] : ($domain->expiry_date ?? '');
                                echo !empty($expiry_val) ? _d($expiry_val) : '—';
                                ?>
                            </div>
                        </div>

                        <div style="display: flex; margin-bottom: 15px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Nameservers</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;">
                                ns1.dns-parking.com<br>
                                ns2.dns-parking.com
                            </div>
                        </div>

                        <div style="display: flex; margin-bottom: 35px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Domain Status</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;">
                                <?php
                                $status = strtolower($domain->status);
                                if ($status === 'active') {
                                    echo 'Active';
                                } else {
                                    echo ucfirst($status);
                                }
                                ?>
                            </div>
                        </div>

                        <hr style="border-top: 1px solid #e5e7eb; margin-bottom: 30px;">

                        <!-- Registrar Information -->
                        <h4 style="font-size: 18px; font-weight: 600; color: #111827; margin-top: 0; margin-bottom: 25px;">Registrar Information</h4>
                        
                        <div style="display: flex; margin-bottom: 15px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Registrar</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;">
                                <?php
                                $registrar_val = !empty($whois['registrar']) ? $whois['registrar'] : ($domain->registrar ?? 'HOSTINGER operations, UAB');
                                echo e($registrar_val);
                                ?>
                            </div>
                        </div>

                        <div style="display: flex; margin-bottom: 35px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Referral URL</div>
                            <div style="flex: 1; font-size: 15px;">
                                <a href="http://www.hostinger.com" target="_blank" style="color: #3b82f6; text-decoration: none;">http://www.hostinger.com</a>
                            </div>
                        </div>

                        <hr style="border-top: 1px solid #e5e7eb; margin-bottom: 30px;">

                        <!-- Registrant Contact Information -->
                        <h4 style="font-size: 18px; font-weight: 600; color: #111827; margin-top: 0; margin-bottom: 25px;">Registrant Contact Information</h4>
                        
                        <div style="display: flex; margin-bottom: 15px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Name</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;"><?= !empty($domain->client_name) ? e($domain->client_name) : '—' ?></div>
                        </div>

                        <div style="display: flex; margin-bottom: 15px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Email</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;">—</div>
                        </div>

                        <div style="display: flex; margin-bottom: 15px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Phone</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;">—</div>
                        </div>

                        <hr style="border-top: 1px solid #e5e7eb; margin-top: 30px; margin-bottom: 30px;">

                        <!-- Mailboxes Section -->
                        <div class="tw-flex tw-justify-between tw-items-center tw-mb-6">
                            <h4 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0;">Mailboxes (<?= count($mailboxes) ?>)</h4>
                            <a href="<?= admin_url('domain_manager_hostinger/email_create?domain=' . urlencode($domain->domain_name) . '&client_id=' . $domain->client_id) ?>" class="btn btn-primary btn-sm" style="background-color: #6366f1; border-color: #6366f1; border-radius: 4px;">
                                <i class="fa fa-plus tw-mr-1"></i> Add Mailbox
                            </a>
                        </div>
                        
                        <?php
                        $CI = &get_instance();
                        $mailboxes = $CI->db->where('domain', $domain->domain_name)->where('deleted', 0)->get(db_prefix() . 'emails_manager')->result_array();
                        ?>
                        
                        <?php if (empty($mailboxes)) { ?>
                            <p class="text-muted tw-mb-8">No mailboxes configured for this domain.</p>
                        <?php } else { ?>
                            <div class="table-responsive tw-mb-8">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Email Address</th>
                                            <th>Status</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mailboxes as $mailbox) { ?>
                                            <tr>
                                                <td>
                                                    <a href="mailto:<?= e($mailbox['mailbox_name']) ?>" style="font-weight: 500; color: #3b82f6;">
                                                        <?= e($mailbox['mailbox_name']) ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="label label-<?= ($mailbox['status'] == 'active') ? 'success' : 'warning' ?>">
                                                        <?= ucfirst(e($mailbox['status'])) ?>
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    <div class="tw-flex tw-justify-end tw-gap-1">
                                                        <a href="<?= admin_url('domain_manager_hostinger/email_view/' . $mailbox['id']) ?>" class="btn btn-default btn-icon btn-xs" title="View"><i class="fa fa-eye"></i></a>
                                                        <a href="<?= admin_url('domain_manager_hostinger/email_edit/' . $mailbox['id']) ?>" class="btn btn-default btn-icon btn-xs" title="Edit"><i class="fa fa-pencil"></i></a>
                                                        <a href="<?= admin_url('domain_manager_hostinger/email_delete/' . $mailbox['id']) ?>" class="btn btn-danger btn-icon btn-xs _delete" title="Delete"><i class="fa fa-remove"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>

                        <?php if (!empty($domain->description)) { ?>
                        <hr style="border-top: 1px solid #e5e7eb; margin-top: 30px; margin-bottom: 30px;">
                        <h4 style="font-size: 18px; font-weight: 600; color: #111827; margin-top: 0; margin-bottom: 25px;">Internal Notes</h4>
                        <div style="color: #4b5563; font-size: 15px; line-height: 1.6; margin-bottom: 30px;">
                            <?= $domain->description ?>
                        </div>
                        <?php } ?>

                        <hr style="border-top: 1px solid #e5e7eb; margin-top: 30px; margin-bottom: 30px;">
                        <h4 style="font-size: 18px; font-weight: 600; color: #111827; margin-top: 0; margin-bottom: 25px;">Raw WHOIS Data</h4>
                        <div style="background-color: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 13px; white-space: pre-wrap; word-wrap: break-word; overflow-x: auto; max-height: 400px; overflow-y: auto; border: 1px solid #333;">
                            <?= e($whois_raw) ?>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Hosting Side Card -->
            <div class="col-md-4">
                <?php if (isset($hosting) && !empty($hosting)) { ?>
                <div class="panel_s" style="border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: none;">
                    <div class="panel-body" style="padding: 30px;">
                        <h4 style="font-size: 18px; font-weight: 600; color: #111827; margin-top: 0; margin-bottom: 25px;">Website Details</h4>
                        
                        <div class="tw-flex tw-items-center tw-mb-8">
                            <div class="tw-w-14 tw-h-14 tw-bg-blue-50 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-mr-4" style="background-color: #eff6ff; border-radius: 12px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                                <i class="fa fa-server tw-text-blue-600 tw-text-xl" style="color: #2563eb; font-size: 20px;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 16px; color: #111827;"><?= e($hosting->provider) ?></div>
                                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;"><?= e($hosting->status) ?> Plan</div>
                            </div>
                        </div>

                        <div style="margin-bottom: 15px; display: flex; justify-content: space-between;">
                            <span style="color: #4b5563; font-size: 14px;">Subscription starts</span>
                            <span style="color: #111827; font-weight: 600; font-size: 14px;"><?= _d($hosting->start_date) ?></span>
                        </div>
                        <div style="margin-bottom: 25px; display: flex; justify-content: space-between;">
                            <span style="color: #4b5563; font-size: 14px;">Subscription expires</span>
                            <span style="color: #dc2626; font-weight: 600; font-size: 14px;"><?= _d($hosting->expiration_date) ?></span>
                        </div>

                        <a href="<?= admin_url('domain_manager_hostinger/view_hosting/' . $hosting->id) ?>" class="btn btn-default btn-block" style="border-color: #d1d5db; color: #374151; font-weight: 500; padding: 10px;">
                            View Full Website Details
                        </a>
                    </div>
                </div>
                <?php } else { ?>
                <div class="panel_s" style="border: 1px dashed #d1d5db; border-radius: 8px; box-shadow: none; background-color: #f9fafb;">
                    <div class="panel-body text-center" style="padding: 40px 20px;">
                        <div style="width: 64px; height: 64px; background-color: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                            <i class="fa fa-plus" style="color: #9ca3af; font-size: 24px;"></i>
                        </div>
                        <h4 style="font-size: 16px; font-weight: 600; color: #111827; margin-bottom: 10px;">No Website Linked</h4>
                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 25px;">This domain is not currently linked to any website.</p>
                        <a href="<?= admin_url('domain_manager_hostinger/hosting_create?domain=' . $domain->id) ?>" class="btn btn-default" style="border-color: #d1d5db; color: #374151; font-weight: 500; padding: 8px 20px;">
                            Link Website
                        </a>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
    $(function() {
        $(".menu-item-domain_manager").addClass('active');
    });
</script>
</body>
</html>