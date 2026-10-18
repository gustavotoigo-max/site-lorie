# Especificação de requisitos — Pagamentos e pedidos da loja de crochê

**Status:** decisões consolidadas da conversa; documento-base para implementação futura por IA.  
**Escopo deste documento:** sistema de pagamento, tratamento de pedidos e personalizações, estornos e controles administrativos relacionados.  
**Importante:** a aparência da loja, as telas/janelas, os componentes visuais, o layout e a navegação **ainda não foram definidos**. Não gerar ou assumir uma solução visual definitiva a partir deste documento.

## 1. Contexto

O site será de uma artesã que produz e vende **bolsas e roupas de crochê**. O volume inicial de pedidos deve ser baixo e os valores unitários relativamente modestos. A solução deverá minimizar custos fixos e a complexidade operacional.

A loja oferecerá produtos com **cores e tamanhos padrão**. O cliente também poderá solicitar modificações personalizadas. A viabilidade dessas modificações será avaliada manualmente pela artesã **depois de realizado o pagamento**.

## 2. Decisões já confirmadas

1. **Provedor pretendido:** Mercado Pago, utilizando um checkout hospedado (Checkout Pro) e integração de servidor por API. Não coletar nem armazenar números de cartão no site.
2. **Meios de pagamento:** Pix e cartão de crédito, selecionáveis conforme configuração da loja.
3. **Pagamento antecipado:** o comprador realiza o pagamento antes de a artesã verificar se uma personalização é possível.
4. **Personalizações:** devem poder ser informadas no contexto do pedido; a artesã analisa a solicitação após recebê-la.
5. **Aceitação ou recusa:** quando a personalização for viável, o pedido segue normalmente; quando não for, a artesã poderá solicitar **reembolso manual pelo painel administrativo do site**, sem precisar executar o processo manualmente no portal do provedor.
6. **Notificação / WhatsApp:** deseja-se encaminhar o pedido à artesã via WhatsApp após o pagamento, contendo os detalhes da encomenda. O método exato de envio ainda não foi definitivamente escolhido (mensagem pré-preenchida iniciada pelo cliente ou envio automático por serviço de mensagens).
7. **Gerenciamento dos pagamentos:** no painel administrativo, a artesã poderá **ativar ou desativar Pix**, **ativar ou desativar cartão de crédito** e **definir um limite máximo de parcelas**.
8. **Efeito das configurações:** meios desativados não deverão ser oferecidos no fluxo de compra, incluindo a página de checkout hospedada, nas condições suportadas pela API do Mercado Pago. As configurações devem ser aplicadas a novos checkouts pelo backend, não apenas ocultadas visualmente.
9. **Autonomia:** a artesã fará essas mudanças pelo painel, sem editar o código.
10. **Cobrança:** preferência por modelo de taxas por transação, sem mensalidade fixa do processador de pagamentos. Hospedagem, domínio, eventuais serviços de mensagem e taxas de transação são custos distintos.

## 3. Fluxo de compra esperado

1. O cliente escolhe produto e as variantes padrão disponíveis (por exemplo, cor e tamanho).
2. Se desejar, informa uma solicitação de personalização vinculada ao pedido; a interface exata de entrada desses dados ainda será definida.
3. O site registra um pedido com identificação única, itens, variantes, observações, valores e status, antes de iniciar o pagamento.
4. O backend cria uma sessão/ordem de checkout no Mercado Pago respeitando os métodos de pagamento habilitados e as regras de parcelamento.
5. O cliente paga via Pix ou cartão; o provedor processa a transação.
6. O backend recebe uma notificação de pagamento (*webhook*), verifica a autenticidade da notificação e consulta o provedor para confirmar o status real da transação. Apenas após confirmação o pedido recebe status de pago.
7. A artesã é informada do pedido com todas as opções e personalizações para avaliá-lo. Caso seja possível, dá continuidade à encomenda; caso seja impossível, acessa o painel e inicia o reembolso.

**Regra central:** a existência de uma observação de personalização não altera a exigência de pagamento antecipado; tampouco significa que a personalização foi automaticamente aprovada.

## 4. Reembolso / estorno

### 4.1 Requisitos funcionais

