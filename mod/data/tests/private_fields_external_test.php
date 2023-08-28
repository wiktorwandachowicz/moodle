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

namespace mod_data\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/data/tests/private_fields_template_test.php');

use externallib_advanced_testcase;
use core_external\external_api;
use mod_data_external;
use mod_data\manager;
use mod_data\private_fields_helper_trait;

/**
 * External function test for private fields.
 *
 * @package    mod_data
 * @category   external
 * @copyright  2023 Wiktor Wandachowicz <wiktor.wandachowicz@p.lodz.pl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.11
 * @coversDefaultClass \mod_data_external
 */
class private_fields_external_test extends externallib_advanced_testcase {

    // Test data will be provided via helper trait.
    use private_fields_helper_trait;

    /**
     * Test the behaviour of get_data_access_information().
     *
     * @covers ::get_data_access_information
     * @dataProvider get_data_access_information_provider
     */
    public function test_get_data_access_information(
        array $expectedaccess,
        bool $otherauthor = false,
        bool $otherentry = false,
        array $capabilities = []
    ) {
        $this->resetAfterTest();
        $this->setAdminUser();

        $testdata = self::create_private_fields_test_data($otherauthor, $otherentry, $capabilities);

        $author = $testdata['author'];
        $activity = $testdata['activity'];

        $this->setUser($author);

        $result = mod_data_external::get_data_access_information($activity->id);
        $result = external_api::clean_returnvalue(mod_data_external::get_data_access_information_returns(), $result);

        // Check access.
        foreach ($expectedaccess as $access => $value) {
            $this->assertEquals($result[$access], $value);
        }
    }

    /**
     * Data provider for test_get_data_access_information().
     *
     * @return array of scenarios
     */
    public function get_data_access_information_provider(): array {
        return [
            'Student default access information' => [
                'expectedaccess' => [
                    'canviewprivate' => false, 'canviewownprivate' => true,
                    'caneditprivate' => false, 'caneditownprivate' => true,
                ],
            ],
            'Teacher default access information' => [
                'expectedaccess' => [
                    'canviewprivate' => true, 'canviewownprivate' => true,
                    'caneditprivate' => true, 'caneditownprivate' => true,
                ],
                'otherauthor' => true,
            ],
        ];
    }

    /**
     * Test the behaviour of get_entry() with private fields.
     *
     * @covers ::get_entry
     * @covers private_fields::get_options
     * @covers record_exporter::get_other_values
     * @covers content_exporter::get_other_values
     * @dataProvider get_entries_with_private_fields_provider
     */
    public function test_data_get_entry_with_private_fields(
        array $expectedaccess,
        bool $otherauthor = false,
        bool $otherentry = false,
        array $capabilities = [],
        array $expectedcontent = [],
    ) {
        $this->resetAfterTest();
        $this->setAdminUser();

        $testdata = self::create_private_fields_test_data($otherauthor, $otherentry, $capabilities);

        $author = $testdata['author'];
        $activity = $testdata['activity'];
        $entry = $testdata['entry'];
        $field = $testdata['field'];
        $privatefield = $testdata['privatefield'];

        $this->setUser($author);

        $result = mod_data_external::get_entry($entry->id, true);
        $result = external_api::clean_returnvalue(mod_data_external::get_entry_returns(), $result);
        $resultentry = $result['entry'];

        $this->assertArrayHasKey('id', $resultentry, 'Expected entry not found');

        // Check access.
        foreach ($expectedaccess as $access => $value) {
            $this->assertEquals($resultentry[$access], $value);
        }

        // Check delaration and content of fields.
        $contents = $resultentry['contents'];
        $this->assertEquals($contents[0]['fieldid'], $field->field->id);
        $this->assertEquals($contents[0]['private'], $field->field->private);
        $this->assertEquals($contents[1]['fieldid'], $privatefield->field->id);
        $this->assertEquals($contents[1]['private'], $privatefield->field->private);
    
        // Some cooked variables for contents checking.
        $replace = [
            '{usertitle}' => 'Student title',
            '{usersummary}' => 'Student summary',
            '{teachertitle}' => 'Teacher title',
            '{teachersummary}' => 'Teacher summary',
            '{cannotview}' => get_string('cannotviewprivatefield', 'data'),
        ];

        $index = 0;
        foreach ($expectedcontent as $expected) {
            if (array_key_exists($expected, $replace)) {
                $expected = $replace[$expected];
            }
            $content = $contents[$index];
            $index++;
            $this->assertEquals($expected, $content['content']);
        }
    }

