<?php 

/**
 * Manages the moodleform and ajax shared outputs
 *
 * @copyright 2010 David Monllaó <david.monllao@urv.cat>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package qtype_programmedresp
 */
class programmedresp_output {
    
    var $mform;
    
    function programmedresp_output(&$mform) {
        $this->mform = $mform;
    }
    
    
    function add_concat_var($name, $vars, $values = false, $return = false) {
    	
    	$concatdiv = '<strong>'.$name.'</strong><br/>';
        $concatdiv.= '<select id="'.$name.'" name="'.$name.'[]" multiple="multiple">';
                    
        // Marking the selected vars
        foreach ($vars as $var) {
            $selectedstr = '';
            if ($values) {
	            foreach ($values as $concatvar) {
	                if ($var == $concatvar) {
	                    $selectedstr = 'selected="selected"';
	                }
	            }
            }
            $concatdiv.= '<option value="'.$var.'" '.$selectedstr.'>'.$var.'</option>';
        }
        $concatdiv.= '</select>';
        $concatdiv.= '&nbsp;&nbsp;<input type="button" onclick="confirm_concat_var(\''.$name.'\');" value="'.get_string('confirmconcatvar', 'qtype_programmedresp').'"/>';
        $concatdiv.= '&nbsp;<input type="button" onclick="cancel_concat_var(\''.$name.'\');" value="'.get_string('cancelconcatvar', 'qtype_programmedresp').'" />';
        $concatdiv.= '<br/><br/>';
                    
    	if ($return) {
    		return $concatdiv;
    	}
    	
    	echo $concatdiv;
    }
    