- Disponibilizar, para pedidos elegíveis e já pagos, uma ação administrativa de **reembolso**.
- A operação é **iniciada manualmente pela artesã**, e não automaticamente pela simples recusa da personalização.
- Antes de executar, mostrar a identificação do pedido, valor a devolver, meio de pagamento e uma confirmação explícita.
- O backend chama a API apropriada do Mercado Pago para solicitar a devolução. **Nunca expor token privado ou credencial da API no navegador.**
- Registrar a solicitação, seu identificador no provedor, o resultado e os eventos posteriores, caso a confirmação seja assíncrona.
- Diferenciar claramente estados como **reembolso solicitado**, **em processamento**, **concluído** e **falhou**. Não marcar como concluído antes da confirmação do provedor.
- Evitar solicitações duplicadas por cliques repetidos ou reenvio de mensagens (idempotência).
- Atualizar o pedido e manter histórico auditável da operação, incluindo o usuário administrativo responsável.
- Não prometer reembolso instantâneo na interface ao comprador.

### 4.2 Expectativas de prazo

- **Pix:** normalmente a devolução é rápida, podendo ocorrer em minutos, mas **não há garantia de instantaneidade** em todas as situações.
- **Cartão de crédito:** a solicitação pode ser processada pelo provedor rapidamente, mas o crédito ao comprador depende do emissor do cartão e do ciclo da fatura; poderá demorar até faturas subsequentes. **Não prometer crédito instantâneo.**
- Os prazos de elegibilidade para solicitar estorno e os prazos efetivos de restituição são conceitos diferentes. Validar ambos na documentação e nas condições vigentes da conta antes de publicar prazos comerciais.
- A disponibilidade de saldo para executar o reembolso deve ser tratada conforme as regras vigentes do Mercado Pago; prever falhas e orientação para resolução.

## 5. Controles administrativos de pagamento

Manter configurações persistidas no banco de dados, por exemplo:

```json
{
  "pix_enabled": true,
  "credit_card_enabled": true,
  "max_installments": 3
}
```

Esses nomes são apenas **proposta de estrutura interna**, não contrato fechado de API.

Regras:

- O backend deve consultar as configurações efetivas ao criar cada novo checkout.
- Não permitir que a loja fique sem qualquer forma de pagamento ativa, salvo se futuramente for definido explicitamente um modo de suspensão de vendas.
- Se cartão estiver desativado, não apresentar parcelamento ao comprador.
- Se o limite de parcelas mudar, aplicá-lo apenas a novos checkouts; não alterar transações anteriores.
- Controlar os meios aceitos **no provedor**, conforme os campos e identificadores documentados na versão vigente da integração; esconder botões no front-end não é suficiente.
- Definir tratamento seguro para checkouts já emitidos antes da mudança: sempre que possível, invalidá-los ou impedir a confirmação de pedidos incompatíveis; não presumir que a mudança retroage sobre links de pagamento já gerados.
- Registrar alterações de configuração no histórico administrativo.

**Nota de implementação:** os nomes exatos dos campos da API, os tipos de métodos de pagamento e as opções de parcelamento devem ser confirmados diretamente na documentação oficial do **Checkout Pro / API atualmente recomendada** durante a implementação. Não tratar exemplos de payload apresentados em conversas como contratos definitivos da API.

## 6. Dados mínimos do pedido

- Identificador interno único do pedido.
- Data de criação e de atualização.
- Itens adquiridos, quantidades, valores e variantes padrão selecionadas.
- Texto das personalizações solicitadas, associado aos itens ou ao pedido, conforme definição posterior.
- Informações de contato necessárias ao atendimento e ao WhatsApp, respeitando a LGPD.
- Total do pedido e, quando aplicável, frete e demais componentes do preço.
- Identificadores da ordem/preferência e do pagamento no Mercado Pago.
- Meio de pagamento utilizado e status financeiro confirmado.
- Status do pedido / da avaliação de personalização.
- Dados mínimos das solicitações de reembolso, resultados e histórico.

A modelagem final do banco de dados, o formato de cadastro de cliente, a logística e os campos de endereço serão definidos posteriormente.

## 7. Estados sugeridos (a validar na modelagem)

**Pagamento:** `pending`, `approved`, `rejected`, `refunding`, `refunded`, `refund_failed`.

**Atendimento:** `received`, `personalization_under_review`, `accepted`, `in_production`, `cancelled`, `completed`.

Separar os estados financeiros dos estados operacionais para evitar interpretações equivocadas (por exemplo, um pagamento aprovado com personalização ainda não avaliada). Esses nomes são sugestões técnicas, não decisões de interface.

