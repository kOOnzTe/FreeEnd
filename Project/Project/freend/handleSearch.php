<?php
session_start();
require_once "./db.php";


if (!isset($_SESSION['user'])) {
    echo 'You must be logged in to perform this action.';
    exit;
}

$searchQuery = $_GET['query'] ?? '';

$stmt = $db->prepare("SELECT * FROM user WHERE email LIKE :query");

$stmt->execute([
    'query' => '%' . $searchQuery . '%',
]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($results) > 0) {
    foreach ($results as $user) {
        echo '<div class="col s4 col-search">';
        echo '<div class="card" style="border-radius: 7px; overflow: hidden;">';
        echo '<div class="card-image waves-effect waves-block waves-light">';
        echo '<img class="activator" src="images/' . htmlspecialchars($user['profile'], ENT_QUOTES, 'UTF-8') . '">';
        echo '</div>';
        echo '<div class="card-content grey lighten-3">';
        echo '<span class="card-title activator grey-text text-darken-4">Name: ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . '<i class="material-icons right">more_vert</i></span>';
        echo '<p>Email: ' . htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') . '</p>';
        echo '</div>';
        echo '<div class="card-action grey lighten-3">';
        echo '<button class="add-friend waves-effect waves-light btn button-image-profile " data-user-id="' . $user['id'] . '">Add Friend</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
} else {
    echo '<p class="center-align result-text">No results found.</p>';
}
