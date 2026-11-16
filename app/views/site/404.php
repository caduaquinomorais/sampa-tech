<?php
/**
 * Página 404.
 * @var \SampaTech\Core\View $view
 * @var array $c conteúdo de "erro-404"
 */
defined('SAMPA_ROOT') || exit;
$view->layout('site/layout', ['title' => $c['titulo'] . ' | Sampa Tech', 'description' => $c['texto']]);
?>
<section class="hero" aria-labelledby="erro-titulo">
    <div class="container hero__inner">
        <p class="tag">Erro 404</p>
        <h1 class="hero__title" id="erro-titulo"><?= e($c['titulo']) ?></h1>
        <p class="hero__lead"><?= e($c['texto']) ?></p>
        <a class="btn btn--primary" href="<?= e(url('/')) ?>"><?= e($c['botao']) ?></a>
    </div>
</section>
