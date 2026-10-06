# Estratégia de testes

- PHPUnit/Pest cobre regras novas; preferir testes determinísticos e rápidos.
- Unit: normalização, gerador e `ScoreCalculator`.
- Feature: autenticação, ownership, partida, validação, conclusão, ranking e admin.
- Gerador deve aceitar fonte aleatória controlável para reproduzir direções e colisões.
- Cubra happy path, limites, erro principal, duplicidade, lista insuficiente e concorrência relevante.
- Rode testes em SQLite quando equivalentes e mantenha uma verificação de integração em MySQL.
