<?php
use HW\API\Operations\Insurance;

$him = hw_get_option('hw_his_name', '' );
$her = hw_get_option('hw_her_name', '' );
$customer = hw_get_quote_customer($id);
$guests = hw_get_quote_guests($id);
$ci = hw_get_quote_check_in($id);
$co = hw_get_quote_check_out($id);
$room = hw_get_room(hw_get_quote_room_id($id));
$insurance_plan_id = hw_get_quote_insurance_plan_id($id);
$terms = apply_filters( 'the_content', hw_get_terms()) ;
$cancellation = apply_filters( 'the_content', hw_get_cancellation_policy()) ;
?>
<style>
.uppercase{
    text-transform: uppercase;
}
.box{
    border-radius: 4px;
    padding: 10px 20px;
    border: 1px solid #ddd;
}
table{
    width: 100%;
}
table .label{
    font-weight: bold;
}
table td{
    padding: 10px 0;
}
.lined-table td{
    border-bottom: 1px solid #eee;
}
.lined-table tr:last-child td{
    border-bottom: 0;
}
.lined-table td:last-child{
    text-align: right;
}
.transfer-row td{
    border-bottom: 0;
}
#payment-table td:last-child{
    width: 30%;
}
.align-right{
    text-align: right;
}
p{
    margin-top: 0;
}
</style>
<page>
    <div style="text-align: center; margin-bottom: 30px">
        <img src="<?= HW_PLUGIN_PATH ?>/assets/img/logo.png" style="height: 70px; width: auto;" />
    </div>
    <div>
        <h1 style="margin-bottom: 10px;"><?= sprintf(__('%s and %s Wedding UCC Form', 'hola-weddings'), $him, $her); ?></h1>
        <p><?= sprintf(__('Reservation: <strong>%s %s</strong> for 1 Room', 'hola-weddings'), $customer['first_name'], $customer['last_name']); ?></p>
    </div>
    <h6 class="uppercase"><?= __('Reservation Details', 'hola-weddings') ?></h6>
    <table class="box" style="width: 100%;">
        <tr>
            <td style="width: 50%;">
                <div class="label" style="font-weight: bold;"><?= __('Check In', 'hola-weddings') ?></div>
                <?= (new DateTime($ci))->format('l F d, Y') ?>
            </td>
            <td style="width: 50%;">
                <div class="label"><?= __('Check Out', 'hola-weddings') ?></div>
                <?= (new DateTime($co))->format('l F d, Y') ?>
            </td>
        </tr>
        <tr>
            <td style="width: 50%;">
                <div class="label" style="font-weight: bold;"><?= __('Room Type', 'hola-weddings') ?></div>
                <?= $room['name'] ?>
            </td>
        </tr>
    </table>
    <h6 class="uppercase"><?= __('Guests', 'hola-weddings') ?></h6>
    <table class="guest-table box">
        <?php foreach($guests as $i => $guest): ?>
            <tr>
                <td style="width: 10%;">#<?= $i ?></td>
                <td style="width: 40%;"><?= ucwords($guest['title'] . '  '. hw_guest_full_name($guest)) ?></td>
                <td style="width: 40%;"><?= sprintf(__('Date of birth: %s'), (new DateTime($guest['date']))->format('m/d/Y')) ?></td>
                <td style="width: 10%;"><?= sprintf(__('Age: %s'), $guest['age'] ) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h6 class="uppercase"><?= __('Billing Information', 'hola-weddings') ?></h6>
    <table class="lined-table box">
        <tr>
            <td style="width: 100%;">
                <div class="label"><?= __('Full Name', 'hola-weddings'); ?></div>
                <?= sprintf('%s %s', $customer['first_name'], $customer['last_name']) ?>
            </td>
        </tr>
        <tr>
            <td style="width: 100%;">
                <div class="label"><?= __('Email Address', 'hola-weddings'); ?></div>
                <?= $customer['email'] ?>
            </td>
        </tr>
        <tr>
            <td style="width: 100%;">
                <div class="label"><?= __('Phone Number', 'hola-weddings'); ?></div>
                <?= $customer['phone_number']; ?>
            </td>
        </tr>
        <tr>
            <td style="border-bottom: 0">
                <div class="label"><?= __('Address', 'hola-weddings'); ?></div>
                <?= hw_format_address($customer) ?>
            </td>
        </tr>
    </table>
    <h6 class="uppercase"><?= __('Insurance', 'hola-weddings') ?></h6>
    <?php if($insurance_plan_id): 

        $plan = Insurance::getPlan($insurance_plan_id);
        ?>
        <table class="box">
            <tr>
                <td style="width: 50%;">
                    <strong><?= __('Insurance Plan', 'hola-weddings'); ?></strong>
                    <p><?= $plan['name'] ?></p>
                </td>
                <td style="width: 50%;">
                    <strong><?= __('Insurance Total', 'hola-weddings'); ?></strong>
                    <p><?= hw_format_price( hw_get_quote_insurance_total($id) ); ?></p>
                </td>
            </tr>
        </table>
    <?php else: ?>
        <p><?= __('Insurance Declined', 'hola-weddings') ?></p>
    <?php endif; ?>
