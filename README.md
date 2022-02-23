# Pimcore DeepL

DeepL works with neural networks in translation and this results in translations of the highest quality. It is possible to translate individual small modules or texts on the fly or to have entire pages, individual content elements or even product data and similar data objects translated into another language.

With DeepL the following languages can currently be translated:
* German
* English
* French
* Spanish
* Portuguese
* Italian
* Dutch
* Polish
* Russian
* Japanese
* Chinese (simplified)

You can find more informations here: https://www.deepl.com/de/whydeepl

## Prerequisites
* PHP 7.1 or higher (https://secure.php.net/)
* Composer (https://getcomposer.org/download/)
* A Pimcore  Installation (Pimcore X)
* DeepL account 


## Installation

```bash
composer require agorate/pimcore-deepl
``` 

## Configuration

just enable the bundle in the pimcore extension manager and add the deepl key in your config.yaml like this:

```
pimcore_deepl:
    deepl_auth_key: 'YOUR_KEY'
```



