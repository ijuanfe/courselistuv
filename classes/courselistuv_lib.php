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

defined('MOODLE_INTERNAL') || die;

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
        //Patron para reconocer y no modificar numeros romanos
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
     * @param array_courses $array_courses Courses array
     * @return array
     */
    public static function order_courses_by_shortname(&$array_courses) {

        global $CFG, $DB;

        $grouped_courses_array = array();
        $regular_courses_array = array();
        $no_regular_courses_array = array();
        $counter = 0;

        foreach($array_courses as $key=>&$course) {

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

            $category = $DB->get_record('course_categories', array("id"=>$categoryid));
            $inipath = explode('/', $category->path);

            // Validación para cursos regulares
            if($inipath[1] == 6){

                array_push($regular_courses_array, $course);

                $explode_course_shortname = explode("-", $course->shortname);

                // Se verifica que tenga en su nombre corto la especificación de fecha de creación
                // una vez identificada se le añade como atributo al curso
                if(count($explode_course_shortname) && preg_match("/^20/", $explode_course_shortname[3])){
                    $date_course = substr($explode_course_shortname[3], 0, -3);
                    $course->date_course = $date_course;
                }

            }else{
                // Cursos no regulares
                array_push($no_regular_courses_array, $course);
            }
        }

        self::sort_array_by_attr($regular_courses_array, 'timecreated', $order = SORT_DESC);
        self::sort_array_by_attr($no_regular_courses_array, 'timecreated', $order = SORT_DESC);

        $grouped_courses_array['regular_courses'] = $regular_courses_array;
        $grouped_courses_array['no_regular_courses'] = $no_regular_courses_array;

        return $grouped_courses_array;
    }

    /**
     * Dado el identificador retorna la fecha de creación de un curso
     *
     * @param $id  Identificador del curso
     * @return int
     */
    private function get_timecreated_course($id) {

        global $DB;

        $sql_query = "SELECT timecreated
                FROM {course}
                WHERE id = $id";

        $timecreated = $DB->get_record_sql($sql_query)->timecreated;

        return $timecreated;
    }

    /**
     * Dado el identificador retorna la fecha de modificación de un curso
     *
     * @param $id  Identificador del curso
     * @return int
     */
    private function get_timemodified_course($id){

        global $DB;

        $sql_query = "SELECT timemodified
                FROM {course}
                WHERE id = $id";

        $timemodified = $DB->get_record_sql($sql_query)->timemodified;

        return $timemodified;

    }

    /**
     * Dado el identificador retorna la categoria de curso asociada en la base de datos
     *
     * @param $id  Identificador del curso
     * @return int
     */
    private function get_course_category_id($id){

        global $DB;

        $sql_query = "SELECT category
                    FROM {course}
                    WHERE id = $id";

        $categoryid = $DB->get_record_sql($sql_query)->category;

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

    private function sort_array_by_attr(&$array_initial, $col, $order = SORT_ASC){

        $arrAux = array();

        foreach ($array_initial as $key=> $row){
            $arrAux[$key] = is_object($row) ? $arrAux[$key] = $row->$col : $row[$col];
            $arrAux[$key] = strtolower($arrAux[$key]);
        }

        array_multisort($arrAux, $order, $array_initial);
    }

    /**
     * Agrupa los cursos por semestre y por categoría
     *
     * @param array_courses $array_courses Courses array
     * @param string $courses_type
     * @return array
     */

    public static function group_courses_by_semester($array_courses, $courses_type){

        $grouped_courses_array = array();
        $grouped_courses_array['inprogress_regular'] = array();
        $grouped_courses_array['past_regular'] = array();
        $grouped_courses_array['inprogress_no_regular'] = array();
        $grouped_courses_array['past_no_regular'] = array();

        // Semestre calendario actual
        $current_semester = self::get_academic_period();
        $date_ranges_current_semester = self::date_ranges_academic_period($current_semester);

        if($courses_type == 'regular'){

            // Se dividen los cursos en dos arreglos regulares pasados y regulares en progreso
            foreach($array_courses as $course){
                // Criterios para determinar un curso activo:
                // 1. Fecha de inicio del curso se encuentra entre las fechas de inicio y fin del
                //    semestre calendario actual.
                // 2. Fecha de finalización del curso no es menor a la fecha actual
                // 3. Su periodo académico asociado se encuentra activo

                $date = date_create();

                $academicPeriodCourse = explode('-', $course->shortname)[3];

                $academicPeriodActive = self::verify_academic_period($academicPeriodCourse);

                if(($course->startdate > $date_ranges_current_semester->start_date &&
                    $course->startdate < $date_ranges_current_semester->end_date) ||
                    $course->enddate > date_timestamp_get($date) ||
                    $academicPeriodActive->active == 1){
                        array_push($grouped_courses_array['inprogress_regular'], $course);
                } else {
                    array_push($grouped_courses_array['past_regular'], $course);
                }
            }

            $past_courses_by_semester = array();
            $counter_semester = -1;
            $semester_name = "";
            $semester_code = "";

            $past_courses_by_semester = array();

            foreach($grouped_courses_array['past_regular'] as $past_regular_course){

                if(intval(substr($past_regular_course->date_course, 4, 2)) <= 6){
                    $coursesemester = substr($past_regular_course->date_course, 0, 4).'I';
                }else{
                    $coursesemester = substr($past_regular_course->date_course, 0, 4).'II';
                }

                if(key_exists($coursesemester, $past_courses_by_semester)){
                    array_push($past_courses_by_semester[$coursesemester]['courses'], $past_regular_course);
                }else{
                    $past_courses_by_semester[$coursesemester] = array();
                    $past_courses_by_semester[$coursesemester]['semester_code'] = $coursesemester;
                    $past_courses_by_semester[$coursesemester]['semester_name'] = get_string('courselistsemester', 'theme_moove')." ".substr($coursesemester, 0, 4)." ".substr($coursesemester, 4, 2);
                    $past_courses_by_semester[$coursesemester]['courses'] = array();
                    array_push($past_courses_by_semester[$coursesemester]['courses'], $past_regular_course);
                }
            }

            krsort($past_courses_by_semester);

            $pastcoursesnoassociative = array();

            foreach($past_courses_by_semester as $semester){
                array_push($pastcoursesnoassociative, $semester);
            }

            $grouped_courses_array['past_regular'] = array();
            $grouped_courses_array['past_regular'] = $pastcoursesnoassociative;

            return $grouped_courses_array;

        }else{

            $grouped_courses_array['past_no_regular']['semester_name'] = get_string('courselistnonregular', 'theme_moove');
            $grouped_courses_array['past_no_regular']['semester_code'] = "noregulars";
            $grouped_courses_array['past_no_regular']['courses'] = array();
            foreach($array_courses as $course){

                if($course->timecreated >= 1590987600
                || $course->timemodified >= 1590987600){
                    array_push($grouped_courses_array['inprogress_no_regular'], $course);
                }else{
                    array_push($grouped_courses_array['past_no_regular']['courses'], $course);
                }
            }

            return $grouped_courses_array;
        }

    }

    /**
     * Retorna un objeto tipo stdClass con el periodo académico actual
     *
     * @return stdClass
     */

    private function get_academic_period(){

        $current_period = new \stdClass();

        $today = getdate();

        $current_period->year = $today['year'];

        if($today['mon'] > 0 && $today['mon'] <= 6){
            $current_period->period = "1";
        }else{
            $current_period->period = "2";
        }

        return $current_period;
    }

    /**
     * Dado el periodo académico actual, retorna el rango de fechas donde el semestre estaría definido
     *
     * @param current_period $current_period stdClass Periodo actual
     * @return stdClass
     */
    private function date_ranges_academic_period($current_period){

        date_default_timezone_set('America/Bogota');
        $date_ranges = new \stdClass();

        if($current_period->period == '1'){
            $human_start_date = $current_period->year."-01-01 00:00:00";
            $human_end_date = $current_period->year."-07-25 23:59:59";

            $timestamp_start_date = strtotime($human_start_date);
            $timestamp_end_date = strtotime($human_end_date);

            $date_ranges->start_month = '01';
            $date_ranges->end_month = '07';
        }else{
            $human_start_date = $current_period->year."-07-26 00:00:00";
            $human_end_date = $current_period->year."-12-31 23:59:59";

            $timestamp_start_date = strtotime($human_start_date);
            $timestamp_end_date = strtotime($human_end_date);

            $date_ranges->start_month = '08';
            $date_ranges->end_month = '12';
        }

        $date_ranges->start_date = $timestamp_start_date;
        $date_ranges->end_date = $timestamp_end_date;
        $date_ranges->year_period = $current_period->year;
        $date_ranges->period = $current_period;

        return $date_ranges;
    }

    /**
     * moove_verify_academic_period
     *
     * @param  mixed $academicPeriod
     * @return obj
     */
    private function verify_academic_period($academicPeriod) {

        global $DB;

        $table = 'iracv_academic_periods';

        $conditions = array();
        $conditions['code'] = $academicPeriod;

        $academicPeriodActive = $DB->get_record($table, $conditions, 'active');

        if(!$academicPeriodActive){
            $academicPeriodActive = new \stdClass();
            $academicPeriodActive->active = 0;
        }

        return $academicPeriodActive;
    }

}
