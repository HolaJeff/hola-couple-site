<?php

namespace HW\Traits;
use HW\Models\DecoratorModel;

trait hasNotes{

    /**
     * return post notes
     *
     * @return array
     */
    public function getNotes(){

        $value = json_decode( $this->hw_notes, true );
        
        if(empty($value)){
            $value = [];
        }

        return $value;
    }

    /**
     * add a note to the post
     *
     * @param string $message
     * @param string $author_name
     * @param integer $author_id
     * @return string
     */
    public function addNote( string $message, string $author_name, int $author_id = 0 ){

        if(!$this instanceof DecoratorModel || empty($this->getID())){
            return false;
        }

        // generate id
        $timestamp = time();
        $id = $timestamp . '-' . uniqid();
        $notes = $this->getNotes();

        $notes[$id] = [
            'message' => str_replace('\\','\\\\', $message),
            'author_name' => str_replace('\\','\\\\', $author_name),
            'author_id' => $author_id,
            'timestamp' => $timestamp
        ];

        $this->saveNotes($notes);

        return $id;
    }

    /**
     * persist notes
     *
     * @param array $notes
     * @return void
     */
    protected function saveNotes( array $notes ){

        $this->set('hw_notes', json_encode($notes));
    }
}