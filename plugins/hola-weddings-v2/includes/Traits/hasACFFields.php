<?php


namespace HW\Traits;

Trait hasACFFields{

    /**
     * get ACF field
     *
     * @param string $key
     * @return mixed
     */
    public function getACFField($key){

        return get_field($key, $this->getID());
    }

    /**
     * set ACF field
     *
     * @param key $key
     * @param mixed $value
     * @return void
     */
    public function setACFField($key, $value){

        $old_value = $this->getACFField($key);

        update_field($key, $value, $this->getID());

        /**
         * dispatch an event on post meta update
         * 
         * @since 2.1.27
         *
         * @param string $name  Metadata Key.
         * @param string $value  New Value.
         * @param string $old_value  Old Value.
         * @param DecoratorModel $this   Model Instance.
         */
        do_action( "hw_model_metadata_updated", $key, $value, $old_value, $this);

        /**
         * dispatch an event on post meta update for a specific post type
         * 
         * @since 2.1.27
         *
         * @param string $name  Metadata Key.
         * @param string $value  New Value.
         * @param string $old_value  Old Value.
         * @param DecoratorModel $this   Model Instance.
         */
        do_action( "hw_model_{$this->getPostType()}_metadata_updated", $key, $value, $old_value, $this);
    }
}