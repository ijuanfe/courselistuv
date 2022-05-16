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
 * This file contains functions used by the Course List UV block.
 *
 * @package    block_courselistuv
 * @copyright  2022 Juan Felipe Orozco Escobar <juan.orozco.escobar@correounivalle.edu.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_courselistuv;

/**
 * Undocumented class
 */
class courselistuv_lib {

    /**
     * uv_first_capital
     * Recibe un string
     * @param $string
     * @author Sebas119
     * @return string|string[]|null
     */
    public static function uv_first_capital($string) {
        // Patron para reconocer y no modificar numeros romanos.
        $pattern = '/\b(?![LXIVCDM]+\b)([A-Z_-ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝ]+)\b/';
        $output = preg_replace_callback($pattern, function($matches) {
            return mb_strtolower($matches[0], 'UTF-8');
        }, $string);
        $output = ucfirst($output);
        return $output;
    }

    /**
     * Ordena un arreglo de cursos a partir de la subcadena de fecha en su nombre corto
     *
     * @param courses $courses Courses array
     * @return array
     */
    public static function order_courses_by_shortname(&$courses) {

        global $CFG, $DB;

        $groupedcourses = array();
        $regularcourses = array();
        $noregularcourses = array();
        $counter = 0;

        foreach ($courses as $key => &$course) {

            $courseobj = new \core_course_list_element($course);

            $idcourse = $course->id;
            $timecreated = self::get_timecreated_course($idcourse);
            $timemodified = self::get_timemodified_course($idcourse);
            $categoryid = self::get_course_category_id($idcourse);

            $course->timecreated = $timecreated;
            $course->timemodified = $timemodified;
            $course->categoryid = $categoryid;

            $course->link = $CFG->wwwroot."/course/view.php?id=".$course->id;

            $course->courseimage = self::get_course_summary_image($courseobj, $course->link);

            $category = $DB->get_record('course_categories', array("id" => $categoryid));
            $inipath = explode('/', $category->path);

            // Validación para cursos regulares.
            if ($inipath[1] == 6) {

                array_push($regularcourses, $course);

                $explodecourseshortname = explode("-", $course->shortname);

                // Se verifica que tenga en su nombre corto la especificación de fecha de creación
                // una vez identificada se le añade como atributo al curso.
                if (count($explodecourseshortname) && preg_match("/^20/", $explodecourseshortname[3])) {
                    $datecourse = substr($explodecourseshortname[3], 0, -3);
                    $course->date_course = $datecourse;
                }

            } else {
                // Cursos no regulares.
                array_push($noregularcourses, $course);
            }
        }

        self::sort_array_by_attr($regularcourses, 'timecreated', $order = SORT_DESC);
        self::sort_array_by_attr($noregularcourses, 'timecreated', $order = SORT_DESC);

        $groupedcourses['regular_courses'] = $regularcourses;
        $groupedcourses['no_regular_courses'] = $noregularcourses;

        return $groupedcourses;
    }

    /**
     * Dado el identificador retorna la fecha de creación de un curso
     *
     * @param $id  Identificador del curso
     * @return int
     */
    private function get_timecreated_course($id) {

        global $DB;

        $sqlquery = "SELECT timecreated
                FROM {course}
                WHERE id = $id";

        $timecreated = $DB->get_record_sql($sqlquery)->timecreated;

        return $timecreated;
    }

    /**
     * Dado el identificador retorna la fecha de modificación de un curso
     *
     * @param $id  Identificador del curso
     * @return int
     */
    private function get_timemodified_course($id) {

        global $DB;

        $sqlquery = "SELECT timemodified
                FROM {course}
                WHERE id = $id";

        $timemodified = $DB->get_record_sql($sqlquery)->timemodified;

        return $timemodified;

    }

