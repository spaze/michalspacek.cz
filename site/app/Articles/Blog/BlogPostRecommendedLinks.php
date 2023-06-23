<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class BlogPostRecommendedLinks
{

	public function __construct(
		private readonly Processor $schemaProcessor,
	) {
	}


	/**
	 * @return list<BlogPostRecommendedLink>
	 * @throws JsonException
	 */
	public function getFromJson(string $json): array
	{
		$decoded = Json::decode($json);
		$schema = Expect::listOf(
			Expect::structure([
				'url' => Expect::string()->required(),
				'text' => Expect::string()->required(),
			])->castTo(BlogPostRecommendedLink::class),
		);
		/** @var list<BlogPostRecommendedLink> $data */
		$data = $this->schemaProcessor->process($schema, $decoded);
		return $data;
	}

}
