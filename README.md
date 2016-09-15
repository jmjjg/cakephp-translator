# cakephp-translator
A translation plugin that allows multiple possible domains to be checked for a
translation, automatic translations based on the controller and action.

## Setup

Assuming the plugin is installed under plugins/Translator.

Add the following to config/bootstrap.php:
```
    Plugin::load('Translator', ['autoload' => true]);
    // or to get the shortcut __m(), __mn(), __mx() and __mxn() functions
    Plugin::load('Translator', ['autoload' => true, 'bootstrap' => true]);

```

If you want to use a custom default translator (say a translator utility class in
your app), add the following to config/bootstrap.php:
```
TranslatorsRegistry::defaultTranslator('App.Translator');
```

Set the language in config/app.php
```
'App.defaultLocale' => 'fr_FR'
```

Uses I18n's default formatter (IcuFormatter), so you can set its value with
```
I18n::defaultFormatter('default')
// or
I18n::defaultFormatter('sprintf')
```

Setup domain autoloading in src/Controller/AppController.php
```
use Translator\Controller\Component\TranslatorAutoloadComponent;

class AppController extends Controller
{
    public function initialize()
    {
        parent::initialize();

        $config = [
            // ...
        ];
        $this->loadComponent('Translator.TranslatorAutoload', $config);
    }
}
```

### Config keys

#### translatorClass
The translatorClass needs to implement the Translator\Utility\TranslatorInterface.
Default: null (see TranslatorsRegistry::defaultTranslator())

#### cache
Wether or not to use merged translations caching.
Default: true

#### events

To get the translations available anywhere in the controller and in the
view and saved before redirection or after rendering (the default):
```
 'events' => [
     'Controller.initialize' => 'load',
     'Controller.startup' => null,
     'Controller.beforeRender' => null,
     'Controller.beforeRedirect' => 'save',
     'Controller.shutdown' => 'save'
]
```

To get the translations available only in the view and saved after
rendering:
```
'events' => [
     'Controller.initialize' => null,
     'Controller.startup' => null,
     'Controller.beforeRender' => 'load',
     'Controller.beforeRedirect' => 'save',
     'Controller.shutdown' => 'save'
]
```

Available events:
 - Controller.initialize (Component.beforeFilter)
 - Controller.startup (Component.startup)
 - Controller.beforeRender (Component.beforeRender)
 - Controller.beforeRedirect (Component.beforeRedirect)
 - Controller.shutdown (Component.beforeRender)

## Usage in locales and in views

### In src/Locale/fr_FR/groups.po
    msgid "name"
    msgstr "Nom"

### In src/Template/Groups/index.ctp
    use Translator\Utility\Translator;
    echo __m('name');

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
        $cells[$path]['label'] = __m($path);
    }
    elseif (true === $isLink) {
        if (false === isset($cell['text'])) {
            $cells[$path]['text'] = __m($path);
        }

        $title = false === isset($cell['title']) || in_array($cell['title'], [null, true], true);
        $confirm = true === isset($cell['confirm']) && true === $cell['confirm'];

        if ($title || $confirm) {
            $data = Url::parse($path);
            $actionMiddle = Inflector::singularize($data['action']);
            $actionStart = mb_convert_case($actionMiddle, MB_CASE_TITLE);
            $entity = mb_convert_case(Inflector::singularize($data['controller']), MB_CASE_LOWER);
            if (true === $title) {
                $cells[$path]['title'] = __m("{$actionStart} {$entity} « {{name}} » (#{{id}})");
            }
            if (true === $confirm) {
                $cells[$path]['confirm'] = __m("Really {$actionMiddle} {$entity} « {{name}} » (#{{id}})?");
            }
        }
    }
}
```

## Sample extended classes

### Translator Utility
```
namespace App\Utility;

use Cake\I18n\I18n;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Translator\Utility\Storage;
use Translator\Utility\TranslatorInterface;

