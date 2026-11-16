# Sampa Tech | ADE SAMPA

Página do **Sampa Tech**, com o botão de redirecionamento para o programa **Aceleração Vai Tec**.

- **No ar:** https://oranilom.com.br/sampa-tech/
- **Stack:** PHP 8.1+ puro, sem Composer, banco de dados ou build. Front controller em [public/index.php](public/index.php).
- **Deploy:** automático, por GitHub Actions + FTPS na KingHost, depois de os testes passarem no `main`. Ver [docs/DEPLOY.md](docs/DEPLOY.md).

## Estrutura

| Pasta/arquivo | Conteúdo | Vai para o servidor? |
|---|---|---|
| `app/content/` | **Textos da página** (edite aqui) | sim (bloqueado para acesso web) |
| `app/views/` | HTML (templates PHP) | sim (bloqueado) |
| `app/src/` | Núcleo: roteamento, segurança, base path | sim (bloqueado) |
| `public/` | Front controller, CSS, fontes e logos | sim |
| `.htaccess`, `index.php` | Encaminham tudo para `public/` (o `index.php` é a reserva caso o `.htaccess` seja ignorado) | sim |
| `tests/`, `bin/`, `docs/`, `.github/`, `*.md` | Testes, scripts, documentação e CI | **não** |

## Rodar localmente

```bash
bin/serve.sh            # http://localhost:8080/
bin/php.sh tests/run.php
```

## Rotas

| Caminho | Resposta |
|---|---|
| `/` | Página do Sampa Tech |
| `/aceleracao-vai-tec` | Redireciona (302) para o endereço em `vai_tec.link` (`app/content/sampa-tech.php`) |
| qualquer outro | Página 404 do site |

## Como mudar algo

Nunca direto no `main`: crie um branch, abra um Pull Request, espere os testes (✅ `PHP 8.1` e `PHP 8.3`) e faça o merge. O deploy acontece sozinho. Passo a passo e rollback: [docs/DEPLOY.md](docs/DEPLOY.md#dia-a-dia).

Pendências de conteúdo: [docs/PENDENCIAS.md](docs/PENDENCIAS.md).
