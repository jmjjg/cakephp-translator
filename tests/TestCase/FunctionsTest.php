<?php
/**
 * Source code for the FunctionsTest unit test class from the Translator CakePHP 3 plugin.
 *
 * @author Christian Buffin
 */
namespace Translator\Test\TestCase;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;
use Translator\Utility\Translator;

/**
 * The FunctionsTest class unit tests the shortcut utility functions from the
 * Translator plugin.
 */
class FunctionsTest extends TestCase
{
    protected $defaultLocale = null;

    protected $locales = null;

    /**
     * Prepare before test method.
     */
    public function setUp()
    {
        parent::setUp();

        $this->locales = Configure::read('App.paths.locales');
        $locales = Plugin::classPath('Translator') . DS . '..' . DS . 'tests' . DS . 'Locale' . DS;
        Configure::write('App.paths.locales', $locales);

        $this->defaultLocale = Configure::read('App.defaultLocale');
        Configure::write('App.defaultLocale', 'fr_FR');
    }

    /**
     * Cleanup after test method.
     */
    public function tearDown()
    {
        parent::tearDown();
        Configure::write('App.paths.locales', $this->locales);
        Configure::write('App.defaultLocale', $this->defaultLocale);
        Translator::reset();
    }

    /**
     * Test the __m() function.
     *
     * @covers \__m
     */
    public function testM()
    {
        Translator::domains(['groups_index', 'groups', 'default']);
        $this->assertNull(__m(null));
        $this->assertEquals('Voir', __m('/Groups/view/{{id}}'));
    }

    /**
     * Test the __mn() function.
     *
     * @covers \__mn
     */
    public function testMn()
    {
        Translator::domains(['groups_index', 'groups', 'default']);
        $this->assertNull(__mn(null, 'horses', 1));
        $this->assertEquals('cheval', __mn('horse', 'horses', 1));
        $this->assertEquals('chevaux', __mn('horse', 'horses', 2));
    }

    /**
     * Test the __mx() function.
     *
     * @covers \__mx
     */
    public function testMx()
    {
        Translator::domains(['groups_index', 'groups', 'default']);
        $this->assertNull(__mx('context1', null));
        $this->assertEquals('X', __mx('context1', 'X'));
        $this->assertEquals('La valeur X', __mx('context2', 'X'));
    }

    /**
     * Test the __mxn() function.
     *
     * @covers \__mxn
     */
    public function testMxn()
    {
        Translator::domains(['groups_index', 'groups', 'default']);
        $this->assertNull(__mxn('context1', null, 'horses', 1));
        $this->assertEquals('cheval', __mxn('context1', 'horse', 'horses', 1));
        $this->assertEquals('chevaux', __mxn('context1', 'horse', 'horses', 2));

        $this->assertNull(__mxn('context2', null, 'horses', 1));
        $this->assertEquals('rosse', __mxn('context2', 'horse', 'horses', 1));
        $this->assertEquals('rosses', __mxn('context2', 'horse', 'horses', 2));
    }
}
