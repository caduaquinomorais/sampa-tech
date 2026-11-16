<?php
/**
 * Layout do site público.
 * @var \SampaTech\Core\View $view
 * @var \SampaTech\View\Render $h
 * @var array $layout conteúdo de cabeçalho/rodapé
 * @var string $content
 * @var string $title
 * @var string $description
 */
defined('SAMPA_ROOT') || exit;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:locale" content="pt_BR">
    <meta name="theme-color" content="#14171F">
    <link rel="preload" href="<?= e(url('/assets/fonts/geist-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="site-header__logo"<?= $h->href($layout['cabecalho']['logo_link']) ?> aria-label="ADE SAMPA — site principal">
            <img src="<?= e(url('/assets/icons/logo-adesampa.svg')) ?>" alt="ADE SAMPA" width="123" height="21">
        </a>
        <a class="site-header__home" href="<?= e(url('/')) ?>">Sampa Tech</a>
    </div>
</header>
<main id="conteudo" tabindex="-1">
<?= $content ?>
</main>
<footer class="site-footer">
    <div class="container site-footer__inner">
        <img src="<?= e(url('/assets/icons/logo-adesampa-branco.svg')) ?>" alt="ADE SAMPA" width="123" height="21">
        <a class="site-footer__link"<?= $h->href($layout['rodape']['site']) ?>><?= e($layout['rodape']['assinatura']) ?></a>
    </div>
</footer>
</body>
</html>
