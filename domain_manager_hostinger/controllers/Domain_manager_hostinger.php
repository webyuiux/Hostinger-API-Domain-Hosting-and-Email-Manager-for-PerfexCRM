<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Domain_manager_hostinger extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'domain_manager_model',
            'staff_model',
            'hosting_details_model',
            'settings_model',
            'hostinger_api_model',
        ]);
    }

    // -----------------------------------------------------------------------
    // DOMAINS
    // -----------------------------------------------------------------------

    public function index()
    {
        if (!has_permission('domain_manager', '', 'view')) {
            access_denied('domain_manager');
        }
        $data['title'] = _l('domain_manager_list');
        
        // Fetch stats for the new UI (excluding soft-deleted records)
        $data['total_assets'] = total_rows(db_prefix() . 'domain_manager', ['deleted' => 0]);
        $data['external_domains'] = total_rows(db_prefix() . 'domain_manager', ['domain_type' => 'external', 'deleted' => 0]);
        $data['internal_domains'] = total_rows(db_prefix() . 'domain_manager', ['domain_type' => 'internal', 'deleted' => 0]);
        $data['externalCount'] = $data['external_domains'];
        $data['internalCount'] = $data['internal_domains'];
        $data['expiring_soon'] = total_rows(db_prefix() . 'domain_manager', 'expiry_date <= "' . date('Y-m-d', strtotime('+30 days')) . '" AND expiry_date >= "' . date('Y-m-d') . '" AND status = "active" AND deleted = 0');

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('domain_manager_hostinger', 'tables/domain_manager'));
        }
        $this->load->view('index', $data);
    }

    public function create()
    {
        if (!has_permission('domain_manager', '', 'create')) {
            access_denied('domain_manager');
        }
        $data['title']   = _l('domain_manager_add');
        $data['clients'] = $this->domain_manager_model->get_clients();
        $this->load->view('create', $data);
    }

    public function save_domain_manager()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'create')) {
            access_denied('domain_manager');
        }
        $data = $this->input->post();
        if (empty($data['name'])) {
            set_alert('warning', 'The domain name field is required.');
            redirect(admin_url('domain_manager_hostinger/create'));
        }
        $domain_name = strtolower(trim($data['name']));

        // Check for duplicate domain name
        $this->db->where('domain_name', $domain_name);
        $existing = $this->db->get(db_prefix() . 'domain_manager')->row();
        if ($existing) {
            set_alert('warning', 'Domain "' . $domain_name . '" already exists in the table.');
            redirect(admin_url('domain_manager_hostinger/create'));
        }

        $insert_data = [
            'domain_name'  => $domain_name,
            'registrar'    => $data['domain_manager_registrar'] ?? null,
            'domain_type'  => in_array($data['domain_type'] ?? '', ['internal', 'external']) ? $data['domain_type'] : 'external',
            'status'       => in_array($data['status'] ?? '', ['active', 'expired', 'pending', 'suspended']) ? $data['status'] : 'active',
            'client_id'    => !empty($data['client_id']) ? (int)$data['client_id'] : null,
            'purchase_date'=> !empty($data['domain_manager_purchase_date']) ? date('Y-m-d', strtotime($data['domain_manager_purchase_date'])) : null,
            'expiry_date'  => !empty($data['domain_manager_expiry_date']) ? date('Y-m-d', strtotime($data['domain_manager_expiry_date'])) : null,
            'description'  => $data['description'] ?? null,
            'created_by'   => get_staff_user_id(),
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
        $id = $this->domain_manager_model->add($insert_data);
        set_alert($id ? 'success' : 'danger', $id ? 'Domain added successfully.' : 'Failed to add domain.');
        redirect(admin_url('domain_manager_hostinger'));
    }

    public function view($id)
    {
        if (!has_permission('domain_manager', '', 'view')) {
            access_denied('domain_manager');
        }
        $data['domain']  = $this->domain_manager_model->get($id);
        $data['hosting'] = $this->hosting_details_model->get_domain_id($id);
        $this->load->view('view', $data);
    }

    public function edit($id)
    {
        if (!has_permission('domain_manager', '', 'edit')) {
            access_denied('domain_manager');
        }
        $data['domain']  = $this->domain_manager_model->get($id);
        $data['clients'] = $this->domain_manager_model->get_clients();
        $this->load->view('edit', $data);
    }

    public function update_domain_manager()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'edit')) {
            access_denied('domain_manager');
        }
        $data = $this->input->post();
        if (empty($data['id']) || empty($data['name'])) {
            set_alert('warning', 'Domain name is required.');
            redirect(admin_url('domain_manager_hostinger'));
        }
        $update_data = [
            'domain_name'  => strtolower(trim($data['name'])),
            'registrar'    => $data['domain_manager_registrar'] ?? null,
            'domain_type'  => in_array($data['domain_type'] ?? '', ['internal', 'external']) ? $data['domain_type'] : 'external',
            'status'       => in_array($data['status'] ?? '', ['active', 'expired', 'pending', 'suspended']) ? $data['status'] : 'active',
            'client_id'    => !empty($data['client_id']) ? (int)$data['client_id'] : null,
            'purchase_date'=> !empty($data['domain_manager_purchase_date']) ? date('Y-m-d', strtotime($data['domain_manager_purchase_date'])) : null,
            'expiry_date'  => !empty($data['domain_manager_expiry_date']) ? date('Y-m-d', strtotime($data['domain_manager_expiry_date'])) : null,
            'description'  => $data['description'] ?? null,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
        $r = $this->domain_manager_model->update($data['id'], $update_data);
        set_alert($r ? 'success' : 'danger', $r ? 'Domain updated successfully.' : 'Failed to update domain.');
        redirect(admin_url('domain_manager_hostinger'));
    }

    public function delete($id)
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'delete')) {
            access_denied('domain_manager');
        }
        if (!is_numeric($id)) {
            set_alert('danger', 'Invalid domain ID.');
            redirect(admin_url('domain_manager_hostinger'));
        }
        $r = $this->domain_manager_model->delete($id);
        set_alert($r ? 'success' : 'danger', $r ? 'Domain deleted.' : 'Failed to delete domain.');
        redirect(admin_url('domain_manager_hostinger'));
    }

    // -----------------------------------------------------------------------
    // SETTINGS
    // -----------------------------------------------------------------------

    public function setting()
    {
        if (!is_admin()) {
            access_denied('domain_manager');
        }
        if ($this->input->post()) {
            $post_data = $this->input->post();
            // Only update token if a new value was provided
            if (isset($post_data['settings']['domain_manager_hostinger_api_token'])
                && trim($post_data['settings']['domain_manager_hostinger_api_token']) === '') {
                unset($post_data['settings']['domain_manager_hostinger_api_token']);
            }
            $success = $this->settings_model->update($post_data);
            set_alert($success ? 'success' : 'danger', $success ? 'Settings saved.' : 'Failed to save settings.');
            redirect(admin_url('domain_manager_hostinger/setting'));
        }
        $data['title']               = _l('domain_manager_setting');
        $data['hostinger_token']     = get_option('domain_manager_hostinger_api_token');
        $data['hostinger_token_set'] = !empty($data['hostinger_token']);
        $this->load->view('manage', $data);
    }

    // -----------------------------------------------------------------------
    // HOSTINGER API ACTIONS
    // -----------------------------------------------------------------------

    public function hostinger_api_test()
    {
        if (!is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        $result = $this->hostinger_api_model->test_connection();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function debug_api($endpoint = 'domains')
    {
        if (!is_admin()) { show_404(); }
        header('Content-Type: application/json');
        $raw = ($endpoint === 'websites')
            ? $this->hostinger_api_model->get_websites()
            : $this->hostinger_api_model->get_domains();
        echo json_encode(['debug' => true, 'endpoint' => $endpoint, 'result' => $raw]);
        exit;
    }

    public function sync_hostinger_domains()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'create')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        $result = $this->hostinger_api_model->sync_domains();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function sync_hostinger_websites()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'create')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        $result = $this->hostinger_api_model->sync_websites();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function domain_manager_table()
    {
        if (!has_permission('domain_manager', '', 'view')) {
            access_denied('domain_manager');
        }
        $this->app->get_table_data(module_views_path('domain_manager_hostinger', 'tables/domain_manager'));
    }

    // -----------------------------------------------------------------------
    // WEBSITES
    // -----------------------------------------------------------------------

    public function hosting_list()
    {
        if (!has_permission('domain_manager', '', 'hosting_view')) {
            access_denied('domain_manager');
        }
        $data['title'] = _l('domain_manager_websites_list');
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('domain_manager_hostinger', 'tables/hosting_details'));
            return;
        }
        $this->load->view('hosting/index', $data);
    }

    public function hosting_create()
    {
        if (!has_permission('domain_manager', '', 'hosting_create')) {
            access_denied('domain_manager');
        }
        $data['title']   = _l('domain_manager_hosting_add');
        $data['clients'] = $this->domain_manager_model->get_clients();
        $data['domains'] = $this->domain_manager_model->get();
        $this->load->view('hosting/create', $data);
    }

    public function save_hosting()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_create')) {
            access_denied('domain_manager');
        }
        $data = $this->input->post();
        if (empty($data['website_name'])) {
            set_alert('warning', 'Website name is required.');
            redirect(admin_url('domain_manager_hostinger/hosting_create'));
        }
        $website_name = trim($data['website_name']);

        // Check for duplicate website name
        $this->db->where('website_name', $website_name);
        $existing = $this->db->get(db_prefix() . 'hosting_details')->row();
        if ($existing) {
            set_alert('warning', 'Website "' . $website_name . '" already exists in the table.');
            redirect(admin_url('domain_manager_hostinger/hosting_create'));
        }

        $insert_data = [
            'website_name'    => $website_name,
            'domain_id'       => !empty($data['domain_id']) ? (int)$data['domain_id'] : null,
            'provider'        => $data['domain_manager_provider'] ?? null,
            'client_id'       => !empty($data['client_id']) ? (int)$data['client_id'] : null,
            'status'          => in_array($data['status'] ?? '', ['active', 'expired', 'pending', 'suspended']) ? $data['status'] : 'active',
            'start_date'      => !empty($data['domain_manager_start_date']) ? date('Y-m-d', strtotime($data['domain_manager_start_date'])) : null,
            'expiration_date' => !empty($data['domain_manager_expiry_date']) ? date('Y-m-d', strtotime($data['domain_manager_expiry_date'])) : null,
            'description'     => $data['description'] ?? null,
            'created_by'      => get_staff_user_id(),
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ];
        $id = $this->hosting_details_model->add($insert_data);
        set_alert($id ? 'success' : 'danger', $id ? 'Website added successfully.' : 'Failed to add website.');
        redirect(admin_url('domain_manager_hostinger/hosting_list'));
    }

    public function edit_hosting($id)
    {
        if (!has_permission('domain_manager', '', 'hosting_edit')) {
            access_denied('domain_manager');
        }
        $data['hosting'] = $this->hosting_details_model->get($id);
        $data['clients'] = $this->domain_manager_model->get_clients();
        $data['domains'] = $this->domain_manager_model->get();
        $this->load->view('hosting/edit', $data);
    }

    public function update_hosting()
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_edit')) {
            access_denied('domain_manager');
        }
        $data = $this->input->post();
        if (empty($data['id']) || empty($data['website_name'])) {
            set_alert('warning', 'Website name is required.');
            redirect(admin_url('domain_manager_hostinger/hosting_list'));
        }
        $update_data = [
            'website_name'    => trim($data['website_name']),
            'domain_id'       => !empty($data['domain_id']) ? (int)$data['domain_id'] : null,
            'provider'        => $data['domain_manager_provider'] ?? null,
            'client_id'       => !empty($data['client_id']) ? (int)$data['client_id'] : null,
            'status'          => in_array($data['status'] ?? '', ['active', 'expired', 'pending', 'suspended']) ? $data['status'] : 'active',
            'start_date'      => !empty($data['domain_manager_start_date']) ? date('Y-m-d', strtotime($data['domain_manager_start_date'])) : null,
            'expiration_date' => !empty($data['domain_manager_expiry_date']) ? date('Y-m-d', strtotime($data['domain_manager_expiry_date'])) : null,
            'description'     => $data['description'] ?? null,
            'updated_at'      => date('Y-m-d H:i:s'),
        ];
        $r = $this->hosting_details_model->update($data['id'], $update_data);
        set_alert($r ? 'success' : 'danger', $r ? 'Website updated successfully.' : 'Failed to update website.');
        redirect(admin_url('domain_manager_hostinger/hosting_list'));
    }

    public function delete_hosting($id)
    {
        if (!has_permission('domain_manager', get_staff_user_id(), 'hosting_delete')) {
            access_denied('domain_manager');
        }
        if (!is_numeric($id)) {
            set_alert('danger', 'Invalid website ID.');
            redirect(admin_url('domain_manager_hostinger/hosting_list'));
        }
        $r = $this->hosting_details_model->delete($id);
        set_alert($r ? 'success' : 'danger', $r ? 'Website deleted.' : 'Failed to delete website.');
        redirect(admin_url('domain_manager_hostinger/hosting_list'));
    }
}
