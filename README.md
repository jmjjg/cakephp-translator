# cakephp-translator
A translation plugin that allows multiple possible domains to be checked for a translation, automatic translations based on the controller and action.

```
// config/bootstrap.php
ini_set('intl.default_locale', 'fr_FR');
```

```
sudo bash -c "( sudo bin/cake orm_cache clear ; rm logs/*.log ; rm -r logs/quality ; find tmp -type f ! -name 'empty' -exec rm {} \; )"
```