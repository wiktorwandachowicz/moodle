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
 * Lets the user edit workflow definitions.
 *
 * Responds to actions:
 *   add       - add a new workflow (no 'id' param given)
 *   edit      - edit the definition of a workflow ('id' param given)
 *   delete    - delete a workflow its states, actions and allows,
 *               reset state of records referring to deleted states
 *               ('id' and 'delete' params given)
 *
 * @copyright 2012 onwards Martin Dougiamas  {@link http://moodle.com}
 * @author    Wiktor.Wandachowicz AT p.lodz.pl
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   mod-data
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once('wfedit_form.php');

/// Get url variables
$courseid = optional_param('courseid', 0, PARAM_INT);
$d        = optional_param('d', 0, PARAM_INT);   // database id
$id       = optional_param('id', 0, PARAM_INT);   // workflow id
$delete   = optional_param('delete', 0, PARAM_INT);    //delete workflowid
$confirm  = optional_param('confirm', 0, PARAM_BOOL);

if ($id) {
    if (!$workflow = $DB->get_record('data_wf', array('id'=>$id))) {
        print_error('invalidworkflowid', 'data');
    }

    if (!$courseid) {
        $courseid = $workflow->courseid;
    } else if ($workflow->courseid && ($courseid != $workflow->courseid)) {
        print_error('invalidcourseid');
    }

    if ($courseid && !$course = $DB->get_record('course', array('id'=>$courseid))) {
        print_error('invalidcourseid');
    }

} else {
    if ($courseid && !$course = $DB->get_record('course', array('id'=>$courseid))) {
        print_error('invalidcourseid');
    }

    $workflow = new stdClass();
    $workflow->courseid = $courseid;
}

// Remember $d in workflow to be able to return to correct database after edit/cancel
$workflow->d = $d;
// Remember $wflocal in workflow to determine value for 'localworkflow' checkbox
$workflow->wflocal = ($workflow->courseid != 0);
// Remember $course in workflow to be able to restore courseid if wflocal is true
$workflow->course = $courseid;
// Remember $wfglobal in workflow to allow changing (enable) 'localworkflow' checkbox
$systemcontext = context_system::instance();
$hassiteconfig = has_capability('moodle/site:config', $systemcontext);
$workflow->wfglobal = (int)$hassiteconfig;

if ($id !== 0) {
    $PAGE->set_url('/mod/data/wfedit.php', array('id'=>$id));
} else {
    $PAGE->set_url('/mod/data/wfedit.php', array('courseid'=>$courseid));
}

if ($workflow && !$courseid) {
    // Editing global workflow
    $course = null;
    require_login();
    $context = $systemcontext;
    $PAGE->set_context($context);
} else {
    require_login($course);
    $context = context_course::instance($course->id);
}
require_capability('mod/data:manageworkflows', $context);

$returnurl = new moodle_url('/mod/data/workflows.php', array('d'=>$d));

if ($id and $delete) {
    if (!$confirm) {
        $PAGE->set_title(get_string('deleteworkflow', 'data'));
        $PAGE->set_heading(($course ? $course->fullname . ': ' : '') . get_string('deleteworkflow', 'data'));
        $PAGE->navbar->add(get_string('workflows', 'data'), $returnurl);
        $PAGE->navbar->add(get_string('deleteworkflow', 'data'));
        echo $OUTPUT->header();
        $optionsyes = array('d'=>$d, 'id'=>$id, 'delete'=>1,
                            'courseid'=>$courseid, 'sesskey'=>sesskey(), 'confirm'=>1);
        $optionsno  = array('d'=>$d, 'wf'=>$id);
        $formcontinue = new single_button(new moodle_url('wfedit.php', $optionsyes), get_string('yes'), 'get');
        $formcancel = new single_button(new moodle_url($returnurl, $optionsno), get_string('no'), 'get');
        $details = '<p><b>(' . $workflow->wfname . ')</b></p>';
        echo $OUTPUT->confirm(get_string('deleteworkflowconfirm', 'data', $details), $formcontinue, $formcancel);
        echo $OUTPUT->footer();
        die;

    } else if (confirm_sesskey()){
        if (wf_delete_workflow($id)) {
            redirect($returnurl);
        } else {
            print_error('erroreditworkflow', 'data', $returnurl);
        }
    }
}

$statecount = $DB->count_records('data_wf_states', array('wfid'=>$id));
$editoroptions = array('maxfiles'=>0, 'context'=>$context);
$shortname = ($course ? $course->shortname : '');
/// First create the form
$editform = new workflow_form(null, array('editoroptions'=>$editoroptions,
    'wfid'=>$id, 'statecount'=>$statecount, 'coursename'=>$shortname));
$editform->set_data($workflow);

if ($editform->is_cancelled()) {
    if ($id)
        $returnurl->param('wf', $id);
    redirect($returnurl);

} elseif ($data = $editform->get_data()) {
    if ($data->id) {
        wf_update_workflow($data, $editform, $editoroptions);
        $returnurl->param('wf', $data->id);
    } else {
        $id = wf_create_workflow($data, $editform, $editoroptions);
        $returnurl->param('wf', $id);
    }
    redirect($returnurl);
}

$strworkflows = get_string('workflows', 'data');

if ($id) {
    $strheading = get_string('editworkflow', 'data');
} else {
    $strheading = get_string('createworkflow', 'data');
}

$PAGE->navbar->add($strworkflows, $returnurl);
$PAGE->navbar->add($strheading);

/// Print header
$PAGE->set_title($strheading);
//$PAGE->set_heading(($course ? $course->fullname.': ' : '') . $strheading);
echo $OUTPUT->header();
echo $OUTPUT->heading($strheading);

echo '<div id="workfloweditform">'.PHP_EOL;
$editform->display();
echo '</div>'.PHP_EOL;

echo $OUTPUT->footer();
