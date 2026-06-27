<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\Formatter\TexyFormatter;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Stringable;

final readonly class Filters
{

	public function __construct(
		private TexyFormatter $texyFormatter,
		private DateTimeFormatter $dateTimeFormatter,
	) {
	}


	/**
	 * @return array<string, callable>
	 */
	public function getAll(): array
	{
		return [
			'staticUrl' => $this->staticUrl(...),
			'staticImageUrl' => $this->staticImageUrl(...),
			'format' => $this->format(...),
			'formatPossiblyUnsafeHtml' => $this->formatPossiblyUnsafeHtml(...),
			'localeDay' => $this->dateTimeFormatter->localeDay(...),
			'localeMonth' => $this->dateTimeFormatter->localeMonth(...),
			'localeIntervalDay' => $this->dateTimeFormatter->localeIntervalDay(...),
			'localeIntervalMonth' => $this->dateTimeFormatter->localeIntervalMonth(...),
			'truncateMiddle' => $this->truncateMiddle(...),
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


	public function format(string|Stringable $message, string|Stringable|int ...$args): Html
	{
		return $this->texyFormatter->substitute($message, array_values($args));
	}


	public function formatPossiblyUnsafeHtml(string|Stringable $message, string|Stringable|int ...$args): Html
	{
		return $this->texyFormatter->substitutePossiblyUnsafeHtml($message, array_values($args));
	}


	/**
	 * Converts e.g. "foo bar fred" to "foo…fred", the … rendered by CSS.
	 */
	public function truncateMiddle(string $value, int $tailLength = 16, ?string $title = null): Html
	{
		$split = max(0, Strings::length($value) - $tailLength);
		return Html::el('span')->setAttribute('class', 'truncateMiddle')->setAttribute('title', $title ?? $value)
			->addHtml(Html::el('span')->setText(Strings::substring($value, 0, $split)))
			->addHtml(Html::el('span')->setText(Strings::substring($value, $split)));
	}

}
