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
 * Get Student Questions API
 *
 * @package   theme_remui_kids
 * @copyright 2024 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');

// Check if user is logged in
require_login();

// Set JSON header
header('Content-Type: application/json');

// Get parameters
$course_id = optional_param('course_id', 0, PARAM_INT);
$grade = optional_param('grade', '', PARAM_TEXT);
$subject = optional_param('subject', '', PARAM_TEXT);
$status = optional_param('status', '', PARAM_TEXT);
$limit = optional_param('limit', 20, PARAM_INT);
$offset = optional_param('offset', 0, PARAM_INT);

try {
    // Build query conditions
    $conditions = [];
    $params = [];
    
    if ($course_id > 0) {
        $conditions[] = "q.course_id = ?";
        $params[] = $course_id;
    }
    
    if (!empty($grade)) {
        $conditions[] = "q.grade = ?";
        $params[] = $grade;
    }
    
    if (!empty($subject)) {
        $conditions[] = "q.subject = ?";
        $params[] = $subject;
    }
    
    if (!empty($status)) {
        $conditions[] = "q.status = ?";
        $params[] = $status;
    }
    
    $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Get questions with student info
    $sql = "SELECT q.*, 
                   u.firstname, u.lastname, u.email,
                   c.shortname as course_name,
                   f.name as forum_name,
                   d.name as discussion_name,
                   d.timemodified as last_activity
            FROM {theme_remui_kids_student_questions} q
            JOIN {user} u ON q.student_id = u.id
            JOIN {course} c ON q.course_id = c.id
            JOIN {forum} f ON q.forum_id = f.id
            JOIN {forum_discussions} d ON q.discussion_id = d.id
            {$where_clause}
            ORDER BY q.created_at DESC";
    
    $questions = $DB->get_records_sql($sql, $params, $offset, $limit);
    
    // Get total count
    $count_sql = "SELECT COUNT(*)
                  FROM {theme_remui_kids_student_questions} q
                  {$where_clause}";
    $total_count = $DB->count_records_sql($count_sql, $params);
    
    // Format questions for frontend
    $formatted_questions = [];
    foreach ($questions as $question) {
        $formatted_questions[] = [
            'id' => $question->id,
            'student_name' => $question->firstname . ' ' . $question->lastname,
            'student_email' => $question->email,
            'course_name' => $question->course_name,
            'grade' => $question->grade,
            'subject' => $question->subject,
            'question_text' => $question->question_text,
            'status' => $question->status,
            'created_at' => date('Y-m-d H:i:s', $question->created_at),
            'last_activity' => date('Y-m-d H:i:s', $question->last_activity),
            'forum_url' => new moodle_url('/mod/forum/discuss.php', ['d' => $question->discussion_id]),
            'discussion_name' => $question->discussion_name
        ];
    }
    
    echo json_encode([
        'success' => true,
        'questions' => $formatted_questions,
        'total_count' => $total_count,
        'has_more' => ($offset + $limit) < $total_count
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
