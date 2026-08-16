# lambda-security-txt

This is what's deployed to AWS Lambda.

Dev tools (PHPStan, phpcs, …) come from `dev-tools/` at the repository root, install them with
`composer --working-dir=../dev-tools/ install` before the first run.

- Update libs with `make composer-update`
- Check with `make test`
- Deploy with
  - `make deploy-dev` to deploy to dev stage
  - `make deploy-prod` to deploy to prod stage

Use the function with

```php
$result = $lambda->invoke([
  'FunctionName' => 'security-txt-dev-fetch', // "dev" for the dev stage
  'Payload' => json_encode(['host' => $host]),
]);

$json = $result->getPayload();
$decoded = json_decode($json, true);
```

This will only fetch the `security.txt` file, not validate it, and pass it back
to the caller where you can use it to create the result object like this:

```php
  $fetchResult = $securityTxtFetchResultFactory->createFromJsonValues($decoded['fetchResult'])
  $parseResult = $securityTxtParser->parseFetchResult($fetchResult);
  $checkHostResult = $securityTxtCheckHostResultFactory->create($host, $parseResult);
  var_dump($checkHostResult->isValid());
```

You can run full check with validation if you set the `checkHost` parameter to `true` in the `Payload` JSON
when calling `$lambda->invoke()` but it won't currently work because Bref's GnuPG extension [doesn't work](https://github.com/brefphp/extra-php-extensions/issues/556).

To send a custom user agent header when fetching the contents set the `userAgent` parameter to a string value.

Use `requireTopLevelLocation` parameter to issue a warning when the `security.txt` file is not present at `/security.txt`, or the location is not redirected to `/.well-known/security.txt`. The default is `false`.

Set the `noIpv6` parameter to `true` to disable fetching `security.txt` over IPv6, which may still be needed for Lambda. The default is `false` to match the spaze/security-txt's default.

Use `'FunctionName' => 'security-txt-dev-version'` or run `serverless invoke --function version` to get version numbers
of the library currently deployed to Lambda.
