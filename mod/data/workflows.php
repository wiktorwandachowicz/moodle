<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Allow setting workflow state for roles
 *
 * @copyright 2012 onwards Martin Dougiamas  {@link http://moodle.com}
 * @author    Wiktor.Wandachowicz AT p.lodz.pl
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   mod-data
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('wflib.php');

$id       = optional_param('id', 0, PARAM_INT);      // course module id
$d        = optional_param('d', 0, PARAM_INT);       // database id
$wfid     = optional_param('wf', 0, PARAM_INT);      // workflow id
$stateid  = optional_param('state', 0, PARAM_INT);   // workflow state id
$tostate  = optional_param('tostate', 0, PARAM_INT); // target state id
$actionid = optional_param('action', 0, PARAM_INT);   // action id (state transition)
$mode     = optional_param('mode', 'wfdefinitions', PARAM_ALPHA);
$action   = workflows_param_action();

// Support either single state= parameter, or array states[]
if ($stateid) {
    $stateids = array($stateid);
} else {
    $stateids = optional_param_array('states', array(), PARAM_INT);
}
$singlestate = (count($stateids) == 1);

// Support either single action= parameter, or array actions[]
if ($actionid) {
    $actionids = array($actionid);
} else {
    $actionids = optional_param_array('actions', array(), PARAM_INT);
}
$singleaction = (count($actionids) == 1);
if ($singleaction) {
    $actionid = $actionids[0];
}

$classformode = array(
    'wfdefinitions' => 'workflow_definitions_page',  //unused
    'wfactions'     => 'workflow_actions_page',  //unused
    'wfallows'      => 'state_role_allow_page',
);
if (!isset($classformode[$mode])) {
    print_error('invalidmode', '', '', $mode);
}

$url = new moodle_url('/mod/data/workflows.php');

if ($id) {
    $url->param('id', $id);
    $PAGE->set_url($url);
    if (! $cm = get_coursemodule_from_id('data', $id)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
        print_error('coursemisconf');
    }
    if (! $data = $DB->get_record('data', array('id'=>$cm->instance))) {
        print_error('invalidcoursemodule');
    }

} else {
    $url->param('d', $d);
    $PAGE->set_url($url);
    if (! $data = $DB->get_record('data', array('id'=>$d))) {
        print_error('invalidid', 'data');
    }
    if (! $course = $DB->get_record('course', array('id'=>$data->course))) {
        print_error('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance('data', $data->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

$url->param('mode', $mode);

$baseurl = new moodle_url($url);
if (! empty($wfid)) {
    $url->param('wf', $wfid);
}
if (! empty($stateid)) {
    $url->param('state', $stateid);
}

#print_error('onlyadmins', '', '', '');

require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/data:manageworkflows', $context);

// Check for multiple/no state errors
if (!$singlestate) {
    switch($action) {
        case 'ajax_getworkflowstates':
        //case 'showgroupsettingsform':
        //case 'showaddmembersform':
        //case 'updatemembers':
            print_error('errorselectone', 'data', $url);
    }
}

switch ($action) {
    case false: //OK, display form.
        break;

    case 'ajax_getworkflowstates':
/*
        $states = array();
        if ($groupmemberroles = groups_get_members_by_role($groupids[0], $courseid, 'u.id,u.firstname,u.lastname'))
            foreach($groupmemberroles as $roleid=>$roledata) {
                $shortroledata = new stdClass();
                $shortroledata->name = $roledata->name;
                $shortroledata->users = array();
                foreach($roledata->users as $member) {
                    $shortmember = new stdClass();
                    $shortmember->id = $member->id;
                    $shortmember->name = fullname($member, true);
                    $shortroledata->users[] = $shortmember;
                }
                $roles[] = $shortroledata;
            }
        }
*/
//      echo json_encode($states);
        die;  // Client side JavaScript takes it from here.

    case 'updatestates': //OK, display form.
        break;

    case 'showeditworkflowform':
        redirect(new moodle_url('/mod/data/wfedit.php', array('d'=>$d,'courseid'=>$course->id,'id'=>$wfid)));
        break;

    case 'deleteworkflow':
        redirect(new moodle_url('/mod/data/wfedit.php', array('d'=>$d,'courseid'=>$course->id,'id'=>$wfid,'delete'=>1)));
        break;

    case 'showcreateworkflowform':
        redirect(new moodle_url('/mod/data/wfedit.php', array('d'=>$d,'courseid'=>$course->id)));
        break;

    case 'showeditstatesform':
        redirect(new moodle_url('/mod/data/wfstate.php', array('d'=>$d,'wf'=>$wfid,'id'=>$stateids[0])));
        break;

    case 'deletestate':
        redirect(new moodle_url('/mod/data/wfstate.php', array('d'=>$d,'wf'=>$wfid,'id'=>$stateids[0],'delete'=>1)));
        break;

    case 'showcreatestateform':
        redirect(new moodle_url('/mod/data/wfstate.php', array('d'=>$d,'wf'=>$wfid)));
        break;

    case 'updatetargets': //OK, display form.
        $actionid = 0; //always select first action for state when refreshing
        break;

    case 'editaction':
        redirect(new moodle_url('/mod/data/wfaction.php', array('d'=>$d,'wf'=>$wfid,'id'=>$actionid)));
        break;

    case 'deleteaction':
        redirect(new moodle_url('/mod/data/wfaction.php', array('d'=>$d,'wf'=>$wfid,'id'=>$actionid,'delete'=>1)));
        break;

    case 'addaction':
        redirect(new moodle_url('/mod/data/wfaction.php', array('d'=>$d,'wf'=>$wfid,'state'=>$stateid,'tostate'=>$tostate)));
        break;
}

$controller = null;

if ($mode == "wfallows") {
   $controller = new $classformode[$mode]($wfid);

   if (optional_param('submit', false, PARAM_BOOL) && data_submitted() && confirm_sesskey()) {
      $controller->process_submission();
      mark_context_dirty($context->path);
      add_to_log(SITEID, 'data', 'workflow edit ' . $mode, str_replace($CFG->wwwroot . '/', '', $url), '', '', $USER->id);
      redirect($url);
   }
}

if (! empty($controller))
  $controller->load_current_settings();


/// Print the page header

//$PAGE->navbar->add(get_string('workflows','data'));
$PAGE->navbar->add(get_string($mode,'data'));

#$PAGE->requires->js('/mod/data/data.js');
$PAGE->set_title($data->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report');
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($data->name));

$currentgroup = groups_get_activity_group($cm);
$groupmode = groups_get_activity_groupmode($cm);

/// Print the tabs.
$currenttab = 'workflows';
include('tabs.php');


if ($mode == "wfdefinitions") {

    $options = get_workflow_options($course->id, false);

    echo $OUTPUT->heading(get_string('wfdefslist', 'data'), 3);

    echo '<form id="wfdefinitionseditform" action="' .$baseurl. '" method="post">'."\n";
    echo '<div>'."\n";
#echo '<input type="hidden" name="id" value="' . $courseid . '" />'."\n";

    echo '<table cellpadding="6" class="generaltable generalbox groupmanagementtable boxaligncenter" summary="">'."\n";
    echo '<tr>'."\n";

    echo "<td>\n";
    echo '<p><label for="wf"><span id="workflowslabel">'.get_string('workflows', 'data').':</span><span id="thegrouping">&nbsp;</span></label></p>'."\n";

#echo '<select name="groups[]" multiple="multiple" id="groups" size="15" class="select" onchange="'.$onchange.'"'."\n";
#echo ' onclick="window.status=this.selectedIndex==-1 ? \'\' : this.options[this.selectedIndex].title;" onmouseout="window.status=\'\';">'."\n";

#echo '<select name="workflows[]" multiple="multiple" id="workflows" size="12" class="select">'."\n";
#echo '<select name="wf" id="wf" size="12" class="select">'."\n";

    $selectedwf = '&nbsp;';

    if ($options) {
        echo simple_html_select($options, 'wf', $wfid, array('size'=>'12', 'class'=>'select'));
    } else {
        echo '<select name="wf" id="wf" size="12" class="select">'."\n";
        // Print an empty option to avoid the XHTML error of having an empty select element
        echo '<option>&nbsp;</option>'."\n";
        echo '</select>'."\n";
    }

#echo '</select>'."\n";

    echo '<p><input type="submit" name="act_updatestates" id="updatestates" value="'
            . get_string('showwfstates', 'data') . '" /></p>'."\n";
    echo '<p><input type="submit" name="act_showeditworkflowform" '
            . 'id="showeditworkflowform" value="' . get_string('editworkflow', 'data'). '" /></p>'."\n";
    echo '<p><input type="submit" name="act_deleteworkflow" '
            . 'id="deleteworkflow" value="' . get_string('deleteselectedworkflow', 'data'). '" /></p>'."\n";
    echo '<p><input type="submit" name="act_showcreateworkflowform" '
            . 'id="showcreateworkflowform" value="' . get_string('createworkflow', 'data'). '" /></p>'."\n";

    echo '</td>'."\n";
    echo '<td>'."\n";

    if ($wfid) {
        $states = get_all_states($wfid);
    } else {
        $states = NULL;
    }
    $selectedname = '&nbsp;';

    echo '<p><label for="states"><span id="stateslabel">'.get_string('states', 'data').':</span></label></p>'."\n";
    echo '<select name="states[]" id="states" size="12" class="select">'."\n";

    if ($states) {
        // Print out the HTML
        foreach ($states as $state) {
            $select = '';
            $statename = $state->statename;
            if (in_array($state->id,$stateids)) {
                $select = ' selected="selected"';
                if ($singlestate) {
                    // Only keep selected name if there is one state selected
                    $selectedname = $statename;
                }
            }

            echo "<option value=\"{$state->id}\"$select title=\"$statename\">$statename</option>\n";
        }
    } else {
        // Print an empty option to avoid the XHTML error of having an empty select element
        echo '<option>&nbsp;</option>';
    }

    echo '</select>'."\n";

    echo '<p><input type="submit" name="act_showeditstatesform" '
            . 'id="showeditstatesform" value="' . get_string('editstate', 'data'). '" /></p>'."\n";
    echo '<p><input type="submit" name="act_deletestate" '
            . 'id="deletestate" value="' . get_string('deleteselectedstate', 'data'). '" /></p>'."\n";
    echo '<p><input type="submit" name="act_showcreatestateform" '
            . 'id="showcreatestateform" value="' . get_string('createstate', 'data'). '" /></p>'."\n";

    echo '</td>'."\n";
    echo '</tr>'."\n";
    echo '</table>'."\n";

//<input type="hidden" name="rand" value="om" />
    echo '</div>'."\n";
    echo '</form>'."\n";


} else if ($mode == "wfactions") {

    $options = get_workflow_options($course->id);

    echo '<div class="buttons">'.PHP_EOL;
    echo '<form id="wfselectform" action="' . $baseurl . '" method="post">'.PHP_EOL;
    echo workflow_selector($options, $wfid).PHP_EOL;
    echo '</form></div>'.PHP_EOL;

    echo $OUTPUT->heading(get_string('wfactions', 'data'), 3);

    echo '<form id="wfactionseditform" action="' .$baseurl. '" method="post">'."\n";
    echo '<input type="hidden" name="wf" value="' . $wfid . '" />'."\n";
    echo '<div>'."\n";
#echo '<input type="hidden" name="id" value="' . $courseid . '" />'."\n";

    echo '<table cellpadding="6" class="generaltable generalbox groupmanagementtable boxaligncenter" summary="">'."\n";
    echo '<tr>'."\n";

    echo "<td>\n";
    echo '<p><label for="state"><span id="statelabel">'.get_string('startstates', 'data').':</span><span id="thegrouping">&nbsp;</span></label></p>'."\n";

    if ($wfid) {
        $states = get_all_states($wfid);
    } else {
        $states = NULL;
    }
    $selectedname = '&nbsp;';

#echo '<select name="groups[]" multiple="multiple" id="groups" size="15" class="select" onchange="'.$onchange.'"'."\n";
#echo ' onclick="window.status=this.selectedIndex==-1 ? \'\' : this.options[this.selectedIndex].title;" onmouseout="window.status=\'\';">'."\n";

    echo '<select name="state" id="state" size="12" class="select">'."\n";

    if ($states) {
        // Print out the HTML
        foreach ($states as $state) {
            $select = '';
            $statename = $state->statename;
            if (empty($stateid)) {
                // Select first state if none selected
                $stateid = $state->id;
                $stateids = array($stateid);
                $singlestate = true;
            }
            if (in_array($state->id,$stateids)) {
                $select = ' selected="selected"';
                if ($singlestate) {
                    // Only keep selected name if there is one state selected
                    $selectedname = $statename;
                }
            }

            echo "<option value=\"{$state->id}\"$select title=\"$statename\">$statename</option>\n";
        }
    } else {
        // Print an empty option to avoid the XHTML error of having an empty select element
        echo '<option>&nbsp;</option>';
    }

    echo '</select>'."\n";
    echo '<p><input type="submit" name="act_updatetargets" id="updatetargets" value="'
            . get_string('showtargetsforstate', 'data') . '" /></p>'."\n";

    echo '</td>'."\n";
    echo '<td>'."\n";

    echo '<p><label for="actions"><span id="actionslabel">'.
        get_string('availableactions', 'data').
        '</span></label></p>'."\n";
#        '</span> <span id="fromstate"><b>'.$selectedname.'</b></span></label></p>'."\n";

    $actstates = get_target_actions_and_states($stateid);

//NOTE: the SELECT was, multiple="multiple" name="tostates[]" - not used and breaks onclick.
#echo '<select name="tostates" id="tostates" size="15" class="select"'."\n";
#echo ' onclick="window.status=this.options[this.selectedIndex].title;" onmouseout="window.status=\'\';">'."\n";
echo '<select name="actions[]" id="actions" size="12" class="select">'."\n";

    if ($actstates) {
        // Print out the HTML
        foreach ($actstates as $actstate) {
            $select = '';
/*
            if (empty($actionid)) {
                // Select first action if none selected
                $actionid = $actstate->id;
                $actionids = array($actionid);
                $singleaction = true;
            }
*/
            if (in_array($actstate->id, $actionids)) {
                $select = ' selected="selected"';
            }
            echo '<option value="'.$actstate->id.'"'.$select .
                 '>[ '.$actstate->actname.' ] : '.$selectedname.' &gt;&gt; '.$actstate->statename.'</option>';
        }
    } else {
        // Print an empty option to avoid the XHTML error of having an empty select element
        echo '<option>&nbsp;</option>';
    }

    echo '</select>'."\n";

    echo '<p><input type="submit" name="act_editaction" '
            . 'id="editaction" value="' . get_string('editaction', 'data'). '" /></p>'."\n";
    echo '<p><input type="submit" name="act_deleteaction" '
            . 'id="deleteaction" value="' . get_string('deleteaction', 'data'). '" /></p>'."\n";

    echo '<hr />'."\n";
    if ($selectedname) {
        echo '<p>'.$selectedname.' &gt;&gt; '."\n";
        echo '<select name="tostate" id="tostate" class="select">'."\n";

        if ($states) {
            // Print out the HTML
            foreach ($states as $state) {
                $statename = $state->statename;
                echo "<option value=\"{$state->id}\"$select title=\"$statename\">$statename</option>\n";
            }
        } else {
            // Print an empty option to avoid the XHTML error of having an empty select element
            echo '<option>&nbsp;</option>';
        }

        echo '</select></p>'."\n";
    }
    echo '<p class="wfaction"><input type="submit" name="act_addaction" '
            . 'id="addaction" value="' . get_string('addaction', 'data'). '" /></p>'."\n";

    echo '</td>'."\n";
    echo '</tr>'."\n";
    echo '</table>'."\n";

//<input type="hidden" name="rand" value="om" />
    echo '</div>'."\n";
    echo '</form>'."\n";


} else if ($mode == "wfallows") {

    echo $OUTPUT->box($controller->get_intro_text());

    $options = get_workflow_options($course->id);
    echo '<div class="buttons">'.PHP_EOL;
    echo '<form id="wfselectform" action="' . $baseurl . '" method="post">'.PHP_EOL;
    echo workflow_selector($options, $wfid).PHP_EOL;
    echo '</form></div>'.PHP_EOL;

    if (! empty($wfid)) {
        $table = $controller->get_table();
        echo '<form id="wfallowsform" action="' . $url . '" method="post">'.PHP_EOL;
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />'.PHP_EOL;
        echo html_writer::table($table).PHP_EOL;
        echo '<div class="buttons"><input type="submit" name="submit" value="'.get_string('savechanges').'"/>'.PHP_EOL;
        echo '</div></form>'.PHP_EOL;
    }
}

/// Finish the page
echo $OUTPUT->footer();
