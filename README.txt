##
# Moodle - Question type - Programmed responses
#
# @package qtype
# @subpackage programmedresp
# @copyright 2011 David Monllaó
# @license http://www.gnu.org/licenses/gpl-2.0.txt
##


INTRODUCTION
Question type which gets the question answer through a set of editable and non-native PHP functions. Useful 
for maths or statistics exercices which requires complex operations to get the answer.


FEATURES
* Users can add variables to the question text, the variables takes random values depending on 
the user selection: max value, min value, increment and the number of values (scalar/vectorial - float/array)

* The function arguments can be one of the question text defined variables, a concatenation of defined variables 
or fixed values.

* User interface to allow the addition of functions. These functions can return one or more values, useful 
for example to return confidence intervals.


REQUIREMENTS
* Compatible with Moodle 1.9.x releases
* Users with Javascript/AJAX support enabled


USAGE
#1st step (optional): Variables can be added to the question text following the  
next format: {$varname} The variables names only accepts alphanumeric characters.

#2nd step (optional, depends on the first step): Each variable must define the maximum and minimum  
values it can take, the increment and the number of values, to allow vectorial variables.

#3rd step (optional): Add concat variables, useful if there should be a vectorial variable which  
values follows different criteria

#4th step: Select the function which will calculate the answer (read ADDING FUNCTIONS to know 
how to add functions)

#5th step: Assign each of the function arguments to a variable or a concat variable, the function 
can also use a fixed value as argument


ADDING FUNCTIONS
There is an interface to allow the addition of functions; preceding the php implementation of each function 
there should be a comment block following phpdoc format, to specify the arguments description and the returned values

Function example:

/**
 * I'm the description block of this function
 *
 * @param array The values
 * @return float 1 The average
 */
function average($values) {

    $sum = 0;
    foreach ($values as $value) {
        $sum = $sum + $value;
    }

    return ($sum / count($values));
}

The '1' after the 'float' on the @return phpdoc tag indicates that the function returns a scalar 
value. If the function returns an array the return phpdoc tag should follow this 
format "@return array Description1|Description2" using the "|" character as separator


GUIDED QUIZ MODULE LINKAGE
There is another function argument type, the guided quiz type. The "mod_guidedquiz" moodle plugin 
is a quiz which allows the addition of variables to the quiz text, following the variables format 
described above; this argument type allows users to use these guided quiz variables as a function
argument.

INSTALL
Follow the usual installation instructions

CREDITS
Tool designed by Josep Maria Mateo, Carme Olivé and Dolors Puigjaner, members of DEQ 
<http://www.etseq.urv.es/DEQ/> and DEIM <http://deim.urv.cat> departments of the 
Universitat Rovira i Virgili.

Consultoria Mosaic <http://www.consultoriamosaic.net>


