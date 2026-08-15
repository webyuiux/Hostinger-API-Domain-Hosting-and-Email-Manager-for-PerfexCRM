<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Email_manager_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get email/mailbox
     * @param  mixed $id id
     * @return mixed      object array
     */
    public function get($id = '', $where = [])
    {
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'emails_manager')->row();
        }
        $this->db->order_by('created_at', 'desc');
        return $this->db->get(db_prefix() . 'emails_manager')->result_array();
    }
    /**
     * Add new email
     * @param array $data
     * @return mixed
     */
    public function add($data)
    {
        $this->db->insert(db_prefix() . 'emails_manager', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Email Added [ID: ' . $insert_id . ']');
            return $insert_id;
        }
        return false;
    }

    /**
     * Update email
     * @param  array $data
     * @param  mixed $id
     * @return boolean
     */
    public function update($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'emails_manager', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Email Updated [ID: ' . $id . ']');
            return true;
        }
        return false;
    }

    /**
     * Delete email
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'emails_manager');
        if ($this->db->affected_rows() > 0) {
            log_activity('Email Deleted [ID: ' . $id . ']');
            return true;
        }
        return false;
    }

    /**
     * Get emails with related domain and client information
     * @return array
     */
    public function get_emails_with_relations()
    {
        $this->db->select(
            db_prefix() . 'emails_manager.*, ' .
            db_prefix() . 'domain_manager.id as domain_id, ' .
            db_prefix() . 'domain_manager.domain_name as linked_domain_name, ' .
            db_prefix() . 'clients.company as client_name'
        );
        $this->db->from(db_prefix() . 'emails_manager');
        $this->db->join(db_prefix() . 'domain_manager', db_prefix() . 'domain_manager.domain_name = ' . db_prefix() . 'emails_manager.domain', 'left');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'emails_manager.client_id', 'left');
        $this->db->where(db_prefix() . 'emails_manager.deleted', 0);
        $this->db->order_by(db_prefix() . 'emails_manager.created_at', 'desc');
        return $this->db->get()->result_array();
    }
}
