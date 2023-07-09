<?php

require_once "./db.php";
require_once "./Upload.php";
session_start();
if (!isset($_SESSION['user'])) {
    echo 'You must be logged in to perform this action.';
    exit;
}
//$userNamee = $_SESSION['userName'];

$usersname = $_SESSION['user']['name'];
$currentUser = $_SESSION['user']['id'];
$friendsStmt = $db->prepare("SELECT sender_id, receiver_id FROM friend_requests WHERE (sender_id = :currentUser OR receiver_id = :currentUser) AND status = 2");
$friendsStmt->execute(['currentUser' => $currentUser]);
$friendRows = $friendsStmt->fetchAll(PDO::FETCH_ASSOC);
$friendIds = [];
$frienduniqueID = [];

foreach ($friendRows as $row) {
    //   $_SESSION['friendIds'] = $frienduniqueID;
    $friendId = $row['sender_id'] == $currentUser ? $row['receiver_id'] : $row['sender_id'];
    $friendIds[] = $friendId;
}

$_SESSION['friendIds'] = $friendIds;

if (!empty($friendIds)) {
    $inQuery = implode(',', array_fill(0, count($friendIds), '?'));
    $postsStmt = $db->prepare("SELECT * FROM posts WHERE user_id IN ($inQuery) ORDER BY timestamp DESC LIMIT 10");
    $postsStmt->execute($friendIds);
    $posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($posts)) {
        $last = end($posts);
        $lastPost = $last['timestamp'];
    }
} else {
    $posts = [];
}
$stmt = $db->prepare("SELECT * FROM friend_requests WHERE receiver_id = :user_id AND status = 0");
$stmt->execute([':user_id' => $_SESSION['user']['id']]);

$friendRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT CASE WHEN sender_id = :user_id THEN receiver_id ELSE sender_id END AS user_id FROM friend_requests WHERE status = 2 AND (sender_id = :user_id OR receiver_id = :user_id)");
$stmt->execute([':user_id' => $_SESSION['user']['id']]);

$friendIDs = $stmt->fetchAll(PDO::FETCH_COLUMN);

$friends = [];

