# Web Resources Plugin for GLPI
[![CodeFactor](https://www.codefactor.io/repository/github/cconard96/glpi-webresources-plugin/badge)](https://www.codefactor.io/repository/github/cconard96/glpi-webresources-plugin)

Adds a dashboard for web resources.
Resources can be scoped to specific Entities, Profiles, Groups, or Users (or a mix).
Resources can have an image icon (favicon for example), or a FontAwesome icon like 'fab fa-github".
Non-image icons can have their colors changed as you see fit.

![Dashboard](https://raw.githubusercontent.com/cconard96/glpi-webresources-plugin/master/screenshots/Dashboard.png)

Resources can be any weblink or a link with a special URI scheme. For example these links are all valid:
 - https://glpi-project.org (Standard URL)
 - market://details?id=org.glpi.inventory.agent&hl=en_US (Link to app on Android's Play Store)
 - softwarecenter://Page=AvailableSoftware (Link to the Available Software page in the SCCM/MEM Software Center)
For more information about URI schemes please refer to https://en.wikipedia.org/wiki/List_of_URI_schemes.

If you want to try automatically getting an icon for a URL, you should make sure the `ext-dom` extension for PHP is installed and loaded. Otherwise, the plugin will try to fallback to 'DOMAIN/favicon.ico'.
## How to use
Please refer to the [Wiki](https://github.com/cconard96/glpi-webresources-plugin/wiki/Quick-Start) for a Quick Start guide.

## Locale Support
- Contribute to existing localizations on [POEditor](https://poeditor.com/join/project?hash=H4Yugw8tw6).
- To request new languages, please open a GitHub issue.

## Version Support

Multiple versions of this plugin are supported at the same time to ease migration.
Only 2 major versions will be supported at the same time (Ex: v1 and v2).
When a new minor version is released, the previous minor version will have support ended after a month.
Only the latest bug fix version of each minor release will be supported.

Note: There was no official version support policy before 2022-05-19.
The following version table may be reduced based on the policy stated above.

| Plugin Version | GLPI Versions | Start of Support | End of Support |
|----------------|---------------|------------------|----------------|
| 1.0.0          | 9.5.X         | 2020-07-19       | 2022-05-19     |
| 1.1.0          | 9.5.X         | 2020-07-25       | 2022-05-19     |
| 1.2.0          | 9.5.X         | 2020-08-02       | 2022-05-19     |
| 1.3.2          | 9.5.X         | 2021-02-13       | In Support     |
| 2.0.1          | 10.0.X        | 2022-05-20       | In Support     |
