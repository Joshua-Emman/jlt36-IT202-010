<?php
require_once(__DIR__ . "/api_helper.php");
require_once(__DIR__ . "/load_api_keys.php");

// UCID: jlt36
// Date: 04/22/2026
// Fetches meme data from the meme API, transforms it to match the database schema,
// and returns either a list of memes or a single meme record ready for insertion.

function fetch_memes($count = 10)
{
    $data = ["count" => $count];
    $endpoint = "https://reddit-meme.p.rapidapi.com/memes";
    $isRapidAPI = true;
    $rapidAPIHost = "reddit-meme.p.rapidapi.com";
    $result = get($endpoint, "MEMEAPI_KEY", $data, $isRapidAPI, $rapidAPIHost);

    error_log("Raw Meme API Response: " . var_export($result, true));

    if (($result["status"] ?? 400) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
        error_log("Decoded Meme API Response: " . var_export($result, true));
    } else {
        error_log("API request failed or missing response");
        return [];
    }

    $transformedResult = [];

    if (isset($result["memes"]) && is_array($result["memes"])) {
        $memes = $result["memes"];
    } elseif (is_array($result)) {
        $memes = $result;
    } else {
        $memes = [];
    }

// UCID: jlt36
// Date: 04/22/2026
// Fetches meme data from the meme API and transforms it to match the database schema.

    foreach ($memes as $meme) {
        if (!is_array($meme)) {
            continue;
        }

        $post_link = $meme["postLink"] ?? $meme["post_link"] ?? $meme["permalink"] ?? $meme["link"] ?? "";

        if (!empty($post_link) && str_starts_with($post_link, "/")) {
            $post_link = "https://reddit.com" . $post_link;
        }

        if (empty($post_link)) {
            $subreddit = $meme["subreddit"] ?? "memes";
            $post_link = "https://reddit.com/r/" . $subreddit . "/comments/" . uniqid();
        }

        $image_url = $meme["url"] ?? $meme["image_url"] ?? $meme["image"] ?? "";
        $author = $meme["author"] ?? ($meme["postedBy"] ?? "");

        $data = [
            "title" => trim($meme["title"] ?? ""),
            "post_link" => trim($post_link),
            "image_url" => trim($image_url),
            "author" => trim($author),
            "subreddit" => trim($meme["subreddit"] ?? ""),
            "is_nsfw" => (!empty($meme["nsfw"]) || !empty($meme["is_nsfw"]) || !empty($meme["over_18"])) ? 1 : 0,
            "spoiler" => !empty($meme["spoiler"]) ? 1 : 0,
            "is_api" => 1
        ];

        if (
            empty($data["title"]) ||
            empty($data["post_link"]) ||
            empty($data["image_url"])
        ) {
            continue;
        }

        $transformedResult[] = $data;
    }

    error_log("Transformed Meme API Result: " . var_export($transformedResult, true));
    return $transformedResult;
}

// UCID: jlt36
// Date: 04/22/2026
// Description: Returns one transformed meme record from the API for single-insert operations.
function fetch_one_meme()
{
    $memes = fetch_memes(25);

    if (empty($memes)) {
        return [];
    }

    $db = getDB();

    foreach ($memes as $meme) {
        $query = "SELECT id FROM `IT202-M25-Memes`
                    WHERE title = :title AND image_url = :image_url
                    LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ":title" => $meme["title"],
            ":image_url" => $meme["image_url"]
        ]);
        $existing = $stmt->fetch();

        if (!$existing) {
            return $meme;
        }
    }

    return [];
}