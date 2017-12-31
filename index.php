<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors","on");
//require __DIR__ . '/vendor/autoload.php';
require './vendor/autoload.php';
define("DRIVER","pgsql");
use Automattic\WooCommerce\Client;
require 'ameliaBD.php';

$woocommerce = new Client(
    'http://localhost/woocommerce', 
    'ck_91d10c29742999ddde4ee8cb9af71c7162e24d8e', 
    'cs_4480c215d94647c277e3894818540206910c7bcb',
    [
        'wp_api' => true,
        'version' => 'wc/v1',
    ]
);
//*DB*//
$db = new ameliaBD;
$db->prepare('SELECT count(*) FROM "products" p');
$db->execute();
$count = $db->fetchAll();
echo "Cantidad de productos: "; print_r($count[0]["count"]);
$length="2";
$offset="0";
$count[0]["count"]="20";
while($offset<=$count[0]["count"]){


    $db->prepare('SELECT p._id,p.name,p.price,p.description,p.magentolink,p.status,p.sku,p.weight,p.depth,p.width,p.height,p.customattributes,p.referenceprice,p.condition,p.brandid
        FROM "products" p /*WHERE p.status=?*/ LIMIT '.$length.' OFFSET '.$offset.' ');
    //Escoger estatos a filtrar
    $db->execute(/*array("payed")*/);
    $d = $db->fetchAll();

    //Primeros datos originales de la bd
    //echo strip_tags(json_encode($d));
    foreach($d as $k => $val){
        $json[$k]["name"]=$val["name"];
        $json[$k]["type"]="simple";
        $json[$k]["regular_price"]="".$val["price"]."";
        $json[$k]["description"]=$val["description"];
        $json[$k]["permalink"]=str_replace("http://www.sensacional.cl/", "", substr($val["magentolink"],0,(strlen($val["magentolink"])-1)));// de otra tabla
        $json[$k]["status"]="publish";//$val["status"];
        $json[$k]["featured"]=false;
        $json[$k]["sku"]=$val["sku"];
        $json[$k]["tax_status"]="taxable";
        $json[$k]["virtual"]=false;
        $json[$k]["downloadable"]=false;
        $json[$k]["stock_quantity"]=1;
        $json[$k]["weight"]=$val["weight"];
        $json[$k]["dimensions"]["length"]=$val["depth"];
        $json[$k]["dimensions"]["width"]=$val["width"];
        $json[$k]["dimensions"]["height"]=$val["height"];
        $json[$k]["short_description"]="";
        $json[$k]["categories"][$k]["id"] = $val["categoryid"];
        $db->prepare('SELECT * FROM "productcategory" WHERE productid = ?; ');
        $db->execute(array($val["_id"]));
        $d2 = $db->fetchAll();
        foreach($d2 as $k2 => $val2){
            $json[$k]["categories"][$k2]["id"] = $val2["categoryid"];
        }
        $json[$k]["attributes"][0]["id"]=7;
        $json[$k]["attributes"][0]["name"]="Precio Nuevo";
        $json[$k]["attributes"][0]["position"]=0;
        $json[$k]["attributes"][0]["visible"]=true;
        $json[$k]["attributes"][0]["variation"]=false;
        $json[$k]["attributes"][0]["options"][0]="".$val["referenceprice"]."";
        /*"options": [
            "Products.referencePrice"
        ]*/
        $json[$k]["attributes"][1]["id"]=5;
        $json[$k]["attributes"][1]["name"]="Marca";
        $json[$k]["attributes"][1]["position"]=1;
        $json[$k]["attributes"][1]["visible"]=true;
        $json[$k]["attributes"][1]["variation"]=false;
        $db->prepare('SELECT b.name FROM "brands" b WHERE b._id=?');
        foreach($db->execute(array($val["brandid"])) as $val3){
            $json[$k]["attributes"][1]["options"][0]=$val3["name"]; 
        }
        /*"options": [
            "Products.BrandId (y retornar el nombre)"
        ]*/
        $json[$k]["attributes"][2]["id"]=6;
        $json[$k]["attributes"][2]["name"]="Condición";
        $json[$k]["attributes"][2]["position"]=2;
        $json[$k]["attributes"][2]["visible"]=true;
        $json[$k]["attributes"][2]["variation"]=false;
        $condition="";
        if($val["condition"]=="sensacional"){
            $condition = "Sensacional";
        }else if($val["condition"]=="casi_sensacional"){
            $condition = "Casi Sensacional";
        }else if($val["condition"]=="aceptable"){
            $condition = "Aceptable";
        }else if($val["condition"]=="ultima_vida"){
            $condition = "Última Vida";
        }else if($val["condition"]=="pequeños_detalles"){
            $condition = "Casi Sensacional";
        }else if($val["condition"]=="como_nuevo"){
            $condition = "Sensacional";
        }
        $json[$k]["attributes"][2]["options"][]=$condition;
        /*"options": [
            "Product.condition
            si es sensacional, poner Sensacional 
            si es casi_sensacional, poner Casi Sensacional 
            si es aceptable, poner Aceptable 
            si es ultima_vida, poner Última Vida
            si es pequeños_detalles, poner Casi Sensacional  
            si es nuevo, poner Sensacional
            si es como_nuevo, poner Sensacional
            "
        ]*/
        $json[$k]["attributes"][3]["id"]=1;
        $json[$k]["attributes"][3]["name"]="Talla";
        $json[$k]["attributes"][3]["position"]=3;
        $json[$k]["attributes"][3]["visible"]=true;
        $json[$k]["attributes"][3]["variation"]=false;
        

        $customattributes=json_decode($val["customattributes"]);    
        $customattributesAUX = explode(":",$val["customattributes"]);
        $customattributes = explode(",",str_replace('"', '', $customattributesAUX[2]));

        $json[$k]["attributes"][3]["options"][]=$customattributes[0];
        /*"options": [
            "L"
            si es categoría Ropa, en Product.customAttributes agregar la "Talla"
        ]*/
        $db->prepare('SELECT a.url,a.pos FROM "assets" a WHERE a.type=? AND productid=?');
        foreach($db->execute(array("official",$val["_id"])) as $k4 => $val4){
            $json[$k]["images"][$k4]["src"]=$val4["url"];
            $json[$k]["images"][$k4]["position"]=$val4["pos"];
        }
        //Permitira enviar de uno en uno
        //echo "<br><br><br>";
        //print_r($woocommerce->post('products',$json));
        //echo "<br><br><br>";
        if($offset<=$count[0]["count"]){
            $offset = $offset+$length;
        }
        echo "<br><br><br>";
        echo strip_tags(json_encode($json[$k]));
    }
    //*DB*//
}


?>