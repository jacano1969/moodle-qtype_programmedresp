<?php

require_once($CFG->dirroot.'/question/type/programmedresp/lib.php');
    
function xmldb_qtype_programmedresp_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($result && $oldversion < 2011042100) {

        /// Define field module to be added to question_programmedresp_val
        $table = new XMLDBTable('question_programmedresp_val');
        $field = new XMLDBField('module');
        $field->setAttributes(XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, null, 'attemptid');

        /// Launch add field module
        $result = $result && add_field($table, $field);
    }
    
    if ($result && $oldversion < 2011051300) {
    	
    	// Drop field responseformat
        $table = new XMLDBTable('question_programmedresp');
        $field = new XMLDBField('responseformat');
        $result = $result && drop_field($table, $field);
        
        // Drop field responsedigits
        $table = new XMLDBTable('question_programmedresp');
        $field = new XMLDBField('responsedigits');

        /// Launch drop field tolerance
        $result = $result && drop_field($table, $field);
    }

    if ($result && $oldversion < 2011053100) {

        $table = new XMLDBTable('question_programmedresp');
        $field = new XMLDBField('tolerance');
        $field->setAttributes(XMLDB_TYPE_CHAR, '30', null, null, null, null, null, '0', 'tolerancetype');

        $result = $result && change_field_type($table, $field, true, true);
    }
    
    if ($result && $oldversion < 2011062602) {

        /// Define table question_programmedresp_conc to be created
        $table = new XMLDBTable('question_programmedresp_conc');

        // New table
        if (!table_exists($table)) {

       	    /// Adding fields to table question_programmedresp_conc
            $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
            $table->addFieldInfo('origin', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, XMLDB_ENUM, array('question', 'quiz'), 'question');
            $table->addFieldInfo('instanceid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('vars', XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null);

            /// Adding keys to table question_programmedresp_conc
            $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

            /// Adding indexes to table question_programmedresp_conc
            $table->addIndexInfo('origin_instanceid', XMLDB_INDEX_NOTUNIQUE, array('origin', 'instanceid'));

            /// Launch create table for question_programmedresp_conc
            $result = $result && create_table($table);

            
            // Adding data
            $concatvarinstances = get_records('question_programmedresp_arg', 'type', PROGRAMMEDRESP_ARG_CONCAT);
            if ($concatvarinstances) {
                foreach ($concatvarinstances as $instance) {
        		
        		    unset($obj);
        		
            		$data = programmedresp_unserialize($instance->value);

            		// New record
            		$obj->origin = 'question';
        	    	$obj->instanceid = $instance->programmedrespid;
        		    $obj->name = $data->name;
        		    $obj->vars = programmedresp_serialize($data->values);
        		    if (!$obj->id = insert_record('question_programmedresp_conc', $obj)) {
        		    	print_error('errordb', 'qtype_programmedresp');
        		    }
        		    
        		    // Referencing the new question_programmedresp_conc record
        		    $instance->value = $obj->id;
        		    update_record('question_programmedresp_arg', $instance);
        	    }
            }
        }
    }
    
    return $result;
}

?>
