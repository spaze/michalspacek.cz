<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Serializer;

use LogicException;
use MichalSpacekCz\Test\WillThrow;
use Override;
use Symfony\Component\Serializer\SerializerInterface;

final class SerializerMock implements SerializerInterface
{

	use WillThrow;


	/**
	 * @param array<string, mixed> $deserializeMap
	 */
	public function __construct(private readonly array $deserializeMap)
	{
	}


	#[Override]
	public function serialize(mixed $data, string $format, array $context = []): string
	{
		$this->maybeThrow();
		return '';
	}


	#[Override]
	public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
	{
		if (!isset($this->deserializeMap[$type])) {
			$this->maybeThrow();
			throw new LogicException("No deserialize entry for $type");
		}
		return $this->deserializeMap[$type];
	}

}