    /**
     * Test the behaviour of get_entries() with private fields.
     *
     * @covers ::get_entries
     * @covers private_fields::get_options
     * @covers record_exporter::get_other_values
     * @dataProvider get_entries_with_private_fields_provider
     */
    public function test_data_get_entries_with_private_fields(
        array $expectedaccess,
        bool $otherauthor = false,
        bool $otherentry = false,
        array $capabilities = []
    ) {
        $this->resetAfterTest();
        $this->setAdminUser();

        $testdata = self::create_private_fields_test_data($otherauthor, $otherentry, $capabilities);

        $author = $testdata['author'];
        $activity = $testdata['activity'];
        $entry = $testdata['entry'];

        $this->setUser($author);

        $result = mod_data_external::get_entries($activity->id, 0, false);
        $result = external_api::clean_returnvalue(mod_data_external::get_entries_returns(), $result);

        // Find the right entry.
        $resultentry = [];
        foreach ($result['entries'] as $obj) {
            if ($obj['id'] == $entry->id) {
                $resultentry = $obj;
                break;
            }
        }
        $this->assertArrayHasKey('id', $resultentry, 'Expected entry not found');

        // Check access.
        foreach ($expectedaccess as $access => $value) {
            $this->assertEquals($resultentry[$access], $value);
        }
    }

    /**
     * Data provider for test_data_get_entry_with_private_fields().
     *
     * @return array of scenarios
     */
    public function get_entries_with_private_fields_provider(): array {
        return [
            // Student scenarios
            'Student can see own private fields' => [
                'expectedaccess' => [
                    'canviewprivate' => false, 'canviewownprivate' => true,
                    'caneditprivate' => false, 'caneditownprivate' => true,
                ],
            ],
            'Student cannot see contents of own private fields' => [
                'expectedaccess' => [
                    'canviewprivate' => false, 'canviewownprivate' => false,
                    'caneditprivate' => false, 'caneditownprivate' => true,
                ],
                'otherauthor' => false,
                'otherentry' => false,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => false],
                'expectedcontent' => ['{usertitle}', '{cannotview}'],
            ],
            'Student cannot see contents of others private fields' => [
                'expectedaccess' => [
                    'canviewprivate' => false, 'canviewownprivate' => false,
                    'caneditprivate' => false, 'caneditownprivate' => true,
                ],
                'otherauthor' => false,
                'otherentry' => true,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => false],
                'expectedcontent' => ['{teachertitle}', '{cannotview}'],
            ],

