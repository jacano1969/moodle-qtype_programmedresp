
var callbackResult = false;
var callerelement = false;
var concatnum = 0;
var editing = false;
var opened = false;
var wwwroot = false;

function get_questiontext() {
    
    var questiontextvalue = false;
    
    // Search for the question text vars 
    // http://moodle.org/mod/forum/discuss.php?d=16953
    if (window.frames.length > 0) {
        questiontextvalue = frames[0].document.body.innerHTML;        
        
    } else if (document.getElementById("id_questiontext")) {
        questiontextvalue = document.getElementById("id_questiontext").innerHTML;
    }
    
    if (!questiontextvalue) {
        return false;
    }
    
    var pattern = /\{\$[a-zA-Z0-9]*\}/g;
    if (questiontextvalue.match(pattern) == null) {
        return false;
    }
    
    return questiontextvalue;
}

function display_vars(element, novarsstring, edit) {

    callerelement = element;

    if (edit != undefined) {
        editing = edit;
    }
    
    var varsheader = document.getElementById("varsheader");
    varsheader.style.visibility = "visible";
    varsheader.style.display = "inline";
    
    var questiontextvalue = get_questiontext();
    
    // If there aren't vars display a message
    if (!questiontextvalue) {
        
        var container = document.getElementById("id_vars_content");
        container.innerHTML = '<span class="error">' + novarsstring + '</span>';
        return false;
    }

    
    display_section("action=displayvars&questiontext=" + questiontextvalue);
}

function functionsection_visible() {
    
    var functionheader = document.getElementById("functionheader");
    var fakecaller = document.getElementById("id_programmedrespfid");
    
    // If it's hidden let's show it
    if (functionheader.style.visibility == "hidden" || functionheader.style.display == "none") {
    	functionheader.style.visibility = "visible";
    	functionheader.style.display = "inline";
    	
    // If there is a selected function reload the function arguments
    } else if (fakecaller) {
    	display_args(fakecaller);
    } else {
    	alert('ni un ni laltre');
    }
    
    return false;
}

function display_functionslist(element) {
    
    callerelement = element;
    
    if (opened == true) {
        var functiondiv = window.opener.document.getElementById("id_programmedrespfid_content");
        var category = window.opener.document.getElementById("id_functioncategory");
    } else {
        var functiondiv = document.getElementById("id_programmedrespfid_content");
        var category = document.getElementById("id_functioncategory");
    }
    
    // Hide function data until a new function selection
    functiondiv.style.visibility = "hidden";
    functiondiv.style.display = "none";
    
    return display_section("action=displayfunctionslist&categoryid=" + category.value);
}

function display_args(element) {
    
    callerelement = element;

    var functionid = document.getElementById("id_programmedrespfid").value;

    // Show function div
    var functiondiv = document.getElementById("id_programmedrespfid_content");
    functiondiv.style.visibility = "visible";
    functiondiv.style.display = "inline";
    
    // Concatenated vars
    var concatstring = "";
    for (var i = 0; i < concatnum; i++) {

        var concatelement = document.getElementById("concatvar_" + i);
        if (concatelement != false) {
            for (var elementi = 0; elementi < concatelement.options.length; elementi++) {
                if (concatelement.options[elementi].selected) {
                    concatstring += "&concatvar_" + i + "[]=" + concatelement.options[elementi].value;
                }
            }
        }
    }

    // function id + question text to extract the vars + the concatenated vars created
    return display_section("action=displayargs&function=" + functionid + "&questiontext=" + get_questiontext() + concatstring);
}


function display_section(params) {
    
    if (opened == true) {
        var contentdiv = window.opener.document.getElementById(callerelement.id + "_content");
    } else {
        var contentdiv = document.getElementById(callerelement.id + "_content");
    }
    contentdiv.innerHTML = "";
    
    // TODO: Posar-li un loading.gif
    
    // Responses manager
    var callbackHandler = 
    {
          success: process_display_section,
          failure: failure_display_section,
          timeout: 50000
    };

    var prefix = '';
    if (wwwroot != false) {
        prefix = wwwroot + "/question/";
    }
    YAHOO.util.Connect.asyncRequest("POST", prefix + "type/programmedresp/contents.php", callbackHandler, params);
    
    return callbackResult;
}

function process_display_section(transaction) {

    if (opened == true) {
        var contentdiv = window.opener.document.getElementById(callerelement.id + "_content");
    } else {
        var contentdiv = document.getElementById(callerelement.id + "_content");
    }
    contentdiv.innerHTML = transaction.responseText;
    
    callbackResult = false;
    callerelement = false;
    
    // The editing param will only be true when calling display_vars on edition
    if (editing != false) {
        if (opened == true) {
            var argscaller = window.opener.document.getElementById("id_programmedrespfid");
        } else {
            var argscaller = document.getElementById("id_programmedrespfid");
        }
        display_args(argscaller);
        editing = false;
    }
}

function failure_display_section() {    callbackResult = false;}


