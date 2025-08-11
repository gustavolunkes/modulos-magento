# MageShop_StoreSchedule - Changelog

## [0.2.0] - 2025-07-08

### Adicionado

- Campos multiselect no admin para definir métodos de envio permitidos:
  - Quando a loja estiver aberta
  - Quando o delivery estiver disponível
- Source model para listar métodos de envio ativos dinamicamente

### Alterado

- Observer `checkShippingMethods` agora considera os métodos configurados no admin, em vez de usar métodos fixos.

---

## [0.1.1] - 2025-07-07

## Corrigido

- Timestamp nos métodos do helper, não estavam respeitando o horário do magento

---

## [0.1.0] - 2025-06-28

### Criado

- Primeira versão do módulo
- Controle de horários por dia da semana
- Verificação se a loja está aberta ou não via helper
- Lógica inicial para exibir/remover métodos de envio com base no horário
