<div class="mt-3 bg-white p-3 p-md-5 rounded">
    <p class="mt-0 text-uppercase font-weight-bold mb-1"><?= __('Test Options', 'hola-weddings') ?></p> 
    <p><?= __('Visible only to admins', 'hola-weddings') ?></p> 
    <div class="row">
        <div class="col-3">
            <p class="mb-1 font-weight-bold"><?= __('Simulate Payment?', 'hola-weddings') ?></p>
            <div class="form-check form-check-inline mb-3">
                <input class="form-check-input" type="checkbox" name="simulate" value="1" id="simulate" >
                <label class="form-check-label" for="simulate">
                    <?= __('Yes', 'hola-weddings') ?>
                </label>
            </div>
        </div>
        <div class="col-3">
            <p class="mb-1 font-weight-bold"><?= __('Disable Redirect?', 'hola-weddings') ?></p>
            <div class="form-check form-check-inline mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="disable_redirect" >
                <label class="form-check-label" for="disable_redirect">
                    <?= __('Yes', 'hola-weddings') ?>
                </label>
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                <label for="test_response_type" class="form-label font-weight-bold"><?= __('Response Type', 'hola-weddings') ?></label>
                <select id="test_response_type" class="form-control" name="test_response_type">
                    <option value="success"><?= __('Success', 'hola-weddings') ?></option>
                    <option value="pending"><?= __('Pending', 'hola-weddings') ?></option>
                    <option value="failed"><?= __('Failed', 'hola-weddings') ?></option>
                </select>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-3">
            <p class="mb-1 font-weight-bold"><?= __('Skip form validation?', 'hola-weddings') ?></p>
            <div class="form-check form-check-inline mb-3">
                <input class="form-check-input" type="checkbox" id="skip_form_validation" >
                <label class="form-check-label" for="skip_form_validation">
                    <?= __('Yes', 'hola-weddings') ?>
                </label>
            </div>
        </div>
    </div>
</div>
