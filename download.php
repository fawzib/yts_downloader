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
echo "Movie title stop: " . $config['yts_movie_title_limit'].PHP_EOL;
echo "Max page limit: " . $config['yts_page_limit'].PHP_EOL.PHP_EOL;



$new_movie_stop = ['',''];

for($page=0; $page < $config['yts_page_limit']; $page++) {

    $page_extra = ($page==0) ? "" : "?page=".$page;
    echo "Fetching page: ".($page+1).PHP_EOL;
    $page_body = $client->get($latest_url.$page_extra)->getBody()->getContents();


    $crawler = new Crawler($page_body);

    $crawler->filter('.browse-movie-bottom')->each(function ($box, $i) {
		
		
		//.browse-movie-wrap 
        global $client;
        global $config;
        global $page;
        global $new_movie_stop;

        try {
            $movie_details = array();
            //assume last link is the highest quality
			$movie_details['movie_year'] = trim($box->filter('.browse-movie-year')->text());
            $movie_details['download_url'] = trim(strip_tags($box->filter(".browse-movie-tags a")->last()->attr("href")));
			$movie_details['download_title'] = trim(strip_tags($box->filter(".browse-movie-tags a")->last()->attr("title")));
            $movie_details['download_title'] = substr($movie_details['download_title'], 9);
            $movie_details['download_title'] = substr($movie_details['download_title'], 0, strlen($movie_details['download_title'])-8);
            $movie_details['movie_title'] = substr($movie_details['download_title'], 0, strrpos($movie_details['download_title'], ' '));
            $movie_details['download_file'] = Funcs::safe_filename($movie_details['download_title']." (". $movie_details['movie_year'] .").torrent");

            if(stripos($movie_details['movie_title'], $config['yts_movie_title_limit']) !== false) {
                echo PHP_EOL."*******".PHP_EOL."New movie stops: '".$new_movie_stop[0]."' & '".$new_movie_stop[1]."'".PHP_EOL;
                die("Found movie limit: ".$config['yts_movie_title_limit'].PHP_EOL.PHP_EOL);
            }
            else if(stripos($movie_details['movie_title'], $config['yts_movie_title_limit_2']) !== false) {
                //alternative title
                echo PHP_EOL."*******".PHP_EOL."New movie stops: '".$new_movie_stop[0]."' & '".$new_movie_stop[1]."'".PHP_EOL;
                die("Found movie limit: ".$config['yts_movie_title_limit_2'].PHP_EOL.PHP_EOL);
            }



            //echo "Downloading: ".$movie_details['download_file'].PHP_EOL;
			echo "Downloading: ".$movie_details['movie_title'].PHP_EOL;
 
            $client->get($movie_details['download_url'], ['sink' => "./downloads/".$movie_details['download_file']]);

            if($page == 0 && ($i == 0 || $i == 1)) {
                $new_movie_stop[$i] = $movie_details['movie_title'];
            }

        }catch (Exception $ex) {
            echo "Error downloading: ".$movie_details['download_url'].PHP_EOL.PHP_EOL;
        }

    });
}
echo "*** Page limit reached ***".PHP_EOL;