# Segurança frontend

- Preserve CSRF do Laravel/Livewire e escape conteúdo com `{{ }}`.
- Evite `{!! !!}`, `x-html` e HTML dinâmico; sanitize quando inevitável.
- Não confie em estado Alpine para regras, pontos, tempo ou autorização.
- Exiba erros genéricos ao usuário e mantenha detalhes em logs protegidos.
- Componentes interativos devem ter foco visível, labels e feedback além de cor.
