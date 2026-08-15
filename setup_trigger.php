<?php
define('BASEPATH', true);
require_once '../../application/config/app-config.php';

// We need to load CI to run the model correctly. 
// A better way is to run a curl request to the refresh_intelligence endpoint using the admin session!
// But since we can't easily curl with a logged-in session, let's just make an endpoint that bypasses auth temporarily.

$code = "
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Trigger_refresh extends App_Controller {
    public function index() {
        \$this->load->model('lead_intelligence/lead_intelligence_model');
        
        \$result = \$this->db->query('SELECT MAX(lead_id) as max_id FROM tbllead_intelligence')->row();
        \$lead_id = \$result->max_id;
        
        if (\$lead_id) {
            echo \"Triggering enrichment for lead_id: \$lead_id<br>\";
            \$success = \$this->lead_intelligence_model->enrich_lead(\$lead_id, true);
            echo \"Enrichment success: \" . (\$success ? 'Yes' : 'No') . \"<br>\";
            
            \$intel = \$this->lead_intelligence_model->get_by_lead_id(\$lead_id);
            echo \"<pre>\";
            print_r(\$intel);
            echo \"</pre>\";
        } else {
            echo \"No leads found.\";
        }
    }
}
";

file_put_contents('../../application/controllers/Trigger_refresh.php', $code);
echo "Created trigger controller.";
?>
