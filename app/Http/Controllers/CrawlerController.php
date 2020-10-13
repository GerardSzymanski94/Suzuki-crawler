<?php

namespace App\Http\Controllers;

use App\Repositories\CrawlerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CrawlerController extends Controller
{
    public function index()
    {
        $products = [];
        $urls = [];
        $accesoriesUrls = [];

        $url = 'https://store.suzukicycles.com/c/accessories';

        $html = file_get_contents($url, true);

        $document = new \DOMDocument('1.0', 'UTF-8');

        $internalErrors = libxml_use_internal_errors(true);
        $document->loadHTML($html);

        $content_node = $document->getElementById("left_nav_categories");
        // $div_a_class_nodes = $this->getElementsByClass($content_node, 'a', 'prod-detail');
        $div_li = $content_node->getElementsByTagName('a');
        foreach ($div_li as $li) {
            $urls[] = 'https://store.suzukicycles.com' . $li->getAttribute('href');

        }
        foreach ($urls as $url) {
            $html = file_get_contents($url, true);
            $document = new \DOMDocument('1.0', 'UTF-8');

            $internalErrors = libxml_use_internal_errors(true);
            $document->loadHTML($html);

            $content_node = $document->getElementById("left_nav_categories");
            $div_a_class_nodes = $this->getElementsByClass($content_node, 'ul', 'ln_lvl_2');
            $j = 0;
            foreach ($div_a_class_nodes as $element) {
                $el = $element->getElementsByTagName('a');
                foreach ($el as $e) {
                    $accesoriesUrls[] = 'https://store.suzukicycles.com' . $e->getAttribute('href');
                }
            }
        }

        $productsUrls = [];
        $prod = 0;
        foreach ($accesoriesUrls as $url) {
            $true = true;
            $page = 1;
            while ($true) {
                $html = file_get_contents($url . '?pg=' . $page, true);
                $document = new \DOMDocument('1.0', 'UTF-8');

                $internalErrors = libxml_use_internal_errors(true);
                $document->loadHTML($html);

                $content_node = $document->getElementById("left_nav_page_content");
                $div_a_class_nodes = $this->getElementsByClass($content_node, 'a', 'prod-detail');

                foreach ($div_a_class_nodes as $element) {
                    $productsUrls[] = 'https://store.suzukicycles.com' . $element->getAttribute('href');
                    /*   $el = $this->getElementsByClass($element, 'h2', 'title_link');

                       $products[$prod]['name'] = $el[0]->nodeValue;

                       $el = $element->getElementsByTagName('img');
                       foreach ($el as $e) {
                           $products[$prod]['src'] = 'https://store.suzukicycles.com' . $e->getAttribute('src');
                       }


                       $el = $this->getElementsByClass($element, 'span', 'price');

                       $products[$prod]['price'] = $el[0]->nodeValue;
                       $prod++;*/
                }
                if (count($div_a_class_nodes) < 24) {
                    $true = false;
                } else {
                    $page++;
                }
            }
        }

        //dd($productsUrls);
        foreach ($productsUrls as $key => $productsUrl) {
            $html = file_get_contents($productsUrl, true);
            $document = new \DOMDocument('1.0', 'UTF-8');

            $internalErrors = libxml_use_internal_errors(true);
            $document->loadHTML($html);

            $content_node = $document->getElementById("columns_container");

              //  $products[$key]['price'] = $document->getElementById("pd_price")->nodeValue;
                $products[$key]['product_description'] = $document->getElementById("product_description")->nodeValue;
                $el = $content_node->getElementsByTagName('img');
                foreach ($el as $e) {
                    $products[$key]['src'] = 'https://store.suzukicycles.com' . $e->getAttribute('src');
                }
                $el = $content_node->getElementsByTagName('h1');
                $products[$key]['name'] = $el[0]->nodeValue;

                $el = $this->getElementsByClass($content_node, 'span', 'item_number');
                $products[$key]['item_number'] = $el[0]->nodeValue;


        }


        $filename = "products.csv";
        $handle = fopen($filename, 'w+');
        fputcsv($handle, array('nazwa', 'zdjęcie', 'opis', 'numer katalogowy'));

        foreach ($products as $key => $row) {
            fputcsv($handle, array($row['name'], $row['src'],  $row['product_description'], $row['item_number']));
        }

        fclose($handle);

        $headers = array(
            'Content-Type' => 'text/csv',
        );

        return Response::download($filename, 'products.csv', $headers);

        dd($products);

        dd($accesoriesUrls);


        $content_node = $document->getElementById("left_nav_page_content");
        $div_a_class_nodes = $this->getElementsByClass($content_node, 'a', 'prod-detail');

        $i = 0;
        foreach ($div_a_class_nodes as $element) {
            $el = $element->getElementsByTagName('img');
            foreach ($el as $e) {
                $products[$i]['src'] = $e->getAttribute('src');
            }

            $el = $this->getElementsByClass($element, 'h2', 'title_link');

            $products[$i]['name'] = $el[0]->nodeValue;
            $el = $this->getElementsByClass($element, 'span', 'price');

            $products[$i]['price'] = $el[0]->nodeValue;
            $i++;
        }
        dd($products);

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


    public function crawler(){
        $repo = new CrawlerRepository();
        $repo->crawler();
    }

}
