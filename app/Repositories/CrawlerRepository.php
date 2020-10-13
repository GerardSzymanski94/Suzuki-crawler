<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class CrawlerRepository
{
    public function crawler()
    {
        Artisan::command('build {project}', function ($project) {
            $this->info("Building {$project}!");
        })->describe('Build the project');
        ini_set('max_execution_time', 36000);

        $this->suzuki();

    }

    public function suzuki()
    {
        //$this->suzukiGetImages();
        $years = $this->suzukiGetYears();
        $categories = $this->suzukiGetCategories($years);
        $subcategories = $this->suzukiGetSubcategories($categories);
        $this->suzukiGetInfo($subcategories);
    }

    private function suzukiGetYears()
    {
        $parts = [];
        $url = 'https://www.suzukipartshouse.com/oemparts/c/suzuki_motorcycle/parts';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; pt-BR; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.04 (lucid) Firefox/3.6.18');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);
        $document->loadHTML($result);
        $div_a_class_nodes = $this->getElementsByClass($document, 'a', 'pjq');
        foreach ($div_a_class_nodes as $node) {
            $parts[] = 'https://www.suzukipartshouse.com' . $node->getAttribute('href');
        }
        return $parts;
    }

    private function suzukiGetInfo($subcategories = [])
    {
        $info = [];
        $i = 0;


        $filename = "products.csv";
        $handle = fopen($filename, 'w+');
        fputcsv($handle, array('nazwa grupy', 'numer ref', 'nazwa części', 'numer serii', 'cena'));


        foreach ($subcategories as $key => $category) {
            $url = $category;
            // $url = 'https://www.suzukipartshouse.com/oemparts/a/suz/5e207fc187a866135c5d2ac9/front-caliper';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; pt-BR; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.04 (lucid) Firefox/3.6.18');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            $document = new \DOMDocument('1.0', 'UTF-8');
            $internalErrors = libxml_use_internal_errors(true);
            $document->loadHTML($result);
            $name = $document->getElementById('partheader')->getElementsByTagName('h1')->item(0)->nodeValue;
            $div_a_class_nodes = $this->getElementsByClass($document, 'div', 'partlistrow');
            foreach ($div_a_class_nodes as $node) {

                $info[$i][0] = $name;
                //numer ref
                $a = $this->getElementsByClass($node, 'div', 'c0');
                $info[$i][1] = $a[0]->nodeValue;
                //nazwa
                $a = $this->getElementsByClass($node, 'div', 'c1a');
                $info[$i][2] = $a[0]->nodeValue;
                //seria
                $a = $this->getElementsByClass($node, 'span', 'itemnum');
                $info[$i][3] = $a[0]->nodeValue;
                //cena
                $a = $this->getElementsByClass($node, 'div', 'c2');
                // dd($a);
                if (isset($a[0]->nodeValue))
                    $info[$i][4] = $a[0]->nodeValue;
                else {
                    $info[$i][4] = 'brak';
                }
                fputcsv($handle, array($info[$i][0], $info[$i][1], $info[$i][2], $info[$i][3], $info[$i][4]));
                $i++;
            }
            if ($i > 100) break;
        }

        $headers = array(
            'Content-Type' => 'text/csv',
        );

        Storage::put('products.csv', $handle);
        fclose($handle);
        return Response::download($filename, 'products.csv', $headers);
    }

    private function suzukiGetSubcategories($categories)
    {
        $sub = [];
        foreach ($categories as $key => $category) {
            $url = $category;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; pt-BR; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.04 (lucid) Firefox/3.6.18');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            $document = new \DOMDocument('1.0', 'UTF-8');
            $internalErrors = libxml_use_internal_errors(true);
            $document->loadHTML($result);
            $div_a_class_nodes = $this->getElementsByClass($document, 'div', 'passemname');
            foreach ($div_a_class_nodes as $node) {
                $a = $node->getElementsByTagName('a')->item(0);
                $sub[] = 'https://www.suzukipartshouse.com' . $a->getAttribute('href');
            }
        }

        return $sub;
    }

    private function suzukiGetCategories($years)
    {
        $parts = [];
        foreach ($years as $year) {
            //$url = 'https://www.suzukipartshouse.com/oemparts/c/suzuki_motorcycle_2020/parts';
            $url = $year;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; pt-BR; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.04 (lucid) Firefox/3.6.18');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            $document = new \DOMDocument('1.0', 'UTF-8');
            $internalErrors = libxml_use_internal_errors(true);
            $document->loadHTML($result);
            $div_a_class_nodes = $this->getElementsByClass($document, 'a', 'pjq');
            foreach ($div_a_class_nodes as $node) {
                $parts[] = 'https://www.suzukipartshouse.com' . $node->getAttribute('href');
            }
        }

        return $parts;
    }

    function get_http_response_code($url)
    {
        $headers = get_headers($url);
        //  dd($headers);
        return substr($headers[0], 9, 3);
    }

    function getElementsByClass(&$parentNode, $tagName, $className)
    {
        $nodes = array();

        $childNodeList = $parentNode->getElementsByTagName($tagName);
        for ($i = 0; $i < $childNodeList->length; $i++) {
            $temp = $childNodeList->item($i);
            if (stripos($temp->getAttribute('class'), $className) !== false) {
                $nodes[] = $temp;
            }
        }

        return $nodes;
    }

    private function suzukiGetImages()
    {
        $array = [];
        $categories = [];
        $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-r1000';
        /* $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-katana-gsx-s1000s';
         $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-r1000r?submodel=gsx-r1000ram0';
         $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-r1000r?submodel=gsx-r1000rzam0';
         $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-rmz450';
         $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-s1000?submodel=gsx-s1000am0';
         $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-s1000?submodel=gsx-s1000zam0';
         $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-s1000f?submodel=gsx-s1000fam0';
         $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-s1000f?submodel=gsx-s1000fzam0';*/

        foreach ($categories as $category) {
            $url = $category;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; pt-BR; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.04 (lucid) Firefox/3.6.18');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            $document = new \DOMDocument('1.0', 'UTF-8');
            $internalErrors = libxml_use_internal_errors(true);
            $document->loadHTML($result);
            $div_a_class_nodes = $this->getElementsByClass($document, 'strong', 'oem-assembly__vehicle-name');
            foreach ($div_a_class_nodes as $node) {
                $catName = trim(preg_replace('/\s\s+/', ' ', $node->nodeValue));
            }
            $div_a_class_nodes = $this->getElementsByClass($document, 'a', 'oem-assemblies__assembly');
            foreach ($div_a_class_nodes as $node) {
                $name = trim(preg_replace('/\s\s+/', ' ', $node->nodeValue));
                $array[strtolower($catName . ' ' . $name)] = 'https://www.revzilla.com' . $node->getAttribute('href');
            }
        }

        $imagesUrls = [];
        $images = [];
        foreach ($array as $key => $image) {
            $url = $image;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; pt-BR; rv:1.9.2.18) Gecko/20110628 Ubuntu/10.04 (lucid) Firefox/3.6.18');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);

            curl_close($ch);
            $document = new \DOMDocument('1.0', 'UTF-8');

            $internalErrors = libxml_use_internal_errors(true);
            $document->loadHTML($result);

            /*   $div_a_class_nodes = $this->getElementsByClass($document, 'h2', 'idSchematicName');

               $name = $key;
               if (isset($div_a_class_nodes[0]))
                   $name = $div_a_class_nodes[0]->nodeValue;*/


            $div_a_class_nodes = $this->getElementsByClass($document, 'div', 'oem-fiche__image');
            foreach ($div_a_class_nodes as $n) {
                $img = str_replace(['background-image: url(', ')'], '', $n->getAttribute('style'));
            }

            $images [$key] = 'https://www.revzilla.com' . $img;

        }

        //$this->info('pobrałem zdjęcia: ' . count($images));
        dd($images);
        return $images;
    }


    public function ceneo($products)
    {
        foreach ($products as $product) {
            if (is_null($product->ceneo_id)) {
                $url = 'https://ceneo.pl/;szukaj-' . $product->ean;
                //dd($url);


                $html = file_get_contents($url, false);;

                $document = new \DOMDocument('1.0', 'UTF-8');

                $internalErrors = libxml_use_internal_errors(true);
                $document->loadHTML($html);
                //   dd($document);
                $xpath = new \DOMXPath($document);

                $div_a_class_nodes = $this->getElementsByClass($document, 'div', 'cat-prod-box');
                //dd($div_a_class_nodes);
                if ($div_a_class_nodes == []) {
                    $div_a_class_nodes = $this->getElementsByClass($document, 'div', 'cat-prod-row');
                    if ($div_a_class_nodes == []) {
                        $url = 'https://ceneo.pl/;szukaj-' . str_replace('++', '+', str_replace([' ', '-', 'ł'], ['+', '', 'l'], $product->name));
                        //dd($url);

                        $html = file_get_contents($url);

                        $document = new \DOMDocument('1.0', 'UTF-8');

                        $internalErrors = libxml_use_internal_errors(true);
                        $document->loadHTML($html);
                        //   dd($document);
                        $xpath = new \DOMXPath($document);

                        $div_a_class_nodes = $this->getElementsByClass($document, 'div', 'cat-prod-box');
                        if ($div_a_class_nodes == []) {
                            $div_a_class_nodes = $this->getElementsByClass($document, 'div', 'cat-prod-row');
                            if ($div_a_class_nodes == []) {
                                //dd($product);
                            }

                        }
                    }
                    $array = [];
                    //dd($document);
                    foreach ($div_a_class_nodes as $node) {
                        //dd($node->find('div[data-pid]', 0)->{'data-pid'});
                        //dd($node->getElementsByTagName('a')->attributes);
                        $a = $node->getElementsByTagName('a')->item(0);
                        if (is_null($a)) {
                            break;
                        }
                        $array[$product->id] = $a->getAttribute('href');
                        $ceneoId = str_replace(['/', '###tab=reviews_scroll', '###tab=reviews_new'], '', $a->getAttribute('href'));
                        if (is_int($ceneoId))
                            $product->ceneo_id = $ceneoId;
                        $product->save();
                        /* if ($node->getElementsByTagName('href')->length > 0) {
                             dd($node->getElementsByTagName('href'));
                         }

                         if ($node->getAttribute('href') != "") {

                             dd($node->getAttribute('href'));
                         }*/
                    }
                }
                // dd($div_a_class_nodes);

            } else {

            }
        }
    }

    public function allegro($products)
    {
        foreach ($products as $product) {
            if ($product->id != 231) {
                continue;
            }
            $url = 'https://allegro.pl/listing?string=' . str_replace(' ', '%20', $product->name) . '&bmatch=baseline-product-cl-eyesa2-engag-dict45-uni-1-1-0717&order=p';
            dd($url);


            $html = file_get_contents($url, false);

            $document = new \DOMDocument('1.0', 'UTF-8');

            $internalErrors = libxml_use_internal_errors(true);
            $document->loadHTML($html);
        }
    }

}
