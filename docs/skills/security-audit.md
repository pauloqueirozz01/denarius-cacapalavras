# Auditoria de segurança

Checklist por entrega: autenticação, autorização, CSRF, XSS, SQL injection, mass assignment, IDOR, fixação de sessão, rate limiting, validação, erros, secrets, headers e dependências.

Execute `composer audit`, `pnpm audit --audit-level=high`, testes de acesso e inspeção das rotas. Classifique achados por impacto e exploração; corrija riscos altos antes do evento.
