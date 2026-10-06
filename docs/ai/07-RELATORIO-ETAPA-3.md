# Relatório da Etapa 3 — Catálogo de termos financeiros

## Identificação

- Branch: `feat/financial-terms-catalog`
- Commit da implementação: `eb639df` (`feat: add financial terms catalog`)
- Data da entrega: 6 de outubro de 2026
- Situação: concluída e publicada no repositório remoto

## Objetivo

Disponibilizar um catálogo administrativo de termos de educação financeira que possa alimentar o futuro gerador de caça-palavras. A entrega precisava preservar uma forma amigável para exibição, gerar automaticamente uma forma segura para o tabuleiro e restringir toda a gestão a administradores.

## Funcionalidades implementadas

### Modelo e persistência

- Criado o modelo `FinancialTerm` com termo de exibição, termo normalizado, descrição educativa, dificuldade e estado ativo.
- Criada a migration da tabela `financial_terms`.
- Adicionada restrição `UNIQUE` para `normalized_term`, garantindo integridade também no banco de dados.
- Adicionados casts para dificuldade e estado ativo.
- Adicionados scopes para consultar termos ativos e filtrar por dificuldade.
- Criada factory com estados coerentes para testes e desenvolvimento.

### Normalização para o caça-palavras

- Criado o serviço `FinancialTermNormalizer`.
- A normalização remove acentos, espaços, números e símbolos, converte o conteúdo para maiúsculas e mantém somente letras entre `A` e `Z`.
- O tamanho máximo da forma normalizada foi definido em 24 letras, compatível com o grid planejado.
- Criado um observer Eloquent para aplicar a normalização em toda gravação do modelo.
- `normalized_term` não pode ser definido por mass assignment.
- Termos vazios após a normalização e termos acima do limite são rejeitados antes da persistência.
- Criada uma regra de validação que impede termos equivalentes, inclusive quando diferem apenas por acentos, espaços ou símbolos.

Exemplo: `Patrimônio Líquido` é armazenado para exibição nessa forma e gera `PATRIMONIOLIQUIDO` para uso no tabuleiro.

### Dificuldades

- Criado o enum `FinancialTermDifficulty` com os níveis `easy`, `medium` e `hard`.
- Os níveis possuem rótulos em português: Fácil, Médio e Difícil.
- Cores administrativas foram associadas aos níveis para facilitar a leitura no Filament.

### Catálogo inicial

- Criado um seeder com 90 termos financeiros e descrições educativas.
- Cada termo possui uma dificuldade definida.
- O seeder utiliza `firstOrCreate`, podendo ser executado novamente sem duplicar registros.
- Alterações feitas posteriormente por administradores são preservadas em novas execuções do seeder.
- O `FinancialTermSeeder` foi integrado ao `DatabaseSeeder`.

### Administração com Filament

- Criado o resource de termos financeiros no painel `/admin`.
- Implementadas páginas de listagem, criação, visualização e edição.
- A listagem permite busca, ordenação e filtros por dificuldade e estado ativo.
- A forma normalizada é exibida para conferência, mas é calculada pelo servidor e não aceita edição direta.
- Implementadas ativação e desativação rápida, exclusão individual e exclusão em lote.
- A descrição educativa é renderizada com escaping, evitando execução de HTML inserido no conteúdo.
- Não foi criada rota ou API pública para o catálogo nesta etapa.

### Autorização e segurança

- Criada uma policy explícita para o modelo `FinancialTerm`.
- Somente usuários com papel `admin` podem listar, visualizar, criar, editar, excluir ou restaurar termos.
- Participantes não podem acessar o resource administrativo.
- A proteção existe na autorização do servidor e não depende apenas da ocultação de elementos da interface.
- A validação da aplicação é complementada pela unicidade no banco, protegendo contra gravações concorrentes.

## Testes automatizados

A etapa adicionou cobertura para:

- normalização de acentos, espaços e símbolos;
- garantia de saída contendo apenas letras ASCII maiúsculas;
- criação e casts do modelo;
- proteção contra mass assignment de `normalized_term`;
- scopes de estado ativo e dificuldade;
- rejeição de termos equivalentes;
- rejeição de valores sem letras e acima do limite do grid;
- criação dos 90 itens pelo seeder;
- idempotência do seeder e preservação de alterações administrativas;
- permissões da policy para administradores e participantes;
- acesso ao resource Filament;
- criação, edição, ativação, desativação e exclusão pelo painel;
- busca e filtros administrativos;
- validação de duplicidade no formulário;
- escaping da descrição na página de visualização.

Validação executada em 6 de outubro de 2026:

```text
38 testes aprovados
624 assertions
0 falhas
```

## Principais arquivos da entrega

- `app/Models/FinancialTerm.php`
- `app/Enums/FinancialTermDifficulty.php`
- `app/Services/FinancialTermNormalizer.php`
- `app/Observers/FinancialTermObserver.php`
- `app/Rules/UniqueNormalizedFinancialTerm.php`
- `app/Policies/FinancialTermPolicy.php`
- `app/Filament/Resources/FinancialTerms/`
- `database/migrations/2026_10_06_152804_create_financial_terms_table.php`
- `database/factories/FinancialTermFactory.php`
- `database/seeders/FinancialTermSeeder.php`
- `tests/Feature/FinancialTerm/`
- `tests/Feature/Filament/FinancialTermResourceTest.php`
- `tests/Unit/Policies/FinancialTermPolicyTest.php`
- `tests/Unit/Services/FinancialTermNormalizerTest.php`

## Resultado

O projeto possui agora uma fonte de dados administrativa, validada e protegida para os termos que serão usados nas partidas. A próxima etapa é implementar o gerador determinístico do caça-palavras, com posicionamento nas oito direções e testes de borda e colisão.
