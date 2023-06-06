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

namespace mod_data;

use context_module;
use stdClass;

/**
 * Helper trait with private fields test data for mod_data.
 * 
 * Used in following unit tests:
 * private_fields_template_test and private_fields_external_test.
 *
 * @package    mod_data
 * @category   test
 * @copyright  2023 Wiktor Wandachowicz <wiktor.wandachowicz@p.lodz.pl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.11
 */
trait private_fields_helper_trait {

    /**
     * Helper method to create a set of test data for private fields tests
     *
     * @return array containing author, course, roleid, activity, context, cmid, field, privatefield, entry and entries
     */
    function create_private_fields_test_data(bool $otherauthor, bool $otherentry, array $capabilities) {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $params = ['course' => $course];
        $activity = $this->getDataGenerator()->create_module(manager::MODULE, $params);
        $cm = get_coursemodule_from_id('data', $activity->cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');

        $user = $this->getDataGenerator()->create_user();
        $roleid = $roleids['student'];
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $roleid);

        $user2 = $this->getDataGenerator()->create_user();
        $roleid2 = $roleids['editingteacher'];
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, $roleid2);

        // Default capabilities as in mod/data/db/access.php
        $defaultcaps = [
            'editprivate' => false, 'editownprivate' => true,
            'viewprivate' => false, 'viewownprivate' => true,
        ];

        $author = $user;
        if ($otherauthor) {
            $author = $user2;
            $roleid = $roleid2;
            $defaultcaps = [
                'editprivate' => true, 'editownprivate' => true,
                'viewprivate' => true, 'viewownprivate' => true,
            ];
        }

        // Convert bool flags into CAP_{*} constants.
        foreach ($defaultcaps as $cap => $enable) {
            if (isset($capabilities[$cap])) {
                $enable = $capabilities[$cap];
            }
            $capabilities[$cap] = ($enable ? CAP_ALLOW : CAP_PROHIBIT);
        }
        // Prepare capabilities.
        assign_capability('mod/data:editprivatefields', $capabilities['editprivate'], $roleid, $context, true);
        assign_capability('mod/data:editownprivatefields', $capabilities['editownprivate'], $roleid, $context, true);
        assign_capability('mod/data:viewprivatefields', $capabilities['viewprivate'], $roleid, $context, true);
        assign_capability('mod/data:viewownprivatefields', $capabilities['viewownprivate'], $roleid, $context, true);

        // Generate fields.
        $generator = $this->getDataGenerator()->get_plugin_generator(manager::PLUGINNAME);
        $fieldrecord = (object)['name' => 'Title', 'type' => 'text', 'private' => 0];
        $field = $generator->create_field($fieldrecord, $activity);
        $privatefieldrecord = (object)['name' => 'Summary', 'type' => 'text', 'private' => 1];
        $privatefield = $generator->create_field($privatefieldrecord, $activity);

        // Generate student entry.
        $this->setUser($user);
        $firstentryid = $generator->create_entry(
            $activity,
            [
                $field->field->id => 'Student title',
                $privatefield->field->id => 'Student summary',
            ],
        );
        // Generate teacher entry.
        $this->setUser($user2);
        $secondentryid = $generator->create_entry(
            $activity,
            [
                $field->field->id => 'Teacher title',
                $privatefield->field->id => 'Teacher summary',
            ],
        );

        // Switch to proper account.
        $this->setUser($author);

        // Select proper entry.
        $entryid = $firstentryid;
        if ($otherentry) {
            $entryid = $secondentryid;
        }

        $entry = (object)[
            'id' => $entryid,
            'timecreated' => 1685347529,
            'timemodified' => 1685368626,
            'userid' => $author->id,
            'groupid' => 0,
            'dataid' => $activity->id,
            'picture' => 0,
            'firstname' => $author->firstname,
            'lastname' => $author->lastname,
            'firstnamephonetic' => $author->firstnamephonetic,
            'lastnamephonetic' => $author->lastnamephonetic,
            'middlename' => $author->middlename,
            'alternatename' => $author->alternatename,
            'imagealt' => 'PIXEXAMPLE',
            'email' => $author->email,
        ];
        $entries = [$entry];

