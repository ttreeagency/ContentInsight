Ttree Content Insight
=====================

This TYPO3 Flow package provider a CLI tools to extract Content Inventory CSV from existing website. This package is
under develpment and considered alpha. Use it at your own risk. If your are intersted to contribute to the project,
your are really welcome.

Features
--------

* DONE Extract website structure and basic meta data
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

