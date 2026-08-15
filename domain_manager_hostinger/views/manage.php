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

            <div class="tw-flex tw-justify-end">
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
});
</script>
</body>
</html>
