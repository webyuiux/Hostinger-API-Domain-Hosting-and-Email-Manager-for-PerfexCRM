<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Hostinger_api_model extends App_Model
{
    private $api_base = 'https://developers.hostinger.com';
    private $api_token = '';

    public function __construct()
    {
        parent::__construct();
        $this->api_token = get_option('domain_manager_hostinger_api_token');
    }

    /**
     * Make an authenticated GET request to Hostinger API.
     *
     * @param string $endpoint
     * @param array  $params
     * @return array|false
     */
    private function get($endpoint, $params = [])
    {
        if (empty($this->api_token)) {
            return ['error' => 'No Hostinger API token configured.'];
        }

        $url = $this->api_base . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->api_token,
                'Accept: application/json',
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (compatible; PerfexCRM/1.0)',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return ['error' => 'cURL error: ' . $curl_error];
        }

        $data = json_decode($response, true);

        if ($http_code === 0) {
            return ['error' => 'Connection failed - check your server can reach developers.hostinger.com'];
        }

        if ($http_code !== 200) {
            $message = '';
            if (is_array($data)) {
                $message = isset($data['message']) ? $data['message'] : (isset($data['error']) ? $data['error'] : json_encode($data));
            }
            if (empty($message)) {
                $message = 'HTTP ' . $http_code . ' from ' . $url;
            }
            return ['error' => $message];
        }

        return $data;
    }

    /**
     * Test the API connection.
     *
     * @return array
     */
    public function test_connection()
    {
        $result = $this->get('/api/domains/v1/portfolio');
        if (isset($result['error'])) {
            return ['success' => false, 'message' => $result['error']];
        }
        return ['success' => true, 'message' => 'Connection successful.'];
    }

    /**
     * Fetch all domains from Hostinger.
     */
    public function get_domains()
    {
        $all_domains = [];
        $page = 1;

        do {
            $result = $this->get('/api/domains/v1/portfolio', ['page' => $page, 'per_page' => 100]);

            if (isset($result['error'])) {
                return ['error' => $result['error']];
            }

            // Handle both response formats:
            // Format A: {"data": [...], "meta": {...}}
            // Format B: direct array of domains
            if (isset($result['data']) && is_array($result['data'])) {
                $items    = $result['data'];
                $has_more = isset($result['meta']['current_page']) && isset($result['meta']['last_page'])
                    && $result['meta']['current_page'] < $result['meta']['last_page'];
            } elseif (isset($result[0]) || (is_array($result) && !isset($result['data']))) {
                // Direct array response
                $items    = array_values($result);
                $has_more = false;
            } else {
                $items    = [];
                $has_more = false;
            }

            $all_domains = array_merge($all_domains, $items);
            $page++;
        } while ($has_more && $page <= 20);

        return ['data' => $all_domains, 'raw_count' => count($all_domains)];
    }

    /**
     * Fetch all websites from Hostinger hosting.
     */
    public function get_websites()
    {
        $all_websites = [];
        $page = 1;

        do {
            $result = $this->get('/api/hosting/v1/websites', ['page' => $page, 'per_page' => 100]);

            if (isset($result['error'])) {
                // Fallback to domains if hosting endpoint is unauthorized
                $fallback = $this->get_domains();
                if (!isset($fallback['error'])) {
                    return $fallback;
                }
                return ['error' => $result['error']];
            }

            if (isset($result['data']) && is_array($result['data'])) {
                $items    = $result['data'];
                $has_more = isset($result['meta']['current_page']) && isset($result['meta']['last_page'])
                    && $result['meta']['current_page'] < $result['meta']['last_page'];
            } elseif (is_array($result) && !isset($result['data'])) {
                $items    = array_values($result);
                $has_more = false;
            } else {
                $items    = [];
                $has_more = false;
            }

            $all_websites = array_merge($all_websites, $items);
            $page++;
        } while ($has_more && $page <= 20);

        return ['data' => $all_websites];
    }

    /**
     * Sync Hostinger domains into the local domain_manager table.
     * Strategy: Update existing records (matched by domain_name), insert new ones.
     *
     * @return array  Summary with counts
     */
    public function sync_domains()
    {
        $result = $this->get_domains();

        if (isset($result['error'])) {
            return ['success' => false, 'message' => $result['error']];
        }

        $domains     = $result['data'];
        $inserted    = 0;
        $updated     = 0;
        $now         = date('Y-m-d H:i:s');

        foreach ($domains as $domain) {
        // Map domain field — Hostinger uses 'domain' key
            $domain_name = null;
            if (isset($domain['domain'])) {
                $domain_name = strtolower(trim($domain['domain']));
            } elseif (isset($domain['name'])) {
                $domain_name = strtolower(trim($domain['name']));
            } elseif (isset($domain['fqdn'])) {
                $domain_name = strtolower(trim($domain['fqdn']));
            }
            if (!$domain_name) continue;

            // Determine domain type: internal = registered at Hostinger, external = pointed there
            $raw_type = isset($domain['type']) ? strtolower($domain['type']) : 'external';
            $domain_type = in_array($raw_type, ['domain', 'free_domain']) ? 'internal' : 'external';
            if (empty($domain_type)) {
                $domain_type = 'external';
            }

            // Map status
            $raw_status    = isset($domain['status']) ? strtolower($domain['status']) : 'active';
            $status_map    = [
                'active'    => 'active',
                'expired'   => 'expired',
                'suspended' => 'suspended',
                'pending'   => 'pending',
            ];
            $status = isset($status_map[$raw_status]) ? $status_map[$raw_status] : 'active';

            // Expiry date
            $expiry_date = null;
            if (!empty($domain['expires_at'])) {
                $expiry_date = date('Y-m-d', strtotime($domain['expires_at']));
            }

            // Created / purchase date from Hostinger
            $purchase_date = null;
            if (!empty($domain['created_at'])) {
                $purchase_date = date('Y-m-d', strtotime($domain['created_at']));
            } elseif (!empty($domain['registered_at'])) {
                $purchase_date = date('Y-m-d', strtotime($domain['registered_at']));
            } elseif (!empty($domain['purchase_date'])) {
                $purchase_date = date('Y-m-d', strtotime($domain['purchase_date']));
            } elseif (!empty($domain['active_at'])) {
                $purchase_date = date('Y-m-d', strtotime($domain['active_at']));
            }

            // Registrar
            $registrar = 'Hostinger';

            // Check if domain already exists
            $this->db->where('domain_name', $domain_name);
            $existing = $this->db->get(db_prefix() . 'domain_manager')->row();

            if ($existing) {
                // Update existing record
                $update_data = [
                    'status'              => $status,
                    'domain_type'         => $domain_type,
                    'expiry_date'         => $expiry_date,
                    'registrar'           => $registrar,
                    'purchase_date'       => $purchase_date,
                    'hostinger_domain_id' => isset($domain['id']) ? $domain['id'] : null,
                    'hostinger_synced_at' => $now,
                    'deleted'             => 0,
                    'updated_at'          => $now,
                ];
                $this->db->where('id', $existing->id);
                $this->db->update(db_prefix() . 'domain_manager', $update_data);
                $updated++;
            } else {
                // Insert new record
                $insert_data = [
                    'domain_name'         => $domain_name,
                    'registrar'           => $registrar,
                    'status'              => $status,
                    'domain_type'         => $domain_type,
                    'expiry_date'         => $expiry_date,
                    'purchase_date'       => $purchase_date,
                    'dns_hosting'         => 'enabled',
                    'registration_status' => $status,
                    'hostinger_domain_id' => isset($domain['id']) ? $domain['id'] : null,
                    'hostinger_synced_at' => $now,
                    'created_by'          => get_staff_user_id(),
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];
                $this->db->insert(db_prefix() . 'domain_manager', $insert_data);
                $inserted++;
            }
        }

        return [
            'success'  => true,
            'inserted' => $inserted,
            'updated'  => $updated,
            'total'    => count($domains),
            'message'  => 'Sync complete: ' . $inserted . ' added, ' . $updated . ' updated out of ' . count($domains) . ' domains.',
        ];
    }

    /**
     * Sync Hostinger websites into the local hosting_details table.
     * Strategy: Update existing (matched by hostinger_website_id), insert new ones. Coexist with manual entries.
     *
     * @return array  Summary with counts
     */
    public function sync_websites()
    {
        $result = $this->get_websites();

        if (isset($result['error'])) {
            return ['success' => false, 'message' => $result['error']];
        }

        $websites = $result['data'];
        $inserted = 0;
        $updated  = 0;
        $now      = date('Y-m-d H:i:s');

        foreach ($websites as $site) {
            $site_domain = isset($site['domain']) ? strtolower(trim($site['domain'])) : (isset($site['name']) ? strtolower(trim($site['name'])) : null);
            if (!$site_domain) continue;

            $hostinger_id = isset($site['id']) ? $site['id'] : null;
            $raw_status   = isset($site['status']) ? strtolower($site['status']) : 'active';
            $status       = in_array($raw_status, ['active', 'suspended', 'pending']) ? $raw_status : 'active';
            $datacenter   = isset($site['datacenter_code']) ? $site['datacenter_code'] : null;
            
            // Extract actual created date from Hostinger
            $hostinger_created = $now;
            if (!empty($site['created_at'])) {
                $hostinger_created = date('Y-m-d H:i:s', strtotime($site['created_at']));
            } elseif (!empty($site['registered_at'])) {
                $hostinger_created = date('Y-m-d H:i:s', strtotime($site['registered_at']));
            }

            // Try to find matching domain record
            $this->db->where('domain_name', $site_domain);
            $domain_record = $this->db->get(db_prefix() . 'domain_manager')->row();
            $domain_id = $domain_record ? $domain_record->id : null;

            // ---------------------------------------------------------------
            // Two-stage lookup to prevent duplicate inserts:
            //
            // Stage 1: Match by Hostinger website ID (fast, exact).
            //          This handles all records that have been synced before.
            //
            // Stage 2: Fallback — match by website_name (domain string).
            //          This handles records created before the hostinger_website_id
            //          column existed, or records entered manually with the same name.
            //          Once matched, we write the ID back so Stage 1 is used next time.
            // ---------------------------------------------------------------
            $existing = null;

            if ($hostinger_id) {
                $this->db->where('hostinger_website_id', $hostinger_id);
                $this->db->where('deleted', 0);
                $existing = $this->db->get(db_prefix() . 'hosting_details')->row();
            }

            if (!$existing && $site_domain) {
                // Fallback: find by website_name (handles legacy / manually-added records)
                $this->db->where('website_name', $site_domain);
                $this->db->where('deleted', 0);
                $existing = $this->db->get(db_prefix() . 'hosting_details')->row();

                // Write the Hostinger ID back so future syncs use Stage 1 (no more duplicates)
                if ($existing && $hostinger_id) {
                    $this->db->where('id', $existing->id);
                    $this->db->update(db_prefix() . 'hosting_details', [
                        'hostinger_website_id' => $hostinger_id,
                    ]);
                }
            }

            // Calculate expiration date (1 year after created date)
            $calculated_expiration = date('Y-m-d', strtotime('+1 year', strtotime($hostinger_created)));

            if ($existing) {
                $update_data = [
                    'status'              => $status,
                    'domain_id'           => $domain_id,
                    'website_name'        => $site_domain,
                    'datacenter'          => $datacenter,
                    'hostinger_synced_at' => $now,
                    'deleted'             => 0,
                    'updated_at'          => $now,
                ];
                // Only update created_at if it's the actual hostinger date and wasn't set previously
                if ($hostinger_created !== $now) {
                    $update_data['created_at'] = $hostinger_created;
                }
                
                // If the expiration date is missing or 0000-00-00, automatically set it to the calculated 1-year expiry
                if (empty($existing->expiration_date) || $existing->expiration_date === '0000-00-00') {
                    $update_data['expiration_date'] = $calculated_expiration;
                }
                
                $this->db->where('id', $existing->id);
                $this->db->update(db_prefix() . 'hosting_details', $update_data);
                $updated++;
            } else {
                $insert_data = [
                    'domain_id'           => $domain_id,
                    'provider'            => 'Hostinger',
                    'website_name'        => $site_domain,
                    'status'              => $status,
                    'datacenter'          => $datacenter,
                    'hostinger_website_id'=> $hostinger_id,
                    'hostinger_synced_at' => $now,
                    'created_by'          => get_staff_user_id(),
                    'created_at'          => $hostinger_created,
                    'expiration_date'     => $calculated_expiration,
                    'updated_at'          => $now,
                ];
                $this->db->insert(db_prefix() . 'hosting_details', $insert_data);
                $inserted++;
            }
        }

        return [
            'success'  => true,
            'inserted' => $inserted,
            'updated'  => $updated,
            'total'    => count($websites),
            'message'  => "Sync complete: {$inserted} added, {$updated} updated out of " . count($websites) . " websites.",
        ];
    }

    /**
     * Return all Hostinger-synced domains that have no Perfex client assigned.
     * Used by the Settings UI to let admins link domains to clients.
     *
     * @return array
     */
    public function get_unlinked_domains()
    {
        $this->db->select(db_prefix() . 'domain_manager.id, ' . db_prefix() . 'domain_manager.domain_name, ' . db_prefix() . 'domain_manager.expiry_date, ' . db_prefix() . 'domain_manager.status, ' . db_prefix() . 'domain_manager.domain_type');
        $this->db->from(db_prefix() . 'domain_manager');
        $this->db->where('hostinger_domain_id IS NOT NULL', null, false);
        $this->db->group_start();
        $this->db->where('client_id', null);
        $this->db->or_where('client_id', 0);
        $this->db->group_end();
        $this->db->where('deleted', 0);
        $this->db->order_by('domain_name', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Auto-match unlinked Hostinger domains to clients by comparing
     * the domain name against client company names (case-insensitive substring).
     * Returns an array of suggested matches: [domain_id => client_id].
     *
     * @return array
     */
    public function auto_match_domains_to_clients()
    {
        $unlinked = $this->get_unlinked_domains();
        if (empty($unlinked)) {
            return [];
        }

        // Fetch all active clients
        $clients = $this->db->select('userid, company, active')
            ->from(db_prefix() . 'clients')
            ->where('active', 1)
            ->get()->result_array();

        $matches = [];

        foreach ($unlinked as $domain) {
            $domain_name = strtolower($domain['domain_name']);
            // Strip TLD and www for matching (e.g. "virratglobal.com" → "virratglobal")
            $domain_base = preg_replace('/\.[a-z]{2,6}(\.[a-z]{2})?$/', '', $domain_name);
            $domain_base = ltrim($domain_base, 'www.');

            foreach ($clients as $client) {
                $company_slug = strtolower(preg_replace('/[^a-z0-9]/i', '', $client['company']));
                $domain_slug  = preg_replace('/[^a-z0-9]/i', '', $domain_base);

                // Match if domain slug is contained in company slug or vice versa
                if (!empty($domain_slug) && (
                    strpos($company_slug, $domain_slug) !== false ||
                    strpos($domain_slug, $company_slug) !== false
                )) {
                    $matches[(int)$domain['id']] = (int)$client['userid'];
                    break; // take first match only
                }
            }
        }

        return $matches;
    }
}
