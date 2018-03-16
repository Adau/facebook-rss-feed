<?php

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Suin\RSSWriter\Channel;
use Suin\RSSWriter\Feed;
use Suin\RSSWriter\Item;

require_once __DIR__ . '/vendor/autoload.php';
$config = parse_ini_file('config.ini');

$fb = new Facebook([
  'app_id' => $config['facebook_app_id'],
  'app_secret' => $config['facebook_app_secret'],
  'default_graph_version' => 'v2.10',
  'default_access_token' => $config['facebook_default_access_token'],
]);

try {
    // https://developers.facebook.com/tools/explorer/145634995501895/?method=GET&path=villeAnnecy%3Ffields%3Dfeed%7Bmessage%2Ccreated_time%2Cid%2Cdescription%2Ctype%2Cfull_picture%2Clink%2Cobject_id%7D&version=v2.12

    $response = $fb->get(
        sprintf(
            '/%s?fields=%s{%s}',
            $_GET['page'],
            implode(',', array('name', 'about', 'link', 'feed')),
            implode(',', array('id', 'created_time', 'message', 'description', 'picture,link'))
        )
    );
} catch (FacebookResponseException $e) {
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch (FacebookSDKException $e) {
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}

$page = $response->getDecodedBody();

$feed = new Feed();

$channel = new Channel();
$channel
    ->title($page['name'])
    ->description($page['about'])
    ->url($page['link'])
    ->language('fr-FR')
    ->ttl(60)
    ->appendTo($feed);

foreach ($page['feed']['data'] as $post) {
    $description = '';
    if (isset($post['picture'])) {
        $description .= '<p><img src="'. $post['picture'] .'" alt="picture"></p>';
    }
    if (isset($post['description'])) {
        $description .= '<p>'. $post['description'] .'</p>';
    } else {
        $description .= '<p>'. $post['message'] .'</p>';
    }
    if (isset($post['link'])) {
        $description .= '<p><a href="'. $post['link'] .'">'. $post['link'] .'</a></p>';
    }

    $item = new Item();
    $item
        ->title($post['message'])
        ->description($description)
        ->contentEncoded($post['message'])
        ->url('https://www.facebook.com/' . $post['id'])
        ->pubDate(strtotime($post['created_time']))
        ->guid($post['id'], true)
        ->preferCdata(true)
        ->appendTo($channel);
}

echo $feed->render();
