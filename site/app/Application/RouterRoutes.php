<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Application\Routers\BlogPostRoute;
use Nette\Application\Routers\Route;

enum RouterRoutes: string
{

	case Route = Route::class;
	case BlogPostRoute = BlogPostRoute::class;

}
