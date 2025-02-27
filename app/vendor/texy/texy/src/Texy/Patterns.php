<?php

/**
 * This file is part of the Texy! (https://texy.info)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Texy;


/**
 * Regular expression patterns
 */
class Patterns
{
	// Unicode character classes
	public const CHAR = 'A-Za-z\x{C0}-\x{2FF}\x{370}-\x{1EFF}';

	// marking meta-characters
	// any mark:              \x14-\x1F
	// CONTENT_MARKUP mark:   \x17-\x1F
	// CONTENT_REPLACED mark: \x16-\x1F
	// CONTENT_TEXTUAL mark:  \x15-\x1F
	// CONTENT_BLOCK mark:    \x14-\x1F
	public const MARK = '\x14-\x1F';

	// modifier .(title)[class]{style}
	public const MODIFIER = '(?:\ *+(?<=\ |^)\.((?:\((?:\\\\\)|[^)\n])++\)|\[[^\]\n]++\]|\{[^}\n]++\}){1,3}?))';

	// modifier .(title)[class]{style}<>
	public const MODIFIER_H = '(?:\ *+(?<=\ |^)\.((?:\((?:\\\\\)|[^)\n])++\)|\[[^\]\n]++\]|\{[^}\n]++\}|<>|>|=|<){1,4}?))';

	// modifier .(title)[class]{style}<>^
	public const MODIFIER_HV = '(?:\ *+(?<=\ |^)\.((?:\((?:\\\\\)|[^)\n])++\)|\[[^\]\n]++\]|\{[^}\n]++\}|<>|>|=|<|\^|\-|\_){1,5}?))';

	// images   [* urls .(title)[class]{style} >]   '\[\* *+([^\n'.MARK.']{1,1000})'.MODIFIER.'? *+(\*|(?<!<)>|<)\]'
	public const IMAGE = '\[\*\ *+([^\n\x14-\x1F]{1,1000})(?:\ *+(?<=\ |^)\.((?:\([^)\n]++\)|\[[^\]\n]++\]|\{[^}\n]++\}){1,3}?))?\ *+(\*|(?<!<)>|<)\]';

	// links, url - doesn't end by :).,!?
	public const LINK_URL = '(?:\[[^\]\n]++\]|(?=[\w/+.~%&?@=_\#$])[^\s\x14-\x1F]{0,1000}?[^:);,.!?\s\x14-\x1F])'; // any url - doesn't end by :).,!?
}
