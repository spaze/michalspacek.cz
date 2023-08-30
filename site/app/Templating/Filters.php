<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\Formatter\TexyFormatter;
use Nette\Utils\Html;

class Filters
{

	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly DateTimeFormatter $dateTimeFormatter,
	) {
	}


	/**
	 * @return array<string, callable>
	 */
	public function getAll(): array
	{
		return [
			'staticUrl' => [$this, 'staticUrl'],
			'staticImageUrl' => [$this, 'staticImageUrl'],
			'format' => [$this, 'format'],
			'localeDay' => [$this->dateTimeFormatter, 'localeDay'],
			'localeMonth' => [$this->dateTimeFormatter, 'localeMonth'],
			'localeIntervalDay' => [$this->dateTimeFormatter, 'localeIntervalDay'],
			'localeIntervalMonth' => [$this->dateTimeFormatter, 'localeIntervalMonth'],
		];
	}


	public function staticUrl(string $filename): string
	{
		return sprintf('%s/%s', $this->texyFormatter->getStaticRoot(), ltrim($filename, '/'));
	}


	public function staticImageUrl(string $filename): string
	{
		return $this->texyFormatter->getImagesRoot($filename);
	}


	public function format(string $message, string|int ...$args): Html
	{
		return $this->texyFormatter->substitute($message, array_values($args));
	}

}
