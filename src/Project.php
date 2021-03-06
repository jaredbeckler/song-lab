<?php
    class Project
    {
        private $id;
        private $title;
        private $description;
        private $genre;
        private $resources;
        private $lyrics;
        private $type;
        private $user_id;

        function __construct($id = null, $title, $description, $genre, $resources, $lyrics, $type, $user_id = null)
        {
            $this->id = $id;
            $this->title = $title;
            $this->description = $description;
            $this->genre = $genre;
            $this->resources = $resources;
            $this->lyrics = $lyrics;
            $this->type = $type;
            $this->user_id = $user_id;
        }
// SETTERS
        function setTitle($new_title)
        {
            $this->title = $new_title;
        }

        function setDescription($new_description)
        {
            $this->description = $new_description;
        }

        function setGenre($new_genre)
        {
            $this->genre = $new_genre;
        }

        function setResources($new_resources)
        {
            $this->resources = $new_resources;
        }

        function setLyrics($new_lyrics)
        {
            $this->lyrics = $new_lyrics;
        }

        function setType($new_type)
        {
            $this->type = $new_type;
        }
// GETTERS
        function getId()
        {
            return $this->id;
        }

        function getTitle()
        {
            return $this->title;
        }

        function getDescription()
        {
            return $this->description;
        }

        function getGenre()
        {
            return $this->genre;
        }

        function getResources()
        {
            return $this->resources;
        }

        function getLyrics()
        {
            return $this->lyrics;
        }

        function getType()
        {
            return $this->type;
        }

        function getUserId()
        {
            return $this->user_id;
        }

        function save()
        {
            $GLOBALS['DB']->exec("INSERT INTO projects (title, description, genre, resources, lyrics, type, user_id) VALUES ('{$this->getTitle()}', '{$this->getDescription()}', '{$this->getGenre()}', '{$this->getResources()}', '{$this->getLyrics()}', '{$this->getType()}', {$this->getUserId()});");
            $this->id = $GLOBALS['DB']->lastInsertId();
        }

        static function getAll()
        {
            $returned_projects = $GLOBALS['DB']->query("SELECT * FROM projects ORDER BY id DESC");
            $projects = array();

            foreach($returned_projects as $project)
            {
                $id = $project['id'];
                $title = $project['title'];
                $description = $project['description'];
                $genre = $project['genre'];
                $resources = $project['resources'];
                $lyrics = $project['lyrics'];
                $type = $project['type'];
                $user_id = $project['user_id'];
                $new_project = new Project($id, $title, $description, $genre, $resources, $lyrics, $type, $user_id);
                array_push($projects, $new_project);
            }
            return $projects;
        }

        static function find($search_id)
        {
            $found_project = null;
            $returned_projects = Project::getAll();

            foreach($returned_projects as $project)
            {
                $project_id = $project->getId();
                if ($project_id == $search_id)
                {
                    $found_project = $project;
                }
            }
            return $found_project;
        }

        static function search($keyword)
        {
          $query = $GLOBALS['DB']->query("SELECT * FROM projects WHERE genre LIKE '%{$keyword}%' OR description LIKE '%{$keyword}%' OR title LIKE '%{$keyword}%'");
          $project_matches = $query->fetchAll(PDO::FETCH_ASSOC);
          $projects = array();

            foreach($project_matches as $project){
                $id = $project['id'];
                $title = $project['title'];
                $description = $project['description'];
                $genre = $project['genre'];
                $resources = $project['resources'];
                $lyrics = $project['lyrics'];
                $type = $project['type'];
                $user_id = $project['user_id'];
                $new_project = new Project($id, $title, $description, $genre, $resources, $lyrics, $type, $user_id);
                array_push($projects, $new_project);
            }
            return $projects;
        }

        function delete()
        {
            $GLOBALS['DB']->exec("DELETE FROM projects WHERE id = {$this->getId()}");
        }

        function update($new_title, $new_description, $new_genre, $new_resources, $new_lyrics, $new_type)
        {
            $GLOBALS['DB']->exec("UPDATE projects SET title = '{$new_title}', description = '{$new_description}', genre = '{$new_genre}', resources = '{$new_resources}', lyrics = '{$new_lyrics}', type = '{$new_type}' WHERE id = {$this->getId()};");
            $this->setTitle($new_title);
            $this->setDescription($new_description);
            $this->setGenre($new_genre);
            $this->setResources($new_resources);
            $this->setLyrics($new_lyrics);
            $this->setType($new_type);
        }

        static function deleteAll()
        {
            $GLOBALS['DB']->exec("DELETE FROM projects");
        }

        function getProjectOwner()
        {
            $returned_owners = $GLOBALS['DB']->query("SELECT * FROM users WHERE id = {$this->getUserId()}");
            $owners = array();

                foreach($returned_owners as $owner) {
                    $id = $owner['id'];
                    $first_name = $owner['first_name'];
                    $last_name = $owner['last_name'];
                    $email= $owner['email'];
                    $username= $owner['username'];
                    $bio= $owner['bio'];
                    $photo= $owner['photo'];
                    $password = $owner['password'];
                    $found_match = User::find($id);
                }
            return $found_match;
        }

        function addCollaborator($user)
        {
            $GLOBALS['DB']->exec("INSERT INTO collaborations (project_id, user_id) VALUES ({$this->getId()}, {$user->getId()});");
        }

        function getCollaborators()
        {
            $returned_users = $GLOBALS['DB']->query("SELECT users.* FROM projects JOIN collaborations ON (collaborations.project_id = projects.id) JOIN users ON (users.id = collaborations.user_id) WHERE projects.id = {$this->getId()};");

            $users = array();
            foreach($returned_users as $user)
            {
                $id = $user['id'];
                $first_name = $user['first_name'];
                $last_name = $user['last_name'];
                $email = $user['email'];
                $username = $user['username'];
                $bio = $user['bio'];
                $photo = $user['photo'];
                $password = $user['password'];
                $new_user = new User($id, $first_name, $last_name, $email, $username, $bio, $photo, $password);
                array_push($users, $new_user);
            }
            return $users;
        }
    }
?>
