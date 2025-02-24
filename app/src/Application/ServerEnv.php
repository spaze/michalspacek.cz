<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Application\Exceptions\ServerEnvNotFoundException;
use MichalSpacekCz\Application\Exceptions\ServerEnvNotStringException;

final class ServerEnv
{

	/**
	 * @param non-empty-string $key
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


	/**
	 * @param non-empty-string $key
	 */
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


	/**
	 * @param non-empty-string $key
	 */
	public static function setString(string $key, string $value): void
	{
		$_SERVER[$key] = $value;
	}


	/**
	 * @param non-empty-string $key
	 */
	public static function unset(string $key): void
	{
		unset($_SERVER[$key]);
	}

}
