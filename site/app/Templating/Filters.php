<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\Formatter\Texy;
use Nette\Utils\Html;

class Filters
{

	public function __construct(
		private readonly Texy $texyFormatter,
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


	/**
	 * @param string $message
	 * @return Html<Html|string>
	 */
	public function format(string $message): Html
	{
		$args = func_get_args();
		array_shift($args);
		return $this->texyFormatter->substitute($message, $args);
	}

}
