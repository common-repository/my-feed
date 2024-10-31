<?php
/**
 * Plugin Name:       AW Feed Manager For WooCommerce Product
 * Description:       This is feed plugin for upload product feed to google merchant center.
 * Version:           1.0.3
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Adonwebs
 * Author URI:        https://adonwebs.com/my-feed-plugin/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */
/*
{AW Feed Manager For WooCommerce Product} is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
{AW Feed Manager For WooCommerce Product} is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with {AW Feed Manager For WooCommerce Product}. If not, see {URI to Plugin License}.
*/
if(!defined('ABSPATH')){
    die("Can't Access");
}

function fmfw_my_feed_activation(){
    //
    global $wpdb, $table_prefix;
    $wp_fp = $table_prefix.'fp_list';
    $q = "CREATE TABLE $wp_fp (
    `id` int(20) NOT NULL AUTO_INCREMENT ,
    `name` text NOT NULL,
    `type` varchar(255) NOT NULL,
    `content` text NOT NULL,
    `datetime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id)
    ) ENGINE= MYISAM;";
    $wpdb->query($q);
}

register_activation_hook(__FILE__,'fmfw_my_feed_activation');

function fmfw_my_feed_deactivation(){
    //
    global $wpdb, $table_prefix;
    $wp_fp = $table_prefix.'fp_list';
    $q = "DROP TABLE $wp_fp";
    $wpdb->query($q);
}
register_deactivation_hook(__FILE__,'fmfw_my_feed_deactivation');
//submenu add in tools menu
function fmfw_my_feed(){
    include 'admin/home.php';
}
function fmfw_dashboard(){
    include 'admin/index.php';
}
function fmfw_new_feed(){
    require_once 'admin/new-feed.php';
}
function fmfw_my_feed_menu(){
    add_menu_page('My Feed', 'My Feed', 'manage_options', 'my-feed','fmfw_my_feed');
    add_submenu_page( 'my-feed', 'New Feed', 'Add New','manage_options', 'new-feed','fmfw_new_feed');

}

add_action('admin_menu','fmfw_my_feed_menu');

function fmfw_my_custom_scripts(){
    $path_js = plugins_url('admin/js/main.js', __FILE__);
    $path_style = plugins_url('admin/js/main.js', __FILE__);
    $dep = array('jquery');
    $ver = filemtime(plugin_dir_path(__FILE__).'admin/js/main.js');
    wp_enqueue_style('my-custom-style',$path_style,'',$ver_style);
    wp_enqueue_script('my-custom-js', $path_js, $deep, $ver,true);
    wp_add_inline_script('my-custom-js','var ajaxUrl= "'.admin_url('admin-ajax.php').'";','before');
}
add_action('wp_enqueue_script','fmfw_my_custom_scripts');
add_action('admin_enqueue_scripts','fmfw_my_custom_scripts');
add_action('wp_ajax_create_feed','create_feed');

// javascript load for home page
function fmfw_js_feed_manager() {

  wp_register_script( 'custom-pagination-delete-home', plugins_url('admin/js/pagination.js', __FILE__).'',array(),1,1,1 );
  wp_enqueue_script('custom-pagination-delete-home');


}
add_action('admin_enqueue_scripts', 'fmfw_js_feed_manager');

$data=array();// your data object you want to pass to your front-end script.
$localise = json_encode($data);
add_action('wp_footer', function() use ($localise){
  printf("<script type='text/javascript'>function delete_user(uid)
    {
        if (confirm('Are You Sure to Delete this Feed?'))
        {
            window.location.href = 'admin.php?page=my-feed&delete&file_id=' + uid;
        }
    }</script>", $localise);
});

//css load for homepage
function load_css_feed_manager() {
  wp_register_style( 'custom-home-css', plugins_url('admin/css/bootstrap.min.css', __FILE__) );
  wp_enqueue_style('custom-home-css');
  wp_register_style( 'custom-home-css1', plugins_url('admin/css/custom.css', __FILE__) );
  wp_enqueue_style('custom-home-css1');
  wp_register_style( 'custom-home-font', plugins_url('admin/css/fonts.css', __FILE__));
  wp_enqueue_style('custom-home-font');
}
add_action('wp_enqueue_scripts', 'load_css_feed_manager');

function create_feed(){

$file_name = sanitize_text_field($_POST['file_name']);
global $wpdb, $table_prefix;
if($_POST['file_type'] == 'xml'){
//header('Content-Type: text/xml; charset=utf-8', true); //set document header content type to be XML
$xml = new DOMDocument("1.0", "UTF-8"); // Create new DOM document.

//create "RSS" element
$xml->formatOutput=true;

$rss = $xml->createElement("rss"); 
$rss_node = $xml->appendChild($rss); //add RSS element to XML node
$rss_node->setAttribute("version","2.0"); //set RSS version

//set attributes
$rss_node->setAttribute("xmlns:g","http://base.google.com/ns/1.0"); //xmlns:dc (info http://j.mp/1mHIl8e )
$rss_node->setAttribute("xmlns:c","http://base.google.com/cns/1.0"); //xmlns:content (info http://j.mp/1og3n2W)

//Create RFC822 Date format to comply with RFC822
$date_f = date("D, d M Y H:i:s T", time());
$build_date = gmdate(DATE_RFC2822, strtotime($date_f));

//create "channel" element under "RSS" element
$channel = $xml->createElement("channel");  
$channel_node = $rss_node->appendChild($channel);

//add general elements under "channel" node
$channel_node->appendChild($xml->createElement("title", "adonwebs")); //title
$channel_node->appendChild($xml->createElement("description", "Feed - This product feed is generated with the AW Feed Manager For WooCommerce Product plugin by adonwebs.com. For all your support questions check out our plugin Docs on https://adonwebs.com/docs or e-mail to: devs@adonwebs.com"));  //description
$channel_node->appendChild($xml->createElement("link", "http://www.adonwebs.com")); //website link 

$args = array('post_type'      => 'product' ,'posts_per_page' => 999);
$loop = new WP_Query( $args );
while ( $loop->have_posts() ) : $loop->the_post();
    global $product;
// get all Products
      $item_node = $channel_node->appendChild($xml->createElement("item")); //create a new node called "item"
 if($product->get_id()>0){ 
      $title_node = $item_node->appendChild($xml->createElement("g:id", ''.$product->get_id().'')); //Add Title under "item"
  }    
//create g:title node under "item"
      $g_title = $item_node->appendChild($xml->createElement("g:title"));  
      //fill title node with CDATA content
      $g_title_contents = $xml->createCDATASection(htmlentities(''.$product->get_name().''));  
      $g_title->appendChild($g_title_contents); 
    
//create g:title node under "item"
      $g_des = $item_node->appendChild($xml->createElement("g:description"));  
      //fill title node with CDATA content
      $g_des_contents = $xml->createCDATASection(htmlentities(''.$product->get_description().''));  
      $g_des->appendChild($g_des_contents); 
  
//create g:title node under "item"
      $g_s_des = $item_node->appendChild($xml->createElement("g:short_description"));  
      //fill title node with CDATA content
      $g_s_des_contents = $xml->createCDATASection(htmlentities(''.$product->get_short_description().''));  
      $g_s_des->appendChild($g_s_des_contents); 

      $link_node = $item_node->appendChild($xml->createElement("g:item_group_id", "345")); //add link node under "item"
  
//create "g:link" node under "item"
      $g_id = $item_node->appendChild($xml->createElement("link"));  
      
      //fill link node with CDATA content
      $g_contents = $xml->createCDATASection(htmlentities(''.get_permalink().''));  
      $g_id->appendChild($g_contents); 
      

      //create "g:link" node under "item"
      $g_product_type = $item_node->appendChild($xml->createElement("g:product_type"));  
      
      //fill link node with CDATA content
      $g_product_type_contents = $xml->createCDATASection(htmlentities(''.strip_tags($product->get_categories(' > ')).''));  
      $g_product_type->appendChild($g_product_type_contents); 
      

if($product->get_type()>0){
//create "g:product_type" node under "item"
      $g_product_type = $item_node->appendChild($xml->createElement("g:product_type"));  
      
      //fill g:product_type node with CDATA content
      $g_pt_contents = $xml->createCDATASection(htmlentities(''.$product->get_type().''));  
      $g_product_type->appendChild($g_pt_contents); 
  }

  $img = get_the_post_thumbnail_url($product->get_id());  
//create "g:image_link" node under "item"
      $g_image_link = $item_node->appendChild($xml->createElement("g:image_link"));  
      
      //fill g:image_link node with CDATA content
      $g_pi_contents = $xml->createCDATASection(htmlentities(''.$img.''));  
      $g_image_link->appendChild($g_pi_contents);

//create "g:condition" node under "item"
      $g_condition = $item_node->appendChild($xml->createElement("g:condition",'New'));  

//create "g:availability" node under "item"
      $g_availability = $item_node->appendChild($xml->createElement("g:availability",'in_stock'));  

//create "g:price" node under "item"
      $g_price = $item_node->appendChild($xml->createElement("g:price"));  
      
      //fill g:price node with CDATA content
      $g_price_contents = $xml->createCDATASection(htmlentities(''.$product->get_regular_price().' '.get_woocommerce_currency().''));  
      $g_price->appendChild($g_price_contents);


//create "g:sale_price" node under "item"
      $g_sale_price = $item_node->appendChild($xml->createElement("g:sale_price"));  
      
      //fill g:sale_price node with CDATA content
      if($product->get_sale_price()>0){
      $g_sale_price_contents = $xml->createCDATASection(htmlentities(''.$product->get_sale_price().' '.get_woocommerce_currency().''));  
      }else{
        $g_sale_price_contents = $xml->createCDATASection(htmlentities(''.$product->get_regular_price().' '.get_woocommerce_currency().''));  
      }
      $g_sale_price->appendChild($g_sale_price_contents);


//create "g:mpn" node under "item"
      $g_mpn = $item_node->appendChild($xml->createElement("g:mpn"));  
      
      //fill g:mpn node with CDATA content
      $g_mpn_contents = $xml->createCDATASection(htmlentities(''.$product->get_sku().''));  
      $g_mpn->appendChild($g_mpn_contents);


//create "g:brand" node under "item"
      $g_brand = $item_node->appendChild($xml->createElement("g:brand"));  
      
      //fill g:mpn node with CDATA content
      $g_brand_contents = $xml->createCDATASection(htmlentities(''.sanitize_text_field($_SERVER['HTTP_HOST']).''));  
      $g_brand->appendChild($g_brand_contents);

//create "g:canonical_link" node under "item"
      $g_canonical_link = $item_node->appendChild($xml->createElement("g:canonical_link"));  
      
      //fill g:canonical_link node with CDATA content
      $g_canonical_link_contents = $xml->createCDATASection(htmlentities(''.get_permalink().''));  
      $g_canonical_link->appendChild($g_canonical_link_contents);
// additional product images
    $productes = new WC_product($product->get_id());
    $attachment_ids = $productes->get_gallery_image_ids();
    $imaget1 = wp_get_attachment_url( $attachment_ids[0] );
    $imaget2 = wp_get_attachment_url( $attachment_ids[1] );
    $imaget3 = wp_get_attachment_url( $attachment_ids[2] );
    $imaget4 = wp_get_attachment_url( $attachment_ids[3] );
    $imaget5 = wp_get_attachment_url( $attachment_ids[4] );
//create "g:additional_image_link" node under "item"
    $g_additional_image_link = $item_node->appendChild($xml->createElement("g:additional_image_link"));  

    //fill g:additional_image_link node with CDATA content
    $g_additional_image_link_contents = $xml->createCDATASection(htmlentities(''.$imaget1.''));  
    $g_additional_image_link->appendChild($g_additional_image_link_contents);

    //create "g:additional_image_link" node under "item"
    $g_additional_image_link2 = $item_node->appendChild($xml->createElement("g:additional_image_link"));  

    //fill g:additional_image_link node with CDATA content
    $g_additional_image_link_contents2 = $xml->createCDATASection(htmlentities(''.$imaget2.''));  
    $g_additional_image_link2->appendChild($g_additional_image_link_contents2);

    //create "g:additional_image_link" node under "item"
    $g_additional_image_link3 = $item_node->appendChild($xml->createElement("g:additional_image_link"));  

    //fill g:additional_image_link node with CDATA content
    $g_additional_image_link_contents3 = $xml->createCDATASection(htmlentities(''.$imaget3.''));  
    $g_additional_image_link3->appendChild($g_additional_image_link_contents3);

    //create "g:additional_image_link" node under "item"
    $g_additional_image_link4 = $item_node->appendChild($xml->createElement("g:additional_image_link"));  

    //fill g:additional_image_link node with CDATA content
    $g_additional_image_link_contents4 = $xml->createCDATASection(htmlentities(''.$imaget4.''));  
    $g_additional_image_link4->appendChild($g_additional_image_link_contents4);

    //create "g:additional_image_link" node under "item"
    $g_additional_image_link5 = $item_node->appendChild($xml->createElement("g:additional_image_link"));  

    //fill g:additional_image_link node with CDATA content
    $g_additional_image_link_contents5 = $xml->createCDATASection(htmlentities(''.$imaget5.''));  
    $g_additional_image_link5->appendChild($g_additional_image_link_contents5);


//create "g:identifier_exists" node under "item"
      $g_identifier_exists = $item_node->appendChild($xml->createElement("g:identifier_exists",'yes'));  
endwhile;




$content =  $xml->saveXML();

//Save XML as a file
 $data = fopen(plugin_dir_path( __DIR__ )."my-feed/admin/googlefiles/$file_name.xml", "w");
  
fwrite($data, $content);
  
// closing the file
fclose($data);
$wp_fp = $table_prefix.'fp_list';
$q="INSERT INTO $wp_fp (name,type,content) VALUES ('$file_name','xml','$content');";
$wpdb->query($q);
echo "XML Feed Successfully Created"; 
wp_die();
}
//txt format feed
if($_POST['file_type'] == 'txt'){
    $wp_fp = $table_prefix.'fp_list';
    $textfile = "id,title,description,item group id,link,product type,google product category,image link,condition,availability,price,sale price,mpn,brand,canonical link,additional image link,additional image link,additional image link,additional image link,additional image link,identifier exists\n";
$args1 = array('post_type'      => 'product' ,'posts_per_page' => 999);
$loop1 = new WP_Query( $args1 ); 
while ( $loop1->have_posts() ) : $loop1->the_post();
    global $product;
    
     $idtext[] = $product->get_id();
     $titletext[] = $product->get_name();
     $desctext[] = $product->get_description();
     $permalinktext[] = $product->get_permalink();
     $pricetext[] = $product->get_regular_price();
     $availabilitytext = "in_stock";
     $imagetext[] = get_the_post_thumbnail_url($product->get_id()); 
     $product_category[] = strip_tags($product->get_categories(' > '));
     $mpntext[] = $product->get_sku();
   //  $brandtext[] = $product->get_name();
     //$textcontent = $idtext.','.$titletext.','.str_replace(',', '',$desctext).','.$permalinktext.','.$pricetext.' INR,'.$availabilitytext.','.$imagetext.','.$gtintext.','.$mpntext.','.$brandtext.',merge';
     if($product->get_sale_price()>0){
      $pricetexts[] = $product->get_sale_price().' '.get_woocommerce_currency();  
      }else{
        $pricetexts[] = $product->get_regular_price().' '.get_woocommerce_currency();  
      }
     endwhile;
$aer = array();
for ($x = 0; $x <= count($idtext); $x++) {

$product_ided = $idtext[$x];
$productes = new WC_product($product_ided);
$attachment_ids = $productes->get_gallery_image_ids();
$imaget1 = wp_get_attachment_url( $attachment_ids[0] );
$imaget2 = wp_get_attachment_url( $attachment_ids[1] );
$imaget3 = wp_get_attachment_url( $attachment_ids[2] );
$imaget4 = wp_get_attachment_url( $attachment_ids[3] );
$imaget5 = wp_get_attachment_url( $attachment_ids[4] );

  $aer[] =  $idtext[$x].','.trim(preg_replace('/\s\s+/', ' ', str_replace(",", " ", $titletext[$x]))).','.trim(preg_replace('/\s\s+/', ' ', str_replace(",", " ", $desctext[$x]))).','.$idtext[$x].','.$permalinktext[$x].','.$product_category[$x].','.' '.','.$imagetext[$x].','.'new'.','.$availabilitytext.','.$pricetext[$x].' '.get_woocommerce_currency().','.$pricetexts[$x].','.$mpntext[$x].','.sanitize_text_field($_SERVER['HTTP_HOST']).','.$permalinktext[$x].','.$imaget1.','.$imaget2.','.$imaget3.','.$imaget4.','.$imaget5.',yes'."\n";
}
$textcontent = implode($aer);
$textcon = $textfile.''.$textcontent; 

$data = fopen(plugin_dir_path( __DIR__ )."my-feed/admin/googlefiles/$file_name.txt", "w");

// writing content to a file using fwrite() function
fwrite($data, "id,title,description,item group id,link,product type,google product category,image link,condition,availability,price,sale price,mpn,brand,canonical link,additional image link,additional image link,additional image link,additional image link,additional image link,identifier exists");
fwrite($data, "\n");
fwrite($data, $textcontent);
  
// closing the file
fclose($data);

    $q="INSERT INTO $wp_fp (name,type,content) VALUES ('$file_name','txt','$textcon');";
    $wpdb->query($q);
    echo "TXT Feed Successfully Created";
    wp_die(); 
    }

//Csv format feed 
    if($_POST['file_type'] == 'csv'){
    $wp_fp = $table_prefix.'fp_list';
    $textfile = "id,title,description,item group,link,product type,google product category,image_link,condition,availability,price,sale price,mpn,brand,canonical link,additional image link,additional image link,additional image link,additional image link,additional image link,identifier exists\n";
    $args = array('post_type'      => 'product' ,'posts_per_page' => 999);
$loop = new WP_Query( $args );
$idtextt2 = array();
while ( $loop->have_posts() ) : $loop->the_post();
    global $product;
     $idtext[] = $product->get_id();
     $titletext[] = $product->get_name();
     $desctext[] = $product->get_description();
     $permalinktext[] = $product->get_permalink();
     $pricetext[] = $product->get_regular_price();
     $availabilitytext = "in_stock";
     $imagetext[] = get_the_post_thumbnail_url($product->get_id()); 

     $product_category[] = strip_tags($product->get_categories(' > '));
     $mpntext[] = $product->get_sku();
    // $brandtext[] = $product->get_name();
     $idtextt2[] = $product->get_id();
      if($product->get_sale_price()>0){
      $pricetexts[] = $product->get_sale_price().' '.get_woocommerce_currency();  
      }else{
        $pricetexts[] = $product->get_regular_price().' '.get_woocommerce_currency();  
      }
     endwhile;
$aer = array();
for ($x = 0; $x <= count($idtext); $x++) {
$product_ided = $idtext[$x];
$productes = new WC_product($product_ided);
$attachment_ids = $productes->get_gallery_image_ids();
$imaget1 = wp_get_attachment_url( $attachment_ids[0] );
$imaget2 = wp_get_attachment_url( $attachment_ids[1] );
$imaget3 = wp_get_attachment_url( $attachment_ids[2] );
$imaget4 = wp_get_attachment_url( $attachment_ids[3] );
$imaget5 = wp_get_attachment_url( $attachment_ids[4] );

  $aer[] =  $idtext[$x].','.trim(preg_replace('/\s\s+/', ' ', str_replace(",", " ", $titletext[$x]))).','.trim(preg_replace('/\s\s+/', ' ', str_replace(",", " ", $desctext[$x]))).','.$idtext[$x].','.$permalinktext[$x].','.$product_category[$x].','.''.','.$imagetext[$x].','.'new'.','.$availabilitytext.','.$pricetext[$x].' '.get_woocommerce_currency().','.$pricetexts[$x].','.$mpntext[$x].','.sanitize_text_field($_SERVER['HTTP_HOST']).','.$permalinktext[$x].','.$imaget1.','.$imaget2.','.$imaget3.','.$imaget4.','.$imaget5.','.'yes'."\n";
}
$textcontent = implode($aer);
 $textcon = $textfile.''.$textcontent; 

  $data = fopen(plugin_dir_path( __DIR__ )."my-feed/admin/googlefiles/$file_name.csv", "w");
 
// writing content to a file using fwrite() function
fwrite($data, $textcon);
  
// closing the file
fclose($data);



    $q="INSERT INTO $wp_fp (name,type,content) VALUES ('$file_name','csv','$textcon');";
    $wpdb->query($q);
    echo "CSV Feed Successfully Created"; 

    wp_die();
    }

}


