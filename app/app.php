<?php
    require_once __DIR__."/../vendor/autoload.php";
    require_once __DIR__."/../src/User.php";
    require_once __DIR__."/../src/Project.php";
    require_once __DIR__."/../src/Message.php";

    $app = new Silex\Application();

    $server = 'mysql:host=localhost:8889;dbname=songlab';
    $username = 'root';
    $password = 'root';
    $DB = new PDO($server, $username, $password);

    $app['debug']=true;

    use Symfony\Component\HttpFoundation\Request;
    Request::enableHttpMethodParameterOverride();

    $app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => __DIR__.'/../views'
    ));

    // Get homepage
    $app->get("/", function() use ($app) {
        session_start();
        $session_status = $_SESSION['user_id'];
        $users = User::getAll();
        $error = "";
        return $app['twig']->render('index.html.twig', array('user' => $users, 'error' => $error, 'session' => $session_status));
    });

    // Get about page
    $app->get("/about", function() use ($app) {
        session_start();
        return $app['twig']->render('about.html.twig');
    });

    // Create user
    $app->post("/user", function() use ($app) {
        $id = null;
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = null; //null - editable in profile
        $username = $_POST['username'];
        $bio = $_POST['bio'];
        $photo = null; //null - upload on profile edit
        $password = $_POST['password1'];
        $user = new User($id, $first_name, $last_name, $email, $username, $bio, $photo, $password);
        $user->save();
        $user_projects = $user->getProjects();
        session_start();
        $_SESSION['user_id'] = $user->getId();
        return $app['twig']->render('private_profile.html.twig', array('user' => $user, 'projects' => $user_projects));
    });

    // Get private user profile
    $app->get("/user/{id}/profile", function($id) use ($app) {
        $user = User::find($id);
        $user_projects = $user->getProjects();
        return $app['twig']->render('private_profile.html.twig', array('user' => $user, 'projects' => $user_projects));
    });

    //delete project from user profile page
    $app->delete("/project/{id}/delete", function($id) use ($app) {
        session_start();
        $project = Project::find($id);
        $project->delete();
        $user = User::find($project->getUserId());

        $user_projects = $user->getOwnerProjects();
        return $app['twig']->render('private_profile.html.twig', array('user' => $user, 'projects' => $user_projects));
    });

    // Get projects list
    $app->get("/projects", function() use ($app){
        session_start();
        $user = User::find($_SESSION['user_id']);
        $projects = Project::getAll();
        $owners = array();
        foreach ($projects as $project){
        $owner = $project->getProjectOwner();
        $owner_name = $owner->getUsername();
        array_push($owners, $owner_name);
        }
        //needs work
        return $app['twig']->render('projects.html.twig', array('projects' => $projects, 'owners' => $owners, 'current_user' => $user));
    });

    // Search projects page
    $app->post("/search", function() use ($app) {
        session_start();
        $username = user::find($id);
        $keyword = $_POST['search_term'];
        $project_matches = Project::search($keyword);
        return $app['twig']->render('projects.html.twig', array('projects' => $project_matches));
    });


    // Send message feature - TBD initial routing
    $app->post("/project/{id}/send_message", function($id) use ($app){
        session_start();
        $project_to_collaborate = Project::find($id);
        $project_owner = $project_to_collaborate->getProjectOwner();
        $id = null;
        $message = $_POST['message'];
        $sender = $_POST['sender'];
        echo $sender;
        $new_message = new Message($id, $message, $sender);
        $new_message->save();
        $project_owner->addMessage($new_message);
        return $app['twig']->render('sent_message.html.twig', array('owner' => $project_owner));
    });

    // Get messages
    $app->get("/user/{id}/messages", function($id) use ($app){
        $user = User::find($id);
        $messages = $user->getOwnerMessages();
        $message_num = count($messages);
        return $app['twig']->render('view_messages.html.twig', array('messages' => $messages, 'count' => $message_num));
    });

    $app->post("/message/{id}/approve", function($id) use ($app){
          //add user to project as collaborator
          $message_to_delete = Message::find($id);
          $message_to_delete->delete();

          $user = User::find($id);
          $messages = $user->getOwnerMessages();
          $message_num = count($messages);
          return $app['twig']->render('view_messages.html.twig', array('messages' => $messages, 'count' => $message_num));
        });

    // Create a user project on private profile
    $app->post("/user/{id}/create_project", function($id) use ($app){
        session_start();
        $user = User::find($id);
        $id = null;
        $title = $_POST['title'];
        $description = $_POST['description'];
        $genre = $_POST['genre'];
        $resources = $_POST['resources'];
        $lyrics = null; //fix this
        $type = null;
        $user_id = $user->getId();
        $new_project = new Project($id, $title, $description, $genre, $resources, $lyrics, $type, $user_id);
        $new_project->save();
        $projects = $user->getOwnerProjects();
        var_dump($projects);
        return $app['twig']->render('private_profile.html.twig', array('user' => $user, 'projects' => $projects));
    });

    // Initial routing for returning to profile
    $app->post("/user/{id}", function($id) use ($app) {
        session_start();
        $user = User::find($id);
        $user_projects = $user->getProjects();
        return $app['twig']->render('profile.html.twig', array('user' => $user, 'projects' => $user_projects));
      });


    // Sign in from index
    $app->post("/sign_in", function() use ($app) {
        $inputted_username = $_POST['username'];
        $inputted_password = $_POST['password'];
        $user =  User::verifyLogin($inputted_username, $inputted_password);

            if($user != null && $user->getUsername() == $inputted_username && $user->getPassword() == $inputted_password)
            {
                $found_user = $user;
                $user_projects = $found_user->getOwnerProjects();
                session_start();
                $_SESSION['user_id'] = $user->getId();
                $session_status = $_SESSION['user_id'];
                return $app['twig']->render('private_profile.html.twig', array('user' => $found_user, 'projects' => $user_projects));

            } else {
                $error = "The username and password do not match!";
                return $app['twig']->render('index.html.twig', array('error' => $error));
            }
    });

    // Edit a specific user and return their profile page
    $app->patch("/user/{id}/edit_profile", function($id) use ($app){
        session_start();
        $user = User::find($id);
        $new_first_name = $_POST['new_first_name'];
        $new_last_name = $_POST['new_last_name'];
        $new_email = null;
        $new_username = $_POST['new_username'];
        $new_bio = $_POST['new_bio'];
        $new_photo = $_POST['new_photo'];
        $new_password = $_POST['new_password'];
        $user->update($new_first_name, $new_last_name, $new_email, $new_username, $new_bio, $new_photo, $new_password);
        return $app['twig']->render('private_profile.html.twig', array('user' => $user, 'projects' => $user->getOwnerProjects()));
    });

    // Get page where user can edit their project
    $app->get("/user/{id}/edit_project", function($id) use ($app){
        session_start();
        $user = User::find($id);
        $project = $user->getProjects($user->getId());
        return $app['twig']->render('edit_project.html.twig', array('user' => $user));
    });

    // Edit project and returns user to their profile page
    $app->patch("/user/{id}/edit_project", function($id) use ($app){
        session_start();
        $project = $user->getProjects($user->getId());
        $new_title = $_POST['new_title'];
        $new_description = $_POST['new_description'];
        $new_genre = $_POST['new_genre'];
        $new_resources = $_POST['new_resources'];
        $new_lyrics = $_POST['new_lyrics'];
        $new_type = $_POST['new_type'];
        $project->update($new_title, $new_description, $new_genre, $new_resources, $new_lyrics, $new_type);
        return $app['twig']->render('profile.html.twig', array('user' => $user, 'projects' => $user_projects));
    });

    // Get page (from edit modal) to delete specific user
	$app->get("/user/{id}/delete", function($id) use ($app) {
        session_start();
		$user = User::find($id);
		return $app['twig']->render('delete_user.html.twig', array(
			'user' => $user));
	});

    // Delete specific user; homepage rendered
	$app->delete("/user/{id}/delete", function($id) use ($app) {
        session_start();
        $_SESSION['user_id'] = null;
        $session_status = $_SESSION['user_id'];
        $user = User::find($id);
        $user->delete();
        $error = "";
        return $app['twig']->render('index.html.twig', array('users' => User::getAll(), 'error' => $error, 'session' => $session_status));
    });

    // User Logs out of their session; homepage rendered
    $app->get("/log_out", function() use ($app) {
        session_start();
        $_SESSION['user_id'] = null;
        $session_status = $_SESSION['user_id'];
        $users = User::getAll();
        $error = "";
        return $app['twig']->render('index.html.twig', array('user' => $users, 'error' => $error, 'session' => $session_status));
    });

    return $app;
?>
