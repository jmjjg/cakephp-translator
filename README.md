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

## Sample usage in a view
```
use Cake\Utility\Inflector;
use Helpers\Utility\Url;

$cells = Hash::normalize($cells);
foreach ($cells as $path => $cell) {
    $isLink = $path[0] === '/';

    if (false === $isLink && false === isset($cell['label'])) {
        $cells[$path]['label'] = Translator::__($path);
    }
    elseif (true === $isLink) {
        if (false === isset($cell['text'])) {
            $cells[$path]['text'] = Translator::__($path);
        }

        $title = false === isset($cell['title']) || in_array($cell['title'], [null, true], true);
        $confirm = true === isset($cell['confirm']) && true === $cell['confirm'];

        if ($title || $confirm) {
            $data = Url::parse($path);
            $actionMiddle = Inflector::singularize($data['action']);
            $actionStart = mb_convert_case($actionMiddle, MB_CASE_TITLE);
            $entity = mb_convert_case(Inflector::singularize($data['controller']), MB_CASE_LOWER);
            if (true === $title) {
                $cells[$path]['title'] = Translator::__("{$actionStart} {$entity} « {{name}} » (#{{id}})");
            }
            if (true === $confirm) {
                $cells[$path]['confirm'] = Translator::__("Really {$actionMiddle} {$entity} « {{name}} » (#{{id}})?");
            }
        }
    }
}
```