<?php

/**
 * Lets the user edit actions (transitions between states).
 *
 * Responds to actions:
 *   add       - add a new action (no 'id' param given)
 *   edit      - edit the definition of an action ('id' param given)
 *   delete    - delete an action ('id' and 'delete' params given)
 *
 * @package    mod-data
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once('wfaction_form.php');
require_once('wflib.php');

/// Get url variables
$courseid  = optional_param('courseid', 0, PARAM_INT);
$wfid      = optional_param('wf', 0, PARAM_INT);   // workflow id
$d         = optional_param('d', 0, PARAM_INT);   // database id
$id        = optional_param('id', 0, PARAM_INT);   // action id
$stateid   = optional_param('state', 0, PARAM_INT);   // start state id
$tostateid = optional_param('tostate', 0, PARAM_INT);   // target state id
$delete    = optional_param('delete', 0, PARAM_INT);    // delete action id
$confirm   = optional_param('confirm', 0, PARAM_BOOL);

if ($id) {
    // Load action record
    if (!$action = $DB->get_record('data_wf_actions', array('id'=>$id))) {
        print_error('invalidactionid', 'data');
    }

    // Check workflow correctness
    if (!$wfid) {
        $wfid = $action->wfid;
    } else if ($action->wfid && ($wfid != $action->wfid)) {
        print_error('invalidworkflowid');
    }
    // Load workflow record
    if ($wfid && !$workflow = $DB->get_record('data_wf', array('id'=>$wfid))) {
        print_error('invalidworkflowid');
    }

    // Load states
    $state   = $DB->get_record('data_wf_states', array('id'=>$action->fromstateid));
    $tostate = $DB->get_record('data_wf_states', array('id'=>$action->tostateid));
    // Prepare parameters for edit form
    $action->state = $state->id;
    $action->tostate = $tostate->id;

} else {
    // Load workflow record
    if (!$wfid) {
        print_error('invalidworkflowid');
    } elseif (!$workflow = $DB->get_record('data_wf', array('id'=>$wfid))) {
        print_error('invalidworkflowid');
    }
    // Load states
    if (!$stateid || !$tostateid) {
        print_error('invalidstateid');
    } else {
        $state   = $DB->get_record('data_wf_states', array('id'=>$stateid));
        $tostate = $DB->get_record('data_wf_states', array('id'=>$tostateid));
    }
    // Check states correctness
    if ($wfid != $state->wfid || $wfid != $tostate->wfid) {
        print_error('invalidworkflowid');
    }
    // Prepare new action
    $action = new stdClass();
    $action->wfid = $wfid;
    $action->state = $stateid;
    $action->tostate = $tostateid;
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

// Remember $d in action to be able to return to correct database after edit/cancel
$action->d = $d;
// Remember $wf in action to be able to return to correct database after edit/cancel
$action->wf = $wfid;

if ($id !== 0) {
    $PAGE->set_url('/mod/data/wfaction.php', array('id'=>$id));
} else {
    $PAGE->set_url('/mod/data/wfaction.php', array('wf'=>$wfid,'state'=>$stateid,'tostate'=>$tostateid));
}

if ($workflow && !$courseid) {
    // Editing global workflow
    $course = null;
    require_login();
    $context = get_context_instance(CONTEXT_SYSTEM);
    $PAGE->set_context($context);
} else {
    require_login($course);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
}

require_capability('mod/data:manageworkflows', $context);

$returnurl = new moodle_url('/mod/data/workflows.php', array('d'=>$d,'mode'=>'wfactions','wf'=>$workflow->id,'action'=>$id));

if ($id and $delete) {
    if (!$confirm) {
        $PAGE->set_title(get_string('deleteaction', 'data'));
        $PAGE->set_heading(($course ? $course->fullname . ': ' : '') . get_string('deleteaction', 'data'));
        $PAGE->navbar->add(get_string('workflows', 'data'), $returnurl);
        $PAGE->navbar->add(get_string('deleteaction', 'data'));
        echo $OUTPUT->header();
        $optionsyes = array('d'=>$d, 'wf'=>$wfid, 'id'=>$id, 'delete'=>1,
                            'courseid'=>$courseid, 'sesskey'=>sesskey(), 'confirm'=>1);
        $optionsno  = array('d'=>$d, 'wf'=>$wfid);
        $formcontinue = new single_button(new moodle_url('wfaction.php', $optionsyes), get_string('yes'), 'get');
        $formcancel = new single_button(new moodle_url($returnurl, $optionsno), get_string('no'), 'get');
        $details = '<p><b>(' . $action->actname . ')</b></p>';
        echo $OUTPUT->confirm(get_string('deleteactionconfirm', 'data', $details), $formcontinue, $formcancel);
        echo $OUTPUT->footer();
        die;

    } else if (confirm_sesskey()){
        if (wf_delete_action($wfid, $id)) {
            redirect($returnurl);
        } else {
            print_error('erroreditaction', 'data', $returnurl);
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
$editform = new actionedit_form(null, array('editoroptions'=>$editoroptions,
    'wfid'=>$wfid, 'wfname'=>$wfname));
$editform->set_data($action);

if ($editform->is_cancelled()) {
    if ($id) {
        $returnurl->param('state', $state->id);
        $returnurl->param('tostate', $tostate->id);
    }
    redirect($returnurl);

} elseif ($data = $editform->get_data()) {
    // Convert parameter names
    $data->fromstateid = $data->state;
    $data->tostateid = $data->tostate;

    if ($data->id) {
        wf_update_action($data, $editform, $editoroptions);
        $returnurl->param('state', $data->state);
        $returnurl->param('tostate', $data->tostate);
    } else {
        $id = wf_create_action($data, $editform, $editoroptions);
        $returnurl->param('state', $data->state);
        $returnurl->param('tostate', $data->tostate);
    }
    redirect($returnurl);
}

$strworkflows = get_string('workflows', 'data');

if ($id) {
    $strheading = get_string('editaction', 'data');
} else {
    $strheading = get_string('addaction', 'data');
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
