<?php
$quote = $invoice->getQuote();
$card = $quote->getCardData();
?>
<div id="card-form" class="mt-3 bg-white p-5 rounded">
    <p class="mt-0 text-uppercase font-weight-bold"><?= __('Payment Method', 'hola-weddings') ?></p>  

    <?php if($quote->hasCardData()): ?>
        <div id="use_saved_card-container" class="form-group custom-control custom-radio pl-0">
            <div class="form-check ">
                <input class="form-check-input custom-control-input" name="use_saved_card" type="radio" id="use_saved_card" value="1" checked>
                <label class="form-check-label custom-control-label" for="use_saved_card"><?= __('Use saved card ', 'hola-weddings') ?> <span id="card-digits"><?= $card ? sprintf('XXXX - XXXX - XXXX - %s', substr($card->getNumber(), -4)) : '' ?></span></label>
            </div>
        </div>
        <div id="use_saved_card-container" class="form-group custom-control custom-radio pl-0">
            <div class="form-check ">
                <input class="form-check-input custom-control-input" name="use_saved_card" type="radio" id="use_new_card" value="0" >
                <label class="form-check-label custom-control-label" for="use_new_card"><?= __('Use different card', 'hola-weddings') ?></label>
            </div>
        </div>
    <?php else: ?>
        <input name="use_saved_card" type="hidden" value="0" >
    <?php endif; ?>

    <div class="fields mb-3 rounded alert alert-primary mt-3 pt-3" <?= $quote->hasCardData() ? 'style="display: none;"' : '' ?>>

        <?= hw_get_template('template-parts/partials/forms/card-form') ?>
        
        <div>
            <button id="clear-card-fields" type="button" class="btn btn-link p-0 text-small" style="font-size: 0.9em">
                <?= __('Clear Card Fields', 'hola-weddings') ?>
            </button>
        </div>
    </div>
</div>