<div id="card-form" class="mt-3 bg-white p-3 rounded" style="display: none;">
    <h3 class="mt-0"><?= __('Card Information', 'hola-weddings') ?></h3>

    <div id="use_saved_card-container" class="form-group">
        <div class="form-check ">
            <input class="form-check-input" name="use_saved_card" type="radio" id="use_saved_card" value="1" checked>
            <label class="form-check-label" for="use_saved_card"><?= __('Use customer saved card ending with ', 'hola-weddings') ?><span id="card-digits"></span></label>
        </div>
    </div>

    <div id="use_saved_card-container" class="form-group">
        <div class="form-check ">
            <input class="form-check-input" name="use_saved_card" type="radio" id="use_new_card" value="0" >
            <label class="form-check-label" for="use_new_card"><?= __('Use new card', 'hola-weddings') ?></label>
        </div>
    </div>

    <div class="fields row mb-3 border-top mt-3 pt-3" style="display: none;">
        
        <div class="form-group col-11 mb-2">
            <label for="c-holder" class="form-label"><?= __('Card Holder Name', 'hola-weddings') ?></label>
            <input type="text" name="card[holder]" value="" class="form-control d-inline-block" id="c-holder" placeholder="<?= __('Insert Card Holder Name', 'hola-weddings') ?>">
        </div>
        <div class="form-group col-8">
            <label for="c-number" class="form-label"><?= __('Card Number', 'hola-weddings') ?></label>
            <input type="text" name="card[number]" value="" class="form-control d-inline-block" id="c-number" placeholder="<?= __('Insert Card Number', 'hola-weddings') ?>">
        </div>
        <div class="form-group col-1">
            <label for="e_month" class="form-label"><?= __('Month', 'hola-weddings') ?></label>
            <input type="number" name="card[month]" value="" class="form-control d-inline-block" id="e_month" placeholder="<?= __('MM', 'hola-weddings') ?>">
        </div>
        <div class="form-group col-1">
            <label for="e_year" class="form-label"><?= __('Year', 'hola-weddings') ?></label>
            <input type="number" name="card[year]" value="" class="form-control d-inline-block" id="e_year" placeholder="<?= __('YYYY', 'hola-weddings') ?>">
        </div>
        <div class="form-group col-2 mt-2">
            <label for="c_ccv" class="form-label"><?= __('CCV', 'hola-weddings') ?></label>
            <input type="number" name="card[ccv]" value="" class="form-control d-inline-block" id="c_ccv" placeholder="<?= __('123', 'hola-weddings') ?>">
        </div>
        <div class="form-group col-11">
            <label class="form-label d-block mt-2"><?= __('Card Type', 'hola-weddings') ?></label>
            <div class="form-check form-check-inline">
                <input type="radio" name="card[type]" value="001" class="form-check-input float-none" id="visa" checked>
                <label for="visa" class="form-check-label align-top"><?= __('Visa', 'hola-weddings') ?></label>
            </div>
            <div class="form-check form-check-inline">
                <input type="radio" name="card[type]" value="002" class="form-check-input float-none" id="mastercard">
                <label for="mastercard" class="form-check-label align-top"><?= __('MasterCard', 'hola-weddings') ?></label>
            </div>
            <div class="form-check form-check-inline">
                <input type="radio" name="card[type]" value="003" class="form-check-input float-none" id="amex">
                <label for="amex" class="form-check-label align-top"><?= __('AMEX', 'hola-weddings') ?></label>
            </div>
            <div class="form-check form-check-inline">
                <input type="radio" name="card[type]" value="004" class="form-check-input float-none" id="discover">
                <label for="discover" class="form-check-label align-top"><?= __('Discover', 'hola-weddings') ?></label>
            </div>
            <div class="form-check form-check-inline">
                <input type="radio" name="card[type]" value="005" class="form-check-input float-none" id="diners">
                <label for="diners" class="form-check-label align-top"><?= __('Diners Club', 'hola-weddings') ?></label>
            </div>
        </div>

        <div>
            <button id="clear-card-fields" type="button" class="btn btn-link p-0 text-small" style="font-size: 0.9em">
                <?= __('Clear data', 'hola-weddings') ?>
            </button>
        </div>
    </div>
</div>