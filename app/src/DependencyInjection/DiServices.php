<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DependencyInjection;

use MichalSpacekCz\DependencyInjection\Exceptions\DiServicesConfigInvalidException;
use Nette\Neon\Entity;
use Nette\Neon\Neon;

class DiServices
{

	/** @var array<string, non-empty-lowercase-string> */
	protected static array $configFiles = [
		__DIR__ . '/../../config/services.neon' => 'services',
		__DIR__ . '/../../config/extensions.neon' => 'extensions',
	];


	/**
	 * @return list<class-string>
	 */
	public static function getAllClasses(): array
	{
		$allServices = [];
		foreach (self::$configFiles as $file => $section) {
			$decoded = Neon::decodeFile($file);
			if (!is_array($decoded)) {
				throw new DiServicesConfigInvalidException($file, null, 'not an array');
			}
			if (!isset($decoded[$section])) {
				throw new DiServicesConfigInvalidException($file, $section, "section doesn't exist");
			}
			$services = $decoded[$section];
			if (!is_array($services)) {
				throw new DiServicesConfigInvalidException($file, $section, "section not iterable");
			}
			$services = array_filter($services, function (mixed $value): bool {
				return is_string($value) || is_array($value) || $value instanceof Entity;
			});
			foreach ($services as $service) {
				$classString = self::getString($service, $file, $section);
				if (str_starts_with($classString, '@')) {
					continue;
				}
				if (class_exists($classString) || interface_exists($classString)) {
					$allServices[] = $classString;
				} else {
					throw new DiServicesConfigInvalidException($file, $section, "class or interface '{$classString}' doesn't exist");
				}
			}
		}
		return $allServices;
	}


	/**
	 * @param string|Entity|array<array-key, mixed> $item
	 */
	private static function getString(string|Entity|array $item, string $file, string $section): string
	{
		if (is_string($item)) {
			return $item;
		} elseif (is_array($item)) {
			if (isset($item['create'])) {
				if (is_string($item['create'])) {
					return $item['create'];
				} elseif ($item['create'] instanceof Entity && is_string($item['create']->value)) {
					return $item['create']->value;
				}
			} elseif (isset($item['type']) && is_string($item['type'])) {
				return $item['type'];
			}
		} elseif (is_string($item->value)) {
			return $item->value;
		}
		$message = is_array($item) ? sprintf("Unsupported array '%s'", json_encode($item)) : sprintf("Unsupported item '%s'", get_debug_type($item));
		throw new DiServicesConfigInvalidException($file, $section, $message);
	}

}
