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

function printCustomNav( $courseid, $role, $view, $course_has_groups ) {

    switch ($role) {
        case 'student':
            echo '<ul class="nav nav-tabs m-b-1">';
            echo '<li class="nav-item">';

            if ( $view == 'default' || $view == 'intra' ) {
                $intermode = '';
                $intramode = 'active';
            } elseif ( $view == 'inter' || $view == 'comparison' ) {
                $intermode = 'active';
                $intramode = '';
            }

            echo '<li class="nav-item"><a class="nav-link ' . $intramode . '" href="index.php?id=' . $courseid . '&view=intra"
                          title="Me vs others">Me vs others</a></li>';

            if ( $course_has_groups != false ) {

                echo '<li class="nav-item"><a class="nav-link ' . $intermode . '" href="index.php?id=' . $courseid . '&view=inter"
                          title="Us vs them">Us vs them</a></li>';

            }

            echo '</ul>';
            break;

        case 'editingteacher':
            echo '<ul class="nav nav-tabs m-b-1">';
            echo '<li class="nav-item"><a class="nav-link active" title="Custom graph" href="index.php?id=' . $courseid . '&view=custom">Custom graph</a></li>';
            echo '</ul>';
            break;

        case 'teacher':

            if ( $view == 'default' || $view == 'progression' ) {
                $comparison = '';
                $progression = 'active';
                $custom = '';
            } elseif ( $view == 'comparison' ) {
                $comparison = 'active';
                $progression = '';
                $custom = '';
            } elseif ( $view = 'custom' ) {
                $comparison = '';
                $progression = '';
                $custom = 'active';
            }

            echo '<ul class="nav nav-tabs m-b-1">';

            echo '<li class="nav-item"><a class="nav-link ' . $progression . '" href="index.php?id=' . $courseid . '&view=progression"
                          title="Progression">Progression</a></li>';

            echo '<li class="nav-item"><a class="nav-link ' . $comparison . '" href="index.php?id=' . $courseid . '&view=comparison"
                          title="Comparison">Comparison</a></li>';

            echo '<li class="nav-item"><a class="nav-link ' . $custom . '" title="Custom graph" href="index.php?id=' . $courseid . '&view=custom">Custom graph</a></li>';
            echo '</ul>';
            break;
    }

}

function printOptions( $courseid, $modality, $groupid = NULL, $activities, $average, $custom_title, $viewtype ) {


    $groupname = groups_get_group_name($groupid);

    // Options
    echo html_writer::tag('h5', 'Options');

    echo '<ul>';

    if ( $groupname ) {
        echo html_writer::tag('li', 'Group name : ' . $groupname . ' (#' . $groupid . ')');
    } else {
    }

    if ( $modality ) {
        echo html_writer::tag('li', 'Modality : ' . $modality);
    } else {
        echo html_writer::tag('li', 'Modality : ignored');
    }

    if ( $average ) {
        echo html_writer::tag('li', 'Average : ' . $average);
    } else {
        echo html_writer::tag('li', 'Average : no');
    }

    if ( $custom_title ) {
        echo html_writer::tag('li', '$custom_title : ' . $custom_title);
    } else {
        echo html_writer::tag('li', '$custom_title : no');
    }

    if ( $viewtype ) {
        echo html_writer::tag('li', '$viewtype : ' . $viewtype);
    } else {
        echo html_writer::tag('li', '$viewtype : error');
    }

    echo '</ul>';

    if ( $activities ) {
        echo html_writer::tag('h6', 'Activities');
        echo '<ul>';
        foreach ( $activities as $activity ) {
            echo html_writer::tag('li', 'Activity : ' . getActivityName( $activity ) . ' (#' . $activity . ')');
        }
        echo '</ul>';
    } else {
        echo html_writer::tag('li', 'Activity : ignored');
    }

    echo '<hr>';

}

