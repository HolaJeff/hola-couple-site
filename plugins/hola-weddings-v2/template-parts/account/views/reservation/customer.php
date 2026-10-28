<?php
use \HW\Helpers\Countries;

$billing = $quote->getBillingInformation();
$is_US = $billing->getCountry() == 'US';
?>
<table style="width: 100%">
    <tbody>
        <tr>
            <td>
                <strong><?= __('First Name') ?></strong>
                <p><?= $billing->getFirstName(); ?></p>
            </td>
            <td>
                <strong><?= __('Last Name') ?></strong>
                <p><?= $billing->getLastName(); ?></p>
            </td>
        </tr>
        <tr>
            <td>
                <strong><?= __('Email') ?></strong>
                <p><?= $billing->getEmail() ?></p>
            </td>
            <td>
                <strong><?= __('Phone') ?></strong>
                <p><?= $billing->getPhone() ?></p>
            </td>
        </tr>
        <tr>
            <td>
                <strong><?= __('Address') ?></strong>
                <p><?= $billing->getAddress() ?></p>
            </td>
            <td>
                <strong><?= __('City') ?></strong>
                <p><?= $billing->getCity() ?></p>
            </td>
            <td>
                <strong><?= __('ZIP code') ?></strong>
                <p><?= $billing->getPostalCode() ?></p>
            </td>
        </tr>
        <tr>
            <td>
                <strong><?= __('State') ?></strong>
                <p><?= $is_US ? hw_get_state_name($billing->getState()) : $billing->getState() ?></p>
            </td>
            <td>
                <strong><?= __('Country') ?></strong>
                <p>
                    <?php
                    if($is_US){
                        $countries = Countries::getList(true);
                        echo $countries[$billing->getCountry()]['name'];
                    }else{
                        echo $billing->getCountry();
                    }
                    ?>
                </p>
            </td>
            <td>
                
            </td>
        </tr>
    </tbody>
</table>