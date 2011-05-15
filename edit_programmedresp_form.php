<?php

require_once($CFG->dirroot.'/question/type/edit_question_form.php');
require_once($CFG->dirroot.'/question/type/programmedresp/lib.php');
require_once($CFG->dirroot.'/question/type/programmedresp/programmedresp_output.class.php');


/**
 * Programmed response editing form definition.
 *
 * @copyright 2010 David Monllaó <david.monllao@urv.cat>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package qtype_programmedresp
 */
class question_edit_programmedresp_form extends question_edit_form {
	
    function definition_inner(&$mform) {

        global $CFG;
        
        $caneditfunctions = has_capability('moodle/question:config', get_context_instance(CONTEXT_SYSTEM));
        
    	require_js(array('yui_yahoo', 'yui_event', 'yui_connection'));
    	
    	// Adding wwwroot
        echo "<script type=\"text/javascript\">//<![CDATA[\n".
             "var wwwroot = '".$CFG->wwwroot."';\n".
             "//]]></script>\n";
    	
    	// To lower than 1.9.9
    	require_js($CFG->wwwroot.'/question/type/programmedresp/script.js');
    	echo '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/question/type/programmedresp/styles.css" />';
    	
    	// Data
    	$categories = get_records('question_programmedresp_fcat', '', '', 'id ASC', 'id, parent, name');
    	
    	// If there are previous data
    	if (!empty($this->question->id)) {
    		$this->programmedresp = get_record('question_programmedresp', 'question', $this->question->id);
    		$this->programmedresp_f = get_record('question_programmedresp_f', 'id', $this->programmedresp->programmedrespfid);
    		$this->programmedresp_vars = get_records('question_programmedresp_var', 'programmedrespid', $this->programmedresp->id);
    		$this->programmedresp_args = get_records('question_programmedresp_arg', 'programmedrespid', $this->programmedresp->id, '', 'argkey, type, value');
    		$this->programmedresp_resps = get_records('question_programmedresp_resp', 'programmedrespid', $this->programmedresp->id, 'returnkey ASC', 'returnkey, label');
    	}
    	
    	$catoptions = array(0 => '&nbsp;('.get_string('selectcategory', 'qtype_programmedresp').')&nbsp;');
    	if ($categories) {
	    	foreach ($categories as $key => $cat) {
	    		if (empty($catoptions[$cat->id])) {
	    			$catoptions[$cat->id] = $cat->name;
	                unset($categories[$key]);
	                programmedresp_add_child_categories($cat->id, $catoptions, $categories);
	    		}
	    	}
    	}

    	$tolerancetypes = array(PROGRAMMEDRESP_TOLERANCE_NOMINAL => get_string('tolerancenominal', 'qtype_programmedresp'),
    	   PROGRAMMEDRESP_TOLERANCE_RELATIVE => get_string('tolerancerelative', 'qtype_programmedresp'));

        // Form elements
        $outputmanager = new programmedresp_output($mform);

        $editingjsparam = 'false';
        // In a new question the vars div should be loaded
        if (!empty($this->question->id)) {
            $editingjsparam = 'true';
        }
        
        // Button label
        if (!empty($this->question->id)) {
            $buttonlabel = get_string('refreshvarsvalues', 'qtype_programmedresp');
        } else {
        	$buttonlabel = get_string('assignvarsvalues', 'qtype_programmedresp');
        }
        $varsattrs = array('onclick' => 'display_vars(this, "'.get_string("novars", "qtype_programmedresp").'", '.$editingjsparam.');');
        $mform->addElement('button', 'vars', $buttonlabel, $varsattrs);
        
    	// Link to fill vars data
    	$mform->addElement('header', 'varsheader', get_string("varsvalues", "qtype_programmedresp"));
    	
    	$mform->addElement('html', '<div id="id_vars_content">');
    	if (!empty($this->question->id)) {
    		$outputmanager->display_vars($this->question->questiontext, $this->programmedresp_args);
    	}
        $mform->addElement('html', '</div>');
        
        
        // Functions header
        $mform->addElement('header', 'functionheader', get_string("assignfunction", "qtype_programmedresp"));
        
        // Category select
        $catattrs['onchange'] = 'update_addfunctionurl();return display_functionslist(this);';
        $mform->addElement('select', 'functioncategory', get_string('functioncategory', 'qtype_programmedresp'), $catoptions, $catattrs);
        
        // Dirty hack to add the function (added later through ajax)
        if (empty($this->question->id)) {
            $mform->addElement('hidden', 'programmedrespfid');
        }
        
        // Link to add a category
        if ($caneditfunctions) {
	        $addcategoryurl = $CFG->wwwroot.'/question/type/programmedresp/manage.php?action=addcategory';
	        $onclick = "window.open(this.href, this.target, 'menubar=0,location=0,scrollbars,resizable,width=500,height=600', true);return false;";
	        $categorylink = '<a href="'.$addcategoryurl.'" onclick="'.$onclick.'" target="addcategory">'.get_string('addcategory', 'qtype_programmedresp').'</a>';
	        $mform->addElement('html', '<div class="fitem"><div class="fitemtitle"></div><div class="felement">'.$categorylink.'<br/><br/></div></div>');
        }
        
        
        // Function list
        $mform->addElement('html', '<div id="id_functioncategory_content">');
        if (!empty($this->question->id)) {
        	$outputmanager->display_functionslist($this->programmedresp_f->programmedrespfcatid);
        }
        $mform->addElement('html', '</div>');
        
    
        // Link to add a function
        if ($caneditfunctions) {
            $addfunctionsurl = $CFG->wwwroot.'/question/type/programmedresp/manage.php?action=addfunctions';
            
            // If it's a function edition we should add the selected category id
            if (!empty($this->question->id)) {
                $addfunctionsurl .= '&fcatid='.$this->programmedresp_f->programmedrespfcatid;
            }
            
            $onclick = "window.open(this.href, this.target, 'menubar=0,location=0,scrollbars,resizable,width=650,height=600', true);return false;";
            $functionlink = '<a href="'.$addfunctionsurl.'" onclick="'.$onclick.'" target="addfunctions" id="id_addfunctionurl">'.get_string('addfunction', 'qtype_programmedresp').'</a>';
            $mform->addElement('html', '<div class="fitem"><div class="fitemtitle"></div><div class="felement">'.$functionlink.'<br/><br/></div></div>');
        }
        
        // Arguments
        $mform->addElement('html', '<div id="id_programmedrespfid_content">');
        if (!empty($this->question->id)) {
        	$outputmanager->display_args($this->programmedresp_f->id, $this->question->questiontext, $this->programmedresp_args, $this->programmedresp_vars);
        }
        $mform->addElement('html', '</div>');
        
        // Tolerance
        $mform->addElement('header', 'toleranceheader', get_string("tolerance", "qtype_programmedresp"));
        $mform->addElement('select', 'tolerancetype', get_string("tolerancetype", "qtype_programmedresp"), $tolerancetypes);
        $mform->addElement('text', 'tolerance', get_string("tolerance", "qtype_programmedresp"));
        $mform->addRule('tolerance', null, 'required', null, 'client');
        $mform->addRule('tolerance', null, 'numeric', null, 'client');
        $mform->setType('tolerance', PARAM_NUMBER);

        // Add the onload javascript to hide next steps
        if (empty($this->question->id)) {
        	require_js($CFG->wwwroot.'/question/type/programmedresp/onload.js');
        }
        
    }
    

