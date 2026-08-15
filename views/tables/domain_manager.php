<?php
defined('BASEPATH') or exit('No direct script access allowed');

$where = [];

// Dynamic filtering based on tabs
$type = isset($_GET['type']) ? $_GET['type'] : null;
if ($type && in_array($type, ['internal', 'external'])) {
    $where[] = "AND " . db_prefix() . "domain_manager.domain_type = '" . $this->db->escape_str($type) . "'";
}

if (isset($_GET['client']) && !empty($_GET['client'])) {
    $where[] = 'AND ' . db_prefix() . 'domain_manager.client_id=' . (int)$_GET['client'];
}

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'domain_manager.client_id',
];

$aColumns = [
    db_prefix() . 'domain_manager.domain_name',
    db_prefix() . 'clients.company',
    db_prefix() . 'domain_manager.registrar',
    db_prefix() . 'domain_manager.expiry_date',
    db_prefix() . 'domain_manager.domain_type',
    db_prefix() . 'domain_manager.status',
    db_prefix() . 'domain_manager.created_at',
    'id', // Actions
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'domain_manager';
$result       = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'domain_manager.id',
    db_prefix() . 'domain_manager.client_id',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // 1. Domain Name with Avatar and IP
    $domain_name = _html_escape($aRow['domain_name']);
    $initial = strtoupper(substr($domain_name, 0, 1));
    
    // Choose avatar class based on domain type
    $av_class = ($aRow['domain_type'] === 'internal') ? 'av-purple' : 'av-dark';

    $ip_text = '';
    if ($aRow['domain_type'] === 'internal') {
        // Real DNS lookup
        $ip_text = gethostbyname($domain_name);
        if ($ip_text === $domain_name) $ip_text = 'Pending DNS...';
        $ip_html = '<div class="d-ip">' . $ip_text . '</div>';
    } else {
        $ip_text = 'External';
        $ip_html = '<div class="d-inactive">' . $ip_text . '</div>';
    }

    $_data = '<div class="d-cell">
                <div class="h-avatar ' . $av_class . '">' . $initial . '</div>
                <div>
                    <div class="d-name">' . $domain_name . '</div>
                    ' . $ip_html . '
                </div>
              </div>';
    $row[] = $_data;

    // 2. Client Name
    if ($aRow['client_id'] && !empty($aRow['company'])) {
        $client_html = '<a href="' . admin_url('clients/client/' . $aRow['client_id']) . '">' . _html_escape($aRow['company']) . '</a>';
    } else {
        $client_html = '<span class="text-muted">—</span>';
    }
    $row[] = $client_html;

    // 3. Registrar with Icon
    $registrar = _html_escape($aRow['registrar'] ?: 'Unknown');
    $reg_icon_class = 'ri-default';
    $reg_icon_text = strtoupper(substr($registrar, 0, 1));

    if (stripos($registrar, 'Namecheap') !== false) { $reg_icon_class = 'ri-nc'; $reg_icon_text = 'N'; }
    elseif (stripos($registrar, 'Google') !== false) { $reg_icon_class = 'ri-goog'; $reg_icon_text = 'G'; }
    elseif (stripos($registrar, 'GoDaddy') !== false) { $reg_icon_class = 'ri-gd'; $reg_icon_text = 'GD'; }
    elseif (stripos($registrar, 'Vercel') !== false) { $reg_icon_class = 'ri-vc'; $reg_icon_text = '▲'; }

    $_data = '<div class="reg-cell">
                <div class="reg-icon ' . $reg_icon_class . '">' . $reg_icon_text . '</div>
                <span class="reg-name">' . $registrar . '</span>
              </div>';
    $row[] = $_data;

    // 4. Expiry Date with Subtext
    $expiry = $aRow['expiry_date'];
    if (empty($expiry) || $expiry == '0000-00-00') {
        $_data = '<span class="tw-text-neutral-400">N/A</span>';
    } else {
        $expiry_formatted = date('M d, Y', strtotime($expiry));
        $days_left = floor((strtotime($expiry) - time()) / (60 * 60 * 24));
        
        $subtext = 'Auto-renews';
        $subtext_class = 'exp-sub';
        $date_class = 'exp-date';
        
        if ($days_left < 30 && $days_left > 0) {
            $subtext = 'EXPIRES IN ' . $days_left . ' DAYS';
            $subtext_class = 'exp-sub danger';
            $date_class = 'exp-date danger';
        } elseif ($days_left <= 0) {
            $subtext = 'EXPIRED';
            $subtext_class = 'exp-sub danger';
            $date_class = 'exp-date danger';
        }

        $_data = '<div>
                    <div class="' . $date_class . '">' . $expiry_formatted . '</div>
                    <div class="' . $subtext_class . '">' . $subtext . '</div>
                  </div>';
    }
    $row[] = $_data;

    // 5. Type Badge
    $type_badge = '';
    if ($aRow['domain_type'] === 'internal') {
        $type_badge = '<span class="label label-info">Internal</span>';
    } else {
        $type_badge = '<span class="label label-default">External</span>';
    }
    $row[] = $type_badge;

    // 6. Status Badge
    $status = strtolower($aRow['status']);
    $badge_class = 'b-active';
    $status_label = 'ACTIVE';

    if ($status === 'expired') {
        $badge_class = 'b-expires';
        $status_label = 'EXPIRED';
    } elseif ($status === 'pending') {
        $badge_class = 'b-pending';
        $status_label = 'PENDING';
    } elseif ($status === 'active' && isset($days_left) && $days_left < 15) {
         $badge_class = 'b-expires';
         $status_label = 'EXPIRES SOON';
    } elseif ($aRow['domain_type'] === 'internal') {
        $badge_class = 'b-synced';
        $status_label = 'SYNCED';
    }

    $_data = '<span class="h-badge ' . $badge_class . '">' . $status_label . '</span>';
    $row[] = $_data;

    // 7. Created Date
    $created_at = $aRow['created_at'];
    $row[] = (!empty($created_at) && $created_at !== '0000-00-00 00:00:00') ? date('M d, Y', strtotime($created_at)) : 'N/A';

    // 8. Actions
    $actions = '<div class="actions-cell">';
    $actions .= '<a href="' . admin_url('domain_manager_hostinger/view/' . $aRow['id']) . '" class="btn-h-whois" style="text-decoration: none;">VIEW</a>';
    $actions .= '<div class="dropdown">
        <button class="btn-h-more dropdown-toggle" data-toggle="dropdown">
            ⋮
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
            <li><a href="' . admin_url('domain_manager_hostinger/view/' . $aRow['id']) . '"><i class="fa fa-eye"></i> Manage</a></li>
            <li><a href="' . admin_url('domain_manager_hostinger/edit/' . $aRow['id']) . '"><i class="fa fa-pencil-square-o"></i> Edit</a></li>
            <li><a href="' . admin_url('domain_manager_hostinger/delete/' . $aRow['id']) . '" class="text-danger _delete"><i class="fa fa-trash"></i> Delete</a></li>
        </ul>
    </div>';
    $actions .= '</div>';
    $row[] = $actions;

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
