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
 * Answer Student Question API
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
    if (empty($input['question_id']) || empty($input['answer_text'])) {
        throw new Exception('Missing required fields: question_id and answer_text');
    }
    
    $question_id = (int)$input['question_id'];
    $answer_text = trim($input['answer_text']);
    $teacher_id = $USER->id;
    
    // Get question details
    $question = $DB->get_record('theme_remui_kids_student_questions', ['id' => $question_id], '*', MUST_EXIST);
    $student = $DB->get_record('user', ['id' => $question->student_id], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $question->course_id], '*', MUST_EXIST);
    
    // Get forum discussion
    $discussion = $DB->get_record('forum_discussions', ['id' => $question->discussion_id], '*', MUST_EXIST);
    $forum = $DB->get_record('forum', ['id' => $question->forum_id], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('forum', $forum->id, $course->id);
    $context = context_module::instance($cm->id);
    
    // Create reply post
    $post = new stdClass();
    $post->discussion = $question->discussion_id;
    $post->parent = $discussion->firstpost; // Reply to the original question
    $post->userid = $teacher_id;
    $post->created = time();
    $post->modified = time();
    $post->mailed = FORUM_MAILED_PENDING;
    $post->subject = "Re: " . $discussion->name;
    $post->message = $answer_text;
    $post->messageformat = FORMAT_HTML;
    $post->messagetrust = trusttext_trusted($context);
    $post->attachment = "";
    $post->totalscore = 0;
    $post->mailnow = 1; // Send email notification
    
    // Add message counts
    \mod_forum\local\entities\post::add_message_counts($post);
    $post->id = $DB->insert_record("forum_posts", $post);
    
    // Update discussion modified date
    $DB->set_field("forum_discussions", "timemodified", $post->modified, ["id" => $question->discussion_id]);
    $DB->set_field("forum_discussions", "usermodified", $post->userid, ["id" => $question->discussion_id]);
    
    // Update question status
    $DB->set_field('theme_remui_kids_student_questions', 'status', 'answered', ['id' => $question_id]);
    $DB->set_field('theme_remui_kids_student_questions', 'answered_by', $teacher_id, ['id' => $question_id]);
    $DB->set_field('theme_remui_kids_student_questions', 'answered_at', time(), ['id' => $question_id]);
    
    // Send notification to student
    $message_text = "Your question has been answered!\n\n";
    $message_text .= "Course: {$course->shortname}\n";
    $message_text .= "Subject: {$question->subject}\n";
    $message_text .= "Grade: {$question->grade}\n\n";
    $message_text .= "Answer from {$USER->firstname} {$USER->lastname}:\n";
    $message_text .= $answer_text . "\n\n";
    $message_text .= "View the full discussion in your course forum.";
    
    // Send message using Moodle's messaging system
    message_post_message($USER, $student, $message_text, FORMAT_PLAIN);
    
    // Log the answer
    $answer_log = new stdClass();
    $answer_log->question_id = $question_id;
    $answer_log->teacher_id = $teacher_id;
    $answer_log->answer_text = $answer_text;
    $answer_log->post_id = $post->id;
    $answer_log->created_at = time();
    $answer_log->id = $DB->insert_record('theme_remui_kids_question_answers', $answer_log);
    
    echo json_encode([
        'success' => true,
        'message' => 'Answer posted successfully!',
        'answer_id' => $answer_log->id,
        'post_id' => $post->id,
        'forum_url' => new moodle_url('/mod/forum/discuss.php', ['d' => $question->discussion_id])
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
