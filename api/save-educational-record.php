<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (empty($input['childId']) || empty($input['schoolYear'])) {
        throw new Exception('Child ID and School Year are required');
    }
    
    // Load existing educational records
    $educationalFile = 'api/data/children-educational-background.json';
    $educationalRecords = [];
    
    if (file_exists($educationalFile)) {
        $educationalData = file_get_contents($educationalFile);
        $educationalRecords = json_decode($educationalData, true) ?: [];
    }
    
    // Generate unique record ID
    $recordId = 'ED' . date('Ymd') . '_' . uniqid();
    
    // Create new educational record
    $newRecord = [
        'recordId' => $recordId,
        'childId' => $input['childId'],
        // Section 1: Child Education Profile
        'currentSchoolName' => $input['currentSchoolName'] ?? '',
        'schoolType' => $input['schoolType'] ?? '',
        'currentGradeLevel' => $input['currentGradeLevel'] ?? '',
        'schoolAddress' => $input['schoolAddress'] ?? '',
        'adviserContactPerson' => $input['adviserContactPerson'] ?? '',
        'enrollmentDate' => $input['enrollmentDate'] ?? '',
        // Section 2: Academic History
        'schoolYear' => $input['schoolYear'],
        'schoolName' => $input['schoolName'] ?? '',
        'gradeLevelCompleted' => $input['gradeLevelCompleted'] ?? '',
        'generalAverage' => $input['generalAverage'] ?? '',
        'achievementsAwards' => $input['achievementsAwards'] ?? '',
        'reasonForTransferExit' => $input['reasonForTransferExit'] ?? '',
        'staffNotes' => $input['staffNotes'] ?? '',
        // Section 3: Performance & Support
        'strongSubjects' => $input['strongSubjects'] ?? '',
        'subjectsNeedingSupport' => $input['subjectsNeedingSupport'] ?? '',
        'attendanceRecord' => $input['attendanceRecord'] ?? '',
        'behavioralNotes' => $input['behavioralNotes'] ?? '',
        'tutoringReceived' => $input['tutoringReceived'] ?? '',
        'specialEducationNeeds' => $input['specialEducationNeeds'] ?? '',
        // Metadata
        'createdBy' => $input['createdBy'] ?? 'System',
        'dateCreated' => date('Y-m-d H:i:s'),
        'lastModified' => date('Y-m-d H:i:s')
    ];
    
    // Add to records array
    $educationalRecords[] = $newRecord;
    
    // Save to file
    if (!file_put_contents($educationalFile, json_encode($educationalRecords, JSON_PRETTY_PRINT))) {
        throw new Exception('Failed to save educational record to file');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Educational record saved successfully',
        'recordId' => $recordId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
