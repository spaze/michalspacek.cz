<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

final readonly class UpcKeysStorageConversions
{

	public function getKeyFromBinary(int $binaryKey): string
	{
		$key = '';
		for ($i = 7; $i >= 0; $i--) {
			$key .= chr(($binaryKey >> $i * 5 & 0x1F) + 0x41);
		}
		return $key;
	}


	public function getBinaryFromKey(string $key): int
	{
		$binaryKey = '';
		for ($i = 0; $i < 8; $i++) {
			$binaryKey .= str_pad(decbin(ord($key[$i]) - 0x41), 5, '0', STR_PAD_LEFT);
		}
		return (int)bindec($binaryKey);
	}

}
