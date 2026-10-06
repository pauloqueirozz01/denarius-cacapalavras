# Segurança backend

- Use autenticação de sessão Laravel, hashing oficial, regeneração no login e invalidação no logout.
- Aplique middleware, policies/gates e ownership em toda sessão de jogo.
- Rate limit login, cadastro, início e submissão de seleções.
- Aceite coordenadas; recalcule palavra, pontos e tempo no servidor.
- Não retorne grid metadata que revele posições ainda não encontradas.
- Registre eventos de segurança sem senhas, tokens ou PII desnecessária.
