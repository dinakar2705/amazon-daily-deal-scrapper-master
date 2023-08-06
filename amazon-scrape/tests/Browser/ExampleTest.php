<?php

namespace Tests\Browser;

use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Cocur\Slugify\Slugify;

class ExampleTest extends DuskTestCase
{
    private $main;
    // Types of urls fetched from amazon
    //amazon.com/deal =>containts further products
    //amazon.com/stores =>contains paginated further products
    //amazon.com/s/browse = >contains misc
    //
    /**
     * Main job to fetch the products data
     * @throws \Throwable
     */
    public function testFetchUrlFromTxt()
    {
        $this->readFromTxt(today()->toDateString() . '1.txt');
    }

    public function hoverGalleryImgs()
    {
        $browser->mouseover('.selector');
    }

    /**
     * Read data from file
     * @param $filename
     * @throws \Throwable
     */

    public function readFromTxt($filename)
    {
        $fh = fopen($filename, 'r');
        while ($productUrl = fgets($fh)) {
            if (!strpos($productUrl, 's/browse') && !strpos($productUrl, 'stores') && !strpos($productUrl, 'deal/') && !strpos($productUrl, 'b?node') && !strpos($productUrl, 's?rh')) {
//                echo $productUrl . PHP_EOL;
                $this->singleProductDataFetch($productUrl);
            } else if (strpos($productUrl, 'deal/')) {
                $this->fetchProductLinksFromDeals($productUrl);
            }
        }
        fclose($fh);
    }

    /**
     * Fetch single product data
     * @param $productLink
     * @throws \Throwable
     */
    public function singleProductDataFetch($productLink)
    {
        try {
            $this->browse(function (Browser $browser) use ($productLink) {
                $mainCategory = null;
                $main = $browser->visit($productLink);
                $breadCrumb = $main->elements('#wayfinding-breadcrumbs_feature_div  > ul > li');
                if (count($breadCrumb) > 0) {
                    $mainCategory = $breadCrumb[0]->getText();
                }
                $title = $main->element('#productTitle') ? $main->element('#productTitle')->getText() : '';
                $cast = $main->element('#bylineInfo') ? $main->element('#bylineInfo')->getText() : null;
                $rating = $main->element('#acrPopover') ? $main->element('#acrPopover')->getAttribute('title') : null;
                $totalRatings = $main->element('#acrCustomerReviewText') ? $main->element('#acrCustomerReviewText')->getText() : null;

//            feature-bullets
//            poExpander

                //mini gallery
                $miniMedia = null;

                $miniGallery = $main->element('.regularAltImageViewLayout');

                if ($miniGallery) {
                    foreach ($miniGallery as $singleMinGallery) {
                        $singleMinGallery->mouseover(WebDriverBy::tagName('li'));
                    }
                }
                //Gallery
                $media = null;
                $gallery = $main->elements('#main-image-container  ul');
                if (count($gallery) > 0) {
                    foreach ($gallery as $singleImg) {
                        if (count($singleImg->findElements(WebDriverBy::tagName('li'))) > 0) {
                            $lis = $singleImg->findElements(WebDriverBy::tagName('li'));
                            $media = $lis[0]->findElements(WebDriverBy::tagName('img'))[0]->getAttribute('src');
//                        dd($lis);
//                        foreach ($lis as $key => $li)
//                        {
//                            echo ($li->findElements(WebDriverBy::tagName('img'))[$key]->getAttribute('src'));
//                            $media[] = $li->findElements(WebDriverBy::tagName('img'))[$key]->getAttribute('src');
//
//                        }

                        }
                    }
                }

                //Overview
//            $main->element('#productOverview_feature_div');
                $about = $main->element('#feature-bullets');
                if ($about) {
                    $about = $about->getText();
                }
                $overview = $main->element('#productOverview_feature_div');
                if ($overview) {
                    $overview = $overview->getText();
                }
                //brand
                $brand = null;
                $company = $main->element('#bylineInfo');
                if ($company) {
                    $brand = $company->getText();
                    if ($brand) {
                        $brand = explode('Visit the ', $brand);
                        if (count($brand) > 1) {
                            $brand = $brand[1];
                        } else {
                            $brand = current($brand);
                        }
                    }

                }

//            //Amazon's choice
                $amazonChoice = $main->element('#acBadge_feature_div');
                if ($amazonChoice) {
                    $amazonChoice = $amazonChoice->getText() ? 'true' : null;

                }
//            //Amazon's best seller
                $amazonBestSeller = $main->element('#zeitgeistBadge_feature_div');
                if ($amazonBestSeller) {
                    $amazonBestSeller = $amazonBestSeller->getText() ? 'true' : null;


                }
                $postMeta = [
                    ['ratings' => $rating],
                    ['total_ratings' => $totalRatings],
                    ['about' => $about],
                    ['cast' => $cast],
                    ['category' => $mainCategory],
                    ['media' => $media],
                    ['original_url' => $productLink],
                    ['brand' => $brand],
                    ['amazon_choice' => $amazonChoice],
                    ['amazon_best_seller' => $amazonBestSeller],
                    ['market' => 'amazon'],
                ];

                //Prices
                $prices = $main->elements('#corePrice_desktop > div > table > tbody > tr');
                if (count($prices)) {
                    foreach ($prices as $price) {
                        $postMeta[][$price->findElement(WebDriverBy::tagName('td'))->getText()] = $price->findElement(WebDriverBy::tagName('span'))->getText();
//                    echo $price->findElement(WebDriverBy::tagName('span'))->getText();
                    }
                }
                $corePrice = $main->element('#corePrice_feature_div');
                if ($corePrice) {
                    $postMeta[] ['core_price'] = $corePrice->getText();
                }
                $postData = [
                    'post_title' => $title,
                    'post_content' => $overview ?? '',
                    'post_status' => 'publish',
                    'post_type' => 'post',
                    'post_excerpt' => $overview ?? '',
                    'to_ping' => 'xyz',
                    'pinged' => 'xyz',
                    'post_content_filtered' => "",

                    'post_date' => now()->toDateString(),
                    'post_date_gmt' => now()->toDateString(),
                    'post_modified' => now()->toDateString(),
                    'post_modified_gmt' => now()->toDateString()
                ];
                //slugify the post title
                $slugify = new Slugify();
                $postData['post_name'] = substr($slugify->slugify($title), 0, 155);


                $this->insertInPost($postData, $postMeta);
//            $browser->quit();

            });
        } catch (\Exception $e) {
            logger($e->getMessage());
        }


    }

