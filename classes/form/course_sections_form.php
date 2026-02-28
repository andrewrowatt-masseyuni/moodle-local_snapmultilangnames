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

namespace local_snapmultilangnames\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for managing per-section language settings.
 *
 * The language controls are rendered via the local_snapmultilangnames/section_card
 * Mustache template as Bootstrap dropdowns backed by hidden inputs. Submission is
 * handled in the page controller via optional_param() rather than get_data(). The
 * form is still used for the sesskey hidden field, cancel button, and submit button.
 *
 * Expected customdata keys:
 *   - courseid  (int)   The course ID.
 *   - sections  (array) Records from course_sections ordered by section number.
 *   - langdata  (array) Existing lang-settings records keyed by sectionid.
 *
 * @package    local_snapmultilangnames
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_sections_form extends \moodleform {

    /** Allowed BCP47 codes (must match the list in course_sections.php). */
    public const ALLOWED_LANGS = ['en', 'mi', 'fr', 'zh', 'ja'];

    /**
     * Returns the select options for a language dropdown.
     *
     * @return array
     */
    public static function lang_options(): array {
        return [
            'en' => get_string('lang_en', 'local_snapmultilangnames'),
            'mi' => get_string('lang_mi', 'local_snapmultilangnames'),
            'fr' => get_string('lang_fr', 'local_snapmultilangnames'),
            'zh' => get_string('lang_zh', 'local_snapmultilangnames'),
            'ja' => get_string('lang_ja', 'local_snapmultilangnames'),
        ];
    }

    /**
     * Build the form definition.
     */
    public function definition(): void {
        global $PAGE;

        $mform    = $this->_form;
        $courseid = (int) $this->_customdata['courseid'];
        $sections = $this->_customdata['sections'];
        $langdata = $this->_customdata['langdata'];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        foreach ($sections as $section) {
            $mform->addElement('html', $this->render_section_card($section, $langdata));
        }

        $PAGE->requires->js_call_amd('local_snapmultilangnames/course_sections_form', 'init');

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Render the Bootstrap card for one course section using the Mustache template.
     *
     * Always renders all three component slots. Slots beyond the number of
     * pipe-delimited parts in the section name are shown as placeholders with a
     * dotted outline and a disabled dropdown button.
     *
     * @param \stdClass $section  A record from course_sections.
     * @param array     $langdata Existing lang rows keyed by sectionid.
     * @return string Rendered HTML.
     */
    private function render_section_card(\stdClass $section, array $langdata): string {
        global $OUTPUT;

        $sid        = (int) $section->id;
        $sectionnum = (int) $section->section;
        $rawname    = isset($section->name) ? (string) $section->name : '';

        $parts    = array_map('trim', explode('|', $rawname));
        $numparts = min(\count($parts), 3);

        $existing = $langdata[$sid] ?? null;
        $defaults = [
            'lang1' => $existing->lang1 ?? 'en',
            'lang2' => $existing->lang2 ?? 'en',
            'lang3' => $existing->lang3 ?? 'en',
        ];

        // Card header: "Section N" or "Section N: raw name".
        $headerlabel = get_string('section_number', 'local_snapmultilangnames', $sectionnum);
        if ($rawname !== '') {
            $headerlabel .= ': <span class="fw-normal">' . s($rawname) . '</span>';
        }

        $alloptions = self::lang_options();
        $arialabel  = get_string('lang_label', 'local_snapmultilangnames');
        $langkeys   = ['lang1', 'lang2', 'lang3'];

        $components = [];
        for ($i = 0; $i < 3; $i++) {
            $n         = $i + 1;
            $langkey   = $langkeys[$i];
            $fieldname = $langkey . '_' . $sid;
            $spanid    = 'mlnc-n' . $n . '-' . $sid;
            $isreal    = $i < $numparts;
            $text      = $isreal ? ($parts[$i] ?? '') : '';
            $langval   = $defaults[$langkey];

            // BCP47 code for the lang= attribute on the preview span.
            // '' means no lang attribute (Mustache falsy → attribute skipped).
            $lang = ($isreal && $langval !== '') ? $langval : '';

            // Label shown in the dropdown toggle button.
            $currentlabel = $alloptions[$langval] ?? $alloptions['en'];

            // Build the flat options array — spanid and fieldname are copied into each
            // option so the Mustache template can access them without a parent-context walk.
            $options = [];
            foreach ($alloptions as $value => $label) {
                $options[] = [
                    'value'     => (string) $value,
                    'label'     => $label,
                    'selected'  => ($value === $langval),
                    'spanid'    => $spanid,
                    'fieldname' => $fieldname,
                ];
            }

            $components[] = [
                'n'              => $n,
                'spanid'         => $spanid,
                'fieldname'      => $fieldname,
                'text'           => $text,           // Mustache {{text}} auto-escapes.
                'isreal'         => $isreal,
                'lang'           => $lang,           // '' (falsy) → no lang= attribute.
                'currentvalue'   => $langval,
                'currentlabel'   => $currentlabel,
                'componentlabel' => get_string('component', 'local_snapmultilangnames', $n),
                'arialabel'      => $arialabel,
                'options'        => $options,
            ];
        }

        return $OUTPUT->render_from_template('local_snapmultilangnames/section_card', [
            'headerlabel' => $headerlabel,
            'sid'         => $sid,
            'sectionnum'  => $sectionnum,
            'components'  => $components,
        ]);
    }

}
