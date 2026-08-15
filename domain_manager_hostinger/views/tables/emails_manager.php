<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'mailbox_name',
    'domain',
    'available_count',
    'client_id',
    'start_date',
    'expiry_date',
    'status',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'emails_manager';

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'emails_manager.client_id',
];

$where = [];
if (isset($_GET['client']) && !empty($_GET['client'])) {
    $where[] = 'AND ' . db_prefix() . 'emails_manager.client_id=' . (int)$_GET['client'];
}
if (isset($_GET['project']) && !empty($_GET['project'])) {
    $where[] = 'AND ' . db_prefix() . 'emails_manager.project_id=' . (int)$_GET['project'];
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    $sTable . '.id',
    'company',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // Mailbox name with options
    $name = '<a href="' . admin_url('domain_manager_hostinger/edit_email/' . $aRow['id']) . '" class="tw-font-medium">' . e($aRow['mailbox_name']) . '</a>';
    $name .= '<div class="row-options">';
    $name .= '<a href="' . admin_url('domain_manager_hostinger/edit_email/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    
    if (has_permission('domain_manager', '', 'hosting_delete')) {
        $name .= ' | <a href="' . admin_url('domain_manager_hostinger/delete_email/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    }
    $name .= '</div>';
    $row[] = $name;

    // Domain
    $row[] = e($aRow['domain']);

    // Available count
    $row[] = $aRow['available_count'];

    // Client
    if ($aRow['client_id']) {
        $row[] = '<a href="' . admin_url('clients/client/' . $aRow['client_id']) . '">' . e($aRow['company']) . '</a>';
    } else {
        $row[] = '<span class="text-muted">—</span>';
    }

    // Start Date
    $row[] = ($aRow['start_date'] && $aRow['start_date'] != '0000-00-00') ? '<span class="text-success tw-font-medium">' . _d($aRow['start_date']) . '</span>' : '<span class="text-muted">—</span>';

    // Expiry Date
    $row[] = ($aRow['expiry_date'] && $aRow['expiry_date'] != '0000-00-00') ? '<span class="text-danger tw-font-semibold">' . _d($aRow['expiry_date']) . '</span>' : '<span class="text-muted">—</span>';

    // Status
    $status_label = '';
    $status_classes = [
        'active'    => 'success',
        'expired'   => 'danger',
        'suspended' => 'warning',
        'pending'   => 'info',
    ];
    $class = isset($status_classes[$aRow['status']]) ? $status_classes[$aRow['status']] : 'default';
    $row[] = '<span class="label label-' . $class . '" style="font-size:11px;">' . strtoupper(_l('domain_manager_' . $aRow['status'])) . '</span>';

    // Options
    $options = icon_btn('domain_manager_hostinger/edit_email/' . $aRow['id'], 'pencil-square-o', 'btn-default');
    if (has_permission('domain_manager', '', 'hosting_delete')) {
        $options .= icon_btn('domain_manager_hostinger/delete_email/' . $aRow['id'], 'remove', 'btn-danger _delete');
    }
    $row[] = $options;

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
