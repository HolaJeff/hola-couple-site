<?php
use \HW\Helpers\Countries;

$customer_data = hw_get_quote_customer();
$country = isset($customer_data['country']) ? $customer_data['country'] : '';
$is_US = $country == 'US';
$state = isset($customer_data['state']) ? $customer_data['state'] : '';
?>
<div class="panel">
    <div class="title"><?= __('Customer Information', 'hola-weddings') ?></div>     
    <div class="body">   
        <table style="width: 100%">
            <tbody>
                <tr>
                    <td>
                        <strong><?= __('First Name') ?></strong>
                        <p><?= hw_get_quote_meta('hw_first_name') ?></p>
                    </td>
                    <td>
                        <strong><?= __('Last Name') ?></strong>
                        <p><?= hw_get_quote_meta( 'hw_last_name') ?></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php $email = hw_get_quote_meta('hw_email') ?>
                        <strong><?= __('Email') ?></strong>
                        <p><?= $email ?></p>
                    </td>
                    <td>
                        <?php $phone = hw_get_quote_meta('hw_phone') ?>
                        <strong><?= __('Phone') ?></strong>
                        <p><?= $phone ?></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong><?= __('Address') ?></strong>
                        <p><?= isset($customer_data['address']) ? $customer_data['address'] : '--' ?></p>
                    </td>
                    <td>
                        <strong><?= __('City') ?></strong>
                        <p><?= isset($customer_data['city']) ? $customer_data['city'] : '--' ?></p>
                    </td>
                    <td>
                        <strong><?= __('ZIP code') ?></strong>
                        <p><?= isset($customer_data['zip']) ? $customer_data['zip'] : '--' ?></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong><?= __('State') ?></strong>
                        <p><?=  $is_US ? hw_get_state_name($state) : $state ?></p>
                    </td>
                    <td>
                        <strong><?= __('Country') ?></strong>
                        <p>
                            <?php
                            if($is_US){
                                $countries = Countries::getList(true);
                                echo $countries[$country]['name'];
                            }else{
                                echo $country;
                            }
                            ?>
                        </p>
                    </td>
                    <td>
                        
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>