    /**
     * Prepares the data to fill the form
     * @param object $question
     */
    function set_data($question) {
    	
    	if (!empty($question->id)) {

    		// Variables
    		$varfields = programmedresp_get_var_fields();
    		foreach ($this->programmedresp_vars as $var) {
    			
    			foreach ($varfields as $varfield => $fielddesc) {
    				$fieldname = 'var_'.$varfield.'_'.$var->varname;
    				$question->{$fieldname} = $var->{$varfield};
    			}
    		}
    		
	    	// Function and function category
            $question->functioncategory = $this->programmedresp_f->programmedrespfcatid;
            $question->programmedrespfid = $this->programmedresp_f->id;
            
            // Function responses
            foreach ($this->programmedresp_resps as $returnkey => $resp) {
            	$fieldname = 'resp_'.$returnkey;
            	$question->{$fieldname} = $resp->label;
            }
            
            // Tolerance
            $programmedresp = array('tolerancetype', 'tolerance');
            foreach ($programmedresp as $field) {
               $question->{$field} = $question->options->programmedresp->{$field};
            }
    	}
    	
        parent::set_data($question);
    }

    
    function validation($data) {
        $errors = array();

        // TODO, do extra validation on the data that came back from the form. E.g.
        // if (/* Some test on $data['customfield']*/) {
        //     $errors['customfield'] = get_string( ... );
        // }
        
        $requiredparams = array('programmedrespfid', 'functioncategory');
        foreach ($requiredparams as $requiredparam) {
        	
        	if (!optional_param($requiredparam, false, PARAM_RAW)) {
        		$errors['questiontext'] = get_string('erroreditformnotcompleted', 'qtype_programmedresp');
        	}   
        	
        }

        if ($errors) {
            return $errors;
        } else {
            return true;
        }
    }

    function qtype() {
        return 'programmedresp';
    }
}
