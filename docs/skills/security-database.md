# Segurança de banco

- Use Eloquent/query builder, bindings e listas permitidas; nunca concatene input em SQL.
- Defina `$fillable`/`$guarded`, casts, foreign keys, constraints e índices.
- Use transactions e locks quando concorrência puder duplicar acertos ou conclusão.
- Não aceite IDs sem escopo do usuário; previna IDOR por query e policy.
- Credenciais locais são descartáveis; produção usa secret manager e privilégio mínimo.
- Backups e migrations destrutivas exigem procedimento explícito.
