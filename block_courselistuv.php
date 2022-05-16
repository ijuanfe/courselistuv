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
 * Block courselistuv is defined here.
 *
 * @package     block_courselistuv
 * @copyright   2022 Juan Felipe Orozco Escobar <juan.orozco.escobar@correounivalle.edu.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');

class block_courselistuv extends block_list {

    /**
     * Initializes class member variables.
     */
    public function init() {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_courselistuv');
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    public function has_config() {
        return true;
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        $this->content->type = ''; // Se uso para identificar si es category o cursos normales.

        $icon = $OUTPUT->pix_icon('i/course', get_string('course'));

        $adminseesall = true;
        if (isset($CFG->block_courselistuv_adminview)) {
            if ($CFG->block_courselistuv_adminview == 'own') {
                $adminseesall = false;
            }
        }

        if (empty($CFG->disablemycourses) and isloggedin() and !isguestuser() and
          !(has_capability('moodle/course:update', context_system::instance()) and $adminseesall)) { // Just print My Courses.
            if ($courses = enrol_get_all_users_courses($USER->id, true, null)) {

                $coursesgroup = array();

                // Función añadida para el Campus Virtual Univalle.
                $coursesorder = \block_courselistuv\courselistuv_lib::order_courses_by_shortname($courses);
                $coursesgroup['regular_courses'] = \block_courselistuv\courselistuv_lib::group_courses_by_semester(
                                                        $coursesorder['regular_courses'], 'regular');
                $coursesgroup['no_regular_courses'] = \block_courselistuv\courselistuv_lib::group_courses_by_semester(
                                                        $coursesorder['no_regular_courses'], 'noregular');
                $html = "";

                // In Progress Regular and In Progress No Regular.
                $html .= "<div class=\"\">
			    <div class=\"\" id=\"heading\" >
				<h5 class=\"mb-0\">
				<button class=\"btn btn-link\" data-toggle=\"collapse\"
                    style=\"padding: 0.190rem .10rem !important;\"
                    data-target=\"#progreso\" aria-expanded=\"true\" aria-controls=\"progreso\">
				    <b><span class=\"fa fa-caret-right\"></span>" . " " . get_string('courselistcurrentsemester', 'theme_moove') . "</b>
				</button>
				</h5>
			    </div>

			    <div id=\"progreso\" class=\"collapse\" aria-labelledby=\"heading\"  data-parent=\"#accordion_course_list\">
				<div class=\"card-body\" style=\"padding: 0.50rem 1.00rem 0rem 1.00rem !important;\">
				<ul style=\"padding-left: 0rem !important;\">";
                foreach ($coursesgroup['regular_courses']['inprogress_regular'] as $coursesinprogress) {
                    $html .= "<li class=\"no_bullet_point\">
					        <a class=\"fullname_course_myoverview\"
                            style=\"text-transform: none !important;\" href=\"$CFG->wwwroot/course/view.php?id=";
                    $html .= $coursesinprogress->id;
                    $html .= "\">";
                    $html .= $coursesinprogress->shortname . " " .
                        \block_courselistuv\courselistuv_lib::uv_first_capital($coursesinprogress->fullname);
                    $html .= "</a>
				     </li>";
                }
                $html .= "</ul>
		                </div>
	                    </div>
                        </div>";
                // End In Progress Regular and In Progress No Regular.

                // Past Regular.
                foreach ($coursesgroup as $coursegroup) {
                    foreach ($coursegroup['past_regular'] as $coursesdata) {
                        $html .= "<div class=\"\">
	                    <div class=\"\" id=\"heading\">
		                <h5 class=\"mb-0\">
		                <button class=\"btn btn-link\" data-toggle=\"collapse\"
                            style=\"padding: 0.190rem .10rem !important;\" data-target=\"";
                            $html .= "#semester-" . $coursesdata['semester_code'] . "M";
                            $html .= "\" aria-expanded=\"true\" aria-controls=\"";
                            $html .= "semester-".$coursesdata['semester_code'] . "M";
                            $html .= "\">
                            <b><span class=\"fa fa-caret-right\"></span> ";
                            $html .= $coursesdata['semester_name'];
                            $html .= "</b>
		                </button>
		                </h5>
	                    </div>";
                        $html .= "<div id=\"";
                        $html .= "semester-".$coursesdata['semester_code'] . "M";
                        $html .= "\" class=\"collapse\" aria-labelledby=\"heading\" data-parent=\"#accordion_course_list\">
		                <div class=\"card-body\" style=\"padding: 0.50rem 1.00rem 0rem 1.00rem !important;\">
			            <ul style=\"padding-left: 0rem !important;\">";
                        foreach ($coursesdata['courses'] as $data) {

                            $html .= "<li class=\"no_bullet_point\">
					        <a class=\"fullname_course_myoverview\"
                                style=\"text-transform: none !important;\" href=\"$CFG->wwwroot/course/view.php?id=";
                                $html .= $data->id;
                                $html .= "\">";
                                $html .= $data->shortname . " " .
                                    \block_courselistuv\courselistuv_lib::uv_first_capital($data->fullname);
                            $html .= "</a>
				            </li>";

                        }
                        $html .= "</ul>
		                </div>
	                    </div>
                        </div>";
                    }
                }
                // End Past Regular.
                // Past No Regular.
                foreach ($coursesgroup['no_regular_courses'] as $key => $coursesdata) {
                    if ($key == 'past_no_regular') {
                        $html .= "<div class=\"\">
                        <div class=\"\" id=\"heading\">
                        <h5 class=\"mb-0\">
                        <button class=\"btn btn-link\" data-toggle=\"collapse\"
                            style=\"padding: 0.190rem .10rem !important;\" data-target=\"";
                            $html .= "#" . $coursesdata['semester_code'] . "M";
                            $html .= "\" aria-expanded=\"true\" aria-controls=\"";
                            $html .= $coursesdata['semester_code'] . "M";
                            $html .= "\">
                            <b><span class=\"fa fa-caret-right\"></span> ";
                            $html .= $coursesdata['semester_name'];
                            $html .= "</b>
                        </button>
                        </h5>
                        </div>";
                        $html .= "<div id=\"";
                        $html .= $coursesdata['semester_code'] . "M";
                        $html .= "\" class=\"collapse\" aria-labelledby=\"heading\" data-parent=\"#accordion_course_list\">
                        <div class=\"card-body\" style=\"padding: 0.50rem 1.00rem 0rem 1.00rem !important;\">
                        <ul style=\"padding-left: 0rem !important;\">";
                        foreach ($coursesgroup['no_regular_courses']["inprogress_no_regular"] as $coursesinprogress) {
                            $html .= "<li class=\"no_bullet_point\">
                                    <a class=\"fullname_course_myoverview\"
                                    style=\"text-transform: none !important;\" href=\"$CFG->wwwroot/course/view.php?id=";
                            $html .= $coursesinprogress->id;
                            $html .= "\">";
                            $html .= \block_courselistuv\courselistuv_lib::uv_first_capital($coursesinprogress->fullname);
                            $html .= "</a>
                             </li>";
                        }
                        foreach ($coursesdata['courses'] as $data) {
                            $html .= "<li class=\"no_bullet_point\">
                            <a class=\"fullname_course_myoverview\"
                            style=\"text-transform: none !important;\" href=\"$CFG->wwwroot/course/view.php?id=";
                            $html .= $data->id;
                            $html .= "\">";
                            $html .= \block_courselistuv\courselistuv_lib::uv_first_capital($data->fullname);
                            $html .= "</a>
                            </li>";
                        }
                        $html .= "</ul>
                        </div>
                        </div>
                        </div>";
                    }
                }
                // End Past No Regular.

                $this->content->items[] = $html;
                $this->title = get_string('mycourses');
            }

            $this->get_remote_courses();
            if ($this->content->items) { // Make sure we don't return an empty list.
                return $this->content;
            }
        }

        $categories = core_course_category::get(0)->get_children(); // Parent = 0 ie top-level categories only.
        if ($categories) { // Check we have categories.
            // Just print top level category links.
            if (count($categories) > 1 || (count($categories) == 1 && $DB->count_records('course') > 200)) {
                foreach ($categories as $category) {
                    $categoryname = $category->get_formatted_name();
                    $linkcss = $category->visible ? "" : " class=\"dimmed\" ";
                    $this->content->items[] = "<a $linkcss href=\"$CFG->wwwroot/course/index.php?categoryid=$category->id\">" .
                                                $icon . $categoryname . "</a>";
                }
                // If we can update any course of the view all isn't hidden, show the view all courses link.
                if (has_capability('moodle/course:update', context_system::instance()) ||
                    empty($CFG->block_courselistuv_hideallcourseslink)) {
                    $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">" .
                                                get_string('fulllistofcourses') . '</a> ...';
                }
                $this->title = get_string('categories');
                $this->title .= '!!!!';
            } else { // Just print course names of single category.
                $category = array_shift($categories);
                $courses = get_courses($category->id);
                if ($courses) {
                    foreach ($courses as $course) {
                        $coursecontext = context_course::instance($course->id);
                        $linkcss = $course->visible ? "" : " class=\"dimmed\" ";

                        $this->content->items[] = "<a $linkcss title=\"" .
                                                    format_string($course->shortname, true, array('context' => $coursecontext)) .
                                                    "\" " . "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">" . $icon .
                                                    format_string(get_course_display_name_for_list($course), true,
                                                    array('context' => context_course::instance($course->id))) . "</a>";
                    }
                    // If we can update any course of the view all isn't hidden, show the view all courses link.
                    if (has_capability('moodle/course:update', context_system::instance()) ||
                        empty($CFG->block_courselistuv_hideallcourseslink)) {
                        $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">" .
                        get_string('fulllistofcourses') . '</a> ...';
                    }
                    $this->get_remote_courses();
                } else {

                    $this->content->icons[] = '';
                    $this->content->items[] = get_string('nocoursesyet');
                    if (has_capability('moodle/course:create', context_coursecat::instance($category->id))) {
                        $this->content->footer = '<a href="' . $CFG->wwwroot . '/course/edit.php?category=' . $category->id . '">' .
                        get_string("addnewcourse") . '</a> ...';
                    }
                    $this->get_remote_courses();
                }
                $this->title = get_string('courses');
            }
        }
        // Just for identify.
        $this->content->type = 'category';

        return $this->content;
    }

    public function get_remote_courses() {
        global $CFG, $USER, $OUTPUT;

        if (!is_enabled_auth('mnet')) {
            // No need to query anything remote related.
            return;
        }

        $icon = $OUTPUT->pix_icon('i/mnethost', get_string('host', 'mnet'));

        // Shortcut - the rest is only for logged in users!
        if (!isloggedin() || isguestuser()) {
            return false;
        }

        if ($courses = get_my_remotecourses()) {
            $this->content->items[] = get_string('remotecourses', 'mnet');
            $this->content->icons[] = '';
            foreach ($courses as $course) {
                $this->content->items[] = "<a title=\"" . format_string($course->shortname, true) . "\" ".
                    "href=\"{$CFG->wwwroot}/auth/mnet/jump.php?hostid={$course->hostid}
                    &amp;wantsurl=/course/view.php?id={$course->remoteid}\">"
                    . $icon . format_string(get_course_display_name_for_list($course)) . "</a>";
            }
            // If we listed courses, we are done.
            return true;
        }

        if ($hosts = get_my_remotehosts()) {
            $this->content->items[] = get_string('remotehosts', 'mnet');
            $this->content->icons[] = '';
            foreach ($USER->mnet_foreign_host_array as $somehost) {
                $this->content->items[] = $somehost['count'] . get_string('courseson', 'mnet') . '<a title="' .
                                            $somehost['name'] . '" href="' .
                                            $somehost['url'] . '">' . $icon .
                                            $somehost['name'] . '</a>';
            }
            // If we listed hosts, done.
            return true;
        }

        return false;
    }

    /**
     * Returns the role that best describes the course list block.
     *
     * @return string
     */
    public function get_aria_role() {
        return 'navigation';
    }

    /**
     * Return the plugin config settings for external functions.
     *
     * @return stdClass the configs for both the block instance and plugin
     * @since Moodle 3.8
     */
    public function get_config_for_external() {
        global $CFG;

        // Return all settings for all users since it is safe (no private keys, etc..).
        $configs = (object) [
            'adminview' => $CFG->block_courselistuv_adminview,
            'hideallcourseslink' => $CFG->block_courselistuv_hideallcourseslink
        ];

        return (object) [
            'instance' => new stdClass(),
            'plugin' => $configs,
        ];
    }

    /**
     * Render the contents of a block_list.
     *
     * @param array $icons the icon for each item.
     * @param array $items the content of each item.
     * @param array $type category or html.
     * @return string HTML
     */
    public function list_block_contents($icons, $items, $type) {
        $row = 0;
        $lis = array();
        foreach ($items as $key => $string) {
            $item = html_writer::start_tag('li', array('class' => 'r' . $row));
            if (!empty($icons[$key])) { // Test if the content has an assigned icon.
                $item .= html_writer::tag('div', $icons[$key], array('class' => 'icon column c0'));
            }
            $item .= html_writer::tag('div', $string, array('class' => 'column c1'));
            $item .= html_writer::end_tag('li');
            $lis[] = $item;
            $row = 1 - $row; // Flip even/odd.
        }
        if ($type == 'category') {
            $data = html_writer::tag('ul', implode("\n", $lis), array('class' => 'unlist'));
        } else {
            $data = html_writer::tag('div', $items[0],
                                        array('class' => 'tab-pane fade active show', 'id' => 'accordion_course_list'));
        }

        return $data;
    }

    protected function formatted_contents($output) {
        $this->get_content();
        $this->get_required_javascript();
        if (!empty($this->content->items)) {
            return $this->list_block_contents($this->content->icons, $this->content->items, $this->content->type);
        } else {
            return '';
        }
    }

}
