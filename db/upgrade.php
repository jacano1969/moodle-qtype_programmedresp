<?php

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

    return $result;
}

?>
