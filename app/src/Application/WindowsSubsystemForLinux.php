<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

final class WindowsSubsystemForLinux
{

	public function isWsl(): bool
	{
		return str_ends_with(php_uname('r'), 'microsoft-standard-WSL2');
	}

}
