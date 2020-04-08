<?php

// Versão funcional com a integração SISDIA. 
// Se por acaso for necessárias muitas mudanças para funcionar com outras integrações, 
// talvez seja melhor duplicar e criar outro arquivo para tal

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

$_POST = json_decode(file_get_contents("php://input"), true);

$headers_received = array();

foreach (getallheaders() as $name => $value) {
	if ($name == 'Authorization' || $name == 'Content-Type' || $name == 'Accept' || $name == 'Content-type' || $name == 'x-access-token'){
    	array_push($headers_received, $name . ': '. $value);
    }
}

// header("Content-Type: text/html");
// echo '<pre>';
// var_dump($_POST);
// var_dump($headers_received);
// echo '</pre>';
// die();

// Checa se existe parâmetro URL e se é uma URL válida
if (!isset($_GET) || !isset($_GET['url'])){
 $resposta['error'] = 'Not a valid URL!';
 echo json_encode($resposta);
 die();
}

$url = str_replace("/proxy/redirect.php?url=", "", $_SERVER['REQUEST_URI']);

// Inicia buffer para armazenar outputs ao invés de já printar a resposta
ob_start();

if (!isset($_POST) || sizeof($_POST) == 0){ // GET Request

    $ch = curl_init($url);

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_received);

	//curl_setopt($ch, CURLOPT_RETURNTRASFER, 1);

	// Função para obter headers
	curl_setopt($ch, CURLOPT_HEADERFUNCTION,
  		function($curl, $header) use (&$response_headers)
  		{
			
    		$len = strlen($header);
    		$header = explode(':', $header, 2);
    		if (count($header) < 2) // ignore invalid headers
      			return $len;

    		$response_headers[strtolower(trim($header[0]))][] = trim($header[1]);

    		return $len;
  		}
	);

	// Faz a chamada à URL
	curl_exec($ch);

} else { // POST Request

	$ch = curl_init($url);

	// Função para obter headers
	curl_setopt($ch, CURLOPT_HEADERFUNCTION,
  		function($curl, $header) use (&$response_headers)
  		{
    		$len = strlen($header);
    		$header = explode(':', $header, 2);
    		if (count($header) < 2) // ignore invalid headers
      			return $len;

    		$response_headers[strtolower(trim($header[0]))][] = trim($header[1]);

    		return $len;
  		}
	);

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($_POST));
//	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// $headers = [
	// 'Content-Type: application/json; charset=utf-8',
	// 'Accept: application/json'
	// ];

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_received);

	// Faz a chamada à URL
	curl_exec($ch);

}

// Se existir um Header content-type, transmite-o como resposta
if (isset($response_headers) && isset($response_headers["content-type"]) && sizeof($response_headers["content-type"]) > 0 && isset($response_headers["content-type"][0])){
	header('Content-Type: '.$response_headers["content-type"][0]);
}

// Já setamos todos os headers necessários, então printamos o que estava no buffer
$output = ob_get_contents();
ob_clean();
echo $output;

die();