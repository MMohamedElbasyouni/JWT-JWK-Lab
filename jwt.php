<?php
/**
 * Minimal JWT (RS256) helper for the lab.
 * NOTE: verify() is INTENTIONALLY vulnerable to JWK header injection.
 */

function b64url_encode($data){
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function b64url_decode($data){
    $pad = strlen($data) % 4;
    if ($pad) $data .= str_repeat('=', 4 - $pad);
    return base64_decode(strtr($data, '-_', '+/'));
}

// Build a JWK (public key components) from a PEM public key
function pem_to_jwk($pemPublicKey){
    $res = openssl_pkey_get_public($pemPublicKey);
    $details = openssl_pkey_get_details($res);
    $n = b64url_encode($details['rsa']['n']);
    $e = b64url_encode($details['rsa']['e']);
    return [
        'kty' => 'RSA',
        'n'   => $n,
        'e'   => $e,
        'alg' => 'RS256',
        'use' => 'sig',
        'kid' => 'lab-key-1'
    ];
}

// Rebuild a usable public key resource from a JWK array (n, e)
function jwk_to_pem($jwk){
    $n = b64url_decode($jwk['n']);
    $e = b64url_decode($jwk['e']);

    // Build an ASN.1 DER SubjectPublicKeyInfo structure for an RSA public key
    $modulus  = "\x00" . $n; // prepend 0x00 to keep it positive
    $exponent = $e;

    $der_len = function($len){
        if ($len < 0x80) return chr($len);
        $bytes = ltrim(pack('N', $len), "\x00");
        return chr(0x80 | strlen($bytes)) . $bytes;
    };

    $mod_der = "\x02" . $der_len(strlen($modulus)) . $modulus;
    $exp_der = "\x02" . $der_len(strlen($exponent)) . $exponent;
    $seq     = $mod_der . $exp_der;
    $rsaPub  = "\x30" . $der_len(strlen($seq)) . $seq;

    // RSA algorithm identifier OID + BIT STRING wrapper (SubjectPublicKeyInfo)
    $algId = pack('H*', '300d06092a864886f70d0101010500'); // rsaEncryption
    $bitStr = "\x03" . $der_len(strlen($rsaPub) + 1) . "\x00" . $rsaPub;
    $spkiSeq = $algId . $bitStr;
    $spki = "\x30" . $der_len(strlen($spkiSeq)) . $spkiSeq;

    $pem = "-----BEGIN PUBLIC KEY-----\n" .
           chunk_split(base64_encode($spki), 64, "\n") .
           "-----END PUBLIC KEY-----\n";
    return $pem;
}

function jwt_sign($payload, $privateKeyPath){
    $header = [
        'typ' => 'JWT',
        'alg' => 'RS256',
        'kid' => 'lab-key-1'
    ];
    $segments = [];
    $segments[] = b64url_encode(json_encode($header));
    $segments[] = b64url_encode(json_encode($payload));
    $signingInput = implode('.', $segments);

    $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
    $signature = '';
    openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

    $segments[] = b64url_encode($signature);
    return implode('.', $segments);
}

/**
 * VULNERABLE verify: if the token header carries an embedded "jwk"
 * object, the server trusts it and verifies the signature using the
 * key material supplied by the CLIENT, instead of always using its
 * own trusted public key. This is a real-world JWT misconfiguration
 * (CWE-347 / JWK header injection).
 */
function jwt_verify($jwt, $trustedPublicKeyPath){
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return null;
    list($headerB64, $payloadB64, $sigB64) = $parts;

    $header  = json_decode(b64url_decode($headerB64), true);
    $payload = json_decode(b64url_decode($payloadB64), true);
    $signature = b64url_decode($sigB64);
    $signingInput = $headerB64 . '.' . $payloadB64;

    if (!$header || strtoupper($header['alg']) !== 'RS256') return null;

    if (isset($header['jwk'])) {
        // --- VULNERABLE PATH ---
        // Trusts whatever public key the token itself provides.
        $pem = jwk_to_pem($header['jwk']);
        $pubKey = openssl_pkey_get_public($pem);
    } else {
        // Correct path: always use the server's own trusted key.
        $pubKey = openssl_pkey_get_public(file_get_contents($trustedPublicKeyPath));
    }

    if (!$pubKey) return null;

    $ok = openssl_verify($signingInput, $signature, $pubKey, OPENSSL_ALGO_SHA256);
    if ($ok !== 1) return null;

    return $payload;
}
