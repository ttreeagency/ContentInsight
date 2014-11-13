Ttree Content Insight
=====================

This TYPO3 Flow package provider a CLI tools to extract Content Inventory CSV from existing website. This package is
under develpment and considered alpha. Use it at your own risk. If your are intersted to contribute to the project,
your are really welcome.

Features
--------

* Extract website structure and basic meta data
* Support crawling presets

Todos
-----

* Sort result based on document tree level
* Crawler advanced configuration (skipping path, URI filtering, ...)
* Build CSV / XLS / Google Spreadsheet report based on extracted data
* Update report / multiple index support
* Get analytics data from Google Analytics

Configuration
-------------

Check the ``Configuration/Settings.yaml`` for detailed configurations. 

By default, this package cache all Raw HTTP request for one day. You can change this settings in you own 
``Settings.yaml`` and ``Caches.yaml``.

Base Preset
-----------

The base preset is automatically merged with all preset. You can enabled or disabled any property with the settings 
``presets.[preset_name].properties.[property_name].enabled``.

```yaml
Ttree:
  ContentInsight:
    presets:
      '*':
        properties:
          'page_title':
            enabled: TRUE
          'navigation_title':
            enabled: TRUE
```

Custom Preset
-----------

You can define custom preset to crawle different kind of informations. With the ``class`` setting you can use your
own processor implementation to get information from the current URI. Your processor must implement 
``Ttree\ContentInsight\CrawlerProcessor\ProcessorInterface``:

```yaml
Ttree:
  ContentInsight:
    presets:
      'custom':
        properties:
          'page_title':
            class: 'Your\Package\CrawlerProcessor\PageTitleProcessor'
          'meta_description':
            enabled: TRUE
          'meta_keywords':
            enabled: TRUE
          'first_level_header':
            enabled: TRUE
```

Usage
-----

To get the complete website inventory:

```
# flow contentinventor:extract --base-url http://www.domain.com
```

Or to limit the crawler to a part of the website

```
# flow contentinventor:extract --base-url http://www.domain.com/products
```

You can select a crawling presets

```
# flow contentinventor:extract --base-url http://www.domain.com/products --preset default
```

