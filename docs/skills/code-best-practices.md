# Boas práticas de código

- Use tipos, nomes de domínio e métodos pequenos; Controllers/Livewire apenas orquestram.
- Coloque casos de uso em Actions e regras reutilizáveis em Services, sem abstrações vazias.
- Valide com Form Requests ou regras Livewire e traduza mensagens para pt-BR.
- Evite N+1, selecione colunas necessárias e use transactions nos fluxos atômicos.
- Mantenha cálculo de pontuação e geração independentes da interface.
- Rode Pint, testes e build antes de cada commit.
