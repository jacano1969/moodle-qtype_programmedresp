<?php 

/**
 * Constant vars and common functions for the programmed responses question type
 * 
 * @copyright 2010 David Monllaó <david.monllao@urv.cat>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package qtype_programmedresp
 */

define('PROGRAMMEDRESP_TOLERANCE_RELATIVE', 1);
define('PROGRAMMEDRESP_TOLERANCE_NOMINAL', 2);

define('PROGRAMMEDRESP_RESPONSEFORMAT_DECIMAL', 1);
define('PROGRAMMEDRESP_RESPONSEFORMAT_SIGNIFICATIVE', 2);

define('PROGRAMMEDRESP_ARG_FIXED', 0);
define('PROGRAMMEDRESP_ARG_VARIABLE', 1);
define('PROGRAMMEDRESP_ARG_GUIDEDQUIZ', 2);
define('PROGRAMMEDRESP_ARG_CONCAT', 3);


/**
 * Returns the vars of the question text
 * 
 * @param string $questiontext Optional
 * @return array Names of the variables found on the question text
 */
function programmedresp_get_question_vars($questiontext = false) {

	if (!$questiontext) {
        $questiontext = optional_param('questiontext', false, PARAM_RAW);
	}
        
    $pattern = '{\{\$[a-zA-Z0-9]*\}}';
    preg_match_all($pattern, $questiontext, $matches);
        
    if (empty($matches) || empty($matches[0])) {
        return false;
    }
        
    foreach ($matches[0] as $match) {
        $varname = substr($match, 2, (strlen($match) - 3));
        $vars[$varname] = $varname;
    }
    
    return $vars;
}


/**
 * Gets the concatenated vars
 * 
 * @param array args Arguments data
 * @return array Concatenated vars with vars selected
 */
function programmedresp_get_concat_vars($args = false) {

	$concatvars = array();
	
	// If there are args filter by CONCAT type
	if ($args) {
		foreach ($args as $arg) {
			
			if (PROGRAMMEDRESP_ARG_CONCAT == $arg->type) {
				$concatdata = programmedresp_unserialize($arg->value);
				$concatvars[$concatdata->name] = $concatdata->name;
			}
		}
		
    // If there aren't args search on _GET
	} else {
		
		// I hope 50 will be ok...
		for ($concatnum = 0; $concatnum < 50; $concatnum++) {
			
			$varname = 'concatvar_'.$concatnum;
			if ($concat = optional_param($varname, false, PARAM_ALPHANUM)) {
				$concatvars[$varname] = $varname;
			}
		}
	}
	
	return $concatvars;
}


/**
 * Gets the different attributes of the question text vars
 * @return array
 */
function programmedresp_get_var_fields() {
	
    return array('nvalues' => get_string('nvalues', 'qtype_programmedresp'),
        'minimum' => get_string('minimum', 'qtype_programmedresp'),
        'maximum' => get_string('maximum', 'qtype_programmedresp'),
        'valueincrement' => get_string('valueincrement', 'qtype_programmedresp'));
}


/**
 * Gets the argtype constant value against the arg type text
 * @return array
 */
function programmedresp_get_argtypes_mapping() {
	
	return array(PROGRAMMEDRESP_ARG_FIXED => 'fixed', 
        PROGRAMMEDRESP_ARG_VARIABLE => 'variable',
        PROGRAMMEDRESP_ARG_CONCAT => 'concat',
        PROGRAMMEDRESP_ARG_GUIDEDQUIZ => 'guidedquiz');
}


/**
 * Adds the function to the functions repository
 * 
 * @pre Function code already verified
 * @param string $functioncode
 */
function programmedresp_add_repository_function($functioncode) {
	
	global $CFG;
	
	// TODO: Interoperability
	$linebreak = "\n";
	
	$file = $CFG->dataroot.'/qtype_programmedresp.php';
	
	// Creating a new file
	if (!file_exists($file)) {
		if (!$fh = fopen($file, 'w')) {
			print_error('errornowritable', 'qtype_programmedresp');
		}
		fwrite($fh, '<?php');
		fwrite($fh, $linebreak);
		fclose($fh);
	}
	
	if(!is_writable($file)) {
		print_error('errornowritable', 'qtype_programmedresp');
	}
	
	$cleanfunctioncode = str_replace(chr(13), '', $functioncode);
	$cleanfunctioncode = str_replace(chr(10), '', $cleanfunctioncode);
	$cleanfunctioncode = str_replace('\r', '', $cleanfunctioncode);
	$cleanfunctioncode = str_replace('\n', '', $cleanfunctioncode);
    $cleanfunctioncode = str_replace('    ', ' ', $cleanfunctioncode);
    $cleanfunctioncode = str_replace('    ', ' ', $cleanfunctioncode);
	
	$fh = fopen($file, 'a+');
	
	fwrite($fh, $linebreak);
	fwrite($fh, $cleanfunctioncode);
	fwrite($fh, $linebreak);
	
	fclose($fh);
}



