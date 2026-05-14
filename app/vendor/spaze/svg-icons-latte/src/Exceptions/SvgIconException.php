<?php
declare(strict_types = 1);

namespace Spaze\SvgIcons\Exceptions;

use Exception;
use Throwable;

class SvgIconException extends Exception
{

	public function __construct(
		private string $icon,
		private string $dir,
		int $code = 0,
		?Throwable $previous = null,
	) {
		$message = "Icon '{$icon}' cannot be read from '{$dir}'";
		if ($previous !== null && $previous->getMessage() !== '') {
			$message .= ": {$previous->getMessage()}";
		}
		parent::__construct($message, $code, $previous);
	}


	public function getIcon(): string
	{
		return $this->icon;
	}


	public function getDir(): string
	{
		return $this->dir;
	}

}
