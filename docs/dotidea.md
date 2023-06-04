Add the following lines to files mentioned:

### *Unchecked Exceptions* in `.idea/php.xml`
```xml
<project version="4">
  <!-- Some COMPONENT tags here -->
  <component name="PhpAnalysisConfiguration">
    <unchecked_exceptions>
      <fqn value="\Contributte\Translation\Exceptions\InvalidArgument" />
      <fqn value="\Error" />
      <fqn value="\LogicException" />
      <fqn value="\MichalSpacekCz\ShouldNotHappenException" />
      <fqn value="\Nette\Application\AbortException" />
      <fqn value="\Nette\Application\BadRequestException" />
      <fqn value="\Psr\Cache\InvalidArgumentException" />
      <fqn value="\RuntimeException" />
    </unchecked_exceptions>
  </component>
  <!-- Some more COMPONENT tags here -->
</project>
```

### *Entry Points* in `.idea/misc.xml`
```xml
<?xml version="1.0" encoding="UTF-8"?>
<project version="4">
  <!-- Some COMPONENT tags here -->
  <component name="PhpEntryPointsManager">
    <pattern value="\*Presenter" member="action*()" />
    <pattern value="\*Presenter" member="render*()" />
    <pattern value="\*Presenter" member="createComponent*()" />
    <pattern value="\*Presenter" member="inject*()" />
    <pattern value="\*Presenter" member="handle*()" />
    <pattern value="\*Test" member="test*()" />
  </component>
  <!-- Some more COMPONENT tags here -->
</project>
```
