<?php 

/**
 * Manages the functions and categories of the system
 * 
 * @copyright 2010 David Monllaó <david.monllao@urv.cat>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package qtype_programmedresp
 */

require_once('../../../config.php');

require_once($CFG->dirroot.'/question/type/programmedresp/forms/programmedresp_addcategory_form.php');
require_once($CFG->dirroot.'/question/type/programmedresp/forms/programmedresp_addfunctions_form.php');
require_once($CFG->dirroot.'/question/type/programmedresp/functions_tokenizer.class.php');
require_once($CFG->dirroot.'/question/type/programmedresp/lib.php');

$action = required_param('action', PARAM_ALPHA);
require_capability('moodle/question:config', get_context_instance(CONTEXT_SYSTEM));

require_js($CFG->wwwroot.'/question/type/programmedresp/script.js');
print_header_simple(get_string($action, 'qtype_programmedresp'));


switch ($action) {
	
	case 'addcategory':
		
		$categories = get_records('question_programmedresp_fcat', '', '', 'id ASC', 'id, parent, name');
		$catoptions[0] = get_string('root', 'qtype_programmedresp');
        foreach ($categories as $key => $cat) {
            if (empty($catoptions[$cat->id])) {
                $catoptions[$cat->id] = $cat->name;
                unset($categories[$key]);
                programmedresp_add_child_categories($cat->id, $catoptions, $categories);
            }
        }
		
		$form = new programmedresp_addcategory_form($CFG->wwwroot.'/question/type/programmedresp/manage.php', array('categories' => $catoptions));
		
		// Insert category
		if ($data = $form->get_data()) {

            $catdata->parent = $data->parent;
			$catdata->name = $data->name;
			if (!$catdata->id = insert_record('question_programmedresp_fcat', $catdata)) {
				print_error('errordb', 'qtype_programmedresp');
			}
			
			echo '<script type="text/javascript">';
			echo 'add_to_parent("'.$catdata->id.'", "'.$catdata->name.'", "id_functioncategory");window.close();';
			echo '</script>';
			
	    // Display form
		} else {
			$form->display();
		}
		break;
		
		
	case 'addfunctions':
		
		$fcatid = required_param('fcatid', PARAM_INT);
		$form = new programmedresp_addfunctions_form($CFG->wwwroot.'/question/type/programmedresp/manage.php', array('fcatid' => $fcatid));
        
        // Insert category
        if ($data = $form->get_data()) {
            
            $tokenizer = new functions_tokenizer();
            if (!$tokenizer->set_code($data->functionstextarea)) {
            	notify(get_string('errorsyntax', 'qtype_programmedresp'), 'error');
            	$form->set_data(array('functionstextarea' => $data->functionstextarea));
            } else {
            	$functions = $tokenizer->get_functions();
            }
            
            // If there aren't valid functions display the form again
            if (empty($functions)) {
            	notify(get_string('errornovalidfunctions', 'qtype_programmedresp'), 'error');
                $form->set_data(array('functionstextarea' => $data->functionstextarea));
                
            // Add functions data
            } else {
	            
	            foreach ($functions as $function) {
	            	if (get_record('question_programmedresp_f', 'name', $function->name) || programmedresp_get_function_code($function->name)) {
	            		notify($function->name.': '.get_string('errorfunctionalreadycreated', 'qtype_programmedresp'), 'error');
	            		continue;
	            	}
	                $fdata->programmedrespfcatid = $fcatid;
	                $fdata->name = $function->name;
	                $fdata->description = $function->description;
	                $fdata->nreturns = $function->nreturns;
	                $fdata->params = serialize($function->params);
	                $fdata->results = serialize($function->results);
	                $fdata->timeadded = time();

	                if (!$fdata->id = insert_record('question_programmedresp_f', $fdata)) {
	                    print_error('errordb', 'qtype_programmedresp');
	                }
	                
	                notify(get_string('functionadded', 'qtype_programmedresp', $function->name), 'green');
	                programmedresp_add_repository_function($function->functioncode);
	                
	                // Array to add to the parent select functions form element
	                $fdatas[] = clone $fdata;
	            }
            }
        	
            // Display errors found
            $errors = $tokenizer->get_errors();
            if ($errors) {
            	foreach ($errors as $error) {
            		notify($error, 'error');
            	}
            }
            
            // Add the functions created to the form
            if (!empty($fdatas)) {
                echo '<script type="text/javascript">';
	            foreach ($fdatas as $f) {
	                echo 'add_to_parent("'.$f->id.'", "'.$f->name.'", "id_programmedrespfid");';
	            }
	            echo '</script>';
            }
            
        }
        
        // Display the form anyway
        $form->display();
        
        echo '<a href="#" onclick="window.close();" style="text-align: center;">'.get_string("closewindow", "qtype_programmedresp").'</a>';
		break;
		
	default:
		
		die();
		break;
}