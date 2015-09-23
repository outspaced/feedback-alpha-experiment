<?php session_start(); ini_set('display_errors', true); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Feedback Alpha</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="/css/callout.css">

    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <script src="//code.jquery.com/jquery-2.1.4.min.js"></script>

    <style>
        #review-container {
            display: none;
        }
    </style>
</head>
<body>
<script>
function statusChangeCallback(response) {

    console.log(response.status);
    if (response.status === 'connected') {

        $('#login-container').hide();
        $('#review-container').show();

        FB.api('/me?fields=name,email', function(response) {
            $('#user_name').val(response.name);
            $('#user_email').val(response.email);
        });
    }
}

function checkLoginState() {
    FB.getLoginStatus(function(response) {
        statusChangeCallback(response);
    });
}

window.fbAsyncInit = function() {
    FB.init({
        appId      : '697975330344414',
        cookie     : true,
        xfbml      : true,
        version    : 'v2.2'
    });

    FB.getLoginStatus(function(response) {
        statusChangeCallback(response);
    });
};

(function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));


$(document).ready(function() {
    $('#facebook-logout').click(function() {
        FB.logout(function(response) { location.reload(); } );
    });
});
</script>



<?php

try {
    $pdo = new PDO("mysql:host=".getenv('FA_DB_HOST').";dbname=".getenv('FA_DB_DBNAME'), getenv('FA_DB_USERNAME'), getenv('FA_DB_PASSWORD'));
} catch(PDOException $e) {
    exit($e->getMessage());
}

