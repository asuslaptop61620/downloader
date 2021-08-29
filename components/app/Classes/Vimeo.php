<?php
namespace App\Classes;
use App\Models\Admin\General;

class Vimeo
{

    function download($url)
    {
		preg_match('%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)(?:[?]?.*)$%im', $url, $vmID);

		$ranServer = rand(1,15);

		$web_page = url_post_contents('https://us'.$ranServer.'.proxysite.com/includes/process.php?action=update', 'server-option=us'.$ranServer.'&d='.urlencode('https://player.vimeo.com/video/'.$vmID[3]).'');
		
        if (preg_match_all('/window.vimeo.clip_page_config.player\s*=\s*({.+?})\s*;\s*\n/', $web_page, $match)) {

            $config_url = json_decode($match[1][0], true)["config_url"];

            $result = json_decode(url_get_contents($config_url), true);

        } else {

            $result = json_decode(get_string_between($web_page, "var config = ", "; if (!config.request)"), true);
        }
        if (empty($result)) {

            return false;

        }

        $video['title'] = $result['video']['title'];

        $video['source'] = 'Vimeo';

        $video['duration'] = format_seconds($result['video']['duration']);

        $thumbnail = file_get_contents( reset($result['video']['thumbs']) );

        $dataBase64 = 'data:image/jpeg;base64,' . base64_encode($thumbnail);

        $video['thumbnail'] = $dataBase64;

        $i = 0;

        foreach ($result['request']['files']['progressive'] as $current) {

            $token['url']      = $current['url'];
            $token['filename'] = General::orderBy('id', 'DESC')->first()->prefix . sanitize_filename($result['video']['title']);
            $token['size']     = $this->get_file_size( $current['url'] );
            $token['type']     = 'mp4';
            $dlLink = url('/') . '/dl.php?token=' . encode( json_encode($token) );

            $video['links'][$i]['url']     = $dlLink;

            $video['links'][$i]['type']    = 'mp4';

            $video['links'][$i]['bytes']   = $this->get_file_size( $current['url'] );

            $video['links'][$i]['size']    = format_size($video['links'][$i]['bytes']);

            $video['links'][$i]['quality'] = $current['quality'];

            $video['links'][$i]['mute']    = false;

            $i++;
        }

        usort($video['links'], 'sort_by_quality');

        return $video;
    }

    function get_file_size($url, $format = false) {
        $result = -1;
        // Issue a HEAD request and follow any redirects.
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.3');
        
        $headers = curl_exec($curl);
        if (curl_errno($curl) == 0) {
            $result = (int)curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        }
        curl_close($curl);
        if ($result > 100) {
            switch ($format) {
                case true:
                    return format_size($result);
                    break;
                case false:
                    return $result;
                    break;
                default:
                    return format_size($result);
                    break;
            }
        } else {
            return "";
        }
    }

}