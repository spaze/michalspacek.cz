<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\FetchMetadata;

enum FetchMetadataHeader: string
{

	case Dest = 'Sec-Fetch-Dest';
	case Mode = 'Sec-Fetch-Mode';
	case Site = 'Sec-Fetch-Site';
	case User = 'Sec-Fetch-User';

}
