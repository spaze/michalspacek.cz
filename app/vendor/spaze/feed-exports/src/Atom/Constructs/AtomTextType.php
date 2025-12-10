<?php
declare(strict_types = 1);

namespace Spaze\Exports\Atom\Constructs;

enum AtomTextType: string
{

	case Text = 'text';
	case Html = 'html';

}
