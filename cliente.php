<?php

const API_URL = 'http://localhost/boletim/api/boletim.php';

function chamar_api(
    string $metodo,
    ?int $id = null,
    ?array $dados = null
): array {
    $url = API_URL . ($id !== null ? '?id=' . $id : '');
    $ch = curl_init($url);

    $opcoes = [
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 10,
    ];

    if ($dados !== null) {
        $json = json_encode($dados, JSON_UNESCAPED_UNICODE);

        $opcoes[CURLOPT_POSTFIELDS] = $json;
    }

    curl_setopt_array($ch, $opcoes);
    $corpo = curl_exec($ch);

    if ($corpo === false) {
        $erro = curl_error($ch);
        curl_close($ch);

        return [
            'status' => 0,
            'dados' => ['erro' => 'Falha ao acessar a API: ' . $erro]
        ];
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $status,
        'dados' => json_decode($corpo, true) ?? []
    ];
}

function h(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}