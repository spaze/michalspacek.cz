<?php
declare(strict_types = 1);

namespace Spaze\SvgIcons\Exceptions;

use Latte\CompileException;
use Latte\Compiler\Nodes\Php\Scalar\StringNode;
use Latte\Compiler\Tag;
use Throwable;

class IconTagException extends CompileException
{

	public function __construct(string $message, Tag $tag, ?StringNode $resource = null, ?Throwable $previous = null)
	{
		$message .= $resource ? " in '{{$tag->name} {$resource->value}}'" : " in '{{$tag->name}}'";
		parent::__construct($message, $tag->position, $previous);
	}

}
