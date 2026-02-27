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
 * Install script for local_snapmultilangnames.
 *
 * @package    local_snapmultilangnames
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install function - creates the "Enable multilang names" course custom field.
 */
function xmldb_local_snapmultilangnames_install(): bool {
    local_snapmultilangnames_create_customfield();
    return true;
}

/**
 * Creates the "Enable multilang names" course custom field category and field.
 */
function local_snapmultilangnames_create_customfield(): void {
    $category = \core_customfield\category_controller::create(0, (object)[
        'name'      => 'Snap Multilang Names',
        'component' => 'core_course',
        'area'      => 'course',
        'itemid'    => 0,
        'contextid' => context_system::instance()->id,
    ]);
    $category->save();

    $field = \core_customfield\field_controller::create(0, (object)[
        'name'              => 'Enable multilang names',
        'shortname'         => 'enablemultilangnames',
        'type'              => 'checkbox',
        'description'       => '',
        'descriptionformat' => FORMAT_HTML,
        'categoryid'        => $category->get('id'),
        'configdata'        => json_encode([
            'checkbydefault' => 0,
            'locked'         => 0,
            'visibility'     => 2, // VISIBILITY_EVERYONE.
        ]),
    ], $category);
    $field->save();
}
