<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors","on");
//set_time_limit(3000);
set_time_limit(0);
ini_set('max_execution_time',300);
//require __DIR__ . '/vendor/autoload.php';
require './vendor/autoload.php';
define("DRIVER","pgsql");
use Automattic\WooCommerce\Client;
require 'ameliaBD.php';

$woocommerce = new Client(
    'http://localhost:8000/', 
    'ck_3fdf4abd586ec112c51ec3eb2b075a87a5271093', 
    'cs_750a008108f2a4cf65594051e4c83dff87338b65',
    [
        'wp_api' => true,
        'version' => 'wc/v1',
        'timeout' => 300
    ]
);
//*DB*//
$db = new ameliaBD;
$db->prepare('SELECT count(*) FROM "products" p');
$db->execute();
$count = $db->fetchAll();
echo "Cantidad de productos: "; print_r($count[0]["count"]);
$length="20";
$offset="20";
$count[0]["count"]="20";
$json=[];
//while($offset<=$count[0]["count"]){

    $db->prepare('SELECT p._id,p.name,p.price,p.description,p.magentolink,p.status,p.sku,p.weight,p.depth,p.width,p.height,p.customattributes,p.referenceprice,p.condition,p.brandid
        FROM "products" p WHERE p.status=? LIMIT '.$length.' OFFSET '.$offset.' ');
    //Escoger estatos a filtrar
    $db->execute(array("published"));
    $d = $db->fetchAll();
    foreach($d as $k => $val){
        $json[$k]["name"]=$val["name"];
        $json[$k]["type"]="simple";
        $json[$k]["regular_price"]="".(empty($val["price"])? 0 : $val["price"])."";
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
        $json[$k]["dimensions"]["length"]="".$val["depth"]."";
        $json[$k]["dimensions"]["width"]="".$val["width"]."";
        $json[$k]["dimensions"]["height"]="".$val["height"]."";
        $json[$k]["short_description"]="";
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
        
        echo "<br><br><br>";
        print_r($json[$k]);
        echo "<br>aquiiiiiiiii<br>";
        print_r($woocommerce->post('products',$json[$k]));
        echo "<br><br><br>";
        if($offset<=$count[0]["count"]){
            $offset = $offset+$length;
        }
    }
    //*DB*//
//}
/*foreach($json as $k5 => $val5){
    echo "<br><br><br>";
    print_r($val5[$k5]);
    echo "<br>aquiiiiiiiii<br>";
    print_r($woocommerce->post('products',$val5[$k5]));
    echo "<br><br><br>";        
}*/

?>