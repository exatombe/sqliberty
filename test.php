<?php

require __DIR__ . '/vendor/autoload.php';

use Sqliberty\Database;
use Sqliberty\Schema;

$db = new Database("localhost", "test_db", "root", "");

$users = $db->model("users", function (Schema $table) {
    $table->int("id");
    $table->varchar("name");
    $table->varchar("email");
    $table->varchar("password");
    $table->datetime("created_at")->nullable();
    $table->datetime("updated_at")->nullable();
    $table->model("posts", function (Schema $post) {
        $post->int("id");
        $post->varchar("title");
        $post->varchar("content");
        $post->datetime("created_at")->nullable();
        $post->datetime("updated_at")->nullable();
        $post->belongTo("users");
        $post->model("comments", function (Schema $comment) {
            $comment->int("id");
            $comment->varchar("content");
            $comment->datetime("created_at")->nullable();
            $comment->datetime("updated_at")->nullable();
            $comment->belongTo("posts");
            return $comment;
        });
        return $post;
    });
    return $table;
});

$users->create([
    "name" => "John Doe",
    "email" => "test@maiL.fr",
    "password" => "password",
    "created_at" => date("Y-m-d H:i:s"),
    "updated_at" => date("Y-m-d H:i:s"),
    "posts" => [
        [
            "title" => "Post special",
            "content" => "Content 1",
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
            "comments" => [
                [
                    "content" => "Comment 1",
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                ],
                [
                    "content" => "Comment 2",
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                ]
            ]
        ],
        [
            "title" => "Post 2",
            "content" => "Content 2",
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
            "comments" => [
                [
                    "content" => "Comment 3",
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                ],
                [
                    "content" => "Comment magique",
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                ]
            ]
        ]
    ]
]);

$founds = $users->findAll();

if ($founds) {
    echo "Users found: " . count($founds) . PHP_EOL;
} else {
    echo "Users not found" . $users->error . PHP_EOL;
}

$found = $users->get(1);

if ($found) {
    echo "User found: " . $found["name"] . PHP_EOL;
} else {
    echo "User not found" . $users->error . PHP_EOL;
}
