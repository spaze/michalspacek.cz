<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity;

enum LocalMode: string
{

	/** Direct access to local files */
	case Direct = 'direct';

	/** Build local files, new file for every new resource version */
	case Build = 'build';


	/**
	 * @return array<int, string>
	 */
	public static function allModes(): array
	{
		return array_map(
			fn(self $mode): string => $mode->value,
			self::cases(),
		);
	}

}
