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
 * Dropdown behaviour for the course sections language settings form.
 *
 * Keeps the Bootstrap dropdowns and preview spans in sync with the hidden
 * inputs that carry the selected BCP47 code on form submit.
 *
 * @module     local_snapmultilangnames/course_sections_form
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Human-readable labels matching PHP lang_options(). */
const langLabels = {
    "en": "English (en)",
    "mi": "M\u0101ori (mi)",
    "fr": "French (fr)",
    "zh": "Chinese (zh)",
    "ja": "Japanese (ja)",
};

/**
 * Close all open mlnc dropdowns except the given one.
 *
 * @param {Element|null} except The button to leave open, or null to close all.
 */
const closeAllDropdowns = (except) => {
    document.querySelectorAll(".mlnc-toggle[aria-expanded='true']").forEach((btn) => {
        if (btn === except) {
            return;
        }
        btn.setAttribute("aria-expanded", "false");
        btn.closest(".dropdown").querySelector(".dropdown-menu").classList.remove("show");
    });
};

/**
 * Initialise dropdown event delegation on the document.
 */
export const init = () => {

    // Self-managed toggle: open/close on button click.
    document.addEventListener("click", (e) => {
        const toggle = e.target.closest(".mlnc-toggle");
        if (toggle && !toggle.disabled) {
            const menu = toggle.closest(".dropdown").querySelector(".dropdown-menu");
            const opening = !menu.classList.contains("show");
            closeAllDropdowns(opening ? null : toggle);
            if (opening) {
                menu.classList.add("show");
                toggle.setAttribute("aria-expanded", "true");
            } else {
                menu.classList.remove("show");
                toggle.setAttribute("aria-expanded", "false");
            }
            return;
        }

        // Close all dropdowns when clicking outside.
        if (!e.target.closest(".dropdown")) {
            closeAllDropdowns(null);
        }
    });

    document.addEventListener("click", (e) => {
        const item = e.target.closest(".dropdown-item[data-mlnc-field]");
        if (!item) {
            return;
        }

        // Close the dropdown that contains this item.
        const dropdown = item.closest(".dropdown");
        const menu = dropdown.querySelector(".dropdown-menu");
        const toggle = dropdown.querySelector(".mlnc-toggle");
        menu.classList.remove("show");
        if (toggle) {
            toggle.setAttribute("aria-expanded", "false");
        }

        const value = item.dataset.mlncValue;
        const spanId = item.dataset.mlncSpan;
        const fieldName = item.dataset.mlncField;

        // Update the hidden input that carries the value on form submit.
        const input = document.getElementById("input-" + fieldName);
        if (input) {
            input.value = value;
        }

        // Update active state within this dropdown menu.
        item.closest(".dropdown-menu").querySelectorAll(".dropdown-item").forEach((el) => {
            el.classList.remove("active");
        });
        item.classList.add("active");

        // Compute lang code and human-readable label for the toggle button.
        const lang = value;
        const label = langLabels[value] || value.toUpperCase();

        // Update the dropdown toggle button label.
        const btn = item.closest(".dropdown").querySelector(".dropdown-toggle");
        if (btn) {
            btn.textContent = label;
        }

        // Update the preview span's lang attribute.
        const span = spanId ? document.getElementById(spanId) : null;
        if (span) {
            if (lang) {
                span.setAttribute("lang", lang);
            } else {
                span.removeAttribute("lang");
            }
            span.setAttribute("data-language", label);
        }
    });
};
