<?php

declare(strict_types=1);

namespace SampaTech\Http\Controller;

defined('SAMPA_ROOT') || exit;

use SampaTech\Core\App;
use SampaTech\Http\HttpException;
use SampaTech\Http\Request;
use SampaTech\Http\Response;
use SampaTech\Security\UrlValidator;
use SampaTech\View\Render;

/** Site público do Sampa Tech (sem sessão e sem cookies). */
final class SiteController
{
    public function __construct(private readonly App $app)
    {
    }

    public function home(Request $request): Response
    {
        $html = $this->app->view->render('site/sampa-tech', $this->shared() + ['c' => $this->app->content('sampa-tech')]);
        $response = Response::html($html);
        $response->header('Cache-Control', 'no-cache');
        return $response;
    }

    /**
     * Botão "Aceleração Vai Tec": redireciona (302) para o endereço oficial do programa,
     * definido em app/content/sampa-tech.php (vai_tec.link). Só aceita https.
     */
    public function vaiTec(Request $request): Response
    {
        $link = trim((string) ($this->app->content('sampa-tech')['vai_tec']['link'] ?? ''));
        if (!str_starts_with(strtolower($link), 'https://') || !UrlValidator::isAllowed($link)) {
            throw HttpException::notFound();
        }
        return new Response('', 302, ['Location' => $link, 'Cache-Control' => 'no-cache']);
    }

    public function renderNotFound(): string
    {
        return $this->app->view->render('site/404', $this->shared() + ['c' => $this->app->content('erro-404')]);
    }

    private function shared(): array
    {
        return ['h' => new Render(), 'layout' => $this->app->content('layout')];
    }
}