    /**
     * Dado el identificador retorna la categoria de curso asociada en la base de datos
     *
     * @param $id  Identificador del curso
     * @return int
     */
    private function get_course_category_id($id) {

        global $DB;

        $sqlquery = "SELECT category
                    FROM {course}
                    WHERE id = $id";

        $categoryid = $DB->get_record_sql($sqlquery)->category;

        return $categoryid;
    }

    /**
     * Returns the first course's summary issue
     *
     * @param $course
     * @param $courselink
     *
     * @return string
     */
    private function get_course_summary_image($course, $courselink) {
        global $CFG;

        $contentimage = '';
        foreach ($course->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
            if ($isimage) {
                $contentimage = \html_writer::link($courselink, \html_writer::empty_tag('img', array(
                    'src' => $url,
                    'alt' => $course->fullname,
                    'class' => 'card-img-top w-100')));
                break;
            }
        }

        if (empty($contentimage)) {
            $url = $CFG->wwwroot . '/theme/moove/pix/default_course.jpg';

            $contentimage = \html_writer::link($courselink, \html_writer::empty_tag('img', array(
                'src' => $url,
                'alt' => $course->fullname,
                'class' => 'card-img-top w-100')));
        }

        return $contentimage;
    }

    /**
     * Ordena un arreglo a partir de uno de sus atributos
     *
     * @param array_initial $array_initial
     * @param col $col Atributo
     * @param order $order Tipo de ordenamiento, ascendente por defecto
     * @return array
     */

    private function sort_array_by_attr(&$arrayinitial, $col, $order = SORT_ASC) {

        $arraux = array();

        foreach ($arrayinitial as $key => $row) {
            $arraux[$key] = is_object($row) ? $arraux[$key] = $row->$col : $row[$col];
            $arraux[$key] = strtolower($arraux[$key]);
        }

        array_multisort($arraux, $order, $arrayinitial);
    }

    /**
     * Agrupa los cursos por semestre y por categoría
     *
     * @param courses $courses Courses array
     * @param string $coursestype
     * @return array
     */

    public static function group_courses_by_semester($courses, $coursestype) {

        $groupedcourses = array();
        $groupedcourses['inprogress_regular'] = array();
        $groupedcourses['past_regular'] = array();
        $groupedcourses['inprogress_no_regular'] = array();
        $groupedcourses['past_no_regular'] = array();

        // Semestre calendario actual.
        $currentsemester = self::get_academic_period();
        $daterangescurrentsemester = self::date_ranges_academic_period($currentsemester);

        if ($coursestype == 'regular') {

            // Se dividen los cursos en dos arreglos regulares pasados y regulares en progreso.
            foreach ($courses as $course) {
                // Criterios para determinar un curso activo:
                // 1. Fecha de inicio del curso se encuentra entre las fechas de inicio y fin del semestre calendario actual.
                // 2. Fecha de finalización del curso no es menor a la fecha actual.
                // 3. Su periodo académico asociado se encuentra activo.

                $date = date_create();

                $academicperiodcourse = explode('-', $course->shortname)[3];

                $academicperiodactive = self::verify_academic_period($academicperiodcourse);

                if (($course->startdate > $daterangescurrentsemester->start_date &&
                    $course->startdate < $daterangescurrentsemester->end_date) ||
                    $course->enddate > date_timestamp_get($date) ||
                    $academicperiodactive->active == 1) {
                        array_push($groupedcourses['inprogress_regular'], $course);
                } else {
                    array_push($groupedcourses['past_regular'], $course);
                }
            }

            $pastcoursesbysemester = array();

            foreach ($groupedcourses['past_regular'] as $pastregularcourse) {

                if (intval(substr($pastregularcourse->date_course, 4, 2)) <= 6) {
                    $coursesemester = substr($pastregularcourse->date_course, 0, 4).'I';
                } else {
                    $coursesemester = substr($pastregularcourse->date_course, 0, 4).'II';
                }

                if (key_exists($coursesemester, $pastcoursesbysemester)) {
                    array_push($pastcoursesbysemester[$coursesemester]['courses'], $pastregularcourse);
                } else {
                    $pastcoursesbysemester[$coursesemester] = array();
                    $pastcoursesbysemester[$coursesemester]['semester_code'] = $coursesemester;
                    $pastcoursesbysemester[$coursesemester]['semester_name'] = get_string('courselistsemester', 'theme_moove') .
                                                                                " " . substr($coursesemester, 0, 4) .
                                                                                " " . substr($coursesemester, 4, 2);
                    $pastcoursesbysemester[$coursesemester]['courses'] = array();
                    array_push($pastcoursesbysemester[$coursesemester]['courses'], $pastregularcourse);
                }
            }

            krsort($pastcoursesbysemester);

            $pastcoursesnoassociative = array();

            foreach ($pastcoursesbysemester as $semester) {
                array_push($pastcoursesnoassociative, $semester);
            }

            $groupedcourses['past_regular'] = array();
            $groupedcourses['past_regular'] = $pastcoursesnoassociative;

            return $groupedcourses;

        } else {

            $groupedcourses['past_no_regular']['semester_name'] = get_string('courselistnonregular', 'theme_moove');
            $groupedcourses['past_no_regular']['semester_code'] = "noregulars";
            $groupedcourses['past_no_regular']['courses'] = array();

            foreach ($courses as $course) {

                if ($course->timecreated >= 1590987600 || $course->timemodified >= 1590987600) {
                    array_push($groupedcourses['inprogress_no_regular'], $course);
                } else {
                    array_push($groupedcourses['past_no_regular']['courses'], $course);
                }
            }

            return $groupedcourses;
        }

    }

