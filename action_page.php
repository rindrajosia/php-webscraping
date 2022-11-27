<?php

require './vendor/autoload.php';
require './Csv.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;


$algolia = new Client([
    'headers' => [ 'Content-Type' => 'application/json' ]
]);

$coursera = new Client([
    'base_uri' => 'https://www.coursera.org/'
]);

$promises = [];

$all_products_list = [];

function input_cleaner($input) {
  $input = trim($input);
  $input = stripslashes($input);
  $input = htmlspecialchars($input);
  return $input;
}



if(!empty(input_cleaner($_POST["fname"]))) {
  $courses = preg_replace('/ /i', '-', $_POST["fname"]);


  $param = new stdClass;
  $param->indexName = "prod_all_launched_products_term_optimization";
  $param->params = "query={$courses}&hitsPerPage=3000&maxValuesPerFacet=3000&page=0&highlightPreTag=%3Cais-highlight-0000000000%3E&highlightPostTag=%3C%2Fais-highlight-0000000000%3E&clickAnalytics=true&facets=%5B%22isCreditEligible%22%2C%22topic%22%2C%22skills%22%2C%22productDifficultyLevel%22%2C%22productDurationEnum%22%2C%22entityTypeDescription%22%2C%22partners%22%2C%22allLanguages%22%5D&tagFilters=";


  try {
    echo '<h1>If CSV file is ready, Please click on the download link. If not, Please wait during fetching process</h1>';


    $response = $algolia->post("https://lua9b20g37-3.algolianet.com/1/indexes/*/queries?x-algolia-agent=Algolia%20for%20vanilla%20JavaScript%20(lite)%203.30.0%3Breact-instantsearch%205.2.3%3BJS%20Helper%202.26.1&x-algolia-application-id=LUA9B20G37&x-algolia-api-key=dcc55281ffd7ba6f24c3a9b18288499b",
      ['body' => json_encode(
          [
              "requests" => [$param]
          ]
      )]
    );


  $all_products_list = ((((json_decode($response->getBody()->getContents(), true))["results"])[0])["hits"]);



    foreach ($all_products_list as $key => $value) {


      $course_type = explode("~",$value["objectID"]);

      $objectUrlArray = explode("/",$value["objectUrl"]);

      $slug = $objectUrlArray[2];

      if( $objectUrlArray[1] !== "certificates"
      && $objectUrlArray[1] !== "degrees" && $objectUrlArray[1] !== "learn"
      && $objectUrlArray[1] !== "projects" && $objectUrlArray[1] !== "mastertrack") {

          if($course_type[0] == "s12n") {
            $method = "onDemandSpecializations";
          }

          if($course_type[0] == "courses") {
            $method = "onDemandCourses";
          }


          $response = $coursera->request('GET', "api/{$method}.v1?q=slug&slug={$slug}&fields=courseIds,id");

          $details = ((json_decode($response->getBody()->getContents(), true))['elements'])[0];

          $datas[]= array(
            'Course Name' => $value["name"],
            'Course provider' =>  $value["partners"][0],
            'Description' =>  $details["description"],
            'Students enrolled' =>  $value["enrollments"],
            'Ratings' =>  $value["avgProductRating"]
          );
      }else {
        $datas[]= array(
          'Course Name' => $value["name"],
          'Course provider' =>  $value["partners"][0],
          'Description' =>  (($value["_snippetResult"])["description"])["value"],
          'Students enrolled' =>  (is_null($value["enrollments"])? 0 : $value["enrollments"]),
          'Ratings' =>  (is_null($value["avgProductRating"])? 0 : $value["avgProductRating"])
        );
      }


    }

    CSV::export($datas, 'coursera');

    echo '<a href="coursera.csv" download>download csv</a>';


  } catch (ClientException $e) {
      echo Psr7\Message::toString($e->getRequest());
      echo Psr7\Message::toString($e->getResponse());
  }

} else {
  echo '<a href="javascript://" onclick="history.back();">Please make sure that course is not empty</a>';
}
