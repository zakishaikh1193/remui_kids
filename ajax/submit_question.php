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
 * Submit Student Question API
 *
 * @package   theme_remui_kids
 * @copyright 2024 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once($CFG->dirroot . '/message/lib.php');

// Check if user is logged in
require_login();

// Set JSON header
header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

try {
    // Validate required fields
    $required_fields = ['question_text', 'course_id', 'grade', 'subject'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }
    
    $question_text = trim($input['question_text']);
    $course_id = (int)$input['course_id'];
    $grade = trim($input['grade']);
    $subject = trim($input['subject']);
    $student_id = $USER->id;
    
    // Get course info
    $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);
    
    // Get or create "Student Questions" forum for this course
    $forum = $DB->get_record('forum', [
        'course' => $course_id,
        'name' => 'Student Questions'
    ]);
    
    if (!$forum) {
        // Create the forum
        $forum = new stdClass();
        $forum->course = $course_id;
        $forum->name = 'Student Questions';
        $forum->intro = 'Students can ask questions here. Teachers will respond to help you learn better.';
        $forum->introformat = FORMAT_HTML;
        $forum->type = 'qanda'; // Q&A format
        $forum->assessed = 0;
        $forum->scale = 0;
        $forum->maxbytes = 0;
        $forum->maxattachments = 0;
        $forum->forcesubscribe = 0;
        $forum->trackingtype = 1;
        $forum->rsstype = 0;
        $forum->rssarticles = 0;
        $forum->timemodified = time();
        $forum->id = $DB->insert_record('forum', $forum);
        
        // Create course module
        $cm = new stdClass();
        $cm->course = $course_id;
        $cm->module = $DB->get_field('modules', 'id', ['name' => 'forum']);
        $cm->instance = $forum->id;
        $cm->section = 0;
        $cm->idnumber = '';
        $cm->added = time();
        $cm->score = 0;
        $cm->indent = 0;
        $cm->visible = 1;
        $cm->visibleoncoursepage = 1;
        $cm->visibleold = 1;
        $cm->groupmode = 0;
        $cm->groupingid = 0;
        $cm->completion = 0;
        $cm->completionview = 0;
        $cm->completionexpected = 0;
        $cm->showdescription = 0;
        $cm->availability = null;
        $cm->deletioninprogress = 0;
        $cm->cmid = $DB->insert_record('course_modules', $cm);
        
        // Add to course section
        $section = $DB->get_record('course_sections', [
            'course' => $course_id,
            'section' => 0
        ]);
        if ($section) {
            $sequence = $section->sequence ? $section->sequence . ',' . $cm->cmid : $cm->cmid;
            $DB->set_field('course_sections', 'sequence', $sequence, ['id' => $section->id]);
        }
    }
    
    // Create discussion for the question
    $discussion = new stdClass();
    $discussion->course = $course_id;
    $discussion->forum = $forum->id;
    $discussion->name = "Question: {$subject} ({$grade})";
    $discussion->message = $question_text;
    $discussion->messageformat = FORMAT_HTML;
    $discussion->messagetrust = 0;
    $discussion->groupid = 0;
    $discussion->mailnow = 1; // Send email notification
    $discussion->timestart = 0;
    $discussion->timeend = 0;
    $discussion->timelocked = 0;
    $discussion->pinned = 0;
    $discussion->timemodified = time();
    
    $discussion_id = forum_add_discussion($discussion);
    
    if ($discussion_id) {
        // Get course teachers
        $teachers = get_enrolled_users(
            context_course::instance($course_id),
            'moodle/course:manageactivities',
            0,
            'u.id, u.firstname, u.lastname, u.email',
            'u.firstname, u.lastname'
        );
        
        // Send notifications to teachers
        foreach ($teachers as $teacher) {
            $message_text = "New student question in {$course->shortname}:\n\n";
            $message_text .= "Student: {$USER->firstname} {$USER->lastname}\n";
            $message_text .= "Grade: {$grade}\n";
            $message_text .= "Subject: {$subject}\n\n";
            $message_text .= "Question: {$question_text}\n\n";
            $message_text .= "Please respond in the course forum.";
            
            // Send message using Moodle's messaging system
            message_post_message($USER, $teacher, $message_text, FORMAT_PLAIN);
        }
        
        // Log the question submission
        $question_log = new stdClass();
        $question_log->student_id = $student_id;
        $question_log->course_id = $course_id;
        $question_log->forum_id = $forum->id;
        $question_log->discussion_id = $discussion_id;
        $question_log->grade = $grade;
        $question_log->subject = $subject;
        $question_log->question_text = $question_text;
        $question_log->status = 'pending';
        $question_log->created_at = time();
        $question_log->id = $DB->insert_record('theme_remui_kids_student_questions', $question_log);
        
        echo json_encode([
            'success' => true,
            'message' => 'Question submitted successfully!',
            'question_id' => $question_log->id,
            'discussion_id' => $discussion_id,
            'forum_url' => new moodle_url('/mod/forum/view.php', ['id' => $forum->id])
        ]);
        
    } else {
        throw new Exception('Failed to create discussion');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
