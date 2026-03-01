<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Slides;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use MichalSpacekCz\Talks\Exceptions\TalkSlideIdDoesNotExistException;
use MichalSpacekCz\Talks\Exceptions\TalkSlideNameDoesNotExistException;
use MichalSpacekCz\Talks\Exceptions\TalkSlideNumberDoesNotExistException;
use Override;

/**
 * @implements IteratorAggregate<int, TalkSlide>
 */
final class TalkSlideCollection implements IteratorAggregate, Countable
{

	/** @var array<int, TalkSlide> slide id => slide */
	private array $slidesById = [];

	/** @var array<int, TalkSlide> slide number => slide */
	private array $slidesByNumber = [];

	/** @var array<string, TalkSlide> slide alias => slide */
	private array $slidesByAlias = [];


	public function __construct(
		private readonly int $talkId,
	) {
	}


	public function add(TalkSlide $slide): void
	{
		$this->slidesById[$slide->getId()] = $this->slidesByNumber[$slide->getNumber()] = $this->slidesByAlias[$slide->getAlias()] = $slide;
	}


	/**
	 * @throws TalkSlideIdDoesNotExistException
	 */
	public function getById(int $id): TalkSlide
	{
		if (!isset($this->slidesById[$id])) {
			throw new TalkSlideIdDoesNotExistException($this->talkId, $id);
		}
		return $this->slidesById[$id];
	}


	/**
	 * @throws TalkSlideNumberDoesNotExistException
	 */
	public function getByNumber(int $number): TalkSlide
	{
		if (!isset($this->slidesByNumber[$number])) {
			throw new TalkSlideNumberDoesNotExistException($this->talkId, $number);
		}
		return $this->slidesByNumber[$number];
	}


	/**
	 * @throws TalkSlideNameDoesNotExistException
	 */
	public function getByAlias(string $alias): TalkSlide
	{
		if (!isset($this->slidesByAlias[$alias])) {
			throw new TalkSlideNameDoesNotExistException($this->talkId, $alias);
		}
		return $this->slidesByAlias[$alias];
	}


	/**
	 * @return ArrayIterator<int, TalkSlide>
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->slidesByNumber);
	}


	#[Override]
	public function count(): int
	{
		return count($this->slidesByNumber);
	}

}
