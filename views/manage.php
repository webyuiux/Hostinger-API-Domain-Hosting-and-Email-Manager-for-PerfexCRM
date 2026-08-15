<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-8 col-md-offset-2">
            <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
               <?php echo _l('domain_manager_setting') ?>
            </h4>

            <?php echo form_open($this->uri->uri_string()); ?>



            <!-- Hostinger API Integration -->
            <div class="h-panel">
                <div class="h-panel-header">
                   <h4 class="h-panel-title"><i class="fa fa-plug tw-mr-1"></i> Hostinger API Integration</h4>
                </div>
                <div class="panel-body tw-p-8">
                  <div class="alert alert-info">
                     <i class="fa fa-info-circle"></i>
                     Connect your Hostinger account to sync domains and websites automatically.
                     Get your API token from <a href="https://hpanel.hostinger.com/api" target="_blank"><strong>hPanel &rarr; Account &rarr; API</strong></a>.
                  </div>

                  <?php if (!empty($hostinger_token_set)): ?>
                  <div class="alert alert-success">
                     <i class="fa fa-check-circle"></i> Hostinger API token is configured.
                     <button type="button" id="test-hostinger-btn" class="btn btn-xs btn-default pull-right">
                        <i class="fa fa-plug"></i> Test Connection
                     </button>
                  </div>
                  <div id="hostinger-test-result" style="display:none;margin-top:8px;"></div>
                  <?php endif; ?>

                  <div class="form-group">
                     <label class="control-label"><?php echo _l('hostinger_api_token'); ?></label>
                     <?php if (!empty($hostinger_token_set)): ?>
                     <div class="input-group" style="margin-bottom:6px">
                        <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                        <input type="text" class="form-control" readonly value="<?php echo str_repeat('*', max(0, strlen($hostinger_token) - 6)) . substr($hostinger_token, -6); ?>">
                     </div>
                     <small class="text-muted" style="display:block;margin-bottom:8px">Token is saved. Enter a new token below to replace it, or leave blank to keep current.</small>
                     <?php endif; ?>
                     <input type="password"
                            name="settings[domain_manager_hostinger_api_token]"
                            class="form-control"
                            autocomplete="new-password"
                            placeholder="<?php echo !empty($hostinger_token_set) ? 'Enter new token to replace current...' : 'Paste your Hostinger Bearer token here'; ?>"
                            value="">
                     <small class="text-muted"><?php echo _l('hostinger_api_token_help'); ?></small>
                  </div>
               </div>
            </div>

            <!-- Notifications -->
            <div class="h-panel tw-mt-6">

                <div class="h-panel-header">
                   <h4 class="h-panel-title"><i class="fa fa-envelope tw-mr-1"></i> Expiry Notifications</h4>
                </div>
                <div class="panel-body tw-p-8">
                   <div class="form-group">
                      <label class="control-label">Send Expiry Alerts To</label>
                      <select name="settings[domain_manager_notify_recipients]" class="selectpicker form-control" data-width="100%">
                         <option value="Customer Only" <?php echo ($notify_recipients === 'Customer Only') ? 'selected' : ''; ?>>Customer Only</option>
                         <option value="Staff Only" <?php echo ($notify_recipients === 'Staff Only') ? 'selected' : ''; ?>>Staff Only</option>
                         <option value="Customer's Contact Email + Staff Assigned to Customer" <?php echo ($notify_recipients === "Customer's Contact Email + Staff Assigned to Customer") ? 'selected' : ''; ?>>Customer's Contact Email + Staff Assigned to Customer</option>
                         <option value="Customer + Assigned Staff + Additional Emails" <?php echo ($notify_recipients === 'Customer + Assigned Staff + Additional Emails') ? 'selected' : ''; ?>>Customer + Assigned Staff + Additional Emails</option>
                      </select>
                      <small class="text-muted">Select the routing rule for sending domain expiration alerts.</small>
                   </div>
                   <div class="form-group">
                      <label class="control-label">Specific Staff to Notify</label>
                      <select name="settings[domain_manager_notify_specific_staff][]" class="selectpicker form-control" multiple data-width="100%" data-live-search="true" data-none-selected-text="Nothing Selected">
                         <?php foreach ($staff as $member) { ?>
                         <option value="<?php echo $member['staffid']; ?>" <?php echo in_array($member['staffid'], $notify_specific_staff) ? 'selected' : ''; ?>>
                            <?php echo e($member['firstname'] . ' ' . $member['lastname']); ?>
                         </option>
                         <?php } ?>
                      </select>
                      <small class="text-muted">Select specific staff members who should receive notifications for all expiring assets.</small>
                   </div>
                   <div class="form-group">
                      <label class="control-label">Additional Email Address(es) (Optional)</label>
                      <input type="text"
                             name="settings[domain_manager_notification_emails]"
                             class="form-control"
                             placeholder="e.g. admin@example.com, alerts@example.com"
                             value="<?php echo html_escape($notification_emails); ?>">
                      <small class="text-muted">These emails <strong>always</strong> receive expiry alerts regardless of the routing rule selected above. Enter comma-separated addresses.</small>
                   </div>
                  <div class="form-group">
                     <label class="control-label">Days Before Expiry to Warn</label>
                     <input type="text"
                            name="settings[domain_manager_notify_days]"
                            class="form-control"
                            placeholder="e.g. 30,15,7,3,1,0"
                            value="<?php echo html_escape($notify_days); ?>">
                     <small class="text-muted">Comma-separated number of days before expiry to send alerts. Default: <strong>30,15,7,3,1,0</strong> (alerts at 30, 15, 7, 3, 1 days before and on expiry day).</small>
                  </div>
               </div>
            </div>

            <!-- Send Test Email -->
            <div class="h-panel tw-mt-6">
                <div class="h-panel-header">
                   <h4 class="h-panel-title"><i class="fa fa-paper-plane tw-mr-1"></i> Send Test Email</h4>
                </div>
                <div class="panel-body tw-p-8">
                   <p class="text-muted tw-mb-4">Send test email to make sure that your SMTP settings is set correctly.</p>
                   <div class="input-group" style="max-width: 520px;">
                      <input type="email"
                             id="test-email-address"
                             class="form-control"
                             placeholder="Email Address"
                             value="<?php echo html_escape(get_option('smtp_email')); ?>">
                      <span class="input-group-btn">
                         <button type="button" id="send-test-email-btn" class="btn btn-primary" style="border-radius: 0 4px 4px 0; padding: 6px 20px; font-weight: 600;">
                            <i class="fa fa-paper-plane"></i> Test
                         </button>
                      </span>
                   </div>
                   <div id="test-email-result" style="display:none; margin-top:12px; max-width:520px;"></div>
                </div>
            </div>

            <div class="tw-flex tw-justify-end tw-mt-6">
               <button class="btn-h-primary" type="submit">
                  <i class="fa fa-save tw-mr-1"></i> <?php echo _l('Save'); ?>
               </button>
            </div>
            <?php echo form_close(); ?>
         </div>
      </div>
   </div>
