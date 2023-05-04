<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Latte\Nodes;

use Generator;
use Latte\CompileException;
use Latte\Compiler\Node;
use Latte\Compiler\Nodes\Php\Expression\BinaryOpNode;
use Latte\Compiler\Nodes\Php\Scalar\StringNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\Position;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Spaze\SubresourceIntegrity\Config;
use Spaze\SubresourceIntegrity\Exceptions\ShouldNotHappenException;
use Spaze\SubresourceIntegrity\Exceptions\UnsupportedNodeException;
use Spaze\SubresourceIntegrity\Exceptions\UnsupportedOperatorException;
use Spaze\SubresourceIntegrity\HtmlElement;

abstract class SriNode extends StatementNode
{

	protected static ?HtmlElement $targetHtmlElement = null;


	/**
	 * @param string $url
	 * @param string $hash
	 * @param array<string, string|null> $attributes
	 */
	final public function __construct(
		public string $url,
		public string $hash,
		public array $attributes,
	) {
	}


	/**
	 * @throws CompileException
	 * @throws ShouldNotHappenException
	 */
	public static function create(Tag $tag, Config $sriConfig): static
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
			if (!$attributeNode || !$attributeNode->value instanceof StringNode) {
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

		$resources = self::getResources($resource);
		return new static(
			$sriConfig->getUrl($resources, static::$targetHtmlElement),
			$sriConfig->getHash($resources, static::$targetHtmlElement),
			$attributes,
		);
	}


	/**
	 * @return array<int, string>
	 * @throws UnsupportedNodeException
	 * @throws UnsupportedOperatorException
	 */
	private static function getResources(Node $node): array
	{
		$resources = [];
		if ($node instanceof StringNode) {
			$resources[] = $node->value;
		} elseif ($node instanceof BinaryOpNode) {
			if ($node->operator !== Config::BUILD_SEPARATOR) {
				throw new UnsupportedOperatorException($node->operator, Config::BUILD_SEPARATOR);
			}
			foreach ($node as $item) {
				$resources = array_merge($resources, self::getResources($item));
			}
		} else {
			throw new UnsupportedNodeException($node::class);
		}
		return $resources;
	}


	/**
	 * @param array<string, string|null> $attributes
	 */
	protected function printTag(PrintContext $context, ?Position $position, array $attributes): string
	{
		$mask = <<<'XX'
			echo '<' . %escape(%dump) %line;
			$ʟ__sriTagAttrs = %dump;
			if (isset($this->global->uiNonce)) {
				$ʟ__sriTagAttrs['nonce'] = (string)$this->global->uiNonce;
			}
			foreach ($ʟ__sriTagAttrs as $ʟ__sriTagAttrKey => $ʟ__sriTagAttrValue) {
				echo $ʟ__sriTagAttrValue ? ' ' . %escape($ʟ__sriTagAttrKey) . '="' . %escape($ʟ__sriTagAttrValue) . '"' : ' ' . %escape($ʟ__sriTagAttrKey);
			}
			echo '>';
			XX;
		if (static::$targetHtmlElement?->hasEndTag()) {
			$mask .= <<<'XX'
			echo '</' . %escape(%dump) . '>';
			XX;
		}
		return $context->format(
			$mask,
			static::$targetHtmlElement?->value,
			$position,
			$attributes + $this->attributes,
			static::$targetHtmlElement?->value,
		);
	}


	public function &getIterator(): Generator
	{
		/**
		 * @noinspection PhpBooleanCanBeSimplifiedInspection
		 * @phpstan-ignore-next-line
		 */
		false && yield;
	}

}
