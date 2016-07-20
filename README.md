# cakephp-translator
A translation plugin that allows multiple possible domains to be checked for a translation, automatic translations based on the controller and action.

## Setup

Assuming the plugin is installed under plugins/Translator.

Add the following to config/bootstrap.php:
```
    Plugin::load('Translator', ['autoload' => true]);
    // or to get the shortcut __m(), __mn(), __mx() and __mxn() functions
    Plugin::load('Translator', ['autoload' => true, 'bootstrap' => true]);

```

And set the following in config/app.php
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
Default: '\\Translator\\Utility\\Translator'

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

namespace App\Utility;

use Cake\I18n\I18n;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Translator\Utility\Storage;
use Translator\Utility\TranslatorInterface;

class Translator extends \Translator\Utility\Translator implements TranslatorInterface
{
    public static function path($key, $tokens)
    {
        $instance = self::getInstance();
        $key = (string)$key;

        $params = [
            '_count' => isset($tokens['_count']) ? $tokens['_count'] : null,
            '_singular' => isset($tokens['_singular']) ? $tokens['_singular'] : null,
            '_context' => isset($tokens['_context']) ? $tokens['_context'] : null
        ];

        return [$instance::lang(), $instance::$_domainsKey, serialize(Hash::filter($params)), $key];
    }

    /**
     * Translates the message indicated by they key, replacing token values
     * along the way.
     *
     * @see I18n::translate()
     *
     * @param string $key The message key.
     * @param array $tokens Token values to interpolate into the
     * message.
     * @return string The translated message with tokens replaced.
     */
    public static function translate($key, array $tokens = [])
    {
        $instance = self::getInstance();
        $key = (string)$key;

        $path = $instance::path($key, $tokens);
        if (Storage::exists($instance::$_cache, $path)) {
            $message = Storage::get($instance::$_cache, $path);
        } else {
            // FIXME: $tokens! with parent
            $message = parent::translate($key, $tokens);

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
