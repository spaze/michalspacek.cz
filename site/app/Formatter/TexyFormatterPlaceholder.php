<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

interface TexyFormatterPlaceholder
{

	public static function getId(): string;


	public function replace(string $value): string;

}
