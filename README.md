# cakephp-translator
A translation plugin that allows multiple possible domains to be checked for a translation, automatic translations based on the controller and action.

```
// config/bootstrap.php
ini_set('intl.default_locale', 'fr_FR');
```

```
sudo bash -c "( rm -rf logs/quality ; for dir in logs tmp; do find $dir -type f ! -name 'empty' -exec rm {} \;; done )"
sudo -u apache ant quality -f plugins/Translator/vendor/Jenkins/build.xml
```