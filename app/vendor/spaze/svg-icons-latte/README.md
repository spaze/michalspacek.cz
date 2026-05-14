# SVG Icons Custom Tag for Latte Templating System

The custom `{icon}` tag will embed an SVG icon loaded from an external file.

## Installation
```
composer require spaze/svg-icons-latte
```
Requires PHP 8.1+

Register the Nette extension, e.g.:
```neon
extensions:
	svgIcons: Spaze\SvgIcons\NetteExtension
```
This will also auto-register the Latte extension itself.

## Configuration

```neon
svgIcons:
	iconsDir: '../../node_modules/humbleicons/icons'
	cssClass: 'humbleicons'
```
- `iconsDir` is a path to a directory with SVG icon files, for example [Humbleicons](https://humbleicons.com/) by [@Zraly](https://twitter.com/zraly) (required)
- `cssClass` defines a CSS class that will be added to the root `<svg>` element (optional)

## Usage in Latte
`{icon wifi}` will be replaced with the contents loaded from `wifi.svg` located in `iconsDir`.

You can also add extra CSS classes:
`{icon wifi class => foo, class => bar}` will add additional CSS classes `foo` and `bar` to the root `<svg>` element.

Given the configuration above, the resulting tag would look like `<svg class="humbleicons foo bar" ...>`.

## Usage in PHP

Use `Spaze\SvgIcons\SvgIcons::getSvg(string $icon): string` to get the icon SVG in PHP,
if you for example want to embed the SVG in an email message where Latte is not available.
Pass the icon name without the `.svg` extension as the `$icon` parameter, for example `wifi`, not `wifi.svg`.

The `SvgIcons` class uses the directory set in configuration and appends `.svg` to load the file contents.
