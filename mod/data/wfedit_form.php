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
 * A form for the creation and editing of workflow.
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
