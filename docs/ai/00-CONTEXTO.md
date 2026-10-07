# Contexto do projeto

## Produto

Denarius Finance Game é um MVP para eventos da Denarius EdTech. O fluxo-alvo é cadastro, login, partida aleatória de caça-palavras financeiros, resultado e ranking dinâmico. O posicionamento é **Educação Financeira Criativa**.

## Escopo

O MVP inclui autenticação, catálogo de termos, jogo responsivo, pontuação calculada no servidor, ranking com polling, mascote substituível, tutorial e administração Filament. Pagamentos, moedas virtuais, chat, IA, microserviços, app mobile e WebSockets estão fora do escopo.

## Estado em 2026-10-06

- Repositório recebido vazio.
- Laravel 13.34.0 inicializado sobre PHP 8.5.11.
- Livewire 4.4.7 e Filament 5.9.0 instalados como dependências de fundação.
- Laravel Boost 2.10.2 instalado para guidelines, skills e MCP de desenvolvimento.
- MySQL 8.4 e phpMyAdmin 5.2 definidos em Docker Compose.
- Autenticação de sessão, papéis `admin`/`participant`, rota protegida `/game` e acesso administrativo Filament estão implementados.
- Catálogo com 90 termos financeiros educativos, dificuldades, ativação e gestão administrativa Filament está implementado.
- Motor backend do caça-palavras implementado com grid configurável, seleção de termos ativos, oito direções e geração determinística em testes.
- Domínio persistente de partidas implementado com snapshots do grid e das palavras, estados explícitos, actions transacionais, autorização por proprietário e validação server-side das seleções.
- Interface jogável Livewire implementada em `/game`, com início/retomada, grid persistido, Pointer Events para mouse/toque, progresso, cronômetro visual, tutorial, conclusão e abandono.
- Pontuação autoritativa implementada com pontos por palavra, bônus de conclusão/velocidade, snapshot da fórmula por partida e exibição persistida na interface.
- Ranking autenticado implementado com melhor partida concluída por participante, posição individual, atualização Livewire periódica e retorno ao jogo após a conclusão.
- Experiência visual refinada na Etapa 9 com componente reutilizável de mascote, estados visuais, tutorial com regras históricas da partida, microanimações e suporte a movimento reduzido.

## Decisões

- Aplicativo monolítico Laravel; sem SPA separada.
- Livewire/Blade para o jogo e Filament apenas em `/admin`.
- MySQL é a persistência da aplicação; SQLite em memória pode ser usado por testes unitários/feature.
- `pnpm` é o gerenciador JavaScript do projeto.
- Cadastro público sempre cria `participant`; somente o seeder configurado por ambiente cria `admin`.
- Verificação de e-mail e recuperação de senha ficam desabilitadas no MVP para reduzir atrito em eventos.
- O catálogo não possui endpoint público; o gerador consulta termos ativos diretamente no backend.
- O resultado do motor é validado e persistido uma única vez por `StartGameSessionAction`; partidas em andamento nunca regeneram o grid.
- Cada usuário pode manter no máximo uma partida ativa. A regra é revalidada dentro da transação após bloquear a linha do usuário.
- O cliente informa somente coordenadas. Status, acertos, contadores, timestamps e duração permanecem sob autoridade do servidor.
- Placements pendentes não são enviados ao navegador. Somente o grid, termos visíveis, progresso e células de palavras já encontradas compõem a interface.
- O JavaScript calcula apenas a trajetória visual do gesto e o cronômetro de exibição; o backend continua validando seleção e duração oficial.
- O score é atualizado na mesma transação do acerto. A fórmula é configurada no backend e congelada em `generation_config.scoring` para auditoria histórica.
- O ranking considera apenas participantes e partidas `COMPLETED`; cada jogador aparece uma vez pela sua melhor partida e o score é lido diretamente do banco.
- A classificação em `/ranking` atualiza sua própria área a cada 5 segundos por `wire:poll`; o resultado da partida mostra score, duração, breakdown histórico e melhor posição quando disponível.
- `x-mascot` seleciona assets por estado (`idle.webp`, `correct.webp`, `error.webp`, `celebration.webp`, `victory.webp`, `abandoned.webp`) e usa um SVG provisório original como fallback; a arte final pode substituir os arquivos sem refatorar as views.
- Roadmap consolidado do MVP: Etapa 9 concluída; Etapa 10 (Filament final, QA e segurança) e Etapa 11 (deploy e documentação final) seguem no backlog.
