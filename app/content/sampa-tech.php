<?php

/*
 * Textos da página do Sampa Tech. Edite aqui; o HTML fica em app/views/site/sampa-tech.php.
 * Todos os textos são exibidos escapados (não aceitam HTML).
 *
 * ATENÇÃO: textos PROVISÓRIOS, escritos só para estruturar a página.
 * Substituir pelos textos oficiais (ver docs/PENDENCIAS.md).
 */

defined('SAMPA_ROOT') || exit;

return [
    'meta' => [
        'titulo' => 'Sampa Tech | ADE SAMPA',
        'descricao' => 'Sampa Tech: iniciativas da ADE SAMPA para negócios de tecnologia e inovação na cidade de São Paulo.',
    ],

    'banner' => [
        'tag' => 'Tecnologia e inovação',
        'titulo' => 'Sampa Tech',
        'texto' => 'Iniciativas da ADE SAMPA para fortalecer negócios de tecnologia e inovação em toda a cidade de São Paulo.',
    ],

    'sobre' => [
        'titulo' => 'O que é o Sampa Tech',
        'texto' => "O Sampa Tech reúne as ações da ADE SAMPA voltadas a empreendedores e startups de base tecnológica.\n\nAqui você encontra os programas disponíveis e os caminhos para participar.",
    ],

    // Botão de redirecionamento: a página aponta para /aceleracao-vai-tec, que redireciona (302) para "link".
    // Assim, se o endereço do programa mudar, basta trocar "link" aqui.
    'vai_tec' => [
        'tag' => 'Programa de aceleração',
        'titulo' => 'Aceleração Vai Tec',
        'texto' => 'Programa de aceleração de negócios de tecnologia da ADE SAMPA, com atenção especial a empreendedores de todas as regiões da cidade.',
        'botao' => 'Conheça o Aceleração Vai Tec',
        'link' => 'https://adesampa.com.br/vaitec/',
    ],
];