        return array(
            'author' => $author,
            'course' => $course,
            'roleid' => $roleid,
            'activity' => $activity,
            'context' => $context,
            'cmid' => $cm->id,
            'field' => $field,
            'privatefield' => $privatefield,
            'entry' => $entry,
            'entries' => $entries,
        );
    }
}

/**
 * Template with private fields tests class for mod_data.
 *
 * @package    mod_data
 * @category   test
 * @copyright  2023 Wiktor Wandachowicz <wiktor.wandachowicz@p.lodz.pl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.11
 */
class private_fields_template_test extends \advanced_testcase {

    // Test data will be provided via helper trait.
    use private_fields_helper_trait;

    /**
     * Setup to ensure that fixtures are loaded.
     */
    public static function setupBeforeClass(): void {
        //global $CFG;
    }

    /**
     * Confirms that private field is visible (or not) according to capabilities,
     * contents of locked private field should be replaced by warning.
     *
     * @covers ::parse_entries
     * @covers ::data_user_privatefield_options
     * @covers ::data_user_canview_field
     * @dataProvider parse_entries_with_private_fields_provider
     */
    public function test_parse_entries_with_private_fields(
        string $templatecontent,
        string $expected,
        bool $otherauthor = false,
        bool $otherentry = false,
        array $capabilities = [],
        array $options = []
    ) {
        $this->resetAfterTest();
        $this->setAdminUser();

        $testdata = self::create_private_fields_test_data($otherauthor, $otherentry, $capabilities);

        $author = $testdata['author'];
        $course = $testdata['course'];
        $roleid = $testdata['roleid'];
        $activity = $testdata['activity'];
        $context = $testdata['context'];
        $cmid = $testdata['cmid'];
        $entry = $testdata['entry'];
        $entries = $testdata['entries'];
        $field = $testdata['field'];
        $privatefield = $testdata['privatefield'];

        $manager = manager::create_from_instance($activity);

        // Some cooked variables for the regular expression.
        $replace = [
            '{authorfullname}' => fullname($author),
            '{timeadded}' => userdate($entry->timecreated, get_string('strftimedatemonthabbr', 'langconfig')),
            '{timemodified}' => userdate($entry->timemodified, get_string('strftimedatemonthabbr', 'langconfig')),
            '{fieldid}' => $field->field->id,
            '{fieldname}' => $field->field->name,
            '{fielddescription}' => $field->field->description,
            '{entryid}' => $entry->id,
            '{cmid}' => $cmid,
            '{courseid}' => $course->id,
            '{authorid}' => $author->id,
            '{usertitle}' => 'Student title',
            '{usersummary}' => 'Student summary',
            '{teachertitle}' => 'Teacher title',
            '{teachersummary}' => 'Teacher summary',
            '{cannotview}' => '.*' . get_string('cannotviewprivatefield', 'data') . '.*',
        ];

        $parser = new template($manager, $templatecontent, $options);
        $result = $parser->parse_entries($entries);

        // We don't want line breaks for the validations.
        $result = str_replace("\n", '', $result);
        $regexp = str_replace(array_keys($replace), array_values($replace), $expected);
        $this->assertMatchesRegularExpression($regexp, $result);
    }