</div>
<?php init_tail(); ?>
<script>
$(function () {
   // Hostinger API test
   $('#test-hostinger-btn').on('click', function () {
      var $btn = $(this).prop('disabled', true).text('Testing...');
      var $result = $('#hostinger-test-result').show();
      $.ajax({
         url: '<?php echo admin_url('domain_manager_hostinger/hostinger_api_test'); ?>',
         type: 'GET',
         dataType: 'json',
         success: function (resp) {
            $result.removeClass('alert-success alert-danger').addClass('alert ' + (resp.success ? 'alert-success' : 'alert-danger'));
            $result.html('<i class="fa ' + (resp.success ? 'fa-check-circle' : 'fa-times-circle') + '"></i> ' + resp.message);
         },
         error: function () {
            $result.addClass('alert alert-danger').html('<i class="fa fa-times-circle"></i> Request failed.');
         },
         complete: function () {
            $btn.prop('disabled', false).html('<i class="fa fa-plug"></i> Test Connection');
         }
      });
   });

   // Send Test Email
   $('#send-test-email-btn').on('click', function () {
      var email = $('#test-email-address').val().trim();
      if (!email) {
         alert('Please enter an email address.');
         return;
      }
      var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');
      var $result = $('#test-email-result').show()
         .removeClass('alert-success alert-danger alert-info')
         .addClass('alert alert-info')
         .html('<i class="fa fa-spinner fa-spin"></i> Sending test email to <strong>' + email + '</strong>...');

      $.ajax({
         url: '<?php echo admin_url('domain_manager_hostinger/send_test_email_ajax'); ?>',
         type: 'POST',
         dataType: 'json',
         data: {
            email: email,
            <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
         },
         success: function (resp) {
            $result.removeClass('alert-info').addClass(resp.success ? 'alert-success' : 'alert-danger');
            $result.html('<i class="fa ' + (resp.success ? 'fa-check-circle' : 'fa-times-circle') + '"></i> ' + resp.message);
         },
         error: function () {
            $result.removeClass('alert-info').addClass('alert-danger').html('<i class="fa fa-times-circle"></i> Test email request failed. Check your SMTP settings.');
         },
         complete: function () {
            $btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Test');
         }
      });
   });


});
</script>
</body>
</html>
