<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

interface TexyFormatterPlaceholder
{

	public static function getPlaceholder(): string;


	public function replace(string $placeholder): string;

}
