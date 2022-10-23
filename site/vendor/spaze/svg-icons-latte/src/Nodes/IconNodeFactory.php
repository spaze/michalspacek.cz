<?php
declare(strict_types = 1);

namespace Spaze\SvgIcons\Nodes;

use DOMDocument;
use Latte\CompileException;
use Latte\Compiler\Nodes\Php\Scalar\StringNode;
use Latte\Compiler\Tag;
use Nette\IOException;
use Nette\Utils\FileSystem;
use Spaze\SvgIcons\Exceptions\IconTagException;

class IconNodeFactory
{

	public function __construct(
		private readonly string $iconsDir,
		private readonly ?string $cssClass,
	) {
	}


	/**
	 * @throws CompileException
	 * @throws IconTagException
	 */
	public function create(Tag $tag): IconNode
	{
		$tag->expectArguments();
		$resource = $tag->parser->parseUnquotedStringOrExpression();
		if (!$resource instanceof StringNode) {
			throw new IconTagException(sprintf("Resource must be a '%s' but is a '%s'", StringNode::class, $resource::class), $tag);
		}
		$cssClasses = $this->cssClass ? [$this->cssClass] : [];
		foreach ($tag->parser->parseArguments()->items as $argument) {
			if (!$argument) {
				continue;
			}
			if (!$argument->value instanceof StringNode) {
				throw new IconTagException('Only strings supported', $tag, $resource);
			}
			if (!$argument->key) {
				throw new IconTagException("Value '{$argument->value->value}' must have a key", $tag, $resource);
			}
			if (!$argument->key instanceof StringNode) {
				throw new IconTagException(sprintf("Key for '%s' must be a '%s' but is a '%s'", $argument->value->value, StringNode::class, $argument->key::class), $tag, $resource);
			}
			$cssClasses[] = match ($argument->key->value) {
				'class' => $argument->value->value,
				default => throw new IconTagException("Unknown argument {$argument->key->value} => {$argument->value->value}", $tag, $resource),
			};
		}
		if ($tag->parser->parseModifier()->filters) {
			throw new IconTagException('Modifiers are not allowed', $tag, $resource);
		}
		try {
			$svg = FileSystem::read("{$this->iconsDir}/{$resource->value}.svg");
		} catch (IOException $e) {
			throw new IconTagException("Icon '{$resource->value}' not found in '{$this->iconsDir}'", $tag, $resource, previous: $e);
		}
		return $cssClasses ? new IconNode($this->addClasses($svg, $cssClasses, $tag, $resource)) : new IconNode($svg);
	}


	/**
	 * @param list<string> $cssClasses
	 * @throws IconTagException
	 */
	private function addClasses(string $svg, array $cssClasses, Tag $tag, StringNode $resource): string
	{
		$xml = new DOMDocument();
		$xml->loadXML($svg);
		$rootEl = $xml->documentElement;
		if (!$rootEl) {
			throw new IconTagException('The XML is missing a document element', $tag, $resource);
		}
		if ($rootEl->getAttribute('class')) {
			$cssClasses[] = $rootEl->getAttribute('class');
		}
		$rootEl->setAttribute('class', implode(' ', $cssClasses));
		$output = $xml->saveXML($rootEl);
		if (!$output) {
			throw new IconTagException('Cannot save generated XML, last error: ' . json_encode(error_get_last()), $tag, $resource);
		}
		return $output;
	}

}
