<?php

use HW\Fields\AgeSelector;
use HW\Booking\Objects\PersonType;

$age_labels = hw_get_age_labels();
$hotel = (object) hw_get_hotel();
$guests_configuration = [
    'adult' => [
        'count' => $idata['adults'],
        'config' => PersonType::make('adult')
    ],
    'teen' => [
        'count' => $idata['teens'],
        'config' => PersonType::make('teen')
    ],
    'child' => [
        'count' => $idata['children'],
        'config' => PersonType::make('child')
    ],
    'infant' => [
        'count' => $idata['infants'],
        'config' => PersonType::make('infant')
    ],
];

$j = 1;

foreach($guests_configuration as $guest_type => $guest_config):
    
    $person_type = $guest_config['config'];
    $label = $person_type->getLabel();
    $count = $guest_config['count'];

    for( $i=1; $i <= $count; $i++ ): 
        
        $guest_info = $data['guests'][$j] ?? [];
        $min_age = $person_type->getMinAge();
        $max_age = $person_type->getMaxAge();

        // first guest must always be 18
        if($j == 1){
            $min_age = 18;
        }

        $min_age_message = sprintf($person_type->getMessage('min'), $min_age);
        $max_age_message = sprintf($person_type->getMessage('max'), $max_age);
        ?>
        <div class="guest guest-<?= $j ?> mb-3 border-bottom pb-3 px-3" <?= $min_age ? sprintf('data-min="%s"',$min_age) : '' ?> <?= $max_age ? sprintf('data-max="%s"',$max_age) : '' ?> <?= $min_age && !empty($min_age_message) ? "data-min-age-message=\"{$min_age_message}\"" : "" ?> <?= $max_age && !empty($max_age_message) ? "data-max-age-message=\"{$max_age_message}\"" : "" ?>>
            <!-- this is a work around  -->
            <input type="hidden" name="guests[<?= $j ?>][person_type]" value="<?= $guest_type ?>" />
            <div class="font-weight-bold">
                <?= sprintf(__('%s #%s', 'hola-weddings'), $label, $i ) ?>
                <?php if($min_age || $max_age): ?>
                    - 
                    <small class="age-label font-weight-normal">
                        <?php if($min_age && !$max_age): ?>
                            <?= sprintf(__('( min. %d yrs old )'),$min_age) ?>
                        <?php else: ?>
                            <?= sprintf(__('( %d - %d yrs old )'),$min_age, $max_age) ?>
                        <?php endif; ?>
                    </small>
                <?php endif; ?>
            </div>
             <div class="form-group mb-0 row">
                <div class="col-12 form-group mb-0">
                    <small class="form-text text-muted">
                        <?= __('Title', 'hola-weddings') ?> 
                    </small>
                    <div class="btn-group" role="group" aria-label="Guest <?= $j ?> title">
                        <!-- Mr -->
                        <input type="radio" value="mr" class="btn-check" name="guests[<?= $j ?>][title]" id="guest-<?= $j ?>-title-mr" autocomplete="off" <?= !isset($guest_info['title']) || isset($guest_info['title']) && $guest_info['title'] == 'mr' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary ml-0 rounded-left" for="guest-<?= $j ?>-title-mr">
                            <?= __('Mr', 'hola-weddings') ?>
                        </label>
                        <!-- Mrs -->
                        <input type="radio" value="mrs" class="btn-check" name="guests[<?= $j ?>][title]" id="guest-<?= $j ?>-title-mrs" autocomplete="off" <?= isset($guest_info['title']) && $guest_info['title'] == 'mrs' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary ml-0" for="guest-<?= $j ?>-title-mrs">
                            <?= __('Mrs', 'hola-weddings') ?>
                        </label>
                        <!-- Miss -->
                        <input type="radio" value="miss" class="btn-check" name="guests[<?= $j ?>][title]" id="guest-<?= $j ?>-title-miss" autocomplete="off" <?= isset($guest_info['title']) && $guest_info['title'] == 'miss' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary ml-0" for="guest-<?= $j ?>-title-miss">
                            <?= __('Miss', 'hola-weddings') ?>
                        </label>
                        <!-- Mstr -->
                        <input type="radio" value="mstr" class="btn-check" name="guests[<?= $j ?>][title]" id="guest-<?= $j ?>-title-mstr" autocomplete="off" <?= isset($guest_info['title']) && $guest_info['title'] == 'mstr' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary ml-0" for="guest-<?= $j ?>-title-mstr">
                            <?= __('Mstr', 'hola-weddings') ?>
                        </label>
                    </div>
                </div>
             </div>
            <div class="form-group mb-0 row">
                <div class="col-md-6 form-group mb-0">
                    <div class="d-flex flex-nowrap">
                        <div class="mr-2">
                            <small class="form-text text-muted">
                                <?= __('First Name', 'hola-weddings') ?> 
                            </small>
                            <input id="guest-name-<?= $j ?>" class="form-control required <?= isset($errors['guest-name-' . $j]) ? 'is-invalid' : '' ?>" type="text" name="guests[<?= $j ?>][name]" placeholder="<?= __('First Name', 'hola-weddings') ?>" value="<?= isset($guest_info['name']) ? $guest_info['name'] : '' ?>" />
                        </div>
                        <div>
                            <small class="form-text text-muted">
                                <?= __('Last Name', 'hola-weddings') ?> 
                            </small>
                            <input id="guest-lastname-<?= $j ?>" class="form-control required <?= isset($errors['guest-name-' . $j]) ? 'is-invalid' : '' ?>" type="text" name="guests[<?= $j ?>][lastname]" placeholder="<?= __('Last Name', 'hola-weddings') ?>" value="<?= isset($guest_info['lastname']) ? $guest_info['lastname'] : '' ?>" />
                        </div>
                    </div>
                    <small><?= __('Please enter your name as it appears on the passport.', 'hola-weddings') ?></small>
                    <?php if(isset($errors['guest-name-' . $j])): ?>
                        <div id="guest-name-<?= $j ?>-Feedback" class="invalid-feedback">
                        <?= $errors['guest-name-' . $j] ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-5 form-group pl-md-0 mb-0">
                    <small class="form-text text-muted">
                        <?= __('Date of birth', 'hola-weddings') ?> 
                    </small>           
                    <?php
                    
                    $value = isset($guest_info['date']) ? $guest_info['date'] : [];
                    $field = new AgeSelector("guests[{$j}][date]", $value );
                    $field->render();
                    ?>

                    <?php if(isset($errors['guest-date-' . $j])): ?>
                        <div id="guest-date-<?= $j ?>-Feedback" class="invalid-feedback">
                        <?= $errors['guest-date-' . $j] ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-1 form-group pl-md-0 mb-0">

                    <small class="form-text text-muted">
                        <?= __('Age', 'hola-weddings') ?> 
                    </small>
                    
                    <input disabled id="guest-age-<?= $j ?>" class="guest-age form-control required pr-0" type="number" name="guests[<?= $j ?>][age]" />
                </div>
            </div>
        </div>
    <?php 
    $j++;
    endfor;
endforeach;