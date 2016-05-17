<?php
/**
 * Source code for the TranslatorTest unit test class from the Translator CakePHP 3 plugin.
 *
 * @author Christian Buffin
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Translator\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Translator\Utility\Translator;

/**
 * The TranslatorTest class unit tests the Translator\Utility\Translator class.
 */
class TranslatorTest extends TestCase
{
    public function setUp()
    {
        // FIXME
//        App::build(array('Locale' => CakePlugin::path('Translator') . 'Test' . DS . 'Locale' . DS), App::PREPEND);
//        debug(App::path('Locale'));
        parent::setUp();
        Configure::write('Config.language', 'fr_FR');
        Translator::reset();
    }

    /**
     * Test of the Translator::lang() method.
     *
     * @covers Translator\Utility\Translator::lang
     */
    public function testLang()
    {
        $result = Translator::lang();
        $expected = 'fr_FR';
        $this->assertEquals($expected, $result);

//        Configure::write('Config.language', 'eng');
//        $result = Translator::lang();
//        $expected = 'eng';
//        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::domains() method.
     *
     * @covers Translator\Utility\Translator::domains
     */
    public function testDomains()
    {
        $domains = 'groups_index';
        $result = Translator::domains((array) $domains);
        $expected = array('groups_index');
        $this->assertEquals($expected, $result);

        $result = Translator::domains();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::__() method.
     *
     * @todo
     *
     * @covers Translator\Utility\Translator::__
     */
    public function testUnderscore()
    {
        Translator::domains('search_plugin');
        $result = Translator::__('Search.Personne.nom');
        $expected = 'Nom';
        $this->assertEquals($expected, $result);

        /*// -----------------------------------------------------------------

        Translator::domains('cg93_contratsinsertion_search');
        $result = Translator::__('Referent.nom_complet');
        $expected = 'Personne établissant le CER';
        $this->assertEquals($expected, $result);

        // -----------------------------------------------------------------

        Translator::domains('contratsinsertion_search');
        $result = Translator::__('Referent.nom_complet');
        $expected = 'Référent lié';
        $this->assertEquals($expected, $result);

        // -----------------------------------------------------------------

        Translator::domains('contratsinsertion');
        $result = Translator::__('Referent.nom_complet');
        $expected = 'Referent.nom_complet';
        $this->assertEquals($expected, $result);

        // -----------------------------------------------------------------

        Translator::domains('referent');
        $result = Translator::__('Referent.nom_complet');
        $expected = 'Nom du prescripteur';
        $this->assertEquals($expected, $result);

        // -----------------------------------------------------------------

        Translator::domains(array('cg93_contratsinsertion_search', 'contratsinsertion_search', 'contratsinsertion', 'referent'));
        for ($i = 0; $i < 2; $i++) {
            $result = Translator::__('Referent.nom_complet');
            $expected = 'Personne établissant le CER';
            $this->assertEquals($expected, $result);
        }

        // -----------------------------------------------------------------
        // C/P from BasicsTest's testTranslate()
        $result = Translator::__('Some string with %s', 'arguments');
        $expected = 'Some string with arguments';
        $this->assertEquals($expected, $result);

        $result = Translator::__('Some string with %s %s', 'multiple', 'arguments');
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);*/

        $result = Translator::__('Some string with {0} {1}', array('other multiple', 'arguments'));
        $expected = 'Some string with other multiple arguments';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::tainted() method.
     *
     * @covers Translator\Utility\Translator::tainted
     */
    public function testTainted()
    {
        Translator::domains('search_plugin');
        $this->assertFalse(Translator::tainted());

        Translator::__('Search.Personne.nom');
        $this->assertTrue(Translator::tainted());
    }


    /**
     * Test of the Translator::reset() method.
     *
     * @covers Translator\Utility\Translator::reset
     */
    public function testReset()
    {
        Translator::domains('search_plugin');
        $result = Translator::__('Search.Personne.nom');
        $expected = 'Nom';
        $this->assertEquals($expected, $result);

        Translator::reset();
        $result = Translator::__('Search.Personne.nom');
        $expected = 'Search.Personne.nom';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::export() method.
     *
     * @covers Translator\Utility\Translator::export
     */
    public function testExport()
    {
        Translator::domains('search_plugin');

        Translator::__('Search.Personne.nom');
        Translator::__('Some string with {0}', ['arguments']);

        $result = Translator::export();
        $expected = array(
            'fr_FR' => array(
                'a:1:{i:0;s:13:"search_plugin";}' => array(
                    '__' => array(
                        'Search.Personne.nom' => 'Nom',
                        'Some string with {0}' => 'Some string with {0}',
                    )
                )
            )
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::import() method.
     *
     * @covers Translator\Utility\Translator::import
     */
    public function testImport()
    {
        $cache = array(
            'fr_FR' => array(
                'a:1:{i:0;s:14:"search_plugin2";}' => array(
                    '__' => array(
                        'Search.Personne.nom' => 'Nom',
                    )
                )
            )
        );
        Translator::import($cache);
        Translator::domains('search_plugin2');
        $result = Translator::__('Search.Personne.nom');
        $expected = 'Nom';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test of the Translator::import() method.
     *
     * @covers Translator\Utility\Translator::import
     */
    public function testMultipleImport()
    {
        $cache = array(
            'fr_FR' => array(
                'a:1:{i:0;s:14:"search_plugin2";}' => array(
                    '__' => array(
                        'Search.Personne.nom' => 'Nom',
                    )
                )
            )
        );
        Translator::import($cache);
        Translator::import(array());
        Translator::domains('search_plugin2');
        $result = Translator::__('Search.Personne.nom');
        $expected = 'Nom';
        $this->assertEquals($expected, $result);
    }
}
