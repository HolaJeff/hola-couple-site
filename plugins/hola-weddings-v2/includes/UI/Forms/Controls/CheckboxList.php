<?php

namespace HW\UI\Forms\Controls;

class CheckboxList extends FormControl{

    protected $options;

    protected $view_more = false;

    public function __construct(string $name)
    {
        parent::__construct( $name );

        $this->name .= '[]';
    }

    /**
     * return the type
     *
     * @return string
     */
    protected function getType()
    {
        return 'checkbox-list';
    }

    /**
     * set options
     *
     * @param array $options
     * @return static::class
     */
    public function options(array $options){

        $this->options = $options;

        return $this;
    }

    /**
     * setter for view more variable
     *
     * @param boolean $value
     * @return static::class
     */
    public function viewMore( bool $value = true){

        $this->view_more = $value;

        return $this;
    }

    /**
     * render the control
     *
     * @return void
     */
    protected function renderControl(){
        
        $read_more = $this->view_more && count($this->options) > 5 ? true : false;

        foreach($this->options as $i => $item): ?>

            <?php if($read_more && $i == 5): ?>
                <div class="read-more">
            <?php endif; ?>

            <div class="form-check">
                <input class="form-check-input" name="<?= $this->name ?>" type="checkbox" value="<?= $item['key'] ?>" id="servicesFilter-<?= $item['key'] ?>">
                <label class="form-check-label" for="servicesFilter-<?= $item['key'] ?>">
                    <?= $item['value'] ?>
                </label>
            </div>

            <?php if($read_more && ( $i + 1 ) == count($this->options)): ?>
                </div>
                <button type="button" class="btn btn-link ps-0 show toggle-read-more">
                    <small>
                        <i class="me-1" data-lucide="plus"></i>
                        <?= __('Ver todos', 'ea') ?>
                    </small>
                </button>
                <button type="button" class="btn btn-link ps-0 hide toggle-read-more">
                    <small>
                        <i class="me-1" data-lucide="minus"></i>
                        <?= __('Ver menos', 'ea') ?>
                    </small>
                </button>
            <?php endif; ?>
        <?php endforeach;
    }
}