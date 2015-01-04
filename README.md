Ttree Content Insight
=====================

[![Build Status](http://gitlab.ttree.ch:8080/buildStatus/icon?job=OSS ContentInsight Master Commit)](http://gitlab.ttree.ch:8080/job/OSS%20ContentInsight%20Master%20Commit/) [![Total Downloads](https://poser.pugx.org/ttree/contentinsight/downloads.png)](https://packagist.org/packages/ttree/contentinsight)


This TYPO3 Flow package provider a CLI tools to extract Content Inventory CSV from existing website. 

This package is under development and considered beta. This package require Flow 2.3.

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
          'pageTitle':
            enabled: TRUE
          'navigationTitle':
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
          'pageTitle':
            class: 'Your\Package\CrawlerProcessor\PageTitleProcessor'
          'metaDescription':
            enabled: TRUE
          'metaKeywords':
            enabled: TRUE
          'firstLevelHeader':
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
              'pageTitle':
                label: 'Page Title'
              'navigationTitle':
                label: 'Navigation Title'
              'externalLink':
                label: 'External Link'
                postProcessor: 'Boolean'
              'currentUri':
                label: 'URL'
              'metaDescription':
                label: 'Meta Description'
              'metaKeywords':
                label: 'Meta Keywords'
              'firstLevelHeaderCount':
                label: 'Main Header Count (H1)'
              'firstLevelHeaderContent':
                label: 'Main Header Content (H1)'
              'remark':
                label: 'Crawling Remark'
```

The keys in the ``properties`` section must match the key produced by the ``CrawlerProcessor`` object.

The position of each column could be specified with the following syntax : ``position: '<position-string>'``
The ``<position-string>`` supports one of the following syntax:

```
    start (<weight>)
    end (<weight>)
    before <key> (<weight>)
    after <key> (<weight>)
    <numerical-order>
```

### Example

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
                position: '<position-string>',
              'pageTitle':
                label: 'Page Title'
                position:'<position-string>'
```

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

