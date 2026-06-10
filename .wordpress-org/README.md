# WordPress.org Directory Assets

Files in this folder are synced to the `assets/` subdirectory of the plugin's
SVN repo on every tag deploy. They populate the plugin's listing page at
https://wordpress.org/plugins/odr-image-optimizer/ and are **not** shipped
inside the plugin zip itself.

Drop the following files here when you have them:

| File                   | Required dimensions   | Where it shows up                      |
| ---------------------- | --------------------- | -------------------------------------- |
| `icon-128x128.png`     | 128 × 128             | Search results, small thumbnails       |
| `icon-256x256.png`     | 256 × 256             | Plugin install dialog, retina displays |
| `banner-772x250.png`   | 772 × 250             | Top of plugin listing page             |
| `banner-1544x500.png`  | 1544 × 500            | Same, retina                           |
| `screenshot-1.png`     | typically 1280 × 800  | Screenshot section, in order           |
| `screenshot-2.png`     | etc.                  |                                        |

Screenshots are paired with the captions in `readme.txt` under `== Screenshots ==`
by index — `screenshot-1.png` matches caption #1, etc.

See https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/ for
the canonical specs.