function display_responselabels() {

    var responselabelsdiv;
    var responselabelslinkdiv;
    
    if (responselabelsdiv = document.getElementById("id_responseslabels")) {
        responselabelsdiv.style.visibility = "visible";
        responselabelsdiv.style.display = "inline";
    }
    
    if (responselabelslinkdiv = document.getElementById("id_responselabelslink")) {
        responselabelslinkdiv.style.visibility = "hidden";
        responselabelslinkdiv.style.display = "none";
    }
    
    return false;
}


function add_concat_var() {
    
    var maindiv = document.getElementById("id_concatvars");
    
    // HTML to add
    var html = '<strong>concatvar_' + concatnum + '</strong><br/>';
    html += '<select id="concatvar_' + concatnum + '" name="concatvar_' + concatnum + '[]" multiple="multiple">';
    
    // Getting var names
    var questiontextvalue = get_questiontext();
    
    var pattern = /\{\$[a-zA-Z0-9]*\}/g;
    var matches = questiontextvalue.match(pattern);
    if (matches == null) {
        return false;
    }
    for (var i = 0; i < matches.length; i++) {
        var matchstr = matches[i].substr(2, (matches[i].length - 3));
        html += '<option value="' + matchstr + '">' + matchstr + '</option>';
    }
    
    html += '</select><br/><br/>';
    
    var vardiv = document.createElement("div");
    vardiv.innerHTML = html;
    
    maindiv.appendChild(vardiv);
    concatnum = concatnum + 1;
}


function change_argument_type(element, argumentkey) {

    var types = new Array('fixed', 'variable', 'guidedquiz', 'concat');
    var tmpelement;
    
    for (var i = 0; i < types.length; i++) {
        
        tmpelement = document.getElementById("id_argument_" + types[i] + "_" + argumentkey);

        if (element.value == i) {
            tmpelement.style.visibility = "visible";
            tmpelement.style.display = "inline";
        } else {
            tmpelement.style.visibility = "hidden";
            tmpelement.style.display = "none";
        }    
    }
}

// TODO: Add a check_maximum and check_minimum to ensure max > min

function check_numeric(element, message) {
    
    var value = element.value;
    var regex = /(^-?\d\d*\.\d*$)|(^-?\d\d*$)|(^-?\.\d\d*$)/;
    
    if (value == '' || !regex.test(value)) {
        return qf_errorHandler(element, message);
    } else {
        return qf_errorHandler(element, '');
    }
}


function add_to_parent(id, name, openerelementid, afterkey) {
    
    var openerselect = window.opener.document.getElementById(openerelementid);
    var optionslength = openerselect.options.length;
    
    var newoption = document.createElement('option');
    newoption.value = id;
    newoption.text = name;
    
    // If it's a function
    if (afterkey == undefined) {
        openerselect.options[optionslength] = newoption;
        return true;
    }
    
    // Store an identation char
    // The dirties hack I've ever seen
    var rootidentchar = openerselect.options[0].text.substr(0, 1);
    
    // After the parent option
    for (var i = 0; i < openerselect.options.length; i++) {
        
        // Move one position down each option
        if (openerselect.options[i].value == afterkey) {
            
            // Getting and adding to the new option the parent identation
            var identations = '';
            
            // If his parent is the root nothing
            if (afterkey == 0) {
                
            // If it's a root child two
            } else if (openerselect.options[i].text[0] != rootidentchar) {
                identations = rootidentchar + rootidentchar;
                
            // Any other case iterate
            } else {
                while (openerselect.options[i].text.indexOf(identations) != -1) {
                    identations = identations + openerselect.options[i].text.substr(0, 2);
                }
            }
            newoption.text = identations + newoption.text;
            
            // Add the child option and move the others
            var tmpoption = false;
            var nextoption = openerselect.options[i + 1];
            openerselect.options[i + 1] = newoption;
                                 
            for (var j = (i + 2); j <= openerselect.options.length; j++) {
                tmpoption = openerselect.options[j];
                openerselect.options[j] = nextoption;
                nextoption = tmpoption;
            }
            
            // Selecting the new option
            openerselect.selectedIndex = (i + 1);
            
            return true;
        }
    }
}


function update_addfunctionurl() {
    
    if (opened == true) {
        var categoryelement = window.opener.document.getElementById("id_functioncategory");
        var functionelement = window.opener.document.getElementById("id_addfunctionurl");
    } else {
        var categoryelement = document.getElementById("id_functioncategory");
        var functionelement = document.getElementById("id_addfunctionurl");
    }
    
    // If there is no function edition capability
    if (!functionelement) {
        return true;
    }
    
    // Add the index
    var fcatidindex = functionelement.href.indexOf("&fcatid", 0);
    if (fcatidindex == -1) {
        functionelement.href = functionelement.href + "&fcatid=" + categoryelement.value;
    } else {
        functionelement.href = functionelement.href.substr(0, fcatidindex) + "&fcatid=" + categoryelement.value;
    }
    
}