function printGraph( $courseid, $modality, $groupid = NULL, $activities = NULL, $average, $custom_title,
                     $custom_weight_array = NULL, $averageonly, $viewtype ) {
    global $OUTPUT, $CFG;

    if ( isset($modality) && $modality == 'intra' ) {

        // Get users from choosen group
        $users = getUsersFromGroup($groupid);           // Get users from this group
        $usernames = getUsernamesFromGroup($groupid);   // Get usernames from this group

        if ( $custom_title ) {
            echo html_writer::tag('h1', $custom_title );
        }

        echo html_writer::tag('h4', groups_get_group_name($groupid) );

        // Get grades for each activity
        $grades_array = array();
        $activities_names = array();
        $average_grades = array();

        // Get grades from user array and item_id
        foreach ( $activities as $activity ) {

            // Push user grades for the activity
            $activity_grades = getGrades($users, $courseid, $activity);
            array_push($grades_array, $activity_grades);

            // Push the name of activity in array
            array_push($activities_names, getActivityName($activity));

        }

        // Generate the chart
        if ( $grades_array && $usernames ) {

            // Create chart and set some settings
            $chart = new \core\chart_bar(); // Create a bar chart instance.
            $CFG->chart_colorset = ['#001f3f', '#D6CFCB', '#CCB7AE', '#A6808C', '#706677', '#565264', '#D9F0FF', '#A3D5FF', '#d6d6d6'];

            // Iterate over the activities final grades
            $i = 0;
            foreach ( $grades_array as $activity_grades) {
                $series = new \core\chart_series($activities_names[$i], $activity_grades);

                if ( $averageonly == NULL ) {
                    $chart->add_series($series);
                }

                $i++;
            }

            // Create a series with averages
            if ( $average ) {

                $average_array = array();

                foreach ( $users as $user ) {
                    $user_grades = getActivitiesGradeFromUserID($user, $courseid, $activities);
                    array_push($average_array, getAverage($user_grades, NULL));
                }

                $average_series = new \core\chart_series('Average', $average_array);
                $chart->add_series($average_series);

            }

            // More settings
            $chart->set_labels($usernames);
            if ($custom_title) {
                $chart->set_title($custom_title);
            }
            if ($viewtype == 'horizontal-bars') {
                $chart->set_horizontal(true);
            }

            // Output chart
            echo $OUTPUT->render_chart($chart);

            echo '<hr />';

            echo '<a href="http://d1abo.i234.me/labs/moodle/grade/report/scgr/index.php?id=' . $courseid . '">Back</a>';

            exportAsJPEG();

        } else {

            echo html_writer::tag('h3', 'Error');
            echo html_writer::tag('p', 'users or grades not avalaible.');
            echo '<a href="index.php?id=' . $courseid . '">Back</a>';

        }

    } elseif ( isset($modality) && $modality == 'inter' ) {

        $groupnames = getGroupNames($courseid);
        $groups = getGroupsIDS($courseid);

        // Output graph if $groupnames and $activities
        if ( $activities && $groupnames ) {

            $chart = new \core\chart_bar(); // Create a bar chart instance.

            foreach ( $activities as $activity ) {
                $grades = getGradesFromGroups($courseid, $activity);
                $series = new \core\chart_series(getActivityName($activity), $grades);
                $chart->add_series($series);
            }

            // Create a series with averages
            if ( $average ) {

                $averages = array();

                foreach ( $groups as $group ) {

                    $group_grades = getActivitiesGradeFromGroupID($group, $courseid, $activities);
                    $group_average = getAverage($group_grades, NULL);
                    array_push($averages, $group_average);
                }

                $average_series = new \core\chart_series('Average', $averages);
                $chart->add_series($average_series);
            }

            // Chart settings
            if ($custom_title) { $chart->set_title($custom_title); }
            if ($viewtype == 'horizontal-bars') { $chart->set_horizontal(true); }
            $chart->set_labels($groupnames);

            echo $OUTPUT->render_chart($chart);

            echo '<hr />';

            echo '<a href="http://d1abo.i234.me/labs/moodle/grade/report/scgr/index.php?id=' . $courseid . '">Back</a> - ';

            exportAsJPEG();

        } else {

            echo html_writer::tag('h3', 'Error');
            echo html_writer::tag('p', 'users or grades not avalaible.');
            echo '<a href="http://d1abo.i234.me/labs/moodle/grade/report/scgr/index.php?id=' . $courseid . '">Revenir</a>';

        }

    }

}

