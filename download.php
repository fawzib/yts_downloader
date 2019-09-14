<?php


require_once "config.php";

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\CssSelector;
use Symfony\Component\DomCrawler\Crawler;



if(!file_exists("config.php")){
    die("config.php does not exist. Please rename config.php.init and configure it correctly");
}
if(filter_var($config['yts_url'], FILTER_VALIDATE_URL) === false) {
    die("Invalid url! Please fill yts_url inside config.php file.");
}
if(strlen($config['yts_username']) < 3 || strlen($config['yts_password']) < 3) {
    die("Please fill username and password inside config.php file");
}

// login
$login_url = $config['yts_url']."ajax/login";

$client = new Client(['cookies' => true]);

$response = $client->post($login_url, [
        'form_params' => [
            'username' => $config['yts_username'],
            'password' => $config['yts_password'],
        ]
    ]
);

if($response->getStatusCode() != "200"){
    var_dump($response);
    die('*** Login Failed ***');
}

$latest_url = $config['yts_url']."browse-movies"; //?page=2



// loop through page limit
for($i=0; $i < $config['yts_page_limit']; $i++) {
    $page_extra = ($i==0) ? "" : "?page=".$i;
    $page_body = $client->get($latest_url.$page_extra)->getBody()->getContents();


    $crawler = new Crawler($page_body);

    $crawler->filter('.browse-movie-wrap .browse-movie-tags')->each(function ($td, $i) {
        global $client;
        global $config;

        try {
            $movie_details = array();
            //assume last link is the highest quality
            $movie_details['download_url'] = trim(strip_tags($td->filter("a")->last()->attr("href")));
            $movie_details['download_title'] = trim(strip_tags($td->filter("a")->last()->attr("title")));
            $movie_details['download_title'] = substr($movie_details['download_title'], 9);
            $movie_details['download_title'] = substr($movie_details['download_title'], 0, strlen($movie_details['download_title'])-8);
            $movie_details['movie_title'] = substr($movie_details['download_title'], 0, strrpos($movie_details['download_title'], ' '));
            $movie_details['download_file'] = Funcs::safe_filename($movie_details['download_title'].".torrent");


            if(strtolower($movie_details['movie_title']) == strtolower($config['yts_movie_title_limit'])) {
                die("Found movie limit: ".$config['yts_movie_title_limit']."\n\n");
            }

            echo "Downloading: ".$movie_details['download_file'].PHP_EOL;
            $client->get($movie_details['download_url'], ['sink' => "./downloads/".$movie_details['download_file']]);

        }catch (Exception $ex) {
            echo "Error finding download url ".$i.PHP_EOL;
        }

    });
}
echo "*** Page limit reached ***".PHP_EOL;