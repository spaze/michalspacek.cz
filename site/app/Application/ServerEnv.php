<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Application\Exceptions\ServerEnvNotArrayException;
use MichalSpacekCz\Application\Exceptions\ServerEnvNotFoundException;
use MichalSpacekCz\Application\Exceptions\ServerEnvNotStringException;

class ServerEnv
{

	/**
	 * @throws ServerEnvNotFoundException
	 * @throws ServerEnvNotStringException
	 */
	public static function getString(string $key): string
	{
		if (!isset($_SERVER[$key])) {
			throw new ServerEnvNotFoundException();
		}
		if (!is_string($_SERVER[$key])) {
			throw new ServerEnvNotStringException();
		}
		return $_SERVER[$key];
	}


	public static function tryGetString(string $key): ?string
	{
		if (!isset($_SERVER[$key])) {
			return null;
		}
		if (!is_string($_SERVER[$key])) {
			return null;
		}
		return $_SERVER[$key];
	}


	public static function setString(string $key, string $value): void
	{
		$_SERVER[$key] = $value;
	}


	/**
	 * @return list<mixed>
	 * @throws ServerEnvNotFoundException
	 * @throws ServerEnvNotArrayException
	 */
	public static function getList(string $key): array
	{
		if (!isset($_SERVER[$key])) {
			throw new ServerEnvNotFoundException();
		}
		if (!is_array($_SERVER[$key])) {
			throw new ServerEnvNotArrayException();
		}
		return array_values($_SERVER[$key]);
	}


	/**
	 * @return list<mixed>|null
	 */
	public static function tryGetList(string $key): ?array
	{
		if (!isset($_SERVER[$key])) {
			return null;
		}
		if (!is_array($_SERVER[$key])) {
			return null;
		}
		return array_values($_SERVER[$key]);
	}


	public static function unset(string $key): void
	{
		unset($_SERVER[$key]);
	}

}
