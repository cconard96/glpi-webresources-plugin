# Web Resources Plugin for GLPI Changelog

## [2.0.0]

### Added
- Added web resource dashboard to Central (homepage) as a tab
- List view for resources
- Improved dark theme support

## [1.3.2]

### Fixed (Backend)
- Added GitHub action to automatically build a release when a tag is pushed. This is purely a timesaver for me when preparing a release and to help prevent mistakes.
- Set up localization on POEditor for English, English (US), French, Portuguese, and Spanish languages and added the required files in the repository. There are no actual translations for non-English languages yet.
- Fixed the license specified in the setup.php file which showed "GPL v2" even though the project has licensed under "GPL v2+".
- Added check in the plugin initialization to only execute most of it like loading resources when the plugin is installed and active.

## [1.3.1]

### Added
- Added config option for moving the Web Resources menu item to a different menu than Plugins to clean up the menu if nothing else is in there.

### Fixed
- Fixed PHP error when updating other Entity tab forms.
- Improved dark mode support
- Fixed issue where the User for a resource wasn't being set.
- Improved icon scaling for non-square images.

## [1.3.0]

### Added
- Added dashboard contexts including the existing custom web resources and dynamic contexts based on existing GLPI items such as Entities, Appliances, and Suppliers. Only those items which have websites/URLs specified will be shown in the dashboard.
- Added search to dashboards
- Added fallback favicon services (Currently used for dynamic dashboards only) which include DuckDuckGo and Google which are disabled by default. It is not recommended that you enable these unless you cannot get the icons dynamically from the websites directly and it is not feasible to provide a static FA icon or icon URL. These services typically provide only low-resolution icons.

## [1.2.0]

### Added
- Added a fallback to a generic icon when an image icon cannot be loaded. For example, when a favicon is used but the resource is not reachable (on a different network or offline).
- Added Add and Remove target massive actions for adding and removing access to multiple resources at once.

## [1.1.0]

### Added
- Added icon preview
- Added auto-fetch of website icons
  - To use this, leave the `icon` field blank when you have a valid URL in the `link` field and then press the `Refresh icon image` button that appears. This will populate the `icon`field with the best quality icon that it found.

## [1.0.0]

### Added
- Initial release
