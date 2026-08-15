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
            return  $this->db->get(db_prefix().'hosting_details')->result_array();
        }else{
            $this->db->where('id',$id);
            return $this->db->get(db_prefix().'hosting_details')->row();
        }
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
}