<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <?= form_hidden('domain_id', $domain->id) ?>
    <div class="content">
        <div class="tw-mb-8">
            <!-- Breadcrumbs -->
            <div class="h-breadcrumbs">
                <a href="<?= admin_url() ?>"><i class="fa fa-home"></i> Dashboard</a>
                <i class="fa fa-chevron-right"></i>
                <a href="<?= admin_url('domain_manager_hostinger') ?>">Domain Portfolio</a>
                <i class="fa fa-chevron-right"></i>
                <span><?= e($domain->domain_name); ?></span>
            </div>

            <div class="tw-flex tw-justify-between tw-items-center">
                <h3 class="tw-font-extrabold tw-text-3xl tw-text-neutral-800 tw-m-0">
                    <?= e($domain->domain_name); ?>
                </h3>
                <div class="tw-flex tw-gap-3">
                    <a href="<?= admin_url('domain_manager_hostinger/edit/' . $domain->id) ?>" class="btn-h-primary">
                        <i class="fa fa-pencil-square-o tw-mr-2"></i> Edit Domain
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Domain Overview -->
            <div class="col-md-7">
                <div class="h-panel h-hover-lift">
                    <div class="h-panel-header">
                        <h4 class="h-panel-title"><i class="fa fa-globe text-primary tw-mr-2"></i> Domain Overview</h4>
                        <?php
                        $status = strtolower($domain->status);
                        if ($status === 'active') {
                            echo '<span class="h-status-active"><i class="fa fa-check-circle"></i> Active</span>';
                        } else {
                            echo '<span class="h-status-expired"><i class="fa fa-times-circle"></i> ' . strtoupper($status) . '</span>';
                        }
                        ?>
                    </div>
                    <div class="panel-body tw-p-8">
                        <div class="tw-grid tw-grid-cols-2 tw-gap-y-8 tw-gap-x-12">
                            <div>
                                <div class="detail-label">Registrar</div>
                                <div class="detail-value"><?= e($domain->registrar ?: 'N/A') ?></div>
                            </div>
                            <div>
                                <div class="detail-label">Customer</div>
                                <div class="detail-value">
                                    <a href="<?= admin_url('clients/client/' . $domain->client_id) ?>" class="tw-text-purple-600 hover:tw-underline">
                                        <?= e($domain->client_name) ?>
                                    </a>
                                </div>
                            </div>
                            <div>
                                <div class="detail-label">Purchase Date</div>
                                <div class="detail-value"><?= _d($domain->purchase_date) ?></div>
                            </div>
                            <div>
                                <div class="detail-label">Expiry Date</div>
                                <div class="detail-value tw-text-orange-600"><?= _d($domain->expiry_date) ?></div>
                            </div>
                            <div>
                                <div class="detail-label">Provider</div>
                                <div class="detail-value"><?= e($domain->provider_name) ?></div>
                            </div>
                            <div>
                                <div class="detail-label">Internal ID</div>
                                <div class="detail-value">#<?= $domain->id ?></div>
                            </div>
                        </div>

                        <?php if (!empty($domain->description)) { ?>
                        <div class="tw-mt-10 tw-pt-10 tw-border-t tw-border-neutral-100">
                            <div class="detail-label">Notes / Description</div>
                            <div class="tw-text-neutral-600 tw-leading-relaxed">
                                <?= $domain->description ?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Hosting Side Card -->
            <div class="col-md-5">
                <?php if (isset($hosting) && !empty($hosting)) { ?>
                <div class="h-panel h-hover-lift" style="border-top: 4px solid var(--h-primary);">
                    <div class="h-panel-header">
                        <h4 class="h-panel-title"><i class="fa fa-server text-primary tw-mr-2"></i> Website Hosting</h4>
                    </div>
                    <div class="panel-body tw-p-8">
                        <div class="tw-flex tw-items-center tw-mb-8">
                            <div class="tw-w-14 tw-h-14 tw-bg-purple-50 tw-rounded-2xl tw-flex tw-items-center tw-justify-center tw-mr-4">
                                <i class="fa fa-bolt tw-text-purple-600 tw-text-xl"></i>
                            </div>
                            <div>
                                <div class="tw-font-bold tw-text-lg"><?= e($hosting->provider) ?></div>
                                <div class="tw-text-xs tw-text-neutral-500 tw-uppercase tw-font-bold tw-tracking-wider"><?= e($hosting->status) ?> Plan</div>
                            </div>
                        </div>

                        <div class="tw-space-y-6">
                            <div class="tw-flex tw-justify-between tw-items-center">
                                <span class="tw-text-sm tw-text-neutral-500">Subscription starts</span>
                                <span class="tw-font-bold"><?= _d($hosting->start_date) ?></span>
                            </div>
                            <div class="tw-flex tw-justify-between tw-items-center">
                                <span class="tw-text-sm tw-text-neutral-500">Subscription expires</span>
                                <span class="tw-font-bold text-danger"><?= _d($hosting->expiration_date) ?></span>
                            </div>
                        </div>

                        <div class="tw-mt-8">
                            <a href="<?= admin_url('domain_manager_hostinger/hosting_view/' . $hosting->id) ?>" class="btn btn-default btn-block btn-h-action-white" style="padding: 12px !important;">
                                View Full Hosting Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php } else { ?>
                <div class="tw-bg-white tw-rounded-2xl tw-p-10 tw-border-2 tw-border-dashed tw-border-neutral-200 tw-text-center">
                    <div class="tw-w-16 tw-h-16 tw-bg-neutral-50 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                        <i class="fa fa-plus tw-text-neutral-300 tw-text-2xl"></i>
                    </div>
                    <h4 class="tw-font-bold tw-text-neutral-800">No Hosting Linked</h4>
                    <p class="tw-text-neutral-500 tw-text-sm tw-mb-6">This domain is not currently linked to any web hosting plan.</p>
                    <a href="<?= admin_url('domain_manager_hostinger/hosting_create?domain=' . $domain->id) ?>" class="btn-h-action-white">
                        Link Hosting
                    </a>
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