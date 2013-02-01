<?php

/**
 * Create//edit workflow state form.
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
class stateedit_form extends moodleform {

    // Define the form
    function definition () {
        global $USER, $CFG, $COURSE;

        $mform =& $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];

        $wfname = $this->_customdata['wfname'];
        $mform->addElement('text','wfname', get_string('workflow', 'data'), array('value'=>$wfname,'size'=>80,'disabled'=>'disabled'));

        $mform->addElement('html', '<br/>');

        $mform->addElement('text','statename', get_string('statename', 'data'),'maxlength="254" size="80"');
        $mform->addRule('statename', get_string('required'), 'required', null, 'client');
        $mform->setType('statename', PARAM_TEXT);

//        $mform->addElement('editor', 'statedescr', get_string('description'), null, $editoroptions);
//        $mform->setType('statedescr_editor', PARAM_RAW);

        $mform->addElement('text','statedescr', get_string('description'),'maxlength="254" size="80"');
        $mform->addRule('statedescr', get_string('required'), 'required', null, 'client');
        $mform->setType('statedescr', PARAM_TEXT);


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

        $statename = trim($data['statename']);
        if ($data['id'] and $state = $DB->get_record('data_wf_states', array('id'=>$data['id']))) {
            if ($textlib->strtolower($state->statename) != $textlib->strtolower($statename)) {
                if (fetch_state_by_name($statename)) {
                    $errors['statename'] = get_string('statenameexists', 'data', $statename);
                }
            }
        } else if (fetch_state_by_name($statename)) {
            $errors['statename'] = get_string('statenameexists', 'data', $statename);
        }

        return $errors;
    }

    function get_editor_options() {
        return $this->_customdata['editoroptions'];
    }
}
