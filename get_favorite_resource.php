<?php
require 'user/db_config.php';
require 'user/token.php';
require 'user/validation.php';

$data = ensure_token_method_argument(['user_id']);
$userName = $data['user_id'];
// reduces the number of database queries from 3 to 1, 
// potentially improving performance by minimizing round trips to the database
$query = "
    SELECT type, cm.id, cm.name, description, NULL AS audio_length_sec, NULL AS sub_count
    FROM favorite_resource fr
    JOIN category_master cm ON fr.id = cm.id
    WHERE fr.user_id = ? AND fr.type = 'category'

    UNION ALL

    SELECT type, cr.id, cr.name, description, NULL AS audio_length_sec, cr.episode_count AS sub_count
    FROM favorite_resource fr
    JOIN course_master cr ON fr.id = cr.id
    WHERE fr.user_id = ? AND fr.type = 'course'

    UNION ALL

    SELECT type, em.id, em.name, NULL AS description, em.audio_length_sec, em.sentence_count AS sub_count
    FROM favorite_resource fr
    JOIN episode_master em ON fr.id = em.id
    WHERE fr.user_id = ? AND fr.type = 'episod'
";

[$stmt, $result] = exec_query($query, "sss", $userName, $userName, $userName);

// Initialize empty arrays for categories, courses, and episodes
$history = [];
$courses = [];
$favList = [];

while ($row = $result->fetch_assoc()) {
    switch ($row['type']) {
        case 'category':
            $history[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'desc' => $row['description']
            ];
            break;
        case 'course':
            $courses[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'desc' => $row['description'],
                'episode_count' => $row['sub_count']
            ];
            break;
        case 'episod':
            $favList[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'audio_length_sec' => $row['audio_length_sec'],
                'sentence_count' => $row['sub_count']
            ];
            break;
    }
}

$stmt->close();

// Return the JSON response
header('Content-Type: application/json');
echo json_encode([
    'category' => $history,
    'course' => $courses,
    'episode' => $favList
]);
?>
