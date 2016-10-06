<?php

require_once 'api.class.php';

class Post extends API
{
    //since we're not using and database connection,
    //just store the data in a local .json file
    protected $postDataTable = '../datastore/data.json';

    protected $cached_posts = array();

    public function __construct($request, $origin)
    {
        parent::__construct($request);
        //load all posts during the class instantiation,
        //and cached it
        $this->cached_posts = $this->getAllPost();
    }

    public function getPosts()
    {
        $data = $this->cached_posts;

        return $data;
    }

    public function create()
    {
        $requiredParams = array('title', 'content');

        $params = $this->requiredParams($requiredParams);

        if ($this->method != 'POST') {
            throw new Exception("Only POST method is allowed!");
        }

        //generate a random id
        $id = uniqid();
        $new['id'] = $id;

        $createData[$id] = array_merge($new, $params);

        $posts = $this->getAllPost();

        $updatedEntry = json_encode(array_merge($posts, $createData));

        $saved = $this->save($updatedEntry);

        $saved['params'] = $params;

        return $saved;
    }

    public function delete()
    {
        if ($this->method != 'DELETE') {
            throw new Exception("Only DELETE method is allowed!");
        }

        $id = $this->verb;
        unset($this->cached_posts[$id]);

        $updated = json_encode($this->cached_posts);

        $saved = $this->save($updated);

        if ($saved['status']) {
            $saved['message'] = 'Post deleted';
        }

        return $saved;
    }

    public function getPost()
    {
        if ($this->method != 'GET') {
            throw new Exception("Only GET method is allowed!");
        }

        $id = $this->verb;

        return $this->cached_posts[$id];
    }

/**
 * Protected Methods
 */

    private function getAllPost()
    {
        $posts = json_decode(file_get_contents($this->postDataTable), true);

        if (empty($posts)) {
            return array();
        } else {
            return $posts;
        }
    }

    private function save($data)
    {
        $filename = $this->postDataTable;

        $response['status'] = false;
        $response['message'] = null;

        if (is_writable($filename)) {

            if (!$handle = fopen($filename, 'w')) {
                $response['status'] = false;
                $response['message'] = "Unable to open datastore";
            }

            if (fwrite($handle, $data) === false) {
                $response['status'] = false;
                $response['message'] = "Unable to save data to datastore";
            }

            $response['status'] = true;
            $response['message'] = "Data updated";

            fclose($handle);

        } else {
            $response['status'] = false;
            $response['message'] = "Datastore is not writable";
        }

        return $response;
    }
}
