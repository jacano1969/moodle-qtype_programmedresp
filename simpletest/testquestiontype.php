<?php
/**
 * Unit tests for this question type.
 *
 * @copyright 2010 David Monllaó <david.monllao@urv.cat>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package qtype_programmedresp
 */
    
require_once(dirname(__FILE__) . '/../../../../config.php');

global $CFG;
require_once($CFG->libdir . '/simpletestlib.php');
require_once($CFG->dirroot . '/question/type/programmedresp/questiontype.php');

class programmedresp_qtype_test extends UnitTestCase {
    var $qtype;
    
    function setUp() {
        $this->qtype = new programmedresp_qtype();
    }
    
    function tearDown() {
        $this->qtype = null;    
    }

    function test_name() {
        $this->assertEqual($this->qtype->name(), 'programmedresp');
    }
    
    // TODO write unit tests for the other methods of the question type class.
}

?>