if (!empty($friendIDs)) {
    $stmt = $db->prepare("SELECT id, name, email, profile FROM user WHERE id IN (" . implode(',', $friendIDs) . ")");
    $stmt->execute();
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCommentsForPost($postId, $db)
{
    $stmt = $db->prepare("
        SELECT * FROM comments 
        WHERE post_id = ?
        ORDER BY timestamp DESC
    ");
    $stmt->execute([$postId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getUser($userID, $db)
{
    $stmt = $db->prepare("
        SELECT * FROM user 
        WHERE id = ?
    ");
    $stmt->execute([$userID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserPosts($userID, $db)
{
    $stmt = $db->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY timestamp DESC");
    $stmt->execute([$userID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLikesDislikes($postId, $db)
{
    $stmt = $db->prepare("
        SELECT interaction_type, COUNT(*) as count
        FROM likes
        WHERE post_id = ?
        GROUP BY interaction_type
    ");

    $stmt->execute([$postId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $likesDislikes = [
        'likes' => 0,
        'dislikes' => 0,
    ];

    foreach ($result as $row) {
        if ($row['interaction_type'] == 'like') {
            $likesDislikes['likes'] = $row['count'];
        } else if ($row['interaction_type'] == 'dislike') {
            $likesDislikes['dislikes'] = $row['count'];
        }
    }

    return $likesDislikes;
}
function userLiked($postId, $userId, $db)
{
    $stmt = $db->prepare("
        SELECT interaction_type
        FROM likes
        WHERE post_id = ? AND user_id = ?
    ");

    $stmt->execute([$postId, $userId]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return -1;
    }

    return $result['interaction_type'] === 'like' ? 0 : 1;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>FREEEND - Timeline</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://necolas.github.io/normalize.css/8.0.1/normalize.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet" href="css/index.css">
</head>

<body class="all-body">
    <nav class="all-nav">

        <div class="header-all">
            <div class="project-name">FREE-END</div>

        </div>
    </nav>
    <a class="log-text" href="logout.php">
        <div id="buttton-2" class="logout-text"> LOGOUT </div>
        <div><i id="icon" class="material-icons exit-icon">exit_to_app</i> </div>
    </a>
    <div id="timeline" class="cont-timeline">

        <li class="collection-item avatar">
            <!-- <span class="title modal-text">Current User:<?= htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES, 'UTF-8') ?></span> -->

        </li>
        <div id="search" class="input-field all-sections">
            <div class="search-section">

                <input id="search-input" type="text" class="validate" style="  border-bottom: 1px solid #ffffff; width: 300px; " placeholder="  Search for friends...">
                <button id="search-button" class="btn waves-effect waves-light purple lighten-3 button-search"><i class="material-icons">search</i></button>

            </div>
            <div class="top-search-section">
                <div class="logout-section">

                    <a href="#modal-friend-requests" class="btn-floating btn-large waves-effect waves-light modal-trigger friend-modal button-image-profile"><i class="material-icons">notifications</i></a>
                    <a href="#modal-friends" class="btn-floating btn-large waves-effect waves-light modal-trigger button-image-profile friend-modal"><i class="material-icons">group</i></a>
                    <a href="#myPostsModal" class="btn-floating btn-large waves-effect waves-light modal-trigger button-image-profile friend-modal"><i class="material-icons">account_circle</i></a>

                </div>
                <div id="myPostsModal" class="modal friend-req-modal">
                    <div class="modal-content">
                        <h4 class="modal-text">My Posts</h4>
                        <ul id="my-posts-list" class="my-posts-list">
                            <?php
                            $myPosts = getUserPosts($currentUser, $db);
                            foreach ($myPosts as $post) : ?>
                                <li class="collection-item avatar">
                                    <div class="my-post">
                                        <p class="posts-post-text"><?= htmlspecialchars($post['text'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php if ($post['image']) : ?>
                                            <img class="posts-post-image" src="images/<?= htmlspecialchars($post['image'], ENT_QUOTES, 'UTF-8') ?>" alt="Post image">
                                        <?php endif; ?>
                                        <button class="delete-post btn waves-effect waves-light button-image-profile btn-width-post" data-post-id="<?= $post['id'] ?>">Delete</button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <a href="#!" class="modal-close waves-effect waves-green btn-flat modal-text">Close</a>
                    </div>
                </div>
                <div id="modal-friends" class="modal friend-req-modal">
                    <div class="modal-content">
                        <h4 class="modal-text">Friends</h4>
                        <?php if (!empty($friends)) : ?>
                            <ul class="collection">
                                <?php foreach ($friends as $friend) : ?>
                                    <li class="collection-item avatar">
                                        <img src="images/<?= htmlspecialchars($friend['profile'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="circle">
                                        <span class="title modal-text"><?= htmlspecialchars($friend['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <p class="modal-text"><?= htmlspecialchars($friend['email'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <button class="btn waves-effect waves-light remove-friend button-image-profile friend-modal-remove" data-friend-id="<?= $friend['id'] ?>"><i class="material-icons">remove</i></button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <p class="modal-text">No friends to show.</p>

                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="modal-close waves-effect waves-green btn-flat modal-text">Close</a>
                    </div>
                </div>
                <div id="modal-friend-requests" class="modal friend-req-modal">
                    <div class="modal-content">
                        <h4 class="modal-text">Friend Requests</h4>
                        <?php
                        /*   $text = "heree";
                        var_dump($text); */
                        if (!empty($friendRequests)) : ?>

                            <ul class="collection modal-text">
                                <?php foreach ($friendRequests as $request) : ?>
                                    <?php $requested = getUser($request['sender_id'], $db);  ?>
                                    <li class="collection-item avatar" id="req-<?= $request['sender_id'] ?>">
                                        <img src="images/<?= htmlspecialchars($requested[0]['profile'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="circle">
                                        <span class="title modal-text"><?= htmlspecialchars($requested[0]['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <p class="modal-text"><?= htmlspecialchars($requested[0]['email'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <button class="handleRequest btn waves-effect waves-light " data-action="accept" data-sender-id="<?= $request['sender_id'] ?>">Accept</button>
                                        <button class="handleRequest btn waves-effect waves-light " data-action="decline" data-sender-id="<?= $request['sender_id'] ?>">Decline</button>
                                    </li>


                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <p class="modal-text">No new friend requests.</p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="modal-close waves-effect waves-green btn-flat modal-text">Close</a>
                    </div>
                </div>

            </div>
        </div>

        <div class="cont-timeline-second row">
            <div id="search-results" class="search-results"></div>
            <form class="form-section" id="post-form" action="handlePost.php" method="POST" enctype="multipart/form-data">
                <div class="input-field input-share">
                    <textarea class="text-area" id="post-text" class="materialize-textarea" name="text" placeholder="Share something.."></textarea>
                </div>


                <div class="file-field input-field">
                    <div id="buttton-2" class="btn button-image-profile purple lighten-3 btn-width">
                        <span>Upload</span>
                        <input type="file" name="imagename">
                    </div>

                    <div class="file-path-wrapper">
                        <input class="file-path validate image-input" type="text" placeholder="Upload your image...">
                    </div>
                </div>

                <button type="submit" class="btn waves-effect waves-light purple lighten-3 button-image-profile btn-width">Post</button>
            </form>
        </div>
        <div class="title">Posts of your friend's -- TIMELINE</div>
        <div class="all-cards row">
            <?php    /* $text = "heree";
                        var_dump($text); */
            if (!empty($posts)) foreach ($posts as $post) : ?>
                <?php
                $postOwner = getUser($post['user_id'], $db);
                ?>
                <div class="col s4 card-width">
                    <div class="card">

                        <ul class="collection">

                            <li class="collection-item avatar">
                                <img src="images/<?= htmlspecialchars($postOwner[0]['profile'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="circle">
                                <span class="title modal-text"><?= htmlspecialchars($postOwner[0]['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <p class="modal-text"><?= htmlspecialchars($post['timestamp'], ENT_QUOTES, 'UTF-8') ?></p>
                            </li>
                        </ul>

                        <div id="buttton-2" class="card-image waves-effect waves-block waves-light">
                            <?php
                            /*  $text = "heree";
                               var_dump($text); */
                            if ($post['image']) : ?>
                                <img class="activator post-image image-post-card" src="images/<?= htmlspecialchars($post['image'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif;
                            ?>
                        </div>
                        <div id="buttton-2" class="card-content grey lighten-3 black-text card-content-beg">
                            <span class="card-title activator black-text">
                                <?= htmlspecialchars($post['text'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <div class="thumbs">
                                <?php $likesDislikes = getLikesDislikes($post['id'], $db); ?>
                                <span><i class="material-icons icon-white button-image-profile thumb btn-width-2 ">thumb_up</i><span class="l-<?= $post['id'] ?>"><?= htmlspecialchars($likesDislikes['likes'], ENT_QUOTES, 'UTF-8') ?></span></span>
                                <span><i class="material-icons icon-white button-image-profile thumb btn-width-2 ">thumb_down</i><span class="d-<?= $post['id'] ?>"><?= htmlspecialchars($likesDislikes['dislikes'], ENT_QUOTES, 'UTF-8') ?></span></span>
                            </div>
                        </div>
                        <div class="card-action grey lighten-3 like-box">
                            <?php if (userLiked($post['id'], $currentUser, $db)) { ?>
                                <a href="#" class="like button-image-profile  btn-width like-unlike" data-post-id="<?= $post['id'] ?>"> Like</a>
                                <a href="#" class="unlike button-image-profile  btn-width  like-unlike" data-post-id="<?= $post['id'] ?>"> Unlike</a>
                            <?php } ?>
                        </div>
                        <div class="card-content grey lighten-3">
                            <h5>Comments</h5>
                            <ul id="comments-list-<?= $post['id'] ?>" class="comments-list-<?= $post['id'] ?>">
                                <?php

                                $comments = getCommentsForPost($post['id'], $db);
                                foreach ($comments as $comment) :

                                ?>
                                    <li>
                                        <span><strong><?= htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8') ?>:</strong></span>
                                        <?= htmlspecialchars($comment['comment'], ENT_QUOTES, 'UTF-8') ?>
                                        <span class="timestamp" style="font-size: 6px;"> <?= $comment['timestamp'] ?></span>
                                    </li>
                                <?php endforeach;
                                ?>
                            </ul>
                            <div id="buttton-2" class="input-field">
                                <textarea id="comment-<?= $post['id'] ?>" class="materialize-textarea"></textarea>
                                <label for="comment-<?= $post['id'] ?>">Add a comment as<?= $usersname ?></label>
                                <button class="add-comment btn waves-effect waves-light button-image-profile " data-post-id="<?= $post['id'] ?>" data-username="<?= $usersname ?>">Comment</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach;
            ?>

        </div>
        <?php if (isset($lastPost)) { ?>
            <button class="load-more btn waves-effect waves-light button-image-profile " data-post="<?= $lastPost ?>">LOAD MORE</button>
        <?php } ?>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

        <script>
            $(document).ready(function() {
                $('.add-comment').click(function(e) {
                    e.preventDefault();

                    var postId = $(this).data('post-id');
                    var usersname = $(this).data('username');
                    var commentText = $('#comment-' + postId).val();


                    $.ajax({
                        url: 'handleComment.php',
                        method: 'post',
                        data: {
                            post_id: postId,
                            comment: commentText,

                            username: usersname,
                        },
                        success: function(response) {
                            var comment = JSON.parse(response);
                            var addcomment = `<li> <span> ${comment.name}: </span>
                            ${comment.comment}
                            <span style="font-size: 9px"> Just now. <span>
                            `;
                            $('.comments-list-' + postId).prepend(addcomment);
                        },
                        error: function() {
                            alert('An error occurred, here!!.');
                        }
                    });
                });
                /*  $text = "heree";
                         var_dump($text); */
                $('#search-button').on('click', function() {
                    $.ajax({
                        url: 'handleSearch.php',
                        type: 'GET',
                        data: {
                            query: $('#search-input').val()
                        },
                        success: function(response) {
                            $('#search-results').html(response);
                        }
                    });
                });
                /*  $text = "heree";
                         var_dump($text); */
                $('#post-form').on('submit', function(e) {
                    e.preventDefault();

                    var formData = new FormData();
                    formData.append('text', $('#post-text').val());
                    formData.append('imagename', $('input[type=file]')[0].files[0]);

                    $.ajax({
                        url: 'handlePost.php',
                        type: 'post',
                        data: formData,
                        cache: false,
                        contentType: false,

                        processData: false,
                        success: function(response) {

                            var post = JSON.parse(response);

                            var newPostHtml = `
                         
                            <div class="col s8 m2">
                    <div class="card" style="border-radius: 7px; overflow: hidden;">
                        
                    <div class="card-image waves-effect waves-block waves-light">

                            ${post.image ? '<img class="activator" src="images/' + post.image + '">' : ''}
                        </div>
                        <div class="card-content black lighten-3">


                            <span class="card-title activator grey-text">Your Post: ${post.content}</span>
                        </div>
                        <div class="card-action black lighten-3">

                            <a href="#" class="like">Like</a>
                            <a href="#" class="unlike">Unlike</a>
                        </div>
                    </div>
                </div>`;

                            $('#timeline').prepend(newPostHtml);
                        },


                        error: function(xhr, status, error) {
                            console.error(error);
                        }
                    });
                });
                $('.modal').modal();
                $(document).on('click', '.add-friend', function() {
                    console.log("buraya girdi");
                    var userId = $(this).data('user-id');
                    $.ajax({
                        url: 'handleRequest.php',
                        type: 'post',
                        data: {
                            receiver_id: userId
                        },
                        success: function(response) {
                            alert(response);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('Error: ' + textStatus, errorThrown);
                        }
                    });
                });
                $('.delete-post').click(function() {
                    var postId = $(this).data('post-id');

                    $.ajax({
                        url: 'handleDelete.php',
                        method: 'post',
                        data: {
                            post_id: postId
                        },
                        success: function() {
                            alert('Post deleted.');
                            location.reload();
                        },
                        error: function() {
                            alert('error.');
                        }
                    });
                });
                $('.handleRequest').click(function(e) {
                    e.preventDefault();

                    var action = $(this).data('action');
                    var senderId = $(this).data('sender-id');

                    $.ajax({
                        url: 'handleAcceptDecline.php',
                        method: 'post',
                        data: {
                            action: action,
                            sender_id: senderId
                        },
                        success: function(response) {
                            console.log("DONEEEEE")
                            $("#req-" + senderId).hide(100);
                        },
                        error: function() {
                            alert('error.');
                        }
                    });
                });
                $('.remove-friend').click(function() {

                    var friendId = $(this).data('friend-id');

                    $.ajax({
                        url: 'handleDelete.php',
                        method: 'post',
                        data: {
                            friend_id: friendId
                        },
                        success: function() {
                            alert('Friend deleted.');
                            location.reload();
                        },
                        error: function() {
                            alert('An error occurred while deleting the post.');
                        }
                    });
                });
                $('.like, .unlike').on('click', function(e) {
                    e.preventDefault();

                    let postId = $(this).data('post-id');
                    let interactionType = $(this).hasClass('like') ? 'like' : 'dislike';

                    $.ajax({
                        url: 'handleLikes.php',
                        type: 'POST',
                        data: {
                            post_id: postId,
                            interaction_type: interactionType
                        },
                        success: function(response) {
                            type = JSON.parse(response);
                            var value = $(".l-" + postId).text();
                            console.log(value)
                            $('.like-box').hide(100);
                            if (type['type'] == "like") {
                                var value = parseInt($(".l-" + postId).text(), 10) + 1;
                                $(".l-" + postId).text(value);
                            } else {
                                var value = parseInt($(".d-" + postId).text(), 10) + 1;
                                $(".d-" + postId).text(value);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                        }
                    });
                });
                $('.load-more').on('click', function() {
                    /*  $text = "burda yükledi";
                         var_dump($text); */
                    var lastpostTimeStamp = $(this).data('post');

                    $.ajax({
                        url: 'loadMore.php',
                        type: 'POST',
                        data: {
                            lastPost: lastpostTimeStamp
                        },
                        success: function(response) {
                            /*  $text = "girdi";
                               var_dump($text); */
                            let posts = JSON.parse(response);
                            if (posts.length > 0) {
                                newLast = posts[posts.length - 1]['timestamp'];
                                console.log(newLast);
                                posts.forEach(post => {
                                    let commentsHtml = '';
                                    post.comments.forEach(comment => {
                                        commentsHtml += `
                                            <li>
                                                <span><strong>${comment.username}:</strong></span>
                                                ${comment.comment}
                                                <span class="timestamp" style="font-size: 6px;"> ${comment.timestamp}</span>
                                            </li>`;
                                    });


                                    let postHtml = `
                                    <div class="col s4 card-width">
                                        <div class="card">
                                            <ul class="collection">
                                                <li class="collection-item avatar">
                                                    <img src="images/${post.owner.profile}" alt="" class="circle">
                                                    <span class="title modal-text">${post.owner.name}</span>
                                                    <p class="modal-text">${post.timestamp}</p>
                                                </li>
                                            </ul>
                                            <div class="card-image waves-effect waves-block waves-light">
                                                ${post.image ? `<img class="activator post-image image-post-card" src="images/${post.image}">` : ''}
                                            </div>
                                            <div class="card-content grey lighten-3 black-text card-content-beg">
                                                <span class="card-title activator black-text">${post.text}</span>
                                                <div class="thumbs">
                                                    <span><i class="material-icons icon-white button-image-profile thumb btn-width-2">thumb_up</i></span>
                                                    <span><i class="material-icons icon-white button-image-profile thumb btn-width-2">thumb_down</i></span>
                                                </div>
                                            </div>
                                            <div class="card-action grey lighten-3">
                                                <a href="#" class="like button-image-profile btn-width like-unlike" class="thumb-text-text" data-post-id="${post.id}"> Like</a>
                                                <a href="#" class="unlike button-image-profile btn-width like-unlike" class="thumb-text-text" data-post-id="${post.id}"> Unlike</a>
                                            </div>
                                            <div class="card-content grey lighten-3">
                                                <h5>Comments</h5>
                                                <ul id="comments-list-${post.id}" class="comments-list-${post.id}">
                                                    ${commentsHtml}
                                                </ul>
                                                <div class="input-field">
                                                    <textarea id="comment-${post.id}" class="materialize-textarea"></textarea>
                                                    <label for="comment-${post.id}">Add a comment as ${post.owner.name}</label>
                                                    <button class="add-comment btn waves-effect waves-light button-image-profile " data-post-id="${post.id}" data-username="${post.owner.name}">Comment</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>`;
                                    $('.load-more').data('post', newLast);
                                    $('.all-cards').append(postHtml);

                                });
                            } else {
                                alert("No MORE TO LOAD :(")
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                        }
                    });
                });
            });
        </script>
</body>

</html>