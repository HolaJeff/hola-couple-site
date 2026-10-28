<?php

use HW\Helpers\Countries;
use HW\Models\CRM\BillingInformation;

/** @var BillingInformation|null $billing_information */
/** @var int                     $quote_id */
/** @var string                  $nonce */

$initial_fields = $billing_information ? [
    'first_name'   => $billing_information->getFirstName(),
    'last_name'    => $billing_information->getLastName(),
    'email'        => $billing_information->getEmail(),
    'phone_number' => $billing_information->getPhone(),
    'address'      => $billing_information->getAddress(),
    'city'         => $billing_information->getCity(),
    'zip'          => $billing_information->getPostalCode(),
    'country'      => $billing_information->getCountry(),
    'state'        => $billing_information->getState(),
] : [];
?>

<script>
function hwCustomerForm() {
    return {
        editing:        false,
        saving:         false,
        message:        null,
        fields:         {},
        originalFields: {},
        nonce:          '',
        quoteId:        '',

        init() {
            this.nonce   = this.$el.dataset.nonce;
            this.quoteId = this.$el.dataset.quote;

            const data = JSON.parse( this.$el.dataset.fields || '{}' );
            this.fields         = { ...data };
            this.originalFields = { ...data };
        },

        async save() {
            this.saving  = true;
            this.message = null;

            const body = new FormData();
            body.append( 'action',   'hw_update_quote_customer' );
            body.append( 'nonce',    this.nonce );
            body.append( 'quote_id', this.quoteId );

            Object.entries( this.fields ).forEach( ( [ key, val ] ) => {
                body.append( `data[billing][${ key }]`, val ?? '' );
            } );

            try {
                const res  = await fetch( ajaxurl, { method: 'POST', body } );
                const json = await res.json();

                if ( json.success ) {
                    this.editing        = false;
                    this.originalFields = { ...this.fields };
                    this.message = { type: 'success', text: json.data?.message || '<?= esc_js( __( 'Customer updated.', 'hola-weddings' ) ) ?>' };
                } else {
                    this.message = { type: 'danger', text: json.data?.message || '<?= esc_js( __( 'Could not save.', 'hola-weddings' ) ) ?>' };
                }
            } catch {
                this.message = { type: 'danger', text: '<?= esc_js( __( 'Network error.', 'hola-weddings' ) ) ?>' };
            } finally {
                this.saving = false;
            }
        },

        cancelEdit() {
            this.fields  = { ...this.originalFields };
            this.editing = false;
            this.message = null;
        },
    }
}
</script>

