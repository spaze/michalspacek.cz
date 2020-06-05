<?php
header('HTTP/1.1 503 Service Unavailable');
header('Retry-After: 300'); // 5 minutes in seconds
?>
<!DOCTYPE html>
<meta charset="utf-8">
<meta name="robots" content="noindex">
<style>
	body { color: #222; background-color: #EEE; width: 500px; margin: 0px auto; font: 1em/1.5 Arial, sans-serif; }
	h1 { margin: .6em 0 }
	p { margin: 1.5em 0 }
</style>
<title>Site is temporarily down for maintenance</title>
<h1>I'm Sorry</h1>
<p>The site is temporarily down for maintenance.<br>Will be back shortly, please try again in a few minutes.</p>
<?php
exit;
