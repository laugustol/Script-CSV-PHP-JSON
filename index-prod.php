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
    'http://localhost/woocommerce', 
    'ck_91d10c29742999ddde4ee8cb9af71c7162e24d8e', 
    'cs_4480c215d94647c277e3894818540206910c7bcb',
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
//echo "Cantidad de productos: "; print_r($count[0]["count"]);
$length="1000";
$offset="0";


    //*DB*//
    $f = fopen("php://memory","w");
    $filename = "members_" . date('Y-m-d') . ".csv";

    $fields = array('idproducto','alvaro_status','name', 'type', 'regular_price', 'description', 'permalink', 'status', 'featured', 'sku', 'tax_status', 'virtual', 'downloadable', 'stock_quantity', 'weight','length','width','height','short_description','categories','id_attribute1','name_attribute1','position_attribute1','visible_attribute1','variation_attribute1','options_attribute1','id_attribute2','name_attribute2','position_attribute2','visible_attribute2','variation_attribute2','options_attribute2','id_attribute3','name_attribute3','position_attribute3','visible_attribute3','variation_attribute3','options_attribute3','id_attribute4','name_attribute4','position_attribute4','visible_attribute4','variation_attribute4','options_atributte4','images');
    fputcsv($f,$fields,",");
    $db->prepare('SELECT p._id,p.name,p.price,p.description,p.magentolink,p.status,p.sku,p.weight,p.depth,p.width,p.height,p.customattributes,p.referenceprice,p.condition,p.brandid,p.status
        FROM "products" p WHERE p.status=? LIMIT '.$length.' OFFSET '.$offset.' ');
    //Escoger estatos a filtrar
    $db->execute(array("published"));
    $d = $db->fetchAll();
    foreach($d as $k => $val){
        $lineData = array($val["_id"],$val["status"],$val["name"],'simple',$val["price"],$val["description"],str_replace("http://www.sensacional.cl/", "", substr($val["magentolink"],0,(strlen($val["magentolink"])-1))),'publish','false',$val["sku"],'taxable','false','false','1',$val["weight"],$val["depth"],$val["width"],$val["height"],'','','7','Precio Nuevo','0','true','false',$val["referenceprice"],'5','Marca','1','true','false','','6','Condición','2','true','false','','1','Talla','3','true','false','','');

        $db->prepare('SELECT c."magentoId" as cateid FROM "productcategory" pc INNER JOIN "Categories" c ON pc.categoryid=c._id WHERE pc.productid = ?; ');
        $db->execute(array($val["_id"]));
        $d2 = $db->fetchAll();
        foreach($d2 as $k2 => $val2){
            $lineData[19].= $val2["cateid"].",";
        }
        $lineData[19] = rtrim($lineData[19],",");
        $db->prepare('SELECT b.name FROM "brands" b WHERE b._id=?');
        foreach($db->execute(array($val["brandid"])) as $val3){
            $lineData[31].= $val3["name"].",";
        }
        $lineData[31] = rtrim($lineData[31],",");

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
        $lineData[37]=$condition;

        $customattributes=json_decode($val["customattributes"]);    
        $customattributesAUX = explode(":",$val["customattributes"]);
        $customattributes = explode(",",str_replace('"', '', $customattributesAUX[2]));
        $lineData[43]= $customattributes[0];

        $db->prepare('SELECT a.url,a.pos FROM "assets" a WHERE a.type=? AND productid=? ORDER BY a.pos ASC');
        foreach($db->execute(array("official",$val["_id"])) as $k4 => $val4){
            $lineData[44].= $val4["url"].",";
        }
        $lineData[44] = rtrim($lineData[44],",");
        fputcsv($f, $lineData, ",");
    }
    fseek($f, 0);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    fpassthru($f);

?>