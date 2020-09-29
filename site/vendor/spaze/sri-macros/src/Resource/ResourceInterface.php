<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Resource;

interface ResourceInterface
{

	public function getContent(): string;


	public function getExtension(): ?string;

}
