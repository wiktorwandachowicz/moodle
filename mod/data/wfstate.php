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
 * Lets the user edit state definitions.
 *
 * Responds to actions:
 *   add       - add a new state (no 'id' param given)
 *   edit      - edit the definition of a state ('id' param given)
 *   delete    - delete a state, actions and allows, reset state of records
 *               referring to deleted state ('id' and 'delete' params given)
 *
 * @copyright 2012 onwards Martin Dougiamas  {@link http://moodle.com}
 * @author    Wiktor.Wandachowicz AT p.lodz.pl
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   mod-data
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once('wfstate_form.php');
require_once('wflib.php');

/// Get url variables
$courseid = optional_param('courseid', 0, PARAM_INT);
$wfid     = optional_param('wf', 0, PARAM_INT);   // workflow id
$d        = optional_param('d', 0, PARAM_INT);   // database id
$id       = optional_param('id', 0, PARAM_INT);   // state id
$delete   = optional_param('delete', 0, PARAM_INT);    //delete stateid
$confirm  = optional_param('confirm', 0, PARAM_BOOL);
//$action   = workflows_param_action();

if ($id) {
    // Load state record
    if (!$state = $DB->get_record('data_wf_states', array('id'=>$id))) {
        print_error('invalidstateid', 'data');
    }

    // Check workflow correctness
    if (!$wfid) {
        $wfid = $state->wfid;
    } else if ($state->wfid && ($wfid != $state->wfid)) {
        print_error('invalidworkflowid');
    }
    // Load workflow record
    if ($wfid && !$workflow = $DB->get_record('data_wf', array('id'=>$wfid))) {
        print_error('invalidworkflowid');
    }

} else {
    // Load workflow record
    if (!$wfid) {
        print_error('invalidworkflowid');
    } elseif (!$workflow = $DB->get_record('data_wf', array('id'=>$wfid))) {
        print_error('invalidworkflowid');
    }
    // Prepare new state
    $state = new stdClass();
    $state->wfid = $wfid;
    $state->notification = STATE_NOTIFY_CREATOR;
}

// Check course correctness
if (!$courseid) {
    $courseid = $workflow->courseid;
} else if ($workflow->courseid && ($courseid != $workflow->courseid)) {
    print_error('invalidcourseid');
}
// Load course record
if ($courseid && !$course = $DB->get_record('course', array('id'=>$courseid))) {
    print_error('invalidcourseid');
}

// Remember $d in state to be able to return to correct database after edit/cancel
$state->d = $d;
// Remember $wf in state to be able to return to correct database after edit/cancel
$state->wf = $wfid;

if ($id !== 0) {
    $PAGE->set_url('/mod/data/wfstate.php', array('id'=>$id));
} else {
    $PAGE->set_url('/mod/data/wfstate.php', array('wf'=>$wfid));
}

if ($workflow && !$courseid) {
    // Editing global workflow
    $course = null;
    require_login();
    $context = context_system::instance();
    $PAGE->set_context($context);
} else {
    require_login($course);
    $context = context_course::instance($course->id);
}

require_capability('mod/data:manageworkflows', $context);

$returnurl = new moodle_url('/mod/data/workflows.php', array('d'=>$d,'wf'=>$workflow->id));

if ($id and $delete) {
    if (!$confirm) {
        $PAGE->set_title(get_string('deletestate', 'data'));
        $PAGE->set_heading(($course ? $course->fullname . ': ' : '') . get_string('deletestate', 'data'));
        $PAGE->navbar->add(get_string('workflows', 'data'), $returnurl);
        $PAGE->navbar->add(get_string('deletestate', 'data'));
        echo $OUTPUT->header();
        $optionsyes = array('d'=>$d, 'wf'=>$wfid, 'id'=>$id, 'delete'=>1,
                            'courseid'=>$courseid, 'sesskey'=>sesskey(), 'confirm'=>1);
        $optionsno  = array('d'=>$d, 'wf'=>$wfid, 'state'=>$id);
        $formcontinue = new single_button(new moodle_url('wfstate.php', $optionsyes), get_string('yes'), 'get');
        $formcancel = new single_button(new moodle_url($returnurl, $optionsno), get_string('no'), 'get');
        $details = '<p><b>(' . $state->statename . ')</b></p>';
        echo $OUTPUT->confirm(get_string('deletestateconfirm', 'data', $details), $formcontinue, $formcancel);
        echo $OUTPUT->footer();
        die;

    } else if (confirm_sesskey()){
        if (wf_delete_state($wfid, $id)) {
            redirect($returnurl);
        } else {
            print_error('erroreditstate', 'data', $returnurl);
        }
    }
}

/*
// Prepare the description editor: We do support files for group descriptions
$editoroptions = array('maxfiles'=>EDITOR_UNLIMITED_FILES, 'maxbytes'=>$course->maxbytes, 'trust'=>false, 'context'=>$context, 'noclean'=>true);
if (!empty($group->id)) {
    $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', $group->id);
} else {
    $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', null);
}
*/
$editoroptions = array('maxfiles'=>0, 'context'=>$context);
$wfname = ($workflow ? $workflow->wfname : '');
/// First create the form
$editform = new stateedit_form(null, array('editoroptions'=>$editoroptions,
    'wfid'=>$wfid, 'stateid'=>$id, 'wfname'=>$wfname));
$editform->set_data($state);

if ($editform->is_cancelled()) {
    if ($id)
        $returnurl->param('state', $id);
    redirect($returnurl);

} elseif ($data = $editform->get_data()) {
    if ($data->id) {
        wf_update_state($data, $editform, $editoroptions);
        $returnurl->param('state', $data->id);
    } else {
        $id = wf_create_state($data, $editform, $editoroptions);
        $returnurl->param('state', $id);
    }
    redirect($returnurl);
}

$strworkflows = get_string('workflows', 'data');

if ($id) {
    $strheading = get_string('editstate', 'data');
} else {
    $strheading = get_string('createstate', 'data');
}

$PAGE->navbar->add($strworkflows, $returnurl);
$PAGE->navbar->add($strheading);

/// Print header
$PAGE->set_title($strheading);
$PAGE->set_heading(($course ? $course->fullname.': ' : '') . $strheading);
echo $OUTPUT->header();
echo $OUTPUT->heading($strheading);

echo '<div id="stateeditform">'.PHP_EOL;
$editform->display();
echo '</div>'.PHP_EOL;

echo $OUTPUT->footer();
