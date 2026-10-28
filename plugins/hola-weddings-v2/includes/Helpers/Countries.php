<?php

namespace HW\Helpers;

class Countries{

    protected static $list = null;

    protected static $states = null;

    /**
     * load from file
     *
     * @return void
     */
    private static function load(){

        if(empty(self::$list)){

            self::$list = json_decode(file_get_contents(HW_INCLUDES_PATH . '/data/countries.json'), true);
        }

        if(empty(self::$states)){

            self::$states = json_decode(file_get_contents(HW_INCLUDES_PATH . '/data/states.json'), true);
        }
    }

    /**
     * return a list of countries
     *
     * @return array
     */
    public static function getList($indexed = false){

        self::load();

        $list = self::$list;

        if($indexed){

            $list = array_combine(array_column(self::$list, 'code'), self::$list);
        }

        return $list;
    }

    /**
     * return 
     *
     * @param string $code
     * @return string
     */
    public static function getCountryName($code){

        $list = self::getList(true);
        
        return isset($list[$code]) ? $list[$code]['name'] : '';
    }

    /**
     * return a list of US states
     *
     * @return array
     */
    public static function getUSStates(){

        self::load();
        
        return self::$states['US'];
    }

    /**
     * return state name by code
     *
     * @param string $code
     * @return string
     */
    public static function getStateName($code){
        
        $states = self::getUSStates();
        return isset($states[$code]) ? $states[$code] : '';
    }
}