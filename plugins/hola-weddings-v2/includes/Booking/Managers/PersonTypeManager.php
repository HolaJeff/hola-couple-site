<?php

namespace HW\Booking\Managers;

use HW\Booking\Configs\PersonTypeConfig;
use HW\Booking\Objects\PersonType;
use HW\Traits\Singleton;

class PersonTypeManager
{
    use Singleton;

    private array $instances = [];

    /**
     * constructor
     */
    public function __construct(){
        $this->load();
    }

    /**
     * Loads all person types into memory on plugin initialization.
     */
    private function load(): void
    {
        $config = PersonTypeConfig::getAll();

        foreach ($config as $type => $config) {
            $this->instances[$type] = new PersonType($type, $config);
        }
    }

    /**
     * Retrieves a specific PersonType instance.
     *
     * @param string $key The person type key (e.g., 'adult', 'child').
     * @return PersonType|null Returns the instance or null if not found.
     */
    public function get(string $key): ?PersonType
    {
        return $this->instances[$key] ?? null;
    }

    /**
     * Returns all loaded PersonType instances.
     *
     * @return PersonType[] List of all person types.
     */
    public function getAll(): array
    {
        return $this->instances;
    }
}