<div x-data="hwCustomerForm()"
     x-init="init()"
     data-quote="<?= intval( $quote_id ) ?>"
     data-nonce="<?= esc_attr( $nonce ) ?>"
     data-fields="<?= esc_attr( json_encode( $initial_fields ) ) ?>">

    <!-- message alert -->
    <template x-if="message">
        <div class="flex-grow-1">
            <div :class="'alert alert-' + message.type + ' py-1 px-2 mb-0 small'" x-text="message.text"></div>
        </div>
    </template>

    <!-- action bar -->
    <div class="d-flex flex-wrap gap-2 mb-1 mt-3 align-items-center">

        <div class="d-flex gap-2 align-items-center ms-auto">
            <button x-show="!editing"
                    @click.prevent="editing = true; message = null"
                    :disabled="Object.keys(originalFields).length === 0"
                    class="btn btn-sm btn-outline-secondary">
                <i class="far fa-edit me-1"></i>
                <?= __( 'Edit', 'hola-weddings' ) ?>
            </button>

            <button x-show="editing"
                    @click.prevent="cancelEdit()"
                    class="btn btn-sm btn-outline-danger">
                <i class="fas fa-times"></i>
                <?= __( 'Cancel', 'hola-weddings' ) ?>
            </button>

            <button x-show="editing"
                    @click.prevent="save()"
                    :disabled="saving"
                    class="btn btn-sm btn-success">
                <i class="far fa-save"></i>
                <span x-show="saving"><?= __( 'Saving…', 'hola-weddings' ) ?></span>
                <span x-show="!saving"><?= __( 'Save', 'hola-weddings' ) ?></span>
            </button>
        </div>
    </div>

    <p x-show="Object.keys(originalFields).length === 0" class="description mt-2">
        <?= __( 'No customer linked to this quote yet.', 'hola-weddings' ) ?>
    </p>

    <!-- fields -->
    <div class="row fields" x-show="Object.keys(originalFields).length > 0">
        <div class="col">
            <h3><?= __( 'Details' ) ?></h3>
            <div class="field">
                <div class="label"><?= __( 'First Name' ) ?></div>
                <div class="value">
                    <input class="form-control" type="text"
                           name="data[billing][first_name]"
                           x-model="fields.first_name"
                           :disabled="!editing">
                </div>
            </div>
            <div class="field">
                <div class="label"><?= __( 'Last Name' ) ?></div>
                <div class="value">
                    <input class="form-control" type="text"
                           name="data[billing][last_name]"
                           x-model="fields.last_name"
                           :disabled="!editing">
                </div>
            </div>
            <div class="field">
                <div class="label"><?= __( 'Email' ) ?></div>
                <div class="value">
                    <div class="input-group">
                        <input class="form-control" type="text"
                               name="data[billing][email]"
                               x-model="fields.email"
                               :disabled="!editing">
                        <a :href="'mailto:' + fields.email" class="btn btn-outline-primary">
                            <?= __( 'Send Email', 'hola-weddings' ) ?>
                        </a>
                    </div>
                </div>
            </div>
            <div class="field">
                <div class="label"><?= __( 'Phone' ) ?></div>
                <div class="value">
                    <input class="form-control" type="text"
                           name="data[billing][phone_number]"
                           x-model="fields.phone_number"
                           :disabled="!editing">
                </div>
            </div>
        </div>

        <div class="col">
            <h3><?= __( 'Billing Information' ) ?></h3>
            <div class="field">
                <div class="label"><?= __( 'Address' ) ?></div>
                <div class="value">
                    <input class="form-control" type="text"
                           name="data[billing][address]"
                           x-model="fields.address"
                           :disabled="!editing">
                </div>
            </div>
            <div class="field">
                <div class="label"><?= __( 'City' ) ?></div>
                <div class="value">
                    <input class="form-control" type="text"
                           name="data[billing][city]"
                           x-model="fields.city"
                           :disabled="!editing">
                </div>
            </div>
            <div class="field">
                <div class="label"><?= __( 'Postal Code' ) ?></div>
                <div class="value">
                    <input class="form-control" type="text"
                           name="data[billing][zip]"
                           x-model="fields.zip"
                           :disabled="!editing">
                </div>
            </div>
            <div class="field">
                <div class="label"><?= __( 'Country' ) ?></div>
                <div class="value">
                    <select class="form-select"
                            name="data[billing][country]"
                            x-model="fields.country"
                            @change="fields.state = ''"
                            :disabled="!editing">
                        <optgroup label="<?= esc_attr( __( 'Most Used', 'hola-weddings' ) ) ?>">
                            <option value="US"><?= __( 'United States', 'hola-weddings' ) ?></option>
                        </optgroup>
                        <optgroup label="<?= esc_attr( __( 'All Countries', 'hola-weddings' ) ) ?>">
                            <?php foreach ( Countries::getList() as $country ) : ?>
                                <?php if ( $country['code'] === 'US' ) continue; ?>
                                <option value="<?= esc_attr( $country['code'] ) ?>">
                                    <?= esc_html( $country['name'] ) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
            </div>
            <div class="field">
                <div class="label"><?= __( 'State/Province' ) ?></div>
                <div class="value form-group">
                    <select class="us-states form-select"
                            name="data[billing][state]"
                            x-model="fields.state"
                            x-show="fields.country === 'US'"
                            :disabled="!editing">
                        <option value=""><?= __( '- Select State -' ) ?></option>
                        <?php foreach ( Countries::getUSStates() as $code => $name ) : ?>
                            <option value="<?= esc_attr( $code ) ?>"><?= esc_html( $name ) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text"
                           class="other-state form-control"
                           name="data[billing][state]"
                           placeholder="<?= esc_attr( __( 'State / Province', 'hola-weddings' ) ) ?>"
                           x-model="fields.state"
                           x-show="fields.country !== 'US'"
                           autocomplete="off"
                           :disabled="!editing">
                </div>
            </div>
        </div>
    </div>

    <!-- /fields -->
</div>
