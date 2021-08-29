<?php 

    function decode($pData)
    {
        $encryption_key = 'themeluxurydotcom';

        $decryption_iv = '9999999999999999';

        $ciphering = "AES-256-CTR"; 
        
        $pData = str_replace(' ','+', $pData);

        $decryption = openssl_decrypt($pData, $ciphering, $encryption_key, 0, $decryption_iv);

        return $decryption;
    }

    if ( !empty($_GET['token']) ) {

        $token = decode($_GET['token']);

        $deJson = json_decode($token);

		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$deJson->filename.'-'.time().'.'.$deJson->type.'"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . $deJson->size);
		header('Connection: Close');
		ob_clean();
		flush();
		readfile($deJson->url);
		exit;

    } else echo 'Silence is Golden!';



