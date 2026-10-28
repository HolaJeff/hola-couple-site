<?php
$client_id = get_network_option(null, 'hw_classic_vacation_client_id', '');
$client_secret = get_network_option(null, 'hw_classic_vacation_client_secret', '');
$redirect = get_network_option(null, 'hw_classic_vacation_client_redirect_uri', '');
$token = get_network_option(null, 'hw_classic_vacation_token', '');
?>
<div class="p-3 bg-white rounded mt-3">
    <h3><?= __('API Settings', 'hola-weddings') ?></h3>
    <form id="classic-vacation-settings" method="post" action="<?= network_admin_url( 'admin.php?page=classic-vacations-connection' ) ?>">
        <input type="hidden" name="action" value="hw_save">    
        <div class="row mb-3">
            <div class="form-group col-4">
                <label for="client-id" class="form-label"><?= __('Client ID', 'hola-weddings') ?></label>
                <input type="text" name="client-id" value="<?= $client_id ?>" class="form-control d-inline-block" id="client-id" placeholder="<?= __('Insert Client ID', 'hola-weddings') ?>">
            </div>
            <div class="form-group col-4">
                <label for="client-secret" class="form-label"><?= __('Client Secret', 'hola-weddings') ?></label>
                <input type="text" name="client-secret" value="<?= $client_secret ?>" class="form-control" id="client-secret" placeholder="<?= __('Insert Client Secret', 'hola-weddings') ?>">
            </div>
            <div class="form-group col-4">
                <label for="redirect-uri" class="form-label"><?= __('Redirect URI', 'hola-weddings') ?></label>
                <input type="text" name="redirect-uri" value="<?= $redirect ?>" class="form-control" id="redirect-uri" placeholder="<?= __('Insert Redirect URI', 'hola-weddings') ?>">
            </div>
        </div>
    </form>
    <hr>
    <h3><?= __('API Connection', 'hola-weddings') ?></h3>
    <?php if(!empty($client_id) && !empty($client_secret) && !empty($redirect) && empty($token)): ?>
        <form method="get" action="https://connect.classicvacations.com/AuthorizationServer/OAuth/Authorize">
            <input type="hidden" name="client_id" value="<?= $client_id ?>">
            <input type="hidden" name="redirect_uri" value="<?= $redirect ?>">
            <input type="hidden" name="response_type" value="code">
            <div class="form-group col-4">
                <label for="scope" class="form-label"><?= __('Scope', 'hola-weddings') ?></label>
                <input type="text" name="scope" value="" class="form-control" id="scope" placeholder="<?= __('Insert scope', 'hola-weddings') ?>">
            </div>
            <button id="cv-connect-btn" type="button" class="btn btn-success mt-3"><?= __('Connect', 'hw-zoho') ?></button>
        </form>
    <?php elseif(!empty($token)): ?>
        <form id="classic-vacation-revoke-token" method="post" action="<?= network_admin_url( 'admin.php?page=classic-vacations-connection' ) ?>">
            <input type="hidden" name="action" value="hw_revoke_token">
            <div class="form-group col-4">
                <label for="access-token" class="form-label"><?= __('Token', 'hola-weddings') ?></label>
                <input type="text" disabled value="<?= $token['access_token'] ?>" class="form-control" id="token">
            </div>
            <button class="btn btn-danger mt-3" type="submit"><?= __('Revoke token', 'hola-weddings') ?></button>
        </form>
    <?php endif; ?>
</div>
<button form="classic-vacation-settings" type="submit" class="btn btn-primary align-bottom mt-3"><?= __('Save', 'hw-zoho') ?></button>

<script>
    jQuery(function($){

        $('#cv-connect-btn').on('click', function(){

            var scope = $('#scope').val(),
                btn = $(this),
                form = btn.closest('form');

            if(scope === ''){
                alert('Insert the scope');
                return;
            }

            btn.prop('disabled', true);

            let url = 'https://connect.classicvacations.com/AuthorizationServer/OAuth/Authorize';
            let query_args = [
                'client_id=<?= $client_id ?>',
                'redirect_uri=<?= $redirect ?>',
                'response_type=code',
                'scope='+scope
            ].join('&');

            window.location.href = url + '?' + query_args;
        });
    });
</script>