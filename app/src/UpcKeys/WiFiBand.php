<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

enum WiFiBand: int
{

	case Band24GHz = 1;
	case Band5GHz = 2;
	case Unknown = 3;


	public function getLabel(): string
	{
		return match ($this) {
			self::Band24GHz => '2.4 GHz',
			self::Band5GHz => '5 GHz',
			self::Unknown => 'unknown',
		};
	}


	/**
	 * @return array<int, self>
	 */
	public static function getKnown(): array
	{
		return [self::Band24GHz, self::Band5GHz];
	}

}