/**
 * @todo Improve!!!
 * @param unknown_type $parentid
 * @param unknown_type $catoptions
 * @param unknown_type $categories
 * @param unknown_type $nspaces
*/
function programmedresp_add_child_categories($parentid, &$catoptions, $categories, $nspaces = 2) {
        
    foreach ($categories as $key => $cat) {
        if ($cat->parent == $parentid && empty($catoptions[$cat->id])) {

            $spaces = '';
            $i = 0;
            while ($i < $nspaces) {
                $spaces.= '&nbsp;';
                $i++;
            }
            $catoptions[$cat->id] = $spaces.$cat->name;
            unset($categories[$key]);
            programmedresp_add_child_categories($cat->id, $catoptions, $categories, $nspaces + 2);
        }
    }
}
    
    
/**
 * Returns the function code
 * @param object $function programmedresp_f
 * @return string
 */
function programmedresp_get_function_code($functionname) {
    global $CFG;
       
    $fh = fopen($CFG->dataroot.'/qtype_programmedresp.php', 'r');
            
    if (!$fh) {
        print_error('errorcantaccessfile', 'qtype_programmedresp');
    }
        
    // The function file line must begin with this
    $searchedstring = 'function '.$functionname;
       
    // Until end of file or function found
    while (($line = fgets($fh, 4096)) !== false && empty($code)) {
            
        // If the line beginning matches the searched string
        if (strstr(substr($line, 0, strlen($searchedstring)), $searchedstring) != false) {
            $code = $line;
        }
    }
    fclose($fh);
        
    if (empty($code)) {
        return false;
    }
        
    return $code;
}


function programmedresp_serialize($var) {
	
	// Single value
	if (!is_object($var) && !is_array($var)) {
		return serialize(str_replace('"', '\"', $var));
	}
	
	if (is_object($var)) {
		foreach ($var as $attr => $value) {
			$var->$attr = str_replace('"', '\"', $value);
		}
	}
	
	if (is_array($var)) {
		foreach ($var as $key => $value) {
			if (!is_object($value)) {
			    $var[$key] = str_replace('"', '\"', $value);
			} else {
				foreach ($value as $attr => $attrvalue) {
					$var[$key]->$attr = str_replace('"', '\"', $attrvalue);
				}
			}
		}
	}
	
	$var = serialize($var);
	
	return $var;
}


function programmedresp_unserialize($var) {
	
	$var = unserialize($var);
	
	if (!is_object($var) && !is_array($var)) {
		return str_replace('\"', '"', $var);
	}
	

    if (is_object($var)) {
        foreach ($var as $attr => $value) {
            $var->$attr = str_replace('\"', '"', $value);
        }
    }
    
    if (is_array($var)) {
        foreach ($var as $key => $value) {
        	if (!is_object($value)) {
                $var[$key] = str_replace('\"', '"', $value);
        	} else {
        		foreach ($value as $attr => $attrvalue) {
        			$var[$key]->$attr = str_replace('\"', '"', $attrvalue);
        		}
        	}
        }
    }
    
	return $var;
}

/**
 * Gets random value/s
 * 
 * @param unknown_type $vardata
 * @return array Values array, array with size = 1 if there is a single value 
 */
function programmedresp_get_random_value($vardata) {
        
    $values = array();
    for ($i = 0; $i < $vardata->nvalues; $i++) {
            
        $differentincrements = round(($vardata->maximum - $vardata->minimum) / $vardata->valueincrement);
        $values[] = $vardata->minimum + (rand(0, $differentincrements) * $vardata->valueincrement);
    }
        
    return $values;
}


/**
 * Returns the module in use
 * @todo Improve the module detection
 * @return string
 */
function programmedresp_get_modname() {

	global $CFG;

//    $withoutmod = substr(str_replace($CFG->dirroot, '', $_SERVER['SCRIPT_FILENAME']), 5);       // The 5 is the /mod/
//    $modname = substr($withoutmod, 0, strpos($withoutmod, '/'));
//
//    return $modname;

	if (strstr($_SERVER['SCRIPT_FILENAME'], 'guidedquiz') != false) {
		return 'guidedquiz';
	} else {
		return 'quiz';
	}
	
}


/**
 * Returns the quiz
 * @param integer $attemptid
 * @param string $modname
 * @return integer The quiz of the attempt
 */
function programmedresp_get_quizid($attemptid, $modname) {
	return get_field($modname.'_attempts', 'quiz', 'uniqueid', $attemptid);
}


/**
 * Taking into account the dots
 * @param unknown_type $response
 * @return boolean
 */
function programmedresp_is_numeric($response) {

	if (!preg_match('/^[0-9]$/', $response) && !preg_match('/^[0-9]+\.[0-9]+$/', $response)) {
        return false;
	}

    return true;
}

/**
 * Uses the question tolerance to round the result
 * @param unknown_type $result
 * @param unknown_type $tolerance
 */
function programmedresp_round($result, $tolerance) {

    if (programmedresp_is_numeric($result) && strstr($tolerance, '.') != false) {
        $tmp = explode('.', $tolerance);
        $result = round($result, strlen($tmp[1]));
    }
    
    return $result;
}
