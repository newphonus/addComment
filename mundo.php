<?php

class Post {
    public $id;
    public $title;
    public $content;
    public $author;
    public $created_at;
    public $comments;
    
    public function __construct($id, $title, $content, $author) {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->author = $author;
        $this->created_at = date('Y-m-d H:i:s');
        $this->comments = [];
    }
    
    public function addComment($comment) {
        $this->comments[] = $comment;
    }
}

class Comment {
    public $id;
    public $author;
    public $content;
    public $created_at;
    
    public function __construct($id, $author, $content) {
        $this->id = $id;
        $this->author = $author;
        $this->content = $content;
        $this->created_at = date('Y-m-d H:i:s');
    }
}

class Blog {
    private $posts;
    private $filename;
    
    public function __construct($filename = 'blog.json') {
        $this->filename = $filename;
        $this->posts = $this->loadPosts();
    }
    
    private function loadPosts() {
        if (file_exists($this->filename)) {
            $json = file_get_contents($this->filename);
            $data = json_decode($json, true);
            
            $posts = [];
            foreach ($data as $postData) {
                $post = new Post(
                    $postData['id'],
                    $postData['title'],
                    $postData['content'],
                    $postData['author']
                );
                $post->created_at = $postData['created_at'];
                
                foreach ($postData['comments'] as $commentData) {
                    $comment = new Comment(
                        $commentData['id'],
                        $commentData['author'],
                        $commentData['content']
                    );
                    $comment->created_at = $commentData['created_at'];
                    $post->addComment($comment);
                }
                
                $posts[] = $post;
            }
            
            return $posts;
        }
        return [];
    }
    
