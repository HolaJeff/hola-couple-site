<?php 

$deposit = hw_get_min_deposit();

if(is_payment_enabled()): ?>
    <div id="payment-card" class="mt-3 card p-3 show-if-payment">
        <h4 class="mb-3"><?= __('Payment', 'hola-weddings') ?></h4>
        <!-- first row -->
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="card-owner"><?= __('Card Owner', 'hola-weddings') ?></label>
                    <input id="card-owner" type="text" class="form-control <?= isset($errors['card-owner']) ? 'is-invalid' : '' ?>" placeholder="<?= __('Card Owner', 'hola-weddings') ?>" name="payment[card-owner]" value="<?= isset($data['payment']['card-owner']) ? $data['payment']['card-owner'] : '' ?>" />
                    <?php if(isset($errors['card-owner'])): ?>
                        <div id="card-owner-Feedback" class="invalid-feedback">
                            <?= $errors['card-owner'] ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><?= __('Card Type', 'hola-weddings') ?></label>
                    <br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input card-type" type="radio" name="payment[card-type]" id="visa" value="visa" checked>
                            <label class="form-check-label" for="visa">
                                <img src="<?= HW_ASSETS_URL ?>/img/visa.png" style="height: 32px; width: auto;" />
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input card-type" type="radio" name="payment[card-type]" id="mc" value="mc">
                            <label class="form-check-label" for="mc">
                                <img src="<?= HW_ASSETS_URL ?>/img/mc.png" style="height: 32px; width: auto;" />
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input card-type" type="radio" name="payment[card-type]" id="amex" value="amex">
                            <label class="form-check-label" for="amex">
                                <img src="<?= HW_ASSETS_URL ?>/img/amex.png" style="height: 32px; width: auto;" />
                            </label>
                        </div>
                    
                    <?php if(isset($errors['card-type'])): ?>
                        <div id="card-type-Feedback" class="invalid-feedback">
                            <?= $errors['card-type'] ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- 2nd row -->
        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-0">
                    <label for="card-number"><?= __('Card Number', 'hola-weddings') ?></label>
                    <?php /* never echo the PAN back into HTML on a validation bounce (PCI) */ ?>
                    <input type="text" class="form-control <?= isset($errors['card-number']) ? 'is-invalid' : '' ?>" id="card-number" placeholder="<?= __('Card Number', 'hola-weddings') ?>" name="payment[card-number]" value="" />
                    <?php if(isset($errors['card-number'])): ?>
                        <div id="card-number-Feedback" class="invalid-feedback">
                           <?= $errors['card-number'] ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row mb-0">
                    <div class="col-8 d-flex flex-column">
                            <label><?= __('Expiration Date', 'hola-weddings') ?></label>
                            <div class="">
                                <div class="d-inline-block form-group">
                                    <input id="card-month" type="number" min="1" max="12" step="1" style="max-width: 90px;" class="form-control <?= isset($errors['card-month']) ? 'is-invalid' : '' ?>" placeholder="<?= __('MM', 'hola-weddings') ?>" name="payment[card-month]" value="<?= isset($data['payment']['card-month']) ? $data['payment']['card-month'] : '' ?>" />
                                    <?php if(isset($errors['expiration'])): ?>
                                        <div id="card-month-Feedback" class="invalid-feedback">
                                           <?= $errors['expiration'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                /
                                <div class="d-inline-block form-group">
                                    <input id="card-year" type="number" min="2022" max="2037" step="1" style="max-width: 110px;" class="form-control <?= isset($errors['card-year']) ? 'is-invalid' : '' ?>" placeholder="<?= __('YYYY', 'hola-weddings') ?>" name="payment[card-year]" value="<?= isset($data['payment']['card-year']) ? $data['payment']['card-year'] : '' ?>" />
                                    <?php if(isset($errors['expiration'])): ?>
                                        <div id="card-year-Feedback" class="invalid-feedback">
                                           <?= $errors['expiration'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                    </div>
                    <div class="col-4">
                        <label for="card-ccv"><?= __('CCV', 'hola-weddings') ?></label>
                        <?php /* never echo the CVV back into HTML (PCI) */ ?>
                        <input id="card-ccv" style="max-width: 100px;" type="number" min="0" class="form-control <?= isset($errors['card-ccv']) ? 'is-invalid' : '' ?>" placeholder="<?= __('CCV', 'hola-weddings') ?>" name="payment[card-ccv]" value="" />
                        <?php if(isset($errors['expiration'])): ?>
                            <div id="card-ccv-Feedback" class="invalid-feedback">
                                <?= $errors['expiration'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                 </div>
            </div>
        </div>
        <!-- 3rd row -->
        <label><?= __('Payment Amount', 'hola-weddings') ?></label>
        <!--<small><?= sprintf(__('Choose the amount you wish to pay in order to confirm your reservation. Minimum deposit amount is %s', 'hola-weddings'),  hw_format_price($deposit)) ?></small>-->

        <div class="row">
            <div class="col-md-12">
                <div class="btn-group d-inline-block" role="group">  
                    <div class="btn-group" role="group">         
                        <input type="radio" class="btn-check" name="deposit[amount]" id="deposit" autocomplete="off" value="deposit" <?= (isset($data['deposit']['amount']) && $data['deposit']['amount'] == 'deposit' ) || empty($data['deposit']['amount']) ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary rounded-left mb-0" for="deposit"><?= sprintf(__('Deposit (<span class="price">%s</span>)', 'hola-weddings'), hw_format_price($deposit)) ?></label>

                        <input type="radio" class="btn-check" name="deposit[amount]" id="full" autocomplete="off" value="full" <?= isset($data['deposit']['amount']) && $data['deposit']['amount'] == 'full' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary mb-0" for="full"><?= __('Full Amount', 'hola-weddings') ?></label>

                        <input type="radio" class="btn-check" name="deposit[amount]" id="custom" autocomplete="off" value="custom" <?= isset($data['deposit']['amount']) && $data['deposit']['amount'] == 'custom' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary mb-0" for="custom"><?= __('Custom Amount', 'hola-weddings') ?></label>
                    </div> 
                    <?php if(isset($errors['deposit-amount'])): ?>
                        <div id="deposit-amount-Feedback" class="invalid-feedback">
                            <?= $errors['deposit-amount'] ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div id="deposit-custom-amount-wrapper" class="ml-3 d-inline-block">
                    <input id="deposit-custom-amount" 
                        style="max-width: 100px;" 
                        type="number" 
                        min="<?= $deposit ?>" 
                        class="form-control d-inline-block 
                        <?= isset($errors['deposit-custom-amount']) ? 'is-invalid' : '' ?>" name="deposit[custom_amount]" 
                        value="<?= isset($data['deposit']['custom_amount']) ? $data['deposit']['custom_amount'] : $deposit ?>"
                        <?= (isset($data['deposit']['amount']) && $data['deposit']['amount'] != 'custom' ) ? 'readonly' : '' ?> />
                    <span><?= hw_get_currency() ?></span>
                    <?php if(isset($errors['deposit-custom-amount'])): ?>
                        <div id="deposit-custom-amount-Feedback" class="invalid-feedback">
                            <?= $errors['deposit-custom-amount'] ?>
                        </div>
                    <?php endif; ?>
                </div>
                <small class="d-block"><?= sprintf(__('A minimum deposit amount of %s plus insurance (if selected) is required', 'hola-weddings'),  hw_format_price($deposit)) ?></small>
            </div>   
        </div>

        <p class="alert alert-warning">
            <?= __('By submitting, you certify that you are the cardholder and are authorizing the travel agency or its chosen Tour Operator/Supplier to charge the listed amount to the credit card. You certify that you have verified that all information contained in the confirmation you received is accurate. You also certify you have read the Terms & Conditions and the appropriate Travel Protection Plan details. Cancellation penalties may apply. Insurance is not refundable.
                <br><br>This charge will be manually applied by the agency to your reservation.  If there are any issues, an agent will get back to you. Please note that you may not see a charge from the travel agency on your credit card statement; the charge will come from our supplier directly. Payment may take 3-5 business days to fully process and be reflected on your statement.
                <br><br>I authorize Hola Weddings to use the above information to charge my credit card the stated amount. Completion of this form and the initialing of this box signifies acceptance in lieu of my signature. By checking the box below and adding your signature, you indicate that the above amount is correct, you authorize the travel agency to charge your credit card for the specified amount, and you agree to the terms and conditions.', 'hola-weddings') ?>
        </p>

        <div class="payment-disclaimer form-check form-group show-if-payment">
            <input class="form-check-input <?= isset($errors['deposit-acceptance']) ? 'is-invalid' : '' ?>" type="checkbox" id="deposit-accept" name="deposit[accept_charge]" value="1" <?= isset($data['deposit']['accept_charge']) && 1 == $data['deposit']['accept_charge'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="deposit-accept">
            <?= __('I authorize Hola Weddings to charge my card for the amount of ', 'hola-weddings') ?>
                <span class="price font-weight-bold"><?= hw_format_price(0) ?></span>
            </label>
        </div>
    </div>
<?php endif; ?>