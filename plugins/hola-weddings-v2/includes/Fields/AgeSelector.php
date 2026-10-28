<?php

namespace HW\Fields;

class AgeSelector extends AbstractField{

    /**
     * Constructor
     *
     * @param string $name
     * @param mixed $value
     * @param array $args
     */
    public function __construct(string $name, $value = null, $args = []){
        
        $args['name'] = $name;
        $args['value'] = $value;

        parent::__construct($args);
    }
    
    /**
     * Render the field
     *
     * @return void
     */
    public function render(){

        $months = hw_get_months_list();
        ?>
        <div class="age-selector">
            <span>
                <input class="day form-control" type="number" maxlength="2" name="<?= $this->name ?>[d]" placeholder="DD" value="<?= !empty($this->value['d']) ? $this->value['d'] : ''?>" />
                <small>Day</small>
            </span>
            <span>
                <select class="month form-control" name="<?= $this->name ?>[m]">
                    <?php foreach($months as $i => $label): ?>
                        <option value="<?=  sprintf("%02d", $i) ?>" <?= !empty($this->value['m']) && sprintf("%02d", $i) == $this->value['m'] ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <small>Month</small>
            </span>
            <span>
                <input class="year form-control" type="number" maxlength="4" min="<?= date('Y') - 100 ?>" max="<?= date('Y') ?>" name="<?= $this->name ?>[y]" placeholder="YYYY" value="<?= !empty($this->value['y']) ? $this->value['y'] : ''?>" />
                <small>Year</small>
            </span>
        </div>
        <?php
    }

    /**
     * Format input from age selector
     *
     * @param array $data
     * @return string
     */
    public static function format_data_to_date($data){

        return sprintf('%s-%s-%s', $data['y'], $data['m'], $data['d']);
    }
}