function printPluginConfig() {
    global $CFG;

    // Options
    echo html_writer::tag('h5', 'Plugin Config');

    echo '<ul>';

    if ( $CFG->scgr_plugin_disable ) {
        echo html_writer::tag('li', 'scgr_plugin_disable : ' . $CFG->scgr_plugin_disable );
    }

    if ( $CFG->scgr_course_activation_choice ) {
        echo html_writer::tag('li', 'scgr_course_activation_choice : ' . $CFG->scgr_course_activation_choice );
    }

    if ( $CFG->scgr_course_groups_activation_choice ) {
        echo html_writer::tag('li', 'scgr_course_groups_activation_choice : ' . $CFG->scgr_course_groups_activation_choice );
    }

    echo '</ul>';

    echo '<hr>';

}

function exportAsJPEG() {

    echo '<a onclick="canvasToImage(\'#FFFFFF\')" download="export.jpg" href="" id="chartdl">Export as JPG</a>';

    // Improve heritage
    echo '<script type="text/javascript">
	    	function canvasToImage(backgroundColor)	{
			var canvas = document.getElementsByTagName("canvas")[0];
			var context = canvas.getContext("2d");
			//cache height and width		
			//var w = canvas.width;
			//var h = canvas.height;
			var w = 1920;
			var h = 1080;

			var data;

			if(backgroundColor)
			{
				//get the current ImageData for the canvas.
				data = context.getImageData(0, 0, w, h);

				//store the current globalCompositeOperation
				var compositeOperation = context.globalCompositeOperation;

				//set to draw behind current content
				context.globalCompositeOperation = "destination-over";

				//set background color
				context.fillStyle = backgroundColor;

				//draw background / rect on entire canvas
				context.fillRect(0,0,w,h);
			}

			//get the image data from the canvas
			var imageData = canvas.toDataURL("image/jpeg");

			if(backgroundColor)
			{
				//clear the canvas
				context.clearRect (0,0,w,h);

				//restore it with original / cached ImageData
				context.putImageData(data, 0,0);

				//reset the globalCompositeOperation to what it was
				context.globalCompositeOperation = compositeOperation;
			}

			//return the Base64 encoded data url string
			document.getElementById("chartdl").href=imageData;
		}
	    </script>';

}

function stripTutorsGroupFromGroupIDS($groups) {

    $groups_to_ignore = array(1);
    $new_groups = array();

    foreach ( $groups as $group ) {

        if ( !in_array($group, $groups_to_ignore) ) {
            array_push($new_groups, $group);
        }

    }

    return $new_groups;
}

function stripTutorsFromUsers($users, $context) {

    $role_to_ignore = array(1);
    $new_users = array();

    foreach ($users as $userid) {

        $user_roles = get_user_roles($context, $userid, false);
        $ignore_user = false;

        foreach ( $user_roles as $role ) {

            if ( $role->shortname == 'teacher' ) {
                $ignore_user = true;
            }

        }

        if ( $ignore_user == false ) {
            array_push($new_users, $userid);
        }

    }

    return $new_users;

}

/*
 * getAverage
 *
 * returns an array with simple averages (automatic weighting) from two arrays with float values inside.
 *
 * @activity1 (array) array containing X float values inside
 * @activity2 (array) array containing X float values inside
 * @return (array)
 */

function getAverage( $grades, $weights = NULL ) {

    if ( !$weights & $grades ) {

        $result = array_sum($grades) / count($grades);

    } else {

    }

    return $result;

}

/*
 * getGradesFromGroups
 *
 * returns an array with X grades (average grade for each group) for a given activity.
 *
 * @courseid (int)
 * @activity (int) Moodle activity ID
 * @return (array)
 */

function getGradesFromGroups( $courseid, $activity ) {

    $groups = getGroupsIDS($courseid);
    $groups_grades = array();

    foreach ( $groups as $groupid ) {

        $users = getUsersFromGroup($groupid);
        $grading_info = grade_get_grades($courseid, 'mod', 'assign', $activity, $users);
        $users_grades = array();
        $total = 0;

        foreach ($users as $user) {

            $user_grade = $grading_info->items[0]->grades[$user]->grade;
            array_push( $users_grades, floatval($user_grade) );

            $total = $total + floatval($user_grade);
        }

        $count = count( $users_grades );
        $average = $total / $count;

        // Push average grade of group in array
        array_push($groups_grades, $average);

    }

    return $groups_grades;

}

