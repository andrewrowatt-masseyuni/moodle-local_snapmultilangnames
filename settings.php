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
 * Admin settings for local_snapmultilangnames.
 *
 * @package    local_snapmultilangnames
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_snapmultilangnames',
        get_string('pluginname', 'local_snapmultilangnames')
    );

    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_snapmultilangnames/enablemultilangnames',
        get_string('enablemultilangnames', 'local_snapmultilangnames'),
        get_string('enablemultilangnames_desc', 'local_snapmultilangnames'),
        0 // Default: off.
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_snapmultilangnames/languages',
        get_string('languages', 'local_snapmultilangnames'),
        get_string('languages_desc', 'local_snapmultilangnames'),
        "en|English (en)\nmi|M\u{0101}ori (mi)\nfr|French (fr)\nzh|Chinese (zh)\nja|Japanese (ja)"
    ));
}
