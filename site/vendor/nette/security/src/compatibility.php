<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Security;

if (false) {
	/** @deprecated use Nette\Security\Authenticator */
	interface IAuthenticator extends Authenticator
	{
	}

	/** @deprecated use Nette\Security\Authorizator */
	interface IAuthorizator extends Authorizator
	{
	}

	/** @deprecated use Nette\Security\Resource */
	interface IResource
	{
	}

	/** @deprecated use Nette\Security\Role */
	interface IRole
	{
	}

	/** @deprecated use Nette\Security\UserStorage */
	interface IUserStorage
	{
	}

	/** @deprecated use Nette\Security\SimpleIdentity */
	class Identity extends SimpleIdentity
	{
	}
} elseif (!interface_exists(IAuthenticator::class)) {
	class_alias(Authenticator::class, IAuthenticator::class);
	class_alias(Authorizator::class, IAuthorizator::class);
	class_alias(Resource::class, IResource::class);
	class_alias(Role::class, IRole::class);
	class_alias(UserStorage::class, IUserStorage::class);
	class_alias(SimpleIdentity::class, Identity::class);
}