    /**
     * Retorna un objeto tipo stdClass con el periodo académico actual
     *
     * @return stdClass
     */

    private function get_academic_period() {

        $currentperiod = new \stdClass();

        $today = getdate();

        $currentperiod->year = $today['year'];

        if ($today['mon'] > 0 && $today['mon'] <= 6) {
            $currentperiod->period = "1";
        } else {
            $currentperiod->period = "2";
        }

        return $currentperiod;
    }

    /**
     * Dado el periodo académico actual, retorna el rango de fechas donde el semestre estaría definido
     *
     * @param currentperiod $currentperiod stdClass Periodo actual
     * @return stdClass
     */
    private function date_ranges_academic_period($currentperiod) {

        date_default_timezone_set('America/Bogota');
        $dateranges = new \stdClass();

        if ($currentperiod->period == '1') {
            $humanstartdate = $currentperiod->year."-01-01 00:00:00";
            $humanenddate = $currentperiod->year."-07-25 23:59:59";

            $timestampstartdate = strtotime($humanstartdate);
            $timestampenddate = strtotime($humanenddate);

            $dateranges->start_month = '01';
            $dateranges->end_month = '07';
        } else {
            $humanstartdate = $currentperiod->year."-07-26 00:00:00";
            $humanenddate = $currentperiod->year."-12-31 23:59:59";

            $timestampstartdate = strtotime($humanstartdate);
            $timestampenddate = strtotime($humanenddate);

            $dateranges->start_month = '08';
            $dateranges->end_month = '12';
        }

        $dateranges->start_date = $timestampstartdate;
        $dateranges->end_date = $timestampenddate;
        $dateranges->year_period = $currentperiod->year;
        $dateranges->period = $currentperiod;

        return $dateranges;
    }

    /**
     * moove_verify_academic_period
     *
     * @param  mixed $academicPeriod
     * @return obj
     */
    private function verify_academic_period($academicperiod) {

        global $DB;

        $table = 'iracv_academic_periods';

        $conditions = array();
        $conditions['code'] = $academicperiod;

        $academicperiodactive = $DB->get_record($table, $conditions, 'active');

        if (!$academicperiodactive) {
            $academicperiodactive = new \stdClass();
            $academicperiodactive->active = 0;
        }

        return $academicperiodactive;
    }

}
