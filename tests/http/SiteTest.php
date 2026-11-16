<?php

declare(strict_types=1);

use SampaTech\Tests\Server;
use SampaTech\Tests\T;

use function SampaTech\Tests\tmpdir;

$server = null;
$srv = static function () use (&$server): Server {
    return $server ??= new Server();
};

/** Copia o conteúdo para uma pasta temporária, aplicando $edit ao arquivo sampa-tech.php. */
$conteudo = static function (callable $edit): string {
    $dir = tmpdir();
    foreach (['layout', 'erro-404', 'sampa-tech'] as $f) {
        copy(SAMPA_ROOT . "/app/content/$f.php", "$dir/$f.php");
    }
    file_put_contents("$dir/sampa-tech.php", $edit((string) file_get_contents("$dir/sampa-tech.php")));
    return $dir;
};

return [
    'home 200 com o botão do Aceleração Vai Tec' => function () use ($srv): void {
        $r = $srv()->client()->get('/');
        T::same(200, $r->status);
        T::contains('<html lang="pt-BR"', $r->body);
        T::contains('<h1 class="hero__title" id="titulo">Sampa Tech</h1>', $r->body);
        T::contains('Aceleração Vai Tec', $r->body);
        T::matches('#<a class="btn btn--secondary" href="/aceleracao-vai-tec" data-cta="aceleracao-vai-tec">#', $r->body);
        T::matches('#href="/assets/css/site\.css\?v=\d+"#', $r->body);
        T::notContains('Lorem', $r->body);
    },
    'sem o .htaccess da raiz: index.php de reserva aponta os assets para /public/assets (todos 200)' => function (): void {
        $s = new Server(null, true);
        $c = $s->client();
        $r = $c->get('/');
        T::same(200, $r->status);
        T::contains('data-cta="aceleracao-vai-tec"', $r->body);
        preg_match_all('~(?:href|src)="(/[^"#]*)"~', $r->body, $m);
        $assets = array_values(array_unique(array_filter($m[1], fn (string $u) => str_contains($u, '/assets/'))));
        T::true(count($assets) >= 3, 'esperados CSS, fonte e logos');
        foreach ($assets as $u) {
            T::true(str_starts_with($u, '/public/assets/'), "asset fora de /public: $u");
            T::same(200, $c->get($u)->status, $u);
        }
        $s->stop();
    },
    'botão do Vai Tec redireciona (302) para o site oficial' => function () use ($srv): void {
        $r = $srv()->client()->get('/aceleracao-vai-tec');
        T::same(302, $r->status);
        T::same('https://adesampa.com.br/vaitec/', $r->location());
    },
    'link do Vai Tec inválido (não https) → 404, sem redirecionar' => function () use ($conteudo): void {
        $dir = $conteudo(fn (string $s) => str_replace("'https://adesampa.com.br/vaitec/'", "'javascript:alert(1)'", $s));
        $s = new Server($dir);
        $r = $s->client()->get('/aceleracao-vai-tec');
        T::same(404, $r->status);
        T::same('', $r->location());
        $s->stop();
    },
    'visitante não recebe cookie' => function () use ($srv): void {
        T::same([], $srv()->client()->get('/')->setCookies());
        T::same([], $srv()->client()->get('/nao-existe')->setCookies());
    },
    'cabeçalhos de segurança presentes e X-Powered-By ausente' => function () use ($srv): void {
        $r = $srv()->client()->get('/');
        $csp = (string) $r->header('Content-Security-Policy');
        T::matches("/script-src 'self' 'nonce-[A-Za-z0-9_-]{20,}'/", $csp);
        T::contains("frame-ancestors 'none'", $csp);
        T::contains("frame-src 'none'", $csp);
        T::contains("object-src 'none'", $csp);
        T::same('nosniff', $r->header('X-Content-Type-Options'));
        T::same('DENY', $r->header('X-Frame-Options'));
        T::same('strict-origin-when-cross-origin', $r->header('Referrer-Policy'));
        T::true($r->header('Permissions-Policy') !== null);
        T::same(null, $r->header('X-Powered-By'));
        T::same(null, $r->header('Strict-Transport-Security'), 'HSTS só em HTTPS');
    },
    'página 404 personalizada' => function () use ($srv): void {
        $r = $srv()->client()->get('/pagina-que-nao-existe');
        T::same(404, $r->status);
        T::contains('Página não encontrada', $r->body);
        T::notContains(DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR, $r->body);
    },
    'código-fonte não é servido' => function () use ($srv): void {
        $c = $srv()->client();
        foreach (['/app/bootstrap.php', '/app/config/app.php', '/../app/bootstrap.php', '/.htaccess', '/index.php/../../app/bootstrap.php', '/app/content/sampa-tech.php'] as $path) {
            $r = $c->get($path);
            T::true(in_array($r->status, [400, 404], true), "$path → {$r->status}");
            T::notContains('<?php', $r->body);
        }
    },
    'assets estáticos servidos (CSS, fonte, logo)' => function () use ($srv): void {
        $c = $srv()->client();
        T::same(200, $c->get('/assets/css/site.css')->status);
        T::same(200, $c->get('/assets/fonts/geist-latin.woff2')->status);
        T::same(200, $c->get('/assets/icons/logo-adesampa.svg')->status);
    },
    'POST no site retorna 405' => function () use ($srv): void {
        T::same(405, $srv()->client()->post('/', ['a' => 'b'])->status);
    },
    'erro interno: página genérica com código de referência, sem detalhes' => function () use ($conteudo): void {
        $dir = $conteudo(fn () => "<?php return ['quebrado' => true];\n");
        $s = new Server($dir);
        $r = $s->client()->get('/');
        T::same(500, $r->status);
        T::matches('/Código de referência: <strong>[0-9A-F]{8}<\/strong>/', $r->body);
        T::notContains('quebrado', $r->body);
        T::notContains('.php', $r->body);
        $s->stop();
    },
    'conteúdo editado no arquivo aparece escapado (sem XSS)' => function () use ($conteudo): void {
        $dir = $conteudo(fn (string $s) => str_replace("'titulo' => 'O que é o Sampa Tech'", "'titulo' => '<script>alert(1)</script> Sobre'", $s));
        $s = new Server($dir);
        $body = $s->client()->get('/')->body;
        T::contains('&lt;script&gt;alert(1)&lt;/script&gt; Sobre', $body);
        T::notContains('<script>alert(1)', $body);
        $s->stop();
    },
];
