<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use ReflectionException;
use ReflectionProperty;

class PrivateProperty
{

	/**
	 * @throws ReflectionException
	 */
	public static function setValue(object $object, string $property, mixed $value): void
	{
		$property = new ReflectionProperty($object, $property);
		$property->setValue($object, $value);
	}


	/**
	 * @throws ReflectionException
	 */
	public static function getValue(object $object, string $property): mixed
	{
		$property = new ReflectionProperty($object, $property);
		return $property->getValue($object);
	}

}
