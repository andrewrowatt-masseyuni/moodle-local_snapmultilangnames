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
 * Upgrade script for local_snapmultilangnames.
 *
 * @package    local_snapmultilangnames
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for local_snapmultilangnames.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_snapmultilangnames_upgrade(int $oldversion): bool {
    if ($oldversion < 2026022701) {
        local_snapmultilangnames_create_customfield();
        upgrade_plugin_savepoint(true, 2026022701, 'local', 'snapmultilangnames');
    }

    return true;
}
