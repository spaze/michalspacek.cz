<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity;

enum HashingAlgo: string
{

	case Sha256 = 'sha256';
	case Sha384 = 'sha384';
	case Sha512 = 'sha512';


	/**
	 * @return array<int, string>
	 */
	public static function allAlgos(): array
	{
		return array_map(
			fn(self $algo): string => $algo->value,
			self::cases(),
		);
	}

}
