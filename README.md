Ttree Content Insight
=====================

This TYPO3 Flow package provider a CLI tools to extract Content Inventory CSV from existing website. This package is
under develpment and considered alpha. Use it at your own risk. If your are intersted to contribute to the project,
your are really welcome.

Features
--------

* Extract website structure and basic meta data
* Support crawling presets
* Flexible report building (include a CSV report builder, but you can register your own report builder)
* Skip URI with regular expression
* Sort inventory based on document tree structure

Todos
-----

* Generate human readable page ID (like, 1, 1.1, 1.2, 2, 2.1, 2.2, ...)
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

How to build a report ?
-----------------------

The package support CSV reporting, but you can register your own Report builder. Check the ``Settings.yaml``:

```yaml
Ttree:
  ContentInsight:
    presets:
      'custom':
		reportConfigurations:
          'csv':
            enabled: TRUE
            renderType: 'Csv'
            renderTypeOptions:
              displayColumnHeaders: TRUE
            reportPath: '%FLOW_PATH_DATA%Reports/Ttree.ContentInsight'
            reportPrefix: 'content-inventory-report'
            properties:
              'id':
                label: 'ID'
              'page_title':
                label: 'Page Title'
              'navigation_title':
                label: 'Navigation Title'
              'external_link':
                label: 'External Link'
                postProcessor: 'Boolean'
              'current_uri':
                label: 'URL'
              'meta_description':
                label: 'Meta Description'
              'meta_keywords':
                label: 'Meta Keywords'
              'first_level_header_count':
                label: 'Main Header Count (H1)'
              'first_level_header_content':
                label: 'Main Header Content (H1)'
              'remark':
                label: 'Crawling Remark'
```

The keys in the ``properties`` section must match the key produced by the ``CrawlerProcessor`` object.

For a single crawling preset you can register multiple reports if required. Foreach property you can register a post 
processor if you need to manipulate the property in the report, see ``BooleanPostProcessor`` for a basic example.

How to skip specific URI ?
--------------------------

You can define invalid URIs patterns in your crawling presets:

```yaml
Ttree:
  ContentInsight:
    presets:
      'custom':
        invalidUriPatterns:
          'javascript':
            pattern: '@^javascript\:void\(0\)$@'
          'mailto':
            pattern: '@^mailto\:.*@'
          'anchor':
            pattern: '@^#.*@'
            message: 'Link to anchor'
```

If the pattern has a ``message`` all URL matching the pattern will be logged. By default the crawler skip 
those URLs silently.

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

