<?php
if (!function_exists('e')) {
    function e($value) {
        return html_escape($value);
    }
}

/**
 * Verify the purchase code with the external API.
 *
 * @param string $p_code The purchase code to verify.
 * @return mixed The API response or false on failure.
 */
function domain_manager_verify($p_code,$product)
{
    if (empty($p_code)) {
        return false; // Early return if the purchase code is empty
    }

    $api_url = 'https://verify.hopperstack.com';
    $query_params = http_build_query([
        'purchase_code' => $p_code,
        'url'           => site_url(),
        'item'          => $product
    ]);

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL            => $api_url . '?' . $query_params,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10, // Set a timeout to avoid long waits
        CURLOPT_SSL_VERIFYPEER  => true, // Ensure SSL verification
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (curl_errno($curl)) {
        log_message('error', 'cURL error: ' . curl_error($curl)); // Log any cURL errors
        $response = false;
    } elseif ($http_code !== 200) {
        log_message('error', 'API request failed with HTTP code: ' . $http_code);
        $response = false;
    }

    curl_close($curl);

    return $response;
}

/**
 * Query WHOIS to get Creation Date and Registrar
 *
 * @param string $domain The domain to lookup
 * @return array|false Returns ['registrar' => ..., 'purchase_date' => ...] or false
 */
function domain_manager_get_whois_info($domain)
{
    if (empty($domain)) return false;
    $domain = strtolower(trim($domain));
    $parts = explode('.', $domain);
    $tld = end($parts);

    $whois_servers = [
        'com'  => 'whois.verisign-grs.com',
        'net'  => 'whois.verisign-grs.com',
        'org'  => 'whois.pir.org',
        'info' => 'whois.afilias.net',
        'biz'  => 'whois.biz',
        'us'   => 'whois.nic.us',
        'uk'   => 'whois.nic.uk',
        'co'   => 'whois.nic.co',
        'me'   => 'whois.nic.me',
        'io'   => 'whois.nic.io',
        'ai'   => 'whois.nic.ai'
    ];

    $whois_server = isset($whois_servers[$tld]) ? $whois_servers[$tld] : 'whois.iana.org';

    // Route query if using IANA
    if ($whois_server == 'whois.iana.org') {
        $out = @fsockopen($whois_server, 43, $errno, $errstr, 3);
        if ($out) {
            fputs($out, $domain . "\r\n");
            $res = '';
            while (!feof($out)) $res .= fgets($out, 128);
            fclose($out);
            if (preg_match('/whois:\s+([a-z0-9\-\.]+)/i', $res, $matches)) {
                $whois_server = trim($matches[1]);
            }
        }
    }

    $out = @fsockopen($whois_server, 43, $errno, $errstr, 5);
    if (!$out) return false;

    // Wait for com/net verisign behavior (requires equals sign for exact match sometimes, but usually just domain is fine)
    fputs($out, ($whois_server == 'whois.verisign-grs.com' ? '=' : '') . $domain . "\r\n");
    $res = '';
    while (!feof($out)) {
        $res .= fgets($out, 128);
    }
    fclose($out);

    $data = [
        'registrar'     => null,
        'purchase_date' => null,
        'updated_date'  => null,
        'expiry_date'   => null,
        'raw_text'      => $res
    ];

    if (preg_match('/Registrar:\s+(.*)/i', $res, $matches)) {
        $data['registrar'] = trim($matches[1]);
    }

    if (preg_match('/Creation Date:\s+(.*)/i', $res, $matches) ||
        preg_match('/Created On:\s+(.*)/i', $res, $matches) ||
        preg_match('/Registration Date:\s+(.*)/i', $res, $matches) ||
        preg_match('/Registered on:\s+(.*)/i', $res, $matches) ||
        preg_match('/created:\s+(.*)/i', $res, $matches) ||
        preg_match('/registered:\s+(.*)/i', $res, $matches) ||
        preg_match('/creation-date:\s+(.*)/i', $res, $matches)) {
        $dt = trim($matches[1]);
        $time = @strtotime($dt);
        if ($time) {
            $data['purchase_date'] = date('Y-m-d', $time);
        }
    }

    if (preg_match('/Updated Date:\s+(.*)/i', $res, $matches) ||
        preg_match('/Last Updated On:\s+(.*)/i', $res, $matches) ||
        preg_match('/Last Updated:\s+(.*)/i', $res, $matches) ||
        preg_match('/updated:\s+(.*)/i', $res, $matches) ||
        preg_match('/last-update:\s+(.*)/i', $res, $matches)) {
        $dt = trim($matches[1]);
        $time = @strtotime($dt);
        if ($time) {
            $data['updated_date'] = date('Y-m-d', $time);
        }
    }

    if (preg_match('/Registry Expiry Date:\s+(.*)/i', $res, $matches) ||
        preg_match('/Expiration Date:\s+(.*)/i', $res, $matches) ||
        preg_match('/Expiration On:\s+(.*)/i', $res, $matches) ||
        preg_match('/Expires on:\s+(.*)/i', $res, $matches) ||
        preg_match('/expires:\s+(.*)/i', $res, $matches) ||
        preg_match('/registrar-expiration-date:\s+(.*)/i', $res, $matches)) {
        $dt = trim($matches[1]);
        $time = @strtotime($dt);
        if ($time) {
            $data['expiry_date'] = date('Y-m-d', $time);
        }
    }

    return $data;
}

/**
 * Synchronize the available_mailbox_count of a domain
 *
 * @param string $domain_name
 * @return void
 */
function domain_manager_sync_mailbox_count($domain_name)
{
    if (empty($domain_name)) return;
    $CI = &get_instance();
    $count = $CI->db->where('domain', $domain_name)->where('deleted', 0)->count_all_results(db_prefix() . 'emails_manager');
    $CI->db->where('domain_name', $domain_name);
    $CI->db->update(db_prefix() . 'domain_manager', ['available_mailbox_count' => $count]);
}