</page>
<?php if(is_payment_enabled() && hw_get_quote_status() == 'confirmed'): 
    $card = hw_get_card_information($id);
    ?>
    <page>
        <h3><?= __('Payment Information', 'hola-weddings') ?></h3>
        <h6 class="uppercase"><?= __('Balance', 'hola-weddings') ?></h6>
        <table id="payment-table" class="box lined-table" style="width: 100%">
            <tbody>
                <tr>
                    <td style="width:70%">
                        <?= __('Room Total', 'hola-weddings' ) ?>
                    </td>
                    <td class="align-right" style="width:30%;">
                        <?= hw_format_price( hw_get_quote_room_total($id) ) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= __('Insurance Total', 'hola-weddings' ) ?>
                    </td>
                    <td class="align-right">
                        <?= hw_format_price( hw_get_quote_insurance_total($id) ) ?>
                    </td>
                </tr>
                <?php if($transfer = hw_get_quote_transfer_fee($id)): ?>
                    <tr class="transfer-row">
                        <td>
                            <?= __('Transfer Fee', 'hola-weddings' ) ?>
                        </td>
                        <td class="align-right">
                            <?= hw_format_price( $transfer ) ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr style="font-weight:bold; background-color: #eee;">
                    <td>
                        <?= __('Total', 'hola-weddings' ) ?>
                    </td>
                    <td class="align-right">
                        <?= hw_format_price( hw_get_quote_total($id) ) ?>
                    </td>
                </tr>
                <?php if($deposit = hw_get_quote_deposit($id)): ?>
                    <tr>
                        <td>
                            <?= __('Deposit', 'hola-weddings' ) ?>
                        </td>
                        <td class="align-right">
                            <?= hw_format_price($deposit['custom_amount']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-bottom: 0;">
                            <?= __('Due Amount', 'hola-weddings' ) ?>
                        </td>
                        <td class="align-right" style="border-bottom: 0;">
                            <?= hw_format_price(hw_get_quote_total($id) - $deposit['custom_amount']); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <h6 class="uppercase"><?= __('Card Information', 'hola-weddings') ?></h6>
        <table id="card-table" class="box">
            <tr>
                <td style="width: 60%;">
                    <strong><?= hw_get_card_type_label($card['card-type']) ?></strong>
                    <br>
                    <?= sprintf('XXXX-XXXX-XXXX-%s', substr($card['card-number'], 12, 4)) ?>
                </td>
                <td style="width: 40%;">
                    <strong><?= __('Card Holder Name', 'hola-weddings'); ?></strong>
                    <br>
                    <?= $card['card-owner'] ?>
                </td>
            </tr>
        </table>
        <p>
            <?= 
            sprintf(
                __('I authorize the %s ending in %s to be charged up to %s for the purposes of: Final Payment Due for 1 room.', 'hola-weddings'),
                sprintf('XXXX-XXXX-XXXX-%s', substr($card['card-number'], 12, 4)),
                hw_get_card_type_label($card['card-type']),
                hw_format_price( hw_get_quote_total($id) ),
            );
            ?>
        </p>
    </page>
<?php endif; ?>
<?php if(!empty($terms) || !empty($cancellation)): ?>
    <page>
        <?php if(!empty($terms)): ?>
            <h6 class="uppercase"><?= __('Terms and conditions', 'hola-weddings') ?></h6>
            <p><?= $terms ?></p>
        <?php endif; ?>

        <?php if(!empty($cancellation)): ?>
            <h6 class="uppercase"><?= __('Cancellation Policy', 'hola-weddings') ?></h6>
            <p><?= $cancellation ?></p>
        <?php endif; ?>
    </page>
<?php endif; ?>