    public function insertInPost($postData, $postMeta)
    {
        $termRelationshipTable = env('WP_PREFIX') . 'term_relationships';
        $table = env('WP_PREFIX') . 'posts';
        $meta = env('WP_PREFIX') . 'postmeta';
        $post = DB::table($table)
            ->updateOrInsert(
                $postData
            );
        $termTaxonomyId = 1;
        if ($postMeta[4]['category']) {
            $termTaxonomyId = $this->createOrUpdateTerm($postMeta[4]['category']);
        }

        if ($post) {
            $data = DB::table($table)->latest('id')->first();
            if ($postMeta[8]['amazon_choice']) {
                $termTaxonomyId = $this->createOrUpdateTermRelations('amazon_choice', $data->ID);
            }
            if ($postMeta[9]['amazon_best_seller']) {

                $termTaxonomyId = $this->createOrUpdateTermRelations('amazon_best_seller', $data->ID);
            }

            foreach ($postMeta as $singleMeta) {
                $metaKey = key($singleMeta);
                if ($metaKey == 'List Price:') {
                    $metaKey = 'list_price';
                }
                if ($metaKey == 'Price:') {
                    $metaKey = 'price';
                }
                $metaValue = current(array_values($singleMeta));
//                    var_dump(['meta_value' => $metaValue, 'meta_key' => $metaKey, 'post_id' => $data->ID]);
                try {
                    DB::table($meta)
                        ->insert(
                            ['meta_value' => $metaValue, 'meta_key' => $metaKey, 'post_id' => $data->ID]
                        );
                } catch (\Exception $e) {
                    logger()->error($e->getMessage());
                }


            }


        } else {
            //do nohting
        }
    }

    private function createOrUpdateTerm($term)
    {
        $slugify = new Slugify();
        $table = env('WP_PREFIX') . 'terms';
        $termTaxonomyTable = env('WP_PREFIX') . 'term_taxonomy';
        $termObj = DB::table($table)->where('name', $term)->first();
        if (!$termObj) {
            $termObj = DB::table($table)
                ->insertGetId(
                    [
                        'name' => $term,
                        'slug' => $slugify->slugify($term)
                    ]
                );

        }
        $termTaxonomyObj = DB::table($termTaxonomyTable)->where('term_id', $termObj->term_id ?? $termObj)->first();
        if ($termTaxonomyObj) {
            return $termTaxonomyObj->term_taxonomy_id;
        }
        return DB::table($termTaxonomyTable)
            ->insertGetId(
                [
                    'term_id' => $termObj->term_id ?? $termObj,
                    'taxonomy' => 'category',
                    'description' => ''
                ]
            );


    }