            // Teacher scenarios
            'Teacher can see others private fields' => [
                'expectedaccess' => [
                    'canviewprivate' => true, 'canviewownprivate' => true,
                    'caneditprivate' => true, 'caneditownprivate' => true,
                ],
                'otherauthor' => true,
            ],
            'Teacher can see own private fields' => [
                'expectedaccess' => [
                    'canviewprivate' => true, 'canviewownprivate' => true,
                    'caneditprivate' => true, 'caneditownprivate' => true,
                ],
                'otherauthor' => true,
                'otherentry' => true,
            ],
            'Teacher may see contents of own private fields' => [
                'expectedaccess' => [
                    'canviewprivate' => true, 'canviewownprivate' => true,
                    'caneditprivate' => true, 'caneditownprivate' => true,
                ],
                'otherauthor' => true,
                'otherentry' => true,
                'capabilities' => [],
                'expectedcontent' => ['{teachertitle}', '{teachersummary}'],
            ],
            'Teacher cannot see contents of own private fields' => [
                'expectedaccess' => [
                    'canviewprivate' => false, 'canviewownprivate' => false,
                    'caneditprivate' => true, 'caneditownprivate' => true,
                ],
                'otherauthor' => true,
                'otherentry' => true,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => false],
                'expectedcontent' => ['{teachertitle}', '{cannotview}'],
            ],
            'Teacher cannot see contents of others private fields' => [
                'expectedaccess' => [
                    'canviewprivate' => false, 'canviewownprivate' => false,
                    'caneditprivate' => true, 'caneditownprivate' => true,
                ],
                'otherauthor' => true,
                'otherentry' => false,
                'capabilities' => ['viewprivate' => false, 'viewownprivate' => false],
                'expectedcontent' => ['{usertitle}', '{cannotview}'],
            ],
        ];
    }

    /**
     * Test the behaviour of add_entry() with private fields.
     *
     * @covers ::add_entry
     * @covers private_fields::get_options
     * @covers private_fields::can_edit_field
     * @dataProvider add_entry_with_private_fields_provider
     */
    public function test_data_add_entry_with_private_fields(
        array $contents,
        array $expected = [],
        array $capabilities = [],
        bool $otherauthor = false,
    ) {
        $this->resetAfterTest();
        $this->setAdminUser();

        $testdata = self::create_private_fields_test_data($otherauthor, false, $capabilities);

        $author = $testdata['author'];
        $activity = $testdata['activity'];
        $field = $testdata['field'];
        $privatefield = $testdata['privatefield'];

        $this->setUser($author);

        // Some cooked variables for the regular expression.
        $replace = [
            '{usertitle}' => 'New student title',
            '{usersummary}' => 'New student summary',
            '{teachertitle}' => 'New teacher title',
            '{teachersummary}' => 'New teacher summary',
            '{cannotedit}' => '.*' . get_string('cannoteditprivatefield', 'data') . '.*',
        ];

        $contents = str_replace(array_keys($replace), array_values($replace), $contents);
        if (!empty($expected['fieldnotifications'])) {
            $expected['fieldnotifications'] = str_replace(array_keys($replace), array_values($replace), $expected['fieldnotifications']);
        }

        // Prepare new entry.
        $data = [
            [
                'fieldid' => $field->field->id,
                'subfield' => '',
                'value' => json_encode($contents['Title']),
            ],
            [
                'fieldid' => $privatefield->field->id,
                'subfield' => '',
                'value' => json_encode($contents['Summary']),
            ],
        ];

        $result = mod_data_external::add_entry($activity->id, 0, $data);
        $result = external_api::clean_returnvalue(mod_data_external::add_entry_returns(), $result);

        $hasnewentry = $expected['newentry'] ?? false;
        $fieldnotifications = $expected['fieldnotifications'] ?? [];

        if ($hasnewentry) {
            $this->assertNotEquals(0, $result['newentryid'], 'Entry should be created, but newentryid = ' . $result['newentryid']);
        } else {
            $this->assertEquals(0, $result['newentryid'], 'Entry should not be created, but newentryid = ' . $result['newentryid']);
        }

        foreach ($fieldnotifications as $fieldname => $notification) {
            $found = false;
            foreach ($result['fieldnotifications'] as $msg) {
                if ($fieldname == $msg['fieldname']) {
                    $this->assertMatchesRegularExpression($notification, $msg['notification'], 'Expected fieldnotifcation');
                    $found = true;
                }
            }
            $this->assertEquals(true, $found, 'Expected notification for field ' . $fieldname);
        }
    }

    /**
     * Data provider for test_data_add_entry_with_private_fields().
     *
     * @return array of scenarios
     */
    public function add_entry_with_private_fields_provider(): array {
        return [
            // Student scenarios
            'Student may add own entry with private fields' => [
                'contents' => [
                    'Title' => '{usertitle}',
                    'Summary' => '{usersummary}',
                ],
                'expected' => ['newentry' => true],
                'capabilities' => [
                    'editprivate' => false, 'editownprivate' => true,
                ],
            ],
            'Student cannot add entry with private fields when missing capabilities' => [
                'contents' => [
                    'Title' => '{usertitle}',
                    'Summary' => '{usersummary}',
                ],
                'expected' => [
                    'newentry' => false,
                    'fieldnotifications' => [
                        'Summary' => '|{cannotedit}|',
                    ],
                ],
                'capabilities' => [
                    'editprivate' => false, 'editownprivate' => false,
                ],
            ],

            // Teacher scenarios
            'Teacher may add own entry with private fields' => [
                'contents' => [
                    'Title' => '{teachertitle}',
                    'Summary' => '{teachersummary}',
                ],
                'expected' => ['newentry' => true],
                'capabilities' => [
                    'editprivate' => false, 'editownprivate' => true,
                ],
                'otherauthor' => true,
            ],
            'Teacher may add entry with private fields' => [
                'contents' => [
                    'Title' => '{teachertitle}',
                    'Summary' => '{teachersummary}',
                ],
                'expected' => ['newentry' => true],
                'capabilities' => [
                    'editprivate' => true, 'editownprivate' => false,
                ],
                'otherauthor' => true,
            ],
            'Teacher cannot add entry with private fields when missing capabilities' => [
                'contents' => [
                    'Title' => '{teachertitle}',
                    'Summary' => '{teachersummary}',
                ],
                'expected' => [
                    'newentry' => false,
                    'fieldnotifications' => [
                        'Summary' => '|{cannotedit}|',
                    ],
                ],
                'capabilities' => [
                    'editprivate' => false, 'editownprivate' => false,
                ],
                'otherauthor' => true,
            ],
        ];
    }

    /**
     * Test the behaviour of update_entry() with private fields.
     *
     * @covers ::update_entry
     * @covers private_fields::get_options
     * @covers private_fields::can_edit_field
     * @dataProvider update_entry_with_private_fields_provider
     */
    public function test_data_update_entry_with_private_fields(
        array $contents,
        array $expected = [],
        array $capabilities = [],
        bool $otherauthor = false,
        bool $otherentry = false,
    ) {
        $this->resetAfterTest();
        $this->setAdminUser();

        $testdata = self::create_private_fields_test_data($otherauthor, false, $capabilities);

        $author = $testdata['author'];
        $activity = $testdata['activity'];
        $entry = $testdata['entry'];
        $field = $testdata['field'];
        $privatefield = $testdata['privatefield'];

        $this->setUser($author);

        // Some cooked variables for the regular expression.
        $replace = [
            '{usertitle}' => 'New student title',
            '{usersummary}' => 'New student summary',
            '{teachertitle}' => 'New teacher title',
            '{teachersummary}' => 'New teacher summary',
            '{cannotedit}' => '.*' . get_string('cannoteditprivatefield', 'data') . '.*',
        ];

        $contents = str_replace(array_keys($replace), array_values($replace), $contents);
        if (!empty($expected['fieldnotifications'])) {
            $expected['fieldnotifications'] = str_replace(array_keys($replace), array_values($replace), $expected['fieldnotifications']);
        }

        // Prepare updated entry.
        $data = [
            [
                'fieldid' => $field->field->id,
                'subfield' => '',
                'value' => json_encode($contents['Title']),
            ],
            [
                'fieldid' => $privatefield->field->id,
                'subfield' => '',
                'value' => json_encode($contents['Summary']),
            ],
        ];

        $result = mod_data_external::update_entry($entry->id, $data);
        $result = external_api::clean_returnvalue(mod_data_external::update_entry_returns(), $result);

        //echo "\n result = "; var_dump($result);
        $updated = $expected['updated'] ?? false;
        $fieldnotifications = $expected['fieldnotifications'] ?? [];

        if ($updated) {
            $this->assertTrue($result['updated'], 'Entry should be updated');
        } else {
            $this->assertFalse($result['updated'], 'Entry should not be updated');
        }

        foreach ($fieldnotifications as $fieldname => $notification) {
            $found = false;
            foreach ($result['fieldnotifications'] as $msg) {
                if ($fieldname == $msg['fieldname']) {
                    $this->assertMatchesRegularExpression($notification, $msg['notification'], 'Expected fieldnotifcation');
                    $found = true;
                }
            }
            $this->assertEquals(true, $found, 'Expected notification for field ' . $fieldname);
        }
    }

    /**
     * Data provider for test_data_add_entry_with_private_fields().
     *
     * @return array of scenarios
     */
    public function update_entry_with_private_fields_provider(): array {
        return [
            // Student scenarios
            'Student may update own private fields' => [
                'contents' => [
                    'Title' => '{usertitle}',
                    'Summary' => '{usersummary}',
                ],
                'expected' => ['updated' => true],
                'capabilities' => [
                    'editprivate' => false, 'editownprivate' => true,
                ],
            ],
            'Student cannot update private fields when missing capabilities' => [
                'contents' => [
                    'Title' => '{usertitle}',
                    'Summary' => '{usersummary}',
                ],
                'expected' => [
                    'updated' => false,
                    'fieldnotifications' => [
                        'Summary' => '|{cannotedit}|',
                    ],
                ],
                'capabilities' => [
                    'editprivate' => false, 'editownprivate' => false,
                ],
            ],

            // Teacher scenarios
            'Teacher may update own private fields' => [
                'contents' => [
                    'Title' => '{teachertitle}',
                    'Summary' => '{teachersummary}',
                ],
                'expected' => ['updated' => true],
                'capabilities' => [
                    'editprivate' => false, 'editownprivate' => true,
                ],
                'otherauthor' => true,
                'otherentry' => true,
            ],
            'Teacher may update private fields' => [
                'contents' => [
                    'Title' => '{teachertitle}',
                    'Summary' => '{teachersummary}',
                ],
                'expected' => ['updated' => true],
                'capabilities' => [
                    'editprivate' => true, 'editownprivate' => false,
                ],
                'otherauthor' => true,
                'otherentry' => true,
            ],
            'Teacher cannot update private fields when missing capabilities' => [
                'contents' => [
                    'Title' => '{teachertitle}',
                    'Summary' => '{teachersummary}',
                ],
                'expected' => [
                    'updated' => false,
                    'fieldnotifications' => [
                        'Summary' => '|{cannotedit}|',
                    ],
                ],
                'capabilities' => [
                    'editprivate' => false, 'editownprivate' => false,
                ],
                'otherauthor' => true,
                'otherentry' => true,
            ],
        ];
    }
}
