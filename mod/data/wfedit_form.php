<?php

/**
 * Create//edit workflow form.
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
class workflow_form extends moodleform {

    // Define the form
    function definition () {
        global $USER, $CFG, $COURSE;

        $mform =& $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];

        $mform->addElement('text','wfname', get_string('workflowname', 'data'),'maxlength="254" size="80"');
        $mform->addRule('wfname', get_string('required'), 'required', null, 'client');
        $mform->setType('wfname', PARAM_TEXT);

        $statecount = $this->_customdata['statecount'];
        if ($statecount > 0)
            $wfstates = get_workflow_state_options($this->_customdata['wfid'], true);
        else
            $wfstates = array(0=>get_string('nostates', 'data'))
                        + get_workflow_state_options($this->_customdata['wfid']);

        $mform->addElement('select', 'initstateid', get_string('initstate', 'data'), $wfstates);

        $mform->addElement('html', '<br/>');

        $mform->addElement('checkbox','wflocal', get_string('localworkflow', 'data'));
        $mform->addHelpButton('wflocal', 'localworkflow', 'data');
        $mform->disabledIf('wflocal', 'wfglobal', 'neq', 1);

        $coursename = $this->_customdata['coursename'];
        $mform->addElement('text','coursename', get_string('currentcourse', 'data'), array('value'=>$coursename,'size'=>80,'disabled'=>'disabled'));

        $mform->addElement('hidden','id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden','d');
        $mform->setType('d', PARAM_INT);

        $mform->addElement('hidden','course');
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden','wfglobal');
        $mform->setType('wfglobal', PARAM_BOOL);

        $mform->addElement('hidden','courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);

        $textlib = textlib_get_instance();

        $wfname = trim($data['wfname']);
        if ($data['id'] and $workflow = $DB->get_record('data_wf', array('id'=>$data['id']))) {
            if ($textlib->strtolower($workflow->wfname) != $textlib->strtolower($wfname)) {
                if (fetch_workflow_by_name($wfname)) {
                    $errors['wfname'] = get_string('workflownameexists', 'data', $wfname);
                }
            }
        } else if (fetch_workflow_by_name($wfname)) {
            $errors['wfname'] = get_string('workflownameexists', 'data', $wfname);
        }

        return $errors;
    }

    function get_editor_options() {
        return $this->_customdata['editoroptions'];
    }
}
