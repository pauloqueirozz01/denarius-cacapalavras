# Segurança no GitHub

- Nunca adicione `.env`, dumps, chaves, tokens, logs ou credenciais.
- Antes de push: `git status`, `git diff`, `git diff --cached` e busca por padrões sensíveis.
- Use branches `feat/*`, `fix/*`, `chore/*`, commits convencionais e revisão obrigatória do diff.
- Ative proteção de `main`, secret scanning e Dependabot quando o repositório remoto estiver disponível.
- Não exponha phpMyAdmin nem `APP_DEBUG=true` em produção.
