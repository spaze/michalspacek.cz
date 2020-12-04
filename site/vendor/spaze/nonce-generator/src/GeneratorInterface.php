<?php
declare(strict_types = 1);

namespace Spaze\NonceGenerator;

interface GeneratorInterface
{

	public function getNonce(): string;

}
