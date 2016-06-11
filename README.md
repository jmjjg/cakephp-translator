# cakephp-translator
A translation plugin that allows multiple possible domains to be checked for a translation, automatic translations based on the controller and action.

## Setup

Assuming the plugin is installed under plugins/Translator.

Add the following to config/bootstrap.php:
```
    Plugin::load('Translator', ['autoload' => true]);

```

And set the following in config/app.php
```
'App.defaultLocale' => 'fr_FR'
```

Setup domain autoloading in src/Controller/AppController.php
```
use Translator\Controller\Component\TranslatorAutoloadComponent;

class AppController extends Controller
{
    public function initialize()
    {
        parent::initialize();

        // ...
        $this->loadComponent('Translator.TranslatorAutoload');
    }
}
```

## Usage in locales and in views

### In src/Locale/fr_FR/groups.po
    msgid "name"
    msgstr "Nom"

### In src/Template/Groups/index.ctp
    use Translator\Utility\Translator;
    echo Translator::__('name');

## Various bash commands
```
# Clear cache (FIXME: svn/git ?)
sudo bash -c "( rm -rf logs/quality ; find . -type f -regex '^\./\(logs\|tmp\)/.*' ! -name 'empty' -exec rm {} \; )"
# Build the plugin
sudo -u apache ant quality -f plugins/Translator/vendor/Jenkins/build.xml
```