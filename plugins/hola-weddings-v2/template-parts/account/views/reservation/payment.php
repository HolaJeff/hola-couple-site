<?php 
$paid_amount = $quote->getPaidAmount();
$quote_total = hw_get_quote_total();
?>
<div class="panel px-3">
    <div class="title mb-3"><?= __('Summary', 'hola-weddings') ?></div>     
    <div class="body">
        <table id="payment-table" class="table" style="width: 100%">
            <tbody>
                <tr>
                    <td>
                        <?= __('Room Total', 'hola-weddings' ) ?>
                    </td>
                    <td>
                        <?= hw_format_price( hw_get_quote_room_total() ) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= __('Insurance Total', 'hola-weddings' ) ?>
                    </td>
                    <td>
                        <?= hw_format_price( hw_get_quote_insurance_total() ) ?>
                    </td>
                </tr>
                <?php if($transfer = hw_get_quote_transfer_fee()): ?>
                    <tr>
                        <td>
                            <?= __('Transfer Fee', 'hola-weddings' ) ?>
                        </td>
                        <td>
                            <?= hw_format_price( $transfer ) ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr class="font-weight-bold">
                    <td>
                        <?= __('Total', 'hola-weddings' ) ?>
                    </td>
                    <td>
                        <?= hw_format_price( $quote_total ) ?>
                    </td>
                </tr>
                <?php if($deposit = hw_get_quote_deposit()): ?>
                    <tr>
                        <td>
                            <?= __('Paid Amount', 'hola-weddings' ) ?>
                        </td>
                        <td>
                            <?= hw_format_price( $paid_amount ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?= __('Due Amount', 'hola-weddings' ) ?>
                        </td>
                        <td>
                            <?= hw_format_price( $quote_total - $paid_amount ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>