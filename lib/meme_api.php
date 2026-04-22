<?php
require_once(__DIR__ . "/api_helper.php");
require_once(__DIR__ . "/load_api_keys.php");

function fetch_memes($count = 10)
{
    $data = ["count" => $count];
    $endpoint = "https://reddit-meme.p.rapidapi.com/memes/top";
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

    foreach ($memes as $meme) {
        if (!is_array($meme)) {
            continue;
        }

        $data = [
            "title" => $meme["title"] ?? "",
            "post_link" => $meme["postLink"] ?? "",
            "image_url" => $meme["url"] ?? "",
            "author" => $meme["author"] ?? "",
            "subreddit" => $meme["subreddit"] ?? "",
            "is_nsfw" => !empty($meme["nsfw"]) ? 1 : 0,
            "spoiler" => !empty($meme["spoiler"]) ? 1 : 0,
            "is_api" => 1
        ];

        if (empty($data["title"]) || empty($data["image_url"])) {
            continue;
        }

        $transformedResult[] = $data;
    }

    error_log("Transformed Meme API Result: " . var_export($transformedResult, true));
    return $transformedResult;
}

function fetch_one_meme()
{
    $memes = fetch_memes(1);
    if (count($memes) > 0) {
        return $memes[0];
    }
    return [];
}