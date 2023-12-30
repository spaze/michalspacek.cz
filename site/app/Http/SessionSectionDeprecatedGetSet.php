<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use Override;

trait SessionSectionDeprecatedGetSet
{

	/**
	 * @deprecated Add get<Property>() method instead
	 */
	#[Override]
	public function &__get(string $name): null
	{
		trigger_error('Add get<Property>() method instead', E_USER_DEPRECATED);
		$var = null;
		return $var; // Only variables can be returned by reference
	}


	/**
	 * @deprecated Add get<Property>() method instead
	 */
	#[Override]
	public function get(string $name): void
	{
		trigger_error('Add get<Property>() method instead', E_USER_DEPRECATED);
	}


	/**
	 * @deprecated Add set<Property>() method instead
	 */
	#[Override]
	public function __set(string $name, mixed $value): void
	{
		trigger_error('Add set<Property>() method instead', E_USER_DEPRECATED);
	}


	/**
	 * @deprecated Add set<Property>() method instead
	 */
	#[Override]
	public function set(string $name, mixed $value, ?string $expire = null): void
	{
		trigger_error('Add set<Property>() method instead', E_USER_DEPRECATED);
	}


	/**
	 * @deprecated Add get<Property>() method instead
	 */
	#[Override]
	public function __isset(string $name): bool
	{
		trigger_error('Add get<Property>() method instead', E_USER_DEPRECATED);
		return false;
	}


	/**
	 * @deprecated Add remove<Property>() method instead
	 */
	#[Override]
	public function __unset(string $name): void
	{
		trigger_error('Add remove<Property>() method instead', E_USER_DEPRECATED);
	}


	/**
	 * @param string|array<array-key, string>|null $name
	 * @deprecated Add remove<Property>() method instead
	 */
	#[Override]
	public function remove($name = null): void
	{
		// Deprecated silently, no error, because it's used in Nette\Http\SessionSection::set() when $value is null.
		parent::remove($name);
	}

}
