<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\Placeholders;

interface TexyFormatterPlaceholder
{

	public static function getId(): string;


	public function replace(string $placeholderValue): string;

}
