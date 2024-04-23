<?php
namespace CodeWisdoms\DeuOfficeLookup;

use GuzzleHttp\Client;

class DEU
{
    private $client = null;
    private const BASE_URL = 'https://www.dir.ca.gov/asp/DEUzip.ASP';

    public function __construct()
    {
        $this->client = new Client(['base_uri' => self::BASE_URL, 'cookies' => true]);
    }
    public function getByZip(string $zip): ?array
    {
        $response = $this->client->post('', [
            'form_params' => [
                'zip' => $zip,
            ],
        ]);

        $data = [];

        $dom = new \DOMDocument();

        libxml_use_internal_errors(true);
        @$dom->loadHTML($response->getBody()->getContents());
        libxml_clear_errors();
        $ps = $dom->getElementById('main')->getElementsByTagName('p');
        foreach ($ps as $key => $p) {
            if ($key == 0 && stripos($p->nodeValue, 'No records match') !== false) {
                return null;
            }
            switch ($key) {
                case 1:{
                        $data[self::_getColName($key)] = self::_getValueFromDom($p->lastChild->lastChild);
                        break;
                    }
                case 2:{
                        $_address = explode(',', self::_getValueFromDom($p->lastChild->lastChild));
                        $_address = array_map('trim', $_address);
                        $data[self::_getColName($key)] = $_address;
                        break;
                    }
                case 3:{
                        $data[self::_getColName($key)] = preg_replace('#\D+#', '', self::_getValueFromDom($p->lastChild));
                        break;
                    }
            }
        }
        return $data;
    }
    private static function _getColName($number): ?string
    {
        switch ($number) {
            case 1:
                return 'office';
            case 2:
                return 'address';
            case 3:
                return 'phone';
            default:
                return null;
        }
    }
    private static function _getValueFromDom(\DOMNode $col)
    {
        if ($col->hasChildNodes() && $col->lastChild->nodeName != '#text') {
            $child = $col->lastChild;
            return self::_decodeText($child->nodeValue);
        } else {
            return self::_decodeText($col->nodeValue);
        }
    }
    private static function _decodeText(string $text)
    {
        return htmlspecialchars_decode(preg_replace('#\s{2,}#', ', ', str_replace('&nbsp;', '', preg_replace('#\s{2,}#', ', ', trim(htmlentities($text))))));
    }
}