    /**
     * Data provider for test_parse_entries_with_private_fields().
     *
     * @return array of scenarios
     */
    public function parse_entries_with_private_fields_provider(): array {
        return [
            // Teacher scenarios.
            'Teacher id tag' => [
                'templatecontent' => 'Some ##id## tag',
                'expected' => '|Some {entryid} tag|',
                'otherauthor' => true,
            ],
            'Teacher may view all private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {usertitle} Summary: {usersummary}|',
                'otherauthor' => true,
            ],
            'Teacher may view others private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {teachertitle} Summary: {teachersummary}|',
                'otherauthor' => true,
                'otherentry' => true,
            ],
            'Teacher may view only own private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {teachertitle} Summary: {teachersummary}|',
                'otherauthor' => true,
                'otherentry' => true,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => true],
            ],
            'Teacher cannot view others private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {usertitle} Summary: {cannotview}|',
                'otherauthor' => true,
                'otherentry' => false,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => true],
            ],
            'Teacher cannot view own private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {teachertitle} Summary: {cannotview}|',
                'otherauthor' => true,
                'otherentry' => true,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => false],
            ],
            'Teacher cannot view any private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {usertitle} Summary: {cannotview}|',
                'otherauthor' => true,
                'otherentry' => false,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => false],
            ],

            // Student scenarios.
            'Student id tag' => [
                'templatecontent' => 'Some ##id## tag',
                'expected' => '|Some {entryid} tag|',
            ],
            'Student may view only own private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {usertitle} Summary: {usersummary}|',
            ],
            'Student cannot view all private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {teachertitle} Summary: {cannotview}|',
                'otherauthor' => false,
                'otherentry' => true,
            ],
            'Student may view others private fields with extra capabilities' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {teachertitle} Summary: {teachersummary}|',
                'otherauthor' => false,
                'otherentry' => true,
                'capabilities' => ['viewprivate' => true],
            ],
            'Student cannot view others private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {teachertitle} Summary: {cannotview}|',
                'otherauthor' => false,
                'otherentry' => true,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => false],
            ],
            'Student cannot view own private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {usertitle} Summary: {cannotview}|',
                'otherauthor' => false,
                'otherentry' => false,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => false],
            ],
        ];
    }

    /**
     * Confirms that private field is editable (or not) according to capabilities,
     * form control for private field may be replaced by:
     * - regular control: display_add_field()
     * - view-only version: display_browse_field()
     * - warning text (without editing capabilities)
     *
     * @covers ::parse_add_entry
     * @covers ::data_user_privatefield_options
     * @covers ::data_user_canedit_field
     * @covers ::data_user_canview_field
     * @dataProvider parse_add_entry_with_private_fields_provider
     */
    public function test_parse_add_entry_with_private_fields(
        string $templatecontent,
        string $expected,
        bool $otherauthor = false,
        bool $otherentry = false,
        array $capabilities = [],
        array $options = []
    ) {
        $this->resetAfterTest();
        $this->setAdminUser();

        $testdata = self::create_private_fields_test_data($otherauthor, $otherentry, $capabilities);

        $author = $testdata['author'];
        $course = $testdata['course'];
        $roleid = $testdata['roleid'];
        $activity = $testdata['activity'];
        $context = $testdata['context'];
        $cmid = $testdata['cmid'];
        $entry = $testdata['entry'];
        $entries = $testdata['entries'];
        $field = $testdata['field'];
        $privatefield = $testdata['privatefield'];

        // echo "\n - Has editprivate    : " . (int)has_capability('mod/data:editprivatefields', $context);
        // echo "\n - Has editownprivate : " . (int)has_capability('mod/data:editownprivatefields', $context);
        // echo "\n - Has viewprivate    : " . (int)has_capability('mod/data:viewprivatefields', $context);
        // echo "\n - Has viewownprivate : " . (int)has_capability('mod/data:viewownprivatefields', $context);
        // echo "\n";

        // Some cooked variables for the regular expression.
        $replace = [
            '{authorfullname}' => fullname($author),
            '{timeadded}' => userdate($entry->timecreated, get_string('strftimedatemonthabbr', 'langconfig')),
            '{timemodified}' => userdate($entry->timemodified, get_string('strftimedatemonthabbr', 'langconfig')),
            '{fieldid}' => $field->field->id,
            '{fieldname}' => $field->field->name,
            '{fielddescription}' => $field->field->description,
            '{entryid}' => $entry->id,
            '{cmid}' => $cmid,
            '{courseid}' => $course->id,
            '{authorid}' => $author->id,
            '{usertitle}' => 'Student title',
            '{usersummary}' => 'Student summary',
            '{teachertitle}' => 'Teacher title',
            '{teachersummary}' => 'Teacher summary',
            '{editusertitle}' => '.*' . 'id="field_' . $field->field->id . '" value="Student title"' . '.*',
            '{editusersummary}' => '.*' . 'id="field_' . $privatefield->field->id . '" value="Student summary"' . '.*',
            '{editteachertitle}' => '.*' . 'id="field_' . $field->field->id . '" value="Teacher title"' . '.*',
            '{editteachersummary}' => '.*' . 'id="field_' . $privatefield->field->id . '" value="Teacher summary"' . '.*',
            '{cannotview}' => '.*' . get_string('cannotviewprivatefield', 'data') . '.*',
            '{cannotedit}' => '.*' . get_string('cannoteditprivatefield', 'data') . '.*',
            // Special case - core_renderer_cli::notification() wraps notifications as '!! NOTIFICATION !!'.
            '{notificationcannotedit}' => '!! .*' . get_string('cannoteditprivatefield', 'data') . '.* !!',
        ];

        $params = array('data' => [
            [
                'fieldid' => $field->field->id,
                'value' => 'New title',
                'subfield' => '',
            ],
            [
                'fieldid' => $privatefield->field->id,
                'value' => 'New summary',
                'subfield' => '',
            ],
        ]);

        // Prepare the data as is expected by the API.
        $datarecord = new stdClass;
        foreach ($params['data'] as $data) {
            $subfield = ($data['subfield'] !== '') ? '_' . $data['subfield'] : '';
            // We ask for JSON encoded values because of multiple choice forms or checkboxes that use array parameters.
            $datarecord->{'field_' . $data['fieldid'] . $subfield} = $data['value'];
        }

        $manager = manager::create_from_instance($activity);
        $data = $manager->get_instance();
        $fields = $manager->get_field_records();

        $processeddata = data_process_submission($data, $fields, $datarecord, $context);

        $parser = new template($manager, $templatecontent, $options);
        $result = $parser->parse_add_entry($processeddata, $entry->id);

        // We don't want line breaks for the validations.
        $result = str_replace("\n", '', $result);
        $regexp = str_replace(array_keys($replace), array_values($replace), $expected);
        $this->assertMatchesRegularExpression($regexp, $result);
    }

    /**
     * Data provider for test_parse_add_entry_with_private_fields().
     *
     * @return array of scenarios
     */
    public function parse_add_entry_with_private_fields_provider(): array {
        return [
            // Teacher scenarios.
            'Teacher may edit own private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {editteachertitle} Summary: {editteachersummary}|',
                'otherauthor' => true,
                'otherentry' => true,
            ],
            'Teacher may edit others private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {editusertitle} Summary: {editusersummary}|',
            ],
            'Teacher may edit only own private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {editteachertitle} Summary: {editteachersummary}|',
                'otherauthor' => true,
                'otherentry' => true,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => true, 'editprivate' => false, 'editownprivate' => true],
            ],
            'Teacher cannot edit but can see own private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {editteachertitle} Summary: {notificationcannotedit}{teachersummary}|',
                'otherauthor' => true,
                'otherentry' => true,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => true, 'editprivate' => false, 'editownprivate' => false],
            ],
            'Teacher cannot edit but can see others private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {editusertitle} Summary: {notificationcannotedit}{cannotedit}|',
                'otherauthor' => true,
                'otherentry' => false,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => true, 'editprivate' => false, 'editownprivate' => false],
            ],
            'Teacher cannot edit own private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {editteachertitle} Summary: {notificationcannotedit}{cannotedit}|',
                'otherauthor' => true,
                'otherentry' => true,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => false, 'editprivate' => false, 'editownprivate' => false],
            ],
            'Teacher cannot edit all private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {editusertitle} Summary: {notificationcannotedit}{cannotedit}|',
                'otherauthor' => true,
                'otherentry' => false,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => false, 'editprivate' => false, 'editownprivate' => false],
            ],

            // Student scenarios.
            'Student may edit own private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {editusertitle} Summary: {editusersummary}|',
            ],
            'Student cannot edit but can see own private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {editusertitle} Summary: {notificationcannotedit}{usersummary}|',
                'otherauthor' => false,
                'otherentry' => false,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => true, 'editprivate' => false, 'editownprivate' => false],
            ],
            'Student cannot edit any private fields' => [
                'templatecontent' => 'Title: [[Title]] Summary: [[Summary]]',
                'expected' => '|Title: {editusertitle} Summary: {notificationcannotedit}{cannotedit}|',
                'otherauthor' => false,
                'otherentry' => false,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => false, 'editprivate' => false, 'editownprivate' => false],
            ],
        ];
    }
}
