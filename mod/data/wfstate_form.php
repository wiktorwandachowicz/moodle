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
 * A form for the creation and editing of workflow state.
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

        $options = array(STATE_NOTIFY_NOBODY => get_string('notifynobody', 'data'),
            STATE_NOTIFY_CREATOR => get_string('notifycreator', 'data'),
            STATE_NOTIFY_BOTH => get_string('notifyboth', 'data'),
            STATE_NOTIFY_SUPERVISOR => get_string('notifysupervisor', 'data'));
        $mform->addElement('select', 'notification', get_string('statenotification', 'data'), $options);
        $mform->addRule('notification', get_string('required'), 'required', null, 'client');

        $mform->addHelpButton('notification', 'statenotification', 'data');

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
                if (fetch_state_by_name($this->_customdata['wfid'], $statename)) {
                    $errors['statename'] = get_string('statenameexists', 'data', $statename);
                }
            }
        } else if (fetch_state_by_name($this->_customdata['wfid'], $statename)) {
            $errors['statename'] = get_string('statenameexists', 'data', $statename);
        }

        return $errors;
    }

    function get_editor_options() {
        return $this->_customdata['editoroptions'];
    }
}
