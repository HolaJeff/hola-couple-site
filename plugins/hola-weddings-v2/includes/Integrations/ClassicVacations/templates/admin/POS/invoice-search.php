<?php
$quotes = hw_get_quote_list();

if(!empty($quotes)): ?>
    <div class="bg-white p-3 rounded">
        <h3 class="mt-0"><?= __('Search Invoice', 'hola-weddings') ?></h3>
        <div class="row">
            <div class="col-4 form-group">
                <label for="pos-quote-field" class="form-label"><?= __('Select Quote', 'hola-weddings') ?></label>
                <select id="pos-quote-field" class="form-select">
                    <option disabled selected><?= __('- Select Quote -', 'hola-weddings') ?></option>
                    <?php foreach($quotes as $quote): ?>
                        <option value="<?= $quote->getID() ?>"><?= sprintf( '#%d - %s', $quote->getID(), $quote->getTitle() ) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="invoices" class="col-4 form-group">
                <div class="field">
                    <label for="pos-invoice-field" class="form-label"><?= __('Select Invoice', 'hola-weddings') ?></label>
                    <select id="pos-invoice-field" class="form-select" disabled name="invoice_id">
                        <option disabled selected><?= __('- Select Invoice -', 'hola-weddings') ?></option>
                    </select>
                </div>
            </div>
        </div>
        <p class="fw-bold"><?= __('Simulate Payment', 'hola-weddings') ?></p>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="simulate" value="1" id="simulate" >
            <label class="form-check-label" for="pos-amount-full">
                <?= __('Yes', 'hola-weddings') ?>
            </label>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning"><?= __('No quotes were found', 'hola-weddings') ?></div>
<?php endif;