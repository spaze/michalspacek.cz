<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity;

readonly class LocalFile
{

	public function __construct(private string $url, private string $filename)
	{
	}


	public function getUrl(): string
	{
		return $this->url;
	}


	public function getFilename(): string
	{
		return $this->filename;
	}

}
