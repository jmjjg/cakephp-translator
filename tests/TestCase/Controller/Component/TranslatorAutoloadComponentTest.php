<?php
/**
 * Source code for the TranslatorAutoloadComponentTest unit test class from the Translator CakePHP 3 plugin.
 *
 * @author Christian Buffin
 */
namespace Translator\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
//use Cake\Controller\Component\PaginatorComponent;
use Cake\Controller\Controller;
//use Cake\Core\Configure;
//use Cake\Datasource\ConnectionManager;
//use Cake\Network\Exception\NotFoundException;
use Cake\Network\Request;
//use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
//use Cake\Utility\Hash;
use Translator\Controller\Component\TranslatorAutoloadComponent;

/**
 * The TranslatorAutoloadComponentTest class unit tests the Translator\Controller\Component\TranslatorAutoloadComponent class.
 */
class TranslatorAutoloadComponentTest extends TestCase
{
    protected $Translator = null;

    public function setUp()
    {
        parent::setUp();

        $this->request = new Request('posts/index');
        $this->request->params = [
            'plugin' => null,
            'controller' => 'posts',
            'action' => 'index',
            '_ext' => null,
            'pass' => []
        ];
        $controller = new Controller($this->request);
        $registry = new ComponentRegistry($controller);
        $this->Translator = new TranslatorAutoloadComponent($registry, []);
    }

    public function tearDown()
    {
        parent::tearDown();
        unset($this->Translator);
    }

    /**
     * Test of the TranslatorAutoloadComponent::domains().
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::domains
     */
    public function testDomains()
    {
        $expected = [
            'posts_index',
            'posts',
            'default'
        ];
        $this->assertEquals($expected, $this->Translator->domains());
    }

    /**
     * Test of the TranslatorAutoloadComponent::cacheKey().
     *
     * @covers Translator\Controller\Component\TranslatorAutoloadComponent::cacheKey
     */
    public function testCacheKey()
    {
        $expected = 'TranslatorAutoload.posts.index';
        $this->assertEquals($expected, $this->Translator->cacheKey());
    }
}
