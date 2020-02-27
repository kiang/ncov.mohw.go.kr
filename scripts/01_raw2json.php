<?php
require dirname(__DIR__) . '/vendor/autoload.php';
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

$client = new Client(HttpClient::create(array(
    'headers' => array(
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.5',
        'Referer' => 'http://ncov.mohw.go.kr/',
        'Upgrade-Insecure-Requests' => '1',
        'Save-Data' => 'on',
        'Pragma' => 'no-cache',
        'Cache-Control' => 'no-cache',
    ),
)));
$client->setServerParameter('HTTP_USER_AGENT', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:73.0) Gecko/20100101 Firefox/73.0');
$client->request('GET', 'http://ncov.mohw.go.kr/');

$basePath = dirname(__DIR__);
$json = array();
for($i = 1; $i <= 9; $i++) {
    $rawPageFile = $basePath . '/raw/page/page_' . $i . '.html';
    $client->request('GET', 'http://ncov.mohw.go.kr/bdBoardList.do?brdId=1&brdGubun=12&pageIndex=' . $i);
    file_put_contents($rawPageFile, $client->getResponse()->getContent());
    $rawPage = file_get_contents($rawPageFile);
    $pos = strpos($rawPage, '<div class="onelist open">');
    while(false !== $pos) {
        $data = array();
        $posEnd = strpos($rawPage, '<!--1개 끝//-->', $pos);
        $block = substr($rawPage, $pos, $posEnd - $pos);
        $parts = explode('<!-- db load -->', $block);
        $lines = explode('</li>', $parts[0]);
        foreach($lines AS $line) {
            $cols = explode('</span>', $line);
            foreach($cols AS $k => $v) {
                $cols[$k] = trim(strip_tags($v));
                $cols[$k] = preg_replace('/\s+/', ' ', $cols[$k]);
            }
            $newLine = trim(implode('', $cols));
            $cols = explode(':', $newLine);
            if(count($cols) === 2) {
                foreach($cols AS $k => $v) {
                    $cols[$k] = trim($v);
                }
                $data[$cols[0]] = $cols[1];
            }
        }
        $lines = explode('</li>', $parts[1]);
        foreach($lines AS $k => $line) {
            $lines[$k] = trim(strip_tags($line));
            if(empty($lines[$k])) {
                unset($lines[$k]);
            }
        }
        $data['info_mtxt'] = array_values($lines);
        $json[] = $data;
        $pos = strpos($rawPage, '<div class="onelist open">', $posEnd);
    }
}
file_put_contents($basePath . '/data/cases.json', json_encode($json,  JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));