## 8. Cuidados obrigatórios na implementação

- Autenticação e autorização para operações administrativas; somente usuários autorizados podem configurar pagamentos e estornar.
- HTTPS em todos os fluxos reais.
- Armazenar credenciais do Mercado Pago exclusivamente em ambiente seguro do servidor.
- Validar eventos e estados recebidos do Mercado Pago; não confiar apenas no redirecionamento do navegador depois do checkout.
- Proteger a aplicação contra duplicidade de pedidos, pagamentos e estornos; usar mecanismos de idempotência e persistência transacional quando necessário.
- Não armazenar dados sensíveis de cartão no site.
- Usar ambiente de teste/sandbox e transações simuladas antes de usar credenciais de produção.
- Prever logs e rastreabilidade sem expor dados pessoais ou credenciais.
- Cumprir regras aplicáveis de privacidade e disponibilizar informações transparentes sobre cancelamento e devolução.

## 9. Pontos ainda NÃO definidos (não presumir)

- Layout, identidade visual, páginas, janelas, modais, organização dos menus e navegação.
- Tecnologia de front-end, backend, hospedagem ou banco de dados. React, Node.js e Supabase foram **sugeridos**, mas não confirmados pelo usuário.
- Processo definitivo de envio de pedidos ao WhatsApp: botão para comprador enviar mensagem ou mensagem automática via integração externa.
- Se haverá painel completo de cadastro/edição de produtos e estoque, além do painel financeiro; recursos foram discutidos como possibilidades, sem confirmação detalhada.
- Regras de frete, estoque, regiões atendidas, prazo de fabricação e envio.
- Política de desconto, acréscimo e juros para parcelamento.
- Política de precificação de personalizações e possibilidade de ajuste de valor depois da avaliação.
- Autorização de reembolso parcial: a API pode oferecer suporte, mas o requisito confirmado até agora é reembolsar pedidos inviáveis por decisão da artesã.
- Notificações adicionais ao cliente (e-mail, WhatsApp automático, SMS), além do fluxo pretendido.
- Políticas comerciais e legais exatas de troca, devolução e direito de arrependimento.

## 10. Dependências e validações externas

Antes de desenvolver a integração de produção:

1. Confirmar disponibilidade, documentação e parâmetros atuais do **Mercado Pago Checkout Pro** para o tipo de conta da artesã.
2. Confirmar a forma oficial de desabilitar Pix e cartão no checkout e de limitar parcelas para novas transações.
3. Confirmar taxas efetivas da conta, eventuais promoções, regras de parcelamento e prazos de recebimento. **Não fixar taxas percentuais da conversa como valores permanentes.**
4. Confirmar endpoints, elegibilidade, saldo exigido e retornos de status para reembolsos Pix e cartão.
5. Confirmar a política de expiração/invalidação de checkouts gerados antes de mudanças de configuração.
6. Testar o fluxo de webhooks, transições de status, falhas de processamento e repetição de notificações.

Referência inicial: https://www.mercadopago.com.br/developers/pt/docs/checkout-pro

## 11. Critérios de aceite funcionais

- Uma compra por Pix aprovada é identificada e registrada corretamente no pedido.
- Uma compra por cartão aprovada é identificada e registrada corretamente no pedido.
- O texto de personalização permanece associado ao pedido pago e acessível à artesã.
- A artesã pode iniciar um reembolso integral de um pedido pago que não poderá atender.
- O sistema informa corretamente o resultado efetivo do estorno e não presume devolução imediata.
- Ao desativar cartão, novos checkouts não oferecem cartão; ao reativar, novos checkouts voltam a oferecê-lo.
- Ao desativar Pix, novos checkouts não oferecem Pix; ao reativar, novos checkouts voltam a oferecê-lo.
- O limite máximo de parcelas é respeitado em novos checkouts quando o cartão está habilitado.
- Usuários não administrativos não podem alterar métodos de pagamento nem solicitar reembolsos.
- Duplicações de notificações ou de cliques não geram operações financeiras repetidas.

---

**Orientação para a IA implementadora:** considerar os itens da seção 2 como decisões do negócio, as seções 3–8 e 11 como requisitos funcionais/técnicos derivados a implementar e validar, e as seções 9–10 como pendências. Não transformar decisões em aberto em requisitos supostamente aprovados; pedir definição quando essas decisões forem necessárias. Priorizar confiabilidade financeira e segurança sobre conveniência visual.
