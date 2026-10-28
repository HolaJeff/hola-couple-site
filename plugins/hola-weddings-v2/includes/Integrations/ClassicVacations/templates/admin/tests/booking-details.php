<?php

use HW\Integrations\ClassicVacations\CRM\Groups\Booking;

?>
<h3>Group Overview</h3>
<div class="p-3 bg-white rounded mt-3">
    <form id="classic-vacation-group" method="get" action="<?= network_admin_url( 'admin.php?page=classic-vacations-tests&tab=booking-details' ) ?>"> 
        <input type="hidden" name="page" value="classic-vacations-tests" />
        <input type="hidden" name="tab" value="booking-details" />
        <div class="row mb-3">
            <div class="form-group col-4">
                <label for="group-id" class="form-label"><?= __('Group ID', 'hola-weddings') ?></label>
                <input type="text" name="group_id" value="<?= isset($_GET['group_id']) ? $_GET['group_id'] : '' ?>" class="form-control d-inline-block" id="group-id" placeholder="<?= __('Insert Group ID', 'hola-weddings') ?>">
            </div>
            <div class="form-group col-4">
                <label for="booking-id" class="form-label"><?= __('Booking ID', 'hola-weddings') ?></label>
                <input type="text" name="booking_id" value="<?= isset($_GET['booking_id']) ? $_GET['booking_id'] : '' ?>" class="form-control d-inline-block" id="booking-id" placeholder="<?= __('Insert Booking ID', 'hola-weddings') ?>">
            </div>
        </div>
        <button class="btn btn-primary"><?= __('Fetch', 'hola-weddings') ?></button>
    </form>
</div>

<?php if(isset($_GET['group_id']) && !empty($_GET['group_id']) && isset($_GET['booking_id']) && !empty($_GET['booking_id'])):

    $groupId = $_GET['group_id'];
    $bookingId = $_GET['booking_id'];
    $response = Booking::getBookingDetails($groupId, $bookingId);
    ?>
    <div class="p-3 bg-white rounded mt-3">
        <h3>Booking Details</h3>
        <?php
        if(hw_cv_is_api_exception($response)){
            echo sprintf('API Exception code %s - %s', $response->getErrorCode(), $response->getErrorDescription());
        }else{
            echo '<pre>';
            var_dump($response);
            echo '</pre>';
        } 
        ?>
    </div>
<?php endif;