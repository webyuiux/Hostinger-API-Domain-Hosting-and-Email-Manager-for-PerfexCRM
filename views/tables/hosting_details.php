<?php
defined('BASEPATH') or exit('No direct script access allowed');

$where = [];
if (isset($_GET['client']) && !empty($_GET['client'])) {
    $where[] = 'AND ' . db_prefix() . 'hosting_details.client_id=' . (int)$_GET['client'];
}

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'hosting_details.client_id',
];

$aColumns = [
    db_prefix() . 'hosting_details.website_name',
    db_prefix() . 'clients.company',
    db_prefix() . 'hosting_details.start_date',
    db_prefix() . 'hosting_details.expiration_date',
    db_prefix() . 'hosting_details.status',
    'id', // Actions
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'hosting_details';
$result       = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'hosting_details.id',
    db_prefix() . 'hosting_details.client_id',
    db_prefix() . 'hosting_details.domain_id',
    db_prefix() . 'hosting_details.hostinger_website_id',
    db_prefix() . 'hosting_details.datacenter',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // 1. Website Name with Icon
    $website_name = _html_escape($aRow['website_name']);
    $initial = strtoupper(substr($website_name, 0, 1));
    $is_synced = !empty($aRow['hostinger_website_id']);
    
    $av_class = $is_synced ? 'av-purple' : 'av-dark';
    $dc_text = $aRow['datacenter'] ?: 'N/A';
    
    $_data = '<div class="d-cell">
                <div class="h-avatar ' . $av_class . '">' . $initial . '</div>
                <div>
                    <div class="d-name">' . $website_name . '</div>
                    <div class="dc-badge">' . $dc_text . '</div>
                </div>
              </div>';
    $row[] = $_data;

    // 2. Client
    $row[] = '<a href="' . admin_url('clients/client/' . $aRow['client_id']) . '">' . _html_escape($aRow['company']) . '</a>';

    // 3. Start Date
    $start_date = $aRow['start_date'];
    $row[] = (!empty($start_date) && $start_date !== '0000-00-00') ? date('M d, Y', strtotime($start_date)) : 'N/A';

    // 4. Expiry Date
    $expiry = $aRow['expiration_date'];
    if (empty($expiry) || $expiry == '0000-00-00') {
        $row[] = 'N/A';
    } else {
        $row[] = date('M d, Y', strtotime($expiry));
    }

    // 5. Status
    $status = strtolower($aRow['status']);
    $badge_class = 'b-active';
    if ($status === 'expired') $badge_class = 'b-expires';
    elseif ($status === 'pending') $badge_class = 'b-pending';
    elseif ($is_synced) $badge_class = 'b-synced';

    $row[] = '<span class="h-badge ' . $badge_class . '">' . strtoupper($status) . '</span>';

    // 6. Actions
    $actions = '<div class="actions-cell">';
    $actions .= '<a href="' . admin_url('domain_manager_hostinger/view/' . $aRow['id']) . '" class="btn-h-whois" style="text-decoration:none;">MANAGE</a>';
    $actions .= '<div class="dropdown">
        <button class="btn-h-more dropdown-toggle" data-toggle="dropdown">⋮</button>
        <ul class="dropdown-menu dropdown-menu-right">
            <li><a href="' . admin_url('domain_manager_hostinger/edit_hosting/' . $aRow['id']) . '"><i class="fa fa-pencil-square-o"></i> Edit</a></li>
            <li><a href="' . admin_url('domain_manager_hostinger/delete_hosting/' . $aRow['id']) . '" class="text-danger _delete"><i class="fa fa-trash"></i> Delete</a></li>
        </ul>
    </div>';
    $actions .= '</div>';
    $row[] = $actions;

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
