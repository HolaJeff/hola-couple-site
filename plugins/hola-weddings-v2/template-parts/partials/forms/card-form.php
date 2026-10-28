<!-- 1st row -->
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label><?= __('Card Type', 'hola-weddings') ?></label>
            <br>
            <div class="form-check form-check-inline">
                <input class="form-check-input card-type" type="radio" name="card[type]" id="visa" value="001" checked>
                <label class="form-check-label" for="visa">
                    <img src="<?= HW_ASSETS_URL ?>/img/visa.png" style="height: 32px; width: auto;" />
                </label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input card-type" type="radio" name="card[type]" id="mc" value="002">
                <label class="form-check-label" for="mc">
                    <img src="<?= HW_ASSETS_URL ?>/img/mc.png" style="height: 32px; width: auto;" />
                </label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input card-type" type="radio" name="card[type]" id="amex" value="003">
                <label class="form-check-label" for="amex">
                    <img src="<?= HW_ASSETS_URL ?>/img/amex.png" style="height: 32px; width: auto;" />
                </label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input card-type" type="radio" name="card[type]" id="discover" value="004">
                <label class="form-check-label" for="discover">
                    <img src="<?= HW_ASSETS_URL ?>/img/discover.png" style="height: 32px; width: auto;" />
                </label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input card-type" type="radio" name="card[type]" id="diners" value="005">
                <label class="form-check-label" for="diners">
                    <img src="<?= HW_ASSETS_URL ?>/img/diners-club.png" style="height: 32px; width: auto;" />
                </label>
            </div>
        </div>
    </div>
</div>
<!-- 2nd row -->
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="card-owner"><?= __('Holder Name', 'hola-weddings') ?></label>
            <input id="card-owner" type="text" class="form-control" placeholder="<?= __('Holder Name', 'hola-weddings') ?>" name="card[holder]" />
        </div>
    </div>
</div>
<!-- 3rd row -->
<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-0">
            <label for="card-number"><?= __('Card Number', 'hola-weddings') ?></label>
            <input type="text" class="form-control" id="card-number" placeholder="<?= __('Card Number', 'hola-weddings') ?>" name="card[number]" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group row mb-0">
            <div class="col-8 d-flex flex-column">
                <label><?= __('Expiration Date', 'hola-weddings') ?></label>
                <div class="">
                    <div class="d-inline-block form-group">
                        <input id="card-month" type="number" min="1" max="12" step="1" style="max-width: 90px;" class="form-control" placeholder="<?= __('MM', 'hola-weddings') ?>" name="card[month]" />
                    </div>
                    /
                    <div class="d-inline-block form-group">
                        <input id="card-year" type="number" min="2022" max="2037" step="1" style="max-width: 110px;" class="form-control" placeholder="<?= __('YYYY', 'hola-weddings') ?>" name="card[year]" />
                    </div>
                </div>
            </div>
            <div class="col-4 form-group">
                <label for="card-ccv"><?= __('CCV', 'hola-weddings') ?></label>
                <input id="card-ccv" 
                    style="max-width: 100px;" 
                    type="text" 
                    inputmode="numeric" 
                    pattern="[0-9]*" 
                    maxlength="4" 
                    class="form-control" 
                    placeholder="<?= __('CCV', 'hola-weddings') ?>" 
                    name="card[ccv]"
                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)" />
            </div>
        </div>
    </div>
</div>