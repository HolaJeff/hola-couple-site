<?php

namespace HW\Objects;

class HWObject{

	public function __construct($properties = null)
	{
		if ($properties !== null)
		{
			$this->setProperties($properties);
		}
	}

    /**
	 * Returns a property of the object or the default value if the property is not set.
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $default   The default value.
	 *
	 * @return  mixed    The value of the property.
	 */
	public function get($property, $default = null)
	{
		return $this->$property;
	}

    /**
	 * Modifies a property of the object, creating it if it does not already exist.
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $value     The value of the property to set.
	 *
	 * @return  mixed  Previous value of the property.
	 */
	public function set($property, $value = null)
	{
		$previous = $this->$property ?: null;
		$this->$property = $value;

		$this->propertyUpdated($property, $value, $previous);

		return $previous;
	}

	/**
	 * Set the object properties based on a named array/hash.
	 *
	 * @param   mixed  $properties  Either an associative array or another object.
	 *
	 * @return  boolean
	 */
	public function setProperties($properties)
	{
		if (is_array($properties) || is_object($properties))
		{
			foreach ((array) $properties as $k => $v)
			{
				// Use the set function which might be overridden.
				$this->set($k, $v);
			}

			return true;
		}

		return false;
	}

	/**
	 * property was updated
	 *
	 * @param string $property
	 * @param mixed $value
	 * @return void
	 */
	public function propertyUpdated($property, $value, $previous){}
}