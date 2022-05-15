<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package    block_courselistuv
 * @category   admin
 * @copyright  2022 Juan Felipe Orozco Escobar <juan.orozco.escobar@correounivalle.edu.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $options = array('all'=>get_string('allcourses', 'block_courselistuv'), 'own'=>get_string('owncourses', 'block_courselistuv'));

    $settings->add(new admin_setting_configselect('block_courselistuv_adminview', get_string('adminview', 'block_courselistuv'),
                    get_string('configadminview', 'block_courselistuv'), 'all', $options));

    $settings->add(new admin_setting_configcheckbox('block_courselistuv_hideallcourseslink', get_string('hideallcourseslink', 'block_courselistuv'),
                    get_string('confighideallcourseslink', 'block_courselistuv'), 0));
}
