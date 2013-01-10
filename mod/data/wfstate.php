<?php

/**
 * Lets the user edit state definitions.
 *
 * Responds to actions:
 *   add       - add a new state (no 'id' param given)
 *   edit      - edit the definition of a state ('id' param given)
 *   delete    - delete a state, actions and allows, reset state of records
 *               referring to deleted state ('id' and 'delete' params given)
 *
 * @package    mod-data
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
    $context = get_context_instance(CONTEXT_SYSTEM);
    $PAGE->set_context($context);
} else {
    require_login($course);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
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
    'stateid'=>$id, 'wfname'=>$wfname));
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

exit;




/**
 * Create state OR edit state settings.
 *
 * @copyright &copy; 2006 The Open University
 * @author N.D.Freear AT open.ac.uk
 * @author J.White AT open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod-data
 */
/*
require_once(dirname(__FILE__) . '/../../config.php');
require_once('lib.php');
require_once('wfstate_form.php');

/// get url variables
$courseid = optional_param('courseid', 0, PARAM_INT);
$stateid  = optional_param('state', 0, PARAM_INT);
$confirm  = optional_param('confirm', 0, PARAM_BOOL);

if ($stateid) {
    if (!$workflow = $DB->get_record('data_wf', array('id'=>$wfid))) {
        print_error('invalidworkflowid', 'data');
    }
    if (empty($courseid)) {
        $courseid = $workflow->courseid;

    } else if ($courseid != $workflow->courseid) {
        print_error('invalidcourseid');
    }

    if ($courseid && !$course = $DB->get_record('course', array('id'=>$courseid))) {
        print_error('invalidcourseid');
    }

} else {
    if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
        print_error('invalidcourseid');
    }
    $group = new stdClass();
    $group->courseid = $course->id;
}

if ($id !== 0) {
    $PAGE->set_url('/group/group.php', array('id'=>$id));
} else {
    $PAGE->set_url('/group/group.php', array('courseid'=>$courseid));
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('moodle/course:managegroups', $context);

$returnurl = $CFG->wwwroot.'/group/index.php?id='.$course->id.'&group='.$id;

if ($id and $delete) {
    if (!$confirm) {
        $PAGE->set_title(get_string('deleteselectedgroup', 'group'));
        $PAGE->set_heading($course->fullname . ': '. get_string('deleteselectedgroup', 'group'));
        echo $OUTPUT->header();
        $optionsyes = array('id'=>$id, 'delete'=>1, 'courseid'=>$courseid, 'sesskey'=>sesskey(), 'confirm'=>1);
        $optionsno  = array('id'=>$courseid);
        $formcontinue = new single_button(new moodle_url('group.php', $optionsyes), get_string('yes'), 'get');
        $formcancel = new single_button(new moodle_url($baseurl, $optionsno), get_string('no'), 'get');
        echo $OUTPUT->confirm(get_string('deletegroupconfirm', 'group', $group->name), $formcontinue, $formcancel);
        echo $OUTPUT->footer();
        die;

    } else if (confirm_sesskey()){
        if (groups_delete_group($id)) {
            redirect('index.php?id='.$course->id);
        } else {
            print_error('erroreditgroup', 'group', $returnurl);
        }
    }
}

// Prepare the description editor: We do support files for group descriptions
$editoroptions = array('maxfiles'=>EDITOR_UNLIMITED_FILES, 'maxbytes'=>$course->maxbytes, 'trust'=>false, 'context'=>$context, 'noclean'=>true);
if (!empty($group->id)) {
    $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', $group->id);
} else {
    $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', null);
}

/// First create the form
$editform = new group_form(null, array('editoroptions'=>$editoroptions));
$editform->set_data($group);

if ($editform->is_cancelled()) {
    redirect($returnurl);

} elseif ($data = $editform->get_data()) {

    if ($data->id) {
        groups_update_group($data, $editform, $editoroptions);
    } else {
        $id = groups_create_group($data, $editform, $editoroptions);
        $returnurl = $CFG->wwwroot.'/group/index.php?id='.$course->id.'&group='.$id;
    }

    redirect($returnurl);
}

$strgroups = get_string('groups');
$strparticipants = get_string('participants');

if ($id) {
    $strheading = get_string('editgroupsettings', 'group');
} else {
    $strheading = get_string('creategroup', 'group');
}

$PAGE->navbar->add($strparticipants, new moodle_url('/user/index.php', array('id'=>$courseid)));
$PAGE->navbar->add($strgroups, new moodle_url('/group/index.php', array('id'=>$courseid)));
$PAGE->navbar->add($strheading);

/// Print header
$PAGE->set_title($strgroups);
$PAGE->set_heading($course->fullname . ': '.$strgroups);
echo $OUTPUT->header();
echo '<div id="grouppicture">';
if ($id) {
    print_group_picture($group, $course->id);
}
echo '</div>';
$editform->display();
echo $OUTPUT->footer();
*/