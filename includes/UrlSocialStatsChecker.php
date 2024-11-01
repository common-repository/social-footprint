<?php
/**
 * Allows retrive sharing stats for specefied url/page.
 *
 * @author Matthew Barby
 * @version  1.0 2015-10-06
 */
class UrlSocialStatsChecker
{
	public $timeout = 10;

	public function get_tweeter_stats($url, $encoded = false)
	{
		$url = $this->encode_url($url, $encoded);
		$json_string = $this->file_get_contents_curl('http://urls.api.twitter.com/1/urls/count.json?url=' . $url);
		$json = json_decode($json_string, true);
		return isset($json['count'])?intval($json['count']):0;
	}

	public function get_fb_stats($url, $encoded = false)
	{
		$url = $this->encode_url($url, $encoded);
		$json_string = $this->file_get_contents_curl('http://api.facebook.com/restserver.php?method=links.getStats&format=json&urls='.$url);
		$json = json_decode($json_string, true);
		return isset($json[0]['share_count']) ? intval($json[0]['share_count']):0;
	}

	public function get_google_stats($url, $encoded = false)
	{
		$url = $this->encode_url($url, $encoded);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"'.rawurldecode($url).'","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		$curl_results = curl_exec ($curl);
		if(curl_error($ch)) {
			throw Exception('Curl error: ' . curl_error($ch));
		}
		curl_close ($curl);

		$json = json_decode($curl_results, true);

		return isset($json[0]['result']['metadata']['globalCounts']['count']) ? intval( $json[0]['result']['metadata']['globalCounts']['count'] ) : 0;
	}


	public function get_piterest_stats($url, $encoded = false)
	{
		$url = $this->encode_url($url, $encoded);
		$return_data = $this->file_get_contents_curl('http://api.pinterest.com/v1/urls/count.json?url='.$url);
		//https://api.pinterest.com/v1/urls/count.json?callback=jsonp&url=
		$json_string = preg_replace('/^receiveCount\((.*)\)$/', "\\1", $return_data);
		$json = json_decode($json_string, true);
		return isset($json['count'])?intval($json['count']):0;
	}

	public function get_reddit_stats($url, $encoded = false, $score_only = false)
	{
		$url = $this->encode_url($url, $encoded);
		$json_string = $this->file_get_contents_curl('http://www.reddit.com/api/info.json?url='.$url);

		$json = json_decode($json_string, true);
		if (!isset($json['data']['children'])) {
			return 0;
		}

		$items = $json['data']['children'];
		$data = array(
			'score' => 0,
			'downs' => 0,
			'ups' => 0,
		);

		foreach($items as $item) {
			if (isset($item['kind']) && 't3' == $item['kind']) {
				$data['score'] += $item['data']['score'];
				if (!$score_only) {
					$data['downs'] += $item['data']['downs'];
					$data['ups'] += $item['data']['ups'];
				}
			}
		}

		if ($score_only) {
			return $data['score'];
		}
		return $data['score'] + $data['ups'] - $data['downs'];
	}

	public function get_linkedin_stats($url, $encoded = false)
	{
		$url = $this->encode_url($url, $encoded);
		$json_string = $this->file_get_contents_curl('http://www.linkedin.com/countserv/count/share?url=' . $url .'&format=json');
		$json = json_decode($json_string, true);
		return isset($json['count'])?intval($json['count']):0;
	}

	public function get_stumbleUpon_stats($url, $encoded = false)
	{
		$url = $this->encode_url($url, $encoded);
		$json_string = $this->file_get_contents_curl('http://www.stumbleupon.com/services/1.01/badge.getinfo?url='.$url);
		$json = json_decode($json_string, true);
		return isset($json['result']['views'])?intval($json['result']['views']):0;
	}

	public function encode_url($url, $encoded = false)
	{
		if (!$encoded) {
			$url = rawurlencode($url);
		}
		return $url;
	}

	private function file_get_contents_curl($url)
	{
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$cont = curl_exec($ch);
		if(curl_error($ch)) {
			throw Exception('Curl error: ' . curl_error($ch));
		}
		curl_close ($ch);
		return $cont;
	}
}