/*
 * getGroupsIDS
 *
 * returns an array with ID's of groups found in a course
 *
 * @courseid (int)
 * @return (array)
 */

function getGroupsIDS( $courseid ) {
    $groups = groups_get_all_groups($courseid);
    $groups_array = array();

    foreach ( $groups as $group ) {
        array_push( $groups_array, intval($group->id) );
    }

    return $groups_array;

}

/*
 * getActivityName
 *
 * returns the name of an activity (based on it's instance)
 *
 * @instanceitem (object)
 * @return (string)
 */

function getActivityName($instanceitem) {
    global $DB;

    $sql = "SELECT * FROM unitice_assign WHERE id = $instanceitem";           // SQL Query
    $records = $DB->get_records_sql($sql);

    foreach ($records as $record) {
        return $record->name;
    }
}

function getActivitiesNames($activities) {
    global $DB;

    $activities_names = array();

    foreach ( $activities as $activity) {
        $sql = "SELECT * FROM unitice_assign WHERE id = $activity";           // SQL Query
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            array_push($activities_names, $record->name);
        }
    }

    return $activities_names;
}

/*
 * getGrades
 *
 * returns the grade of users for a certain activity
 *
 * @users (array)
 * @courseid (int)
 * @activity (int?)
 *
 * @return (array)
 */

function getGrades($users, $courseid, $activity) {

    $grading_info = grade_get_grades($courseid, 'mod', 'assign', $activity, $users);
    $grades = array();

    foreach ($users as $user) {

        if ( !empty($grading_info->items) ) {
            $grade = $grading_info->items[0]->grades[$user]->grade;
            array_push($grades, floatval($grade));
        }

    }

    return $grades;

}

function getEnrolledUsersFromContext($context) {

    $fields = 'u.id, u.username';              //return these fields
    $users = get_enrolled_users($context, '', 0, $fields);
    $users_array = array();

    foreach ( $users as $user ) {
        array_push($users_array, intval($user->id));
    }

    return $users_array;

}

function getActivityGradeFromGroupID($groupid, $courseid, $activity) {

    $users = getUsersFromGroup($groupid);
    $grades = array();

    foreach ($users as $userid) {

        $grading_info = grade_get_grades($courseid, 'mod', 'assign', $activity, $userid);
        $grade = NULL;

        if ( !empty($grading_info->items) ) {
            $grade = $grading_info->items[0]->grades[$userid]->grade;
            array_push($grades, $grade);
        }

    }

    if ( count($grades) != 0 ) {
        $grade = array_sum($grades) / count($grades);
    } else {
        $grade = 0;
    }

    return $grade;

}

function getActivitiesGradeFromGroupID($groupid, $courseid, $activities) {

    $grades = array();

    foreach ( $activities as $activity ) {
        $grade = getActivityGradeFromGroupID($groupid, $courseid, $activity);
        array_push($grades, $grade);
    }

    return $grades;

}

function getActivityGradeFromUserID($userid, $courseid, $activity) {

    $grading_info = grade_get_grades($courseid, 'mod', 'assign', $activity, $userid);
    $grade = NULL;

    if ( !empty($grading_info->items) ) {
        $grade = $grading_info->items[0]->grades[$userid]->grade;
        return $grade;
    }

}

function getActivitiesGradeFromUserID($userid, $courseid, $activities) {

    $grades = array();

    foreach ($activities as $activity) {

        $grading_info = grade_get_grades($courseid, 'mod', 'assign', $activity, $userid);

        if ( !empty($grading_info->items) ) {
            $grade = $grading_info->items[0]->grades[$userid]->grade;
            array_push($grades, floatval($grade));
        }

    }

    return $grades;

}

