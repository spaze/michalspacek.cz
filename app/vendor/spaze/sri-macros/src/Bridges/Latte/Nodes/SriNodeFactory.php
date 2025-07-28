<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Latte\Nodes;

use Latte\CompileException;
use Latte\Compiler\Node;
use Latte\Compiler\Nodes\Php\Expression\BinaryOpNode;
use Latte\Compiler\Nodes\Php\Scalar\StringNode;
use Latte\Compiler\Tag;
use Spaze\SubresourceIntegrity\Config;
use Spaze\SubresourceIntegrity\Exceptions\ShouldNotHappenException;
use Spaze\SubresourceIntegrity\Exceptions\UnsupportedNodeException;
use Spaze\SubresourceIntegrity\Exceptions\UnsupportedOperatorException;

readonly class SriNodeFactory
{

	public function __construct(
		private Config $sriConfig,
	) {
	}


	/**
	 * @param class-string<SriNode> $sriNode
	 * @throws CompileException
	 * @throws ShouldNotHappenException
	 */
	public function create(Tag $tag, string $sriNode): SriNode
	{
		$tag->expectArguments();

		$resource = $tag->parser->parseUnquotedStringOrExpression();
		$attributeNodes = $tag->parser->parseArguments();
		if ($tag->parser->parseModifier()->filters) {
			trigger_error("Modifiers are not allowed in {{$tag->name}}", E_USER_WARNING);
		}

		/** @var array<string, string|null> $attributes */
		$attributes = [];
		foreach ($attributeNodes->items as $attributeNode) {
			if (!$attributeNode->value instanceof StringNode) {
				throw new ShouldNotHappenException();
			}
			if ($attributeNode->key) {
				if (!$attributeNode->key instanceof StringNode) {
					throw new ShouldNotHappenException();
				}
				$attributes[$attributeNode->key->value] = $attributeNode->value->value;
			} else {
				$attributes[$attributeNode->value->value] = null;
			}
		}
		$attributes['crossorigin'] = 'anonymous';

		return new $sriNode(
			$this->sriConfig,
			$this->getResources($resource),
			$attributes,
		);
	}


	/**
	 * @return array<int, string>
	 * @throws UnsupportedNodeException
	 * @throws UnsupportedOperatorException
	 */
	protected function getResources(Node $node): array
	{
		$resources = [];
		if ($node instanceof StringNode) {
			$resources[] = $node->value;
		} elseif ($node instanceof BinaryOpNode) {
			if ($node->operator !== Config::BUILD_SEPARATOR) {
				throw new UnsupportedOperatorException($node->operator, Config::BUILD_SEPARATOR);
			}
			foreach ($node as $item) {
				$resources = array_merge($resources, $this->getResources($item));
			}
		} else {
			throw new UnsupportedNodeException($node::class);
		}
		return $resources;
	}

}
