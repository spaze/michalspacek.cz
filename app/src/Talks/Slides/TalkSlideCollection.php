<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Slides;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use MichalSpacekCz\Talks\Exceptions\TalkSlideDoesNotExistException;
use Override;

/**
 * @implements IteratorAggregate<int, TalkSlide>
 */
final class TalkSlideCollection implements IteratorAggregate, Countable
{

	/** @var array<int, TalkSlide> slide number => slide */
	private array $slides = [];


	public function __construct(
		private readonly int $talkId,
	) {
	}


	public function add(TalkSlide $slide): void
	{
		$this->slides[$slide->getNumber()] = $slide;
	}


	/**
	 * @throws TalkSlideDoesNotExistException
	 */
	public function getByNumber(int $number): TalkSlide
	{
		if (!isset($this->slides[$number])) {
			throw new TalkSlideDoesNotExistException($this->talkId, $number);
		}
		return $this->slides[$number];
	}


	/**
	 * @return ArrayIterator<int, TalkSlide>
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->slides);
	}


	#[Override]
	public function count(): int
	{
		return count($this->slides);
	}

}