function getActivitiesGradeFromUsers($users, $courseid, $activities) {

    $average_grades = array();

    foreach ($activities as $activity) {

        $grades = array();

        foreach ( $users as $user ) {

            $grading_info = grade_get_grades($courseid, 'mod', 'assign', $activity, $user);

            if ( !empty($grading_info->items) ) {

                if ( !empty($grading_info->items[0]->grades[$user]->grade) ) {

                    $grade = $grading_info->items[0]->grades[$user]->grade;
                    array_push($grades, floatval($grade));

                }
            }

        }

        if ( count($grades) != 0 ) {
            $average_grade = array_sum($grades) / count($grades);
        } else {
            $average_grade = 0;
        }

        array_push($average_grades, number_format(floatval($average_grade), 2));

    }

    return $average_grades;

}

/*
 * getCoursesIDandNames
 *
 * returns an array with courses ID's and names
 *
 * @return (array)
 */

function getCoursesIDandNames() {
    $courses = get_courses();
    $courses_array = array();

    foreach ( $courses as $course ) {

        if ( $course->format != 'site' ) {

            $courses_array[$course->id] = $course->fullname;

        }
    }

    return $courses_array;
}

/*
 * getSectionsFromCourseID
 *
 * returns the sections included in a course
 *
 * @courseid (int)
 *
 * @return (array)
 */

function getSectionsFromCourseID($courseid) {
    global $DB;

    $sql = "SELECT * FROM unitice_course_sections
            WHERE course = $courseid";         // SQL Query
    $records = $DB->get_records_sql($sql);                  // Get records with Moodle function
    $sections_list = array();                               // Initialize sections array (empty)
    foreach ( $records as $record ) {                       // This loop populates sections array
        $sections_list[$record->id] = $record->name . ' (' . $record->id . ')';
    }

    return $sections_list;
}

/*
 * getActivitiesFromCourseID
 *
 * returns the an array with all the activities included in a course
 *
 * @courseid (int)
 * @categoryid (int)
 *
 * @return (array)
 */

function getActivitiesFromCourseID($courseid, $categoryid) {
    global $DB;

    $sql = "SELECT * FROM unitice_grade_items
                    WHERE courseid = " . $courseid . "
                    AND hidden != 1
                    AND categoryid = " . $categoryid . " ORDER BY iteminstance";       // SQL Query
    $records = $DB->get_records_sql($sql);                  // Get records with Moodle function
    $activities_list = array();

    foreach ( $records as $record ) {
        $activities_list[$record->iteminstance] = $record->itemname . ' (' . $record->iteminstance . ')';
    }

    return $activities_list;
}

/*
 * getUsersFromGroup
 *
 * returns an array with users from a given group
 *
 * @groupid (int)
 *
 * @return (array)
 */

function getUsersFromGroup($groupid) {
    $fields = 'u.id, u.username';              //return these fields
    $users = groups_get_members($groupid, $fields, $sort='lastname ASC');
    $users_array = array();

    foreach ( $users as $user ) {
        array_push($users_array, intval($user->id));
    }

    return $users_array;
}

/*
 * getUsernamesFromGroup
 *
 * returns an array with the user's names from a group
 *
 * @groupid (int)
 *
 * @return (array)
 */

function getUsernamesFromGroup($groupid) {

    $fields = 'u.username';              //return these fields
    $users = groups_get_members($groupid, $fields, $sort='lastname ASC');

    $usernames = array();

    foreach ( $users as $user ) {
        array_push($usernames, $user->username);
    }

    return $usernames;
}

/*
 * getGroups
 *
 * returns an array with Groups id's and names
 *
 * @courseid (int)
 *
 * @return (array)
 */

function getGroups($courseid) {
    $groups = groups_get_all_groups($courseid);
    $groups_array = array();

    foreach ( $groups as $group ) {
        $groups_array[$group->id] = $group->name;
    }

    return $groups_array;
}

/*
 * getGroupNames
 *
 * returns an array with Groups names
 *
 * @courseid (int)
 *
 * @return (array)
 */

function getGroupNames($courseid) {
    $groups = groups_get_all_groups($courseid);
    $groups_array = array();

    foreach ( $groups as $group ) {
        array_push($groups_array, $group->name);
    }

    return $groups_array;
}