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
 * Contains the class for private fields support.
 *
 * @package   mod_data
 * @copyright 2023 Wiktor Wandachowicz <wiktor.wandachowicz@p.lodz.pl>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_data\local;

use context_module;
use stdClass;

/**
 * Private fields support.
 *
 * @package   mod_data
 * @copyright 2023 Wiktor Wandachowicz <wiktor.wandachowicz@p.lodz.pl>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class private_fields {
    /**
     * Retrieve the capabilities of current user for viewing and editing private fields in database activity.
     *
     * @param int $dataid database ID
     * @param object $context context object
     * @return array returns array of boolean flags corresponding to management capabilities of private fields
     */
    public static function get_options($dataid, $context = null) {
        if (empty($context)) {
            $cm = get_coursemodule_from_instance('data', $dataid, 0, false, MUST_EXIST);
            $context = context_module::instance($cm->id);
        }
        $options = [
            'viewprivate'    => has_capability('mod/data:viewprivatefields', $context),
            'viewownprivate' => has_capability('mod/data:viewownprivatefields', $context),
            'editprivate'    => has_capability('mod/data:editprivatefields', $context),
            'editownprivate' => has_capability('mod/data:editownprivatefields', $context),
        ];
        return $options;
    }

    /**
     * Checks if user can see contents of private field in database entry,
     * also can check ownership of user own entries.
     *
     * @param stdClass $field field parameters from {data_fields} table
     * @param array $options result of a call to private_fields::get_options()
     * @param int|null $entryid entry ID for checking ownership of entry
     *        (zero if this check should be skipped)
     * @return bool returns true if the user is allowed to view the field, false otherwise
     */
    public static function can_view_field(stdClass $field, array $options, ?int $entryid = null) {
        // Step 1) Check if this is private field at all, if non-private - view always.
        return empty($field->private)
            // Step 2) Check if user can see all private fields, e.g. Teacher / Manager.
            || ($options['viewprivate'] ?? false)
            // Step 3) Check if user can see own private fields, optionally check entry ownership.
            || (($options['viewownprivate'] ?? false) && (!$entryid || data_isowner($entryid)));
    }

    /**
     * Checks if user can edit contents of private field in database entry,
     * taking care of ownership of user own entries.
     *
     * @param stdClass $field field parameters from {data_fields} table
     * @param array $options result of a call to private_fields::get_options()
     * @param int|null $entryid entry ID for checking ownership of entry
     *        (zero if this check should be skipped, e.g. when creating new entry)
     * @return bool returns true if the user is allowd to view the field, false otherwise
     */
    public static function can_edit_field(stdClass $field, array $options, ?int $entryid = null) {
        // Step 1) Check if this is private field at all, if non-private - edit always.
        return empty($field->private)
            // Step 2) Check if user can edit all private fields, e.g. Teacher / Manager.
            || ($options['editprivate'] ?? false)
            // Step 3) Check if user can edit own private fields, or is creating new entry.
            || (($options['editownprivate'] ?? false) && (!$entryid || data_isowner($entryid)));
    }
}