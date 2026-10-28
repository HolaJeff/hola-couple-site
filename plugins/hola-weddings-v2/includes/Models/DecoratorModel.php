<?php

namespace HW\Models;

use HW\Objects\HWObject;
use RuntimeException;

abstract class DecoratorModel extends HWObject{

    public static $post_type = null;

    protected $post;

    /**
     * constructor
     *
     * @param int|\WP_Post $post
     */
    public function __construct( $post = null ){

        if(static::$post_type == null){

            throw new RuntimeException( sprintf('Property post_type not defined for class %s', static::class ));
        }
        
        // set post object
        if($post){
            $this->setPost($post);
        }
    }

    /**
     * Magic method to call model attribute or WP_Post attribute
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name){
        
        if(isset($this->{$name})){

            return $this->{$name};
        }
        elseif($this->post){

            return $this->post->{$name};
        }
    }

    /**
     * Magic method to set model attribute or WP_Post attribute
     *
     * @param string $name
     * @return mixed
     */
    public function __set($name, $value){
        
        if(isset($this->{$name})){

            $this->{$name} = $value;
        }
        elseif($this->post){

            $old_value = $this->get($name);

            update_post_meta( $this->post->ID, $name, $value );

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
            do_action( "hw_model_metadata_updated", $name, $value, $old_value, $this);

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
            do_action( "hw_model_{$this->getPostType()}_metadata_updated", $name, $value, $old_value, $this);
        }
    }

    /**
     * Magic method to call WP_Post methods
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, $args) {
        
        if (is_callable($this->$name)) {
            return call_user_func($this->$name, $args);
        }
        elseif($this->post && is_callable($this->post->$name)) {
            return call_user_func($this->post->$name, $args);
        }

        throw new \BadMethodCallException( "Method {$name} doesn't exists for class " . static::class );
    }

    /**
     * return the ID
     *
     * @return int|null
     */
    public function getID(){
        return $this->ID;
    }

    /**
     * return the post title
     *
     * @return string
     */
    public function getTitle(){
        return $this->post_title;
    }

    /**
     * return the author ID
     *
     * @return int
     */
    public function getAuthorID(){
        return $this->post_author;
    }

    /**
     * return post creation date
     *
     * @param string $format
     * @return string
     */
    public function getCreationDate( string $format = '') :string{
        return get_the_date( $format, $this->post );
    }

    /**
     * check if post is exists
     *
     * @return bool
     */
    public function exists(){
        return $this->post instanceof \WP_Post && !empty($this->post->ID); 
    }

    /**
     * get post edit link
     *
     * @param string $context
     * @return string
     */
    public function getEditLink($context = 'display'){
        return get_edit_post_link($this->post, $context);
    }

    /**
     * cast object to array
     *
     * @return array
     */
    public function toArray(){

        return (array)$this;
    }

    /**
     * return the post type
     *
     * @return string
     */
    public function getPostType(){

        return static::$post_type;
    }

    /**
     * set the post
     *
     * @param WP_Post|int $post
     * @return void
     */
    public function setPost($post){

        // set post object
        if( !$post instanceof \WP_Post){

            $post_id = absint($post);
            if(!empty($post_id)){

                $post = get_post($post_id);
                // post doesn't exist
                if(!$post instanceof \WP_Post){
                    return;
                }
            }
        }
        
        // the post is not of the model type
        if($post->post_type != $this->getPostType()){
            return;
        }
        
        // the post exists and is of the model type
        $this->post = $post;
    }

    /**
     * create insert a post and return the instance of decorator class
     *
     * @param string $title
     * @param array $meta
     * @param boolean $draft
     * @return static|\WP_Error
     */
    public static function create(string $title, array $meta = [], array $args = []){

        $post_type = static::$post_type;

        $post_data = wp_parse_args( $args, [
            'post_type' => $post_type,
            'post_title' => $title,
            'post_status' => 'publish',
            'meta_input' => $meta
        ]);

        $post_id = wp_insert_post($post_data, true);

        // return wp error on failure
        if(is_wp_error($post_id)){

            return $post_id;
        }

        $obj = new static($post_id);
        $obj->setPost($post_id);

        /**
         * fires when a new post is created
         */
        do_action("hw_on_{$obj->getPostType()}_post_created", $post_id, $obj);

        return $obj;
    }
}