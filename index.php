<?php
session_start();
ini_set('display_errors', true);
require_once('vendor/autoload.php');

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /");
    die();
}

// DATABASE CONNECTION
try {
    $pdo = new PDO("mysql:host=".getenv('FA_DB_HOST').";dbname=".getenv('FA_DB_DBNAME'), getenv('FA_DB_USERNAME'), getenv('FA_DB_PASSWORD'));
} catch(PDOException $e) {
    exit($e->getMessage());
}

// DATABASE INSERT
if ($_POST) {

    $insert = [
        'user_name' => null,
        'user_email' => null,
        'artist' => null,
        'album' => null,
        'review' => null,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $data = array_merge($_POST, $_SESSION);
    $insert = array_replace($insert, array_intersect_key($data, $insert));

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

// FACEBOOK START
$fb = new Facebook\Facebook([
  'app_id' => getenv('FB_APP_ID'),
  'app_secret' => getenv('FB_APP_SECRET'),
  'default_graph_version' => 'v2.4',
]);

// FACEBOOK LOGIN
try {
    $helper      = $fb->getRedirectLoginHelper();
    $accessToken = $helper->getAccessToken();
    $loginUrl    = $helper->getLoginUrl('http://feedback-alpha.outspaced.com/', ['email', 'public_profile']);

    // Actually log in
    if ( ! empty($accessToken)) {
        $response = $fb->get('/me?fields=name,email', $accessToken);

        $me = $response->getGraphUser();

        $_SESSION['user_email'] = $me->getField('email');
        $_SESSION['user_name'] = $me->getName();

        header("Location: /");
        die();
    }

} catch(Facebook\Exceptions\FacebookSDKException $e) {
    header("Location: /");
    die();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
    header("Location: /");
    die();
}

?><!DOCTYPE html>
<html>
<head>
    <title>Feedback Alpha</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="/css/callout.css">

    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <script src="//code.jquery.com/jquery-2.1.4.min.js"></script>

</head>
<body>

<?php



if ( ! isset($_SESSION['user_email'])):
?>
<div class="container" id="login-container">
    <div id="callout-type-b-i-elems" class="bs-callout bs-callout-info">

        <a href="<?= $loginUrl ?>"><img src="img/login_with_facebook.png"/></a>

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
        <p>Yeah.  Sorry.  It will collect your email address, but obviously I won't do anything evil with it.  <a href="alex.brims@gmail.com">Email me</a> if you really hate this.</p>
    </div>
</div>
<?php else: ?>
<div class="container" id="review-container">
    <div id="review-form" class="col-md-8">
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
                <textarea class="form-control" rows="3"  id="review" name="review" placeholder="Review" required></textarea>
            </div>

            <input type="hidden" name="user_name" id="user_name" value=""/>
            <input type="hidden" name="user_email" id="user_email" value=""/>

            <p><button type="submit" class="btn btn-primary">Submit</button></p>
        </form>
        <p><a href="/?logout=hellyeah" class="btn btn-danger" id="facebook-logout" role="button">Log out</a></p>

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
<?php endif; ?>
</div>
</body>
</html>
