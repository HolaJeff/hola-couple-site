<?php

namespace HW\Utils;

class CardBuilder{

    /**
     * return an instance without calling the constructor
     *
     * @return Card
     */
    public static function build($number, $month, $year, $ccv, $type, $card_holder){

        $class = new \ReflectionClass(Card::class);

        $card = $class->newInstanceWithoutConstructor();
        $card->setCardHolder($card_holder);
        $card->setNumber($number);
        $card->setExpMonth($month);
        $card->setExpYear($year);
        $card->setCCV($ccv);
        $card->setType($type);

        return $card;
    }

    public static function empty(): \HW\Utils\Card
    {
        $ref = new \ReflectionClass(\HW\Utils\Card::class);
        return $ref->newInstanceWithoutConstructor();
    }
}