    /**
     * Prints form elements for the question vars based on question->questiontext
     * @param string $questiontext
     * @param array $args To restore the already added concat vars
     * @param boolean $displayfunctionbutton True for programmedresp, false for guidedquiz
     * @param array $quizconcatvars The already created guided quiz concat vars
     */
    function display_vars($questiontext = false, $args = false, $displayfunctionbutton = true, $quizconcatvars = false) {

    	// If there aren't vars just notify it
        if (!$vars = programmedresp_get_question_vars($questiontext)) {
        	$this->print_form_htmlraw('<span class="programmedresp_novars">'.get_string('novars', 'qtype_programmedresp').'</span>');
        }
        
        // The variables fields
        $fields = programmedresp_get_var_fields();
        $varattrs['onblur'] = 'check_numeric(this, \''.addslashes(get_string("nonumeric", "qtype_programmedresp")).'\');';
        
        // Selectors for each match
        if ($vars) { 
	        foreach ($vars as $varname) {
	
	            $this->print_form_title('<strong>'.get_string("var", "qtype_programmedresp").' '.$varname.'</strong>');
	            
	            foreach ($fields as $fieldkey => $title) {
	                $this->print_form_text($title, 'var_'.$fieldkey.'_'.$varname, '', $varattrs);
	            }
	            $this->print_form_spacer();
	        }
        
	        // Concat vars
	        $concatdiv = '<div id="id_concatvars">';
	        
	        // Restoring concat vars if updating and they exists
	        if ($args) {
	        	foreach ($args as $arg) {
	        		if (PROGRAMMEDRESP_ARG_CONCAT == $arg->type) {
	        			
	        			$concatdata = programmedresp_get_concatvar_data($arg->value);
	        			$concatdiv.= $this->add_concat_var($concatdata->name, $vars, $concatdata->values, true);
	        		}
	        	}
	        }
	        
	        // Restoring the guided quiz concatenated vars
	        if ($quizconcatvars) {
	        	foreach ($quizconcatvars as $concatdata) {
	        		$concatdata->values = programmedresp_unserialize($concatdata->vars);
	        		$concatdiv.= $this->add_concat_var($concatdata->name, $vars, $concatdata->values, true);
	        	}
	        }
	        
	        $concatdiv.= '</div>';
	        $this->print_form_html($concatdiv);
	        $this->print_form_html('<a href="#" onclick="add_concat_var();return false;">'.get_string("addconcatvar", "qtype_programmedresp").'</a><br/><br/>');
        }
        
        // TODO: Add a check_maximum and check_minimum to ensure max > min
        
        // The guided quiz should not display the functions button
        if ($displayfunctionbutton) {
	        $attrs['onclick'] = 'return functionsection_visible();';
	        
	        // Button text
	        if (empty($args)) {
	        	$buttonlabel = get_string("assignfunction", "qtype_programmedresp");
	        } else {
	        	$buttonlabel = get_string("refresharguments", "qtype_programmedresp");
	        }
	        $this->print_form_button($buttonlabel, 'function', $attrs);
        }
        
    }
    
    
    /**
     * Prints a select with the category functions list
     * @param integer $categoryid
     */
    function display_functionslist($categoryid = false) {
        
    	// Retrieving category functions
    	if ($categoryid) {
	        $functions = get_records('qtype_programmedresp_f', 'programmedrespfcatid', $categoryid);
	        if (!$functions) {
	            $this->print_form_html(get_string('errornofunctions', 'qtype_programmedresp'));
	        }
    	}
        
        // Functions
        $options = array('0' => ' ('.get_string("selectfunction", "qtype_programmedresp").') ');
        if (!empty($functions)) {
	        foreach ($functions as $function) {
	            $options[$function->id] = $function->name;
	        }
        }
        
        $attrs['onchange'] = 'return display_args(this);';
        $this->print_form_select(get_string('function', 'qtype_programmedresp'), 'programmedrespfid', $options, $attrs);
        
    }
    
    
    /**
     * Prints form elements to assign vars / values to the selected function arguments
     * @param $functionid
     * @param $questiontext
     * @param $args
     * @param $vars
     */
    function display_args($functionid, $questiontext = false, $args = false, $vars = false) {
        
        if (!$functionid) {
            die();
        }
        
        // Function data
        $functiondata = get_record('qtype_programmedresp_f', 'id', $functionid);
        $functiondata->params = programmedresp_unserialize($functiondata->params);
        $functiondata->results = programmedresp_unserialize($functiondata->results);

        if (!is_array($functiondata->params) || !is_array($functiondata->results)) {
        	$this->print_form_htmlraw('<span class="error">'.get_string('errorparsingfunctiondata', 'qtype_programmedresp').'</span>');
        	return false;
        }
        
        // Get the questiontext vars to fill the variables selector
        $questiontextvars = programmedresp_get_question_vars($questiontext);
        
        // Concatenated vars (if it's a new insertion getting from _GET if not from $args array)
        $concatvars = programmedresp_get_concat_vars($args);

        // Map arg type id => arg type name (fixed, variable or guidedquiz)
        $argtypes = programmedresp_get_argtypes_mapping();
            
        $this->print_form_htmlraw('<br/><div class="programmedresp_functiondescription">'.format_text($functiondata->description, FORMAT_MOODLE).'</div>');
        
        // Assign arguments
        $this->print_form_title('<strong>'.get_string('functionarguments', 'qtype_programmedresp').'</strong>');
        foreach ($functiondata->params as $key => $param) {

        	// Various param types
        	if (strpos($param->type, '|') != false) {
        		$paramtypes = explode('|', $param->type);
        		foreach ($paramtypes as $key => $paramtype) {
        			$paramtypes[$key] = get_string('paramtype'.$paramtype, 'qtype_programmedresp');
        		}
        		$paramsstring = implode(' '.get_string('or','qtype_programmedresp').' ', $paramtypes);
        		
        	// Only one param type
        	} else {
        	    $paramsstring = get_string("paramtype".$param->type, "qtype_programmedresp");
        	}
        	
        	// Argument description
            $this->print_form_htmlraw('<div class="fitem"><div class="fitemtitle">'.format_text($param->description, FORMAT_MOODLE).' ('.get_string("type", "qtype_programmedresp").': '.$paramsstring.')</div>');
            
            // Argument value type
            $paramelement = '<select name="argtype_'.$key.'" onchange="change_argument_type(this, \''.$key.'\');">';
            foreach ($argtypes as $argid => $argname) {
            	
            	if (!$questiontextvars && ($argname == 'variable' || $argname == 'concat')) {
            		continue;
            	}
            	
            	// If there are previous data and it is the selected argument type: selected
            	$selectedstr = '';
            	if ($args && $args[$key]->type == $argid) {
            		$selectedstr = 'selected="selected"';
            	}
            	
            	$paramelement.= '<option value="'.$argid.'" '.$selectedstr.'>'.get_string('arg'.$argname, 'qtype_programmedresp').'</option>';
            }
            $paramelement.= '</select>&nbsp;';
            
            
            // Argument value type dependencies
            $fixedvalue = '';
            $variablevalue = '';
            $concatvalue = '';
            $fixedclass = 'hidden_arg';
            $variableclass = 'hidden_arg';
            $guidedquizclass = 'hidden_arg';
            $concatclass = 'hidden_arg';
            
            // If it's a new insertion we show fixed
            if (!$args) {
            	$fixedclass = ''; 
            	
            } else {
	            if ($args[$key]->type == PROGRAMMEDRESP_ARG_FIXED) {
	            	$fixedvalue = $args[$key]->value;
	            	$fixedclass = '';
	            	
	            } else if ($args[$key]->type == PROGRAMMEDRESP_ARG_VARIABLE) {
	            	$variablevalue = $vars[$args[$key]->value]->varname;
	            	$variableclass = '';
	            	
	            } else if ($args[$key]->type == PROGRAMMEDRESP_ARG_CONCAT) {
	            	$concatdata = programmedresp_get_concatvar_data($args[$key]->value);
	            	$concatvalue = $concatdata->name;
	            	$concatclass = '';
	            	
	            } else {
	            	$guidedquizclass = '';
	            }
            }
            
            // Fixed
            $paramelement.= '<input type="text" name="fixed_'.$key.'" id="id_argument_fixed_'.$key.'" value="'.$fixedvalue.'" class="'.$fixedclass.'"/>';
            
            // Variables
            if ($questiontextvars) {
	            $paramelement.= '<select name="variable_'.$key.'" id="id_argument_variable_'.$key.'" class="'.$variableclass.'">';
	            foreach ($questiontextvars as $varname) {
	            	
	            	$selectedstr = '';
	            	if ($variablevalue == $varname) {
	            		$selectedstr = 'selected="selected"';
	            	}
	                $paramelement.= '<option value="'.$varname.'" '.$selectedstr.'>'.get_string("var", "qtype_programmedresp").' '.$varname.'</option>';
	            }
	            $paramelement.= '</select>';
            
	            // Concat vars
	            $paramelement.= '<select name="concat_'.$key.'" id="id_argument_concat_'.$key.'" class="'.$concatclass.'">';
	            foreach ($concatvars as $varname) {
	            	
	            	$selectedstr = '';
	            	if ($concatvalue == $varname) {
	            		$selectedstr = 'selected="selected"';
	            	}
	            	$paramelement.= '<option value="'.$varname.'" '.$selectedstr.'>'.get_string("var", "qtype_programmedresp").' '.$varname.'</option>';
	            }
	            $paramelement.= '</select>';
            }
            
            // Guided quiz
            $paramelement.= '<span  id="id_argument_guidedquiz_'.$key.'" class="'.$guidedquizclass.'"></span><input type="hidden" name="guidedquiz_'.$key.'" value=""/>';
            $this->print_form_htmlraw('<div class="felement fselect">'.$paramelement.'</div></div>');
        }
        
        
        // To assign labels
        $this->print_form_htmlraw('<br/><br/>');
        
        // Link to show the labels edition elements
        $this->print_form_htmlraw('<div id="id_responselabelslink">');
        $displayresponselabelslink = '<a href="#" onclick="return display_responselabels();">'.get_string('editresponselabels', 'qtype_programmedresp').'</a>';
        $this->print_form_html($displayresponselabelslink);
        $this->print_form_htmlraw('</div>');
        
        // Hidden by default
        $this->print_form_htmlraw('<div id="id_responseslabels">');
        $this->print_form_title('<strong>'.get_string('questionresultslabels', 'qtype_programmedresp').'</strong>');
        for ($i = 0; $i < $functiondata->nreturns; $i++) {
            
            if (!empty($functiondata->results[$i])) {
                $value = str_replace('"', '&quot;', $functiondata->results[$i]);
            } else {
                $value = '';
            }
            
            $this->print_form_text(get_string("response", "qtype_programmedresp").' '.($i + 1), 'resp_'.$i, $value);
        }
        $this->print_form_htmlraw('</div>');
    }

    function print_form_title($title) {
        $this->mform->addElement('html', '<div class="fitem"><div class="fitemtitle">'.$title.'</div></div>');
    }
    
    function print_form_button($title, $elementname, $attrs = false) {
        $this->mform->addElement('button', $elementname, $title, $attrs);
    }
    
    function print_form_text($title, $elementname, $value = '', $attrs = false) {
        $this->mform->addElement('text', $elementname, $title, $attrs);
        $this->mform->setDefault($elementname, $value);
    }
    
    function print_form_html($text) {
        $text = '<div class="fitem"><div class="fitemtitle"></div><div class="felement">'.$text.'</div></div>';
        $this->mform->addElement('html', $text);
    }
    
    function print_form_htmlraw($text) {
        $this->mform->addElement('html', $text);
    }
    
    function print_form_select($title, $elementname, $options, $attrs = false) {
        $this->mform->addElement('select', $elementname, $title, $options, $attrs);
    }

    function print_form_spacer() {
        $this->mform->addElement('html', '<br/><br/>');
    }    
}