    private function savePosts() {
        $data = [];
        foreach ($this->posts as $post) {
            $data[] = [
                'id' => $post->id,
                'title' => $post->title,
                'content' => $post->content,
                'author' => $post->author,
                'created_at' => $post->created_at,
                'comments' => array_map(function($c) {
                    return [
                        'id' => $c->id,
                        'author' => $c->author,
                        'content' => $c->content,
                        'created_at' => $c->created_at
                    ];
                }, $post->comments)
            ];
        }
        
        file_put_contents($this->filename, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function createPost($title, $content, $author) {
        $id = count($this->posts) + 1;
        $post = new Post($id, $title, $content, $author);
        $this->posts[] = $post;
        $this->savePosts();
        return $post;
    }
    
    public function getPost($id) {
        foreach ($this->posts as $post) {
            if ($post->id == $id) {
                return $post;
            }
        }
        return null;
    }
    
    public function getAllPosts() {
        return array_reverse($this->posts);
    }
    
    public function deletePost($id) {
        $this->posts = array_filter($this->posts, function($p) use ($id) {
            return $p->id != $id;
        });
        $this->posts = array_values($this->posts);
        $this->savePosts();
    }
    
    public function addComment($postId, $author, $content) {
        $post = $this->getPost($postId);
        if ($post) {
            $commentId = count($post->comments) + 1;
            $comment = new Comment($commentId, $author, $content);
            $post->addComment($comment);
            $this->savePosts();
            return $comment;
        }
        return null;
    }
    
    public function searchPosts($query) {
        $query = strtolower($query);
        return array_filter($this->posts, function($post) use ($query) {
            return strpos(strtolower($post->title), $query) !== false ||
                   strpos(strtolower($post->content), $query) !== false;
        });
    }
    
    public function exportHTML($filename = 'blog.html') {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>My Blog</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .post { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; }
        .post-title { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .post-meta { color: #666; font-size: 14px; margin-bottom: 15px; }
        .comment { background: #f5f5f5; padding: 10px; margin-top: 10px; }
        .comment-author { font-weight: bold; }
    </style>
</head>
<body>
    <h1>My Blog</h1>';
        
        foreach ($this->getAllPosts() as $post) {
            $html .= '<div class="post">';
            $html .= '<div class="post-title">' . htmlspecialchars($post->title) . '</div>';
            $html .= '<div class="post-meta">By ' . htmlspecialchars($post->author) . ' on ' . $post->created_at . '</div>';
            $html .= '<div class="post-content">' . nl2br(htmlspecialchars($post->content)) . '</div>';
            
            if (!empty($post->comments)) {
                $html .= '<h3>Comments (' . count($post->comments) . ')</h3>';
                foreach ($post->comments as $comment) {
                    $html .= '<div class="comment">';
                    $html .= '<div class="comment-author">' . htmlspecialchars($comment->author) . '</div>';
                    $html .= '<div>' . htmlspecialchars($comment->content) . '</div>';
                    $html .= '<div style="font-size: 12px; color: #999;">' . $comment->created_at . '</div>';
                    $html .= '</div>';
                }
            }
            
            $html .= '</div>';
        }
        
        $html .= '</body></html>';
        
        file_put_contents($filename, $html);
        echo "Blog exported to $filename\n";
    }
}

function readLine($prompt) {
    echo $prompt;
    return trim(fgets(STDIN));
}

function main() {
    $blog = new Blog();
    
    while (true) {
        echo "\n=== Simple Blog ===\n";
        echo "1. Create Post\n";
        echo "2. View All Posts\n";
        echo "3. View Post Details\n";
        echo "4. Add Comment\n";
        echo "5. Delete Post\n";
        echo "6. Search Posts\n";
        echo "7. Export to HTML\n";
        echo "8. Exit\n";
        
        $choice = readLine("\nEnter choice: ");
        
        switch ($choice) {
            case '1':
                $title = readLine("Post title: ");
                $content = readLine("Post content: ");
                $author = readLine("Author name: ");
                $post = $blog->createPost($title, $content, $author);
                echo "Post created with ID: " . $post->id . "\n";
                break;
                
            case '2':
                $posts = $blog->getAllPosts();
                if (empty($posts)) {
                    echo "No posts yet\n";
                } else {
                    foreach ($posts as $post) {
                        echo "\nID: " . $post->id . "\n";
                        echo "Title: " . $post->title . "\n";
                        echo "Author: " . $post->author . "\n";
                        echo "Date: " . $post->created_at . "\n";
                        echo "Comments: " . count($post->comments) . "\n";
                        echo str_repeat("-", 50) . "\n";
                    }
                }
                break;
                
            case '3':
                $id = (int)readLine("Post ID: ");
                $post = $blog->getPost($id);
                if ($post) {
                    echo "\nTitle: " . $post->title . "\n";
                    echo "Author: " . $post->author . "\n";
                    echo "Date: " . $post->created_at . "\n";
                    echo "\nContent:\n" . $post->content . "\n";
                    echo "\nComments (" . count($post->comments) . "):\n";
                    foreach ($post->comments as $comment) {
                        echo "  - " . $comment->author . ": " . $comment->content . "\n";
                        echo "    " . $comment->created_at . "\n";
                    }
                } else {
                    echo "Post not found\n";
                }
                break;
                
            case '4':
                $postId = (int)readLine("Post ID: ");
                $author = readLine("Your name: ");
                $content = readLine("Comment: ");
                $comment = $blog->addComment($postId, $author, $content);
                if ($comment) {
                    echo "Comment added\n";
                } else {
                    echo "Post not found\n";
                }
                break;
                
            case '5':
                $id = (int)readLine("Post ID to delete: ");
                $blog->deletePost($id);
                echo "Post deleted\n";
                break;
                
            case '6':
                $query = readLine("Search query: ");
                $results = $blog->searchPosts($query);
                if (empty($results)) {
                    echo "No posts found\n";
                } else {
                    foreach ($results as $post) {
                        echo "ID: " . $post->id . " - " . $post->title . "\n";
                    }
                }
                break;
                
            case '7':
                $blog->exportHTML();
                break;
                
            case '8':
                exit(0);
                
            default:
                echo "Invalid choice\n";
        }
    }
}

main();
