# Deploy na KingHost

> **Situação (16/11/2026):** em produção em **https://oranilom.com.br/sampa-tech/**, publicado e conferido pelo próprio pipeline (execução "Merge pull request #1": `PHP 8.1`, `PHP 8.3` e `Deploy KingHost (FTPS)` verdes). Verificação manual no mesmo dia: a página, o CSS, as fontes e os logos respondem 200 a partir de `/sampa-tech/assets/…` (o `.htaccess` da raiz está ativo); o botão do Aceleração Vai Tec redireciona (302) para `https://adesampa.com.br/vaitec/`; `app/`, as configurações e `.htaccess` respondem 403; `docs/`, `tests/`, `bin/`, `.git` e `.ftp-deploy-sync-state.json` respondem 404; o 404 personalizado está ativo; os cabeçalhos CSP, HSTS e X-Frame-Options estão presentes. O ruleset do `main` está ativo.

O site não precisa de banco, instalação, diretório de dados, extensões PHP extras nem tarefas agendadas. Publicar é só copiar arquivos.

## Visão geral

```
branch ──► Pull Request ──► testes (PHP 8.1 e 8.3) ──► merge no main ──► testes ──► deploy FTPS ──► confere o site
                            ✘ merge bloqueado                                       ✘ nada é publicado
```

| Peça | Onde | O que faz |
|---|---|---|
| Testes | jobs `PHP 8.1` e `PHP 8.3` em [.github/workflows/testes.yml](../.github/workflows/testes.yml) | `php -l` em todos os arquivos + `php tests/run.php` (unitários e HTTP). Rodam em todo PR e em todo push no `main`. |
| Deploy | job `Deploy KingHost (FTPS)`, no mesmo arquivo | Só no `main` (push ou botão *Run workflow*) e só se os testes passarem. Envia por **FTPS** para `www/sampa-tech` e confere o site, o CSS e o botão do Aceleração Vai Tec. |
| Hospedagem | KingHost, servidor `web1160.kinghost.net`, Apache + PHP-FPM | Serve `www/sampa-tech`. O `.htaccess` da raiz encaminha tudo para `public/` e bloqueia o código. |

