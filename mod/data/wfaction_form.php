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
 * A form for the creation and editing of action state.
 *
 * @copyright 2012 onwards Martin Dougiamas  {@link http://moodle.com}
 * @author    Wiktor.Wandachowicz AT p.lodz.pl
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   mod-data
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

        $mform->addElement('text','actname', get_string('actionname', 'data'),'maxlength="254" size="80"');
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
                if (fetch_action_by_name($this->_customdata['wfid'], $actname, $data['state'])) {
                    $errors['actname'] = get_string('actionnameexists', 'data', $actname);
                }
            }
        } else if (fetch_action_by_name($this->_customdata['wfid'], $actname, $data['state'])) {
            $errors['actname'] = get_string('actionnameexists', 'data', $actname);
        }

        return $errors;
    }

    function get_editor_options() {
        return $this->_customdata['editoroptions'];
    }
}
