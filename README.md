# Vebra Alto Wrapper plugin for Craft CMS 4.x

Integration with the estate agency software [Alto](https://altosoftware.co.uk)

## Requirements

This plugin requires Craft CMS 4.0.0-alpha or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require https://github.com/MadeByField/vebra-alto-wrapper

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Vebra Alto Wrapper.

## Vebra Alto Wrapper Overview

This plugin allows you to import properties from Vebra Alto as entries in Craft CMS 4.
read API details here: http://webservices.vebra.com/export/xsd/v12/Client_Feed_API_v12_UserGuide.pdf

## Configuring Vebra Alto Wrapper

First fill in your Vebra Alto API details into the plugin settings

![GitHub Logo](/resources/img/step1.jpg)

## Using Vebra Alto Wrapper

Then select which location you would like to import (this plugin can handle multiple locations) and select which section you want to import the properties to

![GitHub Logo](/resources/img/step2.jpg)

Then choose which fields you want the desired data to go. Please not 'images' and 'brochure' must be an assets field and propertyType must be a categories field containing a 'For Let' and a 'For Sale' category.

The target section must have an unmapped field with the slug `reference` and a mapped field with the slug `webStatus` (mapped to `web_status`).

Recommend [Read-Only Field plugin](https://plugins.craftcms.com/read-only?craft4) for the `reference` field.

Once all links have been saved you can then periodically update properties via a cron job

![GitHub Logo](/resources/img/step3.jpg)

## Vebra Alto Wrapper Roadmap

Some things to do, and ideas for potential features:

* Release it

Originally written by [Luca Jegard](https://github.com/Jegard)

Updated and adapted for Craft CMS 4 by [Dave Speake](https://github.com/MadeByField)