    private function createOrUpdateTermRelations($term, $postId)
    {
        try {
            $slugify = new Slugify();
            $table = env('WP_PREFIX') . 'terms';
            $termTaxonomyTable = env('WP_PREFIX') . 'term_taxonomy';
            $termRelationTable = env('WP_PREFIX') . 'term_relationships';
            $termObj = DB::table($table)->where('name', $term)->first();
            if (!$termObj) {
                $termObj = DB::table($table)
                    ->insertGetId(
                        [
                            'name' => $term,
                            'slug' => $slugify->slugify($term)
                        ]
                    );
            }
            $termTaxonomyObj = DB::table($termTaxonomyTable)->where('term_id', $termObj->term_id ?? $termObj)->first();
            if (empty($termTaxonomyObj)) {
                $termTaxonomyId = DB::table($termTaxonomyTable)
                    ->insertGetId(
                        [
                            'term_id' => $termObj->term_id ?? $termObj,
                            'taxonomy' => 'post_tag',
                            'description' => ''
                        ]
                    );
            } else {
                $termTaxonomyId = $termTaxonomyObj->term_taxonomy_id;
            }


            return DB::table($termRelationTable)
                ->insertGetId(
                    [
                        'term_taxonomy_id' => $termTaxonomyId,
                        'object_id' => $postId
                    ]
                );
        } catch (\Exception $exception) {
            logger()->info($exception->getMessage());
        }


    }

    /**
     * Fetch product url from deal url
     * @param $dealUrl
     */
    public function fetchProductLinksFromDeals($dealUrl)
    {
        $this->browse(function (Browser $browser) use ($dealUrl) {
            $main = $browser->visit($dealUrl);
            $elements = $main->elements('#octopus-dlp-asin-stream > ul > li');
            foreach ($elements as $element) {
                $productUrl = $element->findElement(WebDriverBy::tagName('a'))->getAttribute('href');
                $haystack = $productUrl;
                $needle = "http://www.amazon.com/";
                if (str_contains($haystack, $needle)) {
                    //Do nothing
                }
                else
                {
                    $productUrl = $needle . $productUrl;
                }
//                if (!strpos($productUrl, 's/browse') && !strpos($productUrl, 'stores')) {
                $this->writeToTxt($productUrl, 1);

//                }
            }
        });
    }

    /**
     * Write product url to txt file
     * @param string $txt
     * @param string $filename
     */
    public function writeToTxt(string $txt, string $filename)
    {

        $myfile = fopen(today()->toDateString() . $filename . '.txt', 'a') or die('Unable to open file!');
        fwrite($myfile, $txt . PHP_EOL);
        fclose($myfile);
    }

    /**
     * A basic browser test that will visit each page.
     *
     * @return void
     * @throws \Throwable
     */

    public function testBasicExample()
    {
        $this->browse(function (Browser $browser) {
            $main = $browser->visit('https://www.amazon.com/gp/goldbox?ref_=nav_cs_gb');
            $this->main = $main;
//            $totalPages = $main->elements('li.a-disabled');
//            $totalPages = $totalPages[count($totalPages) - 1]->getText();
            for ($i = 0; $i < 4; $i++) {
                $this->singleProduct($main);
                sleep(10);
                $main->click('li.a-last a');
                sleep(20);
            }
//            echo $totalPages;
//            die();
        });
    }

    /**
     * Visit every page of daily deal page and insert mixed url (single product,deals etc) in txt file
     * @param $innerMain
     */
    public function singleProduct($innerMain)
    {
        $elements = $this->main->elements('.Grid-module__gridDisplayGrid_2X7cDTY7pjoTwwvSRQbt9Y > div');
        $i = 0;
        foreach ($elements as $element) {
            $productUrl = $element->findElement(WebDriverBy::tagName('a'))->getAttribute('href');

            if (!strpos($productUrl, 's/browse') && !strpos($productUrl, 'stores')) {
                $this->writeToTxt($productUrl, 1);
            }
        }
    }
}
