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
 * Extension of library code used by the roles administration interfaces,
 * to allow editing roles allowed to set workflow states for database records.
 *
 * @copyright 2012 onwards Martin Dougiamas  {@link http://moodle.com}
 * @author    Wiktor.Wandachowicz AT p.lodz.pl
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   mod-data
 */

require_once($CFG->dirroot . '/'. $CFG->admin . '/roles/lib.php');

// Definitions of state's notifications, to determine to who
// send e-mails when database record enters a specific state.
define('STATE_NOTIFY_NOBODY',     0);
define('STATE_NOTIFY_CREATOR',    1);
define('STATE_NOTIFY_SUPERVISOR', 2);
define('STATE_NOTIFY_BOTH',       3);

/**
 * Returns all workflow states in correct sort order.
 *
 * @param int $wfid database workflowid
 * @return array
 */
function get_all_states($wfid = null) {
    global $DB;
    if (empty($wfid)) $wfid = 0;
    return $DB->get_records('data_wf_states', array('wfid' => $wfid), 'statename ASC');
}

/**
 * Find and return state based on name.
 *
 * @param int $wfid database workflowid
 * @param string $statename state name
 * @return object
 */
function fetch_state_by_name($wfid, $statename) {
    global $DB;
    return $DB->get_record('data_wf_states', array('wfid'=>$wfid, 'statename'=>$statename));
}

/**
 * Creates a record in the wf_state_role_allow table
 *
 * @param int $wfid database workflowid
 * @param int $fromroleid source roleid
 * @param int $stateid workflow stateid
 * @return void
 */
function allow_state($wfid, $fromroleid, $stateid) {
    global $DB;

    $record = new stdClass();
    $record->wfid         = $wfid;
    $record->roleid       = $fromroleid;
    $record->allowstateid = $stateid;
    $DB->insert_record('data_wf_state_role_allow', $record);
}

/**
 * Returns actions available for workflow state in correct sort order.
 *
 * @param int $stateid workflow stateid
 * @return array
 */
function get_state_actions($stateid) {
    global $DB;
    return $DB->get_records('data_wf_actions', array('fromstateid' => $stateid), 'actname ASC');
}

/**
 * Find and return action based on name.
 *
 * @param int $wfid database workflowid
 * @param string $actionname action name
 * @param int $stateid workflow start stateid
 * @return object
 */
function fetch_action_by_name($wfid, $actname, $stateid) {
    global $DB;
    return $DB->get_record('data_wf_actions', array('wfid'=>$wfid, 'actname'=>$actname, 'fromstateid'=>$stateid));
}

/**
 * Returns target states for workflow state in correct sort order.
 *
 * @param int $stateid workflow stateid
 * @return array
 */
function get_target_states($stateid) {
    global $DB;
    $states = $DB->get_records_sql(
        "SELECT s.id AS stateid, s.wfid, s.statename, s.statedescr
           FROM {data_wf_actions} a, {data_wf_states} s
          WHERE a.tostateid = s.id
            AND a.fromstateid = $stateid");
    return $states;
}

/**
 * Returns actions (combined with target states) for workflow state in correct sort order.
 *
 * @param int $stateid workflow stateid
 * @return array
 */
function get_target_actions_and_states($stateid) {
    global $DB;
    $actstates = $DB->get_records_sql(
        "SELECT a.*, s.statename, s.statedescr
           FROM {data_wf_actions} a, {data_wf_states} s
          WHERE a.tostateid = s.id
            AND a.fromstateid = $stateid");
    return $actstates;
}

/**
 * Find workflow state for each passed record.
 *
 * @param array $records array of rows from {data_records} table
 * @return array set of states
 */
function get_records_states(&$records) {
    global $DB;
    $recordids = join(',', array_keys($records));
    $states = $DB->get_records_sql(
        "SELECT r.id, s.id AS stateid, s.wfid, s.statename, s.statedescr
           FROM {data_records} r, {data_wf_states} s
          WHERE r.wfstateid = s.id
            AND r.id IN ($recordids)");
    return $states;
}

/**
 * Find allowed states for user roles.
 *
 * @param int $wfid workflowid
 * @param array $roles array of rows from {role} table, typically
 *              a result of calling get_user_roles() or get_course_user_roles()
 * @return array set of allowstateid
 */
function get_role_allowed_states($wfid, &$roles) {
    global $DB;
    $roleids = array();
    foreach ($roles as $role) {
        $roleids[] = $role->roleid;
    }
    $roleids = join(',', $roleids); // Reuse $roleids variable
    $allows = $DB->get_records_sql(
        "SELECT DISTINCT allowstateid
           FROM {data_wf_state_role_allow}
          WHERE wfid = ?
            AND roleid IN ($roleids)", array('wfid' => $wfid));
    return $allows;
}

/**
 * Get initial state for workflow.
 *
 * @param integer $wfid workflowid
 * @return int initial state id or 0 (if workflow not found)
 */
function get_initial_workflow_state($wfid) {
    global $DB;
    $wf = $DB->get_record('data_wf', array('id'=>$wfid), 'initstateid');
    if ($wf)
        return $wf->initstateid;
    else
        return 0;
}

/**
 * Finds and returns all workflows based on list of course ids.
 *
 * @param array $courseids array of course ids.
 * @return object
 */
function fetch_workflows($courseids) {
    global $DB;
    return $DB->get_records_list('data_wf', 'courseid', $courseids, 'wfname ASC');
}

/**
 * Static function returning all global workflows.
 *
 * @return object
 */
function fetch_workflows_global() {
    return fetch_workflows(array(0));
}

/**
 * Static function returning all local course workflows.
 *
 * @param int $courseid course id
 * @return object
 */
function fetch_workflows_local($courseid) {
    return fetch_workflows(array($courseid));
}

/**
 * Static function returning all global and local course workflows.
 *
 * @param int $courseid course id
 * @return object
 */
function fetch_available_workflows($courseid) {
    return fetch_workflows(array($courseid,0));
}

/**
 * Find and return workflow based on name.
 *
 * @param string $wfname workflow name
 * @return object
 */
function fetch_workflow_by_name($wfname) {
    global $DB;
    return $DB->get_record('data_wf', array('wfname'=>$wfname));
}

/**
 * Prepare list of options (workflows) for generating elements of <select> tag.
 *
 * @param int $courseid course id
 * @param bool $selectoption flag for including default '-- select workflow --' element
 * @return array
 */
function get_workflow_options($courseid, $selectoption = true) {
    $workflows = fetch_available_workflows($courseid);

    $options = array();
    if ($selectoption === true) {
        $options[0] = get_string('selectworkflow', 'data');
    }

    foreach ($workflows as $wf) {
        $options[$wf->id] = $wf->wfname;
    }

    return $options;
}

/**
 * Prepare list of options (states) for generating elements of <select> tag.
 *
 * @param int $wfid workflow id
 * @param bool $selectoption flag for including default '-- select state --' element
 * @return array
 */
function get_workflow_state_options($wfid, $selectoption = true) {
    $states = get_all_states($wfid);

    $options = array();
    if ($selectoption === true) {
        $options[0] = get_string('selectstate', 'data');
    }

    foreach ($states as $state) {
        $options[$state->id] = $state->statename;
    }

    return $options;
}

/**
 * Format <select> HTML tag.
 *
 * @param array $options array of 'key'=>'value' used to generate <select> options
 * @param string $id identifier used for 'id=' and 'name=' attributes of generated tag
 * @param string $data key for the option which should be selected
 * @param array $attrs additional attributes for generated <select> tag
 * @return string HTML fragment
 */
function simple_html_select($options, $id, $data, $attrs = array()) {
    $selecthtml = '<select id="'.$id.'" name="'.$id.'"';
    foreach ($attrs as $key => $value) {
        $selecthtml .= ' '.$key.'="'.$value.'"';
    }
    $selecthtml .= '>';
    foreach ($options as $key => $value) {
    // the string cast is needed because key may be integer - 0 is equal to most strings!
        $selecthtml .= '<option value="'.$key.'"'.((string)$key==$data ? ' selected="selected"' : '').'>'.$value.'</option>';
    }
    $selecthtml .= '</select>';
    return $selecthtml;
}

/**
 * Prepare workflow selector.
 *
 * @param array $options array of 'key'=>'value' used to generate <select> options
 * @param int $wfid database workflow id
 * @return string HTML fragment
 */
function workflow_selector($options, $wfid) {
    $html = get_string('selectedworkflow', 'data');
    $html .= ' '.simple_html_select($options, 'wf', $wfid).' ';
    $html .= '<input type="submit" name="change" value="'.get_string('show').'"/>';
    return $html;
}

/**
 * Find all roles for current user in course context.
 *
 * Return only switched role, if role has been switched.
 * @param int $courseid course id
 * @return array roles for current $USER
 */
function get_course_user_roles($courseid) {
    global $DB, $USER;
    $roles = array();
    $coursecontext = context_course::instance($courseid);
    if (is_role_switched($courseid)) { // Has switched roles
        if ($role = $DB->get_record('role', array('id'=>$USER->access['rsw'][$coursecontext->path]))) {
            // Return only switched role
            $role->roleid = $role->id;
            $roles[$role->id] = $role;
        }
    } else {
        // Get all user roles
        $roles = get_user_roles($coursecontext, $USER->id);
    }
    return $roles;
}

/**
 * Set additional fields for each record passed - which control display of
 * workflow state, edit controls and buttons of action form for the state.
 *
 * @param int $courseid course id
 * @param array $records array of rows from {data_records} table
 * @param int $workflowid workflow id
 * @param bool $showactions control if changes and actions should be displayed
 */
function set_records_allowed_actions($courseid, &$records, $workflowid, $showactions=true) {
    global $DB;

    $roles = get_course_user_roles($courseid);
    // Find workflow states for records
    $states = get_records_states($records);
    // Find allowed states for user roles
    $allows = get_role_allowed_states($workflowid, $roles);

    // Add state names to records, set allowchange flag
    foreach ($records as $record) {
        if (array_key_exists($record->id, $states)) {
            if (array_key_exists($record->wfstateid, $allows)) {
                // State allowed, show edit controls and actions
                $record->statename = $states[$record->id]->statename;
                $record->statedescr = $states[$record->id]->statedescr;
                $record->allowchange = $showactions;
                $record->showactions = $showactions;
            } else {
                $wfid = $states[$record->id]->wfid;
                if ($wfid == $workflowid) {
                    // State not allowed, but is from current workflow - just show state
                    $record->statename = $states[$record->id]->statename;
                    $record->statedescr = $states[$record->id]->statedescr;
                } else {
                    // Oops, state from another workflow
                    $wf = $DB->get_record('data_wf', array('id'=>$wfid));
                    $wfname = empty($wf) ? '' : $wf->wfname;
                    // Format descriptive warning, including as much relevant info as possible
                    $record->statename = '<b>'.get_string('incorrectstate','data').'</b> <em>"'.$states[$record->id]->statename.'"</em>';
                    $record->statedescr = '<em>'.get_string('workflow','data').': '.(empty($wfname) ? 'id='.$wfid : '"'.$wfname.'"').'</em>';
                }
                $record->allowchange = false;
                $record->showactions = $showactions;
            }
        } else {
            $record->statename = 'Unknown state (id='.$record->wfstateid.')';
            $record->statedescr = '';
            $record->allowchange = $showactions;
            $record->showactions = $showactions;
        }
    }
}

/**
 * Set default values of fields which control display of workflow info/actions.
 * Used when no workflow support is required.
 *
 * @param array $records array of rows from {data_records} table
 * @param bool $showactions control if changes and actions should be displayed
 */
function set_records_no_actions(&$records, $showactions=true) {
    // Add empty state names to records
    foreach ($records as $record) {
        $record->statename = '';
        $record->statedescr = '';
        $record->allowchange = $showactions;
        $record->showactions = $showactions;
    }
}

/**
 * Check if current workflow state of a record allows changes
 * (editing, deleting, changing state).
 *
 * @param object $data
 * @param int $courseid course id
 * @param int $rid record id
 * @param object $record row from {data_records} table (optional)
 * @return bool
 */
function data_workflow_allows_change($data, $courseid, $rid, $record = NULL) {
    global $DB;

    // Read record from $DB if none given
    if (empty($record)) {
        $record = $DB->get_record('data_records', array('id'=>$rid));
    }
    $records[$rid] = $record;

    // Check if workflow support is necessary
    $hasworkflow = ($data->workflowenable > 0 && $data->workflowid > 0);
    if ($hasworkflow) {
        set_records_allowed_actions($courseid, $records, $data->workflowid);
    } else {
        set_records_no_actions($records, true);
    }

    // Check if changes are allowed
    return $records[$rid]->allowchange;
}

function send_workflow_state_notifications($user, $data, $cm, $record, $stateid) {
    global $DB, $CFG;

    // Abort if no record given
    if (empty($record)) {
        return;
    }

    // Check if workflow support is necessary
    $hasworkflow = ($data->workflowenable > 0 && $data->workflowid > 0);
    if ($hasworkflow) {
        // Read workflow state from database
        $wfstate = $DB->get_record('data_wf_states', array('id' => $stateid));

        if (!empty($wfstate) && !empty($wfstate->notification)) {
            $course = $DB->get_record('course', array('id' => $data->course));
            $url = new moodle_url($CFG->wwwroot.'/mod/data/view.php',
                array('d' => $data->id, 'rid' => $record->id));

            // Prepare e-mail subject
            $subject = get_string('statenotificationemailsubject', 'data', array(
                'course' => $course->fullname,   // {$a->course} - name of the course
                'db' => $data->name));           // {$a->db} - name of the database
            // Prepare e-mail body
            $body = get_string('statenotificationemailbody', 'data', array(
                'user' => fullname($user),       // {$a->user} - name of the user (who makes the change)
                'db' => $data->name,             // {$a->db} - name of the database
                'state' => $wfstate->statename,  // {$a->state} - name of the workflow state
                'url' => ''.$url));              // {$a->url} - URL pointing to the changed record

            if (($wfstate->notification == STATE_NOTIFY_CREATOR) || ($wfstate->notification == STATE_NOTIFY_BOTH)) {
                // Send notification to the creator of the record
                $creator = $DB->get_record('user', array('id' => $record->userid));
                if ($creator) {
                    //echo "email_to_user() creator='$creator->username : $creator->firstname $creator->lastname : $creator->email".PHP_EOL;
                    //echo "<br/>".$subject."<br/>".$body."<br/>".PHP_EOL;
                    email_to_user($creator, generate_email_supportuser(), $subject, $body);
                }
            }

            if (($wfstate->notification == STATE_NOTIFY_SUPERVISOR) || ($wfstate->notification == STATE_NOTIFY_BOTH)) {
                // Send notification to workflow supervisor(s)
                $context = context_module::instance($cm->id);
                $supervisors = get_enrolled_users($context, 'mod/data:superviseworkflow');
                foreach ($supervisors as $recip) {
                    //echo "email_to_user() supervisor='$recip->firstname $recip->lastname : $recip->email".PHP_EOL;
                    //echo "<br/>".$subject."<br/>".$body."<br/>".PHP_EOL;
                    email_to_user($recip, generate_email_supportuser(), $subject, $body);
                }
            }
        }
    }
}

/**
 * Prepare content of a form for switching state of a record.
 *
 * @param int $dataid database recordid
 * @param object $record a row from {data_records} table
 * @param array $roles set of allowstateid - result of call to get_course_user_roles()
 * @return string HTML fragment
 */
function workflow_actions_form($dataid, $record) { //, $roles) {
    global $DB, $OUTPUT;
    $html = '';
    $actions = array();

    if ($dataid) {
        $actions = get_state_actions($record->wfstateid);
    }

    $html .= html_writer::start_tag('span', array('class'=>'buttons'));
    $html .= html_writer::start_tag('span', array('class'=>'singlebutton'));
    $html .= html_writer::start_tag('div', array('id'=>'workflow_box_'.$record->id, 'class'=>'box generalbox feedback_items wfaction', 'style'=>'padding:6px'));

    $html .= get_string('actions', 'data') . ' ';

    if (count($actions) > 0) {
        foreach ($actions as $action) {
            $actionbutton = new single_button(new moodle_url('/mod/data/view.php?d='.$dataid.'&stateid='.$action->tostateid.'&rid='.$record->id), $action->actname, 'post');
            $html .= $OUTPUT->render($actionbutton);
        }
    } else {
        $html .= '<div class="wfactionunavailable"><i>' . get_string('wfactionunavailable', 'data') . '</i></div>';
    }

    $html .= html_writer::end_tag('div');
    $html .= html_writer::end_tag('span');
    $html .= html_writer::end_tag('span');
    return $html;
}


/**
 * Add a new workflow
 *
 * @param object $data workflow properties
 * @param object $editform
 * @param array $editoroptions
 * @return id of workflow or false if error
 */
function wf_create_workflow($data, $editform = false, $editoroptions = false) {
    global $DB;

    //check that courseid exists
    $course = $DB->get_record('course', array('id' => $data->courseid), '*', MUST_EXIST);
    $context = context_course::instance($course->id);

    $data->wfname = trim($data->wfname);

//    if ($editform and $editoroptions) {
//        $data->description = $data->description_editor['text'];
//        $data->descriptionformat = $data->description_editor['format'];
//    }

    $data->id = $DB->insert_record('data_wf', $data);

//    if ($editform and $editoroptions) {
//        // Update description from editor with fixed files
//        $data = file_postupdate_standard_editor($data, 'description', $editoroptions, $context, 'group', 'description', $data->id);
//        $upd = new stdClass();
//        $upd->id                = $data->id;
//        $upd->description       = $data->description;
//        $upd->descriptionformat = $data->descriptionformat;
//        $DB->update_record('groups', $upd);
//    }

    $workflow = $DB->get_record('data_wf', array('id'=>$data->id));

    //trigger workflows events
    events_trigger('wf_workflow_created', $workflow);

    return $workflow->id;
}

/**
 * Update workflow
 *
 * @param object $data workflow properties (with magic quotes)
 * @param object $editform
 * @param array $editoroptions
 * @return boolean true or exception
 */
function wf_update_workflow($data, $editform = false, $editoroptions = false) {
    global $DB;

//    $context = context_course::instance($data->courseid);

    $data->wfname = trim($data->wfname);

//    if ($editform and $editoroptions) {
//        $data = file_postupdate_standard_editor($data, 'description', $editoroptions, $context, 'group', 'description', $data->id);
//    }

    //only admins can make workflow global (courseid == 0)
    $hassiteconfig = has_capability('moodle/site:config', context_system::instance());

    if (empty($data->wflocal) && $hassiteconfig) {
        $data->courseid = 0;
    } elseif (!empty($data->course)) {
        $data->courseid = $data->course;
    }

    $DB->update_record('data_wf', $data);

    $workflow = $DB->get_record('data_wf', array('id'=>$data->id));

    //trigger workflows events
    events_trigger('wf_workflow_updated', $workflow);

    return true;
}

/**
 * Delete workflow
 *
 * Also disable workflow support for all Database activities which used deleted workflow
 *
 * @param int $wfid database workflow id
 * @return boolean true or exception
 */
function wf_delete_workflow($wfid) {
    global $DB;

    //check that wf exists
    $workflow = $DB->get_record('data_wf', array('id'=>$wfid), '*', MUST_EXIST);

    if ($workflow->courseid == 0) {
        //only admins can delete global workflow (courseid == 0)
        require_capability('moodle/site:config', context_system::instance());
    }

    //get all states of deleted workflow (they will be deleted as well)
    $states = $DB->get_records('data_wf_states', array('wfid'=>$wfid), null, 'id');
    if (count($states) > 0) {
        $stateids = join(',', array_keys($states));
        //clear state for records associated with states to be deleted
        $DB->set_field_select('data_records', 'wfstateid', 0, "wfstateid IN ($stateids)");
    }

    //disable workflow support in databases which used deleted workflow
    $DB->set_field('data', 'workflowenable', 0, array('workflowid'=>$wfid));
    $DB->set_field('data', 'workflowid', 0, array('workflowid'=>$wfid));

    //delete allowed roles for states
    $DB->delete_records('data_wf_state_role_allow', array('wfid'=>$wfid));
    //delete actions
    $DB->delete_records('data_wf_actions', array('wfid'=>$wfid));
    //delete states
    $DB->delete_records('data_wf_states', array('wfid'=>$wfid));

    //finally delete workflow
    $DB->delete_records('data_wf', array('id'=>$wfid));

    //trigger workflows events
    events_trigger('wf_workflow_deleted', $workflow);

    return true;
}

/**
 * Add a new workflow state
 *
 * Also use it as initial state, if it's the first state for a workflow
 *
 * @param object $data state properties
 * @param object $editform
 * @param array $editoroptions
 * @return id of state or false if error
 */
function wf_create_state($data, $editform = false, $editoroptions = false) {
    global $DB;

    //check that wf exists
    $workflow = $DB->get_record('data_wf', array('id'=>$data->wf), '*', MUST_EXIST);

    $data->statename = trim($data->statename);
    $data->statedescr = trim($data->statedescr);
    $data->wfid = $workflow->id;

    $data->id = $DB->insert_record('data_wf_states', $data);

    $state = $DB->get_record('data_wf_states', array('id'=>$data->id));

    //is this the first state for a workflow?
    $count = $DB->count_records('data_wf_states', array('wfid'=>$data->wfid));
    if ((int)$count == 1) {
        //use as initial state for workflow
        $DB->set_field('data_wf', 'initstateid', $state->id, array('id'=>$data->wfid));
    }

    //trigger states events
    events_trigger('wf_state_created', $state);

    return $state->id;
}

/**
 * Update workflow state
 *
 * @param object $data state properties (with magic quotes)
 * @param object $editform
 * @param array $editoroptions
 * @return boolean true or exception
 */
function wf_update_state($data, $editform = false, $editoroptions = false) {
    global $DB;

    $data->statename = trim($data->statename);
    $data->statedescr = trim($data->statedescr);

    $DB->update_record('data_wf_states', $data);

    $state = $DB->get_record('data_wf_states', array('id'=>$data->id));

    //trigger states events
    events_trigger('wf_state_updated', $state);

    return true;
}

/**
 * Delete workflow state
 *
 * @param int $wfid database workflow id
 * @param int $stateid id of state to delete
 * @return boolean true or exception
 */
function wf_delete_state($wfid, $stateid) {
    global $DB;

    //check that wf exists
    $workflow = $DB->get_record('data_wf', array('id'=>$wfid), '*', MUST_EXIST);

    if (!$state = $DB->get_record('data_wf_states', array('id'=>$stateid))) {
        //silently ignore attempts to delete missing or already deleted states
        return true;
    }

    //check that state is from correct wf
    if ($state->wfid != $workflow->id) {
        print_error('invalidworkflowid', 'data', $state->wfid);
    }

    //check if deleted state is the initial state for workflow
    if ($stateid == $workflow->initstateid) {
        //determine new initial state for workflow
        $allstates = get_all_states($wfid);
        $newstateid = 0;
        foreach ($allstates as $astate) {
            if ($astate->id !== $stateid) {
                $newstateid = $astate->id;
                break;
            }
        }
        //set new initial state for workflow
        $workflow->initstateid = $newstateid;
        $DB->update_record('data_wf', $workflow);
    } else {
        //use initial workflow state to reset state of associated records
        $newstateid = $workflow->initstateid;
    }

    //reset state for all records associated with deleted state
    $DB->set_field('data_records', 'wfstateid', $newstateid, array('wfstateid'=>$stateid));

    //delete allowed roles for deleted state
    $DB->delete_records('data_wf_state_role_allow', array('wfid'=>$wfid, 'allowstateid'=>$stateid));
    //delete actions associated with deleted state
    $DB->delete_records('data_wf_actions', array('wfid'=>$wfid, 'fromstateid'=>$stateid));
    $DB->delete_records('data_wf_actions', array('wfid'=>$wfid, 'tostateid'=>$stateid));

    //finally delete state
    $DB->delete_records('data_wf_states', array('id'=>$stateid));

    //trigger states events
    events_trigger('wf_state_deleted', $state);

    return true;
}

/**
 * Add a new action - transition between states
 *
 * @param object $data action properties (with magic quotes)
 * @param object $editform
 * @param array $editoroptions
 * @return id of action or false if error
 */
function wf_create_action($data, $editform = false, $editoroptions = false) {
    global $DB;

    //check that wf exists
    $workflow = $DB->get_record('data_wf', array('id'=>$data->wf), '*', MUST_EXIST);
    //check that both states exist
    $fromstate = $DB->get_record('data_wf_states', array('id'=>$data->state), '*', MUST_EXIST);
    $tostate = $DB->get_record('data_wf_states', array('id'=>$data->tostate), '*', MUST_EXIST);

    //check that both states are from the same wf
    if ($fromstate->wfid != $workflow->id) {
        print_error('invalidworkflowid', 'data', $fromstate->wfid);
    }
    if ($tostate->wfid != $workflow->id) {
        print_error('invalidworkflowid', 'data', $tostate->wfid);
    }

    $data->actname = trim($data->actname);
    $data->actdescr = trim($data->actdescr);
    $data->wfid = $workflow->id;

    $data->id = $DB->insert_record('data_wf_actions', $data);

    $action = $DB->get_record('data_wf_actions', array('id'=>$data->id));

    //trigger actions events
    events_trigger('wf_action_created', $action);

    return $action->id;
}

/**
 * Update action - transition between states
 *
 * @param object $data action properties (with magic quotes)
 * @param object $editform
 * @param array $editoroptions
 * @return boolean true or exception
 */
function wf_update_action($data, $editform = false, $editoroptions = false) {
    global $DB;

    //check that wf exists
    $workflow = $DB->get_record('data_wf', array('id'=>$data->wf), '*', MUST_EXIST);
    //check that both states exist
    $fromstate = $DB->get_record('data_wf_states', array('id'=>$data->state), '*', MUST_EXIST);
    $tostate = $DB->get_record('data_wf_states', array('id'=>$data->tostate), '*', MUST_EXIST);

    //check that both states are from the same wf
    if ($fromstate->wfid != $workflow->id) {
        print_error('invalidworkflowid', 'data', $fromstate->wfid);
    }
    if ($tostate->wfid != $workflow->id) {
        print_error('invalidworkflowid', 'data', $tostate->wfid);
    }

    $data->actname = trim($data->actname);
    $data->actdescr = trim($data->actdescr);

    $DB->update_record('data_wf_actions', $data);

    $action = $DB->get_record('data_wf_actions', array('id'=>$data->id));

    //trigger actions events
    events_trigger('wf_action_updated', $action);

    return true;
}

/**
 * Delete action - transition between states
 *
 * @param int $wfid database workflow id
 * @param int $actionid id of action to delete
 * @return boolean true or exception
 */
function wf_delete_action($wfid, $actionid) {
    global $DB;

    //check that wf exists
    $workflow = $DB->get_record('data_wf', array('id'=>$wfid), '*', MUST_EXIST);

    if (!$action = $DB->get_record('data_wf_actions', array('id'=>$actionid))) {
        //silently ignore attempts to delete missing or already deleted actions
        return true;
    }

    //check that action is from correct wf
    if ($action->wfid != $workflow->id) {
        print_error('invalidworkflowid', 'data', $action->wfid);
    }

    //delete action
    $DB->delete_records('data_wf_actions', array('id'=>$actionid));

    //trigger actions events
    events_trigger('wf_action_deleted', $action);

    return true;
}

/**
 * Subclass of role_allow_role_page for the workflow state role Allow tab.
 */
class state_role_allow_page extends role_allow_role_page {
    protected $wfid;
    protected $states;

    public function __construct($wfid) {
        $this->wfid = $wfid;
        $this->load_required_states();
        parent::__construct('data_wf_state_role_allow', 'allowstateid');
    }

    /**
     * Load information about all defined states for current workflow ($wfid).
     */
    protected function load_required_states() {
    /// Get all workflow states
        $this->states = get_all_states($this->wfid);
        //role_fix_names($this->roles, context_system::instance(), ROLENAME_ORIGINAL);
    }

    /**
     * Update the state data with the new settings submitted by the user.
     */
    public function process_submission() {
        global $DB;
    /// Delete all allowance records for workflow, then add back the ones that should be allowed.
        $DB->delete_records($this->tablename, array('wfid' => $this->wfid));
        foreach ($this->roles as $fromroleid => $notused) {
            foreach ($this->states as $stateid => $alsonotused) {
                if (optional_param('s_' . $fromroleid . '_' . $stateid, false, PARAM_BOOL)) {
                    $this->set_allow($fromroleid, $stateid);
                }
            }
        }
    }

    /**
     * Load the current state allows from the database.
     */
    public function load_current_settings() {
        global $DB;
    /// Load the current settings
        $this->allowed = array();
        foreach ($this->roles as $role) {
            if (count($this->states) > 0) {
                // Make an array $role->id => false. This is probably too clever for its own good.
                $this->allowed[$role->id] = array_combine(array_keys($this->states), array_fill(0, count($this->states), false));
            }
        }
        $rs = $DB->get_recordset($this->tablename);
        foreach ($rs as $allow) {
            $this->allowed[$allow->roleid][$allow->{$this->targetcolname}] = true;
        }
        $rs->close();
    }

    protected function set_allow($fromroleid, $stateid) {
        allow_state($this->wfid, $fromroleid, $stateid);
    }

    protected function get_cell_tooltip($fromrole, $state) {
        $a = new stdClass;
        $a->fromrole = $fromrole->localname;
        $a->state = $state->statename;
        return get_string('allowstateforrole', 'data', $a);
    }

    /**
     * @param int $stateid workflow state id.
     * @return boolean whether the user should be allowed to set Allow flags
     * for this state.
     */
    protected function is_allowed_state($stateid) {
        return true;
    }

    /**
     * @return object a $table structure that can be passed to print_table, containing
     * one cell for each checkbox.
     */
    public function get_table() {
        $table = new html_table();
        $table->tablealign = 'center';
        $table->cellpadding = 5;
        $table->cellspacing = 0;
        $table->width = '90%';
        $table->align = array('left');
        $table->rotateheaders = true;
        $table->head = array(get_string('workflowstate', 'data'));
        $table->colclasses = array('');

    /// Add role name headers.
        foreach ($this->roles as $targetrole) {
            $table->head[] = $targetrole->localname;
            $table->align[] = 'left';
        }

    /// Now the rest of the table.
        foreach ($this->states as $state) {
            $row = array( '<span title="'.$state->statename.' - '.$state->statedescr .'">'.$state->statename.' </span>' );
            foreach ($this->roles as $fromrole) {
                $checked = '';
                $disabled = '';
                if ($this->allowed[$fromrole->id][$state->id]) {
                    $checked = 'checked="checked" ';
                }
                if (!$this->is_allowed_target($fromrole->id)) {
                    $disabled = 'disabled="disabled" ';
                }
                $name = 's_' . $fromrole->id . '_' . $state->id;
                $tooltip = $this->get_cell_tooltip($fromrole, $state);
                $row[] = '<input type="checkbox" name="' . $name . '" id="' . $name .
                        '" title="' . $tooltip . '" value="1" ' . $checked . $disabled . '/>' .
                        '<label for="' . $name . '" class="accesshide">' . $tooltip . '</label>';
            }
            if ($this->is_allowed_state($state->id)) {
                $table->rowclasses[] = '';
            } else {
                $table->rowclasses[] = 'dimmed_text';
            }
            $table->data[] = $row;
        }

        return $table;
    }

    public function get_intro_text() {
        return get_string('configallowstateforrole', 'data');
    }
}

function workflows_param_action($prefix = 'act_') {
    $action = false;
//($_SERVER['QUERY_STRING'] && preg_match("/$prefix(.+?)=(.+)/", $_SERVER['QUERY_STRING'], $matches)) { //b_(.*?)[&;]{0,1}/

    if ($_POST) {
        $form_vars = $_POST;
    }
    elseif ($_GET) {
        $form_vars = $_GET;
    }
    if ($form_vars) {
        foreach ($form_vars as $key => $value) {
            if (preg_match("/$prefix(.+)/", $key, $matches)) {
                $action = $matches[1];
                break;
            }
        }
    }
    if ($action && !preg_match('/^\w+$/', $action)) {
        $action = false;
        print_error('unknowaction');
    }
    ///if (debugging()) echo 'Debug: '.$action;
    return $action;
}
