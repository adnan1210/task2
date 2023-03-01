<?php
include_once('simple_html_dom.php');


function crawlWeb($url){
    // Set user agent string
    $options = [
        'http' => [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
        ],
    ];

    // Create a new context with the user agent string
    $context = stream_context_create($options);

    // Load HTML from URL with the context
    $html = file_get_contents($url, false, $context);

    return $html;
}

function getCategoryList(){
    $url = 'https://yourpetpa.com.au';
    $collectionList = array();
    $html = crawlWeb($url);

    $dom = new simple_html_dom();
    $dom->load($html);
    $collections = $dom->find('.site-nav__dropdown-heading');

    foreach($collections as $collection) {
        array_push($collectionList, $collection->href);
    }

    $dom->clear();

    return $collectionList;
}

function getProductList($collectionList){
    $url = 'https://yourpetpa.com.au';
    $productList = [["Id","Title","Category","Price","Url","ImageUrl"]];
    foreach($collectionList as $collection){
        $collectionName=explode("/",$collection)[2];
        $html = crawlWeb($url.$collection);
        $dom = new simple_html_dom();
        $dom->load($html);

        $products = $dom->find('.product__content__wrap');
        foreach($products as $product){
            $productDetails = array();
            $productDom = new simple_html_dom();
            $productDom->load($product);
            //Fetching Product Id
            $FindId = $productDom->find('script.quickbuy-placeholder-template', 0);
            $idDom = new simple_html_dom();
            $idDom->load($FindId->innertext);
            $idDiv = $idDom->find('.product-detail', 0);
            array_push($productDetails,$idDiv->getAttribute('data-product-id')); //Id
            $idDom->clear();

            //Fetching Product Title
            $productTitle=$productDom->find('.product-block__title-link', 0);
            array_push($productDetails,$productTitle->innertext); //Title

            //Fething Category Name
            array_push($productDetails,$collectionName); //category

            //Fetching Product Price
            $price = $productDom->find('.theme_money',0);
            array_push($productDetails,$price->innertext); //Price

            //Fetching Product URL
            array_push($productDetails,$url.$productTitle->href);  //Link

            //Fetching Product Image
            $findimage = $productDom->find('noscript', 0);
            $imageDom = new simple_html_dom();
            $imageDom->load($findimage->innertext);
            $img = $imageDom->find('img', 0);
            $src = $img->src;
            array_push($productDetails,'https:'.$src);  //Image-link
            $imageDom->clear();

            array_push($productList,$productDetails);
        }

        $dom->clear();
    }

    return $productList;
}

function generateCSV($productDetails){
    // Define the name and path of the CSV file
    $filename = 'products.csv';

    // Open the CSV file for writing
    $file = fopen($filename, 'w');

    // Loop through the data and write each row to the CSV file
    foreach ($productDetails as $row) {
        fputcsv($file, $row);
    }

    // Close the CSV file
    fclose($file);

    echo "CSV file created successfully!";
}

$collection = getCategoryList();
$product = getProductList($collection);
generateCSV($product);
?>
