<br>
<?php

$card = $quote->getCardData();
$card_types = hw_get_card_types();
$card_number = $card ? $card->getNumber() : '';
?>
<div class="row fields mt-3">
    <div class="col">
        <div class="field">
            <div class="label"><?= __('Card Holder', 'hola-weddings') ?></div>
            <div class="value">
                <input type="text" name="data[card][holder]" value="<?= $card ? $card->getCardHolder() : '' ?>" class="form-control d-inline-block" id="c-holder" placeholder="<?= __('Insert Card Holder Name', 'hola-weddings') ?>" <?= $readonly ? 'readonly' : '' ?>>
            </div>
        </div>
        <div class="field">
            <div class="label"><?= __('Expiration Date', 'hola-weddings') ?></div>
            <div class="value d-flex align-content-items">
                <div class="form-group">
                    <input type="number" name="data[card][month]" value="<?= $card ? $card->getExpMonth() : '' ?>" class="form-control d-inline-block" id="e_month" placeholder="<?= __('MM', 'hola-weddings') ?>" <?= $readonly ? 'readonly' : '' ?>>
                    <div class="form-text"><?= __('Month', 'hola-weddings') ?></div>
                </div>
                <span class="p-2">/</span>
                <div class="form-group">
                    <input type="number" name="data[card][year]" value="<?= $card ? $card->getExpYear() : '' ?>" class="form-control d-inline-block" id="e_year" placeholder="<?= __('YYYY', 'hola-weddings') ?>" <?= $readonly ? 'readonly' : '' ?>>
                    <div class="form-text"><?= __('Year', 'hola-weddings') ?></div>
                </div>
            </div>
        </div>
        <div class="field">
            <div class="label"><?= __('Card Type', 'hola-weddings') ?></div>
            <div class="value">
                <select name="data[card][type]" class="form-select" id=""  <?= $readonly ? 'disabled' : '' ?>>
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
                <input type="text" name="data[card][number]" value="<?= $card_number ?>" class="form-control d-inline-block" id="c-number" placeholder="<?= __('Insert Card Number', 'hola-weddings') ?>" <?= $readonly ? 'readonly' : '' ?>>
            </div>
        </div>
        <div class="field">
            <div class="label"><?= __('CCV', 'hola-weddings') ?></div>
            <div class="value d-flex">
                <?php if($quote->hasCardData()): ?>
                    <input type="number" name="data[card][ccv]" value="<?= $card->getCCV() ?: ''  ?>" <?= !empty($card->getCCV()) && !current_user_can('administrator') ? 'disabled' : '' ?> class="form-control d-inline-block" id="c-ccv" placeholder="<?= __('Insert Card CCV', 'hola-weddings') ?>" <?= $readonly ? 'readonly' : '' ?>>
                    
                    <?php if(!empty($card->getCCV()) &&  current_user_can('administrator')): ?>
                        <a id="delete-card" class="btn btn-outline-danger ms-2" title="<?= __('Remove CCV') ?>" href="<?= get_edit_post_link() ?>&hw_delete_card=1" href="#">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>