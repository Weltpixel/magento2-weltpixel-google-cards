# magento2-weltpixel-google-cards

### Installation

Dependencies:
- magento2-weltpixel-backend (see https://github.com/Weltpixel/magento2-weltpixel-backend)

With composer:

```sh
$ composer config repositories.weltpixel-magento2-weltpixel-backend git https://github.com/Weltpixel/magento2-weltpixel-backend.git
$ composer require weltpixel/magento2-weltpixel-backend:dev-master

$ composer config repositories.weltpixel-magento2-weltpixel-google-cards git https://github.com/Weltpixel/magento2-weltpixel-google-cards.git
$ composer require weltpixel/magento2-weltpixel-google-cards:dev-master
```

Manually:

Important: Ensure you also install the shared Backend module. If it's already installed, you can skip this. Details in the repo at https://github.com/Weltpixel/magento2-weltpixel-backend.

Copy the zip into the app/code/WeltPixel/GoogleCards directory


#### After installation by either means, enable the extension by running following commands:

```sh
$ php bin/magento module:enable WeltPixel_GoogleCards --clear-static-content
$ php bin/magento setup:upgrade
```

### Documentation

For detailed documentation, please visit: https://weltpixel.com/resources/ModuleDoc/Magento2/RichSnippets/User-Guide-WeltPixel-Rich-Snippets-Schema.html
