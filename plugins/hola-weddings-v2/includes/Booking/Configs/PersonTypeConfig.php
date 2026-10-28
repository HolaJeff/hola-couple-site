<?php

namespace HW\Booking\Configs;

class PersonTypeConfig{

    /**
     * return an array of person types configurations
     *
     * @return array
     */
    public static function getAll(): array
    {
        $hotel = (object) hw_get_hotel();
        $age_labels = hw_get_age_labels();

        return [
            'adult' => [
                'label' => __('Adult', 'hola-weddings'),
                'min' => $hotel->adults_only || hw_is_teen_enabled() ? 18 : 13,
                'messages' => [
                    'min' => __('The guest must be at least %d years old at time of travel', 'hola-weddings')
                ]
            ],
            'teen' => [
                'label' => __('Teen', 'hola-weddings'),
                'min' => $age_labels['teen_min'],
                'max' => $age_labels['teen_max'],
                'messages' => [
                    'min' => __('The minimum age for teens is %d years at time of travel.', 'hola-weddings'),
                    'max' => __('The maximum age for teens is %d years at time of travel.', 'hola-weddings')
                ]
            ],
            'child' => [
                'label' => __('Child', 'hola-weddings'),
                'min' => $age_labels['children_min'],
                'max' => $age_labels['children_max'],
                'messages' => [
                    'min' => __('The minimum age for children is %d years at time of travel.', 'hola-weddings'),
                    'max' => __('The maximum age for children is %d years at time of travel.', 'hola-weddings')
                ]
            ],
            'infant' => [
                'label' => __('Infant', 'hola-weddings'),
                'min' => '0',
                'max' => $age_labels['infant_max'],
                'messages' => [
                    'min' => __('The minimum age for infants is %d years at time of travel.', 'hola-weddings'),
                    'max' => __('The maximum age for infants is %d years at time of travel.', 'hola-weddings')
                ]
            ],
        ];
    }

    /**
     * return configuration for a given person type
     *
     * @param string $key
     * @return array|null
     */
    public static function get(string $key): ?array
    {
        return self::getAll()[$key] ?? null;
    }
}