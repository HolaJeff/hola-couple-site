<h1 class="mb-5">Classic Vacations Point of Sale</h1>
<div id="cv-pos" class="row">
    <div class="col-8">
        <form id="cv-pos-form">
            <input type="hidden" name="action" value="hw_cv_pos_process_payment" />
            
            <?= $this->get_template('admin/POS/invoice-search') ?>
            
            <div id="invoice-checkout" >

                <div class="ajax-container"></div>

                <!-- card options -->
                <?= $this->get_template('admin/POS/checkout/card') ?>

                <!-- billing options -->
                <?= $this->get_template('admin/POS/checkout/billing') ?>

                <!-- submit -->
                <button id="submit" class="btn btn-primary mt-3" type="button"><?= __('Make Payment', 'hola-weddings') ?></button>
            </div>
        </form>
    </div>
    <div id="sidebar" class="col-3">
        <?php //$this->get_template('admin/POS/sidebar') ?>
    </div>
</div>
<style>
    .form-check .form-check-input{
        float: none;
    }
    input[type=checkbox]:checked::before,
    input[type=radio]:checked::before{
        content: none;
    }
</style>