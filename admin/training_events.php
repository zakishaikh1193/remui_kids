<?php
require_once('../../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/theme/remui_kids/admin/training_events.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Training Events');
$PAGE->set_heading('Training Events');

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_events':
            // For demo purposes, return sample events since we don't have training_events table
            $events = [
                (object)[
                    'id' => 1,
                    'title' => 'Introduction to Web Development',
                    'description' => 'Learn the basics of HTML, CSS, and JavaScript',
                    'start_date' => time() + (7 * 24 * 60 * 60), // 7 days from now
                    'end_date' => time() + (8 * 24 * 60 * 60), // 8 days from now
                    'max_participants' => 30,
                    'location' => 'Computer Lab A',
                    'instructor' => 'John Smith',
                    'status' => 'approved',
                    'created_by' => $USER->id,
                    'created_date' => time() - (2 * 24 * 60 * 60), // 2 days ago
                    'firstname' => $USER->firstname,
                    'lastname' => $USER->lastname,
                    'email' => $USER->email,
                    'registrations_count' => 15
                ],
                (object)[
                    'id' => 2,
                    'title' => 'Database Management Workshop',
                    'description' => 'Advanced SQL and database optimization techniques',
                    'start_date' => time() + (14 * 24 * 60 * 60), // 14 days from now
                    'end_date' => time() + (15 * 24 * 60 * 60), // 15 days from now
                    'max_participants' => 20,
                    'location' => 'Conference Room B',
                    'instructor' => 'Sarah Johnson',
                    'status' => 'pending',
                    'created_by' => $USER->id,
                    'created_date' => time() - (1 * 24 * 60 * 60), // 1 day ago
                    'firstname' => $USER->firstname,
                    'lastname' => $USER->lastname,
                    'email' => $USER->email,
                    'registrations_count' => 8
                ],
                (object)[
                    'id' => 3,
                    'title' => 'Project Management Fundamentals',
                    'description' => 'Essential project management skills and tools',
                    'start_date' => time() + (21 * 24 * 60 * 60), // 21 days from now
                    'end_date' => time() + (22 * 24 * 60 * 60), // 22 days from now
                    'max_participants' => 25,
                    'location' => 'Training Center',
                    'instructor' => 'Mike Davis',
                    'status' => 'approved',
                    'created_by' => $USER->id,
                    'created_date' => time() - (3 * 24 * 60 * 60), // 3 days ago
                    'firstname' => $USER->firstname,
                    'lastname' => $USER->lastname,
                    'email' => $USER->email,
                    'registrations_count' => 22
                ]
            ];
            
            // Filter by status if specified
            $status = $_GET['status'] ?? 'all';
            if ($status !== 'all') {
                $events = array_filter($events, function($event) use ($status) {
                    return $event->status === $status;
                });
            }
            
            // Filter by search if specified
            $search = $_GET['search'] ?? '';
            if (!empty($search)) {
                $events = array_filter($events, function($event) use ($search) {
                    return stripos($event->title, $search) !== false || 
                           stripos($event->description, $search) !== false;
                });
            }
            
            echo json_encode([
                'status' => 'success',
                'events' => array_values($events)
            ]);
            exit;
            
        case 'create_event':
            $raw_input = file_get_contents('php://input');
            $data = json_decode($raw_input, true);
            
            try {
                $required_fields = ['title', 'description', 'start_date', 'end_date', 'max_participants'];
                foreach ($required_fields as $field) {
                    if (empty($data[$field])) {
                        throw new Exception("Field {$field} is required");
                    }
                }
                
                // For demo purposes, simulate event creation
                // In a real implementation, you would insert into training_events table
                $event_id = rand(1000, 9999); // Generate a random ID for demo
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Training event created successfully',
                    'event_id' => $event_id
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'update_event_status':
            $raw_input = file_get_contents('php://input');
            $data = json_decode($raw_input, true);
            
            try {
                $event_id = $data['event_id'] ?? null;
                $status = $data['status'] ?? null;
                $comments = $data['comments'] ?? '';
                
                if (!$event_id || !$status) {
                    throw new Exception("Event ID and status are required");
                }
                
                // For demo purposes, simulate status update
                // In a real implementation, you would update the training_events table
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Event status updated successfully'
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'get_event_details':
            $event_id = $_GET['event_id'] ?? null;
            
            if (!$event_id) {
                echo json_encode(['status' => 'error', 'message' => 'Event ID required']);
                exit;
            }
            
            // For demo purposes, return sample event details
            // In a real implementation, you would query the training_events table
            $event = (object)[
                'id' => $event_id,
                'title' => 'Sample Training Event',
                'description' => 'This is a sample training event for demonstration purposes.',
                'start_date' => time() + (7 * 24 * 60 * 60),
                'end_date' => time() + (8 * 24 * 60 * 60),
                'max_participants' => 30,
                'location' => 'Training Center',
                'instructor' => 'John Smith',
                'status' => 'approved',
                'created_by' => $USER->id,
                'created_date' => time() - (2 * 24 * 60 * 60),
                'firstname' => $USER->firstname,
                'lastname' => $USER->lastname,
                'email' => $USER->email,
                'approved_by' => $USER->id,
                'approved_date' => time() - (1 * 24 * 60 * 60),
                'approver_firstname' => $USER->firstname,
                'approver_lastname' => $USER->lastname,
                'approval_comments' => 'Event approved for training purposes.'
            ];
            
            // Sample registrations
            $registrations = [
                (object)[
                    'id' => 1,
                    'user_id' => 1,
                    'event_id' => $event_id,
                    'registration_date' => time() - (1 * 24 * 60 * 60),
                    'firstname' => 'Alice',
                    'lastname' => 'Johnson',
                    'email' => 'alice.johnson@example.com',
                    'phone1' => '+1-555-0101'
                ],
                (object)[
                    'id' => 2,
                    'user_id' => 2,
                    'event_id' => $event_id,
                    'registration_date' => time() - (2 * 24 * 60 * 60),
                    'firstname' => 'Bob',
                    'lastname' => 'Smith',
                    'email' => 'bob.smith@example.com',
                    'phone1' => '+1-555-0102'
                ]
            ];
            
            echo json_encode([
                'status' => 'success',
                'event' => $event,
                'registrations' => array_values($registrations)
            ]);
            exit;
            
        case 'get_event_stats':
            // For demo purposes, return sample stats
            // In a real implementation, you would query the training_events table
            echo json_encode([
                'status' => 'success',
                'stats' => [
                    'total_events' => 3,
                    'pending_events' => 1,
                    'approved_events' => 2,
                    'rejected_events' => 0,
                    'total_registrations' => 45
                ]
            ]);
            exit;
    }
}

// Get template data
$template_data = [
    'config' => [
        'wwwroot' => $CFG->wwwroot
    ],
    'user' => [
        'firstname' => $USER->firstname,
        'lastname' => $USER->lastname
    ]
];

// Render template
echo $OUTPUT->render_from_template('theme_remui_kids/training_events', $template_data);
?>
