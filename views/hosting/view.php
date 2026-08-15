<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <?= form_hidden('hosting_id', $hosting->id) ?>
    <div class="content">
        <div class="tw-mb-8">
            <!-- Breadcrumbs -->
            <div class="h-breadcrumbs" style="font-size: 13px; color: #6b7280; margin-bottom: 15px;">
                <a href="<?= admin_url() ?>" style="color: #3b82f6; text-decoration: none;">Dashboard</a>
                <span class="tw-mx-2">></span>
                <a href="<?= admin_url('domain_manager_hostinger/hosting_list') ?>" style="color: #3b82f6; text-decoration: none;">Websites</a>
                <span class="tw-mx-2">></span>
                <span style="color: #374151;"><?= e($hosting->website_name); ?></span>
            </div>

            <div class="tw-flex tw-justify-between tw-items-start">
                <div>
                    <h3 style="font-size: 28px; font-weight: 500; color: #111827; margin: 0; line-height: 1.2;">
                        <?= e($hosting->website_name); ?>
                    </h3>
                    <p style="font-size: 16px; color: #6b7280; margin-top: 5px; margin-bottom: 0;">WHOIS Information</p>
                </div>
                <div class="tw-flex tw-gap-3">
                    <a href="<?= admin_url('domain_manager_hostinger/edit_hosting/' . $hosting->id) ?>" class="btn btn-primary" style="background-color: #6366f1; border-color: #6366f1; padding: 8px 20px; font-weight: 500; border-radius: 6px;">
                        Edit Website
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Website Overview (WHOIS Style) -->
            <div class="col-md-8">
                <div class="panel_s" style="border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: none;">
                    <div class="panel-body" style="padding: 30px;">
                        
                        <!-- Website Information -->
                        <h4 style="font-size: 18px; font-weight: 600; color: #111827; margin-top: 0; margin-bottom: 25px;">Website Information</h4>
                        
                        <div style="display: flex; margin-bottom: 15px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Created</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;"><?= !empty($hosting->created_at) ? _d(date('Y-m-d', strtotime($hosting->created_at))) : '—' ?></div>
                        </div>
                        
                        <div style="display: flex; margin-bottom: 15px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Updated</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;"><?= !empty($hosting->updated_at) ? _d(date('Y-m-d', strtotime($hosting->updated_at))) : '—' ?></div>
                        </div>

                        <div style="display: flex; margin-bottom: 15px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Expires</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;"><?= !empty($hosting->expiration_date) ? _d($hosting->expiration_date) : '—' ?></div>
                        </div>

                        <div style="display: flex; margin-bottom: 35px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Website Status</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;">
                                <?php
                                $status = strtolower($hosting->status);
                                if ($status === 'active') {
                                    echo 'Active';
                                } else {
                                    echo ucfirst($status);
                                }
                                ?>
                            </div>
                        </div>

                        <hr style="border-top: 1px solid #e5e7eb; margin-bottom: 30px;">

                        <!-- Provider Information -->
                        <h4 style="font-size: 18px; font-weight: 600; color: #111827; margin-top: 0; margin-bottom: 25px;">Provider Information</h4>
                        
                        <div style="display: flex; margin-bottom: 15px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Hosting Provider</div>
                            <div style="flex: 1; color: #111827; font-size: 15px;"><?= e($hosting->provider ?: 'Hostinger') ?></div>
                        </div>
                        
                        <div style="display: flex; margin-bottom: 35px;">
                            <div style="width: 250px; color: #4b5563; font-size: 15px;">Datacenter</div>
                            <div style="flex: 1; font-size: 15px; color: #111827;">
                                <?= !empty($hosting->datacenter) ? e($hosting->datacenter) : '—' ?>
                            </div>
                        </div>

                        <?php if (!empty($hosting->description)) { ?>
                        <hr style="border-top: 1px solid #e5e7eb; margin-top: 30px; margin-bottom: 30px;">
                        <h4 style="font-size: 18px; font-weight: 600; color: #111827; margin-top: 0; margin-bottom: 25px;">Internal Notes</h4>
                        <div style="color: #4b5563; font-size: 15px; line-height: 1.6;">
                            <?= $hosting->description ?>
                        </div>
                        <?php } ?>

                        <?php if (isset($domain) && !empty($domain)) { ?>
                        <hr style="border-top: 1px solid #e5e7eb; margin-top: 30px; margin-bottom: 30px;">
                        <h4 style="font-size: 18px; font-weight: 600; color: #111827; margin-top: 0; margin-bottom: 25px;">Raw WHOIS Data <small class="text-muted">(<?= e($domain->domain_name) ?>)</small></h4>
                        <div style="background-color: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 13px; white-space: pre-wrap; word-wrap: break-word; overflow-x: auto; max-height: 400px; overflow-y: auto; border: 1px solid #333;">
                            <?= e($whois_raw) ?>
                        </div>
                        <?php } ?>

                    </div>
                </div>
            </div>

            <!-- Domain Side Card -->
            <div class="col-md-4">
                <?php if (isset($domain) && !empty($domain)) { ?>
                <div class="panel_s" style="border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: none;">
                    <div class="panel-body" style="padding: 30px;">
                        <h4 style="font-size: 18px; font-weight: 600; color: #111827; margin-top: 0; margin-bottom: 25px;">Linked Domain</h4>
                        
                        <div class="tw-flex tw-items-center tw-mb-8">
                            <div class="tw-w-14 tw-h-14 tw-bg-purple-50 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-mr-4" style="background-color: #f5f3ff; border-radius: 12px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                                <i class="fa fa-globe tw-text-purple-600 tw-text-xl" style="color: #7c3aed; font-size: 20px;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 16px; color: #111827;"><?= e($domain->domain_name) ?></div>
                                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;"><?= e($domain->status) ?> Domain</div>
                            </div>
                        </div>

                        <div style="margin-bottom: 15px; display: flex; justify-content: space-between;">
                            <span style="color: #4b5563; font-size: 14px;">Domain created</span>
                            <span style="color: #111827; font-weight: 600; font-size: 14px;">
                                <?php
                                $created_val = !empty($domain_whois['purchase_date']) ? $domain_whois['purchase_date'] : ($domain->purchase_date ?? '');
                                echo !empty($created_val) ? _d($created_val) : '—';
                                ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 15px; display: flex; justify-content: space-between;">
                            <span style="color: #4b5563; font-size: 14px;">Domain expires</span>
                            <span style="color: #dc2626; font-weight: 600; font-size: 14px;">
                                <?php
                                $expiry_val = !empty($domain_whois['expiry_date']) ? $domain_whois['expiry_date'] : ($domain->expiry_date ?? '');
                                echo !empty($expiry_val) ? _d($expiry_val) : '—';
                                ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 25px; display: flex; justify-content: space-between;">
                            <span style="color: #4b5563; font-size: 14px;">Active Mailboxes</span>
                            <span style="color: #111827; font-weight: 600; font-size: 14px;">
                                <?php
                                $CI = &get_instance();
                                $mailbox_count = $CI->db->where('domain', $domain->domain_name)->where('deleted', 0)->count_all_results(db_prefix() . 'emails_manager');
                                echo $mailbox_count;
                                ?>
                            </span>
                        </div>

                        <a href="<?= admin_url('domain_manager_hostinger/view/' . $domain->id) ?>" class="btn btn-default btn-block" style="border-color: #d1d5db; color: #374151; font-weight: 500; padding: 10px;">
                            View Full Domain Details
                        </a>

                        <?php
                        $mailboxes = $CI->db->where('domain', $domain->domain_name)->where('deleted', 0)->get(db_prefix() . 'emails_manager')->result_array();
                        if (!empty($mailboxes)) {
                        ?>
                            <div style="margin-top: 25px; border-top: 1px dashed #e5e7eb; padding-top: 20px;">
                                <h5 style="font-weight: 600; color: #111827; margin-bottom: 15px; font-size: 14px;">Domain Mailboxes</h5>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach ($mailboxes as $mb) { 
                                        $mb_status_class = ($mb['status'] == 'active') ? 'success' : (($mb['status'] == 'expired' || $mb['status'] == 'suspended') ? 'danger' : 'warning');
                                    ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                                            <a href="mailto:<?= e($mb['mailbox_name']) ?>" style="color: #3b82f6; text-decoration: none; font-weight: 500;"><?= e($mb['mailbox_name']) ?></a>
                                            <span class="label label-<?= $mb_status_class ?>" style="font-size: 10px; padding: 2px 6px;"><?= ucfirst(e($mb['status'])) ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } else { ?>
                <div class="panel_s" style="border: 1px dashed #d1d5db; border-radius: 8px; box-shadow: none; background-color: #f9fafb;">
                    <div class="panel-body text-center" style="padding: 40px 20px;">
                        <div style="width: 64px; height: 64px; background-color: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                            <i class="fa fa-plus" style="color: #9ca3af; font-size: 24px;"></i>
                        </div>
                        <h4 style="font-size: 16px; font-weight: 600; color: #111827; margin-bottom: 10px;">No Domain Linked</h4>
                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 25px;">This website is not currently linked to any domain in your portfolio.</p>
                        <a href="<?= admin_url('domain_manager_hostinger/edit_hosting/' . $hosting->id) ?>" class="btn btn-default" style="border-color: #d1d5db; color: #374151; font-weight: 500; padding: 8px 20px;">
                            Link Domain
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
        $(".sub-menu-item-hosting_view_list").addClass('active');
    });
</script>
</body>
</html>
