<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Hosting_details_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();

    }
    /**
     * Retrieve hosting_details data.
     *
     * @param int|string $id Optional ID of the hosting_details.
     * @return array|object Returns all records if no ID is provided, otherwise returns a single record.
     */
    public function get($id = ''){
        if($id == ''){
            // Exclude soft-deleted records (deleted = 1) so they never receive expiry emails
            if ($this->db->field_exists('deleted', db_prefix() . 'hosting_details')) {
                $this->db->where('deleted', 0);
            }
            return $this->db->get(db_prefix().'hosting_details')->result_array();
        }else{
            $this->db->where('id',$id);
            return $this->db->get(db_prefix().'hosting_details')->row();
        }
    }

    public function get_all_with_relations() {
        $this->db->select(db_prefix().'hosting_details.*, ' . db_prefix().'clients.company AS client_name, ' . db_prefix().'domain_manager.domain_name, ' . db_prefix().'contacts.email as client_email, ' . db_prefix().'contacts.phonenumber as client_phonenumber, IFNULL(CONCAT(s1.firstname, " ", s1.lastname), CONCAT(s2.firstname, " ", s2.lastname)) as assigned_staff_name');
        $this->db->from(db_prefix().'hosting_details');
        $this->db->join(db_prefix().'clients', db_prefix().'clients.userid = '.db_prefix().'hosting_details.client_id', 'left');
        $this->db->join(db_prefix().'contacts', db_prefix().'contacts.userid = '.db_prefix().'clients.userid AND '.db_prefix().'contacts.is_primary = 1', 'left');
        $this->db->join(db_prefix().'domain_manager', db_prefix().'domain_manager.id = '.db_prefix().'hosting_details.domain_id', 'left');
        $this->db->join(db_prefix().'staff s1', 's1.staffid = '.db_prefix().'hosting_details.assigned_staff_id', 'left');
        $this->db->join(db_prefix().'staff s2', 's2.staffid = '.db_prefix().'domain_manager.assigned_staff_id', 'left');
        return $this->db->get()->result_array();
    }
    /**
     * Retrieve domain_manager data.
     *
     * @param int|string $id Optional ID of the domain_manager.
     * @return array|object Returns all records if no ID is provided, otherwise returns a single record.
     */
    public function get_domain_id($domain_id = ''){
        $this->db->select(db_prefix().'hosting_details.*, ' . db_prefix().'clients.company AS client_name, ' . db_prefix().'projects.name AS project_name');
        $this->db->where('domain_id',$domain_id);
        $this->db->join(db_prefix().'clients', db_prefix().'clients.userid = '.db_prefix().'hosting_details.client_id', 'left');
        $this->db->join(db_prefix().'projects', db_prefix().'projects.id = '.db_prefix().'hosting_details.project_id', 'left');
        return $this->db->get(db_prefix().'hosting_details')->row();
    }
     /**
     * Add a new hosting_details record.
     *
     * @param array $data Array of hosting_details data.
     * @return int The ID of the newly inserted hosting_details.
     */
    public function add($data){
        $this->db->insert(db_prefix() . 'hosting_details', $data);
        return $this->db->insert_id();
    }
    /**
     * Update an existing hosting_details record.
     *
     * @param array $data Array of hosting_details data, including ID for the record to be updated.
     * @return bool|int Returns the number of affected rows or false if no ID is provided.
     */
   
    public function update($id,$data)
    {
        if ($id) {
            unset($data['id']);
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'hosting_details', $data);

            return $this->db->affected_rows();
        }

        return false;
    }
     /**
     * Retrieve all hosting_detailss created by the current staff member.
     *
     * @return array Returns an array of hosting_detailss created by the current staff member.
     */
    public function all(){
        $CI = &get_instance();
        $CI->db->from(db_prefix() . 'hosting_details');
        $CI->db->where('created_by', get_staff_user_id());
        $query = $CI->db->get();
        return $query->result_array();

    }
    /**
     * Delete a hosting_details record by ID.
     *
     * @param int $id ID of the hosting_details to be deleted.
     * @return bool Returns true if the record was deleted successfully, otherwise false.
     */
    public function delete($id)
    {
        if (isset($id) && is_numeric($id)) {
            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'hosting_details');
            return ($this->db->affected_rows() > 0);
        }
        return false;
    }

     /**
     * Get Projects
     * @param  mixed project (Optional)
     * @return mixed     object or array
     */
    public function get_projects()
    {
        return $this->db->get(db_prefix() . 'projects')->result_array();
    }

     /**
     * Get Projects
     * @param  mixed project (Optional)
     * @return mixed     object or array
     */
    public function get_clients()
    {
        return $this->db->get(db_prefix() . 'clients')->result_array();
    }

    /**
     * Get websites expiring within a specific number of days (for bell-icon notification)
     *
     * @param  int $days  Look-ahead window in days (default 5)
     * @return array
     */
    public function get_websites_expiring_soon($days = 5)
    {
        $this->db->select(
            db_prefix() . 'hosting_details.*, ' .
            db_prefix() . 'clients.company AS client_name'
        );
        $this->db->from(db_prefix() . 'hosting_details');
        $this->db->join(
            db_prefix() . 'clients',
            db_prefix() . 'clients.userid = ' . db_prefix() . 'hosting_details.client_id',
            'left'
        );
        $this->db->where('status', 'active');
        $this->db->where('expiration_date !=', '0000-00-00');
        $this->db->where('expiration_date IS NOT NULL', null, false);
        $this->db->where('expiration_date <=', date('Y-m-d', strtotime('+' . (int)$days . ' days')));
        $this->db->order_by('expiration_date', 'ASC');
        return $this->db->get()->result_array();
    }
}