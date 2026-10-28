<div class="col-lg-4 offset-lg-4 py-5">
    <div class="bg-white p-3">
        <h4 class="fw-bold main-color mb-4 text-center">
            <?= __('View Invoice', 'hola-weddings') ?>
        </h4>
        <p>
            <?= __('In order to access the invoice insert the email used during the reservation.', 'hola-weddings') ?>
        </p>
        <form method="POST">
            <div class="form-group mb-3">
                <input data-email type="email" class="form-control" id="emailInput" placeholder="<?= __('Insert Reservation E-mail', 'hola-weddings') ?>" name="email">
            </div>
            <input type="hidden" name="action" value="hw_pos_invoice_verify_email" />
            <button class="btn btn-primary" type="submit"><?= __('Access Invoice', 'hola-weddings') ?></button>
        </form>
    </div>
</div>