<?php
$card_types = hw_get_card_types();
$card_number = $card ? $card->getNumber() : '';
if($card && $hide_number){
    $card_number = sprintf('XXXX-XXXX-XXXX-%s', substr($card_number, -4));
}
?>
<br>
<div class="row fields">
    <div class="col">
        <div class="field">
            <div class="label"><?= __('Card Holder', 'hola-weddings') ?></div>
            <div class="value">
                <input type="text" name="card[holder]" value="<?= $card ? $card->getCardHolder() : '' ?>" class="form-control d-inline-block" id="c-holder" placeholder="<?= __('Insert Card Holder Name', 'hola-weddings') ?>" <?= $readonly ? 'readonly' : '' ?>>
            </div>
        </div>
        <div class="field">
            <div class="label"><?= __('Expiration Date', 'hola-weddings') ?></div>
            <div class="value d-flex align-content-items">
                <div class="form-group">
                    <input type="number" name="card[month]" value="<?= $card ? $card->getExpMonth() : '' ?>" class="form-control d-inline-block" id="e_month" placeholder="<?= __('MM', 'hola-weddings') ?>" <?= $readonly ? 'readonly' : '' ?>>
                    <div class="form-text"><?= __('Month', 'hola-weddings') ?></div>
                </div>
                <span class="p-2">/</span>
                <div class="form-group">
                    <input type="number" name="card[year]" value="<?= $card ? $card->getExpYear() : '' ?>" class="form-control d-inline-block" id="e_year" placeholder="<?= __('YYYY', 'hola-weddings') ?>" <?= $readonly ? 'readonly' : '' ?>>
                    <div class="form-text"><?= __('Year', 'hola-weddings') ?></div>
                </div>
            </div>
        </div>
        <div class="field">
            <div class="label"><?= __('Card Type', 'hola-weddings') ?></div>
            <div class="value">
                <select name="card[type]" class="form-select" id=""  <?= $readonly ? 'disabled' : '' ?>>
                    <?php foreach($card_types as $code => $label): ?>
                        <option <?= $card && $card->getType() == $code ? 'selected' : '' ?> value="<?= $code ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="field">
            <div class="label"><?= __('Card Number', 'hola-weddings') ?></div>
            <div class="value">
                <input type="text" name="card[number]" value="<?= $card_number ?>" class="form-control d-inline-block" id="c-number" placeholder="<?= __('Insert Card Number', 'hola-weddings') ?>" <?= $readonly ? 'readonly' : '' ?>>
            </div>
        </div>
        <?php if(!$hide_ccv): ?>
            <div class="label"><?= __('CCV', 'hola-weddings') ?></div>
            <div class="value">
                <input type="number" name="card[ccv]" value="<?= $card ? $card->getCCV() : '' ?>" class="form-control d-inline-block" id="c_ccv" placeholder="<?= __('123', 'hola-weddings') ?>" <?= $readonly ? 'readonly' : '' ?>>
            </div>
        <?php endif; ?>
    </div>
</div>
