<?php

namespace App\Console\Commands;

use App\Repositories\CrawlerRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class StartCrawler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Przejście przez suzuki i pobranie info o częściach';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('start');
        $this->suzuki();
    }

    public function suzuki()
    {
        //$images = $this->suzukiGetImages();
        $years = $this->suzukiGetYears();
        $categories = $this->suzukiGetCategories($years);
        $subcategories = $this->suzukiGetSubcategories($categories);
        $this->suzukiGetInfo($subcategories, []);
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


    private function suzukiGetCategories($years)
    {
        $parts = [];
        foreach ($years as $year) {
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
            $this->info('pobrałem kategorie: ' . count($parts));

        }

        return $parts;
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

            $this->info('pobrałem podkategorie: ' . count($sub));
            //$this->suzukiGetInfo($sub);
            //exit;
        }

        //$this->info('pobrałem podkategorie: ' . count($sub));
        return $sub;
    }

    private function suzukiGetInfo($subcategories = [], $images = [])
    {

        $this->info('Pobieram produkty');
        $info = [];
        $i = 0;
        $bar = $this->output->createProgressBar(count($subcategories));

        $bar->start();


        $filename = "products.csv";
        $handle = fopen($filename, 'w+');
        fputcsv($handle, array('nazwa grupy', 'numer ref', 'nazwa części', 'numer serii', 'cena', 'zdjęcie'));


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
                $img = "";
                if (isset($images[strtolower($name)])) {
                    $img = $images[strtolower($name)];
                }
                fputcsv($handle, array($info[$i][0], $info[$i][1], $info[$i][2], $info[$i][3], $info[$i][4], $img));
                $i++;
            }
            // if ($i > 100) break;
            $bar->advance();
        }

        $headers = array(
            'Content-Type' => 'text/csv',
        );

        $bar->finish();


        $this->info('tworze CSV');
        Storage::put('products.csv', $handle);
        fclose($handle);

        $this->info('zapisałem CSV');
        return Response::download($filename, 'products.csv', $headers);
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
        $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-katana-gsx-s1000s';
        $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-r1000r?submodel=gsx-r1000ram0';
        $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-r1000r?submodel=gsx-r1000rzam0';
        $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-rmz450';
        $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-s1000?submodel=gsx-s1000am0';
        $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-s1000?submodel=gsx-s1000zam0';
        $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-s1000f?submodel=gsx-s1000fam0';
        $categories[] = 'https://www.revzilla.com/oem/suzuki/2020-suzuki-gsx-s1000f?submodel=gsx-s1000fzam0';

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
                $array[$catName . ' ' . $name] = 'https://www.revzilla.com' . $node->getAttribute('href');
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

            $div_a_class_nodes = $this->getElementsByClass($document, 'div', 'oem-fiche__image');
            foreach ($div_a_class_nodes as $n) {
                $img = str_replace(['background-image: url(', ')'], '', $n->getAttribute('style'));
            }

            $images [strtolower($key)] = 'https://www.revzilla.com' . $img;

        }

        $this->info('pobrałem zdjęcia: ' . count($images));
        return $images;
    }
}
