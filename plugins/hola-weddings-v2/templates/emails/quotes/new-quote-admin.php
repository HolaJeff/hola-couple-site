<?php
/**
 * New quote - internal admin notification.
 *
 * Rendered without templates/emails/layout.php: this is a complete document on
 * its own. Staff read and forward it, so it stays a plain, dense table rather
 * than the branded customer chrome.
 *
 * Every value here arrives from NewQuoteAdminNotification::getData(), already
 * resolved off the Quote model.
 *
 * @var int    $quote_id
 * @var string $edit_link
 * @var string $wedding_name
 * @var string $mode
 * @var string $mode_label
 * @var string $package_title
 * @var string $status_label
 * @var string $full_name
 * @var string $email
 * @var string $phone
 * @var string $address
 * @var string $city
 * @var string $zip
 * @var string $state
 * @var string $country
 * @var string $checkin
 * @var string $checkout
 * @var int    $nights
 * @var int    $adults
 * @var int    $teen
 * @var bool   $teen_enabled
 * @var int    $children
 * @var int    $infants
 * @var string $hotel_name
 * @var string $room_name
 * @var string $rate_type
 * @var string $room_total
 * @var array  $guests
 * @var bool   $has_insurance
 * @var string $insurance_name
 * @var string $insurance_total
 * @var string $service_fee
 * @var string $total
 * @var string $notes
 * @var array  $deposit
 */

// Row helper. Declared conditionally because the renderer include()s this file
// once per message, and a second render in the same request would redeclare it.
if ( ! function_exists( 'hw_admin_quote_email_row' ) ) {

    function hw_admin_quote_email_row( $label, $value, $raw = false ) {

        printf(
            '<tr><th align="left" valign="top" style="padding:8px 12px;border-bottom:1px solid #e5e5e5;background:#fafafa;width:38%%;font-weight:bold;">%s</th>'
            . '<td valign="top" style="padding:8px 12px;border-bottom:1px solid #e5e5e5;">%s</td></tr>',
            esc_html( $label ),
            $raw ? $value : esc_html( $value === '' || $value === null ? '--' : $value )
        );
    }
}
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>New quote #<?= esc_html( $quote_id ) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#333333;font-size:14px;line-height:1.5;">

        <p>You have received a new quote request for wedding <strong><?= esc_html( $wedding_name ) ?></strong>, here's the information:</p>

        <p>
            <strong>Reservation #<?= esc_html( $quote_id ) ?></strong>
            <?php if ( ! empty( $edit_link ) ): ?>
                &mdash; <a href="<?= esc_url( $edit_link ) ?>">open in the admin</a>
            <?php endif; ?>
        </p>

        <table cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:680px;border-collapse:collapse;border:1px solid #e5e5e5;">
            <tbody>
                <?php
                hw_admin_quote_email_row( 'Mode', $mode_label );
                hw_admin_quote_email_row( 'Status', $status_label );

                if ( $mode == 'package' ) {
                    hw_admin_quote_email_row( 'Package Title', $package_title );
                }

                hw_admin_quote_email_row( 'Full Name', $full_name );
                hw_admin_quote_email_row( 'Email', $email );
                hw_admin_quote_email_row( 'Phone', $phone );
                hw_admin_quote_email_row( 'Address', $address );
                hw_admin_quote_email_row( 'City', $city );
                hw_admin_quote_email_row( 'ZIP Code', $zip );
                hw_admin_quote_email_row( 'State', $state );
                hw_admin_quote_email_row( 'Country', $country );
                hw_admin_quote_email_row( 'Checkin', $checkin );
                hw_admin_quote_email_row( 'Checkout', $checkout );
                hw_admin_quote_email_row( 'Nights', $nights );
                hw_admin_quote_email_row( 'Adults', $adults );

                if ( $teen_enabled ) {
                    hw_admin_quote_email_row( 'Teen', $teen );
                }

                hw_admin_quote_email_row( 'Children', $children );
                hw_admin_quote_email_row( 'Infants', $infants );
                hw_admin_quote_email_row( 'Hotel', $hotel_name );
                hw_admin_quote_email_row( 'Room', $room_name );
                hw_admin_quote_email_row( 'Rate Type', $rate_type );
                hw_admin_quote_email_row( 'Room Total', $room_total );
                ?>

                <tr>
                    <th align="left" valign="top" style="padding:8px 12px;border-bottom:1px solid #e5e5e5;background:#fafafa;width:38%;font-weight:bold;">Guests</th>
                    <td valign="top" style="padding:8px 12px;border-bottom:1px solid #e5e5e5;">
                        <?php if ( empty( $guests ) ): ?>
                            --
                        <?php else: ?>
                            <?php foreach ( $guests as $guest ): ?>
                                <div style="margin:0 0 6px 0;">
                                    <?= esc_html( trim( sprintf( '%s %s %s', $guest['title'] ?? '', $guest['name'] ?? '', $guest['lastname'] ?? '' ) ) ) ?>
                                    &ndash; Date of birth: <?= esc_html( $guest['date'] ?? '--' ) ?>
                                    &ndash; Age: <?= esc_html( $guest['age'] ?? '--' ) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php
                // Always shown, so a missing plan reads as an explicit "No"
                // rather than as a row that quietly went away.
                hw_admin_quote_email_row( 'Insurance', $insurance_name );

                if ( $has_insurance ) {
                    hw_admin_quote_email_row( 'Insurance Total', $insurance_total );
                }

                hw_admin_quote_email_row( 'Service Fee', $service_fee );
                hw_admin_quote_email_row( 'Total', $total );

                if ( ! empty( $notes ) ) {
                    hw_admin_quote_email_row( 'Additional Notes', $notes );
                }

                if ( ! empty( $deposit ) ) {

                    hw_admin_quote_email_row( 'Deposit', hw_get_deposit_label( $deposit['amount'] ?? '' ) );

                    if ( ! empty( $deposit['custom_amount'] ) ) {
                        hw_admin_quote_email_row( 'Custom Amount', hw_format_price( $deposit['custom_amount'] ) );
                    }

                    if ( ! empty( $deposit['accepted_on'] ) ) {
                        hw_admin_quote_email_row( 'Charge accepted on', date( 'm/d/Y H:i:s', $deposit['accepted_on'] ) );
                    }

                    hw_admin_quote_email_row( 'IP Address', $deposit['ip_address'] ?? '' );
                }
                ?>
            </tbody>
        </table>

    </body>
</html>
