<?php
namespace App\Classes;
use App\Models\Admin\APIKeys;
use App\Models\Admin\General;

class Twitter
{

    function find_id($url)
    {
        $domain = str_ireplace("www.", "", parse_url($url, PHP_URL_HOST));
        $last_char = substr($url, -1);
        if ($last_char == "/") {
            $url = substr($url, 0, -1);
        }
        switch ($domain) {
            case "twitter.com":
                $arr = explode("/", $url);
                return end($arr);
                break;
            case "mobile.twitter.com":
                $arr = explode("/", $url);
                return end($arr);
                break;
            default:
                $arr = explode("/", $url);
                return end($arr);
                break;
        }
    }

    function download($url)
    {   

        include app_path() . '/Classes/TwitterAPIExchange.php';

        $api_key                                 = APIKeys::findOrFail(1);

        if ( !empty($api_key->twitter_oauth_access_token) && !empty($api_key->twitter_oauth_access_token_secret) && !empty($api_key->twitter_consumer_key) && !empty($api_key->twitter_consumer_secret) ) {
            
            $settings = array(
                'oauth_access_token'        => $api_key->twitter_oauth_access_token,
                'oauth_access_token_secret' => $api_key->twitter_oauth_access_token_secret,
                'consumer_key'              => $api_key->twitter_consumer_key,
                'consumer_secret'           => $api_key->twitter_consumer_secret
            );

            $api_url = 'https://api.twitter.com/1.1/statuses/show.json';
            
            $getfield = '?id=' . $this->find_id($url) . '&tweet_mode=extended';

            $requestMethod = 'GET';

            $twitter = new TwitterAPIExchange($settings);
            
            $response = $twitter->setGetfield($getfield)->buildOauth($api_url, $requestMethod)->performRequest();
            
            $deJson = json_decode($response);

            if( !empty($deJson->extended_entities->media[0]->video_info->variants) )
            {
                $data['title'] = $deJson->full_text;

                $thumbnail = file_get_contents( $deJson->extended_entities->media[0]->media_url_https );

                $dataBase64 = 'data:image/jpeg;base64,' . base64_encode($thumbnail);

                $data['thumbnail'] = $dataBase64;

                $data['duration'] = format_seconds( $deJson->extended_entities->media[0]->video_info->duration_millis / 1000);

                $data['source'] = "Twitter";

                $media = $deJson->extended_entities->media[0]->video_info->variants;

                $data['links'] = array();

                $i = 0;

                foreach ($media as $key => $value) 
                {

                    switch ($media[$key]->content_type) 
                    {
                        case 'application/x-mpegURL':

                            $data['links'][$i]['quality'] = 'm3u8';

                            $data['links'][$i]['type'] = 'm3u8';

                        break;

                        case 'application/dash+xml':

                            $data['links'][$i]['quality'] = 'dash';

                            $data['links'][$i]['type'] = 'dash';

                        break;
                        
                        default:

                            preg_match('/\/vid\/[0-9]*x(.*?)\/.*.mp4/', $media[$key]->url, $matchQuality);

                            $data['links'][$i]['quality'] = $matchQuality[1] . 'p';

                            $data['links'][$i]['type']    = 'mp4';

                        break;
                    }

                    $bytes             = get_file_size( $media[$key]->url );

                    $token['url']      = $media[$key]->url;
                    $token['filename'] = General::orderBy('id', 'DESC')->first()->prefix . sanitize_filename( $deJson->full_text );
                    $token['size']     = $bytes;
                    $token['type']     = 'mp4';
                    $dlLink            = url('/') . '/dl.php?token=' . encode( json_encode($token) );

                    $data['links'][$i]['url']     = $dlLink;

                    $data['links'][$i]['bytes']   = $bytes;
                    
                    $data['links'][$i]['size']    = format_size( $data['links'][$i]['bytes'] );
                    
                    $data['links'][$i]['mute']    = false;

                    $i++;

                }

            } else return;

            usort($data['links'], 'sort_by_quality');

            return $data;

        } else{

            session()->flash('status', 'error');
            session()->flash('message', 'Invalid API Keys and Access Tokens!');
            return;
        }

    }


}