if ($_POST) {

    $_SESSION['user_email'] = $_POST['user_email'];

    $insert = [
        'user_name' => null,
        'user_email' => null,
        'artist' => null,
        'album' => null,
        'review' => null,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $insert = array_replace($insert, $_POST);

    mail(getenv('FA_CONFIRM_EMAIL'), 'Feedback Alpha submission from '.$insert['user_email'], print_r($insert, true));

    $query = "INSERT INTO
        review (user_name, user_email, artist, album, review, created_at)
    VALUES
        (:user_name, :user_email, :artist, :album, :review, :created_at)";

    $stmt = $pdo->prepare($query);
    $stmt->execute($insert);

    header("Location: /");
    die();
}
?>

<div class="container" id="login-container">
    <div id="callout-type-b-i-elems" class="bs-callout bs-callout-info">

        <fb:login-button size="large" scope="public_profile,email" onlogin="checkLoginState();">
            Login with Facebook
        </fb:login-button>

        <h3>What is this?</h3>
        <p>This is my very basic prototype for a micro-reviews service.  I just want to see if anyone has any interest in writing
            micro reviews of albums, and what kind of things they write.</p>

        <h3>What do you want from me?</h3>
        <p>I want you to write me some mini-reviews, of whatever albums you feel like.  I haven't set a hard limit on how long
            they must be, but currently I am thinking that between 50 and 150 characters is the ideal.</p>

        <h3>What kind of thing should I write?</h3>
        <p>Whatever you want.  It could be about what the album sounds like, how it makes you feel, memories you associate
            with it, or even just a couple of otherwise-unconnected words that fit with it.</p>

        <h3>Do I need to log in with Facebook?</h3>
        <p>Yeah.  Sorry.  <a href="alex.brims@gmail.com">Email me</a> if you hate this.</p>

    </div>
</div>

<div class="container" id="review-container">
    <div id="review-form" class="col-md-8">
        <?php if ( false and ! isset($_SESSION['user_email'])): ?>
            <div id="callout-type-b-i-elems" class="bs-callout bs-callout-info">
                <h3>What is this?</h3>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sodales faucibus dictum. Donec ut dolor volutpat, finibus est id, vehicula purus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Quisque feugiat ac dui vitae blandit. Donec porttitor commodo tellus vel laoreet. Ut eros metus, pretium at justo feugiat, lobortis pretium nulla. Pellentesque iaculis nibh at tortor sagittis commodo. Vivamus imperdiet auctor enim ac consectetur. Pellentesque non quam ac nisl porta posuere. Donec in dignissim lectus. Nulla id massa a eros malesuada vulputate. Aliquam vel orci nisl. Praesent quis ultricies odio. Aenean aliquet leo vitae quam tincidunt ultricies. Donec quis lorem quis enim facilisis interdum nec sit amet lectus. Donec imperdiet, urna eget sagittis consequat, lacus eros sagittis elit, vel blandit nunc eros in lacus.
            </div>
        <?php endif; ?>

        <h3>Add review</h3>
        <form method="post">
            <div class="form-group">
                <label for="artist">Artist</label>
                <input type="text" class="form-control" id="artist" name="artist" placeholder="Artist name" required>
            </div>
            <div class="form-group">
                <label for="album">Album</label>
                <input type="text" class="form-control" id="album" name="album" placeholder="Album title" required>
            </div>
            <div class="form-group">
                <label for="album">Review</label>
                <input type="text" class="form-control" id="review" name="review" placeholder="Review" required>
            </div>

            <input type="hidden" name="user_name" id="user_name" value=""/>
            <input type="hidden" name="user_email" id="user_email" value=""/>

            <p><button type="submit" class="btn btn-primary">Submit</button></p>
        </form>
        <p><button class="btn btn-danger" id="facebook-logout">Log out</button></p>

<?php
if (isset($_SESSION['user_email'])) {
    $query = "SELECT * FROM review WHERE user_email = :user_email ORDER BY id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_email' => $_SESSION['user_email']]);

    if ($stmt->rowCount() > 0) {
        print "<div><h3>Your reviews</h3>";
        while ($row = $stmt->fetch()) {
            print '<div id="callout-type-b-i-elems" class="bs-callout bs-callout-default">';
            print '<h4>'.$row['artist'].' - '.$row['album'].'</h4>';
            print '<p>'.$row['review'].'</p>';
            print '</div>';
        }
        print "</div>";
    }
}
?>
    </div>

    <!-- EXAMPLES -->
    <div id="examples" class="col-md-4">
        <h3>Examples</h3>
        <div id="callout-type-b-i-elems" class="bs-callout bs-callout-default">
            <h4>Aphex Twin - Selected Ambient Works 85-92</h4>
            <p>For an album that sounds like it was created on a Commodore 64, this has aged incredibly well.
            Smooth and lo-fi with an acidic bite when it wants it</p>
        </div>
        <div id="callout-type-b-i-elems" class="bs-callout bs-callout-default">
            <h4>Public Enemy - Apocalypse '91</h4>
            <p>This is the best Public Enemy album, despite what every single other person thinks.  The anger is perfectly
            directed & delivered, all thrown at you via pounding noise.</p>
        </div>
        <div id="callout-type-b-i-elems" class="bs-callout bs-callout-default">
            <h4>Neneh Cherry - Blank Project</h4>
            <p>The most suprising release of the year, a sophisticated electro-soul album with a pounding
                percussive edge.  Excellent.</p>
        </div>
        <div id="callout-type-b-i-elems" class="bs-callout bs-callout-default">
            <h4>The Go! Team - Thunder, Lightning, Strike</h4>
            <p>I am pretty sure that listening to this album gives you super powers</p>
        </div>
        <div id="callout-type-b-i-elems" class="bs-callout bs-callout-default">
            <h4>Holly Herndon - Platform</h4>
            <p>Sounds like a monastic choir fed through a broken record while someone on the side pelts it with rocks</p>
        </div>
        <div id="callout-type-b-i-elems" class="bs-callout bs-callout-default">
            <h4>Goldie - Timeless</h4>
            <p>Soulful waves and dark atmospheres wrapped around hyperactive and complex breakbeats.  Today it still sounds
                fresh, menacing, and beautiful.</p>
        </div>
        <div id="callout-type-b-i-elems" class="bs-callout bs-callout-default">
            <h4>The Cure - Disintigration</h4>
            <p>An album that is desolate, paranoid, and claustrophobic, but somehow still filled with killer pop tunes</p>
        </div>
    </div>
</div>
</body>
</html>