**Não é usado:** o *Git Webhook* do painel da KingHost (ver [Histórico](#histórico-por-que-não-o-git-webhook)).

## O que o deploy faz, passo a passo

1. **Completa a cadeia do certificado FTPS.** O FTP da KingHost envia só o próprio certificado, sem o intermediário da GlobalSign. O workflow baixa o intermediário do endereço oficial gravado no certificado (campo AIA), converte de DER para PEM, valida a cadeia com `openssl verify` e entrega o resultado ao `curl` (`FTP_CA_BUNDLE`) e ao Node (`NODE_EXTRA_CA_CERTS`). A verificação TLS **continua ligada**.
2. **Localiza a pasta do site.** Faz login, lista a raiz e testa `FTP_DIR` (se existir), `/sampa-tech/`, `/www/sampa-tech/`, `/public_html/sampa-tech/` e `/`. **Só aceita uma pasta que já tenha `app/` e `public/`**, para nunca publicar por cima de outro site. O resultado aparece como anotação no resumo da execução, legível sem abrir o log.
3. **Pausa 20 s**, porque a KingHost limita conexões seguidas do mesmo IP.
4. **Envia** com `SamKirkland/FTP-Deploy-Action@v4.3.5` para **`./sampa-tech/`** (caminho relativo, ver [Estrutura](#estrutura-no-servidor)). Envia só `app/`, `public/`, `index.php` e `.htaccess` e ignora `.github/`, `bin/`, `docs/`, `tests/`, `*.md` e os arquivos do Git. Transfere só o que mudou (o estado fica em `.ftp-deploy-sync-state.json`, no servidor) e apaga do servidor apenas arquivos que ele mesmo enviou.
5. **Se a conexão cair**, espera 1 minuto e tenta mais uma vez.
6. **Confere o site:** `https://oranilom.com.br/sampa-tech/` precisa responder 200, o CSS referenciado na página também, e o botão do **Aceleração Vai Tec** precisa estar na página e redirecionar (302) para um endereço `https://`.

## Configuração

### Segredos (aba *Secrets*): obrigatórios

Cadastre em [Settings → Secrets and variables → Actions → **Secrets**](https://github.com/caduaquinomorais/sampa-tech/settings/secrets/actions/new):

| Nome | Valor |
|---|---|
| `FTP_USERNAME` | usuário FTP principal da conta |
| `FTP_PASSWORD` | senha desse usuário |

> ⚠️ **A senha vai só em *Secrets*.** Na aba *Variables*, o valor fica visível em texto puro. Se isso acontecer, apague a variável e **troque a senha** no painel da KingHost. Nunca envie a senha por chat, e-mail ou print.

### Variáveis (aba *Variables*): opcionais, normalmente **não devem existir**

| Nome | Padrão | Quando criar |
|---|---|---|
| `FTP_SERVER` | `web1160.kinghost.net` | Se a KingHost mover a conta. Descubra o novo nome com `nslookup ftp.oranilom.com.br` (é um CNAME). **Não use `ftp.oranilom.com.br`**: o certificado só vale para `*.kinghost.net`. |
| `FTP_DIR` | descoberta automática | Só se a pasta mudar de nome. Escreva relativo à pasta onde o FTP abre (ex.: `sampa-tech/`). A pasta continua precisando ter `app/` e `public/`. |
| `FTP_PROTOCOL` | `ftps` | Não usar outro valor: `ftp` manda a senha sem criptografia. |

### Ambiente `producao`

É criado automaticamente pelo job de deploy, e o link do ambiente aponta para o site.

### Proteção do `main` (ruleset)

Em *Settings → Rules → Rulesets → New branch ruleset*:

- **Enforcement status:** Active; **Bypass list:** vazia; **Target:** default branch.
- ✅ Restrict deletions · ✅ Block force pushes
- ✅ Require a pull request before merging (0 aprovações, se houver uma pessoa só)
- ✅ Require status checks to pass → `PHP 8.1` e `PHP 8.3`

O repositório é **público** porque, no plano gratuito, rulesets só são aplicados em repositórios públicos. O código não tem segredos: a varredura do histórico (`git log --all -p`) atrás de senhas, tokens, chaves, `.env`, bancos e backups não encontrou nada em 16/11/2026.

## Estrutura no servidor

O FTP da conta **abre dentro de `www`**, mas `www` **não é a raiz `/`** do FTP. Por isso o destino é `./sampa-tech/` (relativo). `/sampa-tech/` (absoluto) aponta para **fora** da `www`: a action diz que deu certo, mas o site não enxerga os arquivos.

```
/home/<conta>/www/          ← onde o FTP abre (aparece como "pasta atual", não como "/")
├── .htaccess               (do site principal)
└── sampa-tech/             ← destino do deploy (./sampa-tech/)
    ├── .htaccess           encaminha tudo para public/ e bloqueia app/, bin/, docs/, tests/ e ocultos
    ├── index.php           reserva: só é usado se o .htaccess acima for ignorado
    ├── app/                código e conteúdo (bloqueado também por app/.htaccess)
    ├── public/             front controller + assets
    └── .ftp-deploy-sync-state.json   estado do deploy (não é servido)
```

O site detecta o caminho `/sampa-tech` sozinho (base path pelo `SCRIPT_NAME`). Se a hospedagem ignorar o `.htaccess` da raiz, o [index.php](../index.php) de reserva serve o site e aponta os arquivos estáticos para `/sampa-tech/public/assets/…`. Os testes cobrem os dois cenários. Se um proxy mudar os caminhos, crie no servidor `app/config/app.local.php` com `<?php return ['base_path' => '/sampa-tech'];`. Esse arquivo fica fora do Git, e o deploy não o apaga.

> Não use `php_value`/`php_flag` em nenhum `.htaccess`: no PHP-FPM da KingHost, isso causa erro 500.

## Dia a dia

```bash
git switch main && git pull
git switch -c ajuste/descricao-curta
# edite e rode os testes: bin/php.sh tests/run.php
git add -A && git commit -m "conteudo: descreve a mudanca"
git push -u origin ajuste/descricao-curta
```

No GitHub: **Compare & pull request → Create pull request** → esperar ✅ em `PHP 8.1` e `PHP 8.3` → **Merge pull request → Confirm merge**. Acompanhe em *Actions*. O job **Deploy KingHost (FTPS)** só fica verde depois de conferir o site no ar.

**Publicar de novo sem mudar código:** *Actions → Testes → Run workflow → branch `main`*.

**Textos da página:** [app/content/sampa-tech.php](../app/content/sampa-tech.php). O endereço do botão do Vai Tec é o campo `vai_tec.link`, e a página aponta para `/sampa-tech/aceleracao-vai-tec`, que redireciona para ele.

### Desfazer (rollback)

No PR que causou o problema, clique em **Revert** e mescle o PR de reversão. O deploy publica a versão anterior. Pelo terminal:

```bash
git switch -c reverte/problema origin/main
git revert <hash-do-merge> -m 1
git push -u origin reverte/problema       # e abra o PR
```

Não use `git push --force` no `main`, nem envie arquivos manualmente por FTP para a pasta do site.

## Checklist de conferência

O deploy já confere o essencial (página, CSS e botão do Vai Tec). Depois de mexer em `.htaccess`, no servidor ou na pasta, confira também:

- [ ] `/sampa-tech/` abre com fontes e logos, e o CSS vem de `/sampa-tech/assets/…`. Se vier de `/sampa-tech/public/assets/…`, o `.htaccess` da raiz está sendo ignorado (ver diagnóstico).
- [ ] O botão **Conheça o Aceleração Vai Tec** leva a `https://adesampa.com.br/vaitec/`.
- [ ] `/sampa-tech/app/content/sampa-tech.php`, `/sampa-tech/app/config/app.php` e `/sampa-tech/.htaccess` → **403/404**.
- [ ] `/sampa-tech/docs/DEPLOY.md`, `/sampa-tech/tests/run.php`, `/sampa-tech/.git/config` e `/sampa-tech/.ftp-deploy-sync-state.json` → **404**.
- [ ] `/sampa-tech/nao-existe` → página 404 do site.
- [ ] Cabeçalhos `Content-Security-Policy`, `Strict-Transport-Security` e `X-Frame-Options` presentes.

## Diagnóstico: sintoma → causa → solução

Todos estes casos aconteceram de verdade nesse servidor (projeto Teia, 13 a 16/11/2026), e o workflow atual já trata os que dependem dele.

| Sintoma | Causa | Solução |
|---|---|---|
| `curl: (60) … unable to get local issuer certificate` | O FTP da KingHost não envia o certificado intermediário. Funciona no Windows, que busca o intermediário sozinho, e falha no Linux. | Etapa "Completar a cadeia do certificado FTPS". **Não** desligue a verificação TLS. |
| Erro de certificado com `ftp.oranilom.com.br` | O certificado é `*.kinghost.net`. | Use `web1160.kinghost.net` (padrão do workflow). |
| `curl: (9) Server denied you to change to the given directory`, e a raiz do FTP mostra só `.htaccess` | A pasta foi criada pelo **Git Webhook** da KingHost, com outro dono, e fica invisível e inacessível ao usuário FTP (`550 denied`). | Apagar a pasta antiga pelo gerenciador de arquivos do painel e recriar `sampa-tech/app` e `sampa-tech/public` pelo FTP ou pelo painel. |
| "Nenhuma pasta com app/ e public/ do Sampa Tech encontrada" | A pasta não existe ou não tem a assinatura do projeto. | Criar `sampa-tech/app` e `sampa-tech/public`. O deploy nunca publica numa pasta sem elas. |
| Deploy verde no envio, mas o site continua vazio ou 403 | `server-dir` absoluto (`/sampa-tech/`) aponta para fora da `www`. | Usar o caminho relativo `./sampa-tech/` (já é o padrão). |
| `ETIMEDOUT … :21` logo depois de uma conexão que funcionou | A KingHost bloqueia o IP por alguns minutos depois de um login falho (ex.: login anônimo por URL malformada `host./…`) ou de muitas conexões seguidas. | Caminhos normalizados, poucas conexões, pausa de 20 s e segunda tentativa depois de 60 s (já no workflow). |
| `Login incorrect` / `530` / `curl: (67)` | Usuário ou senha errados, ou senha cadastrada em *Variables* em vez de *Secrets*. | Corrigir em *Secrets*. Depois de erros repetidos, espere alguns minutos antes de tentar de novo (bloqueio de IP). |
| Variáveis criadas, mas o log continua igual | Foram criadas na aba errada (*Secrets* ou *Environment*). | Criar em *Variables → Repository variables* e usar **Re-run all jobs**. |
| `/sampa-tech/` dá **403 Forbidden** e `/sampa-tech/public/` abre **sem CSS** | O `.htaccess` da raiz da pasta não foi aplicado (aconteceu com a pasta criada pelo webhook). | O [index.php](../index.php) de reserva serve o site e aponta os assets para `/sampa-tech/public/assets/…`. Recriar a pasta pelo FTP resolve. |
| Erro 500 depois de mexer no `.htaccess` | `php_value`/`php_flag` não são aceitos no PHP-FPM da KingHost. | Não usar essas diretivas. |
| Conferência final falha no botão do Vai Tec | O link em `vai_tec.link` está vazio ou não é `https://` (a rota responde 404 de propósito). | Corrigir o link em [app/content/sampa-tech.php](../app/content/sampa-tech.php). |

**Para ler o diagnóstico sem abrir o log:** o resumo de cada execução em *Actions* mostra as anotações "Login OK … Raiz do FTP: …" e "Pasta de destino: …". Como o repositório é público, dá para consultar também pela API, sem login: `GET https://api.github.com/repos/caduaquinomorais/sampa-tech/check-runs/<job-id>/annotations`.

## Histórico: por que não o Git Webhook

O *Git Webhook* do painel da KingHost clona o repositório na pasta do site a cada push. Testado no projeto Teia, nesse mesmo servidor:

- publicou por um dia e depois **parou de atualizar os arquivos**, embora continuasse registrando os pushes no log do painel e o GitHub mostrasse as entregas com ✓;
- a pasta criada por ele ficou **com outro dono, invisível e inacessível ao FTP** (`550 denied`), e com o `.htaccess` da raiz **ignorado** (site em 403);
- publicava o repositório inteiro, incluindo `.git/`, `docs/` e `tests/`, e não dependia dos testes.

O deploy por GitHub Actions + FTPS resolve os três pontos e mostra erros claros. Por isso, o Sampa Tech já começa nele.

## KingHost: o que vale saber

| Item | Situação |
|---|---|
| PHP | 8.1+ obrigatório; recomendado 8.3 (painel → *Configuração PHP*). |
| `.htaccess` | Funciona (Apache). Sem `php_value`/`php_flag`. |
| HTTPS | Let's Encrypt pelo painel. HSTS enviado pelo PHP. |
| FTP | `web1160.kinghost.net`, porta 21, FTPS explícito (AUTH TLS). Certificado `*.kinghost.net` sem o intermediário na cadeia. Abre dentro de `www`. Bloqueio temporário de IP depois de falhas. |
| SFTP/SSH | Não disponíveis no plano. |

## Pendências

- [x] Segredos `FTP_USERNAME` e `FTP_PASSWORD` cadastrados (aba *Secrets*).
- [x] Ruleset do `main` ativo (bypass vazio, PR obrigatório, checks `PHP 8.1` e `PHP 8.3`, sem force push nem exclusão).
- [x] Pasta `www/sampa-tech`: acessível pelo FTP, com `app/` e `public/` e com o `.htaccess` aplicado. Não precisou ser recriada.
- [x] Primeiro deploy e verificação manual (ver [Checklist](#checklist-de-conferência)).
- [ ] Conferir no painel da KingHost (*Git Webhook*) se não há integração apontando para `sampa-tech` e removê-la, se houver.
- [ ] Textos definitivos da página (ver [PENDENCIAS.md](PENDENCIAS.md)).