class Translator extends \Translator\Utility\Translator implements TranslatorInterface
{
    public static function translate($key, array $tokens = [])
    {
        $instance = self::getInstance();
        $key = (string)$key;

        $path = $instance::path($key, $tokens);
        if (Storage::exists($instance::$_cache, $path)) {
            $message = Storage::get($instance::$_cache, $path);
        } else {
            // TODO: $tokens! with parent (unit tests)
            $message = parent::translate($key, json_decode($path[2]));

            if ($message === $key) {
                $tokens = explode('.', $message);
                if (count($tokens)>=2) {
                    $domain = Inflector::underscore($tokens[count($tokens)-2]);
                    $message = I18n::translator($domain)->translate($key);
                }
            }

            $instance::$_cache = Storage::insert($instance::$_cache, $path, $message);
            $instance::$_tainted = true;
        }

        // C/P from CakePHP's Translator::translate()
        // are there token replacement values?
        if (! $tokens) {
            // no, return the message string as-is
            return $message;
        }

        // run message string through I18n default formatter to replace tokens with values
        return $instance::$_formatters->get(I18n::defaultFormatter())->format($instance::lang(), $message, $tokens);
    }
}
```

### Translator Helper
```
namespace App\View\Helper;

use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use Helpers\Utility\Url;
use Translator\Utility\TranslatorsRegistry;

/**
 * The TranslatorHelper makes a bridge between the Translator plugin and the
 * Helpers plugin.
 */
class TranslatorHelper extends Helper
{
    protected $_translators = [];

    public function translator($name = null)
    {
        $className = null === $name ? TranslatorsRegistry::defaultTranslator() : $name;

        if (false === isset($this->_translators[$className])) {
            $this->_translators[$className] = TranslatorsRegistry::getInstance()->get($className);
        }

        return $this->_translators[$className];
    }

    public function params(array $params = [])
    {
        return $params + ['name' => null];
    }

    public function label($path, array $cell = [], array $params = [])
    {
        if (false === isset($cell['label'])) {
            $params = $this->params($params);
            $translator = $this->translator($params['name']);

            $cell['label'] = $translator->translate($path);
        }

        return $cell;
    }

    public function parse($path)
    {
        $data = Url::parse($path);

        $result = ['entity' => mb_convert_case(Inflector::singularize($data['controller']), MB_CASE_LOWER)];
        $result['action']['middle'] = Inflector::singularize($data['action']);
        $result['action']['start'] = mb_convert_case($result['action']['middle'], MB_CASE_TITLE);

        return $result;
    }

    public function action($path, array $cell = [], array $params = [])
    {
        $params = $this->params($params);
        $translator = $this->translator($params['name']);

        if (false === isset($cell['text'])) {
            $cell['text'] = $translator->translate($path);
        }

        $title = false === isset($cell['title']) || in_array($cell['title'], [null, true], true);
        $confirm = true === isset($cell['confirm']) && true === $cell['confirm'];

        if ($title || $confirm) {
            $parsed = $this->parse($path);

            if (true === $title) {
                $singular = "{$parsed['action']['start']} {$parsed['entity']} « {{name}} » (#{{id}})";
                $cell['title'] = $translator->translate($singular);
            }
            if (true === $confirm) {
                $singular = "Really {$parsed['action']['middle']} {$parsed['entity']} « {{name}} » (#{{id}})?";
                $cell['confirm'] = $translator->translate($singular);
            }
        }

        return $cell;
    }

    const TYPE_ACTION = 'action';

    const TYPE_LABEL = 'label';

    public function type($path)
    {
        if ('/' === $path[0]) {
            return self::TYPE_ACTION;
        }

        return self::TYPE_LABEL;
    }

    public function index(array $cells, array $params = [])
    {
        $params = $this->params($params);
        $translator = $this->translator($params['name']);
        $cells = Hash::normalize($cells);

        foreach ($cells as $path => $cell) {
            $type = $this->type($path);

            if (self::TYPE_ACTION === $type) {
                $cells[$path] = $this->action($path, (array)$cell, $params);
            } else {
                $cells[$path] = $this->label($path, (array)$cell, $params);
            }
        }

        return $cells;
    }
}
```