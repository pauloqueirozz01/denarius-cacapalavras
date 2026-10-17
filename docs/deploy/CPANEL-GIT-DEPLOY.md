# Passo a passo — deploy no cPanel via Git™ Version Control

Guia operacional para publicar o Denarius Caça-Palavras em `https://gamefinanceiro.denariusstartup.com.br` usando o cPanel da conta `denarius`. Os scripts e a visão geral da branch `deploy` estão em [CPANEL-DEPLOY.md](CPANEL-DEPLOY.md#deploy-via-git-version-control-gamefinanceirodenariusstartupcombr); o template completo do `.env` está em [PRODUCTION-ENV.md](PRODUCTION-ENV.md).

## Como funciona

1. A branch `deploy` do GitHub contém só a release pronta: código, `vendor/` gerado em PHP 8.4, `public/build/`, `.cpanel.yml` e `cpanel-deploy.sh`.
2. O Git™ Version Control do cPanel mantém um clone dessa branch em `~/repositories/denarius-cacapalavras`.
3. **Deploy HEAD Commit** executa o `.cpanel.yml`, que chama o `cpanel-deploy.sh`. O script copia a release para `~/gamefinanceiro.denariusstartup.com.br`, roda as migrations e gera os caches.

```text
~/repositories/denarius-cacapalavras/      clone gerenciado pelo cPanel (nunca editar)
~/gamefinanceiro.denariusstartup.com.br/   aplicação em produção
├── .env                                   configuração e segredos (criado à mão)
├── storage/                               logs, sessões e caches (preservado)
└── public/                                Document Root do subdomínio
~/denarius-deploy.log                      saída de cada deploy
```

## Antes de começar

Confirme no cPanel da conta `denarius`:

- **Diretório inicial** (barra lateral do cPanel) é `/home1/denarius`. Se for outro, o `.cpanel.yml` precisa ser ajustado e uma nova release gerada antes do primeiro deploy.
- **Git™ Version Control** aparece em *Arquivos*.
- **MultiPHP Manager** (em *Software*) oferece PHP 8.4. O `composer.lock` exige PHP 8.4.1 ou superior.
- **Terminal** ou acesso SSH está habilitado para a conta. Sem acesso shell, o cPanel só permite criar, clonar e visualizar repositórios; os botões de atualização e deploy podem ficar indisponíveis.

Se algum item faltar, solicite ao administrador do servidor antes de seguir.

## 1. Backup do banco

O primeiro deploy executa `php artisan migrate --force` no banco existente.

1. Abra **Bancos de dados » phpMyAdmin**.
2. Selecione `denarius_gamefinanceiro` e use **Exportar** (método rápido, formato SQL).
3. Guarde o arquivo fora do servidor.

Repita antes de cada deploy que contenha migrations novas.

## 2. Banco e usuário

O banco `denarius_gamefinanceiro` e o usuário `denarius_financeuser` são reaproveitados.

1. Abra **Bancos de dados » Bancos de dados MySQL®**.
2. Em *Bancos de dados atuais*, confirme que `denarius_financeuser` aparece em *Usuários privilegiados* de `denarius_gamefinanceiro`.
3. Se não aparecer, em *Adicionar usuário ao banco de dados*, associe o usuário ao banco e marque **TODOS OS PRIVILÉGIOS**.
4. Se a senha do usuário não for conhecida, troque-a em *Usuários atuais » Alterar senha* e use a nova no `.env`.

## 3. Subdomínio

1. Abra **Domínios » Domínios » Criar um novo domínio**.
2. Domínio: `gamefinanceiro.denariusstartup.com.br`.
3. Desmarque **Compartilhar raiz do documento**.
4. Raiz do documento: `gamefinanceiro.denariusstartup.com.br/public`.
5. Clique em **Enviar**.

A raiz precisa ser a pasta `public` da aplicação. Nunca apontar para a pasta da aplicação nem copiar a aplicação para dentro de `public_html`. Se o cPanel recusar uma raiz fora de `public_html`, pare e solicite a liberação ao administrador do servidor.

Se o DNS de `denariusstartup.com.br` estiver fora deste servidor, crie no provedor de DNS um registro **A** do subdomínio apontando para o IP do servidor.

## 4. Versão do PHP

1. Abra **Software » MultiPHP Manager**.
2. Marque `gamefinanceiro.denariusstartup.com.br`.
3. Em *Versão do PHP*, escolha **PHP 8.4** (`ea-php84`) e clique em **Aplicar**.

Depois do primeiro deploy, abra `~/gamefinanceiro.denariusstartup.com.br/public/.htaccess` no Gerenciador de arquivos. Se o servidor usar um handler sem PHP-FPM, deve existir um bloco `# php -- BEGIN cPanel-generated handler`. O script de deploy preserva esse bloco. Com PHP-FPM ele não existe, e isso é esperado.

## 5. HTTPS

1. Abra **Segurança » SSL/TLS Status**.
2. Confirme que `gamefinanceiro.denariusstartup.com.br` tem certificado válido do AutoSSL. Se não tiver, selecione o domínio e clique em **Run AutoSSL**.

O `.env` de produção usa `https` e `SESSION_SECURE_COOKIE=true`. Sem certificado válido, o login falha com erro 419. Se for necessário subir antes do certificado, use `APP_URL=http://...` e `SESSION_SECURE_COOKIE=false` só para teste, com contas descartáveis.

## 6. Arquivo `.env`

O `.env` precisa existir antes do primeiro deploy; sem ele o script aborta sem alterar nada.

1. Abra **Arquivos » Gerenciador de arquivos** e entre em `gamefinanceiro.denariusstartup.com.br/` (a pasta criada com o subdomínio, fora de `public`).
2. Envie o `.env` de produção preparado localmente ou crie o arquivo com o template de [PRODUCTION-ENV.md](PRODUCTION-ENV.md), ajustando:
   - `APP_URL=https://gamefinanceiro.denariusstartup.com.br`
   - `SESSION_SECURE_COOKIE=true`
   - `DB_DATABASE=denarius_gamefinanceiro`, `DB_USERNAME=denarius_financeuser` e `DB_PASSWORD` entre aspas simples
   - `APP_KEY` gerada uma única vez para esta instalação, nunca a chave local de desenvolvimento
3. Preencha `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD` para criar o primeiro administrador. A senha precisa de 12 ou mais caracteres, com maiúscula, minúscula, número e símbolo.
4. Clique com o botão direito no `.env » Change Permissions` e defina **600**.

Nunca colocar o `.env` dentro de `public/` nem registrar senhas e a `APP_KEY` no Git.

## 7. Clonar o repositório

1. Abra **Arquivos » Git™ Version Control » Criar**.
2. Ative **Clonar um repositório**.
3. URL do clone: `https://github.com/pauloqueirozz01/denarius-cacapalavras.git`
4. Caminho do repositório: `repositories/denarius-cacapalavras`
5. Nome do repositório: `denarius-cacapalavras`
6. Clique em **Criar** e aguarde o fim da clonagem.
7. Em **Gerenciar**, selecione a branch **`deploy`** em *Branch em checkout*.

O caminho precisa estar vazio, sem espaços e fora de qualquer raiz pública.

## 8. Primeiro deploy

1. Em **Git™ Version Control », Gerenciar » Pull or Deploy**, clique em **Deploy HEAD Commit**.
2. Abra `~/denarius-deploy.log` no Gerenciador de arquivos. O deploy terminou bem se a última linha for `=== deploy concluído`.

Neste primeiro deploy, o script:

- copia a aplicação para `~/gamefinanceiro.denariusstartup.com.br`;
- executa as migrations;
- cadastra o catálogo inicial de termos financeiros (só no primeiro deploy);
- cria o administrador com os dados `ADMIN_*`;
- **não** gera o cache de configuração e registra dois avisos `AVISO:` no log.

## 9. Remover os dados do administrador

1. No `.env`, deixe vazias as três variáveis:

   ```dotenv
   ADMIN_NAME=
   ADMIN_EMAIL=
   ADMIN_PASSWORD=
   ```

2. Clique em **Deploy HEAD Commit** de novo.
3. Confirme no log que não há `AVISO:` e que terminou com `=== deploy concluído`.

Este segundo deploy gera os caches de produção. Se ele fosse feito com `ADMIN_PASSWORD` preenchida, a senha ficaria em texto puro em `bootstrap/cache/config.php`.

## 10. Verificação

- `https://gamefinanceiro.denariusstartup.com.br/`, `/login`, `/register` e `/up` abrem sem erro.
- `/admin` redireciona para `/admin/login`; o administrador criado consegue entrar.
- Uma partida pode ser iniciada, concluída e aparece no ranking.
- Uma URL inexistente mostra a página 404 em português, sem stack trace.
- `https://gamefinanceiro.denariusstartup.com.br/.env` não é acessível.

O roteiro completo de smoke tests está em [CPANEL-DEPLOY.md](CPANEL-DEPLOY.md#smoke-tests-obrigatórios-após-publicação).

## Próximas atualizações

Depois que uma nova release for publicada na branch `deploy` do GitHub:

1. Faça backup do banco se houver migrations novas.
2. Em **Git™ Version Control » Gerenciar » Pull or Deploy**, clique em **Update from Remote**.
3. Clique em **Deploy HEAD Commit**.
4. Confira `~/denarius-deploy.log`.

O **Update from Remote** só funciona por fast-forward. Nunca editar arquivos dentro de `~/repositories/denarius-cacapalavras`: com alterações locais, o cPanel recusa o deploy.

## Problemas comuns

| Sintoma | Causa provável | O que fazer |
|---|---|---|
| Botões de Pull/Deploy indisponíveis | Conta sem acesso shell ou sem `.cpanel.yml` na branch | Confirmar o acesso shell da conta e que a branch em checkout é `deploy`. |
| Log com `ERRO: Crie .../.env` | `.env` ausente | Fazer o passo 6 e repetir o deploy. |
| Log com `ERRO: PHP CLI não encontrado` | PHP 8.4 não instalado no servidor | Solicitar o `ea-php84` ao administrador do servidor. |
| Log sem nada novo após o deploy | Diretório inicial diferente de `/home1/denarius` | Ajustar `.cpanel.yml` e publicar nova release. |
| Site mostra "em manutenção" | Deploy falhou depois da cópia | Ler o log, corrigir a causa e clicar em **Deploy HEAD Commit** de novo. |
| Erro 419 no login | HTTPS ausente com `SESSION_SECURE_COOKIE=true` | Concluir o passo 5. |
| Erro 500 em todas as páginas | Subdomínio usando PHP abaixo de 8.4.1 | Refazer o passo 4 e conferir o bloco de handler no `public/.htaccess`. |
| **Update from Remote** falha | Branch `deploy` reescrita ou alteração local no clone | Não usar `push --force`; não editar o clone. |

## Referências

- [Git™ Version Control (cPanel)](https://docs.cpanel.net/cpanel/files/git-version-control/)
- [Guide to Git™ Deployment](https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-deployment/)
- [Domains (cPanel)](https://docs.cpanel.net/cpanel/domains/domains/)
- [Laravel 13 — Deployment](https://laravel.com/docs/13.x/deployment)
