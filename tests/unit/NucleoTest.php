<?php

declare(strict_types=1);

use SampaTech\Core\App;
use SampaTech\Http\HttpException;
use SampaTech\Http\Request;
use SampaTech\Http\Router;
use SampaTech\Security\UrlValidator;
use SampaTech\Tests\T;
use SampaTech\View\Render;

return [
    'roteador: parâmetros com regex' => function (): void {
        $r = new Router();
        $r->get('/secao/{slug:[a-z0-9-]{1,40}}', fn () => 'ok');
        T::same(['slug' => 'vai-tec'], $r->match('GET', '/secao/vai-tec')['params']);
    },
    'roteador: 404 para caminho inválido (regex não aceita ../)' => function (): void {
        $r = new Router();
        $r->get('/secao/{slug:[a-z0-9-]{1,40}}', fn () => 'ok');
        T::same(404, T::throws(HttpException::class, fn () => $r->match('GET', '/secao/../../etc'))->status);
        T::same(404, T::throws(HttpException::class, fn () => $r->match('GET', '/secao/VAI'))->status);
    },
    'roteador: 405 com cabeçalho Allow; HEAD usa a rota GET' => function (): void {
        $r = new Router();
        $r->get('/', fn () => 'x');
        $ex = T::throws(HttpException::class, fn () => $r->match('POST', '/'));
        T::same(405, $ex->status);
        T::same('GET', $ex->headers['Allow']);
        T::same([], $r->match('HEAD', '/')['params']);
    },
    'request: detecção do base path (subpasta /sampa-tech)' => function (): void {
        T::same('', Request::detectBasePath('/index.php'));
        T::same('/sampa-tech', Request::detectBasePath('/sampa-tech/public/index.php'));
        T::same('/sampa-tech', Request::detectBasePath('/sampa-tech/index.php'));
        T::same('/', Request::stripBasePath('/sampa-tech', '/sampa-tech'));
        T::same('/x', Request::stripBasePath('/sampa-tech/x', '/sampa-tech'));
        T::same('/x', Request::stripBasePath('/sampa-tech/public/x', '/sampa-tech'));
        T::same('/sampa-techx', Request::stripBasePath('/sampa-techx', '/sampa-tech'));
    },
    'request: assets em /public quando entra pelo index.php da raiz (.htaccess da raiz ignorado)' => function (): void {
        // Reserva: o Apache abre www/sampa-tech/index.php direto.
        $raiz = Request::fromServer([
            'SCRIPT_NAME' => '/sampa-tech/index.php',
            'SCRIPT_FILENAME' => SAMPA_ROOT . '/index.php',
            'REQUEST_URI' => '/sampa-tech/',
        ]);
        T::same('/sampa-tech', $raiz->basePath);
        T::same('/', $raiz->path);
        T::same('/public', $raiz->publicPrefix);

        // Normal: o .htaccess da raiz encaminhou para public/index.php.
        $normal = Request::fromServer([
            'SCRIPT_NAME' => '/sampa-tech/public/index.php',
            'SCRIPT_FILENAME' => SAMPA_ROOT . '/public/index.php',
            'REQUEST_URI' => '/sampa-tech/',
        ]);
        T::same('/sampa-tech', $normal->basePath);
        T::same('', $normal->publicPrefix);

        // url(): só /assets/ recebe o prefixo; o botão do Vai Tec não.
        $app = new App();
        @$app->handle($raiz); // @: no CLI os cabeçalhos já foram enviados; só interessa o estado do App.
        T::same('/sampa-tech/public/assets/css/site.css', url('/assets/css/site.css'));
        T::same('/sampa-tech/', url('/'));
        T::same('/sampa-tech/aceleracao-vai-tec', url('/aceleracao-vai-tec'));
        @$app->handle($normal);
        T::same('/sampa-tech/assets/css/site.css', url('/assets/css/site.css'));
    },
    'e(): escapa aspas simples, duplas e tags' => function (): void {
        T::same('&lt;script&gt;alert(&apos;x&apos;)&lt;/script&gt; &quot;a&quot; &amp;', e("<script>alert('x')</script> \"a\" &"));
        T::same('', e(['array']));
    },
    'URLs: só http, https, mailto, tel e âncoras' => function (): void {
        foreach (['https://adesampa.com.br/vaitec/', 'http://exemplo.com', 'mailto:contato@adesampa.com.br', 'tel:+551130000000', '#vai-tec'] as $ok) {
            T::true(UrlValidator::isAllowed($ok), $ok);
        }
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', ' javascript:alert(1)', "java\tscript:alert(1)", 'data:text/html,<b>', 'vbscript:x',
                  'file:///etc/passwd', '//evil.com', 'https://user:pass@evil.com', 'https://', '#', '#1abc', 'mailto:naoemail', 'ftp://x.com'] as $bad) {
            T::false(UrlValidator::isAllowed($bad), "deveria recusar: $bad");
        }
    },
    'render: link vazio ou perigoso fica sem href; externo ganha rel=noopener' => function (): void {
        $h = new Render();
        T::same(' data-link-pendente="true"', $h->href(''));
        T::same(' data-link-pendente="true"', $h->href('javascript:alert(1)'));
        T::same(' href="#vai-tec"', $h->href('#vai-tec'));
        T::same(' href="https://a.com/?x=1&amp;y=&quot;2&quot;" rel="noopener noreferrer"', $h->href('https://a.com/?x=1&y="2"'));
    },
    'render: parágrafos sempre escapados' => function (): void {
        $h = new Render();
        T::same("<p>a &lt;b&gt;<br>\nc</p>\n<p>d</p>", $h->paragraphs("a <b>\nc\n\nd"));
        T::same('', $h->paragraphs("  \n "));
    },
    'conteúdo: botão do Aceleração Vai Tec aponta para https' => function (): void {
        $c = (new App())->content('sampa-tech');
        T::same('Aceleração Vai Tec', $c['vai_tec']['titulo']);
        T::true(str_starts_with($c['vai_tec']['link'], 'https://'), 'link do Vai Tec precisa ser https');
        T::true(UrlValidator::isAllowed($c['vai_tec']['link']));
    },
    'conteúdo: nome de arquivo com caminho é recusado' => function (): void {
        T::throws(InvalidArgumentException::class, fn () => (new App())->content('../config/app'));
    },
];
