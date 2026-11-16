<?php

declare(strict_types=1);

namespace SampaTech\View;

defined('SAMPA_ROOT') || exit;

use SampaTech\Security\UrlValidator;

/** Helpers de saída usados pelos templates do site. Toda saída é escapada. */
final class Render
{
    /**
     * Atributos de um link. URL vazia (link pendente) ou inválida → sem href
     * (o item aparece, mas não navega), marcado com data-link-pendente.
     */
    public function href(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !UrlValidator::isAllowed($url)) {
            return ' data-link-pendente="true"';
        }
        $out = ' href="' . e($url) . '"';
        if (UrlValidator::isExternal($url)) {
            $out .= ' rel="noopener noreferrer"';
        }
        return $out;
    }

    /** Texto em parágrafos: linha em branco = novo parágrafo; quebra simples = <br>. Sempre escapado. */
    public function paragraphs(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') {
            return '';
        }
        $out = [];
        foreach (preg_split("/\n{2,}/", $text) ?: [] as $block) {
            $out[] = '<p>' . nl2br(e(trim($block)), false) . '</p>';
        }
        return implode("\n", $out);
    }
}
