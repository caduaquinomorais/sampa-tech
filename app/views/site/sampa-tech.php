<?php
/**
 * Página inicial do Sampa Tech.
 * @var \SampaTech\Core\View $view
 * @var array $c conteúdo de "sampa-tech"
 * @var \SampaTech\View\Render $h
 */
defined('SAMPA_ROOT') || exit;
$view->layout('site/layout', ['title' => $c['meta']['titulo'], 'description' => $c['meta']['descricao']]);
?>
<section class="hero" aria-labelledby="titulo">
    <div class="container hero__inner">
        <p class="tag"><?= e($c['banner']['tag']) ?></p>
        <h1 class="hero__title" id="titulo"><?= e($c['banner']['titulo']) ?></h1>
        <p class="hero__lead"><?= e($c['banner']['texto']) ?></p>
        <a class="btn btn--primary" href="#vai-tec">Programas</a>
    </div>
</section>

<section class="section" aria-labelledby="sobre-titulo">
    <div class="container section__inner">
        <h2 class="section__title" id="sobre-titulo"><?= e($c['sobre']['titulo']) ?></h2>
        <div class="section__text"><?= $h->paragraphs($c['sobre']['texto']) ?></div>
    </div>
</section>

<section class="section section--destaque" id="vai-tec" aria-labelledby="vai-tec-titulo">
    <div class="container card">
        <p class="tag tag--claro"><?= e($c['vai_tec']['tag']) ?></p>
        <h2 class="section__title" id="vai-tec-titulo"><?= e($c['vai_tec']['titulo']) ?></h2>
        <p class="card__text"><?= e($c['vai_tec']['texto']) ?></p>
        <a class="btn btn--secondary" href="<?= e(url('/aceleracao-vai-tec')) ?>" data-cta="aceleracao-vai-tec"><?= e($c['vai_tec']['botao']) ?></a>
    </div>
</section>
