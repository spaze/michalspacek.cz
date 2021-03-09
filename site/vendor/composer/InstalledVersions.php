<?php











namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => 'dev-master',
    'version' => 'dev-master',
    'aliases' => 
    array (
    ),
    'reference' => '5bcad8a43c5a3f8f34cd78a24c3d944189faa799',
    'name' => 'spaze/michalspacek.cz',
  ),
  'versions' => 
  array (
    'contributte/translation' => 
    array (
      'pretty_version' => 'v0.8.3',
      'version' => '0.8.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'bbc92735efbb00b84be64227ee7bd32a22856802',
    ),
    'dealerdirect/phpcodesniffer-composer-installer' => 
    array (
      'pretty_version' => 'v0.7.1',
      'version' => '0.7.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'fe390591e0241955f22eb9ba327d137e501c771c',
    ),
    'dg/texy' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'grogy/php-parallel-lint' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'jakub-onderka/php-console-color' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'jakub-onderka/php-console-highlighter' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'jakub-onderka/php-parallel-lint' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'latte/latte' => 
    array (
      'pretty_version' => 'v2.10.2',
      'version' => '2.10.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '2f8dd896472b8a2f2069934f128a02566631d876',
    ),
    'nette/application' => 
    array (
      'pretty_version' => 'v3.1.2',
      'version' => '3.1.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f817a0b738a3190efe1e573a099d1a80797e156b',
    ),
    'nette/bootstrap' => 
    array (
      'pretty_version' => 'v3.1.1',
      'version' => '3.1.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'efe6c30fc009451f59fe56f3b309eb85c48b2baf',
    ),
    'nette/caching' => 
    array (
      'pretty_version' => 'v3.1.0',
      'version' => '3.1.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '60281abf366c4ab76e9436dc1bfe2e402db18b67',
    ),
    'nette/component-model' => 
    array (
      'pretty_version' => 'v3.0.1',
      'version' => '3.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '66409cf5507c77edb46ffa88cf6a92ff58395601',
    ),
    'nette/database' => 
    array (
      'pretty_version' => 'v3.1.1',
      'version' => '3.1.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '17bc9b2b5a5cd57ab089bc1a5982bc6c3b3790e3',
    ),
    'nette/di' => 
    array (
      'pretty_version' => 'v3.0.7',
      'version' => '3.0.7.0',
      'aliases' => 
      array (
      ),
      'reference' => '33b188dd8fce8de15795a19ac89bb34227dfb37a',
    ),
    'nette/finder' => 
    array (
      'pretty_version' => 'v2.5.2',
      'version' => '2.5.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '4ad2c298eb8c687dd0e74ae84206a4186eeaed50',
    ),
    'nette/forms' => 
    array (
      'pretty_version' => 'v3.1.2',
      'version' => '3.1.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '54858a9eeb0c57c316a07cea5aa2fa135c6807ac',
    ),
    'nette/http' => 
    array (
      'pretty_version' => 'v3.1.1',
      'version' => '3.1.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'c903d0f0b793ed2045a442f338e756e1d3954c22',
    ),
    'nette/mail' => 
    array (
      'pretty_version' => 'v3.1.5',
      'version' => '3.1.5.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e072486ef2dbb533189f36a53c020f06b7659f4f',
    ),
    'nette/neon' => 
    array (
      'pretty_version' => 'v3.2.1',
      'version' => '3.2.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a5b3a60833d2ef55283a82d0c30b45d136b29e75',
    ),
    'nette/php-generator' => 
    array (
      'pretty_version' => 'v3.5.2',
      'version' => '3.5.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '41dcc5d1cb322835e5950a76515166c90923c6b7',
    ),
    'nette/robot-loader' => 
    array (
      'pretty_version' => 'v3.3.1',
      'version' => '3.3.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '15c1ecd0e6e69e8d908dfc4cca7b14f3b850a96b',
    ),
    'nette/routing' => 
    array (
      'pretty_version' => 'v3.0.2',
      'version' => '3.0.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '5532e7e3612e13def357f089c1a5c25793a16843',
    ),
    'nette/schema' => 
    array (
      'pretty_version' => 'v1.2.0',
      'version' => '1.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '9962564311f4affebd63f9cab014ab69266306ce',
    ),
    'nette/security' => 
    array (
      'pretty_version' => 'v3.1.3',
      'version' => '3.1.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '817ee98aad1f122f8f40b728c35e634086e1093d',
    ),
    'nette/tester' => 
    array (
      'pretty_version' => 'v2.3.5',
      'version' => '2.3.5.0',
      'aliases' => 
      array (
      ),
      'reference' => '86d0e32ac2011c734d8def8e90ec65522b644bc4',
    ),
    'nette/utils' => 
    array (
      'pretty_version' => 'v3.2.1',
      'version' => '3.2.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '2bc2f58079c920c2ecbb6935645abf6f2f5f94ba',
    ),
    'paragonie/constant_time_encoding' => 
    array (
      'pretty_version' => 'v2.4.0',
      'version' => '2.4.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f34c2b11eb9d2c9318e13540a1dbc2a3afbd939c',
    ),
    'paragonie/halite' => 
    array (
      'pretty_version' => 'v4.7.1',
      'version' => '4.7.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '538a0579e86ac8222973be55d17615a7be8f29cb',
    ),
    'paragonie/hidden-string' => 
    array (
      'pretty_version' => 'v2.0.0',
      'version' => '2.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '151e53d55bfc67dd58087cdf8762dd8177ea7575',
    ),
    'paragonie/random_compat' => 
    array (
      'replaced' => 
      array (
        0 => '9.99.99',
      ),
    ),
    'paragonie/sodium_compat' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'php-parallel-lint/php-console-color' => 
    array (
      'pretty_version' => 'v0.3',
      'version' => '0.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b6af326b2088f1ad3b264696c9fd590ec395b49e',
    ),
    'php-parallel-lint/php-console-highlighter' => 
    array (
      'pretty_version' => 'v0.5',
      'version' => '0.5.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '21bf002f077b177f056d8cb455c5ed573adfdbb8',
    ),
    'php-parallel-lint/php-parallel-lint' => 
    array (
      'pretty_version' => 'v1.2.0',
      'version' => '1.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '474f18bc6cc6aca61ca40bfab55139de614e51ca',
    ),
    'phpstan/phpdoc-parser' => 
    array (
      'pretty_version' => '0.4.9',
      'version' => '0.4.9.0',
      'aliases' => 
      array (
      ),
      'reference' => '98a088b17966bdf6ee25c8a4b634df313d8aa531',
    ),
    'phpstan/phpstan' => 
    array (
      'pretty_version' => '0.12.81',
      'version' => '0.12.81.0',
      'aliases' => 
      array (
      ),
      'reference' => '0dd5b0ebeff568f7000022ea5f04aa86ad3124b8',
    ),
    'phpstan/phpstan-deprecation-rules' => 
    array (
      'pretty_version' => '0.12.6',
      'version' => '0.12.6.0',
      'aliases' => 
      array (
      ),
      'reference' => '46dbd43c2db973d2876d6653e53f5c2cc3a01fbb',
    ),
    'phpstan/phpstan-nette' => 
    array (
      'pretty_version' => '0.12.16',
      'version' => '0.12.16.0',
      'aliases' => 
      array (
      ),
      'reference' => '7fda19691b464b1f0a3e42fa34c2c363e7404c77',
    ),
    'roave/security-advisories' => 
    array (
      'pretty_version' => 'dev-latest',
      'version' => 'dev-latest',
      'aliases' => 
      array (
        0 => '9999999-dev',
      ),
      'reference' => '640ff0b5dcacc0958534c8c0255b90697f3eb2a8',
    ),
    'slevomat/coding-standard' => 
    array (
      'pretty_version' => '6.4.1',
      'version' => '6.4.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '696dcca217d0c9da2c40d02731526c1e25b65346',
    ),
    'spaze/coding-standard' => 
    array (
      'pretty_version' => 'v0.0.4',
      'version' => '0.0.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'bb3c648761dfd86e7c065eeffbeeafd559ba7ddf',
    ),
    'spaze/csp-config' => 
    array (
      'pretty_version' => 'v2.0.7',
      'version' => '2.0.7.0',
      'aliases' => 
      array (
      ),
      'reference' => '2a0941f4532adcd472a7cd2550a20877c9d49128',
    ),
    'spaze/encryption' => 
    array (
      'pretty_version' => 'v0.3.0',
      'version' => '0.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a7e153ee8b66b83fa0ccf0378c10dd3e5e9ce7db',
    ),
    'spaze/feed-exports' => 
    array (
      'pretty_version' => 'v0.2.2',
      'version' => '0.2.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '68aee874527eca4c300f2681e3878e182c3eeeb4',
    ),
    'spaze/michalspacek.cz' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
      ),
      'reference' => '5bcad8a43c5a3f8f34cd78a24c3d944189faa799',
    ),
    'spaze/mysql-session-handler' => 
    array (
      'pretty_version' => 'v2.1.3',
      'version' => '2.1.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '6f5fe5bf2fff6ebf4b213e058100c11af886c651',
    ),
    'spaze/netxten' => 
    array (
      'pretty_version' => 'v0.13.0',
      'version' => '0.13.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e23ca62abb5352efb10c6d8089602240933975e0',
    ),
    'spaze/nonce-generator' => 
    array (
      'pretty_version' => 'v3.0.2',
      'version' => '3.0.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '68a63070fc7c6e628ba372c0ed0363c7e12ed785',
    ),
    'spaze/phpinfo' => 
    array (
      'pretty_version' => 'v0.1.1',
      'version' => '0.1.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '28f5ede5669615ae809e849cb24fbdd5027df1cf',
    ),
    'spaze/phpstan-disallowed-calls' => 
    array (
      'pretty_version' => 'v1.4.0',
      'version' => '1.4.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '146482f2794609f7d652cf5eda713252c05024b5',
    ),
    'spaze/phpstan-disallowed-calls-nette' => 
    array (
      'pretty_version' => 'v1.0.0',
      'version' => '1.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '299f0a133d7cd2607be6f3fc84f0f1175c68f6a9',
    ),
    'spaze/sri-macros' => 
    array (
      'pretty_version' => 'v0.3.2',
      'version' => '0.3.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd91f4e0b8e3541da2c930793eed9b558f2aba5cc',
    ),
    'squizlabs/php_codesniffer' => 
    array (
      'pretty_version' => '3.5.8',
      'version' => '3.5.8.0',
      'aliases' => 
      array (
      ),
      'reference' => '9d583721a7157ee997f235f327de038e7ea6dac4',
    ),
    'symfony/config' => 
    array (
      'pretty_version' => 'v5.2.3',
      'version' => '5.2.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '50e0e1314a3b2609d32b6a5a0d0fb5342494c4ab',
    ),
    'symfony/deprecation-contracts' => 
    array (
      'pretty_version' => 'v2.2.0',
      'version' => '2.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '5fa56b4074d1ae755beb55617ddafe6f5d78f665',
    ),
    'symfony/filesystem' => 
    array (
      'pretty_version' => 'v5.2.3',
      'version' => '5.2.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '262d033b57c73e8b59cd6e68a45c528318b15038',
    ),
    'symfony/polyfill-ctype' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'symfony/polyfill-mbstring' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'symfony/polyfill-php73' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'symfony/polyfill-php80' => 
    array (
      'pretty_version' => 'v1.22.1',
      'version' => '1.22.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'dc3063ba22c2a1fd2f45ed856374d79114998f91',
    ),
    'symfony/translation' => 
    array (
      'pretty_version' => 'v5.2.3',
      'version' => '5.2.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'c021864d4354ee55160ddcfd31dc477a1bc77949',
    ),
    'symfony/translation-contracts' => 
    array (
      'pretty_version' => 'v2.3.0',
      'version' => '2.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e2eaa60b558f26a4b0354e1bbb25636efaaad105',
    ),
    'symfony/translation-implementation' => 
    array (
      'provided' => 
      array (
        0 => '2.0',
      ),
    ),
    'texy/texy' => 
    array (
      'pretty_version' => 'v3.1.4',
      'version' => '3.1.4.0',
      'aliases' => 
      array (
      ),
      'reference' => '5f84b1f629eb0442431e86a5846d47a72177de32',
    ),
    'tracy/tracy' => 
    array (
      'pretty_version' => 'v2.8.3',
      'version' => '2.8.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '342674bbf72365e8456de9855a8cd839ca695933',
    ),
  ),
);
private static $canGetVendors;
private static $installedByVendor = array();







public static function getInstalledPackages()
{
$packages = array();
foreach (self::getInstalled() as $installed) {
$packages[] = array_keys($installed['versions']);
}


if (1 === \count($packages)) {
return $packages[0];
}

return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
}









public static function isInstalled($packageName)
{
foreach (self::getInstalled() as $installed) {
if (isset($installed['versions'][$packageName])) {
return true;
}
}

return false;
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

$ranges = array();
if (isset($installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = $installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['version'])) {
return null;
}

return $installed['versions'][$packageName]['version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getPrettyVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return $installed['versions'][$packageName]['pretty_version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getReference($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['reference'])) {
return null;
}

return $installed['versions'][$packageName]['reference'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getRootPackage()
{
$installed = self::getInstalled();

return $installed[0]['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
self::$installedByVendor = array();
}




private static function getInstalled()
{
if (null === self::$canGetVendors) {
self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
}

$installed = array();

if (self::$canGetVendors) {

foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
if (isset(self::$installedByVendor[$vendorDir])) {
$installed[] = self::$installedByVendor[$vendorDir];
} elseif (is_file($vendorDir.'/composer/installed.php')) {
$installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
}
}
}

$installed[] = self::$installed;

return $installed;
}
}
