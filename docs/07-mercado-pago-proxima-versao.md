# 7 · Mercado Pago (próxima versão)

Nesta versão o pagamento é **fictício** (código `COMPREAQUI`). O que já está pronto, seguindo a [especificação](especificacao-pagamentos.md):

- **Lorié › Pagamentos**: ligar/desligar Pix, ligar/desligar cartão, máximo de parcelas, regra "nunca ficar sem forma de pagamento" e **histórico de alterações** (quem, quando, o quê).
- `Lorie_Payments::mercado_pago_payment_methods()` já monta, no servidor, a configuração de meios aceitos para cada novo checkout.
- **Status financeiro separado** do status de atendimento (`pending`, `approved`, `refunding`, `refunded`, `refund_failed`) e **histórico auditável** por pedido.
- **Reembolso pelo painel** com confirmação explícita, trava contra cliques repetidos e estados "em processamento/concluído/falhou". Já preparado para confirmação assíncrona (`_lorie_refund_async`).

### Passos da integração (quando for a hora)
1. Criar a conta de vendedor e as credenciais **de teste** no <https://www.mercadopago.com.br/developers> (Checkout Pro).
2. Guardar `Access Token` e o segredo do webhook **apenas** no `wp-config.php` (`LORIE_MP_ACCESS_TOKEN`, `LORIE_MP_WEBHOOK_SECRET`).
3. Criar o gateway `lorie_mercadopago`: ao finalizar, criar a *preference* no servidor com `payment_methods` vindo do painel e redirecionar ao checkout hospedado.
4. Criar o endpoint de **webhook**, validar a assinatura, **consultar o pagamento na API** e só então marcar como pago (idempotente).
5. Implementar `process_refund()` chamando a API de reembolso e marcando `refunding` até a confirmação.
6. Testar todos os critérios da seção 11 da especificação em sandbox antes de usar as credenciais de produção, e então desligar o pagamento de teste.

> Os nomes exatos de campos da API devem ser confirmados na documentação oficial vigente no momento da implementação.
