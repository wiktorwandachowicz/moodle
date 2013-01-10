<?php

/**
 * Create//edit action state form.
 *
 * @copyright &copy; 2006 The Open University
 * @author N.D.Freear AT open.ac.uk
 * @author J.White AT open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod-data
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');
require_once('wflib.php');

/// get url variables
class actionedit_form extends moodleform {

    // Define the form
    function definition () {
        global $USER, $CFG, $COURSE;

        $mform =& $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];

        $wfname = $this->_customdata['wfname'];
        $mform->addElement('text','wfname', get_string('workflow', 'data'), array('value'=>$wfname,'size'=>80,'disabled'=>'disabled'));

        $mform->addElement('html', '<br/>');

        $mform->addElement('text','actname', get_string('action', 'data'),'maxlength="254" size="80"');
        $mform->addRule('actname', get_string('required'), 'required', null, 'client');
        $mform->setType('actname', PARAM_TEXT);

//        $mform->addElement('editor', 'actdescr', get_string('description'), null, $editoroptions);
//        $mform->setType('actdescr_editor', PARAM_RAW);

        $mform->addElement('text','actdescr', get_string('description'),'maxlength="254" size="80"');
        $mform->addRule('actdescr', get_string('required'), 'required', null, 'client');
        $mform->setType('actdescr', PARAM_TEXT);

        $wfid = $this->_customdata['wfid'];
        $states = get_all_states($wfid);
        $options = array();
        foreach ($states as $state) {
            $options[$state->id] = $state->statename;;
        }

        $mform->addElement('html', '<br/>');
        $mform->addElement('select','state', get_string('startstate', 'data'), $options);
        $mform->addElement('select','tostate', get_string('targetstate', 'data'), $options);

/*
        $mform->addElement('editor', 'description_editor', get_string('groupdescription', 'group'), null, $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('passwordunmask', 'enrolmentkey', get_string('enrolmentkey', 'group'), 'maxlength="254" size="24"', get_string('enrolmentkey', 'group'));
        $mform->addHelpButton('enrolmentkey', 'enrolmentkey', 'group');
        $mform->setType('enrolmentkey', PARAM_RAW);

        if (!empty($CFG->gdversion)) {
            $options = array(get_string('no'), get_string('yes'));
            $mform->addElement('select', 'hidepicture', get_string('hidepicture'), $options);

            $mform->addElement('filepicker', 'imagefile', get_string('newpicture', 'group'));
            $mform->addHelpButton('imagefile', 'newpicture', 'group');
        }
*/
        $mform->addElement('hidden','id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden','d');
        $mform->setType('d', PARAM_INT);

        $mform->addElement('hidden','wf');
        $mform->setType('wf', PARAM_INT);

        $mform->addElement('hidden','courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);

        $textlib = textlib_get_instance();

        $actname = trim($data['actname']);
        if ($data['id'] and $action = $DB->get_record('data_wf_actions', array('id'=>$data['id']))) {
            if ($textlib->strtolower($action->actname) != $textlib->strtolower($actname)) {
                //if (fetch_ation_by_name($data['wfid'], $actname)) {
                //    $errors['actname'] = get_string('actionnameexists', 'data', $actname);
                //}
            }
/*
            if (!empty($CFG->groupenrolmentkeypolicy) and $data['enrolmentkey'] != '' and $group->enrolmentkey !== $data['enrolmentkey']) {
                // enforce password policy only if changing password
                $errmsg = '';
                if (!check_password_policy($data['enrolmentkey'], $errmsg)) {
                    $errors['enrolmentkey'] = $errmsg;
                }
            }
*/
        } else if (fetch_state_by_name($statename)) {
            $errors['statename'] = get_string('statenameexists', 'data', $statename);
        }

        return $errors;
    }

    function get_editor_options() {
        return $this->_customdata['editoroptions'];
    }
}
